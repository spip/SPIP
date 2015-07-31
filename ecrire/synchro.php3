<?php

include ("inc.php3");
// pour low_sec (iCal)
include_ecrire("inc_acces.php3");
include_ecrire ("inc_logos.php3");
include_ecrire ("inc_mots.php3");
include_ecrire ("inc_documents.php3");
include_ecrire ("inc_agenda.php3");




///// debut de la page
debut_page(_T("icone_suivi_activite"),  "asuivre", "synchro");

echo "<br><br><br>";
gros_titre(_T("icone_suivi_activite"));


debut_gauche();

debut_boite_info();

echo "<div class='verdana2'>";

echo _T('ical_info1').'<br /><br />';

echo _T('ical_info2');

echo "</div>";

fin_boite_info();


$suivi_edito=lire_meta("suivi_edito");
$adresse_suivi=lire_meta("adresse_suivi");
$adresse_site=lire_meta("adresse_site");
$adresse_suivi_inscription=lire_meta("adresse_suivi_inscription");

debut_droite();



///
/// Suivi par mailing-list
///

if ($suivi_edito == "oui" AND strlen($adresse_suivi) > 3 AND strlen($adresse_suivi_inscription) > 3) {
	echo debut_cadre_relief("racine-site-24.gif");
	$lien = propre("[->$adresse_suivi_inscription]");

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif' class='verdana3' style='color:white;'><B>";
	echo _T('ical_titre_mailing')."</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='serif'>";
	echo _T('info_config_suivi_explication');
	echo "<p align='center'>$lien</p>\n";
	echo "</TD></TR>";
	echo "</TABLE>";

	fin_cadre_relief();

	echo "<p>&nbsp;<p>";
}


///
/// Suivi par agenda iCal (taches + rendez-vous)
///

echo debut_cadre_relief("agenda-24.gif");

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif' class='verdana3' style='color:white;'><B>";
echo _T('icone_calendrier')."</FONT></B></TD></TR>";

echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='serif'>";
echo _T('calendrier_synchro');

echo '<p>'._T('ical_info_calendrier').'</p>';


function afficher_liens_calendrier($lien, $icone, $texte) {
	global $adresse_site;
	echo debut_cadre_enfonce($icone);
	echo $texte;
	echo "<div>&nbsp;</div>";
	echo "<div style='float: left; width: 200px;'>";
		icone_horizontale (_T('ical_methode_http'), "$adresse_site/$lien", "calendrier-24.gif");
	echo "</div>";

	echo "<div style='float: right; width: 200px;'>";
		$webcal = ereg_replace("https?://", "webcal://", $adresse_site);
		icone_horizontale (_T('ical_methode_webcal'), "$webcal/$lien", "calendrier-24.gif");
	echo "</div>";
	echo fin_cadre_enfonce();
}

afficher_liens_calendrier('ical.php3','site-24.gif', _T('ical_texte_public'));

echo '<br />';

afficher_liens_calendrier("spip_cal.php3?id=$connect_id_auteur&cle=".afficher_low_sec($connect_id_auteur,'ical'),'cadenas-24.gif',  _T('ical_texte_prive'));

echo "</TD></TR>";
echo "</TABLE>";

echo fin_cadre_relief();

echo "<p>&nbsp;<p>";



///
/// Suivi par RSS
///

echo debut_cadre_relief("site-24.gif");

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif' class='verdana3' style='color:white;'><B>";
echo _T('ical_titre_rss')."</B></TD></TR>";

echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='serif'>";
echo _T('ical_texte_rss').'<p>';

echo propre('<cadre>'.$adresse_site.'/backend.php3</cadre>');

echo "</TD></TR>";
echo "</TABLE>";

echo fin_cadre_relief();

echo "<p>&nbsp;<p>";


///
/// Suivi par Javascript
///

echo debut_cadre_relief("doc-24.gif");

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif' class='verdana3' style='color:white;'><B>";
echo _T('ical_titre_js')."</FONT></B></TD></TR>";

echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='serif'>";
echo _T('ical_texte_js').'<p>';

echo propre('<cadre><script src="'.$adresse_site.'/distrib.php3"></script></cadre>');

echo "</TD></TR>";
echo "</TABLE>";

echo fin_cadre_relief();



fin_page();

?>
