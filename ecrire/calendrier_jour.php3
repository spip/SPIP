<?php

include ("inc.php3");
include_ecrire ("inc_logos.php3");
include_ecrire ("inc_mots.php3");
include_ecrire ("inc_documents.php3");
include_ecrire ("inc_agenda.php3");

// date du jour
$today=getdate(time());
	$jour_today = $today["mday"];
	$mois_today = $today["mon"];
	$annee_today = $today["year"];

// sans arguments => mois courant
if (!$mois){
	$jour=$today["mday"];
	$mois=$today["mon"];
	$annee=$today["year"];
}




///// debut de la page
debut_page(nom_jour("$annee-$mois-$jour")." ".affdate("$annee-$mois-$jour"),  "asuivre", "calendrier");


//////// parents


barre_onglets("calendrier", "jour");


gros_titre(nom_jour("$annee-$mois-$jour")." ".affdate("$annee-$mois-$jour"));


echo "<p /><div align='center'>";
	echo "<font size='1'>";
	echo " <a href='message_edit.php3?rv=$annee-$mois-$jour&new=oui&type=pb' style='color: blue;'><IMG SRC='img_pack/m_envoi_bleu$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0'> "._T("lien_nouvea_pense_bete")."</a>";
	echo " &nbsp; <a href='message_edit.php3?rv=$annee-$mois-$jour&new=oui&type=normal' style='color: green;'><IMG SRC='img_pack/m_envoi$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0'> "._T("lien_nouveau_message")."</a>";

	if ($connect_statut == "0minirezo")
		echo " &nbsp; <a href='message_edit.php3?rv=$annee-$mois-$jour&new=oui&type=affich' style='color: #ff9900;'><IMG SRC='img_pack/m_envoi_jaune$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0'> "._T("lien_nouvelle_annonce")."</a>\n";
	echo "</font>";
echo "</div>\n";	


debut_gauche();

if ($jour != $jour_today OR $mois != $mois_today OR $annee != $annee_today) {
			icone(_T("info_aujourdhui")."<br>".affdate("$annee_today-$mois_today-$jour_today"), "calendrier_jour.php3", "calendrier-24.gif", "", "center");
			echo "<p />";
}

agenda ($mois, $annee, $jour, $mois, $annee);
agenda ($mois+1, $annee, $jour, $mois, $annee);


afficher_taches ();

	// afficher en reduction le tableau du jour suivant
	creer_colonne_droite();	
	calendrier_jour($jour+1,$mois,$annee, false);

debut_droite();

calendrier_jour($jour,$mois,$annee, true);


fin_page();

?>
