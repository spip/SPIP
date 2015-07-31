<?php

include ("inc.php3");

include_ecrire ("inc_config.php3");

function mySel($varaut,$variable){
		$retour= " VALUE=\"$varaut\"";

	if ($variable==$varaut){
		$retour.= " SELECTED";
	}

	return $retour;
}


debut_page(_T('titre_page_configuration'), "administration", "configuration");

echo "<br><br><br>";
gros_titre(_T('titre_configuration'));
barre_onglets("configuration", "contenu");


debut_gauche();

debut_droite();

if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
	echo _T('avis_non_acces_page');
	fin_page();
	exit;
}


//
// Modifications
//

init_config();
if ($changer_config == 'oui') {
	appliquer_modifs_config();
}
else {
	$forums_publics = lire_meta("forums_publics");
	if (!$forums_publics) {
		ecrire_meta("forums_publics", "posteriori");
		ecrire_metas();
	}
}

lire_metas();

avertissement_config();

//
// Afficher les options de config
//

echo "<form action='configuration.php3' method='post'>";
echo "<input type='hidden' name='changer_config' value='oui'>";
debut_cadre_relief("racine-24.gif");

	$nom_site = entites_html(lire_meta("nom_site"));
	$adresse_site = entites_html(lire_meta("adresse_site"));
	$email_webmaster = entites_html(lire_meta("email_webmaster"));

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>";
	echo _T('info_nom_site')."</FONT></B> ".aide ("confnom")."</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	echo "<input type='text' name='nom_site' value=\"$nom_site\" size='40' CLASS='formo'>";
	echo "</TD></TR>";

	echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#000000'>";
	echo _T('info_adresse_url')."</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	echo "<input type='text' name='adresse_site' value=\"$adresse_site/\" size='40' CLASS='formo'><p>&nbsp;";
	echo "</TD></TR>";

	if ($options == "avancees") {
		echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#000000'>";
		echo _T('info_email_webmestre')."</FONT></B></TD></TR>";

		echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
		echo "<input type='text' name='email_webmaster' value=\"$email_webmaster\" size='40' CLASS='forml'>";
		echo "</TD></TR>";
	}

	echo "<TR><TD ALIGN='$spip_lang_right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>";

fin_cadre_relief();

echo "<p>&nbsp;<p>";


//
// Options des articles
//

