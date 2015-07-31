<?php

// Page inclue ?
if (defined("_INC_PUBLIC")) {
	$page = inclure_page($fond, $delais, $contexte_inclus, $fichier_inclus);

	if ($page['process_ins'] == 'php') {
		eval('?' . '>' . $page['texte']); // page 'php'
	} else
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

	// authentification du visiteur
	if ($HTTP_COOKIE_VARS['spip_session'] OR
	($PHP_AUTH_USER AND !$ignore_auth_http)) {
		include_ecrire ("inc_session.php3");
		verifier_visiteur();
	}

	// multilinguisme
	if ($forcer_lang AND ($forcer_lang!=='non') AND empty($HTTP_POST_VARS)) {
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
		reponse_confirmation($id_article, $val_confirm);
	}


	// demande de debug ?
	if ($var_debug == $code_activation_debug AND $code_activation_debug <> '') {
		// verifier que ce n'est pas un robot, en posant un cookie,
		// car ces calculs coutent trop cher pour pouvoir etre "aspires".
		// (pour la securite, cf. $code_activation_debug dans inc_version)
		// ce cookie ne dure qu'une heure, car il sert aussi a afficher
		// le bouton 'debug' sur toutes les pages
		include_local('inc-admin.php3');
		if ($var_debug = verifie_cookie_debug()) {
			$recalcul = 'oui';
			$var_debug = true;
			spip_log('debug !');
		}
	} else
		$var_debug = false;

	// Faut-il preparer les boutons d'admin ?
	if ($affiche_boutons_admin = (!$flag_preserver
	AND $HTTP_COOKIE_VARS['spip_admin'])) {
		include_local('inc-admin.php3');
	}

	include_local ("inc-public-global.php3");

	$page = afficher_page_globale ($fond, $delais, $use_cache);

	// Demarrer un buffer pour le content-length (ou le debug)
	if ($flag_ob)
		ob_start();

	// Afficher la page ; le cas PHP est assez rigolo avec le traitement
	// d'erreurs
	if ($page['process_ins'] == 'php') {

		// Ici on va ruser pour intercepter les erreurs (meme les FATAL)
		// dans le eval : on envoie le bouton debug, et on le supprime
		// de l'autre cote ; de cette facon, si on traverse sans encombre,
		// on est propre, et sinon on a le bouton
		if ($affiche_boutons_admin) {

			// recuperer les parse errors etc., type "FATAL" (cf. infra)
			if ($auteur_session['statut'] == '0minirezo') {
				$tableau_des_erreurs = array();
				$page_principale = $page;
				if (function_exists('set_error_handler'))
					set_error_handler('spip_error_handler');
			}

			if ($flag_ob)
				echo afficher_boutons_admin('', 'debug').'<!-- @@START@@ -->';
		}

		//
		// Evaluer la page php
		//
		$s = eval('?' . '>' . $page['texte']); // page 'php'

		// en cas d'erreur afficher un message + demander les boutons de debug
		if ($affiche_boutons_admin
		AND $auteur_session['statut'] == '0minirezo') {
			if (function_exists('restore_error_handler'))
				restore_error_handler();
			if ($s === false
			OR count($tableau_des_erreurs) > 0)
				affiche_erreurs_execution_page ();
		}

		// supprimer les boutons de debug type "FATAL"
		if ($affiche_boutons_admin AND $flag_ob) {
			$contenu = ob_get_contents();
			ob_end_clean();
			$contenu = preg_replace('/^(.*?)<!-- @@START@@ -->/ms', '',
				$contenu);
			ob_start();
			echo $contenu;
		}

	} else
		echo $page['texte']; // page tout 'html'

	// Passer la main au debuggueur le cas echeant
	if ($var_debug)
		debug_page();

	//
	// Et l'envoyer si on est bufferise (ce qu'il faut souhaiter)
	// avec les entetes de cache
	//
	if ($flag_ob) {
		// recuperer le contenu final de la page
		$contenu = ob_get_contents();
		@ob_end_clean();

		// Traiter var_recherche pour surligner les mots
		if ($var_recherche) {
			include_ecrire("inc_surligne.php3");
			$contenu = surligner_mots($contenu, $var_recherche);
		}

		// Ajouter les boutons admins (les normaux)
		if ($affiche_boutons_admin)
			$contenu = calcul_admin_page($use_cache, $contenu);

		// Interdire au client de cacher un login, un admin ou un recalcul
		if ($flag_dynamique OR ($recalcul == 'oui')
		OR $HTTP_COOKIE_VARS['spip_admin']) {
			@header("Cache-Control: no-cache,must-revalidate");
			@header("Pragma: no-cache");
		// Pour les autres donner l'heure de modif
		} else if ($lastmodified)
			@Header ("Last-Modified: ".http_gmoddate($lastmodified)." GMT");

		// Afficher (pour de vrai)
		@header('Content-Length: '.strlen($contenu)); # ca donne quoi en gzip ?
		@header('Connection: close');
		echo $contenu;

		// Masquer la suite en ne renvoyant rien meme en cas d'affichage
		// Voir commentaire dans inc-public-global.php3
		if (masquer_les_bugs(true)) ob_start('masquer_les_bugs');
	}

	// en absence de buffering on ajoute les boutons en fin de page
	else if ($affiche_boutons_admin)
		echo afficher_boutons_admin($use_cache);

	terminer_public_global($use_cache, $page['cache']);
}

?>
