<?php

include ("inc.php3");

include_ecrire ("inc_config.php3");

debut_page(_T('titre_page_config_fonctions'), "administration", "configuration");

echo "<br><br><br>";
gros_titre(_T('titre_config_fonctions'));
barre_onglets("configuration", "fonctions");

debut_gauche();
debut_droite();

if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
	echo _T('avis_non_acces_page');
	exit;
}

init_config();
if ($changer_config == 'oui' OR $image_process) {
	appliquer_modifs_config();
}

lire_metas();

echo "<form action='config-fonctions.php3' method='post'>";
echo "<input type='hidden' name='changer_config' value='oui'>";


//
// Activer/desactiver la creation automatique de vignettes
//



function afficher_choix_vignette($process) {
	global $taille_preview;

	if ($process == lire_meta('image_process'))
		$border = 2;
	else
		$border=0;

	echo "<td><div align='center' valign='bottom' width='".($taille_preview+5)."'><a href='config-fonctions.php3?image_process=$process'><img src='../spip_image.php3?test_vignette=$process' border='$border' /></a><br />";
	if ($border) echo "<b>$process</b>";
	else echo "$process";
	echo "</div></td>\n";
}

if ($flag_gd OR $flag_imagick OR $convert_command) {
	debut_cadre_relief("image-24.gif");

	$gd_formats = lire_meta("gd_formats");
	$creer_preview = lire_meta("creer_preview");
	$taille_preview = lire_meta("taille_preview");
	if ($taille_preview < 10) $taille_preview = 120;

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee'>";
	echo "<B><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3 COLOR='white'>"._T('info_generation_miniatures_images')."</FONT></B></TD></TR>";
	echo "<TR><TD class='verdana2'>";
	echo _T('info_ajout_image');
	echo "</TD></TR>";

	echo "<TR><TD ALIGN='$spip_lang_left' class='verdana2'>";
	if (($flag_gd AND $gd_formats) OR $flag_imagick OR $convert_command) {
		afficher_choix('creer_preview', $creer_preview,
			array('oui' => _T('item_choix_generation_miniature'),
				'non' => _T('item_choix_non_generation_miniature')));
		echo "<p>";

		if ($creer_preview == "oui") {
			echo "<div style='border: 1px dashed #404040; margin: 6px; padding: 6px;'><table width='99%' align='center'><tr>";

			// Tester les formats
			if ($flag_gd) {
				$nb_process ++;
				afficher_choix_vignette($p = 'gd1');

				if ($flag_ImageCreateTrueColor) {
					afficher_choix_vignette($p = 'gd2');
					$nb_process ++;
				}
			}

			if ($flag_imagick) {
				afficher_choix_vignette($p = 'imagick');
				$nb_process ++;
			}

			if ($convert_command) {
				afficher_choix_vignette($p = 'convert');
				$nb_process ++;
			}

			echo "</tr></table>\n";

			if ($nb_process>1) {
				echo "<div>"._T('info_image_process');
			} else if ($nb == 1 AND $process == '') {
				ecrire_meta('image_process', $p);
				ecrire_metas();
			}

			echo "<div>";

			if ($gd_formats AND (lire_meta('image_process')=='gd1' OR lire_meta('image_process')=='gd2'))
				echo '<div>'._T('info_format_image', array('gd_formats' => $gd_formats)).'</div>';

			echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"._T('info_taille_maximale_vignette');
			echo " &nbsp;&nbsp;<INPUT TYPE='text' NAME='taille_preview' VALUE='$taille_preview' class='fondl' size=5>";
			echo " "._T('info_pixels');

			echo "</div>";
		}
	}

	echo "</TD></TR>\n";
	echo "<TR><TD style='text-align:$spip_lang_right;' COLSPAN=2>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
	echo "</TD></TR>";

	echo "</table>";

	fin_cadre_relief();
	echo "<p>";
}


//
// Indexation pour moteur de recherche
//

debut_cadre_relief("racine-site-24.gif");

$activer_moteur = lire_meta("activer_moteur");

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3 COLOR='#FFFFFF'>"._T('info_moteur_recherche')."</FONT></B> ".aide ("confmoteur")."</TD></TR>";

echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
echo _T('info_question_utilisation_moteur_recherche');
echo "</TD></TR>";

echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='center' class='verdana2'>";
afficher_choix('activer_moteur', $activer_moteur,
	array('oui' => _T('item_utiliser_moteur_recherche'),
		'non' => _T('item_non_utiliser_moteur_recherche')), ' &nbsp; ');
