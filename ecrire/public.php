<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Chargement (et affichage) d'une page ou d'un appel public
 *
 * @package SPIP\Core\Affichage
 **/

// Distinguer une inclusion d'un appel initial
// (cette distinction est obsolete a present, on la garde provisoirement
// par souci de compatiilite).

if (isset($GLOBALS['_INC_PUBLIC']) and $GLOBALS['_INC_PUBLIC']) {
	echo recuperer_fond($fond, $contexte_inclus, [], _request('connect') ?? '');
} else {
	$GLOBALS['_INC_PUBLIC'] = 1;
	define('_PIPELINE_SUFFIX', test_espace_prive() ? '_prive' : '');

	// Faut-il initialiser SPIP ? (oui dans le cas general)
	if (!defined('_DIR_RESTREINT_ABS')) {
		if (
			defined('_DIR_RESTREINT')
			and @file_exists(_ROOT_RESTREINT . 'inc_version.php')
		) {
			include_once _ROOT_RESTREINT . 'inc_version.php';
		} else {
			die('inc_version absent ?');
		}
	} // $fond defini dans le fichier d'appel ?

	else {
		if (isset($fond) and !_request('fond')) {
		} // fond demande dans l'url par page=xxxx ?
		else {
			if (isset($_GET[_SPIP_PAGE])) {
				$fond = (string)$_GET[_SPIP_PAGE];

				// Securite
				if (
					strstr($fond, '/')
					and !(
						isset($GLOBALS['visiteur_session']) // pour eviter d'evaluer la suite pour les anonymes
						and include_spip('inc/autoriser')
						and autoriser('webmestre'))
				) {
					include_spip('inc/minipres');
					echo minipres();
					exit;
				}
				// l'argument Page a priorite sur l'argument action
				// le cas se presente a cause des RewriteRule d'Apache
				// qui permettent d'ajouter un argument dans la QueryString
				// mais pas d'en retirer un en conservant les autres.
				if (isset($_GET['action']) and $_GET['action'] === $fond) {
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
		isset($forcer_lang) and $forcer_lang and ($forcer_lang !== 'non')
		and !_request('action')
		and $_SERVER['REQUEST_METHOD'] != 'POST'
	) {
		include_spip('inc/lang');
		verifier_lang_url();
	}

	$lang = !isset($_GET['lang']) ? '' : lang_select($_GET['lang']);

	// Charger l'aiguilleur des traitements derogatoires
	// (action en base SQL, formulaires CVT, AJax)
	if (_request('action') or _request('var_ajax') or _request('formulaire_action')) {
		include_spip('public/aiguiller');
		if (
			// cas des appels actions ?action=xxx
			traiter_appels_actions()
			or
			// cas des hits ajax sur les inclusions ajax
			traiter_appels_inclusions_ajax()
			or
			// cas des formulaires charger/verifier/traiter
			traiter_formulaires_dynamiques()
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
		$charset = isset($GLOBALS['meta']['charset']) ? $GLOBALS['meta']['charset'] : 'utf-8';
		$page['entetes']['Content-Type'] = 'text/html; charset=' . $charset;
		$html = true;
	} else {
		$html = preg_match(',^\s*text/html,', $page['entetes']['Content-Type']);
	}

	// Tester si on est admin et il y a des choses supplementaires a dire
	// type tableau pour y mettre des choses au besoin.
	$debug = ((_request('var_mode') == 'debug') or $tableau_des_temps) ? [1] : [];

	// affiche-t-on les boutons d'administration ? voir f_admin()
	$affiche_boutons_admin = ($html and (
			(isset($_COOKIE['spip_admin']) and (!isset($flag_preserver) or !$flag_preserver))
			or ($debug and include_spip('inc/autoriser') and autoriser('debug'))
			or (defined('_VAR_PREVIEW') and _VAR_PREVIEW)
		));

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
	$debug = ((_request('var_mode') == 'debug') or $tableau_des_temps) ? [1] : [];

	// Appel au debusqueur en cas d'erreurs ou de demande de trace
	// at last
	if ($debug) {
		// en cas d'erreur, retester l'affichage
		if ($html and ($affiche_boutons_admin or $debug)) {
			$var_mode_affiche = _request('var_mode_affiche');
			$var_mode_objet = _request('var_mode_objet');
			$GLOBALS['debug_objets'][$var_mode_affiche][$var_mode_objet . 'tout'] = ($var_mode_affiche == 'validation' ? $page['texte'] : '');
			echo erreur_squelette(false);
		}
	} else {
		if (
			isset($GLOBALS['meta']['date_prochain_postdate'])
			and $GLOBALS['meta']['date_prochain_postdate'] <= time()
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
