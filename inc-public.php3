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
		if ($redirect) redirige_par_entete($redirect);
	}
	if ($val_confirm) {
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
	AND ($HTTP_COOKIE_VARS['spip_admin']
	OR $HTTP_COOKIE_VARS['spip_debug'])))
		include_local('inc-formulaire_admin.php3');

	$tableau_des_erreurs = array();
	$page = afficher_page_globale ($fond, $delais, $use_cache);

	if (!$flag_preserver) {
	// Interdire au client de cacher un login, un admin ou un recalcul
		if ($flag_dynamique OR $var_mode
		OR $HTTP_COOKIE_VARS['spip_admin']) {
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

			// en cas d'erreur lors du eval, afficher un message
			// et forcer les boutons de debug
			if ($res === false AND $affiche_boutons_admin
			AND $auteur_session['statut'] == '0minirezo') {
				include_ecrire('inc_debug_sql.php3');
				erreur_squelette(_L('erreur d\'execution de la page'));
			}
		}
	}

	// Passer la main au debuggueur le cas echeant 
	if ($var_mode == 'debug') {
		include_ecrire("inc_debug_sql.php3");
		debug_dumpfile('',$var_mode_objet,$var_mode_affiche);
		exit;
	} else if (count($tableau_des_erreurs) > 0
	AND $affiche_boutons_admin)
		affiche_erreurs_page ($tableau_des_erreurs);

	// Traiter var_recherche pour surligner les mots
	if ($var_recherche) {
		include_ecrire("inc_surligne.php3");
		$contenu = surligner_mots($contenu, $var_recherche);
	}

	// Ajouter au besoin la CSS des boutons admins
	if ($affiche_boutons_admin) {
		include_local("inc-admin.php3");
		$contenu = perso_admin($contenu);
	}

	// Afficher le resultat final
	echo $contenu;

	// Ajouter les boutons admins (les normaux) si absents
	// (ce sera apres la balise /html mais tant pis)
	if ($affiche_boutons_admin)
		inclure_balise_dynamique(balise_formulaire_admin_dyn($id_article, $id_breve, $id_rubrique, $id_mot, $id_auteur));

	// Taches de fin
	terminer_public_global();
 }

?>
