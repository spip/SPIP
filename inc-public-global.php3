<?php

// Ce fichier ne sera execute qu'une fois
if (defined("_INC_PUBLIC_GLOBAL")) return;
define("_INC_PUBLIC_GLOBAL", "1");

// fonction principale declenchant tout le service
function calcule_header_et_page ($fond, $delais) {
	  global $affiche_boutons_admin, $auteur_session, $flag_dynamique,
	  $flag_ob, $flag_preserver, $forcer_lang, $ignore_auth_http,
	  $lastmodified, $recherche, $use_cache, $val_confirm, $var_mode,
	  $var_recherche, $tableau_des_erreurs;

	// Regler le $delais par defaut
	if (!isset($delais))
		$delais = 1 * 3600;
	if ($recherche)
		$delais = 0;

	// authentification du visiteur
	if ($GLOBALS['_COOKIE']['spip_session'] OR
	($GLOBALS['_SERVER']['PHP_AUTH_USER']  AND !$ignore_auth_http)) {
		include_ecrire ("inc_session.php3");
		verifier_visiteur();
	}
	// multilinguisme
	if ($forcer_lang AND ($forcer_lang!=='non') AND empty($GLOBALS['_POST'])) {
		include_ecrire('inc_lang.php3');
		verifier_lang_url();
	}
	if ($GLOBALS['_GET']['lang']) {
		include_ecrire('inc_lang.php3');
		lang_select($GLOBALS['_GET']['lang']);
	}

	// Si envoi pour un forum, enregistrer puis rediriger

	if (strlen($GLOBALS['_POST']['confirmer_forum']) > 0
	OR ($GLOBALS['_POST']['afficher_texte']=='non'
		AND $GLOBALS['_POST']['ajouter_mot'])) {
		include('inc-messforum.php3');
		redirige_par_entete(enregistre_forum());
	}

	// si signature de petition, l'enregistrer avant d'afficher la page
	// afin que celle-ci contienne la signature

	if ($GLOBALS['_GET']['val_confirm']) {
		include_local(find_in_path('inc-formulaire_signature.php3'));
		reponse_confirmation($GLOBALS['_GET']['id_article'], $val_confirm);
	}

	//  refus du debug si pas dans les options generales ni admin connecte
	if ($var_mode=='debug') {
		if (($GLOBALS['code_activation_debug'] == 'oui')
		OR $auteur_session['statut'] == '0minirezo')
			spip_log('debug !');
		else
			$var_mode = false; 
	}

	// est-on admin ?
	if ($affiche_boutons_admin = (!$flag_preserver
	AND ($GLOBALS['_COOKIE']['spip_admin']
	OR $GLOBALS['_COOKIE']['spip_debug'])))
		include_local(find_in_path('inc-formulaire_admin.php3'));

	$tableau_des_erreurs = array();
	$page = afficher_page_globale ($fond, $delais, $use_cache);


	//
	// Envoyer les entetes appropries
	// a condition d'etre sur de pouvoir le faire
	//
	if (!$flag_preserver
	AND $flag_ob AND !strlen(@ob_get_contents())) {

		// Si la page est vide, gerer l'erreur 404
		if (preg_match('/^[[:space:]]*$/', $page['texte'])
		AND $var_mode != 'debug') {
			header("HTTP/1.0 404");
			header("Content-Type: text/html; charset=".lire_meta('charset'));
			$contexte_inclus = array(
				'erreur_aucun' => message_erreur_404()
			);
			include(find_in_path('404.php3'));
		}
		// Interdire au client de cacher un login, un admin ou un recalcul
		else if ($flag_dynamique OR $var_mode
		OR $GLOBALS['_COOKIE']['spip_admin']) {
			header("Cache-Control: no-cache,must-revalidate");
			header("Pragma: no-cache");
		}
		// Pour les autres donner l'heure de modif
		else if ($lastmodified) {
			header("Last-Modified: ".http_gmoddate($lastmodified)." GMT");
			header("Content-Type: text/html; charset=".lire_meta('charset'));
		}
	}

	return $page;
}


