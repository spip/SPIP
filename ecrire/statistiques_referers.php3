<?php

include ("inc.php3");
include ("inc_statistiques.php3");


debut_page(_T('titre_page_statistiques_referers'), "administration", "statistiques");


echo "<br><br><br>";
gros_titre(_T('titre_liens_entrants'));
barre_onglets("statistiques", "referers");

debut_gauche();


debut_boite_info();

echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";
echo "<P align=left>"._T('info_gauche_statistiques_referers');


echo "</FONT>";

fin_boite_info();





debut_droite();

if ($connect_statut != '0minirezo') {
	echo _T('avis_non_acces_page');
	fin_page();
	exit;
}


//////

echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=2>";


echo "<ul>";
// Recuperer les donnees du log
$date = date("Y-m-d");

// $query = "SELECT referer, visites_jour FROM spip_referers WHERE visites_jour > 0 GROUP BY referer_md5 ORDER BY visites_jour DESC, referer LIMIT 0,800";
$query = "SELECT domaine, SUM(visites_jour) AS visites_jour, COUNT(*) AS compte FROM spip_referers WHERE (visites_jour > 0) AND (domaine != '') GROUP BY domaine ORDER BY visites_jour DESC LIMIT 0,200";
$result = spip_query($query);

while ($row = @spip_fetch_array($result)) {
	$domaine = $row['domaine'];
	$count = $row['visites_jour'];

	echo "\n<li> ";
	if ($count > 5) echo "<font color='red'>$count "._T('info_visites')." </font>";
	else if ($count > 1) echo "$count "._T('info_visites')." ";
	else echo "<font color='#999999'>$count "._T('info_visite')." </font>";

	$refs = spip_query("SELECT referer, visites_jour FROM spip_referers WHERE domaine = '$domaine' AND (visites_jour > 0) ORDER BY visites_jour DESC LIMIT 0,30");

	if (spip_num_rows($refs) > 1) {
		echo "<b>$domaine</b>";
		echo "<br />";
		while ($row_ref = spip_fetch_array($refs)) {
			$referer = $row_ref['referer'];
			echo stats_show_keywords($referer, $referer)."<br />";
		}
	}
	else {
		while ($row_ref = spip_fetch_array($refs)) {
			$referer = $row_ref['referer'];
			echo stats_show_keywords($referer, $referer)."<br />";
		}
	}

	echo "</li><br />\n";
}

if (spip_num_rows($result) == 800)
	echo "<li>...</li>";

echo "</ul>";
echo "</font>";

fin_page();

?>

