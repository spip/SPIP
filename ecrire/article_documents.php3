<?php

include ("inc.php3");


function afficher_document($id_document) {
	global $connect_id_auteur, $connect_statut;
	global $couleur_foncee, $couleur_claire;
	global $this_link;
	global $id_article;

	$result = mysql_query("SELECT * FROM spip_documents WHERE id_document=$id_document");
	if ($row = mysql_fetch_array($result)) {
		$id_document = $row['id_document'];
		$id_vignette = $row['id_vignette'];
		$id_type = $row['id_type'];
		$titre = $row['titre'];
		$descriptif = $row['descriptif'];
		$fichier = $row['fichier'];
		$largeur = $row['largeur'];
		$hauteur = $row['hauteur'];
		$taille = $row['taille'];
		$mode = $row['mode'];
	}

	if ($mode == 'vignette') {
		$row_vignette = $row;
	}
	else if ($id_vignette) {
		$result_vignette = mysql_query("SELECT * FROM spip_documents WHERE id_document=$id_vignette");
		$row_vignette = @mysql_fetch_array($result_vignette);
	}
	if ($row_vignette) {
		$fichier_vignette = $row_vignette['fichier'];
		$largeur_vignette = $row_vignette['largeur'];
		$hauteur_vignette = $row_vignette['hauteur'];
		$taille_vignette = $row_vignette['taille'];
	}

	$result = mysql_query("SELECT * FROM spip_types_documents WHERE id_type=$id_type");
	if ($row = @mysql_fetch_array($result))	{
		$type_extension = $row['extension'];
		$type_inclus = $row['inclus'];
		$type_titre = $row['titre'];
	}

	$block = "document $id_document";
	echo bouton_block_invisible($block);
	echo "<font face='Verdana, Arial, Helvetica, sans-serif'>\n";
	echo "<font size='4' color='#444444'><b>$titre</b></font> ";
	echo "<font size='2'>";
	if ($taille > 0) {
		if ($type_titre) echo propre($type_titre);
		else echo "fichier &laquo;&nbsp;.$type_extension&nbsp;&raquo;";
		if ($largeur && $hauteur) echo " - $largeur x $hauteur pixels";
		echo " - ".taille_en_octets($taille);
	}
	echo "</font>\n";

	echo "<font size='1'>";
	$hash = calculer_action_auteur("supp_doc ".$id_document);
	echo "[<b><a href='../spip_image.php3?redirect=".urlencode("article_documents.php3")."&id_article=$id_article&hash_id_auteur=$connect_id_auteur&hash=$hash&doc_supp=".$id_document."'>SUPPRIMER&nbsp;CE&nbsp;DOCUMENT</a></b>]\n";
	echo "</font>\n";

	echo "</font>\n";

	echo debut_block_invisible($block);
	echo debut_boite_info();

	echo "<table width='100%' border='0' cellspacing='0' cellpadding='8'><tr>\n";
	echo "<td width='150' align='center' valign='top'>\n";

	//
	// Affichage de la vignette ou de l'apercu
	//

	if ($mode == 'document') {
		echo "<div style='border: 1px dashed black; padding: 4px; background-color: #fdf4e8;'>\n";
		echo "<font size='2'><b>VIGNETTE DE PR&Eacute;VISUALISATION</b></font><br>";
	}
	else {
		echo "<div style='border: 1px solid #808080; padding: 4px; background-color: #e0f080;'>\n";
		echo "<font size='2'><b>APER&Ccedil;U</b></font><br>";
	}


	if ($fichier_vignette) {
		if ($largeur_vignette > 140) {
			$rapport = 140.0 / $largeur_vignette;
			$largeur_vignette = 140;
			$hauteur_vignette = floor($hauteur_vignette * $rapport);
		}
		if ($hauteur_vignette > 150) {
			$rapport = 150.0 / $hauteur_vignette;
			$hauteur_vignette = 150;
			$largeur_vignette = floor($largeur_vignette * $rapport);
		}
		echo "<a href='../$fichier_vignette'><img src='../$fichier_vignette' border='0' height='$hauteur_vignette' width='$largeur_vignette'></a>\n";

		if ($mode == 'document') {
			$hash = calculer_action_auteur("supp_doc ".$id_vignette);
			echo "<font size='2'>\n";
			echo "[<a href='../spip_image.php3?redirect=".urlencode("article_documents.php3")."&id_article=$id_article&hash_id_auteur=$connect_id_auteur&hash=$hash&doc_supp=$id_vignette'>";
			echo "supprimer la vignette</a>]</font><br>\n";
		}

		echo "<font size='1'>\n";
		echo "<div align='left'>&lt;img$id_document|left&gt;</div>\n";
		echo "<div align='center'>&lt;img$id_document|center&gt;</div>\n";
		echo "<div align='right'>&lt;img$id_document|right&gt;</div>\n";
		echo "</font>\n";
	}
	else {
		echo "<font face='verdana, arial, helvetica, sans-serif' size='2'>\n";
		echo "<div align='left'>Si vous voulez ins&eacute;rer un lien graphique vers ce document, installez ici une vignette de pr&eacute;visualisation.<p>";

		$hash = calculer_action_auteur("ajout_doc");
		echo "<form action='../spip_image.php3' METHOD='POST' ENCTYPE='multipart/form-data'>";
		echo "<input name='redirect' type='Hidden' VALUE='article_documents.php3'>";
		echo "<input name='ajout_doc' type='Hidden' VALUE='oui'>";
		echo "<input name='id_document' type='Hidden' VALUE='$id_document'>";
		echo "<input name='id_article' type='Hidden' VALUE='$id_article'>";
		echo "<input name='mode' type='Hidden' VALUE='vignette'>";
		echo "<Input name='hash_id_auteur' type='Hidden' VALUE='$connect_id_auteur'>";
		echo "<input name='hash' type='Hidden' VALUE='$hash'>";

		if (tester_upload()) {
			echo "<B>T&eacute;l&eacute;charger une nouvelle image&nbsp;:</B>";
			echo aide ("artimg");
			echo "<small><br><INPUT NAME='image' TYPE='File'>\n";
			echo " <INPUT NAME='ok' TYPE=Submit VALUE='T&eacute;l&eacute;charger' CLASS='fondo'></small>\n";
		}
		if ($GLOBALS['connect_statut'] == '0minirezo') {
			echo "<br>";
			$texte_upload = '';
			$myDir = opendir("upload");
			while($entryName = readdir($myDir)) {
				if (ereg('\.(gif|jpe?g|png)$', $entryName)) {
					$texte_upload .= "\n<OPTION VALUE=\"ecrire/upload/$entryName\">$entryName";
				}
			}
			closedir($myDir);
			if ($texte_upload) {
				echo "\nS&eacute;lectionner un fichier&nbsp;:";
				echo "\n<SELECT NAME='image' CLASS='forml' SIZE=1>";
				echo $texte_upload;
				echo "\n</SELECT>";
				echo "\n</option>";
				echo "\n  <INPUT NAME='ok' TYPE=Submit VALUE='Choisir' CLASS='fondo'>";
			}
			else if (!tester_upload()) {
				echo "Installer des images dans le dossier /ecrire/upload pour pouvoir les s&eacute;lectionner ici.";
			}
		}
		echo "</form>\n";
		echo "</font>\n";
	}

	echo "</div>\n";

	echo "</td>\n";

	//
	// Afficher le document en tant que tel
	//

	echo "<td width='100%' align='left' valign='top'>\n";

	// Si quelqu'un trouve un endroit ou caser la jolie icone en HTML fait main d'Arno ;-))
/*	if ($fichier) {
		echo "<table cellpadding=0 cellspacing=0 border=0 width=35 height=32 align='left' valign='bottom'>\n";
		echo "<tr width=35 height=32>\n";
		echo "<td width=35 height=32 background='IMG2/document-vierge.gif' align='left'>\n";
		echo "<table bgcolor='#666666' style='border: solid 1px black; margin-top: 10px; padding-top: 0px; padding-bottom: 0px; padding-left: 3px; padding-right: 3px;' cellspacing=0 border=0>\n";
		echo "<tr><td><font face='verdana,arial,helvetica,sans-serif' color='white' size='1'>$type_extension</font></td></tr></table>\n";
		echo "</td></tr></table>\n&nbsp;&nbsp;&nbsp;";
	}*/

	if ($descriptif) {
		echo debut_cadre_relief();
		echo "<font face='Georgia, Garamond, Times, sans-serif' size='2'>\n";
		echo propre($descriptif);
		echo "</font>";
		echo fin_cadre_relief();
	}
	echo "<font face=\"Georgia, Garamond, Times, serif\" size=\"3\">";

	echo "<form action='article_documents.php3' method='post'>";
	echo "<input type='hidden' name='id_article' value='$id_article'>";
	echo "<input type='hidden' name='id_document' value='$id_document'>";
	echo "<input type='hidden' name='modif_document' value='oui'>";

	$titre = htmlspecialchars($titre);
	echo "<b>Titre&nbsp;:</b><br>\n";
	echo "<INPUT TYPE='text' NAME='titre' CLASS='formo' VALUE=\"".htmlspecialchars($titre)."\" SIZE='40'><br>";

	echo "<b>Description&nbsp;:</b<<br>\n";
	echo "<textarea name='descriptif' CLASS='forml' ROWS='3' COLS='*' wrap='soft'>";
	echo htmlspecialchars($descriptif);
	echo "</textarea>\n";

	echo "<div align='right'>";
	echo "<input clasS='fondo' TYPE='submit' NAME='Valider' VALUE='Valider'>";
	echo "</div>";
	echo "</form>";
	echo "</font>";


	echo "</td>\n";

	echo "</tr></table>\n";

	echo fin_boite_info();
	echo fin_block($block);
}


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