//
// Aller chercher la page dans le cache ou pas
//
function obtenir_page ($contexte, $chemin_cache, $delais, &$use_cache, $fond, $inclusion=false) {
	global $lastmodified;

	if (!$use_cache) {
		include_local('inc-calcul.php3');

		// page globale ? calculer le contexte
		if (!$contexte)
			$contexte = calculer_contexte();

		spip_timer('calculer_page');
		$page = calculer_page($chemin_cache,
			array('fond' => $fond,
				'contexte' => $contexte),
			$delais,
			$inclusion);

		$lastmodified = time();

		// log
		if (!$log = $chemin_cache) $log = "($fond, delais=$delais, "
		. $GLOBALS['_SERVER']['REQUEST_METHOD'].")";
		spip_log (($inclusion ? 'calcul inclus':'calcul').' ('
		.spip_timer('calculer_page')."): $log");

		// Nouveau cache : creer un invalideur 't' fixant la date
		// d'expiration et la taille du fichier
		if (@file_exists($chemin_cache)) {
			// Ici on ajoute 3600s pour eviter toute concurrence
			// entre un invalideur et un appel public de page
			$bedtime = time() + $delais + 3600;
			$taille = @filesize($chemin_cache);
			$fichier = addslashes($chemin_cache);
			spip_query("INSERT IGNORE INTO spip_caches (fichier,id,type,taille)
			VALUES ('$fichier','$bedtime','t','$taille')");
		}

	} else {

		//
		// Lire le fichier cache
		//
		lire_fichier ($chemin_cache, $page['texte']);
		$lastmodified = max($lastmodified, @filemtime($chemin_cache));
		# spip_log ("cache $chemin_cache $lastmodified");

		//
		// Lire sa carte d'identite & fixer le contexte global
		//
		if (preg_match("/^<!-- ([^\n]*) -->\n(.*)/ms", $page['texte'], $match)
		AND is_array($meta_donnees = unserialize($match[1]))) {
			foreach ($meta_donnees as $var=>$val)
				$page[$var] = $val;

			$page['texte'] = $match[2];

			// Remplir les globals pour les boutons d'admin
			if (!$inclusion AND is_array($page['contexte']))
				foreach ($page['contexte'] as $var=>$val)
					$GLOBALS[$var] = $val;
		}

	}

	return $page;
}


//
// Appeler cette fonction pour obtenir la page principale
//
function afficher_page_globale ($fond, $delais, &$use_cache) {
	global $flag_preserver, $flag_dynamique, $lastmodified;
	global $var_preview, $var_mode;
	include_local ("inc-cache.php3");

	// demande de previsualisation ?
	// -> inc-calcul.php3 n'enregistrera pas les fichiers caches
	// -> inc-reqsql-squel.php3 acceptera les objets non 'publie'
	if ($var_mode == 'preview') {
		// Verifier qu'on a le droit de previsualisation
		$statut = $GLOBALS['auteur_session']['statut'];
		if ($statut=='0minirezo' OR
		(lire_meta('preview')=='1comite' AND $statut=='1comite')) {
			$var_mode = 'recalcul';
			$delais = 0;
			$var_preview = true;
			spip_log('preview !');
		} else
			$var_preview = false;
	}

	// Faut-il effacer des pages invalidees ?
	if (lire_meta('invalider')) {
		include_ecrire('inc_meta.php3');
		lire_metas();
		if (lire_meta('invalider'))
			retire_caches();
	}

	// Calculer le chemin putatif du cache
	$chemin_cache = generer_nom_fichier_cache('', $fond);

	// Peut-on utiliser un fichier cache ?
	determiner_cache($delais, $use_cache, $chemin_cache);

	// Repondre gentiment aux requetes sympas
	// (ici on ne tient pas compte d'une obsolence du cache ou des
	// eventuels fichiers inclus modifies depuis la date
	// HTTP_IF_MODIFIED_SINCE du client)
	if ($GLOBALS['HTTP_IF_MODIFIED_SINCE'] AND !$var_mode
	AND $chemin_cache AND !$flag_dynamique) {
		$lastmodified = @filemtime($chemin_cache);
		$headers_only = http_last_modified($lastmodified);
	}
	$headers_only |= ($GLOBALS['_SERVER']['REQUEST_METHOD'] == 'HEAD');

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
		$page = obtenir_page ('', $chemin_cache, $delais, $use_cache,
		$fond, false);

		if (!$flag_preserver) {
		// Entete content-type: xml ou html ; charset
			if ($xhtml) {
			// Si Mozilla et tidy actif, passer en "application/xhtml+xml"
			// extremement risque: Mozilla passe en mode debugueur strict
			// mais permet d'afficher du MathML directement dans le texte
			// (et sauf erreur, c'est la bonne facon de declarer du xhtml)
				include_ecrire("inc_tidy.php");
				if (version_tidy() > 0) {
					if (ereg("application/xhtml\+xml", $GLOBALS['HTTP_ACCEPT'])) 
						@header("Content-Type: application/xhtml+xml; charset=".lire_meta('charset'));
					else 
						@header("Content-Type: text/html; charset=".lire_meta('charset'));
					echo '<'.'?xml version="1.0" encoding="'. lire_meta('charset').'"?'.">\n";
				} else {
					@header("Content-Type: text/html; charset=".lire_meta('charset'));
				}
			} else {
				@header("Content-Type: text/html; charset=".lire_meta('charset'));
			}
		}
	}

	if ($chemin_cache) $page['cache'] = $chemin_cache;

	if ($var_preview AND !$flag_preserver) {
		include_ecrire('inc_lang.php3');
		include_ecrire('inc_filtres.php3');
		lang_select($GLOBALS['auteur_session']['lang']);
		$page['texte'] .= '<div style="
		display: block;
		color: #eeeeee;
		background-color: #111111;
		padding-right: 5px;
		padding-top: 2px;
		padding-bottom: 5px;
		font-size: 20px;
		top: 0px;
		left: 0px;
		position: absolute;
		"><img src="' . _DIR_IMG_PACK
		. 'naviguer-site.png" align="left" border="0" />&nbsp; '
		. majuscules(_T('previsualisation')).'</div>';
	}

	return $page;
}


