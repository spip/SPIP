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

include_spip('inc/presentation');
include_spip('inc/mail');
include_spip('inc/config');

function exec_config_contenu_dist()
{
  global $connect_statut, $connect_toutes_rubriques, $options, $spip_lang_right, $spip_lang_left,$changer_config, $envoi_now ;


if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
	echo _T('avis_non_acces_page');
	fin_page();
	exit;
}

init_config();
if ($changer_config == 'oui') {
	appliquer_modifs_config();
}

lire_metas();

pipeline('exec_init',array('args'=>array('exec'=>'config_contenu'),'data'=>''));
debut_page(_T('titre_page_config_contenu'), "configuration", "configuration");

echo "<br><br><br>";
gros_titre(_T('titre_page_config_contenu'));
barre_onglets("configuration", "interactivite");


debut_gauche();

echo pipeline('affiche_gauche',array('args'=>array('exec'=>'config_contenu'),'data'=>''));
creer_colonne_droite();
echo pipeline('affiche_droite',array('args'=>array('exec'=>'config_contenu'),'data'=>''));
debut_droite();

 echo generer_url_post_ecrire("config_contenu");
 echo "<input type='hidden' name='changer_config' value='oui'>";



//
// Mode de fonctionnement des forums publics
//
debut_cadre_trait_couleur("forum-interne-24.gif", false, "", _T('info_mode_fonctionnement_defaut_forum_public').aide ("confforums"));

$forums_publics=$GLOBALS['meta']["forums_publics"];

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
echo "<TR><TD BACKGROUND='" . _DIR_IMG_PACK . "rien.gif' ALIGN='$spip_lang_left' class='verdana2'>";


	if ($forums_publics == "non") $block = "'none', 'block'"; 
	else $block= "'block', 'none'";
	echo bouton_radio("forums_publics", "non", _T('info_desactiver_forum_public'), $forums_publics == "non", "changeVisible(this.checked, 'config-options', $block);");


echo "</TD></TR>";

echo "<TR><TD BACKGROUND='" . _DIR_IMG_PACK . "rien.gif' class='verdana2'>";
echo _T('info_activer_forum_public');
echo "</TD></TR>";

echo "<TR><TD BACKGROUND='" . _DIR_IMG_PACK . "rien.gif' ALIGN='$spip_lang_left' class='verdana2'>";


	if ($forums_publics == "posteriori") $block = "'none', 'block'"; 
	else $block= "'block', 'none'";
	echo bouton_radio("forums_publics", "posteriori", _T('bouton_radio_publication_immediate'), $forums_publics == "posteriori", "changeVisible(this.checked, 'config-options', $block);");
	echo "<br />";
	if ($forums_publics == "priori") $block = "'none', 'block'"; 
	else $block= "'block', 'none'";
	echo bouton_radio("forums_publics", "priori", _T('bouton_radio_moderation_priori'), $forums_publics == "priori", "changeVisible(this.checked, 'config-options', $block);");

	if (tester_mail()) {
		echo "<br />";
		if ($forums_publics == "abo") $block = "'none', 'block'"; 
		else $block= "'block', 'none'";
		echo bouton_radio("forums_publics", "abo", _T('bouton_radio_enregistrement_obligatoire'), $forums_publics == "abo", "changeVisible(this.checked, 'config-options', $block);");
	}

echo "</TD></TR>\n";

echo "<TR><TD BACKGROUND='" . _DIR_IMG_PACK . "rien.gif' ALIGN='$spip_lang_left' class='verdana2'>";

