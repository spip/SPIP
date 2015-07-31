<?php

// Page inclue ?
if (defined("_INC_PUBLIC")) {
	$page = inclure_page($fond, $delais, $contexte_inclus, $fichier_inclus);

	if ($page['process_ins'] == 'php')
		eval('?' . '>' . $page['texte']); // page 'php'
	else
		echo $page['texte']; // page tout 'html'

	if ($page['lang_select'])
		lang_dselect();
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
	if ($HTTP_COOKIE_VARS['spip_session'] OR
	($PHP_AUTH_USER AND !$ignore_auth_http)) {
		include_ecrire ("inc_session.php3");
		verifier_visiteur();
	}
	if ($forcer_lang) {
		include_ecrire('inc_lang.php3');
		verifier_lang_url();
	}
	if ($HTTP_GET_VARS['lang']) {
		include_ecrire('inc_lang.php3');
		lang_select($HTTP_GET_VARS['lang']);     
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

	// Faut-il effacer des pages invalidees ?
	if (lire_meta('invalider')) {
		include_ecrire('inc_invalideur.php3');
		retire_caches();
	}

	include_local ("inc-public-global.php3");
	$page = afficher_page_globale ($fond, $delais, $use_cache);

	afficher_page_si_demande_admin ('page', $page['texte'], $page['cache']);

	// Recuperer la resultat dans un buffer
	// a la fois pour le content-length et le var_recherche
	if ($flag_ob)
		ob_start();

	// envoyer la page
	if ($page['process_ins'] == 'php')
		eval('?' . '>' . $page['texte']); // page 'php'
	else
		echo $page['texte']; // page tout 'html'

	// surlignement des mots recherches
	unset ($envoi);
	if ($flag_ob) {
		$envoi = ob_get_clean();
		if ($var_recherche AND $flag_pcre AND !$flag_preserver) {
			include_ecrire("inc_surligne.php3");
			$envoi = surligner_mots($envoi, $var_recherche);
		}
	}

	if ($envoi) {
#		avec la compression cet entete provoque la mort de
#		la commande 'ab -n100 -c10 http://....'
#		@header("Content-Length: ".strlen($envoi));
		@header("Connection: close");
		echo $envoi;
	}

	terminer_public_global($use_cache, $page['cache']);
}

?>
