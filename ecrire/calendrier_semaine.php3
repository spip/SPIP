<?php

include ("inc.php3");
include_ecrire("inc_agenda.php3");


function afficher_semaine($jour_today,$mois_today,$annee_today){
	global $spip_lang_rtl, $spip_lang_right, $spip_lang_left;
	global $connect_id_auteur, $connect_statut;
	global $spip_lang_rtl;
	global $spip_ecran;
	global $couleur_claire, $couleur_foncee;

	// calculer de nouveau la date du jour pour affichage en blanc
	$ce_jour=date("Y-m-d");

	$nom = mktime(1,1,1,$mois_today,$jour_today,$annee_today);
	$jour_semaine = date("w",$nom);
	
	$debut = date("Y-m-d",mktime (1,1,1,$mois_today, $jour_today-$jour_semaine+1, $annee_today));
	$fin = date("Y-m-d",mktime (1,1,1,$mois_today, $jour_today-$jour_semaine+7, $annee_today));

	if ($jour_semaine==0) $jour_semaine=7;
	
	if ($spip_ecran == "large") {
		$largeur_table = 974;
		$largeur_gauche = 170;
	} else {
		$largeur_table = 750;
		$largeur_gauche = 100;
	}
	$largeur_table = $largeur_table - ($largeur_gauche+20);
	$largeur_col = round($largeur_table/7);
	
	
	
	echo "<table cellpadding=0 cellspacing=0 border=0 width='".($largeur_table+10+$largeur_gauche)."'><tr>";
	
	echo "<td width='$largeur_gauche' class='verdana1' valign='top'>";

		// date du jour
		$today=getdate(time());
		$jour = $today["mday"];
		$mois=$today["mon"];
		$annee=$today["year"];
		$jour_semaine_reel = date("w",mktime(1,1,1,$mois,$jour,$annee));
		$debut_reel = date("Y-m-d",mktime (1,1,1,$mois, $jour-$jour_semaine_reel+1, $annee));
		
	
		
		echo "<div>&nbsp;</div>";
		echo "<div>&nbsp;</div>";

		agenda($mois_today, $annee_today, $jour_today, $mois_today, $annee_today, true);
		agenda($mois_today+1, $annee_today, $jour_today, $mois_today, $annee_today, true);
	
		afficher_taches();

	
	echo "</td>";
	echo "<td width='20'>&nbsp;</td>";
	
	echo "<td width='$largeur_table' valign='top'>";
	echo "<TABLE border=0 CELLSPACING=0 CELLPADDING=0 WIDTH='$largeur_table'>";

	echo "<TR><TD style='text-align:$spip_lang_left;'><A HREF='calendrier_semaine.php3?mois=$mois_today&annee=$annee_today&jour=".($jour_today-7)."'><img src='img_pack/fleche-$spip_lang_left.png' alt='&lt;&lt;&lt;' width='12' height='12' border='0'></A></TD>";
	echo "<TD style='text-align:center;' COLSPAN=5>";
	
		if ($debut != $debut_reel) {
			echo "<div style='float: $spip_lang_left; width: 150px; align: left;'>";
			icone_horizontale(_T("info_aujourdhui")."<br>".affdate_jourcourt("$annee-$mois-$jour"), "calendrier_semaine.php3", "calendrier-24.gif", "", "left");
			echo "</div>";
		}

		echo "<div style='float: $spip_lang_right; width: 120px;'>";
		echo "<a href='calendrier_jour.php3?jour=$jour_today&mois=$mois_today&annee=$annee_today'><img src='img_pack/cal-jour.gif' alt='jour' width='26' height='20' border='0' style='filter: alpha(opacity=50);'></a>";
		echo "&nbsp;";
		echo "<img src='img_pack/cal-semaine.gif' alt='semaine' width='26' height='20' border='0' style='border: 1px solid black'>";
		echo "&nbsp;";
		echo "<a href='calendrier.php3?mois=$mois_today&annee=$annee_today&jour=$jour_today'><img src='img_pack/cal-mois.gif' alt='mois' width='26' height='20' border='0' style='filter: alpha(opacity=50);'></a>";
		echo aide ("messcalen");
		echo "</div>";

	echo "<FONT FACE='arial,helvetica,sans-serif' SIZE='4'><B>";
	
	if (annee($debut) != annee($fin)) echo affdate($debut)." - ".affdate($fin);
	else if (mois($debut) == mois($fin)) echo journum($debut)." - ".affdate_jourcourt($fin);
	else echo affdate_jourcourt($debut)." - ".affdate_jourcourt($fin);
	
	echo "</B></FONT>";

	
	echo "</TD>";
	echo "<TD style='text-align:$spip_lang_right;'><A HREF='calendrier_semaine.php3?mois=$mois_today&annee=$annee_today&jour=".($jour_today+7)."'><img src='img_pack/fleche-$spip_lang_right.png' alt='&gt;&gt;&gt;' width='12' height='12' border='0'></A></TD></TR>";

	echo "<TR>";
	
	for ($j=0; $j<7;$j++){
		$afficher = date("Y-m-d",mktime (1,1,1,$mois_today, $jour_today-$jour_semaine+$j+1, $annee_today));

		echo "<TD ALIGN='center' width='$largeur_col' style='border-bottom: 1px solid black; border-right: 1px solid black; border-left: 1px solid $couleur_claire; border-top: 1px solid $couleur_claire;'  BGCOLOR='$couleur_foncee'><font class='verdana2' color='#FFFFFF'><B>";
		echo "<div style='padding: 3px;'><a href='calendrier_jour.php3?jour=".jour($afficher)."&mois=$mois_today&annee=$annee_today' style='color:white;'>".nom_jour($afficher)." ".jour($afficher)."</a></div>";
		echo "</B></TD>";
	}
	echo "</TR><TR>";
	

	for ($j=0; $j<7;$j++){
		echo "<td width='$largeur_col' HEIGHT='100' BGCOLOR='$couleur_fond' VALIGN='top'>";
		calendrier_jour($jour_today-$jour_semaine+$j+1,$mois_today,$annee_today, "etroit");
		echo "</td>";
	}

	echo "</TR></TABLE>";
	echo "</td></tr></table>";

}