if ($options == 'avancees') {
	echo "<div id='config-options' class='display_au_chargement'>";
	echo "<ul>";
	
	debut_cadre_relief("", false, "", _T('info_options_avancees'));
	
	echo "<table width='100%' cellpadding='2' border='0' class='hauteur'>\n";
	echo "<tr><td class='verdana2'>";
	echo _T('info_appliquer_choix_moderation')."<br>";

	echo "<INPUT TYPE='radio' CHECKED NAME='forums_publics_appliquer' VALUE='futur' id='forums_appliquer_futur'>";
	echo " <b><label for='forums_appliquer_futur'>"._T('bouton_radio_articles_futurs')."</label></b><br>";
	echo "<INPUT TYPE='radio' NAME='forums_publics_appliquer' VALUE='saufnon' id='forums_appliquer_saufnon'>";
	echo " <label for='forums_appliquer_saufnon'>"._T('bouton_radio_articles_tous_sauf_forum_desactive')."</label><br>";
	echo "<INPUT TYPE='radio' NAME='forums_publics_appliquer' VALUE='tous' id='forums_appliquer_tous'>";
	echo " <label for='forums_appliquer_tous'>"._T('bouton_radio_articles_tous')."</label><br>";
	echo "</TD></TR></table>";
	fin_cadre_relief();
	echo "</ul>\n";

	echo "</div>";
}
else {
	echo "<input type='hidden' name='forums_publics_appliquer' value='tous'>";
}


echo "<TR><td style='text-align:$spip_lang_right;'>";
echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
echo "</TD></TR>";
echo "</TABLE>\n";

fin_cadre_trait_couleur();

echo "<p>";



//
// Accepter les inscriptions de redacteurs depuis le site public
//

if ($options == "avancees") {
	debut_cadre_trait_couleur("redacteurs-24.gif", false, "", _T('info_inscription_automatique'));

	$accepter_inscriptions=$GLOBALS['meta']["accepter_inscriptions"];
	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";

	echo "<TR><TD BACKGROUND='" . _DIR_IMG_PACK . "rien.gif' class='verdana2'>";
	echo _T('info_question_inscription_nouveaux_redacteurs')."</i></blockquote>";
	echo "</TD></TR>";


	echo "<TR><TD BACKGROUND='" . _DIR_IMG_PACK . "rien.gif' ALIGN='center' class='verdana2'>";
	afficher_choix('accepter_inscriptions', $accepter_inscriptions,
		array('oui' => _T('item_accepter_inscriptions'),
			'non' => _T('item_non_accepter_inscriptions')), " &nbsp; ");

	echo "</td></tr>\n";
	echo "<tr><td style='text-align:$spip_lang_right;'>";
	echo "<input type='submit' value='"._T('bouton_valider')."' CLASS='fondo' />";
	echo "</TD></TR>";
	echo "</TABLE>\n";

	fin_cadre_trait_couleur();

// Idem pour les visiteurs
// (la balise FORMULAIRE_INSCRIPTION sert au deux)

	debut_cadre_trait_couleur("redacteurs-24.gif", false, "", _T('info_visiteurs'));
		$accepter_visiteurs = $GLOBALS['meta']['accepter_visiteurs'];
		echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
		echo "<TR><TD BACKGROUND='" . _DIR_IMG_PACK . "rien.gif' class='verdana2'>";

		if ($n = ($forums_publics<>'abo')) {
			$n = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_articles WHERE accepter_forum='abo' LIMIT 1"));
			$n = !$n['n'];
		}
		if ($n) {
			echo _T('info_question_accepter_visiteurs');
			echo "</TD></TR>";
			echo "<TR><TD BACKGROUND='" . _DIR_IMG_PACK . "rien.gif' ALIGN='$spip_lang_left' class='verdana2'>";
			afficher_choix('accepter_visiteurs', $accepter_visiteurs,
				array('oui' => _T('info_option_accepter_visiteurs'),
					'non' => _T('info_option_ne_pas_accepter_visiteurs')));
			echo "</td></tr>\n";
			echo "<tr><td style='text-align:$spip_lang_right;'>";
			echo "<input type='submit' value='"._T('bouton_valider')."' CLASS='fondo' />";
		} else {
			echo _T('info_forums_abo_invites');
		}

		echo "</TD></TR></table>\n";
		fin_cadre_trait_couleur();

	echo "<p>";
}


