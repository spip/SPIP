<?php

// Appel spip
if (!function_exists('find_in_path')) {
	if (@file_exists('ecrire/inc_version.php')) {
		include 'ecrire/inc_version.php';
	} else exit;
}

// Reglage du $fond
if (isset($contexte_inclus['fond']))
	$fond = $contexte_inclus['fond'];
else if (isset($_GET['page']))
	$fond = $_GET['page'];
else
	$fond = 'sommaire';

// Securite 
if (strstr($fond, '/'))
	die (_L("Faut pas se gener"));

// Particularites de certains squelettes
if ($fond == 'login')
	$forcer_lang = true;

if (!find_in_path($fond.'.html')) {
	spip_log("page: find_in_path ne trouve pas le squelette $fond");
	echo _T('info_erreur_squelette2',
		array('fichier' => htmlspecialchars($fond)));
	$fond = '404';
}
include (_DIR_INCLUDE . 'public.php');

?>
