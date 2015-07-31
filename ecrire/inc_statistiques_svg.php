<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_ecrire("inc_presentation");

function statistiques_svg_dist()
{
global
  $aff_jours,
  $connect_statut,
  $couleur_claire,
  $couleur_foncee,
  $id_article,
  $visites_today;


// Gestion d'expiration de ce jaja
$date = date("U");
$expire = $date + 2 * 3600;
$headers_only = http_last_modified($expire);

$date = gmdate("D, d M Y H:i:s", $date);
$expire = gmdate("D, d M Y H:i:s", $expire);
if ($headers_only) exit;
@Header ("Last-Modified: ".$date." GMT");
@Header ("Expires: ".$expire." GMT");

if ($connect_statut != '0minirezo') {
	echo _T('avis_non_acces_page');
	fin_page();
	exit;
}
header("Content-type: image/svg+xml");
echo "<?xml version=\"1.0\" standalone=\"no\"?>\n";
echo "<!DOCTYPE svg PUBLIC \"-//W3C//DTD SVG 1.1//EN\" \"http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd\">\n";
echo "<svg  xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" width=\"450\" height=\"310\" x=\"0\" y=\"0\">\n";
	echo "<style type='text/css'>\n";
	echo ".gris {fill: #aaaaaa; fill-opacity: 0.2;}\n";
	echo ".trait {stroke:black;stroke-width:1;}\n";
	echo "</style>\n";
echo '<defs>';
echo '<linearGradient id="orange_red" x1="0%" y1="0%" x2="0%" y2="150%">';
echo '<stop offset="0%" style="stop-color:rgb(255,255,0); stop-opacity:1" />';
echo '<stop offset="100%" style="stop-color:rgb(255,0,0); stop-opacity:1" />';
echo '</linearGradient>';
echo '</defs>';
	echo "<defs>\n";
	echo '<linearGradient id="claire" x1="0%" y1="0%" x2="0%" y2="100%">';
	echo '<stop offset="0%" style="stop-color:'.$couleur_claire.'; stop-opacity:0.3"/>';
	echo '<stop offset="100%" style="stop-color:'.$couleur_foncee.'; stop-opacity:1"/>';
	echo "</linearGradient>\n";
	echo "</defs>\n";
	



	if (!($aff_jours = intval($aff_jours))) $aff_jours = 105;
	if ($id_article = intval($id_article)){
		$table = "spip_visites_articles";
		$table_ref = "spip_referers_articles";
		$where = "id_article=$id_article";
	} else {
		$table = "spip_visites";
		$table_ref = "spip_referers";
		$where = "0=0";
	}
	
	$query="SELECT UNIX_TIMESTAMP(date) AS date_unix FROM $table ".
		"WHERE $where ORDER BY date LIMIT 1";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) {
		$date_premier = $row['date_unix'];
	}

	$query="SELECT UNIX_TIMESTAMP(date) AS date_unix, visites FROM $table ".
		"WHERE $where AND date > DATE_SUB(NOW(),INTERVAL $aff_jours DAY) ORDER BY date";
	$result=spip_query($query);

	while ($row = spip_fetch_array($result)) {
		$date = $row['date_unix'];
		$visites = $row['visites'];

		$log[$date] = $visites;
		if ($i == 0) $date_debut = $date;
		$i++;
	}


	if (count($log)>0) {
		$max = max(max($log),$visites_today);
		$date_today = time();
		$nb_jours = floor(($date_today-$date_debut)/(3600*24));
		
		$maxgraph = substr(ceil(substr($max,0,2) / 10)."000000000000", 0, strlen($max));
	
		if ($maxgraph < 10) $maxgraph = 10;
		if (1.1 * $maxgraph < $max) $maxgraph.="0";	
		if (0.8*$maxgraph > $max) $maxgraph = 0.8 * $maxgraph;
		$rapport = 300 / $maxgraph;

		if (count($log) < 420) $largeur = floor(420 / ($nb_jours+1));
		if ($largeur < 1) {
			$largeur = 1;
			$agreg = ceil(count($log) / 420);	
		} else {
			$agreg = 1;
		}
		if ($largeur > 50) $largeur = 50;

		$largeur_abs = 420 / $aff_jours;
		
		if ($largeur_abs > 1) {
			$inc = ceil($largeur_abs / 5);
			$aff_jours_plus = 420 / ($largeur_abs - $inc);
			$aff_jours_moins = 420 / ($largeur_abs + $inc);
		}
		
		if ($largeur_abs == 1) {
			$aff_jours_plus = 840;
			$aff_jours_moins = 210;
		}
		
		if ($largeur_abs < 1) {
			$aff_jours_plus = 420 * ((1/$largeur_abs) + 1);
			$aff_jours_moins = 420 * ((1/$largeur_abs) - 1);
		}


		echo "<line x1='0' y1='75' x2='".round($largeur*$nb_jours/$agreg)."' y2='75' style='stroke:#999999;stroke-width:1'/>\n";
		echo "<line x1='0' y1='150' x2='".round($largeur*$nb_jours/$agreg)."' y2='150' style='stroke:#999999;stroke-width:1'/>\n";
		echo "<line x1='0' y1='225' x2='".round($largeur*$nb_jours/$agreg)."' y2='225' style='stroke:#999999;stroke-width:1'/>\n";
		
		echo "<line x1='0' y1='37' x2='".round($largeur*$nb_jours/$agreg)."' y2='37' style='stroke:#eeeeee;stroke-width:1'/>\n";
		echo "<line x1='0' y1='112' x2='".round($largeur*$nb_jours/$agreg)."' y2='112' style='stroke:#eeeeee;stroke-width:1'/>\n";
		echo "<line x1='0' y1='187' x2='".round($largeur*$nb_jours/$agreg)."' y2='187' style='stroke:#eeeeee;stroke-width:1'/>\n";
		echo "<line x1='0' y1='262' x2='".round($largeur*$nb_jours/$agreg)."' y2='262' style='stroke:#eeeeee;stroke-width:1'/>\n";


		// Presentation graphique
		while (list($key, $value) = each($log)) {
			
			$test_agreg ++;
	
			if ($test_agreg == $agreg) {	
			
			$test_agreg = 0;
			$n++;
		
			if ($decal == 30) $decal = 0;
			$decal ++;
			$tab_moyenne[$decal] = $value;
		
			// Inserer des jours vides si pas d'entrees	
			if ($jour_prec > 0) {
				$ecart = floor(($key-$jour_prec)/((3600*24)*$agreg)-1);
	
				for ($i=0; $i < $ecart; $i++){
					if ($decal == 30) $decal = 0;
					$decal ++;
					$tab_moyenne[$decal] = $value;

                    $ce_jour=date("Y-m-d", $jour_prec+(3600*24*($i+1)));
			        $jour = nom_jour($ce_jour).' '.affdate_court($ce_jour);

					reset($tab_moyenne);
					$moyenne = 0;
					while (list(,$val_tab) = each($tab_moyenne))
						$moyenne += $val_tab;
					$moyenne = $moyenne / count($tab_moyenne);
	
				//	echo "<td valign='bottom' width=$largeur>";
					$difference = ($hauteur_moyenne) -1;
					$moyenne = round($moyenne,2); // Pour affichage harmonieux
					$hauteur_moyenne = round(($moyenne) * $rapport) - 1;


					if ($hauteur_moyenne_prec > 0) {
						echo "<polygon points = '".(($n-2)*$largeur+round($largeur/2)).",300 ".(($n-2)*$largeur+round($largeur/2)).",".(300-$hauteur_moyenne_prec)." ".(($n-1)*$largeur+round($largeur/2)).",".(300-$hauteur_moyenne)."  ".(($n-1)*$largeur+round($largeur/2)).",300' class='gris' />\n";
						echo "<line x1='".(($n-2)*$largeur+round($largeur/2))."' y1='".(300-$hauteur_moyenne_prec)."' x2='".(($n-1)*$largeur+round($largeur/2))."' y2='".(300-$hauteur_moyenne)."' class='trait' />\n";
					}

					$hauteur_moyenne_prec = $hauteur_moyenne;


					if ($difference > 0) {	
						//echo "<img src='" . _DIR_IMG_PACK . "rien.gif' width=$largeur height=1 style='background-color:#333333;' title=$tagtitle>";
						//echo "<img src='" . _DIR_IMG_PACK . "rien.gif' width=$largeur height=$hauteur_moyenne title=$tagtitle>";
					}
					//echo "<img src='" . _DIR_IMG_PACK . "rien.gif' width=$largeur height=1 style='background-color:black;' title=$tagtitle>";
					//echo "</td>";
					$n++;
				}
			}

			$ce_jour=date("Y-m-d", $key);
			$jour = nom_jour($ce_jour).' '.affdate_court($ce_jour);

			$total_loc = $total_loc + $value;
			reset($tab_moyenne);

			$moyenne = 0;
			while (list(,$val_tab) = each($tab_moyenne))
				$moyenne += $val_tab;
			$moyenne = $moyenne / count($tab_moyenne);
		
			$hauteur_moyenne = round($moyenne * $rapport) - 1;
			$hauteur = round($value * $rapport) - 1;
			$moyenne = round($moyenne,2); // Pour affichage harmonieux
			//echo "<td valign='bottom' width=$largeur>";

//			$tagtitle='"'.attribut_html(supprimer_tags("$jour | "
//			._T('info_visites')." ".$value)).'"';



			if (date("w",$key) == "0") // Dimanche en couleur foncee
				$fill = $couleur_foncee;
			else 
				$fill = "url(#claire)";



			echo "<rect x='".(($n-1)*$largeur)."' y='".(300-$hauteur)."' width='$largeur' height='$hauteur' style='fill:$fill'/>\n";	
			echo "<rect x='".(($n-1)*$largeur)."' y='".(300-$hauteur)."' width='$largeur' height='1' style='fill:$couleur_foncee;'/>\n";	

			if (date("d", $key) == "1") 
				echo "<line x1='".(($n-1)*$largeur)."' y1='".(300-$hauteur_moyenne)."' x2='".(($n-1)*$largeur)."' y2='300' class='trait'/>\n";	
			
				
			if ($hauteur_moyenne_prec > 0) {
				echo "<polygon points = '".(($n-2)*$largeur+round($largeur/2)).",300 ".(($n-2)*$largeur+round($largeur/2)).",".(300-$hauteur_moyenne_prec)." ".(($n-1)*$largeur+round($largeur/2)).",".(300-$hauteur_moyenne)."  ".(($n-1)*$largeur+round($largeur/2)).",300' class='gris' />\n";
				echo "<line x1='".(($n-2)*$largeur+round($largeur/2))."' y1='".(300-$hauteur_moyenne_prec)."' x2='".(($n-1)*$largeur+round($largeur/2))."' y2='".(300-$hauteur_moyenne)."' class='trait' />\n";
			} else {
				echo "<polygon points = '0,300 0,".(300-$hauteur_moyenne)." ".(($n-1)*$largeur+round($largeur/2)).",".(300-$hauteur_moyenne)."  ".(($n-1)*$largeur+round($largeur/2)).",300' class='gris' />\n";
				echo "<line x1='0' y1='".(300-$hauteur_moyenne)."' x2='".(($n-1)*$largeur+round($largeur/2))."' y2='".(300-$hauteur_moyenne)."' class='trait' />\n";
			}
	
			$hauteur_moyenne_prec = $hauteur_moyenne;
			
				$jour_prec = $key;
				$val_prec = $value;
			}
		}


		echo "<rect x='0' y='0' width='".round($largeur*$nb_jours/$agreg)."' height='300' style='stroke-width: 1px; stroke: black; fill: blue; fill-opacity: 0; '/>\n";	

		echo " <text x='".(round($largeur*$nb_jours/$agreg)+3)."' y='300' font-family='Verdana, helvetica, sans-serif, sans' font-size='8' fill='black' >";
		echo "0";
		echo "</text>\n";
		echo " <text x='".(round($largeur*$nb_jours/$agreg)+3)."' y='228' font-family='Verdana, helvetica, sans-serif, sans' font-size='8' fill='black' >";
		echo round($maxgraph/4);
		echo "</text>\n";
		echo " <text x='".(round($largeur*$nb_jours/$agreg)+3)."' y='153' font-family='Verdana, helvetica, sans-serif, sans' font-size='8' fill='black' >";
		echo round($maxgraph/2);
		echo "</text>\n";
		echo " <text x='".(round($largeur*$nb_jours/$agreg)+3)."' y='78' font-family='Verdana, helvetica, sans-serif, sans' font-size='8' fill='black' >";
		echo round(3*$maxgraph/4);
		echo "</text>\n";
		echo " <text x='".(round($largeur*$nb_jours/$agreg)+3)."' y='8' font-family='Verdana, helvetica, sans-serif, sans' font-size='8' fill='black' >";
		echo round($maxgraph);
		echo "</text>\n";

		$gauche_prec = -50;
		for ($jour = $date_debut; $jour <= $date_today; $jour = $jour + (24*3600)) {
			$ce_jour = date("d", $jour);
			
			if ($ce_jour == "1") {
				$afficher = nom_mois(date("Y-m-d", $jour));
				if (date("m", $jour) == 1) $afficher = "<b>".annee(date("Y-m-d", $jour))."</b>";
				
			
				$gauche = ($jour - $date_debut) * $largeur / ((24*3600)*$agreg);
				
				if ($gauche - $gauche_prec >= 40 OR date("m", $jour) == 1) {									
					//echo "<div class='arial0' style='border-$spip_lang_left: 1px solid black; padding-$spip_lang_left: 2px; padding-top: 3px; position: absolute; $spip_lang_left: ".$gauche."px; top: -1px;'>".$afficher."</div>";

					echo "<rect x='$gauche' y='300' width='1' height='10' style='fill: black;'/>\n";
					echo " <text x='".($gauche+3)."' y='308' font-family='Verdana, helvetica, sans-serif, sans' font-size='8' fill='black' >";
					echo filtrer_ical($afficher);
					echo "</text>\n";


					$gauche_prec = $gauche;
					
					
					
				}
			}
		}


	}
	
	echo '</svg>';
}
?>
