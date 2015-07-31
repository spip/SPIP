<?php

if (!defined("_INC_PUBLIC")) {
	define("_INC_PUBLIC", "1");
	include("inc-public-global.php3");
}
else {
	$fichier_requete = $fond;
	if (is_array($contexte_inclus)) {
		reset($contexte_inclus);
		while(list($key, $val) = each($contexte_inclus)) $fichier_requete .= '&'.$key.'='.$val;
	}
	$fichier_cache = generer_nom_fichier_cache($fichier_requete);
	$chemin_cache = "CACHE/".$fichier_cache;

	// Faire varier aleatoirement le delai (50 - 150 %)
	// afin d'obtenir des recalculs non simultanes
	srand(time() * microtime());
	$delais = $delais / 2 + $delais * rand(0, 255) / 256;

	$use_cache = utiliser_cache($chemin_cache, $delais);

	if (!$use_cache) {
//		echo "$fichier_cache recalcul<p>";

		include_ecrire("inc_connect.php3");
		include_local("inc-calcul.php3");
		$fond = chercher_squelette($fond, $contexte_inclus['id_rubrique']);
		$page = calculer_page($fond, $contexte_inclus);
		if ($page) {
			$f = fopen($chemin_cache, "wb");
			fwrite($f, $page);
			fclose($f);
		}
	}
//	else echo "$fichier_cache cache<p>";

	include ($chemin_cache);
	if ($GLOBALS['flag_apc']) {
		apc_rm($chemin_cache);
	}
}

?>