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

// http://doc.spip.org/@statistiques_lang_ok
function affiche_stats_lang($critere) {
	global $spip_lang_right;

	$taille = 450;
	//
	// Statistiques par langue
	//

	$out = "";
	$r = Sql::fetsel("SUM($critere) AS total_visites", "spip_articles");

	$visites = 1;
	// attention a '0.0'
	if ($r and $r['total_visites'] > 0) {
		$total_visites = $r['total_visites'];
	} else {
		$total_visites = 1;
	}

	$result = Sql::select("lang, SUM(".$critere.") AS cnt", "spip_articles", "statut='publie' ", "lang");
		
	$out .= "\n<table cellpadding='2' cellspacing='0' border='0' width='100%' style='border: 1px solid #aaaaaa;'>";
	$ifond = 1;
		
	$visites_abs = 0;
	while ($row = Sql::fetch($result)) {

		$lang = $row['lang'];
		if ($row['cnt']) {
			$visites = round($row['cnt'] / $total_visites * $taille);
			$pourcent = round($row['cnt'] / $total_visites * 100);
		}

		if ($visites > 0) {

				if ($ifond==0){
					$ifond=1;
					$couleur="white";
				}
				else{
					$ifond=0;
					$couleur="eeeeee";
				}
	
				$out .= "\n<tr style='background-color: $couleur'>";
				$dir=lang_dir($lang,'',' dir="rtl"');
				$out .= "<td style='width: 100%; border-bottom: 1px solid #cccccc;'><p $dir><span style='float: $spip_lang_right;'>$pourcent%</span>".traduire_nom_langue($lang)."</p></td>";
				
				$out .= "<td style='border-bottom: 1px solid #cccccc;'>";
				$out .= "\n<table cellpadding='0' cellspacing='0' border='0' width='".($taille+5)."'>";
				$out .= "\n<tr><td style='align:$spip_lang_right; background-color: #eeeeee; border: 1px solid #999999; white-space: nowrap;'>";
				if ($visites_abs > 0)
					$out .= "<img src='" . chemin_image('rien.gif') . "' width='$visites_abs' height='10' alt=' ' />";
				if ($visites>0)
					$out .= "<img src='" . chemin_image('rien.gif') . "' class='couleur_langue' style='border: 0px;' width='$visites' height='8' alt=' ' />";
				$out .= "</td></tr></table>\n";
	
				$out .= "</td>";
				$out .= "</tr>";
				$visites_abs += $visites;
		}
	}
	$out .= "</table>\n";
	return $out;
}
?>
