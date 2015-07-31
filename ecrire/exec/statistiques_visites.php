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

include_spip('inc/presentation');
include_spip('inc/statistiques');

// Donne la hauteur du graphe en fonction de la valeur maximale
// Doit etre un entier "rond", pas trop eloigne du max, et dont
// les graduations (divisions par huit) soient jolies :
// on prend donc le plus proche au-dessus de x de la forme 12,16,20,40,60,80,100
function maxgraph($max) {
	$max = max(10,$max);
	$p = pow(10, strlen($max)-2);
	$m = $max/$p;
	foreach (array(100,80,60,40,20,16,12,10) as $l)
		if ($m<=$l) $maxgraph = $l*$p;
	return $maxgraph;
}

function http_img_rien($width, $height, $style='', $title='') {
	return http_img_pack('rien.gif', $title, 
		"width='$width' height='$height'" 
		. (!$style ? '' : (" style='$style'"))
		. (!$title ? '' : (" title=\"$title\"")));
}

function exec_statistiques_visites_dist()
{
  global
    $aff_jours,
    $connect_statut,
    $couleur_claire,
    $couleur_foncee,
    $id_article,
    $limit,
    $origine,
    $spip_lang_left;


	$GLOBALS['accepte_svg'] = flag_svg();


  $titre = $pourarticle = "";

if ($id_article = intval($id_article)){
	$result = spip_query("SELECT titre, visites, popularite FROM spip_articles WHERE statut='publie' AND id_article=$id_article");


	if ($row = spip_fetch_array($result)) {
		$titre = typo($row['titre']);
		$total_absolu = $row['visites'];
		$val_popularite = round($row['popularite']);
	}
} 
else {
	$result = spip_query("SELECT SUM(visites) AS total_absolu FROM spip_visites");


	if ($row = spip_fetch_array($result)) {
		$total_absolu = $row['total_absolu'];
	}
}


if ($titre) $pourarticle = " "._T('info_pour')." &laquo; $titre &raquo;";

if ($origine) {
	debut_page(_T('titre_page_statistiques_referers'), "statistiques_visites", "statistiques");
	echo "<br /><br />";
	gros_titre(_T('titre_liens_entrants'));
	barre_onglets("statistiques", "referers");

	debut_gauche();
	debut_boite_info();
	echo "<p align=left style='font-family:Verdana,Arial,Sans,sans-serif; font-size:small;'>"._T('info_gauche_statistiques_referers')."</p>";
	fin_boite_info();
	
	debut_droite();

}
else {
	debut_page(_T('titre_page_statistiques_visites').$pourarticle, "statistiques_visites", "statistiques");
	echo "<br /><br />";
	gros_titre(_T('titre_evolution_visite')."<html>".aide("confstat")."</html>");
//	barre_onglets("statistiques", "evolution");
	if ($titre) gros_titre($titre);

	debut_gauche();

	echo "<br />";

	echo "<div class='iconeoff' style='padding: 5px;'>";
	echo "<div style='font-family:Verdana,Arial,Sans,sans-serif; font-size:small;'>";
	echo typo(_T('info_afficher_visites'));
	echo "<ul>";
	if ($id_article>0) {
		echo "<li><b><a href='" . generer_url_ecrire("statistiques_visites","") . "'>"._T('info_tout_site')."</a></b></li>";
	} else {
		echo "<li><b>"._T('titre_page_articles_tous')."</b></li>";
	}

		echo "</ul>";
		echo "</div>";
		echo "</div>";

	
	// Par popularite
	$articles_recents[] = "0";
	$result = spip_query("SELECT id_article FROM spip_articles WHERE statut='publie' AND popularite > 0 ORDER BY date DESC LIMIT 10");

	while ($row = spip_fetch_array($result)) {
		$articles_recents[] = $row['id_article'];
	}
	$articles_recents = join($articles_recents, ",");
		

	// Par popularite
	$result = spip_query("SELECT id_article, titre, popularite, visites FROM spip_articles WHERE statut='publie' AND popularite > 0 ORDER BY popularite DESC");

	$nombre_articles = spip_num_rows($result);
	if ($nombre_articles > 0) {
		echo "<br />\n";
		echo "<div class='iconeoff' style='padding: 5px;'>\n";
		echo "<div style='font-family:Verdana,Arial,Sans,sans-serif; font-size:small;'>";
		echo typo(_T('info_visites_plus_populaires'));
		echo "<ol style='padding-left:40px; font-size:x-small;color:#666666;'>";
		$liste = 0;
		while ($row = spip_fetch_array($result)) {
			$titre = typo($row['titre']);
			$l_article = $row['id_article'];
			$visites = $row['visites'];
			$popularite = round($row['popularite']);
			$liste++;
			$classement[$l_article] = $liste;
			
			if ($liste <= 30) {
				$articles_vus[] = $l_article;
			
				if ($l_article == $id_article){
					echo "\n<li value='$liste'><b>$titre</b>";
				} else {
					echo "\n<li value='$liste'><a href='" . generer_url_ecrire("statistiques_visites","id_article=$l_article") . "' title='"._T('info_popularite', array('popularite' => $popularite, 'visites' => $visites))."'>$titre</a>";
				}
			}
		}
		$articles_vus = join($articles_vus, ",");
			
		// Par popularite
		$result_suite = spip_query("SELECT id_article, titre, popularite, visites FROM spip_articles WHERE statut='publie' AND id_article IN ($articles_recents) AND id_article NOT IN ($articles_vus) ORDER BY popularite DESC");

		
		if (spip_num_rows($result_suite) > 0) {
			echo "<br /><br />[...]<br /><br />";
			while ($row = spip_fetch_array($result_suite)) {
				$titre = typo($row['titre']);
				$l_article = $row['id_article'];
				$visites = $row['visites'];
				$popularite = round($row['popularite']);
				$numero = $classement[$l_article];
				
				if ($l_article == $id_article){
					echo "\n<li value='$numero'><b>$titre</b></li>";
				} else {
					echo "\n<li value='$numero'><a href='" . generer_url_ecrire("statistiques_visites","id_article=$l_article") . "' title='"._T('info_popularite_3', array('popularite' => $popularite, 'visites' => $visites))."'>$titre</a></li>";
				}
			}
		}
			
		echo "</ol>";

		echo "<b>"._T('info_comment_lire_tableau')."</b><br>"._T('texte_comment_lire_tableau');

		echo "</div>";
		echo "</div>";
		echo "</div>";
	}


	// Par visites depuis le debut
	$result = spip_query("SELECT id_article, titre, popularite, visites FROM spip_articles WHERE statut='publie' AND popularite > 0 ORDER BY visites DESC LIMIT 30");

		
	if (spip_num_rows($result) > 0) {
		creer_colonne_droite();

		echo "<br /><div class='iconeoff' style='padding: 5px;'>";
		echo "<div style='font-family:Verdana,Arial,Sans,sans-serif; font-size:small;overflow:hidden;'>";
		echo typo(_T('info_affichier_visites_articles_plus_visites'));
		echo "<ol style='padding-left:40px; font-size:x-small;color:#666666;'>";

		while ($row = spip_fetch_array($result)) {
			$titre = typo($row['titre']);
			$l_article = $row['id_article'];
			$visites = $row['visites'];
			$popularite = round($row['popularite']);
				$numero = $classement[$l_article];
				
				if ($l_article == $id_article){
					echo "\n<li value='$numero'><b>$titre</b></li>";
				} else {
					echo "\n<li value='$numero'><a href='" . generer_url_ecrire("statistiques_visites","id_article=$l_article") . "' title='"._T('info_popularite_4', array('popularite' => $popularite, 'visites' => $visites))."'>$titre</a></li>";
				}
		}
		echo "</ol>";
		echo "</div>";
	}


	//
	// Afficher les boutons de creation d'article et de breve
	//
	if ($connect_statut == '0minirezo') {
		if ($id_article > 0) {
			debut_raccourcis();
			icone_horizontale(_T('icone_retour_article'), generer_url_ecrire("articles","id_article=$id_article"), "article-24.gif","rien.gif");
			fin_raccourcis();
		}
	}



	debut_droite();
}



if ($connect_statut != '0minirezo') {
	echo _T('avis_non_acces_page');
	fin_page();
	exit;
}


//////

 if (!($aff_jours = intval($aff_jours))) $aff_jours = 105;

 if (!$origine) {

	if ($id_article) {
		$table = "spip_visites_articles";
		$table_ref = "spip_referers_articles";
		$where = "id_article=$id_article";
	} else {
		$table = "spip_visites";
		$table_ref = "spip_referers";
		$where = "0=0";
	}
	
	$result = spip_query("SELECT UNIX_TIMESTAMP(date) AS date_unix FROM $table WHERE $where ORDER BY date LIMIT 1");

	while ($row = spip_fetch_array($result)) {
		$date_premier = $row['date_unix'];
	}

	$result=spip_query("SELECT UNIX_TIMESTAMP(date) AS date_unix, visites FROM $table WHERE $where AND date > DATE_SUB(NOW(),INTERVAL $aff_jours DAY) ORDER BY date");

	$date_debut = '';
	$log = array();
	while ($row = spip_fetch_array($result)) {
		$date = $row['date_unix'];
		if (!$date_debut) $date_debut = $date;
		$log[$date] = $row['visites'];
	}


	// S'il y a au moins cinq minutes de stats :-)
	if (count($log)>0) {
		// les visites du jour
		$date_today = max(array_keys($log));
		$visites_today = $log[$date_today];
		// sauf s'il n'y en a pas :
		if (time()-$date_today>3600*24) {
			$date_today = time();
			$visites_today=0;
		}
		
		// le nombre maximum
		$max = max($log);
		$nb_jours = floor(($date_today-$date_debut)/(3600*24));

		$maxgraph = maxgraph($max);
		$rapport = 200 / $maxgraph;

		if (count($log) < 420) $largeur = floor(450 / ($nb_jours+1));
		if ($largeur < 1) {
			$largeur = 1;
			$agreg = ceil(count($log) / 420);	
		} else {
			$agreg = 1;
		}
		if ($largeur > 50) $largeur = 50;

		debut_cadre_relief("statistiques-24.gif");
		
		
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
		
		$pour_article = $id_article ? "&id_article=$id_article" : '';
		
		if ($date_premier < $date_debut)
		  echo http_href_img(generer_url_ecrire("statistiques_visites","aff_jours=$aff_jours_plus$pour_article"),
				     'loupe-moins.gif',
				     "border='0' style='vertical-align:center;'",
				     _T('info_zoom'). '-'), "&nbsp;";
		if ( (($date_today - $date_debut) / (24*3600)) > 30)
		  echo http_href_img(generer_url_ecrire("statistiques_visites","aff_jours=$aff_jours_moins$pour_article"), 
				     'loupe-plus.gif',
				     "border='0' style='vertical-align:center;'",
				     _T('info_zoom'). '+'), "&nbsp;";
	
	
if ($GLOBALS['accepte_svg']) {
	echo "\n<div>";
	echo "<object data='", generer_url_ecrire('statistiques_svg',"id_article=$id_article&aff_jours=$aff_jours"), "' width='450' height='310' type='image/svg+xml'>";
	echo "<embed src='", generer_url_ecrire('statistiques_svg',"id_article=$id_article&aff_jours=$aff_jours"), "' width='450' height='310' type='image/svg+xml' />";
	echo "</object>";
	echo "\n</div>";
	$moyenne =  round($total_absolu / ((date("U")-$date_premier)/(3600*24)));
} else {
	
	echo "<table cellpadding=0 cellspacing=0 border=0><tr>",
	  "<td ".http_style_background("fond-stats.gif").">";
	echo "<table cellpadding=0 cellspacing=0 border=0><tr>";
	
	echo "<td bgcolor='black'>", http_img_rien(1,200), "</td>";
	
	$test_agreg = $decal = $jour_prec = $val_prec = $total_loc =0;

	// Presentation graphique (rq: on n'affiche pas le jour courant)
	foreach ($log as $key => $value) {
		# quand on atteint aujourd'hui, stop
		if ($key == $date_today) break; 

		$test_agreg ++;
		
		if ($test_agreg == $agreg) {	
				
			$test_agreg = 0;
			
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
						$jour = nom_jour($ce_jour).' '.affdate_jourcourt($ce_jour);
	
						reset($tab_moyenne);
						$moyenne = 0;
						while (list(,$val_tab) = each($tab_moyenne))
							$moyenne += $val_tab;
						$moyenne = $moyenne / count($tab_moyenne);
		
						$hauteur_moyenne = round(($moyenne) * $rapport) - 1;
						echo "<td valign='bottom' width=$largeur>";
						$difference = ($hauteur_moyenne) -1;
						$moyenne = round($moyenne,2); // Pour affichage harmonieux
						$tagtitle= attribut_html(supprimer_tags("$jour | "
						._T('info_visites')." | "
						._T('info_moyenne')." $moyenne"));
						if ($difference > 0) {	
						  echo http_img_rien($largeur,1, 'background-color:#333333;', $tagtitle);
						  echo http_img_rien($largeur, $hauteur_moyenne, '', $tagtitle);
						}
						echo 
						    http_img_rien($largeur,1,'background-color:black;', $tagtitle);
						echo "</td>";
					}
				}
	
				$ce_jour=date("Y-m-d", $key);
				$jour = nom_jour($ce_jour).' '.affdate_jourcourt($ce_jour);
	
				$total_loc = $total_loc + $value;
				reset($tab_moyenne);
	
				$moyenne = 0;
				while (list(,$val_tab) = each($tab_moyenne))
					$moyenne += $val_tab;
				$moyenne = $moyenne / count($tab_moyenne);
			
				$hauteur_moyenne = round($moyenne * $rapport) - 1;
				$hauteur = round($value * $rapport) - 1;
				$moyenne = round($moyenne,2); // Pour affichage harmonieux
				echo "<td valign='bottom' width=$largeur>";
	
				$tagtitle= attribut_html(supprimer_tags("$jour | "
				._T('info_visites')." ".$value));
	
				if ($hauteur > 0){
					if ($hauteur_moyenne > $hauteur) {
						$difference = ($hauteur_moyenne - $hauteur) -1;
						echo http_img_rien($largeur, 1,'background-color:#333333;',$tagtitle);
						echo http_img_rien($largeur, $difference, '', $tagtitle);
						echo http_img_rien($largeur,1, "background-color:$couleur_foncee;", $tagtitle);
						if (date("w",$key) == "0") // Dimanche en couleur foncee
						  echo http_img_rien($largeur, $hauteur, "background-color:$couleur_foncee;", $tagtitle);
						else
						  echo http_img_rien($largeur,$hauteur, "background-color:$couleur_claire;", $tagtitle);
					} else if ($hauteur_moyenne < $hauteur) {
						$difference = ($hauteur - $hauteur_moyenne) -1;
						echo http_img_rien($largeur,1,"background-color:$couleur_foncee;", $tagtitle);
						if (date("w",$key) == "0") // Dimanche en couleur foncee
							$couleur =  $couleur_foncee;
						else
							$couleur = $couleur_claire;
						echo http_img_rien($largeur, $difference, "background-color:$couleur;", $tagtitle);
						echo http_img_rien($largeur,1,"background-color:#333333;", $tagtitle);
						echo http_img_rien($largeur, $hauteur_moyenne, "background-color:$couleur;", $tagtitle);
					} else {
					  echo http_img_rien($largeur, 1, "background-color:$couleur_foncee;", $tagtitle);
						if (date("w",$key) == "0") // Dimanche en couleur foncee
						  echo http_img_rien($largeur, $hauteur, "background-color:$couleur_foncee;", $tagtitle);
						else
						  echo http_img_rien($largeur,$hauteur, "background-color:$couleur_claire;", $tagtitle);
					}
				}
				echo http_img_rien($largeur, 1, 'background-color:black;', $tagtitle);
				echo "</td>\n";
			
				$jour_prec = $key;
				$val_prec = $value;
			}
			}
	
			// Dernier jour
			$hauteur = round($visites_today * $rapport)	- 1;
			$total_absolu = $total_absolu + $visites_today;
			echo "<td valign='bottom' width=$largeur>";
			// prevision de visites jusqu'a minuit
			// basee sur la moyenne (site) ou popularite (article)
			if (! $id_article) $val_popularite = $moyenne;
			$prevision = (1 - (date("H")*60 + date("i"))/(24*60)) * $val_popularite;
			$hauteurprevision = ceil($prevision * $rapport);
			// Afficher la barre tout en haut
			if ($hauteur+$hauteurprevision>0)
				echo http_img_rien($largeur, 1, "background-color:$couleur_foncee;");
			// preparer le texte de survol (prevision)
			$tagtitle= attribut_html(supprimer_tags(_T('info_aujourdhui')." $visites_today &rarr; ".(round($prevision,0)+$visites_today)));
			// afficher la barre previsionnelle
			if ($hauteurprevision>0)
				echo http_img_rien($largeur, $hauteurprevision,'background-color:#eeeeee;', $tagtitle);
				// afficher la barre deja realisee
			if ($hauteur>0)
				echo http_img_rien($largeur, $hauteur, 'background-color:#cccccc;', $tagtitle);
			// et afficher la ligne de base
			echo http_img_rien($largeur, 1, 'background-color:black;');
			echo "</td>";


			echo "<td bgcolor='black'>",http_img_rien(1, 1),"</td>";
			echo "</tr></table>";
			echo "</td>",
			  "<td ".http_style_background("fond-stats.gif")."  valign='bottom'>", http_img_rien(3, 1, 'background-color:black;'),"</td>";
			echo "<td>", http_img_rien(5, 1),"</td>";
			echo "<td valign='top'><div style='font-family:Verdana,Arial,Sans,sans-serif; font-size:small;'>";
			echo "<table cellpadding=0 cellspacing=0 border=0>";
			echo "<tr><td height=15 valign='top'>";		
			echo "<font face='arial,helvetica,sans-serif' size=1><b>".round($maxgraph)."</b></font>";
			echo "</td></tr>";
			echo "<tr><td height=25 valign='middle'>";		
			echo "<font face='arial,helvetica,sans-serif' size=1 color='#999999'>".round(7*($maxgraph/8))."</font>";
			echo "</td></tr>";
			echo "<tr><td height=25 valign='middle'>";		
			echo "<font face='arial,helvetica,sans-serif' size=1>".round(3*($maxgraph/4))."</font>";
			echo "</td></tr>";
			echo "<tr><td height=25 valign='middle'>";		
			echo "<font face='arial,helvetica,sans-serif' size=1 color='#999999'>".round(5*($maxgraph/8))."</font>";
			echo "</td></tr>";
			echo "<tr><td height=25 valign='middle'>";		
			echo "<font face='arial,helvetica,sans-serif' size=1><b>".round($maxgraph/2)."</b></font>";
			echo "</td></tr>";
			echo "<tr><td height=25 valign='middle'>";		
			echo "<font face='arial,helvetica,sans-serif' size=1 color='#999999'>".round(3*($maxgraph/8))."</font>";
			echo "</td></tr>";
			echo "<tr><td height=25 valign='middle'>";		
			echo "<font face='arial,helvetica,sans-serif' size=1>".round($maxgraph/4)."</font>";
			echo "</td></tr>";
			echo "<tr><td height=25 valign='middle'>";		
			echo "<font face='arial,helvetica,sans-serif' size=1 color='#999999'>".round(1*($maxgraph/8))."</font>";
			echo "</td></tr>";
			echo "<tr><td height=10 valign='bottom'>";		
			echo "<font face='arial,helvetica,sans-serif' size=1><b>0</b></font>";
			echo "</td>";
			
			
			echo "</tr></table>";
			echo "</div></td>";
			echo "</tr></table>";
			
			echo "<div style='position: relative; height: 15px;'>";
			$gauche_prec = -50;
			for ($jour = $date_debut; $jour <= $date_today; $jour = $jour + (24*3600)) {
				$ce_jour = date("d", $jour);
				
				if ($ce_jour == "1") {
					$afficher = nom_mois(date("Y-m-d", $jour));
					if (date("m", $jour) == 1) $afficher = "<b>".annee(date("Y-m-d", $jour))."</b>";
					
				
					$gauche = ($jour - $date_debut) * $largeur / ((24*3600)*$agreg);
					
					if ($gauche - $gauche_prec >= 40 OR date("m", $jour) == 1) {									
						echo "<div class='arial0' style='border-$spip_lang_left: 1px solid black; padding-$spip_lang_left: 2px; padding-top: 3px; position: absolute; $spip_lang_left: ".$gauche."px; top: -1px;'>".$afficher."</div>";
						$gauche_prec = $gauche;
					}
				}
			}
			echo "</div>";
	}
		//}

		// cette ligne donne la moyenne depuis le debut
		// (desactive au profit de la moeynne "glissante")
		# $moyenne =  round($total_absolu / ((date("U")-$date_premier)/(3600*24)));

		echo "<font face='arial,helvetica,sans-serif' size=1>"._T('texte_statistiques_visites')."</font>";
		echo "<br /><table cellpadding=0 cellspacing=0 border=0 width='100%'><tr style='width:100%;'>";
		echo "<td valign='top' width='33%'><font face='Verdana,Arial,Sans,sans-serif'>";
		echo _T('info_maximum')." ".$max;
		echo "<br>"._T('info_moyenne')." ".round($moyenne);
		echo "</font></td>";
		echo "<td valign='top' width='33%'><font face='Verdana,Arial,Sans,sans-serif'>";
		echo '<a href="' . generer_url_ecrire("statistiques_referers","").'" title="'._T('titre_liens_entrants').'">'._T('info_aujourdhui').'</a> '.$visites_today;
		if ($val_prec > 0) echo '<br><a href="' . generer_url_ecrire("statistiques_referers","jour=veille").'"  title="'._T('titre_liens_entrants').'">'._T('info_hier').'</a> '.$val_prec;
		if ($id_article) echo "<br>"._T('info_popularite_5').' '.$val_popularite;

		echo "</font></td>";
		echo "<td valign='top' width='33%'><font face='Verdana,Arial,Sans,sans-serif'>";
		echo "<b>"._T('info_total')." ".$total_absolu."</b>";
		
		if ($id_article) {
			if ($classement[$id_article] > 0) {
				if ($classement[$id_article] == 1)
				      $ch = _T('info_classement_1', array('liste' => $liste));
				else
				      $ch = _T('info_classement_2', array('liste' => $liste));
				echo "<br>".$classement[$id_article].$ch;
			}
		} else {
			echo "<font size=1>";
			echo "<br>"._T('info_popularite_2')." ";
			echo ceil($GLOBALS['meta']['popularite_total']);
			echo "</font>";
		}
		echo "</font></td></tr></table>";	
	}		
	
	if (count($log) > 60) {
		echo "<br />";
		echo "<font face='verdana,arial,helvetica,sans-serif' size='2'><b>"._T('info_visites_par_mois')."</b></font>";

		echo "<div align='left'>";
		///////// Affichage par mois
		$result=spip_query("SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(date),'%Y-%m') AS date_unix, SUM(visites) AS total_visites  FROM $table WHERE $where AND date > DATE_SUB(NOW(),INTERVAL 2700 DAY) GROUP BY date_unix ORDER BY date");

		
		$i = 0;
		while ($row = spip_fetch_array($result)) {
			$date = $row['date_unix'];
			$visites = $row['total_visites'];
			$i++;
			$entrees["$date"] = $visites;
		}
		
		if (count($entrees)>0){
		
			$max = max($entrees);
			$maxgraph = maxgraph($max);
			$rapport = 200/$maxgraph;

			$largeur = floor(420 / (count($entrees)));
			if ($largeur < 1) $largeur = 1;
			if ($largeur > 50) $largeur = 50;
		}
		
		echo "<table cellpadding=0 cellspacing=0 border=0><tr>",
		  "<td ".http_style_background("fond-stats.gif").">";
		echo "<table cellpadding=0 cellspacing=0 border=0><tr>";
		echo "<td bgcolor='black'>", http_img_rien(1, 200),"</td>";
	
		// Presentation graphique
		$decal = 0;
		$tab_moyenne = "";
			
		while (list($key, $value) = each($entrees)) {
			
			$mois = affdate_mois_annee($key);

			if ($decal == 30) $decal = 0;
			$decal ++;
			$tab_moyenne[$decal] = $value;
			
			$total_loc = $total_loc + $value;
			reset($tab_moyenne);
	
			$moyenne = 0;
			while (list(,$val_tab) = each($tab_moyenne))
				$moyenne += $val_tab;
			$moyenne = $moyenne / count($tab_moyenne);
			
			$hauteur_moyenne = round($moyenne * $rapport) - 1;
			$hauteur = round($value * $rapport) - 1;
			echo "<td valign='bottom' width=$largeur>";

			$tagtitle= attribut_html(supprimer_tags("$mois | "
			._T('info_visites')." ".$value));

			if ($hauteur > 0){
				if ($hauteur_moyenne > $hauteur) {
					$difference = ($hauteur_moyenne - $hauteur) -1;
					echo http_img_rien($largeur, 1, 'background-color:#333333;');
					echo http_img_rien($largeur, $difference, '', $tagtitle);
					echo http_img_rien($largeur,1,"background-color:$couleur_foncee;");
					if (ereg("-01",$key)){ // janvier en couleur foncee
					  echo http_img_rien($largeur,$hauteur,"background-color:$couleur_foncee;", $tagtitle);
					} 
					else {
					  echo http_img_rien($largeur,$hauteur,"background-color:$couleur_claire;", $tagtitle);
					}
				}
				else if ($hauteur_moyenne < $hauteur) {
					$difference = ($hauteur - $hauteur_moyenne) -1;
					echo http_img_rien($largeur,1,"background-color:$couleur_foncee;", $tagtitle);
					if (ereg("-01",$key)){ // janvier en couleur foncee
						$couleur =  $couleur_foncee;
					} 
					else {
						$couleur = $couleur_claire;
					}
					echo http_img_rien($largeur,$difference, "background-color:$couleur;", $tagtitle);
					echo http_img_rien($largeur,1,'background-color:#333333;',$tagtitle);
					echo http_img_rien($largeur,$hauteur_moyenne,"background-color:$couleur;", $tagtitle);
				}
				else {
				  echo http_img_rien($largeur,1,"background-color:$couleur_foncee;", $tagtitle);
					if (ereg("-01",$key)){ // janvier en couleur foncee
					  echo http_img_rien($largeur, $hauteur, "background-color:$couleur_foncee;", $tagtitle);
					} 
					else {
					  echo http_img_rien($largeur,$hauteur, "background-color:$couleur_claire;", $tagtitle);
					}
				}
			}
			echo http_img_rien($largeur,1,'background-color:black;', $tagtitle);
			echo "</td>\n";
		}
		
		echo "<td bgcolor='black'>", http_img_rien(1, 1),"</td>";
		echo "</tr></table>";
		echo "</td>",
		  "<td ".http_style_background("fond-stats.gif")." valign='bottom'>", http_img_rien(3, 1, 'background-color:black;'),"</td>";
		echo "<td>", http_img_rien(5, 1),"</td>";
		echo "<td valign='top'><div style='font-family:Verdana,Arial,Sans,sans-serif; font-size:small;'>";
		echo "<table cellpadding=0 cellspacing=0 border=0>";
		echo "<tr><td height=15 valign='top'>";		
		echo "<font face='arial,helvetica,sans-serif' size=1><b>".round($maxgraph)."</b></font>";
		echo "</td></tr>";
		echo "<tr><td height=25 valign='middle'>";		
		echo "<font face='arial,helvetica,sans-serif' size=1 color='#999999'>".round(7*($maxgraph/8))."</font>";
		echo "</td></tr>";
		echo "<tr><td height=25 valign='middle'>";		
		echo "<font face='arial,helvetica,sans-serif' size=1>".round(3*($maxgraph/4))."</font>";
		echo "</td></tr>";
		echo "<tr><td height=25 valign='middle'>";		
		echo "<font face='arial,helvetica,sans-serif' size=1 color='#999999'>".round(5*($maxgraph/8))."</font>";
		echo "</td></tr>";
		echo "<tr><td height=25 valign='middle'>";		
		echo "<font face='arial,helvetica,sans-serif' size=1><b>".round($maxgraph/2)."</b></font>";
		echo "</td></tr>";
		echo "<tr><td height=25 valign='middle'>";		
		echo "<font face='arial,helvetica,sans-serif' size=1 color='#999999'>".round(3*($maxgraph/8))."</font>";
		echo "</td></tr>";
		echo "<tr><td height=25 valign='middle'>";		
		echo "<font face='arial,helvetica,sans-serif' size=1>".round($maxgraph/4)."</font>";
		echo "</td></tr>";
		echo "<tr><td height=25 valign='middle'>";		
		echo "<font face='arial,helvetica,sans-serif' size=1 color='#999999'>".round(1*($maxgraph/8))."</font>";
		echo "</td></tr>";
		echo "<tr><td height=10 valign='bottom'>";		
		echo "<font face='arial,helvetica,sans-serif' size=1><b>0</b></font>";
		echo "</td>";

		echo "</tr></table>";
		echo "</div></td></tr></table>";
		echo "</div>";
	}
	
	/////
		
	fin_cadre_relief();


	// Le bouton pour passer de svg a htm
	if ($GLOBALS['accepte_svg']) {
		$lien = 'non'; $alter = 'HTML';
	} else {
		$lien = 'oui'; $alter = 'SVG';
	}
	echo "\n<div align='".$GLOBALS['spip_lang_right']."' style='font-family:Verdana,Arial,Sans,sans-serif; font-size:x-small;'>
	<a href='".
	parametre_url(self(), 'var_svg', $lien)."'>$alter</a></div>\n";

}



//
// Affichage des referers
//

// nombre de referers a afficher
$limit = intval($limit);	//secu
if ($limit == 0)
	$limit = 100;

// afficher quels referers ?
$vis = "visites";
if ($origine) {
	$where = "visites_jour>0";
	$vis = "visites_jour";
	$table_ref = "spip_referers";
}


$result = spip_query("SELECT referer, $vis AS vis FROM $table_ref WHERE $where ORDER BY vis DESC LIMIT $limit");
	

echo "<br /><br /><br />";
gros_titre(_T("onglet_origine_visites"));

echo "<div style='font-family:Verdana,Arial,Sans,sans-serif; font-size:small;overflow:hidden;'><br />";
echo aff_referers ($result, $limit, generer_url_ecrire('statistiques_visites', ('limit=' . strval($limit+200))));
echo "<br /></div>";	

fin_page();
     }
?>