debut_html("Images et documents li&eacute;s &agrave; l'article");

echo "<table width='100%' border='0' cellpadding='6' cellspacing='0'>\n";

echo "<tr bgcolor='$couleur_foncee'>\n";
echo "<td width='100%'><font face='Verdana,Arial,Helvetica,sans-serif' size='4' color='#FFFFFF'>";
echo "Documents li&eacute;s &agrave l'article";
echo "</td></tr>\n";

$docs_affiches = "";

$query = "SELECT lien.id_document, documents.id_vignette FROM spip_documents_articles AS lien, spip_documents AS documents ".
	"WHERE id_article=$id_article AND lien.id_document=documents.id_document ".
	"AND documents.mode='document' AND documents.titre!='' ORDER BY documents.titre";
$result = mysql_query($query);
while ($row = mysql_fetch_array($result)) {
	$id_document = $row[0];
	$docs_affiches[] = $row[1];

	echo "<tr><td>\n";
	afficher_document($id_document);
	echo "</td></tr>\n";
}


echo "<tr><td height='10'>&nbsp;</td></tr>\n";

echo "<tr bgcolor='$couleur_foncee'>\n";
echo "<td width='100%'><font face='Verdana,Arial,Helvetica,sans-serif' size='4' color='#FFFFFF'>";
echo "Images affichables dans l'article";
echo "</td></tr>\n";

