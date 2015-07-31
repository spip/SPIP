<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;

// fonction principale declenchant tout le service
// elle-meme ne fait que traiter les cas particuliers, puis passe la main.
function calcule_header_et_page ($fond) {
	  global $auteur_session, $forcer_lang, $ignore_auth_http,
	  $var_confirm, $var_mode;

	// multilinguisme
	if ($forcer_lang AND ($forcer_lang!=='non') AND !count($_POST)) {
		include_spip('inc/lang');
		verifier_lang_url();
	}
	if ($_GET['lang']) {
		include_spip('inc/lang');
		lang_select($_GET['lang']);
	}

	// Si envoi pour un forum, enregistrer puis rediriger

	if (strlen($_POST['confirmer_forum']) > 0
	    OR ($GLOBALS['afficher_texte']=='non' AND $_POST['ajouter_mot'])) {
		$f = include_fonction('forum_insert', 'inc');
		redirige_par_entete($f());
	}

	// si signature de petition, l'enregistrer avant d'afficher la page
	// afin que celle-ci contienne la signature

	if ($_GET['var_confirm']) {
		include_spip('balise/formulaire_signature');
		reponse_confirmation($_GET['id_article'], $var_confirm);
	}

	//  refus du debug si l'admin n'est pas connecte
	if ($var_mode=='debug') {
		if ($auteur_session['statut'] == '0minirezo')
			spip_log('debug !');
		else
			redirige_par_entete(generer_url_public('login',
			'url='.rawurlencode(
			parametre_url(self(), 'var_mode', 'debug', '&')
			), true));
	}

	return afficher_page_globale ($fond);
}


// Remplir les globals pour les boutons d'admin

function restaurer_globales ($contexte) {
	if (is_array($contexte)) {
		foreach ($contexte as $var=>$val) {
			$GLOBALS[$var] = $val;
		}
	}
}

function is_preview()
{
	global $var_mode;
	if ($var_mode !== 'preview') return false;
	$statut = $GLOBALS['auteur_session']['statut'];
	return ($statut=='0minirezo' OR
		($GLOBALS['meta']['preview']=='1comite' AND $statut=='1comite'));
}

//
// calculer la page principale et envoyer les entetes
//
function afficher_page_globale ($fond) {
	global $flag_dynamique, $flag_ob, $flag_preserver,$lastmodified,
		$use_cache, $var_mode, $var_preview;

	include_spip('public/cache');

	// Peut-on utiliser un fichier cache ?
	list($chemin_cache, $page, $lastmodified) = 
		determiner_cache($use_cache, NULL, $fond);

	if (!$chemin_cache || !$lastmodified) $lastmodified = time();

	// demande de previsualisation ?
	// -> inc-calcul n'enregistrera pas les fichiers caches
	// -> inc-boucles acceptera les objets non 'publie'
	if (is_preview()) {
			$var_mode = 'recalcul';
			$var_preview = true;
			spip_log('preview !');
		} else	$var_preview = false;

	$headers_only = ($_SERVER['REQUEST_METHOD'] == 'HEAD');

	// une perennite valide a meme reponse qu'une requete HEAD

	if ($GLOBALS['HTTP_IF_MODIFIED_SINCE'] AND !$var_mode
	AND $chemin_cache AND !$flag_dynamique) {
		if (!preg_match(',IIS/,', $_SERVER['SERVER_SOFTWARE'])) {
			$since = preg_replace('/;.*/', '',
				$GLOBALS['HTTP_IF_MODIFIED_SINCE']);
			$since = str_replace('GMT', '', $since);
			if (trim($since) == http_gmoddate($lastmodified)) {
				$status = 304;
				$headers_only = true;
			}
		}
	}

	// si le last-modified (mis + bas) est suffisant, ne meme pas mettre
	// de content-type (pour contrer le bouton admin de inc-public)

	if ($headers_only) {
		$page['entetes']["Connection"] = "close";
	} else {
		if (!$use_cache)
			restaurer_globales($page['contexte']);
		else {
			include_spip('public/calcul');
			$page = calculer_page_globale ($chemin_cache, $fond);
			if ($chemin_cache)
				creer_cache($page, $chemin_cache, $use_cache);
		}

		if ($chemin_cache) $page['cache'] = $chemin_cache;

		// compatibilite. devrait pouvoir sauter
		if ($page['process_ins'] == 'php') {
			auto_content_type($page['texte']);
			auto_expire($page['texte']);
		}

		$flag_preserver |=  (headers_sent());

	// Definir les entetes si ce n'est fait 

		if (!$flag_preserver) {

			if (!isset($page['entetes']['Content-Type'])) {
				$page['entetes']['Content-Type'] = 
					"text/html; charset="
					. $GLOBALS['meta']['charset'];
			}
			if ($flag_ob) {
			// Si la page est vide, produire l'erreur 404
				if (trim($page['texte']) === ''
				    AND $var_mode != 'debug') {
					$page = message_erreur_404();
					$status = 404;
					$flag_dynamique = true;
				}
	// pas de cache client en mode 'observation (ou si deja indique)
				if ($flag_dynamique  OR $var_mode) {
				  $page['entetes']["Cache-Control"]= "no-cache,must-revalidate";
				  $page['entetes']["Pragma"] = "no-cache";
				} 
			}
		}
	}
	// toujours utile
	$page['entetes']["Last-Modified"]=http_gmoddate($lastmodified)." GMT";
	$page['status'] = $status;

	return $page;
}

