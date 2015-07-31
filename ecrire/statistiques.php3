<?php

include ("inc.php3");


debut_page(_T('titre_page_statistiques'), "suivi", "repartition");

echo "<br><br>";
gros_titre(_T('titre_statistiques'));
if (lire_meta('multi_articles') == 'oui' OR lire_meta('multi_rubriques') == 'oui')
	barre_onglets("repartition", "rubriques");

if ($GLOBALS["critere"] == "debut") {
	$critere = "visites";
	gros_titre(_T('onglet_repartition_debut'));	
}
else {
	$critere = "popularite";
	gros_titre(_T('onglet_repartition_actuelle'));	
}



debut_gauche();

if ($critere == "popularite") icone_horizontale(_T('icone_repartition_debut'), "statistiques.php3?critere=debut", "rubrique-24.gif","");
else icone_horizontale(_T('icone_repartition_actuelle'), "statistiques.php3", "rubrique-24.gif","");



debut_droite();

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

	$query = "SELECT id_rubrique FROM spip_rubriques WHERE id_parent=\"$id_parent\"";
	$result = spip_query($query);
	$nombre = 0;

	while($row = spip_fetch_array($result)) {
		$id_rubrique = $row['id_rubrique'];

		$query2 = "SELECT SUM(".$critere.") AS cnt FROM spip_articles WHERE id_rubrique=\"$id_rubrique\"";
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


function enfants_aff($id_parent,$decalage) {
	global $total_vis;
	global $ifond;
	global $niveau;
	global $nombre_vis;
	global $nombre_abs;
	global $couleur_claire;
	global $abs_total;
	$query="SELECT id_rubrique, titre FROM spip_rubriques WHERE id_parent=\"$id_parent\" ORDER BY titre";
	$result=spip_query($query);


	while($row = spip_fetch_array($result)){
		$id_rubrique = $row['id_rubrique'];
		$titre = typo($row['titre']);

		if ($nombre_vis[$id_rubrique]>0 OR $nombre_abs[$id_rubrique]>0){
			$largeur_rouge = floor(($nombre_vis[$id_rubrique] - $nombre_abs[$id_rubrique]) * 100 / $total_vis);
			$largeur_vert = floor($nombre_abs[$id_rubrique] * 100 / $total_vis);
			
			if ($largeur_rouge+$largeur_vert>0){
				if ($ifond==0){
					$ifond=1;
					$couleur="#FFFFFF";
				}else{
					$ifond=0;
					$couleur="$couleur_claire";
				}
				if ($niveau==0) {
					$couleur='#DDDDCC';
					$titre = majuscules($titre);
				}

				echo "<TR BGCOLOR='$couleur' BACKGROUND='img_pack/rien.gif'><TD WIDTH=\"100%\">";
				echo "<IMG SRC='img_pack/rien.gif' WIDTH='".($niveau*20+1)."' HEIGHT=8 BORDER=0>";
				echo "<FONT FACE='arial,helvetica,sans-serif' SIZE=2>";	
				echo "<A HREF='naviguer.php3?coll=$id_rubrique'>$titre</A>";
				
				if ($niveau==0){
					$pourcent=round($nombre_vis[$id_rubrique]/$abs_total*100);
					echo " &nbsp; $pourcent %";
				}
				
				echo "</FONT>";
				echo "</TD><TD ALIGN='right'>";
				
				
				echo "<TABLE CELLPADDING=0 CELLSPACING=0 BORDER=0 WIDTH=".($decalage+1)." HEIGHT=8>";
				echo "<TR><TD BACKGROUND='img_pack/jauge-fond.gif' ALIGN='right'>";
				if ($largeur_vert>0) echo "<IMG SRC='img_pack/jauge-vert.gif' WIDTH=$largeur_vert HEIGHT=8 BORDER=0>";
				if ($largeur_rouge>0) echo "<IMG SRC='img_pack/jauge-rouge.gif' WIDTH=$largeur_rouge HEIGHT=8 BORDER=0>";
				echo "<IMG SRC='img_pack/rien.gif' HEIGHT=8 WIDTH=1 BORDER=0>";
				
				echo "</TD></TR></TABLE>\n";
				echo "</TD></TR>";
		}	
		}	
		$niveau++;
		enfants_aff($id_rubrique,$largeur_rouge);
		$niveau--;
	}
	
}


$query = "SELECT count(*) AS cnt FROM spip_articles where statut='publie'";
$result = spip_fetch_array(spip_query($query));
$nb_art = $result['cnt'];

if ($nb_art){
	$cesite = "<LI> $nb_art "._T('info_article_2');
	$query = "SELECT count(*) AS cnt FROM spip_breves where statut='publie'";
	$result = spip_fetch_array(spip_query($query));
	$nb_breves = $result['cnt'];
	if ($nb_breves) $cesite .= "<LI> $nb_breves "._T('info_breves_2');
	$query = "SELECT count(*) AS cnt FROM spip_forum where statut='publie'";
	$result = spip_fetch_array(spip_query($query));
	$nb_forum = $result['cnt'];
	if ($nb_forum) $cesite .= "<LI> $nb_forum "._T('info_contribution');
	echo "<P><B>"._T('info_contenance')."<UL> $cesite.</UL></B>";
}



$abs_total=enfants(0);
if ($abs_total<1) $abs_total=1;
$nombre_vis[0] = 0;

$query = "SELECT id_rubrique FROM spip_rubriques WHERE id_parent=\"0\"";
$result = spip_query($query);

while($row = spip_fetch_array($result)) {
	$id_rubrique = $row['id_rubrique'];
	if ($nombre_vis[$id_rubrique]>$total_vis) $total_vis=$nombre_vis[$id_rubrique];
}

if ($total_vis<1) $total_vis=1;

debut_cadre_relief("statistiques-24.gif");
echo "<TABLE CELLPADDING=2 CELLSPACING=0 BORDER=0>";
enfants_aff(0,100);
echo "<TR><TD></TD><TD><IMG SRC='img_pack/rien.gif' WIDTH=100 HEIGHT=1 BORDER=0></TD>";


echo "</TABLE>";

echo "<P><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3>"._T('texte_signification')."</FONT>";


fin_cadre_relief();



fin_page();

?>

