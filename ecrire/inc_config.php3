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
		'articles_modif' => 'non',

		'activer_sites' => 'oui',
		'proposer_sites' => 0,
		'activer_syndic' => 'oui',
		'visiter_sites' => 'non',
		'moderation_sites' => 'non',

		'forums_publics' => 'posteriori',
		'accepter_inscriptions' => 'non',
		'prevenir_auteurs' => 'non',
		'activer_messagerire' => 'oui',
		'activer_imessage' => 'oui',
		'suivi_edito' => 'non',
		'quoi_de_neuf' => 'non',

		'activer_moteur' => 'non',
		'activer_statistiques' => 'oui',
		'activer_statistiques_ref' => 'non',

		'documents_article' => 'oui',
		'documents_rubrique' => 'non',
		'charset' => 'iso-8859-1'
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

	?>
	<FONT FACE='Georgia,Garamond,Times,serif' SIZE='3'>
	<P align="center"><B>ATTENTION !</B>

	<P align="justify">
	<img src="img_pack/warning.gif" alt="Avertissement" width="48" height="48" align="right">
	Les modifications effectu&eacute;es dans ces pages influent notablement sur le
	fonctionnement de votre site. Nous vous recommandons de ne pas y intervenir tant que vous n'&ecirc;tes pas
	familier du fonctionnement du syst&egrave;me SPIP. <P align="justify"><B>Plus
	g&eacute;n&eacute;ralement, il est fortement conseill&eacute;
	de laisser la charge de ces pages au webmestre principal de votre site.</B>
	</FONT>

	<?php

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
		$requete_appliquer = "UPDATE spip_articles SET accepter_forum='$accepter_forum'";
	}
	if ($requete_appliquer) spip_query($requete_appliquer);

	// Test du proxy : $tester_proxy est le bouton "submit"
	if ($tester_proxy) {
		if (!$test_proxy) {
			echo "Vous n'avez pas indiqu&eacute; d'adresse &agrave; tester !";
			exit;
		} else {
			include_ecrire("inc_sites.php3");
			$page = recuperer_page($test_proxy);
			if ($page)
				echo "<pre>".entites_html($page)."</pre>";
			else
				echo propre("{{Erreur !}} Impossible de lire la page <tt><html>$test_proxy</html></tt> &agrave; travers le proxy <tt><html>$http_proxy</html></tt>.") . aide('confhttpproxy');
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

		'activer_moteur',
		'activer_statistiques',
		'activer_statistiques_ref',

		'documents_article',
		'documents_rubrique',
		'charset'
	);
	while (list(,$i) = each($liste_meta))
		if (isset($GLOBALS[$i])) ecrire_meta($i, $GLOBALS[$i]);
	ecrire_metas();

	if ($purger_skel) {
		$hash = calculer_action_auteur("purger_squelettes");
		@header ("Location:../spip_cache.php3?purger_squelettes=oui&id_auteur=$connect_id_auteur&hash=$hash&redirect=".urlencode($clean_link->getUrl()));
	}
}


?>