if ($options == 'avancees') {
	debut_cadre_enfonce("article-24.gif");

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif' COLSPAN=2><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>"._T('titre_les_articles')."</FONT></B></TD></TR>";
	echo "</table>";

	//
	// Champs optionnels des articles
	//

	debut_cadre_relief();

	$articles_surtitre = lire_meta("articles_surtitre");
	$articles_soustitre = lire_meta("articles_soustitre");
	$articles_descriptif = lire_meta("articles_descriptif");
	$articles_chapeau = lire_meta("articles_chapeau");
	$articles_ps = lire_meta("articles_ps");
	$articles_redac = lire_meta("articles_redac");
	$articles_urlref = lire_meta("articles_urlref");

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif' COLSPAN=2><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='black'>"._T('info_contenu_articles')."</FONT></B>".aide ("confart")."</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' COLSPAN='2' class='verdana2'>";
	echo _T('texte_contenu_articles');
	echo "</TD></TR>";

	echo "<TR>";
	echo "<TD ALIGN='$spip_lang_left' class='verdana2'>";
	echo _T('info_surtitre');
	echo "</TD>";
	echo "<TD ALIGN='$spip_lang_left' class='verdana2'>";
	afficher_choix('articles_surtitre', $articles_surtitre,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ");
	echo "</TD></TR>\n";

	echo "<TR>";
	echo "<TD ALIGN='$spip_lang_left' class='verdana2'>";
	echo _T('info_sous_titre');
	echo "</TD>";
	echo "<TD ALIGN='$spip_lang_left' class='verdana2'>";
	afficher_choix('articles_soustitre', $articles_soustitre,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ");
	echo "</TD></TR>\n";

	echo "<TR>";
	echo "<TD ALIGN='$spip_lang_left' class='verdana2'>";
	echo _T('info_descriptif');
	echo "</TD>";
	echo "<TD ALIGN='$spip_lang_left' class='verdana2'>";
	afficher_choix('articles_descriptif', $articles_descriptif,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ");
	echo "</TD></TR>\n";

	echo "<TR>";
	echo "<TD ALIGN='$spip_lang_left' class='verdana2'>";
	echo _T('info_chapeau_2');
	echo "</TD>";
	echo "<TD ALIGN='$spip_lang_left' class='verdana2'>";
	afficher_choix('articles_chapeau', $articles_chapeau,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ");
	echo "</TD></TR>\n";

	echo "<TR>";
	echo "<TD ALIGN='$spip_lang_left' class='verdana2'>";
	echo _T('info_post_scriptum_2');
	echo "</TD>";
	echo "<TD ALIGN='$spip_lang_left' class='verdana2'>";
	afficher_choix('articles_ps', $articles_ps,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ");
	echo "</TD></TR>\n";

	echo "<TR>";
	echo "<TD ALIGN='$spip_lang_left' class='verdana2'>";
	echo _T('info_date_publication_anterieure');
	echo "</TD>";
	echo "<TD ALIGN='$spip_lang_left' class='verdana2'>";
	afficher_choix('articles_redac', $articles_redac,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ");
	echo "</TD></TR>\n";

	echo "<TR>";
	echo "<TD ALIGN='$spip_lang_left' class='verdana2'>";
	echo _T('info_urlref');
	echo "</TD>";
	echo "<TD ALIGN='$spip_lang_left' class='verdana2'>";
	afficher_choix('articles_urlref', $articles_urlref,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ");
	echo "</TD></TR>\n";

	echo "<TR><TD ALIGN='$spip_lang_right' COLSPAN=2>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>";

	fin_cadre_relief();

	//
	// Articles post-dates
	//

	debut_cadre_relief();

	$post_dates = lire_meta("post_dates");

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='black'>"._T('titre_publication_articles_post_dates')."</FONT></B> ".aide ("confdates")."</TD></TR>";

	echo "<TR><TD class='verdana2'>";
	echo _T('texte_publication_articles_post_dates');
	echo "</TD></TR>";

	echo "<TR><TD ALIGN='$spip_lang_left' class='verdana2'>";
	afficher_choix('post_dates', $post_dates,
		array('oui' => _T('item_publier_articles'),
			'non' => _T('item_non_publier_articles')));
	echo "</TD></TR>\n";

	echo "<TR><TD ALIGN='$spip_lang_right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>\n";

	fin_cadre_relief();
}


if ($options == "avancees") fin_cadre_enfonce();

echo "<p>";


//
// Actives/desactiver les breves
//

debut_cadre_relief("breve-24.gif");

$activer_breves = lire_meta("activer_breves");

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>";
echo _T('titre_breves')."</FONT></B> ".aide ("confbreves")."</TD></TR>";

echo "<TR><TD class='verdana2'>";
echo _T('texte_breves')."<p>";
echo _T('info_breves');
echo "</TD></TR>";

echo "<TR><TD align='center' class='verdana2'>";
afficher_choix('activer_breves', $activer_breves,
	array('oui' => _T('item_utiliser_breves'),
		'non' => _T('item_non_utiliser_breves')), " &nbsp; ");
echo "</FONT>";
echo "</TD></TR>\n";

echo "<TR><TD ALIGN='$spip_lang_right'>";
echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
echo "</TD></TR>";
echo "</TABLE>\n";

fin_cadre_relief();

echo "<p>";


//
// Gestion des mots-cles
//

if ($options == "avancees") {

	debut_cadre_relief("mot-cle-24.gif");

	$articles_mots = lire_meta("articles_mots");
	$config_precise_groupes = lire_meta("config_precise_groupes");
	$mots_cles_forums = lire_meta("mots_cles_forums");
	$forums_publics = lire_meta("forums_publics");

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>"._T('info_mots_cles')."</FONT></B> </TD></TR>";

	echo "<TR><TD class='verdana2'>";
	echo _T('texte_mots_cles')."<p>";
	echo _T('info_question_mots_cles');
	echo "</TD></TR>";

	echo "<TR>";
	echo "<TD align='center' class='verdana2'>";
	afficher_choix('articles_mots', $articles_mots,
		array('oui' => _T('item_utiliser_mots_cles'),
			'non' => _T('item_non_utiliser_mots_cles')), " &nbsp; ");
	echo "</FONT>";
	echo "</TD></TR>";

	if ($articles_mots != "non") {

		echo "<TR><TD>&nbsp;</TD></TR>";
		echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#000000'>"._T('titre_config_groupe_mots_cles')."</FONT></B></TD></TR>";

		echo "<TR><TD class='verdana2'>";
		echo _T('texte_config_groupe_mots_cles');
		echo "</TD></TR>";

		echo "<TR>";
		echo "<TD ALIGN='$spip_lang_left' class='verdana2'>";
		afficher_choix('config_precise_groupes', $config_precise_groupes,
			array('oui' => _T('item_utiliser_config_groupe_mots_cles'),
				'non' => _T('item_non_utiliser_config_groupe_mots_cles')));
		echo "</TD></TR>";

		if ($forums_publics != "non"){
			echo "<TR><TD>&nbsp;</TD></TR>";
			echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#000000'>"._T('titre_mots_cles_dans_forum')."</FONT></B></TD></TR>";

			echo "<TR><TD class='verdana2'>";
			echo _T('texte_mots_cles_dans_forum');
			echo "</TD></TR>";

			echo "<TR>";
			echo "<TD BACKGROUND='img_pack/rien.gif' ALIGN='$spip_lang_left' class='verdana2'>";
			afficher_choix('mots_cles_forums', $mots_cles_forums,
				array('oui' => _T('item_ajout_mots_cles'),
					'non' => _T('item_non_ajout_mots_cles')));
			echo "</FONT>";
			echo "</TD></TR>";
		}
	}

	echo "<TR><TD ALIGN='$spip_lang_right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>\n";

	fin_cadre_relief();

	echo "<p>";
}


//
// Actives/desactiver systeme de syndication
//

debut_cadre_enfonce("site-24.gif");

$activer_sites = lire_meta('activer_sites');
$activer_syndic = lire_meta("activer_syndic");
$proposer_sites = lire_meta("proposer_sites");
$visiter_sites = lire_meta("visiter_sites");
$moderation_sites = lire_meta("moderation_sites");

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>"._T('titre_referencement_sites')."</FONT></B>".aide ("reference")."</TD></TR>";


echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='$spip_lang_left' class='verdana2'>";
afficher_choix('activer_sites', $activer_sites,
	array('oui' => _T('item_gerer_annuaire_site_web'),
	'non' => _T('item_non_gerer_annuaire_site_web')));
echo "</TD></TR>\n";



if ($activer_sites != 'non') {
	//
	// Utilisateurs autorises a proposer des sites references
	//
	if ($options == "avancees") {
		echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
		echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
		echo "<hr><p>"._T('info_question_proposer_site');
			echo "<center><SELECT NAME='proposer_sites' CLASS='fondo' SIZE=1>\n";
				echo "<OPTION".mySel('0',$proposer_sites).">"._T('item_choix_administrateurs')."\n";
				echo "<OPTION".mySel('1',$proposer_sites).">"._T('item_choix_redacteurs')."\n";
				echo "<OPTION".mySel('2',$proposer_sites).">"._T('item_choix_visiteurs')."\n";
			echo "</SELECT></center><P>\n";
		echo "</FONT>";
		echo "</TD></TR>";
	}

	echo "</TABLE>\n";

	debut_cadre_relief();

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";

	echo "<TR><TD BGCOLOR='EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#000000'>"._T('titre_syndication')."</FONT></B> ".aide ("rubsyn")."</TD></TR>";

	//
	// Reglage de la syndication
	//
	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	echo _T('texte_syndication');
	echo "</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='$spip_lang_left' class='verdana2'>";
	afficher_choix('activer_syndic', $activer_syndic,
		array('oui' => _T('item_utiliser_syndication'),
		'non' => _T('item_non_utiliser_syndication')));

	if ($activer_syndic != "non" AND $options == "avancees") {
		// Moderation par defaut des sites syndiques
		echo "<p><hr><p align='$spip_lang_left'>";
		echo _T('texte_liens_sites_syndiques')."<p>";

		afficher_choix('moderation_sites', $moderation_sites,
			array('oui' => _T('item_bloquer_liens_syndiques'),
			'non' => _T('item_non_bloquer_liens_syndiques')));

		// Si indexation, activer/desactiver pages recuperees

		$activer_moteur = lire_meta("activer_moteur");
		if ($activer_moteur == "oui") {
			echo "<p><hr><p align='$spip_lang_left'>";
			echo _T('texte_utilisation_moteur_syndiques')." ";
			echo "<blockquote><i>"._T('texte_utilisation_moteur_syndiques_2')."</i></blockquote><p>";

			afficher_choix('visiter_sites', $visiter_sites,
				array('non' => _T('item_limiter_recherche'),
					'oui' => _T('item_non_limiter_recherche')));
		}
	}
	echo "</TD></TR>\n";

	echo "</TABLE>\n";

	fin_cadre_relief();

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
}

echo "<TR><TD ALIGN='$spip_lang_right'>";
echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
echo "</TD></TR>";
echo "</TABLE>\n";

fin_cadre_enfonce();

echo "<p>";


//
// Gestion des documents joints
//

debut_cadre_relief("doc-24.gif");

$documents_rubrique = lire_meta("documents_rubrique");
$documents_article = lire_meta("documents_article");

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>"._T('titre_documents_joints')."</FONT></B> </TD></TR>";

echo "<TR><TD class='verdana2'>";
echo _T('texte_documents_joints');
echo _T('texte_documents_joints_2');
echo "</TD></TR>";

echo "<TR>";
echo "<TD align='$spip_lang_left' class='verdana2'>";
afficher_choix('documents_article', $documents_article,
	array('oui' => _T('item_autoriser_documents_joints'),
		'non' => _T('item_non_autoriser_documents_joints')), "<br>");
echo "<br><br>\n";
afficher_choix('documents_rubrique', $documents_rubrique,
	array('oui' => _T('item_autoriser_documents_joints_rubriques'),
		'non' => _T('item_non_autoriser_documents_joints_rubriques')), "<br>");
echo "</FONT>";
echo "</TD></TR>";

echo "<TR><TD ALIGN='$spip_lang_right'>";
echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
echo "</TD></TR>";
echo "</TABLE>\n";

fin_cadre_relief();

echo "<p>";



echo "</form>";


fin_page();

?>
