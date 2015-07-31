<?php


// Distinguer une inclusion d'un appel initial

if (defined("_INC_PUBLIC_GLOBAL")) {
	$page = inclure_page($fond, $delais, $contexte_inclus);
	if ($page['process_ins'] == 'html')
		echo $page['texte'];
	else
		eval('?' . '>' . $page['texte']);

	if ($page['lang_select'])
		lang_dselect();

} else {
	define ("_INC_PUBLIC", 1);
	include ("ecrire/inc_version.php3");
	include_local('inc-public-global.php3');

	list($http_status, $page) = calcule_header_et_page($fond, $delai);
	echo $page;

	// Si le 404 a ete renvoye (page vide), donner un message approprie
	if ($http_status == 404) {
		$contexte_inclus = array('erreur_aucun' => message_erreur_404());
		include(find_in_path('404.php3'));
	}

	terminer_public_global();
}

?>
