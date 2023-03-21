<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Chargement (et affichage) d'une page ou d'un appel public
 *
 * @package SPIP\Core\Affichage
 **/

// Distinguer une inclusion d'un appel initial
// (cette distinction est obsolete a present, on la garde provisoirement
// par souci de compatiilite).

if (isset($GLOBALS['_INC_PUBLIC']) && $GLOBALS['_INC_PUBLIC']) {
	echo recuperer_fond($fond, $contexte_inclus, [], _request('connect') ?? '');
} else {
	$GLOBALS['_INC_PUBLIC'] = 1;
	define('_PIPELINE_SUFFIX', test_espace_prive() ? '_prive' : '');

	// Faut-il initialiser SPIP ? (oui dans le cas general)
	if (!defined('_DIR_RESTREINT_ABS')) {
		if (
			defined('_DIR_RESTREINT')
			&& @file_exists(_ROOT_RESTREINT . 'inc_version.php')
		) {
			include_once _ROOT_RESTREINT . 'inc_version.php';
		} else {
			die('inc_version absent ?');
		}
	} // $fond defini dans le fichier d'appel ?

	else {
		if (isset($fond) && !_request('fond')) {
		} // fond demande dans l'url par page=xxxx ?
		else {
			if (isset($_GET[_SPIP_PAGE])) {
				$fond = (string)$_GET[_SPIP_PAGE];

				// Securite
				if (
					strstr($fond, '/')
					&& !(isset($GLOBALS['visiteur_session']) && include_spip('inc/autoriser') && autoriser('webmestre'))
				) {
					include_spip('inc/minipres');
					echo minipres();
					exit;
				}
				// l'argument Page a priorite sur l'argument action
				// le cas se presente a cause des RewriteRule d'Apache
				// qui permettent d'ajouter un argument dans la QueryString
				// mais pas d'en retirer un en conservant les autres.
				if (isset($_GET['action']) && $_GET['action'] === $fond) {
					unset($_GET['action']);
				}
				# sinon, fond par defaut
			} else {
				// sinon fond par defaut (cf. assembler.php)
				$fond = pipeline('detecter_fond_par_defaut', '');
			}
		}
	}

	$tableau_des_temps = [];

	// Particularites de certains squelettes
	if ($fond == 'login') {
		$forcer_lang = true;
	}

	if (
		isset($forcer_lang) && $forcer_lang && $forcer_lang !== 'non'
		&& !_request('action')
		&& $_SERVER['REQUEST_METHOD'] != 'POST'
	) {
		include_spip('inc/lang');
		verifier_lang_url();
	}

	$lang = isset($_GET['lang']) ? lang_select($_GET['lang']) : '';

	// Charger l'aiguilleur des traitements derogatoires
	// (action en base SQL, formulaires CVT, AJax)
	if (_request('action') || _request('var_ajax') || _request('formulaire_action')) {
		include_spip('public/aiguiller');
		if (
			// cas des appels actions ?action=xxx
			traiter_appels_actions()
			// cas des hits ajax sur les inclusions ajax
			|| traiter_appels_inclusions_ajax()
			// cas des formulaires charger/verifier/traiter
			|| traiter_formulaires_dynamiques()
		) {
			// lancer les taches sur affichage final, comme le cron
			// mais sans rien afficher
			$GLOBALS['html'] = false; // ne rien afficher
			pipeline('affichage_final' . _PIPELINE_SUFFIX, '');
			exit; // le hit est fini !
		}
	}

	// Il y a du texte a produire, charger le metteur en page
	include_spip('public/assembler');
	$page = assembler($fond, _request('connect') ?? '');

	if (isset($page['status'])) {
		include_spip('inc/headers');
		http_response_code($page['status']);
	}

	// Content-Type ?
	if (!isset($page['entetes']['Content-Type'])) {
		$charset = $GLOBALS['meta']['charset'] ?? 'utf-8';
		$page['entetes']['Content-Type'] = 'text/html; charset=' . $charset;
		$html = true;
	} else {
		$html = preg_match(',^\s*text/html,', (string) $page['entetes']['Content-Type']);
	}

	// Tester si on est admin et il y a des choses supplementaires a dire
	// type tableau pour y mettre des choses au besoin.
	$debug = (_request('var_mode') == 'debug' || $tableau_des_temps) ? [1] : [];

	// affiche-t-on les boutons d'administration ? voir f_admin()
	$affiche_boutons_admin = ($html && (
		isset($_COOKIE['spip_admin']) && (!isset($flag_preserver) || !$flag_preserver)
		|| $debug && include_spip('inc/autoriser') && autoriser('debug')
		|| defined('_VAR_PREVIEW') && _VAR_PREVIEW)
	);

	if ($affiche_boutons_admin) {
		include_spip('balise/formulaire_admin');
	}


	// Execution de la page calculee

	// traitements sur les entetes avant envoi
	// peut servir pour le plugin de stats
	$page['entetes'] = pipeline('affichage_entetes_final' . _PIPELINE_SUFFIX, $page['entetes']);


	// eval $page et affecte $res
	include _ROOT_RESTREINT . 'public/evaluer_page.php';
	envoyer_entetes($page['entetes']);
	if ($res === false) {
		include_spip('inc/autoriser');
		$err = _T('zbug_erreur_execution_page');
		if (autoriser('webmestre')) {
			$err .= "\n<hr />\n"
				. highlight_string($page['codephp'], true)
				. "\n<hr />\n";
		}
		$msg = [$err];
		erreur_squelette($msg);
	}

	//
	// Envoyer le resultat apres post-traitements
	//
	// (c'est ici qu'on fait var_recherche, validation, boutons d'admin,
	// cf. public/assembler.php)
	echo pipeline('affichage_final' . _PIPELINE_SUFFIX, $page['texte']);

	if ($lang) {
		lang_select();
	}
	// l'affichage de la page a pu lever des erreurs (inclusion manquante)
	// il faut tester a nouveau
	$debug = (_request('var_mode') == 'debug' || $tableau_des_temps) ? [1] : [];

	// Appel au debusqueur en cas d'erreurs ou de demande de trace
	// at last
	if ($debug) {
		// en cas d'erreur, retester l'affichage
		if ($html && ($affiche_boutons_admin || $debug)) {
			$var_mode_affiche = _request('var_mode_affiche');
			$var_mode_objet = _request('var_mode_objet');
			$GLOBALS['debug_objets'][$var_mode_affiche][$var_mode_objet . 'tout'] = ($var_mode_affiche == 'validation' ? $page['texte'] : '');
			echo erreur_squelette(false);
		}
	} else {
		if (
			isset($GLOBALS['meta']['date_prochain_postdate'])
			&& $GLOBALS['meta']['date_prochain_postdate'] <= time()
		) {
			include_spip('inc/rubriques');
			calculer_prochain_postdate(true);
		}

		// Effectuer une tache de fond ?
		// si _DIRECT_CRON_FORCE est present, on force l'appel
		if (defined('_DIRECT_CRON_FORCE')) {
			cron();
		}

		// sauver le cache chemin si necessaire
		save_path_cache();
	}
}
