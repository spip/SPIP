<?php

// Appel spip
if (!function_exists('find_in_path')) {
	define('_SPIP_PAGE',1); # ne pas mourir si on passe le $fond
	include ("ecrire/inc_version.php");
}
// Reglage du $fond
if (isset($contexte_inclus['fond']))
	$fond = $contexte_inclus['fond'];
else if (isset($_GET["fond"]))
	$fond = $_GET["fond"];
else
	$fond = '404';

// Securite 
if (strstr($fond, '/'))
	die (_L("Faut pas se gener"));

if (!find_in_path("$fond.html")) {
	spip_log("page: find_in_path ne trouve pas le squelette $fond");
	echo _T('info_erreur_squelette2',
		array('fichier' => htmlspecialchars($fond)));
	$fond = '404';
}
include ("inc-public.php3");

?>
