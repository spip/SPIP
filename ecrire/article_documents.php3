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
} else {
	$titre_art = '';
	$lien_art = '';
}

debut_html("Images et documents");

echo "<table width='100%' border='0' cellpadding='6' cellspacing='0'>\n";

echo "<tr bgcolor='$couleur_foncee'>\n";
echo "<td width='100%'><font face='Verdana,Arial,Helvetica,sans-serif' size='4' color='#FFFFFF'>";
echo "Documents li&eacute;s &agrave l'article".$lien_art;
echo "</td></tr>\n";

$docs_affiches = "";
$id_doc_actif = $id_document;

$query = "SELECT lien.id_document, documents.id_vignette FROM spip_documents_articles AS lien, spip_documents AS documents ".
	"WHERE lien.id_article=$id_article AND lien.id_document=documents.id_document ".
	"AND documents.mode='document' ORDER BY documents.titre";
$result = mysql_query($query);
while ($row = mysql_fetch_array($result)) {
	$id_document = $row[0];
	$docs_affiches[] = $row[1];

	echo "<tr><td>\n";
	afficher_document($id_document, $id_doc_actif);
	echo "</td></tr>\n";
}


echo "<tr><td height='10'>&nbsp;</td></tr>\n";

echo "<tr bgcolor='$couleur_foncee'>\n";
echo "<td width='100%'><font face='Verdana,Arial,Helvetica,sans-serif' size='4' color='#FFFFFF'>";
echo "Images affichables dans l'article";
echo "</td></tr>\n";

if ($docs_affiches) $docs_affiches = "AND lien.id_document NOT IN (".join(',', $docs_affiches).") ";

$query = "SELECT lien.id_document as id_document FROM spip_documents_articles AS lien, spip_documents AS documents ".
	"WHERE lien.id_article=$id_article AND lien.id_document=documents.id_document ".$docs_affiches.
	"AND documents.mode='vignette' AND documents.titre!='' ORDER BY documents.titre";
$result = mysql_query($query);
while ($row = mysql_fetch_array($result)) {
	$id_document = $row['id_document'];

	echo "<tr><td>\n";
	afficher_document($id_document, $id_doc_actif);
	echo "</td></tr>\n";
}


//
// Ajouter un document
//


echo "<tr><td height='15'>&nbsp;</td></tr>\n";

echo "<tr bgcolor='$couleur_foncee'>\n";
echo "<td><font face='Verdana,Arial,Helvetica,sans-serif' size='4' color='#FFFFFF'>";
echo "Ajouter une image ou un document";
echo "</td></tr>\n";
echo debut_boite_info();

$link = new Link('../spip_image.php3');
$link->addVar('hash', calculer_action_auteur("ajout_doc"));
$link->addVar('hash_id_auteur', $connect_id_auteur);
$link->addVar('ajout_doc', 'oui');
$link->addVar('id_article', $id_article);

afficher_upload($link, 'T&eacute;l&eacute;charger une image ou un document&nbsp;:');

echo "</font>\n";

echo fin_boite_info();

echo "</td></tr></table>\n";

fin_html();

?>
