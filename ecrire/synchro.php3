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

//////// parents


debut_gauche();

	$suivi_edito=lire_meta("suivi_edito");
	$adresse_suivi=lire_meta("adresse_suivi");
	$adresse_site=lire_meta("adresse_site");
	$adresse_suivi_inscription=lire_meta("adresse_suivi_inscription");



debut_droite();



	///
	/// Suivi par mailing-list
	///

	if ($suivi_edito == "oui" AND strlen($adresse_suivi) > 3 AND strlen($adresse_suivi_inscription) > 3) {
	
	
		echo debut_cadre_relief("");
		
		if (ereg("^http",$adresse_suivi_inscription)) $lien = $adresse_suivi_inscription;
		else $lien = "mailto:$adresse_suivi_inscription";
		echo _T("info_config_suivi_explication");
		echo "<div>&nbsp;</div><div style='text-align:center;'><b>";
		echo "<a href=\"$lien\">$adresse_suivi_inscription</a>";
		echo "</b></div><div>&nbsp;</div>";

		echo fin_cadre_relief();
	}


	///
	/// Suivi par agenda iCal (taches + rendez-vous)
	///
	
	echo debut_cadre_relief("agenda-24.gif");

		echo _T("calendrier_synchro");

		echo "<div>&nbsp;</div>";
		
		echo "<div style='float: left; width: 200px;'>";
		icone_horizontale (_T("calendrier_synchro_lien"), "$adresse_site/spip_cal.php3?id=$connect_id_auteur&cle=".afficher_low_sec($connect_id_auteur,'ical'), "calendrier-24.gif");
		echo "</div>";
	
		echo "<div style='float: right; width: 200px;'>";
		
		$webcal = ereg_replace("http.?://", "webcal://", $adresse_site);
		
		icone_horizontale (_T("calendrier_synchro_sync"), "$webcal/spip_cal.php3?id=$connect_id_auteur&cle=".afficher_low_sec($connect_id_auteur,'ical'), "calendrier-24.gif");
		echo "</div>";


	echo fin_cadre_relief();



	///
	/// Fil, tu nous fais un chouette feed RSS ?
	///

fin_page();

?>
