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
// http://doc.spip.org/@public_assembler_dist
function public_assembler_dist($fond) {
	  global $auteur_session, $forcer_lang, $ignore_auth_http,
	  $var_confirm, $var_mode;

	// multilinguisme
	if ($forcer_lang AND ($forcer_lang!=='non') AND !count($_POST)) {
		include_spip('inc/lang');
		verifier_lang_url();
	}
	if (isset($_GET['lang'])) {
		include_spip('inc/lang');
		lang_select($_GET['lang']);
	}

	// Si envoi pour un forum, enregistrer puis rediriger
	if (isset($_POST['confirmer_forum'])
	OR (isset($_POST['ajouter_mot']) AND $GLOBALS['afficher_texte']=='non')) {
		$f = charger_fonction('forum_insert', 'inc');
		redirige_par_entete($f());
	}

	// si signature de petition, l'enregistrer avant d'afficher la page
	// afin que celle-ci contienne la signature
	if (isset($_GET['var_confirm'])) {
		include_spip('balise/formulaire_signature');
		reponse_confirmation($var_confirm);
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

	return assembler_page ($fond);
}


// http://doc.spip.org/@is_preview
function is_preview()
{
	global $var_mode;
	if ($var_mode !== 'preview') return false;
	$statut = $GLOBALS['auteur_session']['statut'];
	return ($statut=='0minirezo' OR
		($GLOBALS['meta']['preview']=='1comite' AND $statut=='1comite'));
}

//
// calcule la page et les entetes
//
// http://doc.spip.org/@assembler_page
function assembler_page ($fond) {
	global $flag_dynamique, $flag_ob, $flag_preserver,$lastmodified,
		$use_cache, $var_mode, $var_preview;

	// Cette fonction est utilisee deux fois
	$fcache = charger_fonction('cacher', 'public');
	// Garnir ces quatre parametres avec les infos sur le cache
	$fcache(NULL, $use_cache, $chemin_cache, $page, $lastmodified);

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

	// Pour les pages non-dynamiques (indiquees par #CACHE{duree,cache-client})
	// une perennite valide a meme reponse qu'une requete HEAD (par defaut les
	// pages sont dynamiques)
	if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
	AND !$var_mode
	AND $chemin_cache
	AND !$flag_dynamique
	AND !strstr('IIS/', $_SERVER['SERVER_SOFTWARE'])
	) {
		$since = preg_replace('/;.*/', '',
			$_SERVER['HTTP_IF_MODIFIED_SINCE']);
		$since = str_replace('GMT', '', $since);
		if (trim($since) == gmdate("D, d M Y H:i:s", $lastmodified)) {
			$page['status'] = 304;
			$headers_only = true;
		}
	}

	// Si requete HEAD ou Last-modified compatible, ignorer le texte
	// et pas de content-type (pour contrer le bouton admin de inc-public)

	if ($headers_only) {
		$page['entetes']["Connection"] = "close";
		$page['texte'] = "";
	} else {
		if (!$use_cache )  {
			if (isset($page['contexte'])){
				// Remplir les globals pour les boutons d'admin
				foreach ($page['contexte'] as $var=>$val)
					$GLOBALS[$var] = $val;
			}
		} else {
			$f = charger_fonction('parametrer', 'public');
			$page = $f($fond, '', $chemin_cache);
			if ($chemin_cache)
				$fcache(NULL, $use_cache, $chemin_cache, $page, $lastmodified);
		}

		if ($chemin_cache) $page['cache'] = $chemin_cache;

		auto_content_type($page);
		auto_expire($page);

		$flag_preserver |=  (headers_sent());

	// Definir les entetes si ce n'est fait 

		if (!$flag_preserver) {
			if ($flag_ob) {
			// Si la page est vide, produire l'erreur 404
				if (trim($page['texte']) === ''
				    AND $var_mode != 'debug') {
					$page = message_erreur_404();
					$flag_dynamique = true;
				}
	// pas de cache client en mode 'observation (ou si deja indique)
				if ($flag_dynamique OR $var_mode) {
				  $page['entetes']["Cache-Control"]= "no-cache,must-revalidate";
				  $page['entetes']["Pragma"] = "no-cache";
				} 
			}
		}
	}

	if ($lastmodified)
		$page['entetes']["Last-Modified"]=gmdate("D, d M Y H:i:s", $lastmodified)." GMT";

	return $page;
}

