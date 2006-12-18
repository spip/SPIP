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

// http://doc.spip.org/@exec_statistiques_lang_dist
function exec_statistiques_lang_dist()
{
  global $connect_statut, $couleur_foncee, $critere, $spip_ecran, $spip_lang_right;

  $commencer_page = charger_fonction('commencer_page', 'inc');
  echo $commencer_page(_T('onglet_repartition_lang'), "statistiques_visites", "repartition-langues");

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

if ($critere == "debut") {
	$critere = "visites";
//	gros_titre(_T('onglet_repartition_debut'));	
}
else {
	$critere = "popularite";
//	gros_titre(_T('onglet_repartition_actuelle'));	
}

 echo ($critere == "popularite") ? barre_onglets("rep_depuis", "popularite"): barre_onglets("rep_depuis", "debut");


if ($connect_statut != '0minirezo') {
	echo _T('avis_non_acces_page');
	echo fin_page();
	exit;
}

//
// Statistiques par langue
//


debut_cadre_enfonce("langues-24.gif");

$result = spip_query("SELECT SUM(".$critere.") AS total_visites FROM spip_articles");

		$visites = 1;
		if ($row = spip_fetch_array($result))
			$total_visites = $row['total_visites'];
		else
			$total_visites = 1;
		echo "<p>";

		$result = spip_query("SELECT lang, SUM(".$critere.") AS cnt FROM spip_articles WHERE statut='publie' GROUP BY lang");
		
		echo "<table cellpadding = 2 cellspacing = 0 border = 0 width='100%' style='border: 1px solid #aaaaaa;'>";
		$ifond = 1;
		
		while ($row = spip_fetch_array($result)) {
			$lang = $row['lang'];
			$visites = round($row['cnt'] / $total_visites * $taille);
			$pourcent = round($row['cnt'] / $total_visites * 100);

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
				echo "<td width='100%' style='border-bottom: 1px solid #cccccc;'><span class='verdana2'$dir><div style='float: $spip_lang_right;'>$pourcent%</div>".traduire_nom_langue($lang)."</span></td>";
				
				echo "<td style='border-bottom: 1px solid #cccccc;'>";
					echo "<table cellpadding='0' cellspacing='0' border='0' width='".($taille+5)."' height=8>";
					echo "<tr><td style='align:$spip_lang_right; background-color: #eeeeee; border: 1px solid #999999; white-space: nowrap;'>";
					if ($visites_abs > 0) echo "<img src='" . _DIR_IMG_PACK . "rien.gif' width='$visites_abs' height='8'>";
					if ($visites>0) echo "<IMG src='" . _DIR_IMG_PACK . "rien.gif' style='background-color: $couleur_foncee;' width='$visites' height=8 border=0>";
					echo "</td></tr></table>\n";
	
				echo "</td>";
				echo "</tr>";
			$visites_abs += $visites;
			}
		}
		echo "</table>";


//echo "<p><font face='Verdana,Arial,Sans,sans-serif' size='3'>"._T('texte_signification')."</font>";


fin_cadre_enfonce();



echo "</td></tr></table></center>";
}

?>
