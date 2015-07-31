<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_CONFIG")) return;
define("_ECRIRE_INC_CONFIG", "1");

include_ecrire ("inc_meta.php3");
include_ecrire ("inc_admin.php3");
include_ecrire ("inc_mail.php3");


//
// Appliquer les valeurs par defaut pour les options non initialisees
//
function init_config() {
	$liste_meta = array(
		'activer_breves' => 'oui',
		'config_precise_groupes' => 'non',
		'mots_cles_forums' =>  'non',
		'articles_surtitre' => 'oui',
		'articles_soustitre' => 'oui',
		'articles_descriptif' => 'oui',
		'articles_chapeau' => 'oui',
		'articles_ps' => 'oui',
		'articles_redac' => 'non',
		'articles_mots' => 'oui',
		'post_dates' => 'oui',
		'creer_preview' => 'non',
		'taille_preview' => 150,
		'articles_modif' => 'oui',
		
		'activer_sites' => 'oui',
		'proposer_sites' => 0,
		'activer_syndic' => 'oui',
		'visiter_sites' => 'non',
		'moderation_sites' => 'non',

		'forums_publics' => 'posteriori',
		'accepter_inscriptions' => 'non',
		'prevenir_auteurs' => 'non',
		'activer_messagerie' => 'oui',
		'activer_imessage' => 'oui',
		'suivi_edito' => 'non',
		'quoi_de_neuf' => 'non',
		'forum_prive_admin' => 'non',

		'activer_moteur' => 'non',
		'activer_statistiques' => 'oui',
		'activer_statistiques_ref' => 'non',

		'documents_article' => 'oui',
		'documents_rubrique' => 'non',
		'charset' => 'iso-8859-1',

		'creer_htpasswd' => 'non',
		
		'langue_site' => 'fr'
		
	);
	while (list($nom, $valeur) = each($liste_meta)) {
		if (!lire_meta($nom)) {
			ecrire_meta($nom, $valeur);
			$modifs = true;
		}
	}

	if ($modifs) ecrire_metas();
}


function avertissement_config() {
	debut_boite_info();

	echo "<FONT FACE='Georgia,Garamond,Times,serif' SIZE='3'>
	<P align='center'><B>"._T('avis_attention')."</B><P align='justify'>
	<img src='img_pack/warning.gif' alt='' width='48' height='48' align='right'>";

	echo _T('texte_inc_config');

	echo "</FONT>";

	fin_boite_info();
	echo "<p>&nbsp;<p>";
}


function bouton_radio($nom, $valeur, $titre, $actif = false) {
	static $id_label = 0;
	$texte = "<input type='radio' name='$nom' value='$valeur' id='label_$id_label'";
	if ($actif) {
		$texte .= ' checked';
		$titre = '<b>'.$titre.'</b>';
	}
	$texte .= "> <label for='label_$id_label'>$titre</label>\n";
	$id_label++;
	return $texte;
}


function afficher_choix($nom, $valeur_actuelle, $valeurs, $sep = "<br>") {
	while (list($valeur, $titre) = each($valeurs)) {
		$choix[] = bouton_radio($nom, $valeur, $titre, $valeur == $valeur_actuelle);
	}
	echo "\n".join($sep, $choix);
}


//
// Gestion des modifs
//