//
// 2 fonctions pour compatibilite arriere. Sont probablement superflues
//

// http://doc.spip.org/@auto_content_type
function auto_content_type($page)
{
	global $flag_preserver;
	if (!isset($flag_preserver))
	  {
		$flag_preserver = preg_match("/header\s*\(\s*.content\-type:/isx",$page['texte']) || (isset($page['entetes']['Content-Type']));
	  }
}

// http://doc.spip.org/@auto_expire
function auto_expire($page)
{
	global $flag_dynamique;
	if (!isset($flag_dynamique)) {
		if (preg_match("/header\s*\(\s*.Expire:([\s\d])*.\s*\)/is",$page['texte'], $r))
			$flag_dynamique = (intval($r[1]) === 0);
		else	if (isset($page['entetes']['Expire']) AND preg_match("/([\s\d])*.\s*\)/is",$page['entetes']['Expire'], $r))
			$flag_dynamique = (intval($r[1]) === 0);
	}
}

// http://doc.spip.org/@stop_inclure
function stop_inclure($fragment) {
	if ($fragment == _request('var_fragment')) {
		define('_STOP_INCLURE', 1);
		#spip_log("fin du fragment $fragment, on arrete d'inclure");
	}
}
// http://doc.spip.org/@inclure_page
function inclure_page($fond, $contexte_inclus) {
	global $lastmodified;

	// Si un fragment est demande et deja obtenu, inutile de continuer a inclure
	if (defined('_STOP_INCLURE')) {
		return array(
		'texte' => '',
		'process_ins' => 'html'
		);
	}

	// Si on a inclus sans fixer le critere de lang, on prend la langue courante
	if (!isset($contexte_inclus['lang']))
		$contexte_inclus['lang'] = $GLOBALS['spip_lang'];

	if ($contexte_inclus['lang'] != $GLOBALS['meta']['langue_site']) {
		lang_select($lang);
		$lang_select = true; // pour lang_dselect en sortie
	}

	$fcache = charger_fonction('cacher', 'public');
	// Garnir ces quatre parametres avec les infos sur le cache
	$fcache($contexte_inclus, $use_cache, $chemin_cache, $page, $lastinclude);

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
		$f = charger_fonction('parametrer', 'public');
		$page = $f($fond, $contexte_inclus, $chemin_cache);
		$lastmodified = time();
		if ($chemin_cache)
			$fcache($contexte_inclus, $use_cache, $chemin_cache, $page, $lastmodified);
	}
	if($lang_select)
		lang_dselect();

#print_r($contexte_inclus);print_r($page);exit;

	return $page;
}


# Attention, un appel explicite a cette fonction suppose certains include
# (voir l'exemple de spip_inscription et spip_pass)
# $echo = faut-il faire echo ou return

// http://doc.spip.org/@inclure_balise_dynamique
function inclure_balise_dynamique($texte, $echo=true, $ligne=0) {
	global $contexte_inclus; # provisoire : c'est pour le debuggueur

	if (is_array($texte)) {

		list($fond, $delainc, $contexte_inclus) = $texte;

		// delais a l'ancienne, c'est pratiquement mort
		$d = isset($GLOBALS['delais']) ? $GLOBALS['delais'] : 0;
		$GLOBALS['delais'] = $delainc;
		$page = inclure_page($fond, $contexte_inclus);
		$GLOBALS['delais'] = $d;
		if (is_array($page['entetes']))
			foreach($page['entetes'] as $k => $v) {
			  // ceci se discute
			  // if ((strtolower($k) != 'content-type')
			  // OR !isset( $GLOBALS['page']['entetes'][$k])
				$GLOBALS['page']['entetes'][$k] = $v;
			}

		if ($page['process_ins'] == 'html') {
				$texte = $page['texte'];
		} else {
				ob_start();
				eval('?' . '>' . $page['texte']);
				$texte = ob_get_contents();
				ob_end_clean();
		}
	}

	if ($GLOBALS['var_mode'] == 'debug')
		$GLOBALS['debug_objets']['resultat'][$ligne] = $texte;

	if ($echo)
		echo $texte;
	else
		return $texte;

}

