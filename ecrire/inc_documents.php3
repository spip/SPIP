<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_DOCUMENTS")) return;
define("_ECRIRE_INC_DOCUMENTS", "1");

include_ecrire ("inc_objet.php3");


//
// Retourner le code HTML d'utilisation de fichiers uploades a la main
//

function texte_upload_manuel($dir, $inclus = '') {
	$myDir = opendir($dir);
	while($entryName = readdir($myDir)) {
		if (is_file("upload/".$entryName) AND !($entryName=='remove.txt')) {
			if (ereg("\.([^.]+)$", $entryName, $match)) {
				$ext = strtolower($match[1]);
				if ($ext == 'jpeg')
					$ext = 'jpg';
				$req = "SELECT extension FROM spip_types_documents WHERE extension='$ext'";
				if ($inclus)
					$req .= " AND inclus='$inclus'";
				if (@mysql_fetch_array(mysql_query($req)))
					$texte_upload .= "\n<option value=\"$entryName\">$entryName</option>";
			}
		}
	}
	closedir($myDir);
	return $texte_upload;
}


function texte_vignette($largeur_vignette, $hauteur_vignette, $fichier_vignette) {
	if ($largeur_vignette > 140) {
		$rapport = 140.0 / $largeur_vignette;
		$largeur_vignette = 140;
		$hauteur_vignette = ceil($hauteur_vignette * $rapport);
	}
	if ($hauteur_vignette > 130) {
		$rapport = 130.0 / $hauteur_vignette;
		$hauteur_vignette = 130;
		$largeur_vignette = ceil($largeur_vignette * $rapport);
	}
	return "<a href='../$fichier_vignette'><img src='../$fichier_vignette' border='0' height='$hauteur_vignette' width='$largeur_vignette'></a>\n";
}


//
// Afficher un formulaire d'upload
//

function afficher_upload($link, $intitule, $inclus = '') {
	global $this_link, $connect_statut;

	if (!$link->getVar('redirect')) {
		$my_link = new Link("article_documents.php3");	// faute de mieux pour l'instant (Fil)
		$link->addVar('redirect', $my_link->getUrl());
	}

	echo "<font face='verdana, arial, helvetica, sans-serif' size='2'>\n";
	echo $link->getForm('POST', '', 'multipart/form-data');

	if (tester_upload()) {
		echo "<b>$intitule</b>";
		echo aide ("artimg");
		echo "<br><small><input name='image' type='File'>\n";
		echo " &nbsp;&nbsp;<input name='ok' type='Submit' VALUE='T&eacute;l&eacute;charger' CLASS='fondo'></small>\n";
	}

	if ($connect_statut == '0minirezo') {
		echo "<p><div style='border: 1px #303030 dashed; padding: 2px;'>";
		$texte_upload = texte_upload_manuel("upload", $inclus);
		echo "<font color='#505050'>";
		if ($texte_upload) {
			echo "\nUn fichier du dossier ecrire/upload&nbsp;:";
			echo "\n<select name='image2' size='1'>";
			echo $texte_upload;
			echo "\n</select>";
			echo "\n  <input name='ok' type='Submit' value='Choisir' class='fondo'>";
		}
		else {
			echo "En tant qu'administrateur, vous pouvez installer (par FTP) des fichiers dans le dossier ecrire/upload pour ensuite les s&eacute;lectionner directement ici.";
		}
		echo "</font></div>\n";
	}
	echo "</form>\n";
	echo "</font>\n";
}


//
// Afficher un document sous forme de ligne depliable
//