//
// Activer/desactiver mails automatiques
//
if (tester_mail()) {
	debut_cadre_trait_couleur("", false, "", _T('info_envoi_email_automatique').aide ("confmails"));

	$prevenir_auteurs=$GLOBALS['meta']["prevenir_auteurs"];


	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BACKGROUND='" . _DIR_IMG_PACK . "rien.gif' class='verdana2'>";
	echo "<blockquote><i>"._T('info_hebergeur_desactiver_envoi_email')."</i></blockquote>";
	echo "</TD></TR></table>";

	debut_cadre_relief("", false, "", _T('info_envoi_forum'));
	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BACKGROUND='" . _DIR_IMG_PACK . "rien.gif' class='verdana2'>";
	echo _T('info_option_email');
	echo "</TD></TR>";

	echo "<TR><TD BACKGROUND='" . _DIR_IMG_PACK . "rien.gif' ALIGN='$spip_lang_left' class='verdana2'>";
	afficher_choix('prevenir_auteurs', $prevenir_auteurs,
		array('oui' => _T('info_option_faire_suivre'),
			'non' => _T('info_option_ne_pas_faire_suivre')));
	echo "</TD></TR></table>\n";
	fin_cadre_relief();

	//
	// Suivi editorial (articles proposes & publies)
	//

	$suivi_edito=$GLOBALS['meta']["suivi_edito"];
	$adresse_suivi=$GLOBALS['meta']["adresse_suivi"];
	$adresse_suivi_inscription=$GLOBALS['meta']["adresse_suivi_inscription"];

	echo "<p />";
	debut_cadre_relief("", false, "", _T('info_suivi_activite'));
	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";

	echo "<TR><TD BACKGROUND='" . _DIR_IMG_PACK . "rien.gif' class='verdana2'>";
	echo _T('info_facilite_suivi_activite')."</FONT>";
	echo "</TD></TR></table>";


	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BACKGROUND='" . _DIR_IMG_PACK . "rien.gif' ALIGN='$spip_lang_left' class='verdana2'>";

		echo bouton_radio("suivi_edito", "oui", _T('bouton_radio_envoi_annonces_adresse'), $suivi_edito == "oui", "changeVisible(this.checked, 'config-edito', 'block', 'none');");


			if ($suivi_edito == "oui") $style = "display: block;";
			else $style = "display: none;";			
			echo "<div id='config-edito' style='$style'>";
			echo "<div style='text-align: center;'><input type='text' name='adresse_suivi' value='$adresse_suivi' size='30' CLASS='fondl'></div>";
			echo "<blockquote class='spip'>";
			if (!$adresse_suivi) $adresse_suivi = "mailing@monsite.net";
			echo _T('info_config_suivi', array('adresse_suivi' => $adresse_suivi));
			echo "<br><input type='text' name='adresse_suivi_inscription' value='$adresse_suivi_inscription' size='50' CLASS='fondl'>";
			echo "</blockquote>";
			echo "</div>";

		echo "<br />";
		echo bouton_radio("suivi_edito", "non", _T('bouton_radio_non_envoi_annonces_editoriales'), $suivi_edito == "non", "changeVisible(this.checked, 'config-edito', 'none', 'block');");

	echo "</TD></TR></table>\n";
	fin_cadre_relief();

	//
	// Annonce des nouveautes
	//
	$quoi_de_neuf=$GLOBALS['meta']["quoi_de_neuf"];
	$adresse_neuf=$GLOBALS['meta']["adresse_neuf"];
	$jours_neuf=$GLOBALS['meta']["jours_neuf"];

	// provoquer l'envoi des nouveautes en supprimant le fichier lock
	if ($envoi_now)
		@unlink(_DIR_TMP . 'mail.lock');

	echo "<p />";
	debut_cadre_relief("", false, "", _T('info_annonce_nouveautes'));
	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";

	echo "<TR><TD BACKGROUND='" . _DIR_IMG_PACK . "rien.gif' class='verdana2'>";
	echo _T('info_non_envoi_annonce_dernieres_nouveautes');
	echo "</TD></TR>";

	echo "<TR><TD BACKGROUND='" . _DIR_IMG_PACK . "rien.gif' ALIGN='$spip_lang_left' class='verdana2'>";

		echo bouton_radio("quoi_de_neuf", "oui", _T('bouton_radio_envoi_liste_nouveautes'), $quoi_de_neuf == "oui", "changeVisible(this.checked, 'config-neuf', 'block', 'none');");
	//	echo "<INPUT TYPE='radio' NAME='quoi_de_neuf' VALUE='oui' id='quoi_de_neuf_on' CHECKED>";
	//	echo " <B><label for='quoi_de_neuf_on'>"._T('bouton_radio_envoi_liste_nouveautes')."</label></B> ";

			if ($quoi_de_neuf == "oui") $style = "display: block;";
			else $style = "display: none;";			
		echo "<div id='config-neuf' style='$style'>";
		echo "<UL>";
		echo "<LI>"._T('info_adresse')." <input type='text' name='adresse_neuf' value='$adresse_neuf' size='30' CLASS='fondl'>";
		echo "<LI>"._T('info_tous_les')." <input type='text' name='jours_neuf' value='$jours_neuf' size='4' CLASS='fondl'> "._T('info_jours');
		echo " &nbsp;  &nbsp;  &nbsp; <INPUT TYPE='submit' NAME='envoi_now' VALUE='"._T('info_envoyer_maintenant')."' CLASS='fondl'>";
		echo "</UL>";
		echo "</div>";

		echo "<br />";
		echo bouton_radio("quoi_de_neuf", "non", _T('info_non_envoi_liste_nouveautes'), $quoi_de_neuf == "non", "changeVisible(this.checked, 'config-neuf', 'none', 'block');");
		//echo "<BR><INPUT TYPE='radio' NAME='quoi_de_neuf' VALUE='non' id='quoi_de_neuf_off'>";
		//echo " <label for='quoi_de_neuf_off'>"._T('info_non_envoi_liste_nouveautes')."</label> ";
	
	
	
	echo "</TD></TR></table>\n";
	fin_cadre_relief();

	if($options == "avancees") {
		$email_envoi = entites_html($GLOBALS['meta']["email_envoi"]);
		echo "<p />";
		debut_cadre_relief("", false, "", _T('info_email_envoi'));
		echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
		echo "<TR><TD BACKGROUND='" . _DIR_IMG_PACK . "rien.gif' class='verdana2'>";
		echo _T('info_email_envoi_txt');
		echo " <input type='text' name='email_envoi' value=\"$email_envoi\" size='20' CLASS='fondl'>";
		echo "</TD></TR>";
		echo "<TR><TD>&nbsp;</TD></TR></table>";
		fin_cadre_relief();
	}

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><td style='text-align:$spip_lang_right;'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>\n";

	fin_cadre_trait_couleur();
	echo "<p />";
}

// Activer forum admins

if ($options == "avancees") {
	
	debut_cadre_trait_couleur("forum-admin-24.gif", false, "", _T('titre_cadre_forum_administrateur'));
	
	echo "<div class='verdana2'>";

	echo _T('info_forum_ouvert');
	echo "<br />";
	afficher_choix('forum_prive_admin', $GLOBALS['meta']['forum_prive_admin'],
		array('oui' => _T('item_activer_forum_administrateur'),
			'non' => _T('item_desactiver_forum_administrateur')));

	echo "</div>";
	echo "<div style='text-align:$spip_lang_right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'></div>";

	fin_cadre_trait_couleur();
	echo "<p />";

}


echo "</form>";

fin_page();
}
?>
