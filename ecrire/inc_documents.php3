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


function texte_vignette_document($largeur_vignette, $hauteur_vignette, $fichier_vignette,$fichier_document) {
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
	
	if (strlen($fichier_document)>0)
		return "<a href='../$fichier_document'><img src='../$fichier_vignette' border='0' height='$hauteur_vignette' width='$largeur_vignette'></a>\n";
	else
		return "<img src='../$fichier_vignette' border='0' height='$hauteur_vignette' width='$largeur_vignette'>\n";
}

function texte_vignette_previ($largeur_vignette, $hauteur_vignette, $fichier_vignette,$fichier_document) {
	if ($largeur_vignette > 220) {
		$rapport = 220.0 / $largeur_vignette;
		$largeur_vignette = 220;
		$hauteur_vignette = ceil($hauteur_vignette * $rapport);
	}
	if ($hauteur_vignette > 150) {
		$rapport = 150.0 / $hauteur_vignette;
		$hauteur_vignette = 150;
		$largeur_vignette = ceil($largeur_vignette * $rapport);
	}
	
	if (strlen($fichier_document)>0)
		return "<a href='../$fichier_document'><img src='../$fichier_vignette' border='0' height='$hauteur_vignette' width='$largeur_vignette'></a>\n";
	else
		return "<img src='../$fichier_vignette' border='0' height='$hauteur_vignette' width='$largeur_vignette'>\n";
}

function texte_vignette_non_inclus($largeur_vignette, $hauteur_vignette, $fichier_vignette,$fichier_document) {
	if ($largeur_vignette > 80) {
		$rapport = 80.0 / $largeur_vignette;
		$largeur_vignette = 80;
		$hauteur_vignette = ceil($hauteur_vignette * $rapport);
	}
	if ($hauteur_vignette > 70) {
		$rapport = 70.0 / $hauteur_vignette;
		$hauteur_vignette = 70;
		$largeur_vignette = ceil($largeur_vignette * $rapport);
	}
	
	if (strlen($fichier_document)>0)
		return "<a href='../$fichier_document'><img src='../$fichier_vignette' border='0' height='$hauteur_vignette' width='$largeur_vignette'></a>\n";
	else
		return "<img src='../$fichier_vignette' border='0' height='$hauteur_vignette' width='$largeur_vignette'>\n";
}


//
// Afficher un formulaire d'upload
//

