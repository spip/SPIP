<?php

include ("inc.php3");
include ("inc_statistiques.php3");


debut_page("Statistiques", "administration", "statistiques");


echo "<br><br><br>";
gros_titre("Les liens entrants du jour");
barre_onglets("statistiques", "referers");

debut_gauche();


debut_boite_info();

echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";
echo "<P align=left>".propre("Cette page pr&eacute;sente la liste des {referers}, c'est-&agrave;-dire des sites contenant des liens menant vers votre propre site, uniquement pour aujourd'hui: en effet, cette liste est remise &agrave; z&eacute;ro toutes les 24 heures.");


echo "</FONT>";

fin_boite_info();





debut_droite();

if ($connect_statut != '0minirezo') {
	echo "Vous n'avez pas acc&egrave;s &agrave; cette page.";
	fin_page();
	exit;
}


//////

echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=2>";


echo "<ul>";
// Recuperer les donnees du log	
$query = "SELECT referer, COUNT(DISTINCT ip) AS count FROM spip_referers_temp ".
	"GROUP BY referer_md5 ORDER BY count DESC";
$result = spip_query($query);

while ($row = @mysql_fetch_array($result)) {
	$referer = $row['referer'];
	$count = $row['count'];
	
	echo "\n<li>";
	if ($count > 5) echo "<font color='red'>$count visites : </font>";
	else if ($count > 1) echo "$count visites : ";
	else echo "<font color='#999999'>$count visite : </font>";

	echo stats_show_keywords($referer, $referer);
	echo "</li>\n";
}

echo "</ul>";
echo "</font>";

fin_page();

?>