// Traiter var_recherche pour surligner les mots
// http://doc.spip.org/@f_surligne
function f_surligne ($texte) {
	if (isset($_GET['var_recherche'])) {
		include_spip('inc/surligne');
		$texte = surligner_mots($texte, $_GET['var_recherche']);
	}
	return $texte;
}

// Valider/indenter a la demande.
// http://doc.spip.org/@f_tidy
function f_tidy ($texte) {
	global $xhtml;

	if ($xhtml # tidy demande
	AND $GLOBALS['html'] # verifie que la page avait l'entete text/html
	AND strlen($texte)
	AND (_request('var_fragment') === NULL)
	AND !headers_sent()) {
		# Compatibilite ascendante
		if ($xhtml === true) $xhtml ='tidy';
		else if ($xhtml == 'spip_sax') $xhtml = 'sax';

		if ($f = charger_fonction($xhtml, 'inc'))
			$texte = $f($texte);
	}

	return $texte;
}

// Inserer au besoin les boutons admins
// http://doc.spip.org/@f_admin
function f_admin ($texte) {
	if ($GLOBALS['affiche_boutons_admin']) {
		include_spip('public/admin');
		$texte = affiche_boutons_admin($texte);
	}

	return $texte;
}

// http://doc.spip.org/@message_erreur_404
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
	$contexte_inclus = array(
		'erreur' => _T($erreur),
		'lang' => $GLOBALS['spip_lang']
	);
	$page = inclure_page('404', $contexte_inclus);
	$page['status'] = 404;
	return $page;
}

// fonction permettant de recuperer le resultat du calcul d'un squelette
// pour une inclusion dans un flux
// http://doc.spip.org/@recuperer_fond
function recuperer_fond($fond, $contexte=array()) {

	// on est peut etre dans l'espace prive au moment de l'appel
	define ('_INC_PUBLIC', 1);
	if (($fond=='')&&isset($contexte['fond']))
		$fond = $contexte['fond'];
	
	$contexte['fond'] = $fond; // necessaire pour calculer correctement le cache

	$page = inclure_page($fond, $contexte);

	if ($GLOBALS['flag_ob'] AND ($page['process_ins'] != 'html')) {
		ob_start();
		eval('?' . '>' . $page['texte']);
		$page['texte'] = ob_get_contents();
		ob_end_clean();
	}

	return trim($page['texte']);
}

// temporairement ici : a mettre dans le futur inc/modeles
// creer_contexte_de_modele('left', 'autostart=true', ...) renvoie un array()
// http://doc.spip.org/@creer_contexte_de_modele
function creer_contexte_de_modele($args) {
	$contexte = array();
	$params = array();
	foreach ($args as $var=>$val) {
		if (is_int($var)){ // argument pas formate
			if (in_array($val, array('left', 'right', 'center'))) {
				$var = 'align';
				$contexte[$var] = $val;
			} else {
				$args = explode('=', $val);
				if (count($args)==2)
					$contexte[$args[0]] = $args[1];
			}
		}
		else
			$contexte[$var] = $val;
	}

	return $contexte;
}

