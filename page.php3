<?php

if (!$fond = $_GET["fond"]) {
	$fond = $contexte_inclus['fond'];
}

// Securite : le squelette *doit* exister dans squelettes/
if (strstr($fond, '..')) {
	die ("Faut pas se gener");
}
if (!function_exists('find_in_path')) {
	include ('ecrire/inc_version.php3');
}
if (preg_match(',^squelettes/,', find_in_path("$fond.html"))) {
	include ("inc-public.php3");
} else {
	spip_log("page.php3: le squelette $fond.html *doit* se trouver dans squelettes/");
}


?>