function appliquer_modifs_config() {
	global $clean_link, $connect_id_auteur;
	global $adresse_site, $email_webmaster, $post_dates, $tester_proxy, $test_proxy, $activer_moteur;
	global $forums_publics, $forums_publics_appliquer;
	global $charset, $charset_custom;

	$adresse_site = ereg_replace("/$", "", $adresse_site);

	// Purger les squelettes si un changement de meta les affecte
	if ($post_dates AND ($post_dates != lire_meta("post_dates")))
		$purger_skel = true;
	if ($forums_publics AND ($forums_publics != lire_meta("forums_publics")))
		$purger_skel = true;

	// Appliquer les changements de moderation forum
	// forums_publics_appliquer : futur, saufnon, tous
	$requete_appliquer = '';
	$accepter_forum = substr($forums_publics,0,3);
	if ($forums_publics_appliquer == 'saufnon') {
		$requete_appliquer = "UPDATE spip_articles SET accepter_forum='$accepter_forum' WHERE accepter_forum != 'non'";
	} else if ($forums_publics_appliquer == 'tous') {
		ecrire_meta('accepter_visiteurs', 'oui');
		$requete_appliquer = "UPDATE spip_articles SET accepter_forum='$accepter_forum'";
	}
	if ($requete_appliquer) spip_query($requete_appliquer);

	// Test du proxy : $tester_proxy est le bouton "submit"
	if ($tester_proxy) {
		if (!$test_proxy) {
			echo _T('info_adresse_non_indiquee');
			exit;
		} else {
			include_ecrire("inc_sites.php3");
			$page = recuperer_page($test_proxy);
			if ($page)
				echo "<pre>".entites_html($page)."</pre>";
			else
				echo _T('info_impossible_lire_page', array('test_proxy' => $test_proxy))."<html>$http_proxy</html></tt>.".aide('confhttpproxy');
			exit;
		}
	}

	// Activer le moteur : dresser la liste des choses a indexer
	if ($activer_moteur == 'oui') {
		include_ecrire('inc_index.php3');
		creer_liste_indexation();
	}

	if (isset($email_webmaster) AND email_valide($email_webmaster))
		ecrire_meta("email_webmaster", $email_webmaster);
	if ($charset == 'custom') $charset = $charset_custom;

	$liste_meta = array(
		'nom_site',
		'adresse_site',

		'activer_breves',
		'config_precise_groupes',
		'mots_cles_forums',
		'articles_surtitre',
		'articles_soustitre',
		'articles_descriptif',
		'articles_chapeau',
		'articles_ps',
		'articles_redac',
		'articles_mots',
		'post_dates',
		'creer_preview',
		'taille_preview',
		'articles_modif',
		
		'activer_sites',
		'proposer_sites',
		'activer_syndic',
		'visiter_sites',
		'moderation_sites',
		'http_proxy',

		'forums_publics',
		'accepter_inscriptions',
		'prevenir_auteurs',
		'activer_messagerie',
		'activer_imessage',
		'suivi_edito',
		'adresse_suivi',
		'quoi_de_neuf',
		'adresse_neuf',
		'jours_neuf',
		'forum_prive_admin',

		'activer_moteur',
		'activer_statistiques',
		'activer_statistiques_ref',

		'documents_article',
		'documents_rubrique',

		'charset'
	);
	while (list(,$i) = each($liste_meta))
		if (isset($GLOBALS[$i])) ecrire_meta($i, $GLOBALS[$i]);

	// langue_site : la globale est mangee par inc_version
	if ($lang = $GLOBALS['changer_langue_site']) {
		$lang2 = $GLOBALS['spip_lang'];
		if (changer_langue($lang)) {
			ecrire_meta('langue_site', $lang);
			changer_langue($lang2);
		}
	}

	ecrire_metas();

	// modifs de secu (necessitent une authentification ftp)
	$liste_meta = array(
		// 'secu_avertissement',	// n'existe plus !
		'creer_htpasswd'
	);
	while (list(,$i) = each($liste_meta))
		if (isset($GLOBALS[$i]) AND ($GLOBALS[$i] != lire_meta($i)))
			$modif_secu=true;
	if ($modif_secu) {
		include_ecrire('inc_admin.php3');
		$admin = _T('info_modification_parametres_securite');
		debut_admin($admin);
		reset($liste_meta);
		while (list(,$i) = each($liste_meta))
			if (isset($GLOBALS[$i])) ecrire_meta($i, $GLOBALS[$i]);
		ecrire_metas();
		fin_admin($admin);
	}


	if ($purger_skel) {
		$hash = calculer_action_auteur("purger_squelettes");
		@header ("Location:../spip_cache.php3?purger_squelettes=oui&id_auteur=$connect_id_auteur&hash=$hash&redirect=".urlencode($clean_link->getUrl()));
	}
}


?>
