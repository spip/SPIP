<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_DOCUMENTS")) return;
define("_ECRIRE_INC_DOCUMENTS", "1");


function texte_upload($inclus){
	$myDir = opendir("upload");
	while($entryName = readdir($myDir)) {
		if (is_file("upload/".$entryName) AND !($entryName=='remove.txt')) {
			if (ereg("\.([^.]+)$", $entryName, $match)) {
				$ext = strtolower($match[1]);
				if ($ext == 'jpeg')
					$ext = 'jpg';
				$req = "SELECT * FROM spip_types_documents WHERE extension='$ext'";
				if ($inclus)
					$req .= " AND inclus='$inclus'";
				if (@mysql_fetch_array(mysql_query($req)))
					$texte_upload .= "\n<OPTION VALUE=\"ecrire/upload/$entryName\">$entryName";
			}
		}
	}
	closedir($myDir);
	return ($texte_upload);
}

function vignette($largeur_vignette, $hauteur_vignette, $fichier_vignette) {

		if ($largeur_vignette > 140) {
			$rapport = 140.0 / $largeur_vignette;
			$largeur_vignette = 140;
			$hauteur_vignette = ceil($hauteur_vignette * $rapport);
		}

		if ($hauteur_vignette > 150) {
			$rapport = 150.0 / $hauteur_vignette;
			$hauteur_vignette = 150;
			$largeur_vignette = ceil($largeur_vignette * $rapport);
		}

		return "<a href='../$fichier_vignette'><img src='../$fichier_vignette' border='0' height='$hauteur_vignette' width='$largeur_vignette'></a>\n";
}

