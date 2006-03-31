<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');
include_spip('inc/suivi_versions');

function exec_suivi_revisions_dist()
{
  global
    $connect_id_auteur,
    $connect_statut,
    $debut,
    $id_auteur,
    $id_secteur,
    $lang_choisie,
    $uniq_auteur;

$debut = intval($debut);
$id_auteur = ($id_auteur == $connect_id_auteur) ? $id_auteur : false;

debut_page(_T("icone_suivi_revisions"));


//////////////////////////////////////////////////////
// Affichage de la colonne de gauche
//

debut_gauche();


if ($connect_statut == "0minirezo") $req_where = " AND articles.statut IN ('prepa','prop','publie')"; 
else $req_where = " AND articles.statut IN ('prop','publie')"; 

echo "<p>";


debut_cadre_relief();

echo "<div class='arial11'><ul>";
echo "<p>";

if (!$id_auteur AND $id_secteur < 1) echo "<li><b>"._T('info_tout_site')."</b>";
else echo "<li><a href='" . generer_url_ecrire("suivi_revisions") . "'>"._T('info_tout_site')."</a>";

echo "<p>";

$nom_auteur = $GLOBALS['auteur_session']['nom'];

if ($id_auteur) echo "<li><b>$nom_auteur</b>";
else echo "<li><a href='" . generer_url_ecrire("suivi_revisions","id_auteur=$connect_id_auteur") . "'>$nom_auteur</a>";

echo "<p>";

$query = "SELECT * FROM spip_rubriques WHERE id_parent = 0 ORDER BY 0+titre, titre";
$result = spip_query($query);

while ($row = spip_fetch_array($result)) {
	$id_rubrique = $row['id_rubrique'];
	$titre = propre($row['titre']);
	
	$query_rub = "
SELECT versions.*, articles.statut, articles.titre
FROM spip_versions AS versions, spip_articles AS articles 
WHERE versions.id_article = articles.id_article AND versions.id_version > 1 AND articles.id_secteur=$id_rubrique$req_where LIMIT 1";
	$result_rub = spip_query($query_rub);
	
	if ($id_rubrique == $id_secteur)  echo "<li><b>$titre</b>";
	else if (spip_num_rows($result_rub) > 0) echo "<li><a href='" . generer_url_ecrire("suivi_revisions","id_secteur=$id_rubrique") . "'>$titre</a>";
}

if (($GLOBALS['meta']['multi_rubriques'] == 'oui') OR ($GLOBALS['meta']['multi_articles'] == 'oui')) {
	echo "<p>";
	$langues = explode(',', $GLOBALS['meta']['langues_multilingue']);
	
	foreach ($langues as $lang) {
		$titre = traduire_nom_langue($lang);
	
		$query_lang = "
SELECT versions.*
FROM spip_versions AS versions, spip_articles AS articles 
WHERE versions.id_article = articles.id_article AND versions.id_version > 1 AND articles.lang='$lang' $req_where LIMIT 1";
		$result_lang = spip_query($query_lang);
		
		if ($lang == $lang_choisie)  echo "<li><b>$titre</b>";
		else if (spip_num_rows($result_lang) > 0) echo "<li><a href='" . generer_url_ecrire("suivi_revisions","lang_choisie=$lang") . "'>$titre</a>";
	}
}


echo "</ul></div>\n";

// lien vers le rss

$op = 'revisions';
$args = array(
	'id_secteur' => $id_secteur,
	'id_auteur' => $id_auteur,
	'lang_choisie' => $lang_choisie
);
echo "<div style='text-align: "
	. $GLOBALS['spip_lang_right']
	. ";'>"
	. bouton_spip_rss($op, $args)
	."</div>";


fin_cadre_relief();



//////////////////////////////////////////////////////
// Affichage de la colonne de droite
//


debut_droite();

 afficher_suivi_versions ($debut, $id_secteur, $id_auteur, $lang_choisie);

fin_page();
}

?>
