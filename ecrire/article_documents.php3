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

debut_html("Images et documents");

echo "<table width='100%' border='0' cellpadding='6' cellspacing='0'>\n";

echo "<tr bgcolor='$couleur_foncee'>\n";
echo "<td width='100%'><font face='Verdana,Arial,Helvetica,sans-serif' size='4' color='#FFFFFF'>";
echo "Documents li&eacute;s &agrave l'article";
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

echo "<tr bgcolor='#EEEECC'>\n";
echo "<td width='100%'><font face='Verdana,Arial,Helvetica,sans-serif' size='4' color='#000000'>";
echo "Ajouter un nouveau document";
echo "</td></tr>\n";


echo "<tr><td>\n";

$hash = calculer_action_auteur("ajout_doc");

echo "<font face=\"verdana, arial, helvetica, sans-serif\" size='2'>\n";
echo "<FORM ACTION='../spip_image.php3' METHOD='POST' ENCTYPE='multipart/form-data'>\n";
echo "<INPUT NAME='redirect' TYPE=Hidden VALUE='article_documents.php3'>\n";
echo "<INPUT NAME='id_article' TYPE=Hidden VALUE=$id_article>\n";
echo "<INPUT NAME='hash_id_auteur' TYPE=Hidden VALUE=$connect_id_auteur>\n";
echo "<INPUT NAME='hash' TYPE=Hidden VALUE=$hash>\n";
echo "<INPUT NAME='ajout_doc' TYPE=Hidden VALUE='oui'>\n";
echo "Ajouter une image ou un document&nbsp;:";
echo aide("artimg");
if (tester_upload() AND ($connect_statut == '0minirezo')) {
	$texte_upload = texte_upload("");
	if ($texte_upload){
		echo "\nS&eacute;lectionner un fichier&nbsp;:";
		echo "\n<SELECT NAME='image' CLASS='forml' SIZE=1>";
		echo $texte_upload;
		echo "\n</SELECT>";
		echo "\n  <INPUT NAME='ok' TYPE=Submit VALUE='Choisir' CLASS='fondo'>";
	}
	else {
		echo "<br><small><INPUT NAME='image' TYPE=File CLASS='forml'></small>";
		echo "   <INPUT NAME='ok' TYPE=Submit VALUE='T&eacute;l&eacute;charger' CLASS='fondo'>";
	}
}
echo "</FORM>";
echo "</div>";
echo "</font>\n";

echo "</td></tr>\n";

echo "</table></body></html>\n";
flush();

?>
