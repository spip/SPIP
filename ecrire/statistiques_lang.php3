<?php

include ("inc.php3");


debut_page(_T('onglet_repartition_lang'), "suivi", "repartition-langues");

	if ($spip_ecran == "large") {
		$largeur_table = 974;
	} else {
		$largeur_table = 750;
	}	
	$taille = $largeur_table - 200;	
echo "<center><table width='$largeur_table'><tr width='$largeur_table'><td width='$largeur_table' class='verdana2'>";



echo "<br><br>";
echo "<center>";
gros_titre(_T('onglet_repartition_lang'));
echo "</center>";
//barre_onglets("repartition", "langues");

if ($GLOBALS["critere"] == "debut") {
	$critere = "visites";
//	gros_titre(_T('onglet_repartition_debut'));	
}
else {
	$critere = "popularite";
//	gros_titre(_T('onglet_repartition_actuelle'));	
}

if ($critere == "popularite") barre_onglets("rep_depuis", "popularite");
else barre_onglets("rep_depuis", "debut");



if ($connect_statut != '0minirezo') {
	echo _T('avis_non_acces_page');
	fin_page();
	exit;
}

//
// Statistiques sur le site
//


function enfants($id_parent){
	global $nombre_vis;
	global $total_vis;
	global $nombre_abs;
	global $critere;

	$query = "SELECT id_rubrique FROM spip_rubriques WHERE id_parent='$id_parent'";
	$result = spip_query($query);
	$nombre = 0;

	while($row = spip_fetch_array($result)) {
		$id_rubrique = $row['id_rubrique'];

		$query2 = "SELECT SUM(".$critere.") AS cnt FROM spip_articles WHERE id_rubrique='$id_rubrique'";
		$result2 = spip_query($query2);
		$visites = 0;
		if ($row2 = spip_fetch_array($result2)) {
			$visites = $row2['cnt'];
		}
		$nombre_abs[$id_rubrique] = $visites;
		$nombre_vis[$id_rubrique] = $visites;
		$nombre += $visites;
		$nombre += enfants($id_rubrique);
	}
	$nombre_vis[$id_parent] += $nombre;
	return $nombre;
}





if ($total_vis<1) $total_vis=1;

debut_cadre_enfonce("langues-24.gif");


		$query = "SELECT SUM(".$critere.") AS total_visites FROM spip_articles";
		$result = spip_query($query);
		$visites = 1;
		if ($row = spip_fetch_array($result))
			$total_visites = $row['total_visites'];
		else
			$total_visites = 1;
		echo "<p>";


		$query = "SELECT lang, SUM(".$critere.") AS cnt FROM spip_articles WHERE statut='publie' GROUP BY lang";
		$result = spip_query($query);
		
		
		echo "<table cellpadding = 2 cellspacing = 0 border = 0 width='100%' style='border: 1px solid #aaaaaa;'>";
		$ifond = 1;
		
		while ($row = spip_fetch_array($result)) {
			$lang = $row['lang'];
			$visites = round($row['cnt'] / $total_visites * $taille);
			
			if ($visites > 0) {

				if ($ifond==0){
					$ifond=1;
					$couleur="white";
				}else{
					$ifond=0;
					$couleur="eeeeee";
				}
	
				echo "<tr bgcolor='$couleur'>";
				$dir=lang_dir($lang,'',' dir=rtl');
				echo "<td width='100%' style='border-bottom: 1px solid #cccccc;'><span class='verdana2'$dir><div style='float: $spip_lang_right;'>$visites%</div>".traduire_nom_langue($lang)."</span></td>";
				
				echo "<td style='border-bottom: 1px solid #cccccc;'>";
					echo "<TABLE CELLPADDING=0 CELLSPACING=0 BORDER=0 WIDTH='".($taille+5)."' HEIGHT=8>";
					echo "<TR><TD style='align:$spip_lang_right; background-color: #eeeeee; border: 1px solid #999999; white-space: nowrap;'>";
					if ($visites_abs > 0) echo "<img src='" . _DIR_IMG_PACK . "rien.gif' width='$visites_abs' height='8'>";
					if ($visites>0) echo "<IMG SRC='" . _DIR_IMG_PACK . "rien.gif' style='background-color: $couleur_foncee;' WIDTH='$visites' HEIGHT=8 BORDER=0>";
					echo "</TD></TR></TABLE>\n";
	
				echo "</td>";
				echo "</tr>";
			$visites_abs += $visites;
			}
		}
		echo "</table>";


//echo "<P><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3>"._T('texte_signification')."</FONT>";


fin_cadre_enfonce();



echo "</td></tr></table></center>";

?>

