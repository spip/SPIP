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

	$date = date("Y-m-d", mktime(0,0,0,$mois, $jour, $annee));
	$jour = jour($date);
	$mois = mois($date);
	$annee = annee($date);



///// debut de la page
debut_page(nom_jour("$annee-$mois-$jour")." ".affdate("$annee-$mois-$jour"),  "redacteurs", "calendrier");


//////// parents


//barre_onglets("calendrier", "jour");

	if ($spip_ecran == "large") {
		$largeur_table = 974;
	} else {
		$largeur_table = 750;
	}

	echo "<div>&nbsp;</div>";
	echo "<table width='$largeur_table'>";
	echo "<TR><TD style='text-align:$spip_lang_left;'><A HREF='calendrier_jour.php3?jour=".($jour-1)."&mois=$mois&annee=$annee'><img src='img_pack/fleche-$spip_lang_left.png' alt='&lt;&lt;&lt;' width='12' height='12' border='0'></A></TD>";
	echo "<TD style='text-align:center;'>";
	

	echo "<div style='float: $spip_lang_left; width: 150px; align: left;'>";
	if ($jour != $jour_today OR $mois != $mois_today OR $annee != $annee_today) {
			icone_horizontale(_T("info_aujourdhui")."<br>".affdate("$annee_today-$mois_today-$jour_today"), "calendrier_jour.php3", "calendrier-24.gif", "", "center");
	}
	echo "&nbsp;</div>";


		echo "<div style='float: $spip_lang_right; width: 120px;'>";
		echo "<img src='img_pack/cal-jour.gif' alt='jour' width='26' height='20' border='0' style='border: 1px solid black;'>";
		echo "&nbsp;";
		echo "<img src='img_pack/cal-semaine.gif' alt='semaine' width='26' height='20' border='0' style='filter: alpha(opacity=50);'>";
		echo "&nbsp;";
		echo "<a href='calendrier.php3?mois=$mois&annee=$annee'><img src='img_pack/cal-mois.gif' alt='mois' width='26' height='20' border='0' style='filter: alpha(opacity=50);'></a>";
		echo "</div>";

	echo "<FONT FACE='arial,helvetica,sans-serif' SIZE=4><B>".nom_jour("$annee-$mois-$jour")." ".affdate("$annee-$mois-$jour")."</B></FONT>";

	
	echo "</TD>";
	echo "<TD style='text-align:$spip_lang_right;'><A HREF='calendrier_jour.php3?jour=".($jour+1)."&mois=$mois&annee=$annee'><img src='img_pack/fleche-$spip_lang_right.png' alt='&gt;&gt;&gt;' width='12' height='12' border='0'></A></TD></TR>";

	echo "</table>";	




echo "<div align='center'>";
	echo "<font size='1'>";
	echo " <a href='message_edit.php3?rv=$annee-$mois-$jour&new=oui&type=pb' style='color: blue;'><IMG SRC='img_pack/m_envoi_bleu$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0'> "._T("lien_nouvea_pense_bete")."</a>";
	echo " &nbsp; <a href='message_edit.php3?rv=$annee-$mois-$jour&new=oui&type=normal' style='color: green;'><IMG SRC='img_pack/m_envoi$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0'> "._T("lien_nouveau_message")."</a>";

	if ($connect_statut == "0minirezo")
		echo " &nbsp; <a href='message_edit.php3?rv=$annee-$mois-$jour&new=oui&type=affich' style='color: #ff9900;'><IMG SRC='img_pack/m_envoi_jaune$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0'> "._T("lien_nouvelle_annonce")."</a>\n";
	echo "</font>";
echo "</div>\n";	


debut_gauche();


agenda ($mois, $annee, $jour, $mois, $annee);
agenda ($mois+1, $annee, $jour, $mois, $annee);


afficher_taches ();

	// afficher en reduction le tableau du jour suivant
	if ($spip_ecran == "large") {
		creer_colonne_droite();	
		calendrier_jour($jour+1,$mois,$annee, false);
	}
	
debut_droite();

	echo "<div>&nbsp;</div>";
	calendrier_jour($jour,$mois,$annee, true);


fin_page();

?>