function afficher_upload($link, $intitule, $inclus = '', $afficher_texte_ftp = true, $forcer_document = false) {
	global $this_link, $connect_statut;

	if (!$link->getVar('redirect')) {
		$link->addVar('redirect', $this_link->getUrl());
	}

	if ($forcer_document)
		$link->addVar('forcer_document', 'oui');


	echo "<font face='verdana, arial, helvetica, sans-serif' size='2'>\n";
	echo $link->getForm('POST', '', 'multipart/form-data');

	if (tester_upload()) {
		echo "<b>$intitule</b>";
		echo aide ("artimg");
		echo "<br><small><input name='image' type='File' class='forml'>\n";
		echo " &nbsp;&nbsp;<input name='ok' type='Submit' VALUE='T&eacute;l&eacute;charger' CLASS='fondo'></small>\n";
	}

	if ($connect_statut == '0minirezo' AND $afficher_texte_ftp) {
		echo "<p><div style='border: 1px #303030 dashed; padding: 2px;'>";
		$texte_upload = texte_upload_manuel("upload", $inclus);
		echo "<font color='#505050'>";
		if ($forcer_document) echo '<input type="hidden" name="forcer_document" value="oui">';
		if ($texte_upload) {
			echo "\nUn fichier du dossier ecrire/upload&nbsp;:";
			echo "\n<select name='image2' size='1' class='forml'>";
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

function afficher_document($id_document, $image_link, $redirect_url = "", $deplier = false) {
	global $connect_id_auteur, $connect_statut;
	global $couleur_foncee, $couleur_claire;
	global $this_link;
	
	

	if (!$redirect_url) $redirect_url = $this_link->getUrl();

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
	if ($deplier)
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

	$link = $image_link;
	$link->addVar('redirect', $redirect_url);
	$link->addVar('hash', calculer_action_auteur("supp_doc ".$id_document));
	$link->addVar('hash_id_auteur', $connect_id_auteur);
	$link->addVar('doc_supp', $id_document);

	echo "[<b><a ".$link->getHref().">SUPPRIMER</a></b>]\n";
	echo "</font>\n";

	echo "</font>\n";

	if ($deplier)
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

		echo "</div>\n";
		echo "</td>\n";
	}

	if ($type_inclus == 'image') {
		if ($mode == 'vignette') {
			$link = new Link($redirect_url);
			$link->addVar('transformer_image', 'document');
			$link->addVar('id_document', $id_document);
			$link_transformer = "<font size='1'>[<b><a ".$link->getHref().">Transformer en document</a></b>]</font>\n";
		} else if ($mode == 'document') {
			$link = new Link($redirect_url);
			$link->addVar('transformer_image', 'vignette');
			$link->addVar('id_document', $id_document);
			$link_transformer = "<font size='1'>[<b><a ".$link->getHref().">Transformer en image affichable</a></b>]</font>\n";
		}
	}

	//
	// Afficher le document en tant que tel
	//

	echo "<td width='100%' align='left' valign='top'>\n";

	// Si pas image, lien vers le document
	if ($type_inclus != "image") {
		afficher_icone("../$fichier", 'Voir le document', 'IMG2/voir.gif', 40, 28, 'right');
	}

	if ($descriptif) {
		echo debut_cadre_relief();
		echo "<font face='Georgia, Garamond, Times, sans-serif' size='3'>\n";
		echo propre($descriptif);
		echo "</font>";
		echo fin_cadre_relief();
	}
	echo "<font face=\"Georgia, Garamond, Times, serif\" size=\"3\">";

	$link = new Link($redirect_url);
	$link->addVar('modif_document', 'oui');
	$link->addVar('id_document', $id_document);
	echo $link->getForm('POST');

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
	echo "</td>\n";

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

			$link = $image_link;
			$link->addVar('redirect', $redirect_url);
			$link->addVar('hash', calculer_action_auteur("supp_doc ".$id_vignette));
			$link->addVar('hash_id_auteur', $connect_id_auteur);
			$link->addVar('doc_supp', $id_vignette);

			echo "[<a ".$link->getHref().">";
			echo "supprimer la vignette";
			echo "</a>]</font><br>\n";
			echo $raccourci_doc;
		}
		else {
			// pas de vignette
			echo "<div align='center' style='border: 0px; padding: 2px; background-color: #f0e8e4'>\n";
			list($icone, $largeur_icone, $hauteur_icone) = vignette_par_defaut($type_extension);
			if ($icone) {
				echo "<img src='$icone' width='$largeur_icone' height='$hauteur_icone' align='right'>\n";
			}
			echo "<font size='2'>VIGNETTE PAR D&Eacute;FAUT</font>";
			echo "</div>\n";

			echo "<p>".$raccourci_doc;

			echo "<div align='left'>\n";
			$hash = calculer_action_auteur("ajout_doc");

			$link = $image_link;
			$link->addVar('redirect', $redirect_url);
			$link->addVar('hash', calculer_action_auteur("ajout_doc"));
			$link->addVar('hash_id_auteur', $connect_id_auteur);
			$link->addVar('ajout_doc', 'oui');
			$link->addVar('id_document', $id_document);
			$link->addVar('mode', 'vignette');
	
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



//
// Afficher les documents non inclus
// (page des articles)

function afficher_documents_non_inclus($id_article) {
	global $connect_id_auteur, $connect_statut;
	global $couleur_foncee, $couleur_claire;
	global $this_link;
	global $flag_editable;

	if ($flag_editable){
		$image_link = new Link('../spip_image.php3');
		if ($id_article) $image_link->addVar('id_article', $id_article);
		
		$id_doc_actif = $id_document;
		

		
		// Ne pas afficher vignettes en tant qu'images sans docs
		//// Documents associes
		$query = "SELECT #cols FROM #table, spip_documents_articles AS l ".
			"WHERE l.id_article=$id_article AND l.id_document=#table.id_document ".
			"AND #table.mode='document' AND #table.inclus='non' ORDER BY #table.titre";
		
		$documents_lies = fetch_document($query);


		if ($documents_lies) {
		
			reset($documents_lies);
			while (list(, $id_document) = each($documents_lies)) {
				echo "<p>\n";
				afficher_horizontal_document($id_document, $image_link, $redirect_url, $id_doc_actif == $id_document);
			}
		}

	
	
		/// Ajouter nouveau document/image
		echo "<p><div style='padding: 5px; border : solid 1px black; background-color: #e4e4e4; text-align: center; color: black;'>";	
		
		echo "<div style='padding: 2px; border : dashed 1px black; background-color: #aaaaaa; text-align: left; color: black;'>";
		echo bouton_block_invisible("ajouter_document");	
		echo "<b><font size=1>AJOUTER UN DOCUMENT</font></b>";
		echo "</div>\n";

		echo debut_block_invisible("ajouter_document");
		echo "<p><table width='100%' cellpadding=0 cellspacing=0 border=0>";
		echo "<tr>";
		echo "<td width='200' valign='top'>";
		echo "<font face='verdana,arial,helvetica,sans-serif' size=2>";
		
		echo "<font size=1><b>Vous pouvez joindre &agrave; votre article des documents de type&nbsp;:</b>";
		$query_types_docs = "SELECT extension FROM spip_types_documents ORDER BY extension";
		$result_types_docs = mysql_query($query_types_docs);
		
		while($row=mysql_fetch_array($result_types_docs)){
			$extension=$row['extension'];
			echo "$extension, ";
		}
		echo "<b> ces documents pourront &ecirc;tre par la suite ins&eacute;r&eacute;s <i>&agrave; l'int&eacute;rieur</i> du texte si vous le d&eacute;sirez (&laquo;Modifier cet article&raquo; pour acc&eacute;der &agrave; cette option), ou affich&eacute;s hors du texte de l'article.</b>";
		if (function_exists("imagejpeg")){
			$creer_preview=lire_meta("creer_preview");
			$taille_preview=lire_meta("taille_preview");
			if ($taille_preview < 15) $taille_preview = 120;
			
			if ($creer_preview == 'oui'){
					echo "<p>La cr&eacute;ation automatique de vignettes de pr&eacute;visualisation est activ&eacute;e sur ce site. Si vous installez &agrave; partir de ce formulaire des images au format JPEG, elles seront accompagn&eacute;es d'une vignette d'une taille maximale de $taille_preview&nbsp;pixels. ";
			}
			else {
				if ($connect_statut == "0minirezo"){
					echo "<p>La cr&eacute;ation automatique de vignettes de pr&eacute;visualisation est d&eacute;sactiv&eacute;e sur ce site (r&eacute;glage sur la page &laquo;Configuration pr&eacute;cise&raquo;). Cette fonction facilite la mise en ligne d'un portfolio (collection de photographies pr&eacute;sent&eacute;es sous forme de vignettes cliquables).";
				}
			}
		}
		echo "</font>";
		echo "</td><td width=20>&nbsp;</td>";
		echo "<td valign='top'><font face='verdana,arial,helvetica,sans-serif' size=2>";
		$link = $image_link;
		$link->addVar('redirect', $redirect_url);
		$link->addVar('hash', calculer_action_auteur("ajout_doc"));
		$link->addVar('hash_id_auteur', $connect_id_auteur);
		$link->addVar('ajout_doc', 'oui');
		
		afficher_upload($link, 'T&eacute;l&eacute;charger depuis votre ordinateur&nbsp;:', '', true, true);
		
		echo "</font>\n";
		echo "</td></tr></table>";

		echo fin_block();
		echo "</div>";

	}

}



//
// Afficher un document sous forme de ligne horizontale
//

function afficher_horizontal_document($id_document, $image_link, $redirect_url = "", $deplier = false) {
	global $connect_id_auteur, $connect_statut;
	global $couleur_foncee, $couleur_claire;
	global $this_link;




	if (!$redirect_url) $redirect_url = $this_link->getUrl();

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



	if ($mode == 'document') {
		echo "<div style='border: 1px dashed black; padding: 4px; background-color: #fdf4e8;'>\n";


		echo "<table width='100%' cellpadding=0 cellspacing=0 border=0>";
		echo "<tr><td width='150' valign='top'>";
		echo "<font face='verdana,arial,helvetica,sans-serif' size=2>";
		if ($id_vignette) $vignette = fetch_document($id_vignette);
		if ($vignette) {
			$fichier_vignette = $vignette->get('fichier');
			$largeur_vignette = $vignette->get('largeur');
			$hauteur_vignette = $vignette->get('hauteur');
			$taille_vignette = $vignette->get('taille');
		}

		if ($fichier_vignette) {
			echo "<div align='left'>\n";
			//echo "<font size='2'>VIGNETTE DE PR&Eacute;VISUALISATION</font>";
			$block = "doc_vignette $id_document";
			echo bouton_block_invisible($block);
			echo "<span align='center'>";
			echo texte_vignette_non_inclus($largeur_vignette, $hauteur_vignette, $fichier_vignette, "$fichier");
			echo "</span>";
			echo "<font size='2'>\n";
			$hash = calculer_action_auteur("supp_doc ".$id_vignette);

			$link = $image_link;
			$link->addVar('redirect', $redirect_url);
			$link->addVar('hash', calculer_action_auteur("supp_doc ".$id_vignette));
			$link->addVar('hash_id_auteur', $connect_id_auteur);
			$link->addVar('doc_supp', $id_vignette);
			echo debut_block_invisible($block);
			echo "<b>Vignette personnalis&eacute;e</b>";
			echo "<center>$largeur_vignette x $hauteur_vignette pixels</center>";
			echo "<center><font face='verdana,arial,helvetica,sans-serif'><b>[<a ".$link->getHref().">supprimer la vignette</a>]</b></font></center>\n";
			//echo $raccourci_doc;
			echo fin_block();
			echo "</div>\n";
		}
		else {
			// pas de vignette
			$block = "doc_vignette $id_document";
			echo bouton_block_invisible($block);
			echo "<span align='center'>\n";
			list($icone, $largeur_icone, $hauteur_icone) = vignette_par_defaut($type_extension);
			if ($icone) {
				echo "<a href='../$fichier'><img src='$icone' width='$largeur_icone' height='$hauteur_icone'></a>\n";
			}
			//echo "<font size='2'>VIGNETTE PAR D&Eacute;FAUT</font>";
			echo "</span>\n";

			//echo "<p>".$raccourci_doc;

			echo "<div align='left'>\n";
			$hash = calculer_action_auteur("ajout_doc");

			$link = $image_link;
			$link->addVar('redirect', $redirect_url);
			$link->addVar('hash', calculer_action_auteur("ajout_doc"));
			$link->addVar('hash_id_auteur', $connect_id_auteur);
			$link->addVar('ajout_doc', 'oui');
			$link->addVar('id_document', $id_document);
			$link->addVar('mode', 'vignette');

	
			echo debut_block_invisible($block);
			echo "<b>Vignette par d&eacute;faut</b>";
			echo "<font size=1>";
			afficher_upload($link, 'Remplacer la vignette par d&eacute;faut par un logo personnalis&eacute;&nbsp;:', 'image', false);
			echo "</font>";
			echo fin_block();
			echo "</div>\n";
		}
			
		echo "</td><td width='20'>&nbsp;</td>";
		echo "<td valign='top'>";	
		echo "<font face='verdana,arial,helvetica,sans-serif' size=2>";
		echo "<div style='border: 1px dashed black; padding: 0px;'>";	
			
			$block = "document $id_document";
			echo "<div style='padding: 2px; background-color: #aaaaaa; text-align: left; color: black;'>";	
			echo bouton_block_invisible($block);
			echo "<b><font size=1>DOCUMENT JOINT : ".majuscules(propre($titre))."</font></b>";
			echo "</div>\n";
			
			echo "<div style='padding: 5px; background-color: #ffffff;'>";	
			if (strlen($descriptif)>0) echo propre($descriptif)."<br>";
			
			echo "<font size=1 face='arial,helvetica,sans-serif'>";
			if ($type_titre){
				echo "$type_titre";
			} else {
				echo "Document ".majuscules($type_extension);
			}
			
			echo " : <a href='../$fichier'>".taille_en_octets($taille)."</a>";
			echo "</font>";

			echo debut_block_invisible($block);
			
			/*
			// Afficher l'image du document (trop lourd?)
			if (ereg("^jpg|gif|png$",$type_extension)){
				$vignette = fetch_document($id_document);
				$fichier_vignette = $vignette->get('fichier');
				$largeur_vignette = $vignette->get('largeur');
				$hauteur_vignette = $vignette->get('hauteur');
				$taille_vignette = $vignette->get('taille');
				echo "<div align='center'>";
				echo texte_vignette_previ($largeur_vignette, $hauteur_vignette, $fichier_vignette, "$fichier");
				echo "</div>";
			}
			*/


			$link = new Link($redirect_url);
			$link->addVar('modif_document', 'oui');
			$link->addVar('id_document', $id_document);
			echo $link->getForm('POST');
		
			echo "<b>Titre&nbsp;:</b><br>\n";
			echo "<input type='text' name='titre_document' class='forml' value=\"".htmlspecialchars($titre)."\" size='40'><br>";
		
			echo "<b>Description&nbsp;:</b><br>\n";
			echo "<textarea name='descriptif_document' rows='6' class='forml' cols='*' wrap='soft'>";
			echo htmlspecialchars($descriptif);
			echo "</textarea>\n";
		
			echo "<div align='right'>";
			echo "<input class='fondgris' TYPE='submit' NAME='Valider' VALUE='Valider'>";
			echo "</div>";
			echo "</form>";


			echo "<hr noshade size=1><font size='1'><center>";
		
			$link = $image_link;
			$link->addVar('redirect', $redirect_url);
			$link->addVar('hash', calculer_action_auteur("supp_doc ".$id_document));
			$link->addVar('hash_id_auteur', $connect_id_auteur);
			$link->addVar('doc_supp', $id_document);
		
			echo "<p>[<b><a ".$link->getHref().">SUPPRIMER CE DOCUMENT</a></b>]\n";
			echo "</font></center>\n";



			echo fin_block();


			
			echo "</div>";	
		echo "</div>";	
		echo "</td></tr></table>";

		echo "</div>\n";
	}

}




//
// Afficher un document dans la colonne de gauche
// (edition des articles)

function afficher_documents_colonne($id_article) {
	global $connect_id_auteur, $connect_statut;
	global $couleur_foncee, $couleur_claire;
	global $this_link;
	global $flag_editable;

	if ($flag_editable){
		$image_link = new Link('../spip_image.php3');
		if ($id_article) $image_link->addVar('id_article', $id_article);
		
		$id_doc_actif = $id_document;
		
		echo "<p><div style='border: 1px solid #E86519; padding: 5px; background-color: white;'>";
				
		echo propre("Vous pouvez ins&eacute;rer les images et les documents que vous associez &agrave; votre article &agrave; l'int&eacute;rieur du texte. Pour cela, vous pouvez utiliser\n- les raccourcis de type &lt;IMGxx|yy&gt; pour ins&eacute;rer uniquement l'image ou la vignette,\n- les raccourcis de type &lt;DOCxxx|yy&gt; pour compl&eacute;ter l'affichage de la vignette du titre du document, de son descriptif, et &eacute;ventuellement de la taille du document joint.");	
		echo "</div>";
		
		// Ne pas afficher vignettes en tant qu'images sans docs
		//// Documents associes
		$query = "SELECT #cols FROM #table, spip_documents_articles AS l ".
			"WHERE l.id_article=$id_article AND l.id_document=#table.id_document ".
			"AND #table.mode='document' ORDER BY #table.titre";
		
		$documents_lies = fetch_document($query);

		if ($documents_lies){
			$res = mysql_query("SELECT DISTINCT id_vignette FROM spip_documents ".
				"WHERE id_document in (".join(',', $documents_lies).")");
			while ($v = mysql_fetch_object($res))
				$vignettes[] = $v->id_vignette;
		
			$docs_exclus = ereg_replace('^,','',join(',', $vignettes).','.join(',', $documents_lies));
		
			if ($docs_exclus)
				$docs_exclus = "AND l.id_document NOT IN ($docs_exclus) ";
		}
	
		//// Images sans documents
		$query = "SELECT #cols FROM #table, spip_documents_articles AS l ".
				"WHERE l.id_article=$id_article AND l.id_document=#table.id_document ".$docs_exclus.
				"AND #table.mode='vignette' AND #table.titre!='' ORDER BY #table.titre";
		
		$images_liees = fetch_document($query);
		
		if ($images_liees) {
		reset($images_liees);
		
			while (list(, $id_document) = each($images_liees)) {
				echo "<p>\n";
				afficher_case_document($id_document, $image_link, $redirect_url, $id_doc_actif == $id_document);
			}
		}
	
		if ($documents_lies) {
		
			reset($documents_lies);
			while (list(, $id_document) = each($documents_lies)) {
				echo "<p>\n";
				afficher_case_document($id_document, $image_link, $redirect_url, $id_doc_actif == $id_document);
			}
		}

	
	
		/// Ajouter nouveau document/image
		echo "<p><div style='border: 1px solid black; padding: 4px; background-color: #e4e4e4;'>\n";
		
		echo "<div style='padding: 2px; border : dashed 1px black; background-color: #aaaaaa; text-align: center; color: black;'>";	

		echo bouton_block_invisible("ajouter_image");
		echo "<b><font size=1>AJOUTER UNE IMAGE<br> OU UN DOCUMENT</font></b>";
		echo "</div>\n";
		
		echo debut_block_invisible("ajouter_image");
		echo "<p><font size=1><b>Vous pouvez joindre &agrave; votre article des documents de type&nbsp;:</b>";
		$query_types_docs = "SELECT extension FROM spip_types_documents ORDER BY extension";
		$result_types_docs = mysql_query($query_types_docs);
		
		while($row=mysql_fetch_array($result_types_docs)){
			$extension=$row['extension'];
			echo "$extension, ";
		}
		echo "<b>ou installer des images &agrave; ins&eacute;rer dans le texte.</b>";
		echo "</font>";
		echo fin_block();
				
		$link = $image_link;
		$link->addVar('redirect', $redirect_url);
		$link->addVar('hash', calculer_action_auteur("ajout_doc"));
		$link->addVar('hash_id_auteur', $connect_id_auteur);
		$link->addVar('ajout_doc', 'oui');
		
		afficher_upload($link, 'T&eacute;l&eacute;charger depuis votre ordinateur&nbsp;:');
		
		echo "</font>\n";
		
		echo "</div>";
	}

}


//
// Afficher un document sous forme de ligne depliable
//

function afficher_case_document($id_document, $image_link, $redirect_url = "", $deplier = false) {
	global $connect_id_auteur, $connect_statut;
	global $couleur_foncee, $couleur_claire;
	global $this_link;




	if (!$redirect_url) $redirect_url = $this_link->getUrl();

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



	if ($mode == 'document') {
		echo "<div style='border: 1px dashed black; padding: 4px; background-color: #fdf4e8;'>\n";

		if ($id_vignette) $vignette = fetch_document($id_vignette);
		if ($vignette) {
			$fichier_vignette = $vignette->get('fichier');
			$largeur_vignette = $vignette->get('largeur');
			$hauteur_vignette = $vignette->get('hauteur');
			$taille_vignette = $vignette->get('taille');
		}

		if ($fichier_vignette) {
			echo "<div align='left'>\n";
			//echo "<font size='2'>VIGNETTE DE PR&Eacute;VISUALISATION</font>";
			echo "<div align='center'>";
			echo texte_vignette_document($largeur_vignette, $hauteur_vignette, $fichier_vignette, "$fichier");
			echo "</div>";
			echo "<font size='2'>\n";
			$hash = calculer_action_auteur("supp_doc ".$id_vignette);

			$link = $image_link;
			$link->addVar('redirect', $redirect_url);
			$link->addVar('hash', calculer_action_auteur("supp_doc ".$id_vignette));
			$link->addVar('hash_id_auteur', $connect_id_auteur);
			$link->addVar('doc_supp', $id_vignette);
			$block = "doc_vignette $id_document";
			echo bouton_block_invisible($block);
			echo "<b>Vignette personnalis&eacute;e</b>";
			echo debut_block_invisible($block);
			echo "<center>$largeur_vignette x $hauteur_vignette pixels</center>";
			echo "<center><font face='verdana,arial,helvetica,sans-serif'><b>[<a ".$link->getHref().">supprimer la vignette</a>]</b></font></center>\n";
			echo fin_block();
			echo "</div>\n";
		}
		else {
			// pas de vignette
			echo "<div align='center'>\n";
			list($icone, $largeur_icone, $hauteur_icone) = vignette_par_defaut($type_extension);
			if ($icone) {
				echo "<a href='../$fichier'><img src='$icone' width='$largeur_icone' height='$hauteur_icone'></a>\n";
			}
			//echo "<font size='2'>VIGNETTE PAR D&Eacute;FAUT</font>";
			echo "</div>\n";

			//echo "<p>".$raccourci_doc;

			echo "<div align='left'>\n";
			$hash = calculer_action_auteur("ajout_doc");

			$link = $image_link;
			$link->addVar('redirect', $redirect_url);
			$link->addVar('hash', calculer_action_auteur("ajout_doc"));
			$link->addVar('hash_id_auteur', $connect_id_auteur);
			$link->addVar('ajout_doc', 'oui');
			$link->addVar('id_document', $id_document);
			$link->addVar('mode', 'vignette');

			$block = "doc_vignette $id_document";
			echo bouton_block_invisible($block);
			echo "<b>Vignette par d&eacute;faut</b>";
			//echo "<font size=1 face='arial,helvetica,sans-serif' color='#666666'><div align=left>&lt;img$id_document|left&gt;</div><div align=center>&lt;img$id_document|center&gt;</div><div align=right>&lt;img$id_document|right&gt;</div></font>\n";
	
			echo debut_block_invisible($block);
			echo "<font size=1>";
			afficher_upload($link, 'Remplacer la vignette par d&eacute;faut par un logo personnalis&eacute;&nbsp;:', 'image', false);
			echo "</font>";
			echo fin_block();
			echo "</div>\n";
		}
			
			
		echo "<p></p><div style='border: 1px dashed #aaaaaa; padding: 0px;'>";	
			
			$block = "document $id_document";
			echo "<div style='padding: 2px; background-color: #aaaaaa; text-align: left; color: black;'>";	
			echo bouton_block_invisible($block);
			echo "<b><font size=2>".propre($titre)."</font></b>";
			echo "</div>\n";
			
			echo "<div style='padding: 5px; background-color: #ffffff;'>";	
			if (strlen($descriptif)>0) echo propre($descriptif)."<br>";
			
			echo "<font size=1 face='arial,helvetica,sans-serif'>";
			if ($type_titre){
				echo "$type_titre";
			} else {
				echo "Document ".majuscules($type_extension);
			}
			
			echo " : <a href='../$fichier'>".taille_en_octets($taille)."</a>";
			echo "<font color='666666'><div align=left>&lt;doc$id_document|left&gt;</div><div align=center>&lt;center&gt;&lt;doc$id_document|center&gt;&lt;/center&gt;</div><div align=right>&lt;doc$id_document|right&gt;</div></font>\n";
			echo "</font>";

			echo debut_block_invisible($block);
			$link = new Link($redirect_url);
			$link->addVar('modif_document', 'oui');
			$link->addVar('id_document', $id_document);
			echo $link->getForm('POST');
		
			echo "<b>Titre&nbsp;:</b><br>\n";
			echo "<input type='text' name='titre_document' class='forml' value=\"".htmlspecialchars($titre)."\" size='40'><br>";
		
			echo "<b>Description&nbsp;:</b><br>\n";
			echo "<textarea name='descriptif_document' rows='6' class='forml' cols='*' wrap='soft'>";
			echo htmlspecialchars($descriptif);
			echo "</textarea>\n";
		
			echo "<div align='right'>";
			echo "<input class='fondgris' TYPE='submit' NAME='Valider' VALUE='Valider'>";
			echo "</div>";
			echo "</form>";


			echo "<hr noshade size=1><font size='1'><center>";
		
			$link = $image_link;
			$link->addVar('redirect', $redirect_url);
			$link->addVar('hash', calculer_action_auteur("supp_doc ".$id_document));
			$link->addVar('hash_id_auteur', $connect_id_auteur);
			$link->addVar('doc_supp', $id_document);
		
			echo "<p>[<b><a ".$link->getHref().">SUPPRIMER CE DOCUMENT</a></b>]\n";
			echo "</font></center>\n";



			echo fin_block();


			
			echo "</div>";	
		echo "</div>";	
			

		echo "</div>\n";
	}

	else if ($mode == 'vignette') {
		echo "<div style='border: 1px dashed black; padding: 4px; background-color: #ffffff;'>\n";

	
		//
		// Preparer le raccourci a afficher sous la vignette ou sous l'apercu
		//
	
		$raccourci_doc = "<font size='1' color='#666666' face='arial,helvetica,sans-serif'>";
		$raccourci_doc .= "<div align='left'>&lt;img$id_document|left&gt;</div>\n".
			"<div align='center'>&lt;img$id_document|center&gt;</div>\n".
			"<div align='right'>&lt;img$id_document|right&gt;</div>\n";
		$raccourci_doc .= "</font>\n";
	
		//
		// Afficher un apercu (pour les images)
		//

		$block = "image $id_document";

		if ($type_inclus == 'image') {
			echo "<div style='text-align: center'>\n";
			echo texte_vignette_document($largeur, $hauteur, $fichier,"");
			if (strlen($titre)>0)
			echo "</div>\n";
			echo bouton_block_invisible($block);
			echo "<b>$titre</b><br>";
			if (strlen($descriptif)>0)
				echo propre($descriptif)."<br>";
			echo "<font face='verdana, arial, helvetica, sans-serif' size='1'>$largeur x $hauteur pixels<br></font>\n";
	
			if ($mode == 'vignette')// le raccourci pour une image-document est propose avec la vignette
				echo $raccourci_doc;
	
		}
	
		if ($type_inclus == 'image') {
			if ($mode == 'vignette') {
				$link = new Link($redirect_url);
				$link->addVar('transformer_image', 'document');
				$link->addVar('id_document', $id_document);
				$link_transformer = "<font size='1'>[<b><a ".$link->getHref().">Transformer en document</a></b>]</font>\n";
			} else if ($mode == 'document') {
				$link = new Link($redirect_url);
				$link->addVar('transformer_image', 'vignette');
				$link->addVar('id_document', $id_document);
				$link_transformer = "<font size='1'>[<b><a ".$link->getHref().">Transformer en image affichable</a></b>]</font>\n";
			}
		}

		echo debut_block_invisible($block);


			$link = new Link($redirect_url);
			$link->addVar('modif_document', 'oui');
			$link->addVar('id_document', $id_document);
			echo $link->getForm('POST');
		
			echo "<p></p><div style='border: 1px dashed black; padding: 5px;'>";	
			echo "<b>Titre de l'image&nbsp;:</b><br>\n";
			echo "<input type='text' name='titre_document' class='formo' value=\"".htmlspecialchars($titre)."\" size='40'><br>";
		
			echo "<b>Description&nbsp;:</b><br>\n";
			echo "<textarea name='descriptif_document' rows='6' class='formo' cols='*' wrap='soft'>";
			echo htmlspecialchars($descriptif);
			echo "</textarea>\n";
		
			echo "<p align='right'>";
			echo "<input class='fondo' TYPE='submit' NAME='Valider' VALUE='Valider'>";
			echo "</p>";
			echo "</div>";
			echo "</form>";

			echo "<font size='1'><center>";
		
			$link = $image_link;
			$link->addVar('redirect', $redirect_url);
			$link->addVar('hash', calculer_action_auteur("supp_doc ".$id_document));
			$link->addVar('hash_id_auteur', $connect_id_auteur);
			$link->addVar('doc_supp', $id_document);
		
			echo "<p>[<b><a ".$link->getHref().">SUPPRIMER CETTE IMAGE</a></b>]\n";
			echo "</font></center><br><br>\n";


			echo "<font size='1'>";


			echo "<div style='border: 1px dashed black; padding: 0px;'>";	
			
			echo "<div style='padding: 5px; background-color: #333333; text-align: center; color: white;'>";	
			echo "<b><font size=1>JOINDRE UN DOCUMENT</font></b>";
			echo "</div>\n";
			
			echo "<div style='padding: 5px; background-color: #fdf4e8;'>";	


			$link = $image_link;
			$link->addVar('redirect', $redirect_url);
			$link->addVar('hash', calculer_action_auteur("ajout_doc"));
			$link->addVar('hash_id_auteur', $connect_id_auteur);
			$link->addVar('doc_vignette', $id_document);
			$link->addVar('titre_vignette', $titre);
			$link->addVar('descriptif_vignette', $descriptif);
			$link->addVar('joindre_doc', 'oui');
			
			afficher_upload($link, 'Vous pouvez associer un document &agrave; cette image&nbsp;:','',false);

			echo "</div></div>";

		echo fin_block();

		
		echo "</div>";
		
	}
}




//
// Resume et lien vers les documents lies a l'article
//





function boite_documents_article($id_article) {
	global $puce;

	$result_doc = mysql_query("SELECT type.extension AS extension, COUNT(doc.id_document) AS cnt
		FROM spip_types_documents AS type, spip_documents AS doc, spip_documents_articles AS lien
		WHERE lien.id_article=$id_article AND doc.id_document = lien.id_document AND doc.id_type = type.id_type
		GROUP BY doc.id_type");
	while ($type = mysql_fetch_object($result_doc)) {
		$documents .= $puce.$type->cnt." ".$type->extension."<br>";
		$nbdoc += $type->cnt;
	}

	$a = "<a href=\"javascript:window.open('article_documents.php3?id_article=$id_article',
		'docs_article', 'scrollbars=yes,resizable=yes,toolbar=yes,width=630,height=550'); void(0);\">";

	if ($nbdoc)
		$icone = "documents-directory.png";
	else
		$icone = "download-dir.png";
	$txticone = "$a<img src='IMG2/$icone' width='48' height='48' border='0'></a>";

	if ($nbdoc == 0) {
		$txtdoc .= $a."<b>Lier une image ou un document &agrave; cet article</b></a>";
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