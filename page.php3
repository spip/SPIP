<?php

// Appel spip
if (!function_exists('find_in_path')) {
	define('_SPIP_PAGE',1); # ne pas mourir si on passe le $fond
	include ('ecrire/inc_version.php3');
}
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
if (strstr($fond, '/')
OR preg_match(',^formulaire_,i', $fond)) {
	die ("Faut pas se gener");
}
if (!find_in_path("$fond.html")) {
	spip_log("page.php3: find_in_path ne trouve pas le squelette $fond");
	$fond = '404';
}
include ("inc-public.php3");

?>
