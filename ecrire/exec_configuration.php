<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_ecrire("inc_presentation");
include_ecrire ("inc_config");

function configuration_dist()
{
  global $connect_statut, $connect_toutes_rubriques, $options, $spip_lang_left, $spip_lang_right,$changer_config;

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
	$forums_publics = $GLOBALS['meta']["forums_publics"];
	if (!$forums_publics) {
		ecrire_meta("forums_publics", "posteriori");
		ecrire_metas();
	}
 }
lire_metas();


debut_page(_T('titre_page_configuration'), "administration", "configuration");

echo "<br><br><br>";
gros_titre(_T('titre_configuration'));
barre_onglets("configuration", "contenu");


debut_gauche();

debut_droite();

avertissement_config();

//
// Afficher les options de config
//

echo generer_url_post_ecrire('configuration');
echo "<input type='hidden' name='changer_config' value='oui'>";
debut_cadre_couleur("racine-site-24.gif");

	$nom_site = entites_html($GLOBALS['meta']["nom_site"]);
	$adresse_site = entites_html($GLOBALS['meta']["adresse_site"]);
	$email_webmaster = entites_html($GLOBALS['meta']["email_webmaster"]);

	debut_cadre_relief("", false, "", _T('info_nom_site').aide ("confnom"));
	echo "<input type='text' name='nom_site' value=\"$nom_site\" size='40' CLASS='forml'>";
	fin_cadre_relief();

	debut_cadre_relief("", false, "", _T('info_adresse_url'));
	echo "<input type='text' name='adresse_site' value=\"$adresse_site/\" size='40' CLASS='forml'>";
	fin_cadre_relief();

	if ($options == "avancees") {
		echo "<div>&nbsp;</div>";
	
		debut_cadre_relief("", false, "", _T('info_email_webmestre'));
		echo "<input type='text' name='email_webmaster' value=\"$email_webmaster\" size='40' CLASS='formo'>";
		fin_cadre_relief();
	}

	echo "<div style='text-align:right;'><input type='submit' name='Valider' value='"._T('bouton_enregistrer')."' CLASS='fondo'></div>";

fin_cadre_couleur();

echo "<p>&nbsp;<p>";


//
// Options des articles
//

if ($options == 'avancees') {
	debut_cadre_trait_couleur("article-24.gif", false, "", _T('titre_les_articles'));


	//
	// Champs optionnels des articles
	//

	debut_cadre_relief("", false, "", _T('info_contenu_articles').aide ("confart"));

	$articles_surtitre = $GLOBALS['meta']["articles_surtitre"];
	$articles_soustitre = $GLOBALS['meta']["articles_soustitre"];
	$articles_descriptif = $GLOBALS['meta']["articles_descriptif"];
	$articles_chapeau = $GLOBALS['meta']["articles_chapeau"];
	$articles_ps = $GLOBALS['meta']["articles_ps"];
	$articles_redac = $GLOBALS['meta']["articles_redac"];
	$articles_urlref = $GLOBALS['meta']["articles_urlref"];

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";

	echo "<TR><TD BACKGROUND='" . _DIR_IMG_PACK . "rien.gif' COLSPAN='2' class='verdana2'>";
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

	echo "<TR><TD style='text-align: $spip_lang_right;' COLSPAN=2>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>";

	fin_cadre_relief();

	//
	// Articles post-dates
	//

	debut_cadre_relief("", false, "", _T('titre_publication_articles_post_dates').aide ("confdates"));

	$post_dates = $GLOBALS['meta']["post_dates"];

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD class='verdana2'>";
	echo _T('texte_publication_articles_post_dates');
	echo "</TD></TR>";

	echo "<TR><TD ALIGN='$spip_lang_left' class='verdana2'>";
	afficher_choix('post_dates', $post_dates,
		array('oui' => _T('item_publier_articles'),
			'non' => _T('item_non_publier_articles')));
	echo "</TD></TR>\n";

	echo "<TR><td style='text-align:$spip_lang_right;'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>\n";

	fin_cadre_relief();

	fin_cadre_trait_couleur();
}



echo "<p>";


//
// Actives/desactiver les breves
//

debut_cadre_trait_couleur("breve-24.gif", false, "", _T('titre_breves').aide ("confbreves"));

$activer_breves = $GLOBALS['meta']["activer_breves"];

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
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

