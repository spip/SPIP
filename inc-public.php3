<?php

if (!defined("_INC_PUBLIC")) {
 	define("_INC_PUBLIC", "1");
	include("inc-public-global.php3");
}
else {
	$cache_inclus = inclure_fichier($fond, $delais, $contexte_inclus);
	if (!$delais) $cache_supprimes[] = $cache_inclus; // message pour suppression
	include($cache_inclus);
}

?>
