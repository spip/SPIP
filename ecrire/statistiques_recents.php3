<?php

include ("inc.php3");


debut_page("Statistiques", "administration", "statistiques");

echo "<br><br><br>";
gros_titre("Statistiques du site");
barre_onglets("statistiques", "recents");

debut_gauche();


debut_boite_info();

echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";
echo "<P align=left>".propre("Le syst&egrave;me de statistiques int&eacute;gr&eacute; &agrave; SPIP est volontairement rudimentaire (afin de ne pas alourdir la base de donn&eacute;es et de ne pas tracer les visiteurs du site). De ce fait, les nombres de visites indiqu&eacute;s ici doivent &ecirc;tre pond&eacute;r&eacute;s: ils servent uniquement d'{indication} sur la popularit&eacute; {relative} des articles et des rubriques. ");


echo "</FONT>";

fin_boite_info();





debut_droite();

if ($connect_statut != '0minirezo') {
	echo "Vous n'avez pas acc&egrave;s &agrave; cette page.";
	fin_page();
	exit;
}




//////


$query="SELECT MAX(date) AS cnt FROM spip_articles WHERE statut='publie'";
$result=spip_query($query);

if ($row = mysql_fetch_array($result)) {
	$date = $row['cnt'];
}

afficher_articles("Les articles r&eacute;cents (3 mois) les plus visit&eacute;s",
"SELECT id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
"FROM spip_articles WHERE visites > 0 AND date>DATE_SUB('$date',INTERVAL 90 DAY) ORDER BY visites DESC LIMIT 0,100", true);


fin_page();

?>

