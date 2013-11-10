<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');

// http://doc.spip.org/@enfants
function enfants($id_parent, $critere, &$nombre_branche, &$nombre_rub){
	$result = Sql::select("id_rubrique", "spip_rubriques", "id_parent=".intval($id_parent));

	$nombre = 0;

	while($row = Sql::fetch($result)) {
		$id_rubrique = $row['id_rubrique'];

		$visites = intval(Sql::getfetsel("SUM(".$critere.")", "spip_articles", "id_rubrique=".intval($id_rubrique)));
		$nombre_rub[$id_rubrique] = $visites;
		$nombre_branche[$id_rubrique] = $visites;
		$nombre += $visites + enfants($id_rubrique, $critere, $nombre_branche, $nombre_rub);
	}
	if (!isset($nombre_branche[$id_parent])) $nombre_branche[$id_parent]=0;
	$nombre_branche[$id_parent] += $nombre;
	return $nombre;
}


// http://doc.spip.org/@enfants_aff
function enfants_aff($id_parent,$decalage, $taille, $critere, $gauche=0) {
	global $spip_lang_right, $spip_lang_left;
	static $total_site=null;
	static $niveau=0;
	static $nombre_branche;
	static $nombre_rub;
	if (is_null($total_site)){
		$nombre_branche = array();
		$nombre_rub = array();
		$total_site = enfants(0, $critere, $nombre_branche, $nombre_rub);
		if ($total_site<1) $total_site=1;
	}
	$visites_abs = 0;
	$out = "";
	$width = intval(floor(($nombre_branche[$id_parent]/$total_site)*$taille));
	$width = "width:{$width}px;float:$spip_lang_left;";


	$result = Sql::select("id_rubrique, titre, descriptif", "spip_rubriques", "id_parent=$id_parent",'', '0+titre,titre');

	while($row = Sql::fetch($result)){
		$id_rubrique = $row['id_rubrique'];
		$titre = typo($row['titre']);
		$descriptif = attribut_html(couper(typo($row['descriptif']),80));

		if ($nombre_branche[$id_rubrique]>0 OR $nombre_rub[$id_rubrique]>0){
			$largeur_branche = floor(($nombre_branche[$id_rubrique] - $nombre_rub[$id_rubrique]) * $taille / $total_site);
			$largeur_rub = floor($nombre_rub[$id_rubrique] * $taille / $total_site);
			
			if ($largeur_branche+$largeur_rub>0){
					
				if ($niveau == 0) {
					$couleur="#cccccc";
				}

				else if ($niveau == 1) {
					$couleur="#eeeeee";
				}
				else {
					$couleur="white";
				}
				$out .= "<table cellpadding='2' cellspacing='0' border='0' width='100%'>";
				$out .= "\n<tr style='background-color: $couleur'>";
				$out .= "\n<td style='border-bottom: 1px solid #aaaaaa; padding-$spip_lang_left: ".($niveau*20+5)."px;'>";

				
				if ( $largeur_branche > 2) 
					$out .= bouton_block_depliable("<a href='" . generer_url_entite($id_rubrique,'rubrique') . "' style='color: black;' title=\"$descriptif\">$titre</a>","incertain", "stats$id_rubrique");
				else
					$out .= "<div class='rubsimple' style='padding-left: 18px;'>"
						. "<a href='" . generer_url_entite($id_rubrique,'rubrique') . "' style='color: black;' title=\"$descriptif\">$titre</a>"
						. "</div>";
				$out .= "</td>";
				

				// pourcentage de visites dans la branche par rapport au total du site
				$pourcent = round($nombre_branche[$id_rubrique]/$total_site*1000)/10;
				$out .= "\n<td class='verdana1' style='text-align: $spip_lang_right; width: 40px; border-bottom: 1px solid #aaaaaa;'>$pourcent%</td>";

				
				$out .= "\n<td align='right' style='border-bottom: 1px solid #aaaaaa; width:" . ($taille+5) ."px'>";
				
				
				$out .= "\n<table cellpadding='0' cellspacing='0' border='0' width='".($decalage+1+$gauche)."'>";
				$out .= "\n<tr>";
				if ($gauche > 0) $out .= "<td style='width: " .$gauche."px'></td>";
				$out .= "\n<td style='border: 0px; white-space: nowrap;'>";
				$out .= "<div style='border: 1px solid #999999; background-color: #dddddd; height: 1em; padding: 0px; margin: 0px;$width'>";
				if ($visites_abs > 0) $out .= "<img src='" . chemin_image('rien.gif') . "' style='vertical-align: top; height: 1em; border: 0px; width: ".$visites_abs."px;' alt= ' '/>";
				if ($largeur_branche>0) $out .= "<img src='" . chemin_image('rien.gif') . "' class='couleur_cumul' style='vertical-align: top; height: 1em; border: 0px; width: " . $largeur_branche . "px;' alt=' ' />";
				if ($largeur_rub>0) $out .= "<img src='" . chemin_image('rien.gif') . "' class='couleur_nombre' style='vertical-align: top; width: " . $largeur_rub ."px; height: 1em; border: 0px' alt=' ' />";
				$out .= "</div>";
				$out .= "</td></tr></table>\n";
				$out .= "</td></tr></table>";
			}	
		}
		
		if (isset($largeur_branche) && ($largeur_branche > 0)) {
			$niveau++;
			$out .= debut_block_depliable(false,"stats$id_rubrique");
			$out .= enfants_aff($id_rubrique,$largeur_branche, $taille, $critere, $visites_abs+$gauche);
			$out .= fin_block();
			$niveau--;
		}
		$visites_abs = $visites_abs + round($nombre_branche[$id_rubrique]/$total_site*$taille);
	}
	return $out;
}

?>
