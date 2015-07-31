<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/



include ("inc.php3");
include_ecrire("inc_presentation.php3");
include_ecrire("inc_texte.php3");
include_ecrire("inc_urls.php3");
include_ecrire("inc_rubriques.php3");
include_ecrire("inc_statistiques.php3");


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


debut_page(_T('titre_page_statistiques_referers'), "suivi", "referers");
echo "<br><br><br>";

	gros_titre(_T('titre_liens_entrants'));

//barre_onglets("statistiques", "referers");

debut_gauche();
debut_boite_info();
echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2>";
echo "<P align=left>"._T('info_gauche_statistiques_referers')."</P></FONT>";
fin_boite_info();

debut_droite();


if ($connect_statut != '0minirezo') {
	echo _T('avis_non_acces_page');
	fin_page();
	exit;
}


//
// Affichage des referers
//

// nombre de referers a afficher
$limit = intval($limit);	//secu
if ($limit == 0) $limit = 100;

if ($jour<>'veille')
	$jour='jour';

barre_onglets("stat_referers", $jour);


// afficher quels referers ?
$where = "visites_$jour>0";
$vis = "visites_$jour";

$table_ref = "spip_referers";

$query = "SELECT referer, $vis AS vis FROM $table_ref WHERE $where ORDER BY $vis DESC";

echo "<p><font face='Verdana,Arial,Sans,sans-serif' size=2>";
echo aff_referers ($query, $limit);
echo "</font></p>";	

echo "</font>";

fin_page();

?>