echo "</TD></TR>";

echo "<TR><td style='text-align:$spip_lang_right;'>";
echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
echo "</TD></TR>";
echo "</TABLE>";

fin_cadre_relief();

echo "<p>";


//
// Activer les statistiques
//

debut_cadre_relief("statistiques-24.gif");

$activer_statistiques = lire_meta("activer_statistiques");

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3 COLOR='#FFFFFF'>"._T('info_forum_statistiques')."</FONT></B> ".aide ("confstat")."</TD></TR>";

echo "<TR><TD class='verdana2'>";
echo _T('info_question_gerer_statistiques');
echo "</TD></TR>";

echo "<TR><TD ALIGN='center' class='verdana2'>";
afficher_choix('activer_statistiques', $activer_statistiques,
	array('oui' => _T('item_gerer_statistiques'),
		'non' => _T('item_non_gerer_statistiques')), ' &nbsp; ');
echo "</TD></TR>\n";

echo "<TR><td style='text-align:$spip_lang_right;'>";
echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
echo "</TD></TR>";
echo "</TABLE>\n";

fin_cadre_relief();

echo "<p>";


//
// Notification de modification des articles
//

if ($options == "avancees") {
	debut_cadre_relief("article-24.gif");

	$articles_modif = lire_meta("articles_modif");

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3 COLOR='#FFFFFF'>"._T('info_travail_colaboratif')."</FONT></B></TD></TR>";

	echo "<TR><TD class='verdana2'>";
	echo _T('texte_travail_collaboratif');
	echo "</TD></TR>";

	echo "<TR><TD ALIGN='center' class='verdana2'>";
	afficher_choix('articles_modif', $articles_modif,
		array('oui' => _T('item_activer_messages_avertissement'),
			'non' => _T('item_non_activer_messages_avertissement')));
	echo "</TD></TR>\n";

	echo "<TR><td style='text-align:$spip_lang_right;'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>\n";

	fin_cadre_relief();

	echo "<p>";
}






//
// Utilisation d'un proxy pour aller lire les sites syndiques
//

if ($options == 'avancees') {
	debut_cadre_relief("base-24.gif");

	$http_proxy=entites_html(lire_meta("http_proxy"));

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3 COLOR='#FFFFFF'>"._T('info_sites_proxy')."</FONT></B> ".aide ("confhttpproxy")."</TD></TR>";

	echo "<TR><TD class='verdana2'>";
	echo _T('texte_proxy') . "</FONT>";
	echo "</TD></TR>";

	echo "<TR><TD ALIGN='center'>";
	echo "<INPUT TYPE='text' NAME='http_proxy' VALUE='$http_proxy' size='40' class='forml'>";
	echo "</TD></TR>";

	echo "<TR><td style='text-align:$spip_lang_right;'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
	if ($http_proxy) {
		echo "<p align='$spip_lang_left'><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2 COLOR='#000000'>"
			. _T('texte_test_proxy');
		echo "</TD></TR>";

		echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='center'>";
		echo "<INPUT TYPE='text' NAME='test_proxy' VALUE='http://www.spip.net/' size='40' class='forml'>";
		echo "</TD></TR>";

		echo "<TR><td style='text-align:$spip_lang_right;'>";

		echo "</font><div align='$spip_lang_right'><INPUT TYPE='submit' NAME='tester_proxy' VALUE='"._T('bouton_test_proxy')."' CLASS='fondo'></div>";
	}
	echo "</TD></TR>";

	echo "</TABLE>";

	fin_cadre_relief();

	echo "<p>";
}



//
// Creer fichier .htpasswd ?
//

if ($options == "avancees" AND !@file_exists('.htaccess') AND !$REMOTE_USER ) {
	include_ecrire ("inc_acces.php3");
	ecrire_acces();

	debut_cadre_relief("cadenas-24.gif");

	$creer_htpasswd = lire_meta("creer_htpasswd");

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3 COLOR='#FFFFFF'>"._T('info_fichiers_authent')."</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	echo _T('texte_fichier_authent');
	echo "</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='center' class='verdana2'>";
	afficher_choix('creer_htpasswd', $creer_htpasswd,
		array('oui' => _T('item_creer_fichiers_authent'),
		'non' => _T('item_non_creer_fichiers_authent')), ' &nbsp; ');
	echo "</TD></TR>";

	echo "<TR><td style='text-align:$spip_lang_right;'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>";

	fin_cadre_relief();

	echo "<p>";
}


echo "</form>";

fin_page();

?>
