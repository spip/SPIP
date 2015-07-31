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
	  global $_GET, $_POST, $_COOKIE, $_SERVER;

	// authentification du visiteur
	if ($_COOKIE['spip_session'] OR
	($_SERVER['PHP_AUTH_USER']  AND !$ignore_auth_http)) {
		include_ecrire ("inc_session");
		verifier_visiteur();
	}
	// multilinguisme
	if ($forcer_lang AND ($forcer_lang!=='non') AND !count($_POST)) {
		include_ecrire('inc_lang');
		verifier_lang_url();
	}
	if ($_GET['lang']) {
		include_ecrire('inc_lang');
		lang_select($_GET['lang']);
	}

	// Si envoi pour un forum, enregistrer puis rediriger

	if (strlen($_POST['confirmer_forum']) > 0
	    OR ($GLOBALS['afficher_texte']=='non' AND $_POST['ajouter_mot'])) {
		include_local('inc-messforum');
		redirige_par_entete(enregistre_forum());
	}

	// si signature de petition, l'enregistrer avant d'afficher la page
	// afin que celle-ci contienne la signature

	if ($_GET['var_confirm']) {
		include_local(find_in_path('inc-formulaire_signature' . _EXTENSION_PHP));
		reponse_confirmation($_GET['id_article'], $var_confirm);
	}

	//  refus du debug si l'admin n'est pas connecte
	if ($var_mode=='debug') {
		if ($auteur_session['statut'] == '0minirezo')
			spip_log('debug !');
		else {
			$link = new Link();
			$link->addvar('var_mode', 'debug');
			redirige_par_entete(generer_url_public('spip_login'), '?url='.urlencode($link->getUrl()));
			exit;
		}
	}

	return afficher_page_globale ($fond);
}


function obtenir_page_ancienne ($chemin_cache, $fond, $inclusion=false) {

	//
	// Lire le fichier cache
	//
	lire_fichier ($chemin_cache, $page['texte']);
	$lastmodified = max($lastmodified, @filemtime($chemin_cache));
	# spip_log ("cache $chemin_cache $lastmodified");

	//
	// Lire sa carte d'identite & fixer le contexte global
	//
	if (preg_match("/^<!-- ([^\n]*) -->\n/ms", $page['texte'], $match)) {
		$meta_donnees = unserialize($match[1]);
		if (is_array($meta_donnees)) {
			foreach ($meta_donnees as $var=>$val) {
				$page[$var] = $val;
			}
		}

		$page['texte'] = substr($page['texte'], strlen($match[0]));

		// Remplir les globals pour les boutons d'admin
		if (!$inclusion AND is_array($page['contexte'])) {
			foreach ($page['contexte'] as $var=>$val) {
				$GLOBALS[$var] = $val;
			}
		}
	}

	return $page;
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
	global $flag_dynamique, $flag_ob, $flag_preserver,
	  $lastmodified, $recherche, $use_cache, $var_mode, $var_preview;
	global $_GET, $_POST, $_COOKIE, $_SERVER;

	include_local("inc-cache");

	// Peut-on utiliser un fichier cache ?
	$chemin_cache = determiner_cache($use_cache, NULL, $fond);

	// demande de previsualisation ?
	// -> inc-calcul n'enregistrera pas les fichiers caches
	// -> inc-boucles acceptera les objets non 'publie'
	if (is_preview()) {
			$var_mode = 'recalcul';
			$var_preview = true;
			spip_log('preview !');
		} else
			$var_preview = false;

	// Repondre gentiment aux requetes sympas
	// (ici on ne tient pas compte d'une obsolence du cache ou des
	// eventuels fichiers inclus modifies depuis la date
	// HTTP_IF_MODIFIED_SINCE du client)
	if ($GLOBALS['HTTP_IF_MODIFIED_SINCE'] AND !$var_mode
	AND $chemin_cache AND !$flag_dynamique) {
		$lastmodified = @filemtime($chemin_cache);
		$headers_only = http_last_modified($lastmodified);
	}
	$headers_only |= ($_SERVER['REQUEST_METHOD'] == 'HEAD');

	if ($headers_only) {
		if ($chemin_cache)
			$t = @filemtime($chemin_cache);
		else
			$t = time();
		@header('Last-Modified: '.http_gmoddate($t).' GMT');
		@header('Connection: close');
		// Pas de bouton admin pour un HEAD
		$flag_preserver = true;
	}
	else {
		// Obtenir la page
		if (!$use_cache)
			$page = obtenir_page_ancienne ($chemin_cache, $fond, false);
		else {
			include_local('inc-calcul');
			$page = calculer_page_globale ($chemin_cache, $fond);

			if ($chemin_cache)
				creer_cache($page, $chemin_cache, $use_cache);
		}
	}

	if ($chemin_cache) $page['cache'] = $chemin_cache;

	if ($page['process_ins'] == 'php') {
		if (!isset($flag_preserver))
			$flag_preserver = preg_match("/header\s*\(\s*.content\-type:/isx",$page['texte']);

		$expire = preg_match("/header\s*\(\s*.Expire:([\s\d])*.\s*\)/is",$php, $r);
		if (!isset($flag_dynamique))
		      $flag_dynamique = $expire && (intval($r[1]) === 0);
	}

	if ($var_preview AND !$flag_preserver) {
		include_ecrire('inc_minipres');
		$page['texte'] .= afficher_bouton_preview();
	}
	//
	// Envoyer les entetes appropries
	// a condition d'etre sur de pouvoir le faire
	//
	if (!headers_sent() AND !$flag_preserver) {

		// Content-type: par defaut html+charset (poss surcharge par la suite)
		header("Content-Type: text/html; charset=".$GLOBALS['meta']['charset']);

		if ($flag_ob) {
			// Si la page est vide, produire l'erreur 404
			if (trim($page['texte']) === ''
			AND $var_mode != 'debug') {
				$page = message_erreur_404();	
			}
			// Interdire au client de cacher un login, un admin ou un recalcul
			else if ($flag_dynamique OR $var_mode
			OR $_COOKIE['spip_admin']) {
				header("Cache-Control: no-cache,must-revalidate");
				header("Pragma: no-cache");
			}
			// Pour les autres donner l'heure de modif
			else if ($lastmodified) {
				header("Last-Modified: ".http_gmoddate($lastmodified)." GMT");
			}
		}
	}

	return $page;
}


