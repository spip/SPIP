<?php

include ("inc.php3");
include ("inc_statistiques.php3");

/*$query = "SELECT * FROM spip_visites_temp WHERE date <= DATE_SUB(NOW(),INTERVAL 24 HOUR)";
$result = spip_query($query);

if (mysql_num_rows($result) > 0) {
	ecrire_meta("date_stats_process", "$date");
	ecrire_metas();
	calculer_visites();
}*/



debut_page("Statistiques", "administration", "statistiques");

echo "<br><br><br>";
gros_titre("&Eacute;volution des visites<html>".aide("confstat")."</html>");
barre_onglets("statistiques", "evolution");

if ($id_article){
	$query = "SELECT titre, visites FROM spip_articles WHERE statut='publie' AND id_article ='$id_article'";
	$result = spip_query($query);

	if ($row = mysql_fetch_array($result)) {
		$titre = typo($row['titre']);
		$total_absolu = $row['visites'];
		gros_titre($titre);
	}
} 
else {
	$query = "SELECT SUM(visites) AS total_absolu FROM spip_visites";
	$result = spip_query($query);

	if ($row = mysql_fetch_array($result)) {
		$total_absolu = $row['total_absolu'];
	}
}


debut_gauche();





	echo "<p>";
	echo "<div class='iconeoff' style='padding: 5px;'>";
	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=2>";
	echo typo("Afficher les visites pour:");
	echo "<ul>";
	if ($id_article>0) {
		echo "<li><b><a href='statistiques_visites.php3'>Tout le site</a></b>";
	} else {
		echo "<li><b>Tout le site</b>";
	}

	echo "<font size=1>";
	$query = "SELECT id_article, titre FROM spip_articles WHERE statut='publie' AND visites > 0 ORDER BY date DESC LIMIT 0,20";
	$result = spip_query($query);

	if (mysql_num_rows($result) > 0) {
		echo "<br><br>";
		while ($row = mysql_fetch_array($result)) {
			$titre = typo($row['titre']);
			$l_article = $row['id_article'];
			if ($l_article == $id_article){
				echo "\n<li><b>$titre</b></li>";
			} else {
				echo "\n<li><a href='statistiques_visites.php3?id_article=$l_article'>$titre</a></li>";
			}
		}
	}
	else {
		echo "\n<i>(aucun article visit&eacute;)</i>";
	}
	echo "</font>";
	echo "</ul>";
	echo "</font>";
	echo "</div>";


	creer_colonne_droite();
	
	// Par popularite
	$query = "SELECT id_article, titre, popularite FROM spip_articles WHERE statut='publie' AND popularite > 0 ORDER BY popularite DESC LIMIT 0,10";
	$result = spip_query($query);

	if (mysql_num_rows($result) > 0) {
		echo "<p>";
		echo "<div class='iconeoff' style='padding: 5px;'>";
		echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=2>";
		echo typo("Afficher les visites pour <b>les articles les plus populaires</b>:");
		echo "<ul>";
		echo "<font size=1>";

		while ($row = mysql_fetch_array($result)) {
			$titre = typo($row['titre']);
			$l_article = $row['id_article'];
			$popularite = ceil(min(100,100 * $row['popularite'] / max(1,lire_meta('popularite_max'))));
			if ($l_article == $id_article){
				echo "\n<li><b>$titre</b></li>";
			} else {
				echo "\n<li><a href='statistiques_visites.php3?id_article=$l_article'>$titre ($popularite%)</a></li>";
			}
		}
		echo "</font>";
		echo "</ul>";
		echo "</font>";
		echo "</div>";
	}


	$query = "SELECT date FROM spip_visites_articles ORDER BY date DESC LIMIT 0,1";
	$result = spip_query($query);
	if ($row = mysql_fetch_array($result)) {
		$hier = $row['date'];
		
		// Par visites hier
		$query = "SELECT articles.id_article AS id_article, articles.titre AS titre, lien.visites AS visiteurs FROM spip_articles AS articles, spip_visites_articles AS lien WHERE lien.date='$hier' AND lien.visites > 0 AND articles.statut='publie' AND articles.id_article=lien.id_article ORDER BY visiteurs DESC LIMIT 0,10";
		$result = spip_query($query);
	
		if (mysql_num_rows($result) > 0) {
			echo "<p>";
			echo "<div class='iconeoff' style='padding: 5px;'>";
			echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=2>";
			echo typo("Afficher les visites pour <b>les articles les plus visit&eacute;s hier</b>:");
			echo "<ul>";
			echo "<font size=1>";
	
			while ($row = mysql_fetch_array($result)) {
				$titre = typo($row['titre']);
				$l_article = $row['id_article'];
				$visiteurs = $row['visiteurs'];
				if ($l_article == $id_article){
					echo "\n<li><b>$titre</b></li>";
				} else {
					echo "\n<li><a href='statistiques_visites.php3?id_article=$l_article'>$titre ($visiteurs)</a></li>";
				}
			}
			echo "</font>";
			echo "</ul>";
			echo "</font>";
			echo "</div>";
		}

	}

	