// Calcule le modele et retourne la mini-page ainsi calculee
// http://doc.spip.org/@inclure_modele
function inclure_modele($type, $id, $params, $lien) {
	static $compteur;
	if (++$compteur>10) return ''; # ne pas boucler indefiniment

	$type = strtolower($type);

	$fond = 'modeles/'.$type;

	$params = array_filter(explode('|', $params));
	if ($params) {
		list(,$soustype) = each($params);
		$soustype = strtolower($soustype);
		if (in_array($soustype,
		array('left', 'right', 'center'))) {
			list(,$soustype) = each($params);
			$soustype = strtolower($soustype);
		}

		if (preg_match(',^[a-z0-9_]+$,', $soustype)) {
			$fond = 'modeles/'.$type.'_'.$soustype;
			if (!find_in_path($fond.'.html')) {
				$fond = 'modeles/'.$type;
				$class = $soustype;
			}
		}
	}

	// en cas d'echec : si l'objet demande a une url, on cree un petit encadre
	// avec un lien vers l'objet ; sinon on passe la main au suivant
	if (!find_in_path($fond.'.html')) {
		if (!$lien)
			$lien = calculer_url("$type$id", '', 'tout');
		if ($lien[1] == 'spip_url')
			return false;
		else
			return '<a href="'.$lien[0].'" class="spip_modele'
				. ($class ? " $class" : '')
				. '">'.sinon($lien[2], _T('ecrire:info_sans_titre'))."</a>";
	}


	// Creer le contexte
	$contexte = array( 
		'lang' => $GLOBALS['spip_lang'], 
		'fond' => $fond, 
		'dir_racine' => _DIR_RACINE # eviter de mixer un cache racine et un cache ecrire (meme si pour l'instant les modeles ne sont pas caches, le resultat etant different il faut que le contexte en tienne compte 
	); 
	// Fixer l'identifiant qu'on passe dans #ENV ;
	// pour le modele <site1> on veut id_syndic => 1
	// par souci de systematisme on ajoute aussi
	// id => 1.
	$contexte[id_table_objet($type)] = $contexte['id'] = $id;

	if ($class)
		$contexte['class'] = $class;

	// Si un lien a ete passe en parametre, ex: [<modele1>->url]
	if ($lien) {
		# un eventuel guillemet (") sera reechappe par #ENV
		$contexte['lien'] = str_replace("&quot;",'"', $lien[0]);
		$contexte['lien_class'] = $lien[1];
	}
	
	// Traiter les parametres
	// par exemple : <img1|center>, <emb12|autostart=true> ou <doc1|lang=en>
	$contexte = array_merge($contexte,
		creer_contexte_de_modele($params)); 

	// On cree un marqueur de notes unique lie a ce modele
	// et on enregistre l'etat courant des globales de notes...
	$enregistre_marqueur_notes = $GLOBALS['marqueur_notes'];
	$enregistre_les_notes = $GLOBALS['les_notes'];
	$enregistre_compt_note = $GLOBALS['compt_note'];
	$GLOBALS['marqueur_notes'] = substr(md5(serialize($contexte)),0,8);
	$GLOBALS['les_notes'] = '';
	$GLOBALS['compt_note'] = 0;

	// Appliquer le modele avec le contexte
	$retour = trim(recuperer_fond($fond, $contexte));

	// On restitue les globales de notes telles qu'elles etaient avant l'appel
	// du modele. Si le modele n'a pas affiche ses notes, tant pis (elles *doivent*
	// etre dans le cache du modele, autrement elles ne seraient pas prises en
	// compte a chaque calcul d'un texte contenant un modele, mais seulement
	// quand le modele serait calcule, et on aurait des resultats incoherents)
	$GLOBALS['les_notes'] = $enregistre_les_notes;
	$GLOBALS['marqueur_notes'] = $enregistre_marqueur_notes;
	$GLOBALS['compt_note'] = $enregistre_compt_note;

	// Regarder si le modele tient compte des liens (il *doit* alors indiquer
	// spip_lien_ok dans les classes de son conteneur de premier niveau ;
	// sinon, s'il y a un lien, on l'ajoute classiquement
	if (strstr(' ' . ($classes = extraire_attribut($retour, 'class')).' ',
	'spip_lien_ok')) {
		$retour = inserer_attribut($retour, 'class',
			trim(str_replace(' spip_lien_ok ', ' ', " $classes ")));
	} else if ($lien)
		$retour = "<a href='".$lien[0]."' class='".$lien[1]."'>".$retour."</a>";

	$compteur--;
	return $retour;
}

?>