if ($docs_affiches) $docs_affiches = "AND lien.id_document NOT IN (".join(',', $docs_affiches).") ";

$query = "SELECT lien.id_document FROM spip_documents_articles AS lien, spip_documents AS documents ".
	"WHERE id_article=$id_article AND lien.id_document=documents.id_document ".$docs_affiches.
	"AND documents.mode='vignette' AND documents.titre!='' ORDER BY documents.titre";
$result = mysql_query($query);
while ($row = mysql_fetch_array($result)) {
	$id_document = $row[0];

	echo "<tr><td>\n";
	afficher_document($id_document);
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
if (tester_upload()) {
	echo "Ajouter une image ou un document&nbsp;:";
	echo aide("artimg");
	echo "<br><small><INPUT NAME='image' TYPE=File CLASS='forml'></small>";
	echo "   <INPUT NAME='ok' TYPE=Submit VALUE='T&eacute;l&eacute;charger' CLASS='fondo'>";
}
if ($connect_statut == '0minirezo') {
	$myDir = opendir("upload");
	while($entryName = readdir($myDir)){
		if (!ereg("^\.", $entryName) AND eregi("(gif|jpg|png)$", $entryName)) {
			$entryName = addslashes($entryName);
			$afficher .= "\n<OPTION VALUE='ecrire/upload/$entryName'>$entryName</OPTION>";
		}
	}
	closedir($myDir);

	if (strlen($afficher) > 10){
		echo "\nS&eacute;lectionner un fichier&nbsp;:";
		echo "\n<SELECT NAME='image' CLASS='forml' SIZE=1>";
		echo $afficher;
		echo "\n</SELECT>";
		echo "\n  <INPUT NAME='ok' TYPE=Submit VALUE='Choisir' CLASS='fondo'>";
	}
	else if (!tester_upload()) {
		echo "Vous devez installer des images dans le dossier /ecrire/upload pour pouvoir les s&eacute;lectionner ici.<p>";
	}
}
echo "</FORM>";
echo "</div>";
echo "</font>\n";

echo "</td></tr>\n";

echo "</table>\n";



fin_html();

?>
