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

echo "<div class='verdana3'>";

echo 'Cette page pr&eacute;sente plusieurs m&eacute;thodes pour rester en contact avec la vie de ce site.<br /><br />';

echo 'Pour plus de renseignements sur toutes ces techniques,<br /> n\'h&eacute;sitez pas &agrave; consulter <a href="http://www.spip.net/fr_suivi">la documentation de SPIP</a>.';

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
	echo "<TR><TD BGCOLOR='$couleur_foncee' class='verdana3'><B>";
	echo 'Mailing-list'."</FONT></B></TD></TR>";

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
echo 'Calendrier'."</FONT></B></TD></TR>";

echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='serif'>";
echo _T("calendrier_synchro");

echo '<p>'.'Deux calendriers sont &agrave; votre disposition. Le premier est un plan du site annon&ccedil;ant tous les articles publi&eacute;s. Le second contient les annonces &eacute;ditoriales ainsi que vos derniers messages priv&eacute;s&nbsp;: il vous est r&eacute;serv&eacute; gr&acirc;ce &agrave; une cl&eacute; personnelle, que vous pouvez modifier &agrave; tout moment en renouvelant votre mot de passe.'.'</p>';


function afficher_liens_calendrier($lien, $icone, $texte) {
	global $adresse_site;
	echo debut_cadre_enfonce($icone);
	echo $texte;
	echo "<div>&nbsp;</div>";
	echo "<div style='float: left; width: 200px;'>";
		icone_horizontale ('T&eacute;l&eacute;chargement', "$adresse_site/$lien", "calendrier-24.gif");
	echo "</div>";

	echo "<div style='float: right; width: 200px;'>";
		$webcal = ereg_replace("https?://", "webcal://", $adresse_site);
		icone_horizontale ('Synchronisation (webcal)', "$webcal/$lien", "calendrier-24.gif");
	echo "</div>";
	echo fin_cadre_enfonce();
}

$texte = 'Ce calendrier vous permet de suivre l\'activit&eacute; publique de ce site (articles et br&egrave;ves publi&eacute;s).';
afficher_liens_calendrier('ical.php3','site-24.gif', $texte);

echo '<br />';

$texte = 'Ce calendrier, &agrave; usage strictement personnel, vous informe de l\'activit&eacute; &eacute;ditoriale priv&eacute;e de ce site (t&acirc;ches et rendez-vous personnels, articles et br&egrave;ves propos&eacute;s...).';
afficher_liens_calendrier("spip_cal.php3?id=$connect_id_auteur&cle=".afficher_low_sec($connect_id_auteur,'ical'),'cadenas-24.gif', $texte);

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
echo 'Fichier &laquo; backend &raquo;'."</B></TD></TR>";

echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='serif'>";
echo 'Vous pouvez syndiquer les nouveaut&eacute;s de ce site dans n\'importe quel lecteur de fichiers au format XML/RSS (Rich Site Summary). C\'est aussi le format qui permet &agrave; SPIP de lire les nouveaut&eacute;s publi&eacute;es sur d\'autres sites utilisant un format d\'&eacute;change compatible.'.'<p>';

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
echo 'Javascript'."</FONT></B></TD></TR>";

echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='serif'>";
echo 'Une ligne de javascript vous permet d\'afficher tr&egrave;s simplement, sur n\'importe quel site vous appartenant, les articles r&eacute;cents publi&eacute;s sur ce site.'.'<p>';

echo propre('<cadre><script src="'.$adresse_site.'/distrib.php3"></script></cadre>');

echo "</TD></TR>";
echo "</TABLE>";

echo fin_cadre_relief();



fin_page();

?>
