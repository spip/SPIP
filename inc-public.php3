<?php


// Distinguer une inclusion d'un appel initial

if (function_exists('inclure_page_lang'))
	inclure_page_lang($fond, $delais, $contexte_inclus);
else {
	define ("_INC_PUBLIC", 1);
	include ("ecrire/inc_version.php3");
	include_local('inc-public-global.php3');

	list($http_status, $page) = calcule_header_et_page($fond, $delai);
	echo $page;

	// Si le 404 a ete renvoye (page vide), donner un message approprie
	if ($http_status == 404) include(find_in_path('404.php3'));

	terminer_public_global();
}

?>