function afficher_document($id_document, $id_doc_actif=0) {
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

	$result = mysql_query("SELECT * FROM spip_types_documents WHERE id_type=$id_type");
	if ($type = @mysql_fetch_array($result))	{
		$type_extension = $type['extension'];
		$type_inclus = $type['inclus'];
		$type_titre = $type['titre'];
	}

	$block = "document $id_document";
	if ($id_document == $id_doc_actif)
		echo bouton_block_visible($block);
	else
		echo bouton_block_invisible($block);
	echo "<font face='Verdana, Arial, Helvetica, sans-serif'>\n";
	echo "<font size='4' color='#444444'><b>$titre</b></font> ";
	echo "<font size='2'>";
	if ($taille > 0) {
		if ($type_titre) echo propre($type_titre);
		else echo "fichier &laquo;&nbsp;.$type_extension&nbsp;&raquo;";
		echo " - ".taille_en_octets($taille);
	}
	echo "</font>\n";

	echo "<font size='1'>";
	$hash = calculer_action_auteur("supp_doc ".$id_document);
	echo "[<a href='../spip_image.php3?redirect=".urlencode("article_documents.php3")."&id_article=$id_article&hash_id_auteur=$connect_id_auteur&hash=$hash&doc_supp=".$id_document."'>supprimer le document</a>]\n";
	echo "</font>\n";

	echo "</font>\n";

	if ($id_document == $id_doc_actif)
		echo debut_block_visible($block);
	else
		echo debut_block_invisible($block);
	echo debut_boite_info();

	echo "<table width='100%' border='0' cellspacing='0' cellpadding='8'><tr>\n";

	//
	// preparer le raccourci a afficher sous la vignette ou sous l'apercu
	//
	$raccourci_img =  "<font size='1'>\n".
		"<div align='left'>&lt;img$id_document|left&gt;</div>\n".
		"<div align='center'>&lt;img$id_document|center&gt;</div>\n".
		"<div align='right'>&lt;img$id_document|right&gt;</div>\n".
		"</font>\n";
	$raccourci_doc =  "<font size='1'>\n".
		"<div align='left'>&lt;doc$id_document|left&gt;</div>\n".
		"<div align='center'>&lt;doc$id_document|center&gt;</div>\n".
		"<div align='right'>&lt;doc$id_document|right&gt;</div>\n".
		"</font>\n". "<br>".$raccourci_img;

	//
	// Affichage de la vignette
	//

	$result_vignette = mysql_query("SELECT * FROM spip_documents WHERE id_document=$id_vignette");
	$row_vignette = @mysql_fetch_array($result_vignette);

	if ($row_vignette) {
		$fichier_vignette = $row_vignette['fichier'];
		$largeur_vignette = $row_vignette['largeur'];
		$hauteur_vignette = $row_vignette['hauteur'];
		$taille_vignette = $row_vignette['taille'];
	}

	echo "<td width='150' align='center' valign='top'>\n";
	echo "<div style='border: 1px dashed black; padding: 4px; background-color: #fdf4e8;'>\n";
	echo "<font size='2'><b>VIGNETTE DE PR&Eacute;VISUALISATION</b></font><br>";

	if ($fichier_vignette) {
		echo vignette($largeur_vignette, $hauteur_vignette, $fichier_vignette);
		echo "<font size='2'>\n";
		$hash = calculer_action_auteur("supp_doc ".$id_vignette);
		echo "[<a href='../spip_image.php3?redirect=".urlencode("article_documents.php3")."&id_document=$id_document&id_article=$id_article&hash_id_auteur=$connect_id_auteur&hash=$hash&doc_supp=$id_vignette'>";
		echo "supprimer la vignette";
		echo "</a>]</font><br>\n";

		echo $raccourci_doc; $raccourci_img='';
	}
	else {

		//
		// joli icone a la main
		//
		echo "<table cellpadding=0 cellspacing=0 border=0 width=35 height=32 align='left' valign='bottom'>\n";
		echo "<tr width=35 height=32>\n";
		echo "<td width=35 height=32 background='IMG2/document-vierge.gif' align='left'>\n";
		echo "<table bgcolor='#666666' style='border: solid 1px black; margin-top: 10px; padding-top: 0px; padding-bottom: 0px; padding-left: 3px; padding-right: 3px;' cellspacing=0 border=0>\n";
		echo "<tr><td><font face='verdana,arial,helvetica,sans-serif' color='white' size='1'>$type_extension</font></td></tr></table>\n";
		echo "</td></tr></table>\n&nbsp;&nbsp;&nbsp;";

		// retour aux choses serieuses
		echo "<font face='verdana, arial, helvetica, sans-serif' size='1'>\n";
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
			echo "<small><b>T&eacute;l&eacute;charger une nouvelle image&nbsp;:</b></small>";
			echo aide ("artimg");
			echo "<small><br><INPUT NAME='image' TYPE='File'>\n";
			echo " <INPUT NAME='ok' TYPE=Submit VALUE='T&eacute;l&eacute;charger' CLASS='fondo'></small>\n";
		}
		if ($GLOBALS['connect_statut'] == '0minirezo') {
			echo "<br>";
			$texte_upload = texte_upload("image");
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
	// Afficher un apercu (pour les images)
	//
	if ($type_inclus == 'image') {
		echo "<td width='150' align='center' valign='top'>\n";
		echo "<div style='border: 1px solid #808080; padding: 4px; background-color: #e0f080;'>\n";
		echo "<font size='2'><b>IMAGE</b></font><br>";

		$fichier_vignette = $row['fichier'];
		$largeur_vignette = $row['largeur'];
		$hauteur_vignette = $row['hauteur'];
		echo vignette($largeur_vignette, $hauteur_vignette, $fichier_vignette);
		echo "<font face='verdana, arial, helvetica, sans-serif' size='1'><br>$largeur x $hauteur pixels<br><br></font>";
		echo $raccourci_img;
	}

	//
	// Afficher le document en tant que tel
	//

	echo "<td width='100%' align='left' valign='top'>\n";

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

	echo "<b>Titre&nbsp;:</b><br>\n";
	echo "<INPUT TYPE='text' NAME='titre' CLASS='formo' VALUE=\"".htmlspecialchars($titre)."\" SIZE='40'><br>";

	echo "<b>Description&nbsp;:</b><br>\n";
	echo "<textarea name='descriptif' CLASS='forml' ROWS='5' COLS='*' wrap='soft'>";
	echo htmlspecialchars($descriptif);
	echo "</textarea>\n";

	echo "<p align='right'>";
	echo "<input class='fondo' TYPE='submit' NAME='Valider' VALUE='Valider'>";
	echo "</p>";
	echo "</form>";
	echo "</font>";


	echo "</td>\n";

	echo "</tr></table>\n";

	echo fin_boite_info();
	echo fin_block($block);
}


function pave_documents($id_article) {
	global $puce;

	if ($id_article) {
		$result_doc = mysql_query(("SELECT type.extension AS extension, COUNT(doc.id_document) AS cnt
			FROM spip_types_documents AS type, spip_documents AS doc, spip_documents_articles AS lien
			WHERE lien.id_article=$id_article AND doc.id_document = lien.id_document AND doc.id_type = type.id_type
			GROUP BY doc.id_type"));
		while ($type = mysql_fetch_object($result_doc)) {
			$documents .= $puce.$type->cnt." ".$type->extension."<br>";
			$nbdoc += $type->cnt;
		}

		$a = "<a href=\"javascript:window.open('article_documents.php3?id_article=$id_article',
			'docs_article', 'scrollbars=yes,resizable=yes,width=630,height=550'); void(0);\">";

		if ($nbdoc)
			$icone = "documents-directory.png";
		else
			$icone = "download-dir.png";
		$txticone = "$a<img src='IMG2/$icone' width='48' height='48' border='0'></a>";

		if ($nbdoc == 0) {
			$txtdoc .= $a."<b>Lier un document &agrave; cet article</b></a>";
		}
		else {
			$txtdoc .= $a."<b>";
			if ($nbdoc == 1)
				$txtdoc .= "Un document li&eacute; &agrave; l'article</b></a><br>\n";
			else {
				$txtdoc .= "$nbdoc documents li&eacute;s &agrave; l'article</b></a><br>\n";
			}
			$txtdoc .= $documents;
		}

		debut_boite_info();
		echo "<table border='0' align='center' valign='center'><tr>\n";
		echo "<td align='right'>\n";
		echo $txticone;
		echo "</td>\n";
		echo "<td align='center'><font size='2'>\n";
		echo $txtdoc;
		echo "</font></td>\n";
		echo "</tr></table>\n";
		fin_boite_info();
	}
}

?>