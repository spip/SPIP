<?php

// Page inclue ?
if (defined("_INC_PUBLIC")) {
	$page = inclure_page($fond, $delais, $contexte_inclus, $fichier_inclus);
	$contenu = $page['texte'];
	// Traiter var_recherche pour surligner les mots
	if ($GLOBALS['var_recherche']) {
	  include_ecrire("inc_surligne.php3");
	  $contenu = surligner_mots($contenu, $var_recherche);
		}
	if ($page['process_ins'] == 'php') {
		eval('?' . '>' . $contenu); // page 'php'
	} else
		echo $contenu; // page tout 'html'

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
	if ($var_debug AND ($code_activation_debug == $var_debug
	OR ($code_activation_debug == ''
	AND $auteur_session['statut'] == '0minirezo')
	)) {
		include_local('inc-admin.php3');
		$recalcul = 'oui';
		$var_debug = true;
		spip_log('debug !');
	} else
		$var_debug = false;

	// Faut-il preparer les boutons d'admin ?
	$affiche_boutons_admin = (!$flag_preserver
				  AND ($HTTP_COOKIE_VARS['spip_admin']
				       OR $HTTP_COOKIE_VARS['spip_debug']));

	// inc-admin contient aussi le traitement d'erreur
	include_local('inc-admin.php3');
	include_local ("inc-public-global.php3");

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
 #entre gzip et debug, faut revoir
		#@header('Content-Length: '.strlen($contenu));
#		@header('Connection: close');

		$contenu = $page['texte'];
		spip_log($page['process_ins'] . strlen($contenu));
		// Traiter var_recherche pour surligner les mots
		if ($var_recherche) {
			include_ecrire("inc_surligne.php3");
			$contenu = surligner_mots($contenu, $var_recherche);
		}

		// Ajouter les boutons admins (les normaux)
		if ($affiche_boutons_admin)
			$contenu = calcul_admin_page($use_cache, $contenu);
		spip_log($page['process_ins'] . $contenu);

		if ($page['process_ins'] == 'html') 
		  {if (!$var_debug) echo $contenu;}
		else {

		// Ici on va ruser pour intercepter les erreurs (meme les FATAL)
		// dans le eval : on envoie le bouton debug, et on le supprime
		// de l'autre cote ; de cette facon, si on traverse sans encombre,
		// on est propre, et sinon on a le bouton

			if ($affiche_boutons_admin) {

		// recuperer les parse errors etc., type "FATAL" (cf. infra)
			  if ($auteur_session['statut'] == '0minirezo') {
				$page_principale = $page;
				if (function_exists('set_error_handler'))
					set_error_handler('spip_error_handler');
			  }
			  
			}

		//
		// Evaluer la page php
		//
			if (!$var_debug)
			  $res = eval('?' . '>' . $contenu); 
			else {
			  ob_start(); 
			  $res = eval('?' . '>' . $contenu);
			  $contenu = ob_get_contents(); 
			  ob_end_clean();
			}
                      
		// en cas d'erreur afficher un message + demander les boutons de debug
			if ($affiche_boutons_admin
			    AND $auteur_session['statut'] == '0minirezo') {
			  if (function_exists('restore_error_handler'))
			    restore_error_handler();
			  if ($res === false)
			    spip_error_handler(1,'erreur de compilation','','','');
			}
			
		}
	// Passer la main au debuggueur le cas echeant 
	if ($var_debug)	
	  debug_dumpfile('');
	else {
		if (count($tableau_des_erreurs) > 0)
			affiche_erreurs_execution_page ();
	}
	terminer_public_global($use_cache, $page['cache']);
}

?>