function afficher_document($id_document, $id_doc_actif = 0) {
	global $connect_id_auteur, $connect_statut;
	global $couleur_foncee, $couleur_claire;
	global $this_link;
	global $id_article;

	$document = fetch_document($id_document);

	$id_vignette = $document->get('id_vignette');
	$id_type = $document->get('id_type');
	$titre = $document->get('titre');
	$descriptif = $document->get('descriptif');
	$fichier = $document->get('fichier');
	$largeur = $document->get('largeur');
	$hauteur = $document->get('hauteur');
	$taille = $document->get('taille');
	$mode = $document->get('mode');

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
	$link = new Link('../spip_image.php3');
	$link->addVar('redirect', $this_link->getUrl());
	$link->addVar('hash', calculer_action_auteur("supp_doc ".$id_document));
	$link->addVar('hash_id_auteur', $connect_id_auteur);
	$link->addVar('id_article', $id_article);
	$link->addVar('doc_supp', $id_document);

	echo "[<b><a ".$link->getHref().">SUPPRIMER</a></b>]\n";
	echo "</font>\n";

	echo "</font>\n";

	if ($id_document == $id_doc_actif)
		echo debut_block_visible($block);
	else
		echo debut_block_invisible($block);
	echo debut_boite_info();

	echo "<table width='100%' border='0' cellspacing='0' cellpadding='8'><tr>\n";

	//
	// Preparer le raccourci a afficher sous la vignette ou sous l'apercu
	//

	$raccourci_doc = "<font size='1'>\n";
	$raccourci_doc .= "<div align='center'><b>Raccourcis</b></div>\n";
	$raccourci_doc .= "<div align='left'>&lt;img$id_document|left&gt;</div>\n".
		"<div align='center'>&lt;img$id_document|center&gt;</div>\n".
		"<div align='right'>&lt;img$id_document|right&gt;</div>\n";
	$raccourci_doc .=  "<div align='center'>&lt;doc$id_document|...&gt;</div>\n";
	$raccourci_doc .= "</font>\n";

	//
	// Afficher un apercu (pour les images)
	//
	if ($type_inclus == 'image') {
		echo "<td width='150' align='center' valign='top' rowspan='2'>\n";
		echo "<div style='border: 1px solid #808080; padding: 4px; background-color: #e0f080;'>\n";
		echo "<font size='2'><b>IMAGE</b></font><br>\n";
		echo texte_vignette($largeur, $hauteur, $fichier);
		echo "<font face='verdana, arial, helvetica, sans-serif' size='1'><br>$largeur x $hauteur pixels<br><br></font>\n";

		if ($mode == 'vignette')// le raccourci pour une image-document est propose avec la vignette
			echo $raccourci_doc;

		echo "</td>\n";
	}

	if ($type_inclus == 'image') {
		if ($mode == 'vignette') {
			$link = new Link();
			$link->addVar('transformer_image','document');
			$link->addVar('id_document',$id_document);
			$link_transformer = "<font size='1'>[<b><a ".$link->getHref().">Transformer en document</a></b>]</font>\n";
		} else if ($mode == 'document') {
			$link = new Link();
			$link->addVar('transformer_image','vignette');
			$link->addVar('id_document',$id_document);
			$link_transformer = "<font size='1'>[<b><a ".$link->getHref().">Transformer en image affichable</a></b>]</font>\n";
		}
	}

	//
	// Afficher le document en tant que tel
	//

	echo "<td width='100%' align='left' valign='top'>\n";

	if ($descriptif) {
		echo debut_cadre_relief();
		echo "<font face='Georgia, Garamond, Times, sans-serif' size='3'>\n";
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
	echo "<input type='text' name='titre' class='formo' value=\"".htmlspecialchars($titre)."\" size='40'><br>";

	echo "<b>Description&nbsp;:</b><br>\n";
	echo "<textarea name='descriptif' rows='6' class='formo' cols='*' wrap='soft'>";
	echo htmlspecialchars($descriptif);
	echo "</textarea>\n";

	echo "<p align='right'>";
	echo "<input class='fondo' TYPE='submit' NAME='Valider' VALUE='Valider'>";
	echo "</p>";
	if ($link_transformer) echo "<p align='right'>$link_transformer</p>";

	echo "</form>";
	echo "</font>";

	//
	// Affichage de la vignette (pour les documents)
	//

	if ($mode == 'document') {
		if ($id_vignette) $vignette = fetch_document($id_vignette);
		if ($vignette) {
			$fichier_vignette = $vignette->get('fichier');
			$largeur_vignette = $vignette->get('largeur');
			$hauteur_vignette = $vignette->get('hauteur');
			$taille_vignette = $vignette->get('taille');
		}

		echo "<td align='center' valign='top'>\n";
		echo "<div style='border: 1px dashed black; padding: 4px; background-color: #fdf4e8;'>\n";

		if ($fichier_vignette) {
			echo "<div align='center' style='border: 0px; padding: 2px; background-color: #f0e8e4'>\n";
			echo "<font size='2'>VIGNETTE DE PR&Eacute;VISUALISATION</font>";
			echo "</div>\n";
			echo texte_vignette($largeur_vignette, $hauteur_vignette, $fichier_vignette);
			echo "<font size='2'>\n";
			$hash = calculer_action_auteur("supp_doc ".$id_vignette);
			echo "[<a href='../spip_image.php3?redirect=".urlencode("article_documents.php3")."&id_document=$id_document&id_article=$id_article&hash_id_auteur=$connect_id_auteur&hash=$hash&doc_supp=$id_vignette'>";
			echo "supprimer la vignette";
			echo "</a>]</font><br>\n";
			echo $raccourci_doc;
		}
		else {
			// pas de vignette
			echo "<div align='center' style='border: 0px; padding: 2px; background-color: #f0e8e4'>\n";
			echo vignette_par_defaut ($type_extension, 'right');
			echo "<font size='2'>VIGNETTE PAR D&Eacute;FAUT</font>";
			echo "</div>\n";

			echo "<p>".$raccourci_doc;

			echo "<div align='left'>\n";
			$hash = calculer_action_auteur("ajout_doc");
			$link = new Link('../spip_image.php3');
			$link->addVar('redirect', $this_link->getUrl());
			$link->addVar('ajout_doc', 'oui');
			$link->addVar('id_document', $id_document);
			$link->addVar('id_article', $id_article);
			$link->addVar('mode', 'vignette');
			$link->addVar('hash_id_auteur', $connect_id_auteur);
			$link->addVar('hash', $hash);
	
			afficher_upload($link, 'Charger une vignette&nbsp;:', 'image');
			echo "</div>\n";
		}

		echo "</div>\n";
		echo "</td>\n";
	}

	//
	// fin de la boite document
	//

	echo "</tr></table>\n";

	echo fin_boite_info();
	echo fin_block($block);
}


function pave_documents($id_article) {
	global $puce;

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

?>