<?php

if (!defined("_INC_PUBLIC")) {
 	define("_INC_PUBLIC", "1");
	include("inc-public-global.php3");
}
else {
	include(inclure_fichier($fond, $delais, $contexte_inclus));
}

?>