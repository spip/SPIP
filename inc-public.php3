<?php
if (defined("_INC_PUBLIC")) {
	 inclure_page_lang($fond, $delais, $contexte_inclus);
}
// Premier appel inc-public
else {
	define("_INC_PUBLIC", "1");
	include ("ecrire/inc_version.php3");

	//
	// Initialisations
	//

	// Regler le $delais par defaut
	if (is_insecure('fond') || is_insecure('delais'))
		exit;
	if (!isset($delais))
		$delais = 1 * 3600;
	if ($recherche)
		$delais = 0;

	// authentification du visiteur
	if ($_COOKIE['spip_session'] OR
	($PHP_AUTH_USER AND !$ignore_auth_http)) {
		include_ecrire ("inc_session.php3");
		verifier_visiteur();
	}
	// multilinguisme
	if ($forcer_lang AND ($forcer_lang!=='non') AND empty($GLOBALS['_POST'])) {
		include_ecrire('inc_lang.php3');
		verifier_lang_url();
	}
	if ($GLOBALS['_GET']['lang']) {
		include_ecrire('inc_lang.php3');
		lang_select($_GET['lang']);
	}

	// Si envoi pour un forum, enregistrer puis rediriger

	if (strlen($GLOBALS['_POST']['confirmer_forum']) > 0
	OR ($GLOBALS['_POST']['afficher_texte']=='non'
		AND $GLOBALS['_POST']['ajouter_mot'])) {
		include('inc-messforum.php3');
		redirige_par_entete(enregistre_forum());
	}

	// si signature de petition, l'enregistrer avant d'afficher la page
	// afin que celle-ci contienne la signature

	if ($GLOBALS['_GET']['val_confirm']) {
		include_local('inc-formulaire_signature.php3');
		reponse_confirmation($id_article, $val_confirm);
	}

	include_local ('inc-public-global.php3');

	//  refus du debug si pas dans les options generales ni admin connecte
	if ($var_mode=='debug') {
		if (($code_activation_debug == 'oui')
		OR $auteur_session['statut'] == '0minirezo')
			spip_log('debug !');
		else
			$var_mode = false; 
	}

	// est-on admin ?
	if ($affiche_boutons_admin = (!$flag_preserver
	AND ($_COOKIE['spip_admin']
	OR $_COOKIE['spip_debug'])))
		include_local('inc-formulaire_admin.php3');

	$tableau_des_erreurs = array();
	$page = afficher_page_globale ($fond, $delais, $use_cache);

	if (!$flag_preserver) {
	// Interdire au client de cacher un login, un admin ou un recalcul
		if ($flag_dynamique OR $var_mode
		OR $_COOKIE['spip_admin']) {
			@header("Cache-Control: no-cache,must-revalidate");
			@header("Pragma: no-cache");
	// Pour les autres donner l'heure de modif
		} else if ($lastmodified)
			@Header ("Last-Modified: ".http_gmoddate($lastmodified)." GMT");

	// si le squelette est nul se rabattre sur l'entete standard
		if ($page['texte']) 
			@header("Content-Type: text/html; charset=".lire_meta('charset'));
		else
			echo debut_entete($fond);
	}
	define('spip_active_ob', $flag_ob AND
		($var_mode == 'debug' OR $var_recherche OR $affiche_boutons_admin));

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
			eval('?' . '>' . $page['texte']);
			$contenu = '';
		} else {
			ob_start(); 
			$res = eval('?' . '>' . $page['texte']);
			$contenu = ob_get_contents(); 
			ob_end_clean();

			// en cas d'erreur lors du eval,
			// la memoriser dans le tableau des erreurs
			// et forcer les boutons de debug.
			// On ne revient pas ici si le nb d'erreurs > 4
			if ($res === false AND $affiche_boutons_admin
			AND $auteur_session['statut'] == '0minirezo') {
				include_ecrire('inc_debug_sql.php3');
				erreur_squelette(_T('zbug_erreur_execution_page'));
			}
		}
	}

	// Passer la main au debuggueur le cas echeant 
	if ($var_mode == 'debug') {
		include_ecrire("inc_debug_sql.php3");
		debug_dumpfile('',$var_mode_objet,$var_mode_affiche);
		exit;
	} else if (count($tableau_des_erreurs) > 0 AND $affiche_boutons_admin)
	  $contenu = affiche_erreurs_page($tableau_des_erreurs) . $contenu;

	// Traiter var_recherche pour surligner les mots
	if ($var_recherche) {
		include_ecrire("inc_surligne.php3");
		$contenu = surligner_mots($contenu, $var_recherche);
	}

	// Afficher au besoin les boutons admins
	if ($affiche_boutons_admin) {
		include_local("inc-admin.php3");
		$contenu = affiche_boutons_admin($contenu);
	}

	// Afficher le resultat final
	echo $contenu;

	// Taches de fin
	terminer_public_global();
 }

?>
