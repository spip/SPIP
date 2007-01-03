<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');

// http://doc.spip.org/@enfants
function enfants($id_parent, $critere){
	global $nombre_vis;

	global $nombre_abs;

	$result = spip_query("SELECT id_rubrique FROM spip_rubriques WHERE id_parent='$id_parent'");

	$nombre = 0;

	while($row = spip_fetch_array($result)) {
		$id_rubrique = $row['id_rubrique'];

		$result2 = spip_query("SELECT SUM(".$critere.") AS cnt FROM spip_articles WHERE id_rubrique='$id_rubrique'");

		$visites = 0;
		if ($row2 = spip_fetch_array($result2)) {
			$visites = $row2['cnt'];
		}
		$nombre_abs[$id_rubrique] = $visites;
		$nombre_vis[$id_rubrique] = $visites;
		$nombre += $visites;
		$nombre += enfants($id_rubrique, $critere);
	}
	$nombre_vis[$id_parent] += $nombre;
	return $nombre;
}


// http://doc.spip.org/@enfants_aff
function enfants_aff($id_parent,$decalage, $critere, $gauche=0) {

	global $ifond;
	global $niveau;
	global $nombre_vis;
	global $nombre_abs;
	global $couleur_claire, $couleur_foncee, $spip_lang_right, $spip_lang_left;
	global $abs_total;
	global $taille;

	$result=spip_query("SELECT id_rubrique, titre, descriptif FROM spip_rubriques WHERE id_parent='$id_parent' ORDER BY 0+titre, titre");

	while($row = spip_fetch_array($result)){
		$id_rubrique = $row['id_rubrique'];
		$titre = typo($row['titre']);
		$descriptif = attribut_html(couper(typo($row['descriptif']),80));

		if ($nombre_vis[$id_rubrique]>0 OR $nombre_abs[$id_rubrique]>0){
			$largeur_rouge = floor(($nombre_vis[$id_rubrique] - $nombre_abs[$id_rubrique]) * $taille / $abs_total);
			$largeur_vert = floor($nombre_abs[$id_rubrique] * $taille / $abs_total);
			
			if ($largeur_rouge+$largeur_vert>0){
					
				if ($niveau == 0) {
					$couleur="#cccccc";
				}

				else if ($niveau == 1) {
					$couleur="#eeeeee";
				}
				else {
					$couleur="white";
				}
				echo "<table cellpadding='2' cellspacing='0' border='0' width='100%'>";
				echo "\n<tr bgcolor='$couleur'>";
				echo "\n<td style='border-bottom: 1px solid #aaaaaa; padding-$spip_lang_left: ".($niveau*20+5)."px;'>";
				if ($niveau==0 OR 1==1){
					$pourcent = round($nombre_vis[$id_rubrique]/$abs_total*1000)/10;
					echo "\n<div class='verdana1' style='float: $spip_lang_right;'>$pourcent%</div>";
				}

				if ( $largeur_rouge > 2) echo bouton_block_invisible("stats$id_rubrique");
				
				echo "<span class='verdana1'>";	
				echo "<a href='" . generer_url_ecrire("naviguer","id_rubrique=$id_rubrique") . "' style='color: black;' title=\"$descriptif\">$titre</a>";
				
				
				echo "</span>";
				echo "</td>\n<td align='right' style='border-bottom: 1px solid #aaaaaa; width:" . ($taille+5) ."px'>";
				
				
				echo "\n<table cellpadding='0' cellspacing='0' border='0' width='".($decalage+1+$gauche)."'>";
				echo "\n<tr>";
				if ($gauche > 0) echo "<td style='width: " .$gauche."px'></td>";
				echo "\n<td style='background-color: #eeeeee; border: 1px solid #999999; white-space: nowrap;'>";
				if ($visites_abs > 0) echo "<img src='" . _DIR_IMG_PACK . "rien.gif' style='height: 8px; border: 0px; width: ".$visites_abs."px;' alt= ' '/>";
				if ($largeur_rouge>0) echo "<img src='" . _DIR_IMG_PACK . "rien.gif' style='background-color: $couleur_foncee; height: 8px; border: 0px; width: " . $largeur_rouge . "px;' alt=' ' />";
				if ($largeur_vert>0) echo "<img src='" . _DIR_IMG_PACK . "rien.gif' style='background-color: $couleur_claire; width: " . $largeur_vert ."px; height: 8px; border: 0px' alt=' ' />";
				
				echo "</td></tr></table>\n";
				echo "</td></tr></table>";
			}	
		}
		
		if ($largeur_rouge > 0) {
			$niveau++;
			echo debut_block_invisible("stats$id_rubrique");
			enfants_aff($id_rubrique,$largeur_rouge, $critere, $visites_abs+$gauche);
			echo fin_block();
			$niveau--;
		}
		$visites_abs = $visites_abs + round($nombre_vis[$id_rubrique]/$abs_total*$taille);
	}
}

// http://doc.spip.org/@exec_statistiques_repartition_dist
function exec_statistiques_repartition_dist()
{

  global $connect_statut, $connect_toutes_rubriques, $spip_ecran, $taille,
    $abs_total, $nombre_vis, $critere;

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_statistiques'), "statistiques_visites", "repartition");
	
	if (($connect_statut != '0minirezo')|| !$connect_toutes_rubriques) {
		echo _T('avis_non_acces_page');
		exit;
	}

	if ($spip_ecran == "large") {
		$largeur_table = 974;
		$taille = 550;
	} else {
		$largeur_table = 750;
		$taille = 400;
	}

	echo "\n<br /><br /><table width='$largeur_table'><tr><td class='verdana2' style='text-align: center;  width: $largeur_table" . "px;'>";
	gros_titre(_T('titre_page_statistiques'));

	if ($critere == "debut") {
		$critere = "visites";
		echo barre_onglets("stat_depuis", "debut");
	}
	else {
		$critere = "popularite";
		echo barre_onglets("stat_depuis", "popularite");
	}

	$abs_total=enfants(0, $critere);
	if ($abs_total<1) $abs_total=1;
	$nombre_vis[0] = 0;

	debut_cadre_relief("statistiques-24.gif");
	echo "<div style='border: 1px solid #aaaaaa;'>";
	enfants_aff(0,$taille, $critere);
	echo "</div><br />",
	  "<div class='verdana3' style='text-align: left;'>",
	  _T('texte_signification'),
	  "</div>";
	fin_cadre_relief();

	echo "</td></tr></table>";

	echo fin_page();
}
?>
