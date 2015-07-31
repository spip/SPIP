<?php

include ("inc.php3");
include_ecrire ("inc_mots.php3");

debut_page("R&eacute;sultats de la recherche $recherche");

debut_gauche();



debut_droite();

echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif'><B>R&eacute;sultats de la recherche :</B><BR>";
echo "<FONT SIZE=5 COLOR='$couleur_foncee'><B>".typo($recherche)."</B></FONT><p>";

$query_articles = "SELECT spip_articles.id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut FROM spip_articles WHERE";
$query_breves = "SELECT * FROM spip_breves WHERE ";
$query_rubriques = "SELECT * FROM spip_rubriques WHERE ";
$query_sites = "SELECT * FROM spip_syndic WHERE ";

if (ereg("^[0-9]+$", $recherche)) {
	$query_articles .= " (id_article = $recherche) OR ";
	$query_breves .= " (id_breve = $recherche) OR ";
	$query_rubriques .= " (id_rubrique = $recherche) OR ";
	$query_sites .= " (id_syndic = $recherche) OR ";
}

$rech2 = split("[[:space:]]+", addslashes($recherche));
if ($rech2)
	$where = " (titre LIKE '%".join("%' AND titre LIKE '%", $rech2)."%') ";
else
	$where = " 1=2";

$query_articles .= " $where ORDER BY maj DESC";
$query_breves .= " $where ORDER BY maj DESC LIMIT 0,10";
$query_rubriques .= " $where ORDER BY maj DESC LIMIT 0,10";

$query_sites .= " $where ORDER BY maj DESC LIMIT 0,10";
$query_sites  = ereg_replace("titre LIKE", "nom_site LIKE", $query_sites);

if (lire_meta('activer_moteur') == 'oui') {	// texte integral
	include_ecrire ('inc_index.php3');
	$hash_recherche = requete_hash ($recherche);
	$query_articles_int = requete_txt_integral('article', $hash_recherche);
	$query_breves_int = requete_txt_integral('breve', $hash_recherche);
	$query_rubriques_int = requete_txt_integral('rubrique', $hash_recherche);
	$query_sites_int = requete_txt_integral('syndic', $hash_recherche);
	$query_auteurs_int = requete_txt_integral('auteur', $hash_recherche);
}

if ($query_articles)
	$nba = afficher_articles ("Articles trouv&eacute;s", $query_articles);
if ($query_articles_int) {
	if ($nba) {
		$doublons = join($nba, ",");
		$query_articles_int = ereg_replace ("WHERE", "WHERE objet.id_article NOT IN ($doublons) AND", $query_articles_int);
	}
	$nba1 = afficher_articles ("Articles trouv&eacute;s (dans le texte)", $query_articles_int);
}

if ($query_breves)
	$nbb = afficher_breves ("Br&egrave;ves trouv&eacute;es", $query_breves);
if ($query_breves_int) {
	if ($nbb) {
		$doublons = join($nbb, ",");
		$query_breves_int = ereg_replace ("WHERE", "WHERE objet.id_breve NOT IN ($doublons) AND", $query_breves_int);
	}
	$nbb1 = afficher_breves ("Br&egrave;ves trouv&eacute;es (dans le texte)", $query_breves_int);
}

if ($query_rubriques)
	$nbr = afficher_rubriques ("Rubriques trouv&eacute;es", $query_rubriques);
if ($query_rubriques_int) {
	if ($nbr) {
		$doublons = join($nbr, ",");
		$query_rubriques_int = ereg_replace ("WHERE", "WHERE objet.id_rubrique NOT IN ($doublons) AND", $query_rubriques_int);
	}
	$nbr1 = afficher_rubriques ("Rubriques trouv&eacute;es (dans le texte)", $query_rubriques_int);
}

if ($query_auteurs_int AND $connect_statut == '0minirezo')
	$nbt = afficher_auteurs ("Auteurs trouv&eacute;s", $query_auteurs_int);

if ($query_sites)
	$nbs = afficher_sites ("Sites trouv&eacute;s", $query_sites);
if ($query_sites_int) {
	if ($nbs) {
		$doublons = join($nbs, ",");	
		$query_sites_int = ereg_replace ("WHERE", "WHERE objet.id_syndic NOT IN ($doublons) AND", $query_sites_int);
	}
	$nbs1 = afficher_sites ("Sites trouv&eacute;s (dans le texte)", $query_sites_int);
}

if (!$nba AND !$nba1 AND !$nbb AND !$nbb1 AND !$nbr AND !$nbr1 AND !$nbt AND !$nbs AND !$nbs1) {
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif'>Aucun r&eacute;sultat.</FONT><P>";
}

echo "<p>";

fin_page();

?>