function inclure_page($fond, $contexte_inclus, $cache_incluant='') {

	// Peut-on utiliser un fichier cache ?
	$chemin_cache = determiner_cache($use_cache, $contexte_inclus, $fond);

	// Si on a inclus sans fixer le critere de lang, de deux choses l'une :
	// - on est dans la langue du site, et pas besoin d'inclure inc_lang
	// - on n'y est pas, et alors il faut revenir dans la langue par defaut
	if (($lang = $contexte_inclus['lang'])
	|| ($GLOBALS['spip_lang'] != ($lang = $GLOBALS['meta']['langue_site']))) {
		include_ecrire('inc_lang');
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
	if (!$use_cache)
		$page =  obtenir_page_ancienne ($chemin_cache, $fond, false);
	else {
		include_local('inc-calcul');
		$page = cherche_page($chemin_cache, $contexte_inclus, $fond, false);
		$page['signal']['process_ins'] = $page['process_ins'];
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

	if (!is_string($texte))
	  {
	    // Revoir l'API des balises dynamiques:
	    // leurs squelettes sont petits et sans boucle,
	    // la gestion du delai est donc superfetatoire
		list($fond, $delainc, $contexte_inclus) = $texte;

		if ((!$contexte_inclus['lang']) AND
		($GLOBALS['spip_lang'] != $GLOBALS['meta']['langue_site']))
			$contexte_inclus['lang'] = $GLOBALS['spip_lang'];

		$f = find_in_path("inc-cache" . _EXTENSION_PHP);
		if ($f && is_readable($f)) {
		  if (!$GLOBALS['included_files']['inc-cache']++) include($f);
		} else include_local("inc-cache");

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
	include_ecrire('inc_headers');
	http_status(404);

	return array('texte' => '<'.'?php
			$contexte_inclus = array("fond" => 404,
 				"erreur" => _T("' . $erreur  . '"));
			include(\'page' . _EXTENSION_PHP .'\'); ?'.'>',
		     'process_ins' => 'php');
}

//
// pour calcul du nom du fichier cache et autres
//

function nettoyer_uri() {
	return eregi_replace
		('[?&](PHPSESSID|(var_[^=&]*))=[^&]*',
		'', 
		 $GLOBALS['REQUEST_URI']);
}

// Renvoie le _GET ou le _POST emis par l'utilisateur
function _request($var) {
	global $_GET, $_POST;
	if (isset($_GET[$var])) return $_GET[$var];
	if (isset($_POST[$var])) return $_POST[$var];
	return NULL;
}
?>