//
// Afficher les boutons de creation d'article et de breve
//
if ($connect_statut == '0minirezo') {
	debut_raccourcis();
	
	if ($id_article > 0){
	icone_horizontale("Retour &agrave; l'article", "articles.php3?id_article=$id_article", "article-24.gif","rien.gif");
	}
	icone_horizontale("Suivi des forums", "controle_forum.php3", "suivi-forum-24.gif", "rien.gif");
	
	fin_raccourcis();
}



debut_droite();

if ($connect_statut != '0minirezo') {
	echo "Vous n'avez pas acc&egrave;s &agrave; cette page.";
	fin_page();
	exit;
}




//////

if ($id_article) {
	$table = "spip_visites_articles";
	$table_ref = "spip_referers_articles";
	$where = "id_article=$id_article";
}
else {
	$table = "spip_visites";
	$table_ref = "spip_referers";
	$where = "1";
}



$query="SELECT UNIX_TIMESTAMP(date) AS date_unix, visites FROM $table ".
	"WHERE $where AND date > DATE_SUB(NOW(),INTERVAL 420 DAY) ORDER BY date";
$result=spip_query($query);

while ($row = mysql_fetch_array($result)) {
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
}
else {
	$query = "SELECT COUNT(DISTINCT ip) AS visites FROM spip_visites_temp";
	$result = spip_query($query);
}
if ($row = @mysql_fetch_array($result)) {
	$visites_today = $row['visites'];
}
else
	$visites_today = 0;

