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


if ($connect_statut == "0minirezo") $req_where = " AND articles.statut IN ('prepa','prop','publie')"; 
else $req_where = " AND articles.statut IN ('prop','publie')"; 

echo "<p>";

echo "<div class='arial11'><ul>";
echo "<p>";

if (!$uniq_auteur AND $id_secteur < 1) echo "<li><b>"._T('info_tout_site')."</b>";
else echo "<li><a href='suivi_versions.php'>"._T('info_tout_site')."</a>";

echo "<p>";

$nom_auteur = $GLOBALS['auteur_session']['nom'];

if ($uniq_auteur) echo "<li><b>$nom_auteur</b>";
else echo "<li><a href='suivi_versions.php?uniq_auteur=true'>$nom_auteur</a>";

echo "<p>";

$query = "SELECT * FROM spip_rubriques WHERE id_parent = 0 ORDER BY titre";
$result = spip_query($query);

while ($row = mysql_fetch_array($result)) {
	$id_rubrique = $row['id_rubrique'];
	$titre = propre($row['titre']);
	
	$query_rub = "
SELECT versions.*, articles.statut, articles.titre
FROM spip_versions AS versions, spip_articles AS articles 
WHERE versions.id_article = articles.id_article AND versions.id_version > 1 AND articles.id_secteur=$id_rubrique$req_where LIMIT 0,1";
	$result_rub = spip_query($query_rub);
	
	if ($id_rubrique == $id_secteur)  echo "<li><b>$titre</b>";
	else if (spip_num_rows($result_rub) > 0) echo "<li><a href='suivi_versions.php?id_secteur=$id_rubrique'>$titre</a>";
}

if ((lire_meta('multi_rubriques') == 'oui') OR (lire_meta('multi_articles') == 'oui')) {
	echo "<p>";
	$langues = explode(',', lire_meta('langues_multilingue'));
	
	foreach ($langues as $lang) {
		$titre = traduire_nom_langue($lang);
	
		$query_lang = "
SELECT versions.*
FROM spip_versions AS versions, spip_articles AS articles 
WHERE versions.id_article = articles.id_article AND versions.id_version > 1 AND articles.lang='$lang' $req_where LIMIT 0,1";
		$result_lang = spip_query($query_lang);
		
		if ($lang == $lang_choisie)  echo "<li><b>$titre</b>";
		else if (spip_num_rows($result_lang) > 0) echo "<li><a href='suivi_versions.php?lang_choisie=$lang'>$titre</a>";
	}
}


echo "</ul></div>";


//////////////////////////////////////////////////////
// Affichage de la colonne de droite
//


debut_droite();

afficher_suivi_versions ($debut, $id_secteur, $uniq_auteur, $lang_choisie);

fin_page();

?>
