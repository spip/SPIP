<?php

include ("inc.php3");


if ($connect_statut == '0minirezo' AND $supp_syndic) {
	$query="DELETE FROM spip_syndic WHERE id_syndic=$supp_syndic";
	$result=mysql_query($query);
}


debut_page();
debut_gauche();


debut_boite_info();
echo "<center><b>TOUS LES SITES SYNDIQU&Eacute;S</b></center>";
echo propre("<p>Cette page vous permet de v&eacute;rifier tous les sites que vous avez syndiqu&eacute;. Ces sites sont class&eacute;s par ordre de leur derni&egrave;re date de mise-&agrave;-jour. Surveillez en particulier les sites en fin de liste (mise-&agrave;-jour la plus ancienne), si le d&eacute;lais depuis la derni&egrave;re mise-&agrave;-jour est tr&egrave;s long, peut-&ecirc;tre le fichier {backend} correspondant ne fonctionne-t-il plus.");

echo aide ("rubsyn");

fin_boite_info();


debut_droite();

$request_syndic="SELECT sites.*, COUNT(*) AS nombre, MAX(articles.date) AS max_date FROM spip_syndic AS sites, spip_syndic_articles AS articles WHERE sites.id_syndic=articles.id_syndic GROUP BY sites.id_syndic ORDER BY max_date DESC";
$result_syndic=mysql_query($request_syndic);

while($row=mysql_fetch_array($result_syndic)){
	$id_syndic=$row["id_syndic"];
	$id_rubrique=$row["id_rubrique"];
	$nom_site=typo($row["nom_site"]);
	$url_site=$row["url_site"];
	$url_syndic=$row["url_syndic"];
	$description=propre($row["description"]);
	$nombre=$row[nombre];
	$max_date=$row[max_date];
	
	debut_cadre_relief();
	echo "<B><A HREF='$url_site'>$nom_site</A></B>";
	echo "<br><font size='1'>NOMBRE D'ARTICLES SYNDIQU&Eacute;S :</font> <b>$nombre</b>";
	echo "<br><font size='1'>DERNI&Egrave;RE MISE &Agrave; JOUR :</font> <b>".affdate($max_date)."</b>";

	$result_rub=mysql_query("SELECT titre FROM spip_rubriques WHERE id_rubrique=$id_rubrique");
	while($row=mysql_fetch_array($result_rub)){
		$titre_rubrique=typo($row["titre"]);
	}
	echo "<br><font size='1'>DANS LA RUBRIQUE :</font> <a href='naviguer.php3?coll=$id_rubrique'>$titre_rubrique</a>";
	echo "<p>$description";
	echo "<p align='right'><FONT SIZE=2 FACE='arial,helvetica'>[<A HREF='syndic_tous.php3?supp_syndic=$id_syndic'>supprimer</A>]</FONT>";

	fin_cadre_relief();
	echo "<p>";
}

fin_page();

?>

