<?php

// Page inclue ?
if (defined("_INC_PUBLIC")) {
	$page = inclure_page($fond, $delais, $contexte_inclus, $fichier_inclus);

	if ($page['process_ins'] == 'html')
		echo $page['texte'];
	else
		eval('?' . '>' . $page['texte']);

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

	include_local ('inc-public-global.php3');

	// demande de debug ?
	if ($var_debug AND ($code_activation_debug == $var_debug
		OR $auteur_session['statut'] == '0minirezo'
	)) {
		$recalcul = 'oui';
		$var_debug = true;
		spip_log('debug !');
	} else
		$var_debug = false;

	// Faut-il preparer les boutons d'admin ?
	$affiche_boutons_admin = (!$flag_preserver
		AND ($HTTP_COOKIE_VARS['spip_admin']
			OR $HTTP_COOKIE_VARS['spip_debug']));
	if ($affiche_boutons_admin)
		include_local('inc-admin.php3');

	$tableau_des_erreurs = array();
	$page = afficher_page_globale ($fond, $delais, $use_cache);

	// Interdire au client de cacher un login, un admin ou un recalcul
	if ($flag_dynamique OR ($recalcul == 'oui')
			OR $HTTP_COOKIE_VARS['spip_admin']) {
			@header("Cache-Control: no-cache,must-revalidate");
			@header("Pragma: no-cache");
		// Pour les autres donner l'heure de modif
		} else if ($lastmodified)
			@Header ("Last-Modified: ".http_gmoddate($lastmodified)." GMT");

		@header("Content-Type: text/html; charset=".lire_meta('charset'));

		// Faudra-t-il post-traiter la page ?
		define('spip_active_ob', $flag_ob AND
		($var_debug OR $var_recherche OR $affiche_boutons_admin));

		// Cas d'une page contenant uniquement du HTML :
		if ($page['process_ins'] == 'html') {
			if (!spip_active_ob) {
				echo $page['texte'];
				$contenu = '';
			} else
				$contenu = $page['texte'];
		}

		// Cas d'une page contenant du PHP :
		else {

			// Evaluer la page
			if (!spip_active_ob) {
				$res = eval('?' . '>' . $page['texte']);
				$contenu = '';
			} else {
				ob_start(); 
				$res = eval('?' . '>' . $page['texte']);
				$contenu = ob_get_contents(); 
				ob_end_clean();
			}
                      
			// en cas d'erreur afficher un message + demander boutons debug
			if ($affiche_boutons_admin
			AND $auteur_session['statut'] == '0minirezo') {
				if (function_exists('restore_error_handler'))
					restore_error_handler();
				if ($res === false)
					spip_error_handler(1,'erreur de compilation','','','');
			}

		}

		// Passer la main au debuggueur le cas echeant 
		if ($var_debug) {
			debug_dumpfile('');
			exit;
		} else if (count($tableau_des_erreurs) > 0)
			affiche_erreurs_execution_page ();

		// Traiter var_recherche pour surligner les mots
		if ($var_recherche) {
			include_ecrire("inc_surligne.php3");
			$contenu = surligner_mots($contenu, $var_recherche);
		}

		// Ajouter les boutons admins (les normaux)
		if ($affiche_boutons_admin)
			$contenu = calcul_admin_page($use_cache, $contenu);

		echo $contenu;

		terminer_public_global($use_cache, $page['cache']);
}

?>
