<?php

include ("inc.php3");
include ("inc_statistiques.php3");


debut_page("Statistiques", "administration", "statistiques");

echo "<br><br><br>";
gros_titre("&Eacute;volution des visites");
barre_onglets("statistiques", "evolution");

if ($id_article){
	$query = "SELECT titre, visites FROM spip_articles WHERE statut='publie' AND id_article ='$id_article'";
	$result = spip_query($query);

	if ($row = mysql_fetch_array($result)) {
		$titre = propre($row['titre']);
		$total_absolu = propre($row['visites']);
		gros_titre($titre);
	}
} 
else {
	$query = "SELECT SUM(visites) AS total_absolu FROM spip_visites WHERE type='tout'";
	$result = spip_query($query);

	if ($row = mysql_fetch_array($result)) {
		$total_absolu = $row['total_absolu'];
	}
}


debut_gauche();


	echo "<p>";
	echo "<div class='iconeoff' style='padding: 5px;'>";
	echo "<font face='verdana,arial,helvetica,sans-serif' size=2>";
	echo propre("Afficher les visites pour:");
	echo "<ul>";
	if ($id_article>0) {
		echo "<li><b><a href='statistiques_visites.php3'>Tout le site</a></b>";
	}

	echo "<font size=1>";
	$query = "SELECT id_article, titre FROM spip_articles WHERE statut='publie' AND id_article !='$id_article' AND visites > 0 ORDER BY date DESC LIMIT 0,20";
	$result = spip_query($query);

	while ($row = mysql_fetch_array($result)) {
		$titre = propre($row['titre']);
		$l_article = $row['id_article'];
		echo "\n<li><a href='statistiques_visites.php3?id_article=$l_article'>$titre</a>";
	}
	echo "</font>";
	echo "</ul>";
	echo "</font>";
	echo "</div>";

debut_droite();

if ($connect_statut != '0minirezo') {
	echo "Vous n'avez pas acc&egrave;s &agrave; cette page.";
	fin_page();
	exit;
}




//////

if ($id_article) $page = "article$id_article";
else $page = "tout";


$query="SELECT UNIX_TIMESTAMP(date) AS date_unix, visites FROM spip_visites WHERE type = '$page' AND date > DATE_SUB(NOW(),INTERVAL 365 DAY) ORDER BY date";
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
	$query = "SELECT * FROM spip_visites_temp WHERE type = 'article$id_article' GROUP BY ip";
	$result = spip_query($query);
}
else {
	$query = "SELECT * FROM spip_visites_temp GROUP BY ip";
	$result = spip_query($query);
}
$visites_today = mysql_num_rows($result);

if (count($log)>0){
	$max = max(max($log),$visites_today);
	$date_today = time();
	
	$nb_jours = floor(($date_today-$date_debut)/(3600*24));

	
	if ($max>10) $maxgraph = substr(ceil(substr($max,0,2) / 10)."000000000000", 0, strlen($max));
	else $maxgraph = 10;
	
	$rapport = 200 / $maxgraph;
	
	if (count($log) < 365) $largeur = floor(365 / ($nb_jours+1));
	if ($largeur < 1) $largeur = 1;
	
	debut_cadre_relief();
	echo "<table cellpadding=0 cellspacing=0 border=0><tr>";
	
		echo "<td bgcolor='black'><img src='img_pack/rien.gif' width=1 height=1></td>";

	// Presentation graphique
	while (list($key, $value) = each($log)) {
		
		//inserer des jours vides si pas d'entrees	
		if ($jour_prec > 0) {
			$ecart = floor(($key-$jour_prec)/(3600*24)-1);
	
			for ($i=0; $i < $ecart; $i++){
				echo "<td valign='bottom' width=$largeur>";
				echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:black;'>";
				echo "</td>";
			}

		}
		$hauteur = round($value * $rapport)	- 1;
		echo "<td valign='bottom' width=$largeur>";
		if ($hauteur > 0){
			echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:$couleur_foncee;'>";
			echo "<img src='img_pack/rien.gif' width=$largeur height=$hauteur style='background-color:$couleur_claire;'>";
		}
		echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:black;'>";
		echo "</td>";
		
		$jour_prec = $key;
	}
		// Dernier jour
		$hauteur = round($visites_today * $rapport)	- 1;
		$total_absolu = $total_absolu + $visites_today;
		echo "<td valign='bottom' width=$largeur>";
		if ($hauteur > 0){
			echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:$couleur_foncee;'>";
			echo "<img src='img_pack/rien.gif' width=$largeur height=$hauteur style='background-color:#e4e4e4;'>";
		}
		echo "<img src='img_pack/rien.gif' width=$largeur height=1 style='background-color:black;'>";
		echo "</td>";
	
	
	echo "<td bgcolor='black'><img src='img_pack/rien.gif' width=1 height=1></td>";
	echo "<td><img src='img_pack/rien.gif' width=5 height=1></td>";
	echo "<td valign='top'><font face='verdana,arial,helvetica,sans-serif' size=2>";
		echo "max&nbsp;: $max";
		echo "<br>aujourd'hui&nbsp;: $visites_today";
		echo "<br>total : $total_absolu";
	echo "</font></td>";
	echo "</tr></table>";
	
	fin_cadre_relief();

}


// Affichage des referers

$query = "SELECT * FROM spip_visites_referers WHERE type = '$page' ORDER BY visites DESC LIMIT 0,100";
$result = spip_query($query);

echo "<p><font face='verdana,arial,helvetica,sans-serif' size=2>";
while ($row = mysql_fetch_array($result)) {
	$referer = $row['referer'];
	$visites = $row['visites'];

	echo "\n<li>";


	if ($visites > 5) echo "<font color='red'>$visites liens : </font>";
	else if ($visites > 1) echo "$visites liens : ";
	else echo "<font color='#999999'>$visites lien : </font>";

	echo stats_show_keywords($referer, $referer);
}
echo "</font>";

fin_page();

?>

