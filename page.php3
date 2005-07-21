<?php

// Reglage du $fond
if (isset($contexte_inclus['fond']))
	$fond = $contexte_inclus['fond'];
else if (isset($_GET["fond"]))
	$fond = $_GET["fond"];
else
	$fond = '404';

// Reglage du $delais
// par defaut : la valeur existante (inclusion) ou sinon SPIP fera son reglage
if (isset($contexte_inclus['delais']))
	$delais = $contexte_inclus['delais'];

// Securite 
if (strstr($fond, '/')) {
	die ("Faut pas se gener");
}
if (!function_exists('find_in_path')) {
	include ('ecrire/inc_version.php3');
}
if (find_in_path("$fond.html")) {
	include ("inc-public.php3");
} else {
	spip_log("page.php3: find_in_path ne trouve pas le squelette $fond");
}


?>
