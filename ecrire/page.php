<?php

// Appel spip direct ?
if (!function_exists('find_in_path')) {
	if (defined('_DIR_RESTREINT_ABS') AND
	@file_exists(_DIR_RESTREINT_ABS.'inc_version.php')) {
		include _DIR_RESTREINT_ABS.'inc_version.php';
	}

	## note: ce passage permet d'appeler ecrire/page.php?page=plan
	## ce qui invoque compilateur, cache etc... pas encore
	## totalement fonctionnel
	else if (file_exists('./inc_version.php')) {
		define('_DIR_RESTREINT_ABS', basename(dirname(__FILE__)).'/');
		chdir('..');
		include _DIR_RESTREINT_ABS.'inc_version.php';
	}

	else
		die('stupid death...');
}

// Est-ce une action ?
if ($action = _request('action')) {
	$var_f = include_fonction('spip_action_' . $action);
	$var_f();
	if ($redirect) redirige_par_entete(urldecode($redirect));
	exit;
}

// Sinon, reglage du $fond

# passe par INCLURE()
if (isset($contexte_inclus['fond']))
	$fond = $contexte_inclus['fond'];

# passe par l'url
else if (isset($_GET['page'])) {
	$fond = $_GET['page'];
	// Securite
	if (strstr($fond, '/'))
		die (_L("Faut pas se gener"));

# par defaut
} else
	$fond = 'sommaire';


// Particularites de certains squelettes
if ($fond == 'login')
	$forcer_lang = true;

// Chercher le fond et erreur s'il est absent
if (!find_in_path($fond.'.html')) {
	spip_log("page: find_in_path ne trouve pas le squelette $fond");
	$fond = '404';
}

include (_DIR_INCLUDE . 'public.php');

?>
