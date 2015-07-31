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


	// Ajout_forum (pour les forums) et $val_confirm signalent des modifications
	// a faire avant d'afficher la page
	if ($ajout_forum) {
		$redirect = '';
		include('inc-messforum.php3');
		if ($redirect) {
			@header("Location: $redirect");
			exit();
		}
	}
	if ($val_confirm) {
		// il nous faut id_article ! C'est donc encore a nettoyer...
		include_local('inc-calcul.php3');
		calculer_contexte();
		include_local('inc-formulaires.php3');
		reponse_confirmation($id_article);
	}


	// Faut-il effacer des pages invalidees ?
	if (lire_meta('invalider')) {
		include_ecrire('inc_invalideur.php3');
		include_ecrire('inc_meta.php3');
		lire_metas();
		if (lire_meta('invalider'))
			retire_caches();
	}

	include_local ("inc-public-global.php3");
	$page = afficher_page_globale ($fond, $delais, $use_cache);

	afficher_page_si_demande_admin ('page', $page['texte'], $page['cache']);

	// Afficher la page
	if ($page['process_ins'] == 'php')
		eval('?' . '>' . $page['texte']); // page 'php'
	else
		echo $page['texte']; // page tout 'html'

	//
	// Et l'envoyer si on est bufferise (ce qu'il faut souhaiter)
	// avec les entetes de cache
	//
	if ($flag_ob) {
		// Interdire au client de cacher un login, un admin ou un recalcul
		if ($flag_dynamique OR ($recalcul == 'oui')
		OR $HTTP_COOKIE_VARS['spip_admin']) {
			@header("Cache-Control: no-cache,must-revalidate");
			@header("Pragma: no-cache");
		// Pour les autres donner l'heure de modif
		} else if ($lastmodified)
			@Header ("Last-Modified: ".http_gmoddate($lastmodified)." GMT");

		// Appeler le buffer (var_recherche + compression)
		// NOTE: COMMENTER CETTE LIGNE FERA S'AFFICHER D'EVENTUELS
		// MESSAGES D'ERREUR DE SPIP_CRON()
		@ob_end_flush();
	}

	terminer_public_global($use_cache, $page['cache']);
}

?>