// date du jour
$today=getdate(time());

// sans arguments => mois courant
if (!$mois){
	$jour=$today["mday"];
	$mois=$today["mon"];
	$annee=$today["year"];
}

$date = date("Y-m-d", mktime(0,0,0,$mois, $jour, $annee));
$jour = journum($date);
$mois = mois($date);
$annee = annee($date);



debut_page(d_apostrophe(_T('titre_page_calendrier', array('nom_mois' => $nom_mois, 'annee' => $annee))), "redacteurs", "calendrier");
$activer_messagerie = lire_meta("activer_messagerie");
$connect_activer_messagerie = $GLOBALS["connect_activer_messagerie"];


echo "<div>&nbsp;</div>";

afficher_semaine($jour,$mois,$annee);

if (strlen($les_breves["0"]) > 0 OR $les_articles["0"] > 0){
	echo "<table width=200 background=''><tr width=200><td><FONT FACE='arial,helvetica,sans-serif' SIZE=1>";
	echo "<b>"._T('info_mois_courant')."</b>";
	echo $les_breves["0"];
	echo $les_articles["0"];
	echo "</font></td></tr></table>";
}
	
if ($activer_messagerie == "oui" AND $connect_activer_messagerie != "non"){
	echo "<br><br><br><table width='700' background=''><tr width='700'><td><FONT FACE='arial,helvetica,sans-serif' SIZE=2>";
	echo "<b>"._T('info_aide')."</b>";
	echo "<br><IMG SRC='img_pack/m_envoi_bleu$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0'> "._T('info_symbole_bleu')."\n";
	echo "<br><IMG SRC='img_pack/m_envoi$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0'> "._T('info_symbole_vert')."\n";
	echo "<br><IMG SRC='img_pack/m_envoi_jaune$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0'> "._T('info_symbole_jaune')."\n";
	echo "</font></td></tr></table>";
}

// fin_page();

?>