echo "<TR><td style='text-align:$spip_lang_right;'>";
echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
echo "</TD></TR>";
echo "</TABLE>\n";

fin_cadre_trait_couleur();

echo "<p>";


//
// Gestion des mots-cles
//

if ($options == "avancees") {

	debut_cadre_trait_couleur("mot-cle-24.gif", false, "", _T('info_mots_cles'));

	$articles_mots = $GLOBALS['meta']["articles_mots"];
	$config_precise_groupes = $GLOBALS['meta']["config_precise_groupes"];
	$mots_cles_forums = $GLOBALS['meta']["mots_cles_forums"];
	$forums_publics = $GLOBALS['meta']["forums_publics"];

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD class='verdana2'>";
	echo _T('texte_mots_cles')."<p>";
	echo _T('info_question_mots_cles');
	echo "</TD></TR>";

	echo "<TR>";
	echo "<TD align='center' class='verdana2'>";


		echo bouton_radio("articles_mots", "oui", _T('item_utiliser_mots_cles'), $articles_mots == "oui", "changeVisible(this.checked, 'mots-config', 'block', 'none');");
		echo " &nbsp;";
		echo bouton_radio("articles_mots", "non", _T('item_non_utiliser_mots_cles'), $articles_mots == "non", "changeVisible(this.checked, 'mots-config', 'none', 'block');");


//	afficher_choix('articles_mots', $articles_mots,
//		array('oui' => _T('item_utiliser_mots_cles'),
//			'non' => _T('item_non_utiliser_mots_cles')), "<br />");
	echo "</TD></TR></table>";

	if ($articles_mots != "non") $style = "display: block;";
	else $style = "display: none;";
	
		echo "<div id='mots-config' style='$style'>";
		
		echo "<p />";
		debut_cadre_relief("", false, "", _T('titre_config_groupe_mots_cles'));

		echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
		echo "<TR><TD class='verdana2'>";
		echo _T('texte_config_groupe_mots_cles');
		echo "</TD></TR>";

		echo "<TR>";
		echo "<TD ALIGN='$spip_lang_left' class='verdana2'>";
		afficher_choix('config_precise_groupes', $config_precise_groupes,
			array('oui' => _T('item_utiliser_config_groupe_mots_cles'),
				'non' => _T('item_non_utiliser_config_groupe_mots_cles')));
		echo "</TD></TR></table>";
		fin_cadre_relief();

		if ($forums_publics != "non"){
			echo "<p />";
			debut_cadre_relief("", false, "", _T('titre_mots_cles_dans_forum'));
			echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
			echo "<TR><TD class='verdana2'>";
			echo _T('texte_mots_cles_dans_forum');
			echo "</TD></TR>";

			echo "<TR>";
			echo "<TD BACKGROUND='" . _DIR_IMG_PACK . "rien.gif' ALIGN='$spip_lang_left' class='verdana2'>";
			afficher_choix('mots_cles_forums', $mots_cles_forums,
				array('oui' => _T('item_ajout_mots_cles'),
					'non' => _T('item_non_ajout_mots_cles')));
			echo "</FONT>";
			echo "</TD></TR>";
			echo "</table>";
			fin_cadre_relief();
		}
		echo "</div>";
	

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><td style='text-align:$spip_lang_right;'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>\n";

	fin_cadre_trait_couleur();

	echo "<p>";
}


//
// Actives/desactiver systeme de syndication
//

debut_cadre_trait_couleur("site-24.gif", false, "", _T('titre_referencement_sites').aide ("reference"));

$activer_sites = $GLOBALS['meta']['activer_sites'];
$activer_syndic = $GLOBALS['meta']["activer_syndic"];
$proposer_sites = $GLOBALS['meta']["proposer_sites"];
$visiter_sites = $GLOBALS['meta']["visiter_sites"];
$moderation_sites = $GLOBALS['meta']["moderation_sites"];

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";

echo "<TR><TD BACKGROUND='" . _DIR_IMG_PACK . "rien.gif' ALIGN='$spip_lang_left' class='verdana2'>";

		echo bouton_radio("activer_sites", "oui", _T('item_gerer_annuaire_site_web'), $activer_sites == "oui", "changeVisible(this.checked, 'config-site', 'block', 'none');");
		echo " &nbsp;";
		echo bouton_radio("activer_sites", "non", _T('item_non_gerer_annuaire_site_web'), $activer_sites == "non", "changeVisible(this.checked, 'config-site', 'none', 'block');");

