<?php

// Page inclue ?
if (defined("_INC_PUBLIC")) {
	$page = inclure_page($fond, $delais, $contexte_inclus, $fichier_inclus);

	/* if ($page['process_ins']) {
		eval('?' . '>' .  $page['texte']); 
	} else { 
		echo $page['texte']; 
	} */

	eval('?' . '>' .  $page['texte']); 
}

// Premier appel inc-public
else {
	define("_INC_PUBLIC", "1");
	include ("ecrire/inc_version.php3");

	//
	// Initialisations
	//

	// Regler le $delais par defaut
	if ($INSECURE['fond'] || $INSECURE['delais'])
		exit;
	if (!isset($delais))
		$delais = 1 * 3600;
	if ($recherche)
		$delais = 0;

	// les meta
	include_ecrire("inc_meta.php3");

	// multilinguisme
	if ($GLOBALS['HTTP_COOKIE_VARS']['spip_session'] OR
	($GLOBALS['PHP_AUTH_USER'] AND !$ignore_auth_http)) {
		include_ecrire ("inc_session.php3");
		verifier_visiteur();
	}
 	if ($GLOBALS['forcer_lang']) {
		include_ecrire('inc_lang.php3');
		verifier_lang_url();
	}
	if ($lang = $GLOBALS['HTTP_GET_VARS']['lang']) {
		include_ecrire('inc_lang.php3');
		lang_select($lang);     
	}


	// Ajout_forum est une HTTP_GET_VARS installee par retour_forum dans
	// inc-forum.
	// Il s'agit de memoriser les HTTP_POST_VARS, afin de mettre en base
	// les valeurs transmises, avant reaffichage du formulaire avec celles-ci.
	// En cas de validation finale ca redirige vers l'URL ayant provoque l'appel
	// au lieu de laisser l'URL appelee resynthetiser le formulaire.
	if ($ajout_forum) {
		$redirect = '';
		include('inc-messforum.php3');
		if ($redirect) {
			@header("Location: $redirect");
			exit();
		}
	}

	include_local ("inc-public-global.php3");
	$page = afficher_page_globale ($fond, $delais, $use_cache);
	eval('?' . '>' . $page['texte']);
	terminer_public_global($use_cache, $page['cache']);
}

?>