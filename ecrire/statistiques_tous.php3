<?php

include ("inc.php3");


debut_page("Statistiques", "administration", "statistiques");


echo "<br><br><br>";
gros_titre("Statistiques du site");
barre_onglets("statistiques", "tous");

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





echo "<font size=2 face='verdana,arial,helvetica'><b>";
echo "[<a href='statistiques_tous.php3'>par nombre de visites</a>] ";
echo "[<a href='statistiques_tous.php3?critere=referers'>par arriv&eacute;es directes sur la page</a>] ";
echo "[<a href='statistiques_tous.php3?critere=popularite'>par popularit&eacute;</a>] ";
echo "</b></font><p>";

if ($critere == "referers"){
	afficher_articles("Les articles les plus visit&eacute;s depuis le d&eacute;but",
"SELECT id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
"FROM spip_articles WHERE visites > 0 ORDER BY referers DESC LIMIT 0,100", true);
}
else if ($critere == "popularite"){

	echo propre("La Çpopularit&eacute;È est calcul&eacute;e d'apr&egrave;s le nombre d'arriv&eacute;es directes sur un article, multipli&eacute; par le nombre de visites. Un article devient donc &laquo;populaire&raquo; lorsqu'il fait l'objet d'un r&eacute;f&eacute;rencement sur d'autres sites et lorsqu'il est tr&egrave;s visit&eacute;.")."<p>";
	afficher_articles("Les articles les plus visit&eacute;s depuis le d&eacute;but",
"SELECT id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
"FROM spip_articles WHERE visites > 0 ORDER BY popularite DESC LIMIT 0,100", true);
}
else{
	afficher_articles("Les articles les plus visit&eacute;s depuis le d&eacute;but",
"SELECT id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
"FROM spip_articles WHERE visites > 0 ORDER BY visites DESC LIMIT 0,100", true);
}





fin_page();

?>