echo "</TD></TR></table>\n";



if ($activer_sites != 'non') $style = "display: block;";
else $style = "display: none;";

	echo "<div id='config-site' style='$style'>";
	
	// Utilisateurs autorises a proposer des sites references
	//
		echo "<p />";
		debut_cadre_relief();
		echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
		echo "<TR><TD BACKGROUND='" . _DIR_IMG_PACK . "rien.gif'>";
		echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2 COLOR='#000000'>";
		echo _T('info_question_proposer_site');
			echo "<center><SELECT NAME='proposer_sites' CLASS='fondo' SIZE=1>\n";
				echo "<OPTION".mySel('0',$proposer_sites).">"._T('item_choix_administrateurs')."\n";
				echo "<OPTION".mySel('1',$proposer_sites).">"._T('item_choix_redacteurs')."\n";
				echo "<OPTION".mySel('2',$proposer_sites).">"._T('item_choix_visiteurs')."\n";
			echo "</SELECT></center><P>\n";
		echo "</FONT>";
		echo "</TD></TR></table>";
		fin_cadre_relief();


	if ($options == "avancees") {
		debut_cadre_relief("", false, "", _T('titre_syndication').aide ("rubsyn"));
	
		echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
		//
		// Reglage de la syndication
		//
		echo "<TR><TD BACKGROUND='" . _DIR_IMG_PACK . "rien.gif' class='verdana2'>";
		echo _T('texte_syndication');
		echo "</TD></TR>";
	
		echo "<TR><TD BACKGROUND='" . _DIR_IMG_PACK . "rien.gif' ALIGN='$spip_lang_left' class='verdana2'>";

			echo bouton_radio("activer_syndic", "oui", _T('item_utiliser_syndication'), $activer_syndic == "oui", "changeVisible(this.checked, 'config-syndic', 'block', 'none');");
			echo "<br />";
			echo bouton_radio("activer_syndic", "non", _T('item_non_utiliser_syndication'), $activer_syndic == "non", "changeVisible(this.checked, 'config-syndic', 'none', 'block');");



	
		if ($activer_syndic != "non") $style = "display: block;";
		else $style = "display: none;";
			
			echo "<div id='config-syndic' style='$style'>";
		
			// Moderation par defaut des sites syndiques
			echo "<p><hr><p align='$spip_lang_left'>";
			echo _T('texte_liens_sites_syndiques')."<p>";
	
			afficher_choix('moderation_sites', $moderation_sites,
				array('oui' => _T('item_bloquer_liens_syndiques'),
				'non' => _T('item_non_bloquer_liens_syndiques')));
	
			// Si indexation, activer/desactiver pages recuperees
	
			$activer_moteur = $GLOBALS['meta']["activer_moteur"];
			if ($activer_moteur == "oui") {
				echo "<p><hr><p align='$spip_lang_left'>";
				echo _T('texte_utilisation_moteur_syndiques')." ";
				echo "<blockquote><i>"._T('texte_utilisation_moteur_syndiques_2')."</i></blockquote><p>";
	
				afficher_choix('visiter_sites', $visiter_sites,
					array('non' => _T('item_limiter_recherche'),
						'oui' => _T('item_non_limiter_recherche')));
			}
			echo "</div>";
		
		echo "</TD></TR>\n";
	
		echo "</TABLE>\n";
	
		fin_cadre_relief();
	}
	echo "</div>";



echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
echo "<TR><td style='text-align:$spip_lang_right;'>";
echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
echo "</TD></TR>";
echo "</TABLE>\n";

fin_cadre_trait_couleur();

echo "<p>";


//
// Gestion des documents joints
//

debut_cadre_trait_couleur("doc-24.gif", false, "", _T('titre_documents_joints'));

$documents_rubrique = $GLOBALS['meta']["documents_rubrique"];
$documents_article = $GLOBALS['meta']["documents_article"];

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";

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

echo "<TR><td style='text-align:$spip_lang_right;'>";
echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
echo "</TD></TR>";
echo "</TABLE>\n";

fin_cadre_trait_couleur();

echo "<p>";



echo "</form>";


fin_page();
}
?>
