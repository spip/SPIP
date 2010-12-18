<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/presentation');

// http://doc.spip.org/@enfants
function enfants($id_parent, $critere){
	global $nombre_vis, $nombre_abs;

	$result = sql_select("id_rubrique", "spip_rubriques", "id_parent=$id_parent");

	$nombre = 0;

	while($row = sql_fetch($result)) {
		$id_rubrique = $row['id_rubrique'];

		$visites = intval(sql_getfetsel("SUM(".$critere.")", "spip_articles", "id_rubrique=$id_rubrique"));
		$nombre_abs[$id_rubrique] = $visites;
		$nombre_vis[$id_rubrique] = $visites;
		$nombre += $visites + enfants($id_rubrique, $critere);
	}
	if (!isset($nombre_vis[$id_parent])) $nombre_vis[$id_parent]=0;
	$nombre_vis[$id_parent] += $nombre;
	return $nombre;
}


// http://doc.spip.org/@enfants_aff
function enfants_aff($id_parent,$decalage, $critere, $gauche=0) {

	global $niveau;
	global $nombre_vis;
	global $nombre_abs;
	global $spip_lang_right, $spip_lang_left;
	global $abs_total;
	global $taille;
	$visites_abs = 0;

	$result = sql_select("id_rubrique, titre, descriptif", "spip_rubriques", "id_parent=$id_parent",'', '0+titre,titre');

	while($row = sql_fetch($result)){
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
				echo "\n<tr style='background-color: $couleur'>";
				echo "\n<td style='border-bottom: 1px solid #aaaaaa; padding-$spip_lang_left: ".($niveau*20+5)."px;'>";

				
				if ( $largeur_rouge > 2) 
					echo bouton_block_depliable("<a href='" . generer_url_ecrire("naviguer","id_rubrique=$id_rubrique") . "' style='color: black;' title=\"$descriptif\">$titre</a>","incertain", "stats$id_rubrique");
				else
					echo 	"<div class='verdana1' style='padding-left: 18px; padding-top: 4px; padding-bottom: 3px;'>",
					  "<a href='" . generer_url_ecrire("naviguer","id_rubrique=$id_rubrique") . "' style='color: black;' title=\"$descriptif\">$titre</a>",
					  "</div>";
				echo "</td>";
				
				
				if ($niveau==0 OR 1==1){
					$pourcent = round($nombre_vis[$id_rubrique]/$abs_total*1000)/10;
					echo "\n<td class='verdana1' style='text-align: $spip_lang_right; width: 40px; border-bottom: 1px solid #aaaaaa;'>$pourcent%</td>";
				}
				else { echo "<td style='width: 10px; border-bottom: 1px solid #aaaaaa;'></td>"; }
				
				
				echo "\n<td align='right' style='border-bottom: 1px solid #aaaaaa; width:" . ($taille+5) ."px'>";
				
				
				echo "\n<table cellpadding='0' cellspacing='0' border='0' width='".($decalage+1+$gauche)."'>";
				echo "\n<tr>";
				if ($gauche > 0) echo "<td style='width: " .$gauche."px'></td>";
				echo "\n<td style='border: 0px; white-space: nowrap;'>";
				echo "<div style='border: 1px solid #999999; background-color: #dddddd; height: 12px; padding: 0px; margin: 0px;'>";
				if ($visites_abs > 0) echo "<img src='" . chemin_image('rien.gif') . "' style='vertical-align: top; height: 12px; border: 0px; width: ".$visites_abs."px;' alt= ' '/>";
				if ($largeur_rouge>0) echo "<img src='" . chemin_image('rien.gif') . "' class='couleur_cumul' style='vertical-align: top; height: 12px; border: 0px; width: " . $largeur_rouge . "px;' alt=' ' />";
				if ($largeur_vert>0) echo "<img src='" . chemin_image('rien.gif') . "' class='couleur_nombre' style='vertical-align: top; width: " . $largeur_vert ."px; height: 12px; border: 0px' alt=' ' />";
				echo "</div>";
				echo "</td></tr></table>\n";
				echo "</td></tr></table>";
			}	
		}
		
		if (isset($largeur_rouge) && ($largeur_rouge > 0)) {
			$niveau++;
			echo debut_block_depliable(false,"stats$id_rubrique");
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

	global  $abs_total, $nombre_vis, $taille, $spip_ecran;

	if (!autoriser('voirstats')) {
		include_spip('inc/minipres');
		echo minipres();
	} else {

	$taille = _request('taille');
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_statistiques'), "statistiques_visites", "repartition");
	
	echo debut_grand_cadre(true);
	echo gros_titre(_T('titre_page_statistiques'),'',false);
	if ($spip_ecran == "large") { 
	 	                $largeur_table = 974; 
	 	                $taille = 550; 
	 	        } else { 
	 	                $largeur_table = 750; 
	 	                $taille = 400; 
	 	        } 
	 	 
	echo "\n<br /><br /><table width='$largeur_table'><tr><td class='verdana2' style='text-align: center;  width: $largeur_table" . "px;'>"; 
	$critere = _request('critere');
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

	echo debut_cadre_relief("statistiques-24.gif",true);
	echo "<div style='border: 1px solid #aaaaaa; border-bottom: 0px;'>";
	enfants_aff(0,$taille, $critere);
	echo "</div><br />",
	  "<div class='verdana3' style='text-align: left;'>",
	  _T('texte_signification'),
	  "</div>";
	echo fin_cadre_relief(true);
	echo "</td></tr></table>"; 
	echo fin_grand_cadre(true),fin_page();
	}
}
?>
