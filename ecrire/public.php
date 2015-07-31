<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

// Distinguer une inclusion d'un appel initial
// (cette distinction est obsolete a present, on la garde provisoirement
// par souci de compatiilite).

if (isset($GLOBALS['_INC_PUBLIC']) AND $GLOBALS['_INC_PUBLIC']) {

	echo recuperer_fond($fond, $contexte_inclus, array(), _request('connect'));

} else {

	$GLOBALS['_INC_PUBLIC'] = 1;
	define('_PIPELINE_SUFFIX',  test_espace_prive()?'_prive':'');

	// Faut-il initialiser SPIP ? (oui dans le cas general)
	if (!defined('_DIR_RESTREINT_ABS'))
		if (defined('_DIR_RESTREINT')
		AND @file_exists(_ROOT_RESTREINT . 'inc_version.php')) {
			include_once _ROOT_RESTREINT . 'inc_version.php';
		}
		else
			die('inc_version absent ?');


	// $fond defini dans le fichier d'appel ?

	else if (isset($fond) AND !_request('fond')) { }

	// fond demande dans l'url par page=xxxx ?
	else if (isset($_GET[_SPIP_PAGE])) {
		$fond = (string)$_GET[_SPIP_PAGE];

		// Securite
		if (strstr($fond, '/')
			AND !(
				isset($GLOBALS['visiteur_session']) // pour eviter d'evaluer la suite pour les anonymes
				AND include_spip('inc/autoriser')
				AND autoriser('webmestre'))) {
			include_spip('inc/minipres');
			echo minipres();
			exit;
		}
		// l'argument Page a priorite sur l'argument action
		// le cas se presente a cause des RewriteRule d'Apache
		// qui permettent d'ajouter un argument dans la QueryString
		// mais pas d'en retirer un en conservant les autres.
		if (isset($_GET['action']) AND $_GET['action'] === $fond)
			unset($_GET['action']);
	# sinon, fond par defaut
	} else {
		// sinon fond par defaut (cf. assembler.php)
		$fond = pipeline('detecter_fond_par_defaut','');
	}

	$tableau_des_temps = array();

	// Particularites de certains squelettes
	if ($fond == 'login')
		$forcer_lang = true;

	if (isset($forcer_lang) AND $forcer_lang AND ($forcer_lang!=='non')
		AND !_request('action')
		AND $_SERVER['REQUEST_METHOD'] != 'POST') {
		include_spip('inc/lang');
		verifier_lang_url();
	}

	$lang = !isset($_GET['lang']) ? '' : lang_select($_GET['lang']);

	// Charger l'aiguilleur des traitements derogatoires
	// (action en base SQL, formulaires CVT, AJax)
	if (_request('action') OR _request('var_ajax') OR _request('formulaire_action')){
		include_spip('public/aiguiller');
		if (
			// cas des appels actions ?action=xxx
			traiter_appels_actions()
		OR
			// cas des hits ajax sur les inclusions ajax
			traiter_appels_inclusions_ajax()
		 OR
			// cas des formulaires charger/verifier/traiter
			traiter_formulaires_dynamiques()){
			// lancer les taches sur affichage final, comme le cron
			// mais sans rien afficher
			$GLOBALS['html'] = false; // ne rien afficher
			pipeline('affichage_final'._PIPELINE_SUFFIX, '');
			exit; // le hit est fini !
		}
	}

	// Il y a du texte a produire, charger le metteur en page
	include_spip('public/assembler');
	$page = assembler($fond, _request('connect'));

	if (isset($page['status'])) {
		include_spip('inc/headers');
		http_status($page['status']);
	}

	// Content-Type ?
	if (!isset($page['entetes']['Content-Type'])) {
		$page['entetes']['Content-Type'] = 
			"text/html; charset=" . $GLOBALS['meta']['charset'];
		$html = true;
	} else {
		$html = preg_match(',^\s*text/html,',$page['entetes']['Content-Type']);
	}

	// Tester si on est admin et il y a des choses supplementaires a dire
	// type tableau pour y mettre des choses au besoin.
	$debug = ((_request('var_mode') == 'debug') OR $tableau_des_temps) ? array(1) : array();

	$affiche_boutons_admin = ($html AND (
		(isset($_COOKIE['spip_admin']) AND (!isset($flag_preserver) OR !$flag_preserver))
		OR $debug
		OR (defined('_VAR_PREVIEW') AND _VAR_PREVIEW)
	));

	if ($affiche_boutons_admin)
		include_spip('balise/formulaire_admin');


	// Execution de la page calculee

	// traitements sur les entetes avant envoi
	// peut servir pour le plugin de stats
	$page['entetes'] = pipeline('affichage_entetes_final'._PIPELINE_SUFFIX, $page['entetes']);


	// eval $page et affecte $res
	include _ROOT_RESTREINT."public/evaluer_page.php";
	envoyer_entetes($page['entetes']);
	if ($res === false) {
		$msg = array('zbug_erreur_execution_page');
		erreur_squelette($msg);
	}

	//
	// Envoyer le resultat apres post-traitements
	//
	// (c'est ici qu'on fait var_recherche, validation, boutons d'admin,
	// cf. public/assembler.php)
	echo pipeline('affichage_final'._PIPELINE_SUFFIX, $page['texte']);

	if ($lang) lang_select();
	// l'affichage de la page a pu lever des erreurs (inclusion manquante)
	// il faut tester a nouveau
	$debug = ((_request('var_mode') == 'debug') OR $tableau_des_temps) ? array(1) : array();

	// Appel au debusqueur en cas d'erreurs ou de demande de trace
	// at last
	if ($debug) {
		// en cas d'erreur, retester l'affichage
		if ($html AND ($affiche_boutons_admin OR $debug)) {
			$var_mode_affiche = _request('var_mode_affiche');
			$GLOBALS['debug_objets'][$var_mode_affiche][$var_mode_objet . 'tout'] = ($var_mode_affiche== 'validation' ? $page['texte'] :"");
			echo erreur_squelette(false);
		}
	} else {

		if (isset($GLOBALS['meta']['date_prochain_postdate'])
		AND $GLOBALS['meta']['date_prochain_postdate'] <= time()) {
			include_spip('inc/rubriques');
			calculer_prochain_postdate(true);
		}

		// Effectuer une tache de fond ?
		// si _DIRECT_CRON_FORCE est present, on force l'appel
		if (defined('_DIRECT_CRON_FORCE'))
			cron();

		// sauver le cache chemin si necessaire
		save_path_cache();
	}
}

?>
