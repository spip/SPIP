<?php

include ("inc.php3");
include_local ("inc_documents.php3");

//
// Gerer les modifications
//

if ($new == "oui") {
	mysql_query("INSERT spip_documents (id_vignette, titre) ".
		"VALUES ('$id_vignette', 'nouveau document')");
	$id_document =  mysql_insert_id();

	if ($id_article) {
		mysql_query("INSERT spip_documents_articles (id_document, id_article) ".
			"VALUES ($id_document, $id_article)");
	}
}

$flag_editable = true; // a affiner ;-))

if ($modif_document == 'oui') {
	$titre = addslashes(corriger_caracteres($titre));
	$descriptif = addslashes(corriger_caracteres($descriptif));
	mysql_query("UPDATE spip_documents SET titre=\"$titre\", descriptif=\"$descriptif\" WHERE id_document=$id_document");
}

$query = "SELECT titre FROM spip_articles WHERE id_article = $id_article";
$result = mysql_query($query);
if ($art = mysql_fetch_object($result)) {
	$titre_art = "&laquo; ".typo($art->titre)." &raquo;";
	$lien_art = " <a href='articles.php3?id_article=$id_article' target='spip_normal'><font color='ffffff'>$titre_art</font></a>";
}
else {
	$titre_art = '';
	$lien_art = '';
}

debut_html("Images et documents li&eacute;s &agrave; l'article $titre_art");


echo "<table width='100%' border='0' cellpadding='6' cellspacing='0'>\n";

$query = "SELECT #cols FROM #table, spip_documents_articles AS l ".
	"WHERE l.id_article=$id_article AND l.id_document=#table.id_document ".
	"AND #table.mode='document' ORDER BY #table.titre";

$documents_lies = fetch_document($query);

if ($documents_lies) {
	echo "<tr bgcolor='$couleur_foncee'>\n";
	echo "<td width='100%'><font face='Verdana,Arial,Helvetica,sans-serif' size='4' color='#ffffff'>";
	echo "Documents li&eacute;s &agrave l'article".$lien_art;
	echo "</td></tr>\n";

	reset($documents_lies);
	while (list(, $id_document) = each($documents_lies)) {
		echo "<tr><td>\n";
		afficher_document($id_document, $id_doc_actif);
		echo "</td></tr>\n";
	}
	echo "<tr><td height='10'>&nbsp;</td></tr>\n";
}


if ($documents_lies) $docs_exclus = "AND l.id_document NOT IN (".join(',', $documents_lies).") ";
$query = "SELECT #cols FROM #table, spip_documents_articles AS l ".
	"WHERE l.id_article=$id_article AND l.id_document=#table.id_document ".$docs_exclus.
	"AND #table.mode='vignette' AND #table.titre!='' ORDER BY #table.titre";

$images_liees = fetch_document($query);

if ($images_liees) {
	echo "<tr bgcolor='$couleur_foncee'>\n";
	echo "<td width='100%'><font face='Verdana,Arial,Helvetica,sans-serif' size='4' color='#ffffff'>";
	echo "Images affichables dans l'article";
	echo "</td></tr>\n";

	reset($images_liees);
	while (list(, $id_document) = each($images_liees)) {
		echo "<tr><td>\n";
		afficher_document($id_document, $id_doc_actif);
		echo "</td></tr>\n";
	}
	echo "<tr><td height='10'>&nbsp;</td></tr>\n";
}


//
// Ajouter un document
//


echo "<tr><td height='5'>&nbsp;</td></tr>\n";

//echo "<tr bgcolor='$couleur_foncee'>\n";
echo "<tr bgcolor='#EEEECC'>\n";
echo "<td><font face='Verdana,Arial,Helvetica,sans-serif' size='4' color='#000000'>";
echo "Ajouter une image ou un document";
echo "</td></tr>\n";
echo "</td></tr></table>\n";

echo debut_boite_info();

$link = new Link('../spip_image.php3');
$link->addVar('hash', calculer_action_auteur("ajout_doc"));
$link->addVar('hash_id_auteur', $connect_id_auteur);
$link->addVar('ajout_doc', 'oui');
$link->addVar('id_article', $id_article);

afficher_upload($link, 'T&eacute;l&eacute;charger depuis votre ordinateur&nbsp;:');

echo "</font>\n";

echo fin_boite_info();


fin_html();

?>
