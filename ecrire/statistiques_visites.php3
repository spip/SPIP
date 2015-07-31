<?php

include ("inc.php3");
include_ecrire("inc_statistiques.php3");


if ($id_article = intval($id_article)){
	$query = "SELECT titre, visites, popularite FROM spip_articles WHERE statut='publie' AND id_article ='$id_article'";
	$result = spip_query($query);

	if ($row = spip_fetch_array($result)) {
		$titre = typo($row['titre']);
		$total_absolu = $row['visites'];
		$val_popularite = round($row['popularite']);
	}
} 
else {
	$query = "SELECT SUM(visites) AS total_absolu FROM spip_visites";
	$result = spip_query($query);

	if ($row = spip_fetch_array($result)) {
		$total_absolu = $row['total_absolu'];
	}
}


if ($titre) $pourarticle = " "._T('info_pour')." &laquo; $titre &raquo;";

if ($origine) {
	debut_page(_T('titre_page_statistiques_referers'), "suivi", "statistiques");
	echo "<br><br>";
	gros_titre(_T('titre_liens_entrants'));
	barre_onglets("statistiques", "referers");

	debut_gauche();
	debut_boite_info();
	echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2>";
	echo "<P align=left>"._T('info_gauche_statistiques_referers')."</P></FONT>";
	fin_boite_info();
	
	debut_droite();

}
else {
	debut_page(_T('titre_page_statistiques_visites').$pourarticle, "suivi", "statistiques");
	echo "<br><br>";
	gros_titre(_T('titre_evolution_visite')."<html>".aide("confstat")."</html>");
//	barre_onglets("statistiques", "evolution");
	if ($titre) gros_titre($titre);

	debut_gauche();

	echo "<p>";

	echo "<div class='iconeoff' style='padding: 5px;'>";
	echo "<font face='Verdana,Arial,Sans,sans-serif' size=2>";
	echo typo(_T('info_afficher_visites'));
	echo "<ul>";
	if ($id_article>0) {
		echo "<li><b><a href='statistiques_visites.php3'>"._T('info_tout_site')."</a></b>";
	} else {
		echo "<li><b>"._T('titre_page_articles_tous')."</b>";
	}

		echo "</ul>";
		echo "</font>";
		echo "</div>";

	
	// Par popularite
	$articles_recents[] = "0";
	$query = "SELECT id_article FROM spip_articles WHERE statut='publie' AND popularite > 0 ORDER BY date DESC LIMIT 0,10";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) {
		$articles_recents[] = $row['id_article'];
	}
	$articles_recents = join($articles_recents, ",");
		
	// Par popularite
	$query = "SELECT id_article, titre, popularite, visites FROM spip_articles WHERE statut='publie' AND popularite > 0 ORDER BY popularite DESC";
	$result = spip_query($query);

	$nombre_articles = spip_num_rows($result);
	if ($nombre_articles > 0) {
		echo "<p>";
		echo "<div class='iconeoff' style='padding: 5px;'>";
		echo "<font face='Verdana,Arial,Sans,sans-serif' size=2>";
		echo typo(_T('info_visites_plus_populaires'));
		echo "<ol style='padding-left:25 px;'>";
		echo "<font size=1 color='#666666'>";
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
					echo "\n<li value='$liste'><a href='statistiques_visites.php3?id_article=$l_article' title='"._T('info_popularite', array('popularite' => $popularite, 'visites' => $visites))."'>$titre</a>";
				}
			}
		}
		$articles_vus = join($articles_vus, ",");
			
		// Par popularite
		$query_suite = "SELECT id_article, titre, popularite, visites FROM spip_articles WHERE statut='publie' AND id_article IN ($articles_recents) AND id_article NOT IN ($articles_vus) ORDER BY popularite DESC";
		$result_suite = spip_query($query_suite);
		
		if (spip_num_rows($result_suite) > 0) {
			echo "<br><br>[...]<br><br>";
			while ($row = spip_fetch_array($result_suite)) {
				$titre = typo($row['titre']);
				$l_article = $row['id_article'];
				$visites = $row['visites'];
				$popularite = round($row['popularite']);
				$numero = $classement[$l_article];
				
				if ($l_article == $id_article){
					echo "\n<li value='$numero'><b>$titre</b></li>";
				} else {
					echo "\n<li value='$numero'><a href='statistiques_visites.php3?id_article=$l_article' title='"._T('info_popularite_3', array('popularite' => $popularite, 'visites' => $visites))."'>$titre</a></li>";
				}
			}
		}
			
		echo "</ol>";

		echo "<b>"._T('info_comment_lire_tableau')."</b><br>"._T('texte_comment_lire_tableau');

		echo "</font>";
		echo "</font>";
		echo "</div>";
	}


	// Par visites depuis le debut
	$query = "SELECT id_article, titre, popularite, visites FROM spip_articles WHERE statut='publie' AND popularite > 0 ORDER BY visites DESC LIMIT 0,30";
	$result = spip_query($query);
		
	if (spip_num_rows($result) > 0) {
		creer_colonne_droite();

		echo "<p></p><div class='iconeoff' style='padding: 5px;'>";
		echo "<font face='Verdana,Arial,Sans,sans-serif' size=2>";
		echo typo(_T('info_affichier_visites_articles_plus_visites'));
		echo "<ol style='padding-left:25 px;'>";
		echo "<font size=1 color='#666666'>";

		while ($row = spip_fetch_array($result)) {
			$titre = typo($row['titre']);
			$l_article = $row['id_article'];
			$visites = $row['visites'];
			$popularite = round($row['popularite']);
				$numero = $classement[$l_article];
				
				if ($l_article == $id_article){
					echo "\n<li value='$numero'><b>$titre</b></li>";
				} else {
					echo "\n<li value='$numero'><a href='statistiques_visites.php3?id_article=$l_article' title='"._T('info_popularite_4', array('popularite' => $popularite, 'visites' => $visites))."'>$titre</a></li>";
				}
		}
		echo "</ol>";
		echo "</font>";
	
		echo "</font>";
		echo "</div>";
	}


	//
	// Afficher les boutons de creation d'article et de breve
	//
	if ($connect_statut == '0minirezo') {
		if ($id_article > 0) {
			debut_raccourcis();
			icone_horizontale(_T('icone_retour_article'), "articles.php3?id_article=$id_article", "article-24.gif","rien.gif");
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

if (!$aff_jours) $aff_jours = 105;

if (!$origine) {




	if ($id_article) {
		$table = "spip_visites_articles";
		$table_ref = "spip_referers_articles";
		$where = "id_article=$id_article";
	} else {
		$table = "spip_visites";
		$table_ref = "spip_referers";
		$where = "1";
	}
	
	$query="SELECT UNIX_TIMESTAMP(date) AS date_unix FROM $table ".
		"WHERE $where ORDER BY date LIMIT 0,1";
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

	// Visites du jour
	if ($id_article) {
		$query = "SELECT COUNT(DISTINCT ip) AS visites FROM spip_visites_temp WHERE type = 'article' AND id_objet = $id_article";
		$result = spip_query($query);
	} else {
		$query = "SELECT COUNT(DISTINCT ip) AS visites FROM spip_visites_temp";
		$result = spip_query($query);
	}
	if ($row = @spip_fetch_array($result))
		$visites_today = $row['visites'];
	else
		$visites_today = 0;

	if (count($log)>0) {
		$max = max(max($log),$visites_today);
		$date_today = time();
		$nb_jours = floor(($date_today-$date_debut)/(3600*24));
		
		$maxgraph = substr(ceil(substr($max,0,2) / 10)."000000000000", 0, strlen($max));
	
		if ($maxgraph < 10) $maxgraph = 10;
		if (1.1 * $maxgraph < $max) $maxgraph.="0";	
		if (0.8*$maxgraph > $max) $maxgraph = 0.8 * $maxgraph;
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
			$aff_jour_moins = 210;
		}
		
		if ($largeur_abs < 1) {
			$aff_jours_plus = 420 * ((1/$largeur_abs) + 1);
			$aff_jours_moins = 420 * ((1/$largeur_abs) - 1);
		}
		
//		$aff_jours_plus = round($aff_jours * 1.5);		
//		$aff_jours_moins = round($aff_jours / 1.5);
		
		
		
		if ($id_article) $pour_article="&id_article=$id_article";
		
		if ($date_premier < $date_debut) echo "<a href='statistiques_visites.php3?aff_jours=$aff_jours_plus$pour_article'><img src='img_pack/loupe-moins.gif' border='0' valign='center'></a>&nbsp;";
		if ( (($date_today - $date_debut) / (24*3600)) > 30)  echo "<a href='statistiques_visites.php3?aff_jours=$aff_jours_moins$pour_article'><img src='img_pack/loupe-plus.gif' border='0' valign='center'></a>&nbsp;";
	
		/*
		if ($spip_svg_plugin == 'oui') {
			echo "<div>";
			echo "<object data='statistiques_svg.php3?id_article=$id_article&aff_jours=$aff_jours' width='450' height='310' type='image/svg+xml'>";
			echo "<embed src='statistiques_svg.php3?id_article=$id_article&aff_jours=$aff_jours'  width='450' height='310' type='image/svg+xml' />";
			echo "</object>";
			echo "</div>";
		} 
		else {
		*/
			echo "<table cellpadding=0 cellspacing=0 border=0><tr><td background='img_pack/fond-stats.gif'>";
			echo "<table cellpadding=0 cellspacing=0 border=0><tr>";
	
			echo "<td bgcolor='black'><img src='img_pack/rien.gif' width=1 height=200></td>";
	
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
		
						$hauteur_moyenne = round(($moyenne) * $rapport) - 1;
						echo "<td valign='bottom' width=$largeur>";
						$difference = ($hauteur_moyenne) -1;
						$moyenne = round($moyenne,2); // Pour affichage harmonieux
						$tagtitle='"'.attribut_html(supprimer_tags("$jour | "
						._T('info_visites')." | "
						._T('info_moyenne')." $moyenne")).'"';
						if ($difference > 0) {	
							echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:#333333;' title=$tagtitle>";
							echo "<img src='img_pack/rien.gif' width=$largeur height=$hauteur_moyenne title=$tagtitle>";
						}
						echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:black;' title=$tagtitle>";
						echo "</td>";
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
				echo "<td valign='bottom' width=$largeur>";
	
				$tagtitle='"'.attribut_html(supprimer_tags("$jour | "
				._T('info_visites')." ".$value)).'"';
	
				if ($hauteur > 0){
					if ($hauteur_moyenne > $hauteur) {
						$difference = ($hauteur_moyenne - $hauteur) -1;
						echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:#333333;' title=$tagtitle>";
						echo "<img src='img_pack/rien.gif' width=$largeur height=$difference title=$tagtitle>";
						echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:$couleur_foncee;' title=$tagtitle>";
						if (date("w",$key) == "0") // Dimanche en couleur foncee
							echo "<img src='img_pack/rien.gif' width=$largeur height=$hauteur style='background-color:$couleur_foncee;' title=$tagtitle>";
						else
							echo "<img src='img_pack/rien.gif' width=$largeur height=$hauteur style='background-color:$couleur_claire;' title=$tagtitle>";
					} else if ($hauteur_moyenne < $hauteur) {
						$difference = ($hauteur - $hauteur_moyenne) -1;
						echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:$couleur_foncee;' title=$tagtitle>";
						if (date("w",$key) == "0") // Dimanche en couleur foncee
							$couleur =  $couleur_foncee;
						else
							$couleur = $couleur_claire;
						echo "<img src='img_pack/rien.gif' width=$largeur height='$difference' style='background-color:$couleur;' title=$tagtitle>";
						echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:#333333;' title=$tagtitle>";
						echo "<img src='img_pack/rien.gif' width=$largeur height=$hauteur_moyenne style='background-color:$couleur;' title=$tagtitle>";
					} else {
						echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:$couleur_foncee;' title=$tagtitle>";
						if (date("w",$key) == "0") // Dimanche en couleur foncee
							echo "<img src='img_pack/rien.gif' width=$largeur height=$hauteur style='background-color:$couleur_foncee;' title=$tagtitle>";
						else
							echo "<img src='img_pack/rien.gif' width=$largeur height=$hauteur style='background-color:$couleur_claire;' title=$tagtitle>";
					}
				}
				echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:black;' title=$tagtitle>";
				echo "</td>\n";
			
				$jour_prec = $key;
				$val_prec = $value;
			}
			}
	
			// Dernier jour
			$hauteur = round($visites_today * $rapport)	- 1;
			$total_absolu = $total_absolu + $visites_today;
			echo "<td valign='bottom' width=$largeur>";
			if ($hauteur > 0){
				echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:$couleur_foncee;'>";
	
				// prevision de visites jusqu'a minuit
				// basee sur la moyenne (site) ou popularite (article)
				if (! $id_article) $val_popularite = $moyenne;
				$prevision = (1 - (date("H")*60 - date("i"))/(24*60)) * $val_popularite;
				$hauteurprevision = ceil($prevision * $rapport);
				$prevision = round($prevision,0)+$visites_today; // Pour affichage harmonieux
				$tagtitle='"'.attribut_html(supprimer_tags(_T('info_aujourdhui')." $visites_today &rarr; $prevision")).'"';
				echo "<img src='img_pack/rien.gif' width=$largeur height=$hauteurprevision style='background-color:#eeeeee;' title=$tagtitle>";
	
				echo "<img src='img_pack/rien.gif' width=$largeur height=$hauteur style='background-color:#cccccc;' title=$tagtitle>";
			}
			echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:black;'>";
			echo "</td>";
		
			echo "<td bgcolor='black'><img src='img_pack/rien.gif' width=1 height=1></td>";
			echo "</tr></table>";
			echo "</td>";
			echo "<td background='img_pack/fond-stats.gif' valign='bottom'><img src='img_pack/rien.gif' style='background-color:black;' width=3 height=1></td>";
			echo "<td><img src='img_pack/rien.gif' width=5 height=1></td>";
			echo "<td valign='top'><font face='Verdana,Arial,Sans,sans-serif' size=2>";
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
			
			
			echo "</table>";
			echo "</font></td>";
			echo "</td></tr></table>";
			
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
			
		//}
				
				
		$moyenne =  round($total_absolu / ((date("U")-$date_premier)/(3600*24)));

		echo "<font face='arial,helvetica,sans-serif' size=1>"._T('texte_statistiques_visites')."</font>";
		echo "<p><table cellpadding=0 cellspacing=0 border=0 width='100%'><tr width='100%'>";
		echo "<td valign='top' width='33%'><font face='Verdana,Arial,Sans,sans-serif'>";
		echo _T('info_maximum')." ".$max;
		echo "<br>"._T('info_moyenne')." ".round($moyenne);
		echo "</td>";
		echo "<td valign='top' width='33%'><font face='Verdana,Arial,Sans,sans-serif'>";
		echo _T('info_aujourdhui').' '.$visites_today;
		if ($val_prec > 0) echo "<br>"._T('info_hier').' '.$val_prec;
		if ($id_article) echo "<br>"._T('info_popularite_5').' '.$val_popularite;

		echo "</td>";
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
			echo ceil(lire_meta('popularite_total'));
			echo "</font>";
		}
		echo "</td></tr></table>";	
	}		
	
	if (count($log) > 60) {
		echo "<p>";
		echo "<font face='verdana,arial,helvetica,sans-serif' size='2'><b>"._T('info_visites_par_mois')."</b></font>";

		echo "<div align='left'>";
		///////// Affichage par mois
		$query="SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(date),'%Y-%m') AS date_unix, SUM(visites) AS total_visites  FROM $table ".
			"WHERE $where AND date > DATE_SUB(NOW(),INTERVAL 2700 DAY) GROUP BY date_unix ORDER BY date";
		$result=spip_query($query);
		
		$i = 0;
		while ($row = spip_fetch_array($result)) {
			$date = $row['date_unix'];
			$visites = $row['total_visites'];
			$i++;
			$entrees["$date"] = $visites;
		}
		
		if (count($entrees)>0){
		
			$max = max($entrees);
			$maxgraph = substr(ceil(substr($max,0,2) / 10)."000000000000", 0, strlen($max));
			
			if ($maxgraph < 10) $maxgraph = 10;
			if (1.1 * $maxgraph < $max) $maxgraph.="0";	
			if (0.8*$maxgraph > $max) $maxgraph = 0.8 * $maxgraph;
			$rapport = 200 / $maxgraph;
	
			$largeur = floor(420 / (count($entrees)));
			if ($largeur < 1) $largeur = 1;
			if ($largeur > 50) $largeur = 50;
		}
		
		echo "<table cellpadding=0 cellspacing=0 border=0><tr><td background='img_pack/fond-stats.gif'>";
		echo "<table cellpadding=0 cellspacing=0 border=0><tr>";
		echo "<td bgcolor='black'><img src='img_pack/rien.gif' width=1 height=200></td>";
	
		// Presentation graphique
		$n = 0;
		$decal = 0;
		$tab_moyenne = "";
			
		while (list($key, $value) = each($entrees)) {
			$n++;
			
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

			$tagtitle='"'.attribut_html(supprimer_tags("$mois | "
			._T('info_visites')." ".$value)).'"';

			if ($hauteur > 0){
				if ($hauteur_moyenne > $hauteur) {
					$difference = ($hauteur_moyenne - $hauteur) -1;
					echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:#333333;'>";
					echo "<img src='img_pack/rien.gif' width=$largeur height='$difference' title=$tagtitle>";
					echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:$couleur_foncee;'>";
					if (ereg("-01",$key)){ // janvier en couleur foncee
						echo "<img src='img_pack/rien.gif' width=$largeur height='$hauteur' style='background-color:$couleur_foncee;' title=$tagtitle>";
					} 
					else {
						echo "<img src='img_pack/rien.gif' width=$largeur height='$hauteur' style='background-color:$couleur_claire;' title=$tagtitle>";
					}
				}
				else if ($hauteur_moyenne < $hauteur) {
					$difference = ($hauteur - $hauteur_moyenne) -1;
					echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:$couleur_foncee;' title=$tagtitle>";
					if (ereg("-01",$key)){ // janvier en couleur foncee
						$couleur =  $couleur_foncee;
					} 
					else {
						$couleur = $couleur_claire;
					}
					echo "<img src='img_pack/rien.gif' width=$largeur height='$difference' style='background-color:$couleur;' title=$tagtitle>";
					echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:#333333;' title=$tagtitle>";
					echo "<img src='img_pack/rien.gif' width=$largeur height=$hauteur_moyenne style='background-color:$couleur;' title=$tagtitle>";
				}
				else {
					echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:$couleur_foncee;' title=$tagtitle>";
					if (ereg("-01",$key)){ // janvier en couleur foncee
						echo "<img src='img_pack/rien.gif' width=$largeur height=$hauteur style='background-color:$couleur_foncee;' title=$tagtitle>";
					} 
					else {
						echo "<img src='img_pack/rien.gif' width=$largeur height=$hauteur style='background-color:$couleur_claire;' title=$tagtitle>";
					}
				}
			}
			echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:black;' title=$tagtitle>";
			echo "</td>\n";
			
			$jour_prec = $key;
			$val_prec = $value;
		}
		
		echo "<td bgcolor='black'><img src='img_pack/rien.gif' width=1 height=1></td>";
		echo "</tr></table>";
		echo "</td>";
		echo "<td background='img_pack/fond-stats.gif' valign='bottom'><img src='img_pack/rien.gif' style='background-color:black;' width=3 height=1></td>";
		echo "<td><img src='img_pack/rien.gif' width=5 height=1></td>";
		echo "<td valign='top'><font face='Verdana,Arial,Sans,sans-serif' size=2>";
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
		echo "</td></tr></table>";
		echo "</div>";
	}
	
	/////
		
	fin_cadre_relief();

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

$query = "SELECT referer, $vis AS vis FROM $table_ref WHERE $where ORDER BY $vis DESC";


echo "<br><br><br>";
gros_titre(_T("onglet_origine_visites"));

echo "<p><font face='Verdana,Arial,Sans,sans-serif' size=2>";
echo aff_referers ($query, $limit);
echo "</font></p>";	

echo "</font>";

fin_page();

?>
