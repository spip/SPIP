<?

include ("inc.php3");
include_local ("inc_mots.php3");

debut_page("R&eacute;sultats de la recherche");

debut_gauche();



debut_droite();

echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif'><B>R&eacute;sultats de la recherche :</B><BR>";
echo "<FONT SIZE=5 COLOR='$couleur_foncee'><B>".typo($recherche)."</B></FONT><BR>";
echo "<FONT SIZE=2>(recherche sur les titres des articles et br&egrave;ves, ou sur leur num&eacute;ro)</FONT></FONT><P>";

$query_articles = "SELECT spip_articles.id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut FROM spip_articles WHERE";
$query_breves = "SELECT * FROM spip_breves WHERE ";

if (ereg("^[0-9]+$", $recherche)) {
	$query_articles .= " (id_article = $recherche) OR ";
	$query_breves .= " (id_breve = $recherche) OR ";
}

$recherche = split("[[:space:]]+", addslashes($recherche));
if ($recherche) {
	$where = " (titre LIKE '%".join("%' AND titre LIKE '%", $recherche)."%') ";
}
else {
	$where = " 1=2";
}

$query_articles .= " $where ORDER BY maj DESC";
$query_breves .= " $where ORDER BY maj DESC LIMIT 0,10";

$nb_articles = afficher_articles("Articles trouv&eacute;s", $query_articles);
$nb_breves = afficher_breves("Br&egrave;ves trouv&eacute;es", $query_breves);

if (!$nb_articles AND !$nb_breves) {
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif'>Aucun r&eacute;sultat.</FONT><P>";
}

echo "<p>";

fin_page();

?>