//
// 2 fonctions pour compatibilite arriere. Sont probablement superflues
//

function auto_content_type($code)
{
	global $flag_preserver;
	if (!isset($flag_preserver))
		$flag_preserver = preg_match("/header\s*\(\s*.content\-type:/isx",$code);
}

function auto_expire($code)
{
	global $flag_dynamique;
	if (!isset($flag_dynamique)) {
		if (preg_match("/header\s*\(\s*.Expire:([\s\d])*.\s*\)/is",$code, $r))
			$flag_dynamique = (intval($r[1]) === 0);
	}
}

function inclure_page($fond, $contexte_inclus, $cache_incluant='') {
	global $lastmodified;

	// Peut-on utiliser un fichier cache ?
	list($chemin_cache, $page, $lastinclude) = 
		determiner_cache($use_cache, $contexte_inclus, $fond);

	// Si on a inclus sans fixer le critere de lang, de deux choses l'une :
	// - on est dans la langue du site, et pas besoin d'inclure inc_lang
	// - on n'y est pas, et alors il faut revenir dans la langue par defaut
	if (($lang = $contexte_inclus['lang'])
	|| ($GLOBALS['spip_lang'] != ($lang = $GLOBALS['meta']['langue_site']))) {
		include_spip('inc/lang');
		lang_select($lang);
		$lang_select = true; // pour lang_dselect en sortie
	}

	// Une fois le chemin-cache decide, on ajoute la date (et date_redac)
	// dans le contexte inclus, pour que les criteres {age} etc fonctionnent
	if (!isset($contexte_inclus['date']))
		$contexte_inclus['date'] = date('Y-m-d H:i:s');
	if (!isset($contexte_inclus['date_redac']))
		$contexte_inclus['date_redac'] = $contexte_inclus['date'];

	// On va ensuite chercher la page
	if (!$use_cache) {
		$lastmodified = max($lastmodified, $lastinclude);
	} else {
		include_spip('public/calcul');
		$page = cherche_page($chemin_cache, $contexte_inclus, $fond, false);
		$lastmodified = time();
		if ($chemin_cache) creer_cache($page, $chemin_cache, $use_cache);
	}

	$page['lang_select'] = $lang_select;

	return $page;
}


# Attention, un appel explicite a cette fonction suppose certains include
# (voir l'exemple de spip_inscription et spip_pass)
# $echo = faut-il faire echo ou return

function inclure_balise_dynamique($texte, $echo=true, $ligne=0) {
	global $contexte_inclus; # provisoire : c'est pour le debuggueur

	if (is_array($texte)) {

		list($fond, $delainc, $contexte_inclus) = $texte;

		if ((!$contexte_inclus['lang']) AND
		($GLOBALS['spip_lang'] != $GLOBALS['meta']['langue_site']))
			$contexte_inclus['lang'] = $GLOBALS['spip_lang'];

		include_spip('public/cache');

		$d = $GLOBALS['delais'];
		$GLOBALS['delais'] = $delainc;
		$page = inclure_page($fond, $contexte_inclus);
		$GLOBALS['delais'] = $d;

		if ($page['process_ins'] == 'html') {
				$texte = $page['texte'];
		} else {
				ob_start();
				eval('?' . '>' . $page['texte']);
				$texte = ob_get_contents();
				ob_end_clean();
		}

		if ($page['lang_select'])
			lang_dselect();

	}

	if ($GLOBALS['var_mode'] == 'debug')
		$GLOBALS['debug_objets']['resultat'][$ligne] = $texte;

	if ($echo)
		echo $texte;
	else
		return $texte;

}


function message_erreur_404 ($erreur= "") {
	if (!$erreur) {
		if (isset($GLOBALS['id_article']))
		$erreur = 'public:aucun_article';
		else if (isset($GLOBALS['id_rubrique']))
		$erreur = 'public:aucune_rubrique';
		else if (isset($GLOBALS['id_breve']))
		$erreur = 'public:aucune_breve';
		else if (isset($GLOBALS['id_auteur']))
		$erreur = 'public:aucun_auteur';
		else if (isset($GLOBALS['id_syndic']))
		$erreur = 'public:aucun_site';
	}
	return array('texte' => '<'.'?php
			$contexte_inclus = array("fond" => 404,
 				"erreur" => _T("' . $erreur  . '"));
			include(\'spip.php\'); ?'.'>',
		     'process_ins' => 'php');
}

?>
