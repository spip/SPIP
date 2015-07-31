<?php

include ("inc.php3");


debut_page("Statistiques", "administration", "statistiques");

echo "<br><br><br>";
gros_titre("Statistiques du site");
barre_onglets("statistiques", "recents");

debut_gauche();

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

$activer_statistiques_ref = lire_meta("activer_statistiques_ref");
if ($activer_statistiques_ref != "non"){
	echo "<font size=2 face='Verdana,Arial,Helvetica,sans-serif'>";
	if ($critere == "visites" OR !$critere) echo "[<b>par nombre de visites</b>] " ;
		else  echo "[<a href='statistiques_articles.php3'>par nombre de visites</a>] ";
	if ($critere == "popularite") echo "[<b>par popularit&eacute;</b>] ";
		else echo "[<a href='statistiques_articles.php3?critere=popularite'>par popularit&eacute;</a>] ";
	echo "</font><p>";
}

if ($critere == "referers"){
	echo propre("Les &laquo;acc&egrave;s directs sur la page&raquo; sont le nombre de visiteurs arriv&eacute;s directement {&agrave; l'int&eacute;rieur} du site depuis un lien ext&eacute;rieur, sans passer par la page d'accueil. Plus une page de votre site est r&eacute;f&eacute;renc&eacute;e sur des sites &agrave; fort traffic, plus le nombre d'arriv&eacute;es directes sur cette page sera important.")."<p>";
	afficher_articles("Les articles r&eacute;cents (3 mois) les plus r&eacute;f&eacute;renc&eacute;s",
"SELECT id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, popularite, id_rubrique, statut ".
"FROM spip_articles WHERE visites > 0 AND date>DATE_SUB('$date',INTERVAL 90 DAY) ORDER BY referers DESC LIMIT 0,100", true);
}
else if ($critere == "popularite"){
	echo propre("La &laquo;popularit&eacute;&raquo; est recalcul&eacute;e chaque jour d'apr&egrave;s ".
		"le nombre de liens entrants vers un article, multipli&eacute; par le nombre de visites. ".
		"Un article devient donc &laquo;populaire&raquo; lorsqu'il fait l'objet d'un r&eacute;f&eacute;rencement ".
		"sur d'autres sites et lorsqu'il est derni&egrave;rement tr&egrave;s visit&eacute;.")."<p>";
	afficher_articles("Les articles r&eacute;cents (3 mois) les plus populaires",
"SELECT id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, popularite, id_rubrique, statut ".
"FROM spip_articles WHERE popularite > 0 AND date>DATE_SUB('$date',INTERVAL 90 DAY) ORDER BY popularite DESC LIMIT 0,100", true);
	afficher_articles("Les articles les plus populaires depuis le d&eacute;but",
"SELECT id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, popularite, id_rubrique, statut ".
"FROM spip_articles WHERE popularite > 0 ORDER BY popularite DESC LIMIT 0,100", true);
}
else{
	afficher_articles("Les articles r&eacute;cents (3 mois) les plus visit&eacute;s",
"SELECT id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, popularite, id_rubrique, statut ".
"FROM spip_articles WHERE visites > 0 AND date>DATE_SUB('$date',INTERVAL 90 DAY) ORDER BY visites DESC LIMIT 0,100", true);
	afficher_articles("Les articles les plus visit&eacute;s depuis le d&eacute;but",
"SELECT id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, popularite, id_rubrique, statut ".
"FROM spip_articles WHERE visites > 0 ORDER BY visites DESC LIMIT 0,100", true);
}

fin_page();

?>

