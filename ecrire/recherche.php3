<?php

include ("inc.php3");
include_ecrire ("inc_mots.php3");

debut_page("R&eacute;sultats de la recherche");

debut_gauche();



debut_droite();

if ($rech) $recherche = '';

echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif'><B>R&eacute;sultats de la recherche :</B><BR>";
echo "<FONT SIZE=5 COLOR='$couleur_foncee'><B>".typo($recherche.$rech)."</B></FONT><BR>";
if ($recherche)
	echo "<FONT SIZE=2>(recherche sur les titres des articles et br&egrave;ves, ou sur leur num&eacute;ro)</FONT></FONT><P>";
else
	echo "<FONT SIZE=2>(recherche en texte int&eacute;gral)</FONT></FONT><P>";

$query_articles = "SELECT spip_articles.id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut FROM spip_articles WHERE";
$query_breves = "SELECT * FROM spip_breves WHERE ";

if (ereg("^[0-9]+$", $recherche)) {
	$query_articles .= " (id_article = $recherche) OR ";
	$query_breves .= " (id_breve = $recherche) OR ";
}

$rech2 = split("[[:space:]]+", addslashes($recherche));
if ($rech2)
	$where = " (titre LIKE '%".join("%' AND titre LIKE '%", $rech2)."%') ";
else
	$where = " 1=2";

$query_articles .= " $where ORDER BY maj DESC";
$query_breves .= " $where ORDER BY maj DESC LIMIT 0,10";


if ($rech) // texte integral
{
	include_ecrire ('inc_index.php3');
	$hash_recherche = requete_hash ($rech);
	$query_articles = requete_txt_integral('article', $hash_recherche);
	$query_breves = requete_txt_integral('breve', $hash_recherche);
	$query_rubriques = requete_txt_integral('rubrique', $hash_recherche);
//	$query_auteurs = requete_txt_integral('auteur', $hash_recherche);
	$query_sites = requete_txt_integral('syndic', $hash_recherche);
}

if ($query_articles)
	$nba = afficher_articles ("Articles trouv&eacute;s", $query_articles);
if ($query_breves)
	$nbb = afficher_breves ("Br&egrave;ves trouv&eacute;es", $query_breves);
if ($query_rubriques)
	$nbr = afficher_rubriques ("Rubriques trouv&eacute;es", $query_rubriques);
// if ($query_sites)
//	$nbt = afficher_auteurs ("Auteurs trouv&eacute;s", $query_auteurs);
if ($query_sites)
	$nbs = afficher_sites ("Sites trouv&eacute;s", $query_sites);

if (!$nba AND !$nbb AND !$nbr AND !$nbt AND !$nbs) {
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif'>Aucun r&eacute;sultat.</FONT><P>";
}

if (lire_meta('activer_moteur') == 'oui') {
	debut_cadre_relief();
	echo "<form action='recherche.php3' method='get'>";
	echo "<p>Recherche en texte int&eacute;gral :<br>";
	echo "<input type='text' name='rech' value='$recherche$rech'>";
	echo "</form>";
	fin_cadre_relief();
}

echo "<p>";

fin_page();

?>