if (count($log)>0){

	$max = max(max($log),$visites_today);
	$date_today = time();

	$nb_jours = floor(($date_today-$date_debut)/(3600*24));

	
	$maxgraph = substr(ceil(substr($max,0,2) / 10)."000000000000", 0, strlen($max));
	
	if ($maxgraph < 10) $maxgraph = 10;
	if (1.1 * $maxgraph < $max) $maxgraph.="0";	

	if (0.8*$maxgraph > $max) $maxgraph = 0.8 * $maxgraph;

	$rapport = 200 / $maxgraph;

	if (count($log) < 420) $largeur = floor(420 / ($nb_jours+1));
	if ($largeur < 1) $largeur = 1;

	debut_cadre_relief("statistiques-24.gif");
	echo "<table cellpadding=0 cellspacing=0 border=0><tr><td background='img_pack/fond-stats.gif'>";
	echo "<table cellpadding=0 cellspacing=0 border=0><tr>";

		echo "<td bgcolor='black'><img src='img_pack/rien.gif' width=1 height=200></td>";

	// Presentation graphique
	while (list($key, $value) = each($log)) {
		$n++;
		
		//inserer des jours vides si pas d'entrees	
		if ($jour_prec > 0) {
			$ecart = floor(($key-$jour_prec)/(3600*24)-1);
	
			for ($i=0; $i < $ecart; $i++){
				$moyenne = $total_loc / $n;
				$hauteur_moyenne = round(($moyenne) * $rapport) - 1;
				echo "<td valign='bottom' width=$largeur>";
				$difference = ($hauteur_moyenne) -1;
				if ($difference > 0) {	
					echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:#333333;'>";
					echo "<img src='img_pack/rien.gif' width=$largeur height=$hauteur_moyenne>";
				}
				echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:black;'>";
				echo "</td>";
				$n++;
			}
		}
		$total_loc = $total_loc + $value;
		$moyenne = $total_loc / $n;
		$hauteur_moyenne = round($moyenne * $rapport) - 1;
		$hauteur = round($value * $rapport)	- 1;
		echo "<td valign='bottom' width=$largeur>";
		
		if ($hauteur > 0){
			if ($hauteur_moyenne > $hauteur) {
				$difference = ($hauteur_moyenne - $hauteur) -1;
				echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:#333333;'>";
				echo "<img src='img_pack/rien.gif' width=$largeur height=$difference>";
				echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:$couleur_foncee;'>";
				if (date("w",$key) == "0"){ // Dimanche en couleur foncee
					echo "<img src='img_pack/rien.gif' width=$largeur height=$hauteur style='background-color:$couleur_foncee;'>";
				} 
				else {
					echo "<img src='img_pack/rien.gif' width=$largeur height=$hauteur style='background-color:$couleur_claire;'>";
				}
			}
			else if ($hauteur_moyenne < $hauteur) {
				$difference = ($hauteur - $hauteur_moyenne) -1;
				echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:$couleur_foncee;'>";
				if (date("w",$key) == "0"){ // Dimanche en couleur foncee
					$couleur =  $couleur_foncee;
				} 
				else {
					$couleur = $couleur_claire;
				}
				echo "<img src='img_pack/rien.gif' width=$largeur height=$difference style='background-color:$couleur;'>";
				echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:#333333;'>";
				echo "<img src='img_pack/rien.gif' width=$largeur height=$hauteur_moyenne style='background-color:$couleur;'>";
			}
			else {
				echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:$couleur_foncee;'>";
				if (date("w",$key) == "0"){ // Dimanche en couleur foncee
					echo "<img src='img_pack/rien.gif' width=$largeur height=$hauteur style='background-color:$couleur_foncee;'>";
				} 
				else {
					echo "<img src='img_pack/rien.gif' width=$largeur height=$hauteur style='background-color:$couleur_claire;'>";
				}
			}
		}
		echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:black;'>";
		echo "</td>\n";
		
		$jour_prec = $key;
		$val_prec = $value;
	}
		// Dernier jour
		$hauteur = round($visites_today * $rapport)	- 1;
		$total_absolu = $total_absolu + $visites_today;
		echo "<td valign='bottom' width=$largeur>";
		if ($hauteur > 0){
			echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:$couleur_foncee;'>";
			echo "<img src='img_pack/rien.gif' width=$largeur height=$hauteur style='background-color:#eeeeee;'>";
		}
		echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:black;'>";
		echo "</td>";
	
	
	echo "<td bgcolor='black'><img src='img_pack/rien.gif' width=1 height=1></td>";
	echo "</tr></table>";
	echo "</td>";
	echo "<td background='img_pack/fond-stats.gif' valign='bottom'><img src='img_pack/rien.gif' style='background-color:black;' width=3 height=1></td>";
	echo "<td><img src='img_pack/rien.gif' width=5 height=1></td>";
	echo "<td valign='top'><font face='Verdana,Arial,Helvetica,sans-serif' size=2>";
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
		echo "<font face='arial,helvetica,sans-serif' size=1>(barres fonc&eacute;es :  dimanche / courbe fonc&eacute;e : &eacute;volution de la moyenne)</font>";
		
		echo "<p><table cellpadding=0 cellspacing=0 border=0 width='100%'><tr width='100%'>";
		echo "<td valign='top' width='33%'><font face='Verdana,Arial,Helvetica,sans-serif'>";
		echo "maximum&nbsp;: $max";
		echo "<br>moyenne&nbsp;: ".round($moyenne);
		echo "</td>";
		echo "<td valign='top' width='33%'><font face='Verdana,Arial,Helvetica,sans-serif'>";
		echo "aujourd'hui&nbsp;: $visites_today";
		if ($val_prec > 0) echo "<br>hier&nbsp;: $val_prec";
		echo "</td>";
		echo "<td valign='top' width='33%'><font face='Verdana,Arial,Helvetica,sans-serif'>";
		echo "<b>total : $total_absolu</b>";
		echo "</td></tr></table>";		
	
	fin_cadre_relief();

}

$activer_statistiques_ref = lire_meta("activer_statistiques_ref");
if ($activer_statistiques_ref == "oui"){
	// Affichage des referers

	$query = "SELECT * FROM $table_ref WHERE $where ORDER BY visites DESC LIMIT 0,100";
	$result = spip_query($query);
	
	echo "<p><font face='Verdana,Arial,Helvetica,sans-serif' size=2>";
	while ($row = mysql_fetch_array($result)) {
		$referer = $row['referer'];
		$visites = $row['visites'];
	
		echo "\n<li>";
	
	
		if ($visites > 5) echo "<font color='red'>$visites liens : </font>";
		else if ($visites > 1) echo "$visites liens : ";
		else echo "<font color='#999999'>$visites lien : </font>";
	
		echo stats_show_keywords($referer, $referer);
	}
}
echo "</font>";

fin_page();

?>
