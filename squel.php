<?php


	require 'test.inc';

	// calcule un test a partir d'un squelette
	$fond = preg_replace(',\.html$,', '', $_GET['test']);

	$debug = (_request('var_mode') == 'debug');
	define('_INTERDIRE_COMPRESSION_HTML',true);

	$flag_preserver = true;

	$dir_tests = substr(__DIR__, strlen(_SPIP_TEST_CHDIR) + 1);
	global $dossier_squelettes;
	$GLOBALS['dossier_squelettes'] = $dir_tests . '/tests/legacy/squelettes';

	$contenu = recuperer_fond($fond);
	
	//include 'spip.php';
	//$contenu = ob_get_contents();
	//ob_end_clean();

	$page = preg_replace(',^filtre:\s*.*\n?,im', '', $contenu);

	// filtres a appliquer
	if (preg_match_all(',^filtre:\s*(\w+),im', $contenu, $r, PREG_SET_ORDER))
		foreach ($r as $filtre)
			$page = $filtre[1]($page);

	$page = trim($page);

	echo $page;

	// Tester si on est admin et il y a des choses supplementaires a dire
	// type tableau pour y mettre des choses au besoin.
	$debug = ($debug OR (isset($tableau_des_temps) AND $tableau_des_temps)) ? array(1) : array();
	if ($debug) {
		$var_mode_affiche = _request('var_mode_affiche');
		$var_mode_objet = _request('var_mode_objet');
		$GLOBALS['debug_objets'][$var_mode_affiche][$var_mode_objet . 'tout'] = ($var_mode_affiche== 'validation' ? $page['texte'] :"");
		echo erreur_squelette(false);
	}


