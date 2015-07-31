<?php

include("inc.php");

include_ecrire("inc_suivi_revisions.php");
include_spip("ecrire.php");
include_spip("revisions.php");
include_spip("diff.php");




debut_page(_T("icone_suivi_revisions"));


//////////////////////////////////////////////////////
// Affichage de la colonne de gauche
//

debut_gauche();
if (!$debut) $debut = 0;

if (!$uniq_auteur) $uniq_auteur = false ;
else $uniq_auteur = true;


if ($connect_statut == "0minirezo") $req_where = " AND spip_articles.statut IN ('prepa','prop','publie')"; 
else $req_where = " AND spip_articles.statut IN ('prop','publie')"; 


echo "<div class='arial1'>";

if (!$uniq_auteur AND $id_secteur < 1) echo "<li><b>tout afficher</b>";
else echo "<li><a href='suivi_versions.php'>tout afficher</a>";

if ($uniq_auteur) echo "<li><b>mes modifications</b>";
else echo "<li><a href='suivi_versions.php?uniq_auteur=true'>mes modifications</a>";

$query = "SELECT * FROM spip_rubriques WHERE id_parent = 0 ORDER BY titre";
$result = spip_query($query);

while ($row = mysql_fetch_array($result)) {
	$id_rubrique = $row['id_rubrique'];
	$titre = propre($row['titre']);
	
	$query_rub = "SELECT spip_versions.*, spip_articles.statut, spip_articles.titre FROM spip_versions, spip_articles WHERE spip_versions.id_article = spip_articles.id_article AND spip_versions.id_version > 1 AND spip_articles.id_secteur=$id_rubrique$req_where LIMIT 0,1";
	$result_rub = spip_query($query_rub);
	
	if ($id_rubrique == $id_secteur)  echo "<li><b>$titre</b>";
	else if (spip_num_rows($result_rub) > 0) echo "<li><a href='suivi_versions.php?id_secteur=$id_rubrique'>$titre</a>";
}
echo "</div>";


//////////////////////////////////////////////////////
// Affichage de la colonne de droite
//


debut_droite();

afficher_suivi_versions ($debut, $id_secteur, $uniq_auteur);

fin_page();

?>
