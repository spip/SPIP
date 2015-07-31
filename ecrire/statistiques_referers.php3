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
$query = "SELECT referer, visites_jour FROM spip_referers ".
	"WHERE visites_jour > 0 ".
	"GROUP BY referer_md5 ORDER BY visites_jour DESC, referer LIMIT 0,800";
$result = spip_query($query);

while ($row = @spip_fetch_array($result)) {
	$referer = $row['referer'];
	$count = $row['visites_jour'];

	echo "\n<li>";
	if ($count > 5) echo "<font color='red'>$count "._T('info_visites')." </font>";
	else if ($count > 1) echo "$count "._T('info_visites')." ";
	else echo "<font color='#999999'>$count "._T('info_visite')." </font>";

	echo stats_show_keywords($referer, $referer);
	echo "</li>\n";
}

if (spip_num_rows($result) == 800)
	echo "<li>...</li>";

echo "</ul>";
echo "</font>";

fin_page();

?>

