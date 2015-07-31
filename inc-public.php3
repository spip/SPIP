<?php


// Distinguer une inclusion d'un appel initial

if (function_exists('inclure_page_lang'))
	inclure_page_lang($fond, $delais, $contexte_inclus);
else {
	include ("ecrire/inc_version.php3");
	include_local('inc-public-global.php3');

	list($http_status, $page) = calcule_header_et_page($fond, $delai);

	// Si le 404 a ete renvoye (page vide), donner un message approprie
	// Page n'est plus necessairement vide a cause des boutons admin

	if ($http_status == 404) {
		$qcq = _T('public:aucun_' . $fond);
		if (!$qcq) $qcq = _T('public:aucune_' . $fond);
		if (!$qcq) $qcq = _T('public:texte_vide');
		$page .= $qcq;
	}

	echo $page;
	terminer_public_global();
}

?>