function terminer_public_global() {

	// Gestion des statistiques du site public
	if (lire_meta("activer_statistiques") != "non") {
		include_local ("inc-stats.php3");
		ecrire_stats();
	}

	// Effectuer une tache de fond ?
	cron();
}


function inclure_page($fond, $delais_inclus, $contexte_inclus, $cache_incluant='') {

	$contexte_inclus['fond'] = $fond;

	$chemin_cache = generer_nom_fichier_cache($contexte_inclus, $fond);

	// Peut-on utiliser un fichier cache ?
	determiner_cache($delais_inclus, $use_cache, $chemin_cache);

	// Si on a inclus sans fixer le critere de lang, de deux choses l'une :
	// - on est dans la langue du site, et pas besoin d'inclure inc_lang
	// - on n'y est pas, et alors il faut revenir dans la langue par defaut
	if (($lang = $contexte_inclus['lang'])
	|| ($GLOBALS['spip_lang'] != ($lang = lire_meta('langue_site')))) {
		include_ecrire('inc_lang.php3');
		lang_select($lang);
		$lang_select = true; // pour lang_dselect en sortie
	}
	
	// Si on est inclus en POST, il faut ajouter les variables _POST dans
	// le contexte inclus, sinon les formulaires ne marchent pas...
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		include_local('inc-calcul.php3');
		$contexte_inclus = array_merge(calculer_contexte(), $contexte_inclus);
	}

	$page = obtenir_page ($contexte_inclus, $chemin_cache, $delais_inclus,
	$use_cache, $fond, true);

	$page['lang_select'] = $lang_select;

	// Retourner le contenu...
	return $page;
}

// equivalente a texte_script dans inc_filtre.

function securise_script($texte) {
	return str_replace('\'', '\\\'', str_replace('\\', '\\\\', $texte));
}

# les balises dynamiques sont traitees comme des inclusions

function synthetiser_balise_dynamique($nom, $args, $file, $lang) {
	return
		('<'.'?php 
include_ecrire(\'inc_lang.php3\');
lang_select("'.$lang.'");
include_local("'
		. $file
		. '");
inclure_balise_dynamique(balise_'
		. $nom
		. '_dyn(\''
		. join("', '", array_map("securise_script", $args))
		. '\'));
	lang_dselect();
?'
		.">");
}

# Attention, un appel explicite a cette fonction suppose certains include
# (voir l'exemple de spip_inscription et spip_pass)

function inclure_balise_dynamique($r) {
	if (is_string($r))
		echo $r;
	else {
		list($fond, $delais, $contexte_inclus) = $r;

		if ((!$contexte_inclus['lang']) AND
		($GLOBALS['spip_lang'] != lire_meta('langue_site')))
			$contexte_inclus['lang'] = $GLOBALS['spip_lang'];


		// Appeler la page
		$page = inclure_page($fond, $delais, $contexte_inclus);
		if ($page['process_ins'] == 'html')
			echo $page['texte'];
		else
			eval('?' . '>' . $page['texte']);

		if ($page['lang_select'])
			lang_dselect();

	}
}


function message_erreur_404 () {
	if ($GLOBALS['id_article'])
		$erreur = 'aucun_article';
	else if ($GLOBALS['id_rubrique'])
		$erreur = 'aucune_rubrique';
	else if ($GLOBALS['id_breve'])
		$erreur = 'aucune_breve';
	else if ($GLOBALS['id_auteur'])
		$erreur = 'aucun_auteur';
	else if ($GLOBALS['id_syndic'])
		$erreur = 'aucun_site';
	else
		$erreur = '';

	return _T("public:".$erreur);
}

?>
