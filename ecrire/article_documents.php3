<?php

include ("inc.php3");
include_local ("inc_documents.php3");

$redirect_url = new Link('article_documents.php3');
if ($id_document) $redirect_url->addVar('id_document', $id_document);
if ($id_article) $redirect_url->addVar('id_article', $id_article);
$redirect_url = $redirect_url->getUrl();

$image_link = new Link('../spip_image.php3');
if ($id_article) $image_link->addVar('id_article', $id_article);

$id_doc_actif = $id_document;


//
// editable ?
//
if ($id_article) {
	$query = "SELECT id_rubrique FROM spip_articles WHERE id_article=$id_article";
	if ($art = mysql_fetch_object(mysql_query($query)))
		$id_rubrique = $art->id_rubrique;
	$query = "SELECT * FROM spip_auteurs_articles WHERE id_article=$id_article AND id_auteur=$connect_id_auteur";
	$result_auteur = mysql_query($query);
	$flag_auteur = (mysql_num_rows($result_auteur) > 0);
	$flag_editable = (acces_rubrique($id_rubrique) OR ($flag_auteur > 0 AND ($statut == 'prepa' OR $statut == 'prop' OR $new == 'oui')));
} else
	$flag_editable = false;

if (!$flag_editable) {
	echo "<h3>Acc&egrave;s interdit.</h3>";
	exit;
}

//
// Gerer les modifications
//

if ($modif_document == 'oui') {
	$titre = addslashes(corriger_caracteres($titre));
	$descriptif = addslashes(corriger_caracteres($descriptif));
	mysql_query("UPDATE spip_documents SET titre=\"$titre\", descriptif=\"$descriptif\" WHERE id_document=$id_document");
}

// transformer en vignette/document
if ($transformer_image == 'document') {
	mysql_query("UPDATE spip_documents SET mode='document' WHERE id_document=$id_document");
}
else if ($transformer_image == 'vignette') {
	mysql_query("UPDATE spip_documents SET mode='vignette', id_vignette=0 WHERE id_document=$id_document");
}

//
// Affichage
//

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
	$lien_art='';
	echo "</td></tr>\n";

	reset($documents_lies);
	while (list(, $id_document) = each($documents_lies)) {
		echo "<tr><td>\n";
		afficher_document($id_document, $image_link, $redirect_url, $id_doc_actif == $id_document);
		echo "</td></tr>\n";
	}
	echo "<tr><td height='10'>&nbsp;</td></tr>\n";

	$res = mysql_query("SELECT DISTINCT id_vignette FROM spip_documents ".
		"WHERE id_document in (".join(',', $documents_lies).")");
	while ($v = mysql_fetch_object($res))
		$vignettes[] = $v->id_vignette;

	$docs_exclus = ereg_replace('^,','',join(',', $vignettes).','.join(',', $documents_lies));

	if ($docs_exclus)
		$docs_exclus = "AND l.id_document NOT IN ($docs_exclus) ";

}

$query = "SELECT #cols FROM #table, spip_documents_articles AS l ".
		"WHERE l.id_article=$id_article AND l.id_document=#table.id_document ".$docs_exclus.
		"AND #table.mode='vignette' AND #table.titre!='' ORDER BY #table.titre";

$images_liees = fetch_document($query);

if ($images_liees) {
	echo "<tr bgcolor='$couleur_foncee'>\n";
	echo "<td width='100%'><font face='Verdana,Arial,Helvetica,sans-serif' size='4' color='#ffffff'>";
	echo "Images affichables dans l'article".$lien_art;
	$lien_art = '';
	echo "</td></tr>\n";

	reset($images_liees);
	while (list(, $id_document) = each($images_liees)) {
		echo "<tr><td>\n";
		afficher_document($id_document, $image_link, $redirect_url, $id_doc_actif == $id_document);
		echo "</td></tr>\n";
	}
	echo "<tr><td height='10'>&nbsp;</td></tr>\n";
}


//
// Ajouter un document
//


echo "<tr bgcolor='$couleur_foncee'>\n";
echo "<td width='100%'><font face='Verdana,Arial,Helvetica,sans-serif' size='4' color='#ffffff'>";
echo "<i>Ajouter une image ou un document &agrave; l'article".$lien_art;
$lien_art = '';
echo "</i></td></tr>\n";
echo "</td></tr></table>\n";

echo debut_boite_info();

$link = $image_link;
$link->addVar('redirect', $redirect_url);
$link->addVar('hash', calculer_action_auteur("ajout_doc"));
$link->addVar('hash_id_auteur', $connect_id_auteur);
$link->addVar('ajout_doc', 'oui');

afficher_upload($link, 'T&eacute;l&eacute;charger depuis votre ordinateur&nbsp;:');

echo "</font>\n";

echo fin_boite_info();


fin_html();

?>
