<?php

include ("inc.php3");
include ("inc_statistiques.php3");


if ($id_article = intval($id_article)){
	$query = "SELECT titre, visites, popularite FROM spip_articles WHERE statut='publie' AND id_article ='$id_article'";
	$result = spip_query($query);

	if ($row = spip_fetch_array($result)) {
		$titre = typo($row['titre']);
		$total_absolu = $row['visites'];
		$val_popularite = round($row['popularite']);
	}
} 
else {
	$query = "SELECT SUM(visites) AS total_absolu FROM spip_visites";
	$result = spip_query($query);

	if ($row = spip_fetch_array($result)) {
		$total_absolu = $row['total_absolu'];
	}
}


if ($titre) $pourarticle = " "._T('info_pour')." &laquo; $titre &raquo;";


debut_page(_T('titre_page_statistiques_referers'), "administration", "statistiques");
echo "<br><br><br>";
if ($aujourdhui == 'oui')
	gros_titre(_T('titre_liens_entrants'));
else
	gros_titre(_T('titre_liens_entrants_total'));

barre_onglets("statistiques", "referers");

debut_gauche();
debut_boite_info();
echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";
echo "<P align=left>"._T('info_gauche_statistiques_referers')."</P></FONT>";
fin_boite_info();

debut_droite();


if ($connect_statut != '0minirezo') {
	echo _T('avis_non_acces_page');
	fin_page();
	exit;
}

echo "<div align='right' style='width: 150;'>";
if ($aujourdhui == 'oui')
	icone_horizontale(_T('titre_liens_entrants_total'), "statistiques_referers.php3", "statistiques-24.gif");
else
	icone_horizontale(_T('titre_liens_entrants'), "statistiques_referers.php3?aujourdhui=oui", "statistiques-24.gif");
echo "</div>";


//
// Affichage des referers
//
if (lire_meta("activer_statistiques_ref") != "non"){
	// nombre de referers a afficher
	$limit = intval($limit);	//secu
	if ($limit == 0)
		$limit = 100;

	// afficher quels referers ?
	$vis = "visites";
	if ($aujourdhui == 'oui') {
		$where = "visites_jour>0";
		$vis = "visites_jour";
		$table_ref = "spip_referers";
	}
	else {
		$table_ref = "spip_referers";
		$where = "1";
	}

	$query = "SELECT referer, $vis AS vis FROM $table_ref WHERE $where ORDER BY $vis DESC";

	echo "<p><font face='Verdana,Arial,Helvetica,sans-serif' size=2>";
	echo aff_referers ($query, $limit);
	echo "</font></p>";	
}
echo "</font>";

fin_page();

?>
