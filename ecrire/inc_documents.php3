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
				if (@mysql_fetch_array(spip_query($req)))
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
		return "<a href='../$fichier_document'><img src='../$fichier_vignette' border='0' height='$hauteur_vignette' width='$largeur_vignette' align='top'></a>\n";
	else
		return "<img src='../$fichier_vignette' border='0' height='$hauteur_vignette' width='$largeur_vignette' align='top'>\n";
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

function afficher_upload($link, $intitule, $inclus = '', $afficher_texte_ftp = true, $forcer_document = false, $dossier_complet = false) {
	global $this_link, $connect_statut;

	if (!$link->getVar('redirect')) {
		$link->addVar('redirect', $this_link->getUrl());
	}

	if ($forcer_document)
		$link->addVar('forcer_document', 'oui');


	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size='2'>\n";
	echo $link->getForm('POST', '', 'multipart/form-data');

	if (tester_upload()) {
		echo "<b>$intitule</b>";
		echo "<br><small><input name='image' type='File'  class='fondl' style='font-size: 9px; width: 100%;'>\n";
		echo "<div align='right'><input name='ok' type='Submit' VALUE='T&eacute;l&eacute;charger' CLASS='fondo' style='font-size: 9px;'></div></small>\n";
	}

	if ($connect_statut == '0minirezo') {
		$texte_upload = texte_upload_manuel("upload", $inclus);
		if ($texte_upload) {
			echo "<p><div style='border: 1px #303030 dashed; padding: 2px;'>";
			echo "<font color='#505050'>";
			if ($forcer_document) echo '<input type="hidden" name="forcer_document" value="oui">';
			echo "\nVous pouvez s&eacute;lectionner un fichier du dossier <i>upload</i>&nbsp;:";
			echo "\n<select name='image2' size='1' class='fondl' style='width:100%; font-size: 9px;'>";
			echo $texte_upload;
			echo "\n</select>";
			echo "\n  <div align='right'><input name='ok' type='Submit' value='Choisir' class='fondo' style='font-size: 9px;'></div>";
			
			if ($afficher_texte_ftp){
				if ($dossier_complet){
					echo "\n<p><b>Portfolio automatique&nbsp;:</b>";
					echo "\n<br>Vous pouvez installer automatiquement tous les documents contenus dans le dossier <i>upload</i>.";
					echo "\n<div align='right'><input name='dossier_complet' type='Submit' value='Installer tous les documents' class='fondo' style='font-size:9px;'></div>";
				}
			}
			echo "</font></div>\n";
			
		}
		else if ($afficher_texte_ftp) {
			echo "En tant qu'administrateur, vous pouvez installer (par FTP) des fichiers dans le dossier ecrire/upload pour ensuite les s&eacute;lectionner directement ici.".aide("ins_upload");
		}
	}
	echo "</form>\n";
	echo "</font>\n";
}




//
// Afficher les documents non inclus
// (page des articles)

function afficher_documents_non_inclus($id_article, $type = "article", $flag_modif = true) {
	global $connect_id_auteur, $connect_statut;
	global $couleur_foncee, $couleur_claire;
	global $this_link;
	global $id_doublons, $options;

	if ($flag_modif){
		$image_link = new Link('../spip_image.php3');
		if ($id_article) $image_link->addVar('id_article', $id_article);
		if ($type == "rubrique") $image_link->addVar('modifier_rubrique','oui');

		
		$id_doc_actif = $id_document;
		
		// Ne pas afficher vignettes en tant qu'images sans docs
		//// Documents associes
		$query = "SELECT * FROM #table AS docs, spip_documents_".$type."s AS l ".
			"WHERE l.id_$type=$id_article AND l.id_document=docs.id_document ".
			"AND docs.mode='document'";
			
		if ($id_doublons['documents']) $query .= " AND docs.id_document NOT IN (".$id_doublons['documents'].") ";
		$query .= " ORDER BY docs.id_document";
		
		$documents_lies = fetch_document($query);

		echo "<p>";	
		//debut_cadre_enfonce("doc-24.gif");
		if ($documents_lies) {
	
			if ($type == "article") echo "<font size=2 face='Verdana,Arial,Helvetica,sans-serif'>Les documents suivants sont associ&eacute;s &agrave; votre article. Vous ne les avez pas ins&eacute;r&eacute;s directement dans le texte de votre article, ils appara&icirc;tront sur le site public sous forme de &laquo;documents attach&eacute;s&raquo;.</font>";

			$case = "gauche";
			echo "<table width=100% cellpadding=0 cellspacing=0 border=0>";
			reset($documents_lies);
			while (list(, $id_document) = each($documents_lies)) {
				if ($case == "gauche") echo "<tr><td><img src='img_pack/rien.gif' height=5></td></tr><tr><td width=50% valign='top'>";
				else echo "</td><td><img src='img_pack/rien.gif' width=5></td><td width=50% valign='top'>";
				echo "\n";
				afficher_horizontal_document($id_document, $image_link, $redirect_url, $id_doc_actif == $id_document);
				if ($case == "gauche") {
					echo "</td>";
					$case = "droite";
				}
				else {
					echo "</td></tr>";
					$case = "gauche";
				}
				
			}
			if ($case == "droite") echo "<td><img src='img_pack/rien.gif' height=5></td><td width=50%> &nbsp; </td></tr>";
			else echo "</tr>";
			echo "<tr><td><img src='img_pack/rien.gif' height=5></td></tr>";
			echo "</table>";
		}

	
		if ($options == "avancees"){
			/// Ajouter nouveau document/image
			
			echo debut_cadre_enfonce("doc-24.gif",false,"creer.gif");
			echo "<div style='padding: 2px; background-color: $couleur_claire; text-align: left; color: black;'>";
			echo bouton_block_invisible("ajouter_document");	
			if ($type == "rubrique") echo "<b><font size=1>PUBLIER UN DOCUMENT DANS CETTE RUBRIQUE</font></b>".aide("ins_doc");
			else echo "<b><font size=1>JOINDRE UN DOCUMENT</font></b>".aide("ins_doc");
			echo "</div>\n";
			echo debut_block_invisible("ajouter_document");
			
			echo "<p><table width='100%' cellpadding=0 cellspacing=0 border=0>";
			echo "<tr>";
			echo "<td width='200' valign='top'>";
			echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=2>";
			
			if ($type == "article") echo "<font size=1><b>Vous pouvez joindre &agrave; votre article des documents de type&nbsp;:</b>";
			else if ($type == "rubrique") echo "<font size=1><b>Vous pouvez installer dans cette rubrique des documents de type&nbsp;:</b>";
			$query_types_docs = "SELECT extension FROM spip_types_documents ORDER BY extension";
			$result_types_docs = spip_query($query_types_docs);
			
			while($row=mysql_fetch_array($result_types_docs)){
				$extension=$row['extension'];
				echo "$extension, ";
			}
			if ($type == "article") echo "<b> ces documents pourront &ecirc;tre par la suite ins&eacute;r&eacute;s <i>&agrave; l'int&eacute;rieur</i> du texte si vous le d&eacute;sirez (&laquo;Modifier cet article&raquo; pour acc&eacute;der &agrave; cette option), ou affich&eacute;s hors du texte de l'article.</b>";
	
			if (function_exists("imagejpeg") AND function_exists("ImageCreateFromJPEG")){
				$creer_preview=lire_meta("creer_preview");
				$taille_preview=lire_meta("taille_preview");
				$gd_formats=lire_meta("gd_formats");
				if ($taille_preview < 15) $taille_preview = 120;
				
				if ($creer_preview == 'oui'){
						echo "<p>La cr&eacute;ation automatique de vignettes de pr&eacute;visualisation est activ&eacute;e sur ce site. Si vous installez &agrave; partir de ce formulaire des images au(x) format(s) $gd_formats, elles seront accompagn&eacute;es d'une vignette d'une taille maximale de $taille_preview&nbsp;pixels. ";
				}
				else {
					if ($connect_statut == "0minirezo"){
						echo "<p>La cr&eacute;ation automatique de vignettes de pr&eacute;visualisation est d&eacute;sactiv&eacute;e sur ce site (r&eacute;glage sur la page &laquo;Configuration pr&eacute;cise&raquo;). Cette fonction facilite la mise en ligne d'un portfolio (collection de photographies pr&eacute;sent&eacute;es sous forme de vignettes cliquables).";
					}
				}
			}
			echo "</font>";
			echo "</td><td width=20>&nbsp;</td>";
			echo "<td valign='top'><font face='Verdana,Arial,Helvetica,sans-serif' size=2>";
			$link = $image_link;
			$link->addVar('redirect', $redirect_url);
			$link->addVar('hash', calculer_action_auteur("ajout_doc"));
			$link->addVar('hash_id_auteur', $connect_id_auteur);
			$link->addVar('ajout_doc', 'oui');
			$link->addVar('type', $type);
			
			afficher_upload($link, 'T&eacute;l&eacute;charger depuis votre ordinateur&nbsp;:', '', true, true, true);
			
			
			
			
			echo "</font>\n";
			echo "</td></tr></table>";
			echo fin_block();
			fin_cadre_enfonce();
		}
		

	}

}



//
// Afficher un document sous forme de ligne horizontale
//

function afficher_horizontal_document($id_document, $image_link, $redirect_url = "", $deplier = false) {
	global $connect_id_auteur, $connect_statut;
	global $couleur_foncee, $couleur_claire;
	global $this_link;
	global $options;




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
	$date = $document->get('date');
	$mode = $document->get('mode');
	if (!$titre) {
		$titre_aff = "fichier : ".ereg_replace("^[^\/]*\/[^\/]*\/","",$fichier);
	} else {
		$titre_aff = $titre;
	}

	$result = spip_query("SELECT * FROM spip_types_documents WHERE id_type=$id_type");
	if ($type = @mysql_fetch_array($result))	{
		$type_extension = $type['extension'];
		$type_inclus = $type['inclus'];
		$type_titre = $type['titre'];
	}


	if ($mode == 'document') {
		debut_cadre_enfonce("doc-24.gif");
		//echo "<div style='border: 1px dashed #aaaaaa; padding: 0px; background-color: #e4e4e4;'>\n";
			echo "<div style='padding: 2px; background-color: #aaaaaa; text-align: left; color: black;'>";	
			echo bouton_block_invisible("doc_vignette $id_document,document $id_document");
			echo "<font size=1 face='arial,helvetica,sans-serif'>Document : </font> <b><font size=2>".propre($titre_aff)."</font></b>";
			echo "</div>\n";


		if ($id_vignette) $vignette = fetch_document($id_vignette);
		if ($vignette) {
			$fichier_vignette = $vignette->get('fichier');
			$largeur_vignette = $vignette->get('largeur');
			$hauteur_vignette = $vignette->get('hauteur');
			$taille_vignette = $vignette->get('taille');
		}

		
		echo "<p></p><div style='border: 1px dashed #666666; padding: 5px; background-color: #f0f0f0;'>";
		if ($fichier_vignette) {
			echo "<div align='left'>\n";
			echo "<div align='center''>";
			$block = "doc_vignette $id_document";
			echo texte_vignette_document($largeur_vignette, $hauteur_vignette, $fichier_vignette, "$fichier");
			echo "</div>";
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
			echo "<center><font face='Verdana,Arial,Helvetica,sans-serif'><b>[<a ".$link->getHref().">supprimer la vignette</a>]</b></font></center>\n";
			echo fin_block();
			echo "</div>\n";
		}
		else {
			// pas de vignette
			echo "<div align='center'>\n";
			$block = "doc_vignette $id_document";
			list($icone, $largeur_icone, $hauteur_icone) = vignette_par_defaut($type_extension);
			if ($icone) {
				echo "<a href='../$fichier'><img src='$icone' border=0 width='$largeur_icone' align='top' height='$hauteur_icone'></a>\n";
			}
			echo "</div>\n";
			echo "<font size='2'>\n";

			echo "<div align='left'>\n";
			$hash = calculer_action_auteur("ajout_doc");

			$link = $image_link;
			$link->addVar('redirect', $redirect_url);
			$link->addVar('hash', calculer_action_auteur("ajout_doc"));
			$link->addVar('hash_id_auteur', $connect_id_auteur);
			$link->addVar('ajout_doc', 'oui');
			$link->addVar('id_document', $id_document);
			$link->addVar('mode', 'vignette');
			
			if ($options == 'avancees'){
				echo debut_block_invisible($block);
				echo "<b>Vignette par d&eacute;faut</b>";
	
				
				echo "<p></p><div><font size=1>";
				afficher_upload($link, 'Remplacer la vignette par d&eacute;faut par un logo personnalis&eacute;&nbsp;:', 'image', false);
				echo "</font></div>";
				echo fin_block();
			}
			echo "</div>\n";
		}
		echo "</div>";

		$block = "document $id_document";

		echo debut_block_invisible($block);
		echo "<p></p><div style='border: 1px dashed #666666; padding: 0px; background-color: #f0f0f0;'>";	
			
			
			
			echo "<div style='padding: 5px;'>";	
			if (strlen($descriptif)>0) echo propre($descriptif)."<br>";
			

			if ($type_titre){
				echo "$type_titre";
			} else {
				echo "Document ".majuscules($type_extension);
			}
			echo " : <a href='../$fichier'>".taille_en_octets($taille)."</a>";

			$link = new Link($redirect_url);
			$link->addVar('modif_document', 'oui');
			$link->addVar('id_document', $id_document);
			echo $link->getForm('POST');
		
			echo "<b>Titre du document&nbsp;:</b><br>\n";
			echo "<input type='text' name='titre_document' class='formo' style='font-size:9px;' value=\"".htmlspecialchars($titre)."\" size='40'><br>";
	
			if ($GLOBALS['coll'] > 0){
				if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})", $date, $regs)) {
					$mois = $regs[2];
					$jour = $regs[3];
					$annee = $regs[1];
				}
				echo "<b>Date de mise en ligne&nbsp;:</b><br>\n";
				echo "<SELECT NAME='jour_doc' SIZE=1 CLASS='fondl' style='font-size:9px;'>";
				afficher_jour($jour);
				echo "</SELECT> ";
				echo "<SELECT NAME='mois_doc' SIZE=1 CLASS='fondl' style='font-size:9px;'>";
				afficher_mois($mois);
				echo "</SELECT> ";
				echo "<SELECT NAME='annee_doc' SIZE=1 CLASS='fondl' style='font-size:9px;'>";
				afficher_annee($annee);
				echo "</SELECT><br>";
		
			}
		
			echo "<b>Description&nbsp;:</b><br>\n";
			echo "<textarea name='descriptif_document' rows='4' class='formo' style='font-size:9px;' cols='*' wrap='soft'>";
			echo htmlspecialchars($descriptif);
			echo "</textarea>\n";

			if ($type_inclus == "embed" OR $type_inclus == "image") {
			echo "<br><b>Dimensions&nbsp;:</b><br>\n";
				echo "<input type='text' name='largeur_document' class='fondl' style='font-size:9px;' value=\"$largeur\" size='5'>";
				echo " x <input type='text' name='hauteur_document' class='fondl' style='font-size:9px;' value=\"$hauteur\" size='5'> pixels";
			}
		
			echo "<div align='right'>";
			echo "<input TYPE='submit' class='fondo' style='font-size:9px;' NAME='Valider' VALUE='Valider'>";
			echo "</div>";
			echo "</form>";


		
			$link = $image_link;
			$link->addVar('redirect', $redirect_url);
			$link->addVar('hash', calculer_action_auteur("supp_doc ".$id_document));
			$link->addVar('hash_id_auteur', $connect_id_auteur);
			$link->addVar('doc_supp', $id_document);
		
			echo "</font></center>\n";
			echo "</div>";	



		
			echo "</div>";	
		
			echo "<p></p><div align='center'>";
			icone_horizontale("Supprimer ce document", $link->getUrl(), "doc-24.gif", "supprimer.gif");
			echo "</div>";
			echo fin_block();
			
		//echo "</div>\n";
		fin_cadre_enfonce();
	}

}






//
// Afficher un document dans la colonne de gauche
// (edition des articles)

function afficher_documents_colonne($id_article, $type="article", $flag_modif = true) {
	global $connect_id_auteur, $connect_statut;
	global $couleur_foncee, $couleur_claire, $options;
	global $this_link;
	
	if ($flag_modif){
		$image_link = new Link('../spip_image.php3');
		if ($id_article) $image_link->addVar('id_article', $id_article);
		
		$id_doc_actif = $id_document;
		
		
		// Ne pas afficher vignettes en tant qu'images sans docs
		//// Documents associes
		$query = "SELECT * FROM #table AS docs, spip_documents_".$type."s AS l ".
			"WHERE l.id_".$type."=$id_article AND l.id_document=docs.id_document ".
			"AND docs.mode='document' ORDER BY docs.id_document";
		
		$documents_lies = fetch_document($query);

		if ($documents_lies){
			$res = spip_query("SELECT DISTINCT id_vignette FROM spip_documents ".
				"WHERE id_document in (".join(',', $documents_lies).")");
			while ($v = mysql_fetch_object($res))
				$vignettes[] = $v->id_vignette;
		
			$docs_exclus = ereg_replace('^,','',join(',', $vignettes).','.join(',', $documents_lies));
		
			if ($docs_exclus)
				$docs_exclus = "AND l.id_document NOT IN ($docs_exclus) ";
		}
	
		//// Images sans documents
		$query = "SELECT * FROM #table AS docs, spip_documents_".$type."s AS l ".
				"WHERE l.id_".$type."=$id_article AND l.id_document=docs.id_document ".$docs_exclus.
				"AND docs.mode='vignette' ORDER BY docs.id_document";
				
		$images_liees = fetch_document($query);
		
		/// Ajouter nouvelle image
		echo "\n<p>";
		//debut_cadre_relief("image-24.gif");
		if ($images_liees) {
			reset($images_liees);
			while (list(, $id_document) = each($images_liees)) {
				afficher_case_document($id_document, $image_link, $redirect_url, $id_doc_actif == $id_document);
				//echo "<p>\n";
			}
		}
	

		debut_cadre_relief("image-24.gif", false, "creer.gif");
		
		echo "<div style='padding: 2px; background-color: $couleur_claire; text-align: center; color: black;'>";	
		echo bouton_block_invisible("ajouter_image");
		echo "<b><font size=1>AJOUTER UNE IMAGE".aide("ins_img")."</font></b>";
		echo "</div>\n";
		
		echo debut_block_invisible("ajouter_image");
		echo "<font size=1>";
		echo "<b>Vous pouvez installer des images aux formats JPEG, GIF et PNG.</b>";
		echo "</font>";
				
		$link = $image_link;
		$link->addVar('redirect', $redirect_url);
		$link->addVar('hash', calculer_action_auteur("ajout_doc"));
		$link->addVar('hash_id_auteur', $connect_id_auteur);
		$link->addVar('ajout_doc', 'oui');
		$link->addVar('mode', 'vignette');
		$link->addVar('type', $type);
		
		afficher_upload($link, 'T&eacute;l&eacute;charger depuis votre ordinateur&nbsp;:');
		echo fin_block();
	
		echo "</font>\n";
		fin_cadre_relief();
		
		//fin_cadre_relief();

		if ($type == "article") {
			echo "\n<p>";
			if ($documents_lies) {
			
				reset($documents_lies);
				while (list(, $id_document) = each($documents_lies)) {
					afficher_case_document($id_document, $image_link, $redirect_url, $id_doc_actif == $id_document);
					echo "<p>\n";
				}
			}
	
		
			if ($options == "avancees"){
				/// Ajouter nouveau document
					
				debut_cadre_enfonce("doc-24.gif", false, "creer.gif");
				echo "<div style='padding: 2px;background-color: $couleur_claire; text-align: center; color: black;'>";	
				echo bouton_block_invisible("ajouter_document");
				echo "<b><font size=1>JOINDRE UN DOCUMENT</font></b>".aide("ins_doc");
				echo "</div>\n";
				
				echo debut_block_invisible("ajouter_document");
				echo "<font size=1>";
				echo "<b>Vous pouvez joindre &agrave; votre article des documents de type&nbsp;:</b>";
				$query_types_docs = "SELECT extension FROM spip_types_documents ORDER BY extension";
				$result_types_docs = spip_query($query_types_docs);
				
				while($row=mysql_fetch_array($result_types_docs)){
					$extension=$row['extension'];
					echo "$extension, ";
				}
				echo "<b>ou installer des images &agrave; ins&eacute;rer dans le texte.</b>";
				echo "</font>";
						
				$link = $image_link;
				$link->addVar('redirect', $redirect_url);
				$link->addVar('hash', calculer_action_auteur("ajout_doc"));
				$link->addVar('hash_id_auteur', $connect_id_auteur);
				$link->addVar('ajout_doc', 'oui');
				$link->addVar('mode', 'document');
				$link->addVar('type', $type);
				
				afficher_upload($link, 'T&eacute;l&eacute;charger depuis votre ordinateur&nbsp;:');
				echo fin_block();
				
				echo "</font>\n";
				fin_cadre_enfonce();
			}
		}
	}

}


//
// Afficher un document sous forme de ligne depliable
//

function afficher_case_document($id_document, $image_link, $redirect_url = "", $deplier = false) {
	global $connect_id_auteur, $connect_statut;
	global $couleur_foncee, $couleur_claire;
	global $this_link;
	global $options;
	global $id_doublons;
	
 	$doublons = $id_doublons['documents'].",";

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
	if (!$titre) {
		$titre_fichier = "fichier : ".ereg_replace("^[^\/]*\/[^\/]*\/","",$fichier);
	}

	$result = spip_query("SELECT * FROM spip_types_documents WHERE id_type=$id_type");
	if ($type = @mysql_fetch_array($result))	{
		$type_extension = $type['extension'];
		$type_inclus = $type['inclus'];
		$type_titre = $type['titre'];
	}



	if ($mode == 'document') {
		debut_cadre_enfonce("doc-24.gif");
		//echo "<div style='border: 1px dashed #aaaaaa; padding: 0px; background-color: #e4e4e4;'>\n";
			echo "<div style='padding: 2px; background-color: #aaaaaa; text-align: left; color: black;'>";	
			echo bouton_block_invisible("doc_vignette $id_document,document $id_document");
			echo "<font size=1 face='arial,helvetica,sans-serif'>Document : </font> <b><font size=2>".propre($titre).propre($titre_fichier)."</font></b>";
			echo "</div>\n";


		if ($id_vignette) $vignette = fetch_document($id_vignette);
		if ($vignette) {
			$fichier_vignette = $vignette->get('fichier');
			$largeur_vignette = $vignette->get('largeur');
			$hauteur_vignette = $vignette->get('hauteur');
			$taille_vignette = $vignette->get('taille');
		}

		
		echo "<p></p><div style='border: 1px dashed #666666; padding: 5px; background-color: #f0f0f0;'>";
		if ($fichier_vignette) {
			echo "<div align='left'>\n";
			echo "<div align='center''>";
			$block = "doc_vignette $id_document";
			echo texte_vignette_document($largeur_vignette, $hauteur_vignette, $fichier_vignette, "$fichier");
			echo "</div>";
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
			echo "<center><font face='Verdana,Arial,Helvetica,sans-serif'><b>[<a ".$link->getHref().">supprimer la vignette</a>]</b></font></center>\n";
			echo fin_block();
			echo "</div>\n";
		}
		else {
			// pas de vignette
			echo "<div align='center'>\n";
			$block = "doc_vignette $id_document";
			list($icone, $largeur_icone, $hauteur_icone) = vignette_par_defaut($type_extension);
			if ($icone) {
				echo "<a href='../$fichier'><img src='$icone' border=0 width='$largeur_icone' align='top' height='$hauteur_icone'></a>\n";
			}
			echo "</div>\n";
			echo "<font size='2'>\n";

			echo "<div align='left'>\n";
			$hash = calculer_action_auteur("ajout_doc");

			$link = $image_link;
			$link->addVar('redirect', $redirect_url);
			$link->addVar('hash', calculer_action_auteur("ajout_doc"));
			$link->addVar('hash_id_auteur', $connect_id_auteur);
			$link->addVar('ajout_doc', 'oui');
			$link->addVar('id_document', $id_document);
			$link->addVar('mode', 'vignette');
			
			if ($options == 'avancees'){
				echo debut_block_invisible($block);
				echo "<b>Vignette par d&eacute;faut</b>";
	
				
				echo "<p></p><div><font size=1>";
				afficher_upload($link, 'Remplacer la vignette par d&eacute;faut par un logo personnalis&eacute;&nbsp;:', 'image', false);
				echo "</font></div>";
				echo fin_block();
			}
			echo "</div>\n";
		}
		echo "</div>";
			
			
		if (!ereg(",$id_document,", "$doublons")) {
			echo "<div style='padding:2px;'><font size=1 face='arial,helvetica,sans-serif'>";
			if (($type_inclus == "embed" OR $type_inclus == "image") AND $largeur > 0 AND $hauteur > 0) {
				echo "<b>Inclusion de la vignette&nbsp;:</b></br>";
			}
			echo "<font color='333333'><div align=left>&lt;doc$id_document|left&gt;</div><div align=center>&lt;doc$id_document|center&gt;</div><div align=right>&lt;doc$id_document|right&gt;</div></font>\n";
			echo "</font></div>";
			
			if (($type_inclus == "embed" OR $type_inclus == "image") AND $largeur > 0 AND $hauteur > 0) {
				echo "<div style='padding:2px;'><font size=1 face='arial,helvetica,sans-serif'>";
				echo "<b>Inclusion directe&nbsp;:</b></br>";
				echo "<font color='333333'><div align=left>&lt;emb$id_document|left&gt;</div><div align=center>&lt;emb$id_document|center&gt;</div><div align=right>&lt;emb$id_document|right&gt;</div></font>\n";
				echo "</font></div>";
			}
		}

		$block = "document $id_document";

		echo debut_block_invisible($block);
		if (ereg(",$id_document,", "$doublons")) {
			echo "<div style='padding:2px;'><font size=1 face='arial,helvetica,sans-serif'>";
			echo "<div align=center>&lt;doc$id_document&gt;</div>\n";
			echo "</font></div>";
		}
		echo "<div style='border: 1px dashed #666666; padding: 0px; background-color: #f0f0f0;'>";	
			
			
			
			echo "<div style='padding: 5px;'>";	
			if (strlen($descriptif)>0) echo propre($descriptif)."<br>";
			

			if ($type_titre){
				echo "$type_titre";
			} else {
				echo "Document ".majuscules($type_extension);
			}
			echo " : <a href='../$fichier'>".taille_en_octets($taille)."</a>";

			$link = new Link($redirect_url);
			$link->addVar('modif_document', 'oui');
			$link->addVar('id_document', $id_document);
			echo $link->getForm('POST');
		
			echo "<b>Titre du document&nbsp;:</b><br>\n";
			echo "<input type='text' name='titre_document' class='formo' style='font-size:9px;' value=\"".htmlspecialchars($titre)."\" size='40'><br>";
		
			echo "<b>Description&nbsp;:</b><br>\n";
			echo "<textarea name='descriptif_document' rows='4' class='formo' style='font-size:9px;' cols='*' wrap='soft'>";
			echo htmlspecialchars($descriptif);
			echo "</textarea>\n";
			
			if ($type_inclus == "embed" OR $type_inclus == "image") {
			echo "<br><b>Dimensions&nbsp;:</b><br>\n";
				echo "<input type='text' name='largeur_document' class='fondl' style='font-size:9px;' value=\"$largeur\" size='5'>";
				echo " x <input type='text' name='hauteur_document' class='fondl' style='font-size:9px;' value=\"$hauteur\" size='5'> pixels";
			}
		
			echo "<div align='right'>";
			echo "<input TYPE='submit' class='fondo' style='font-size:9px;' NAME='Valider' VALUE='Valider'>";
			echo "</div>";
			echo "</form>";


		
			$link = $image_link;
			$link->addVar('redirect', $redirect_url);
			$link->addVar('hash', calculer_action_auteur("supp_doc ".$id_document));
			$link->addVar('hash_id_auteur', $connect_id_auteur);
			$link->addVar('doc_supp', $id_document);
		
			echo "</font></center>\n";
			echo "</div>";	



		
			echo "</div>";	
		
			echo "<p></p><div align='center'>";
			icone_horizontale("Supprimer ce document", $link->getUrl(), "doc-24.gif", "supprimer.gif");
			echo "</div>";
			echo fin_block();
			
		//echo "</div>\n";
		fin_cadre_enfonce();
	}

	else if ($mode == 'vignette') {
		//echo "<div style='border: 1px dashed #aaaaaa; padding: 4px; background-color: #f0f0f0;'>\n";
		debut_cadre_relief("image-24.gif");

		$block = "image $id_document";
		echo "<div style='padding: 2px; background-color: #e4e4e4; text-align: left; color: black;'>";	
		echo bouton_block_invisible("$block");
		echo "<font size=1 face='arial,helvetica,sans-serif'>Image : </font> <b><font size=2>".propre($titre).propre($titre_fichier)."</font></b>";
		echo "</div>\n";


	
		//
		// Preparer le raccourci a afficher sous la vignette ou sous l'apercu
		//
		
		if (!ereg(",$id_document,", "$doublons")) {
			$raccourci_doc = "<div><font size='1' color='#666666' face='arial,helvetica,sans-serif'>";
			if (strlen($descriptif) > 0 OR strlen($titre) > 0) {
				$raccourci_doc .= "<div align='left'>&lt;doc$id_document|left&gt;</div>\n".
					"<div align='center'>&lt;doc$id_document|center&gt;</div>\n".
					"<div align='right'>&lt;doc$id_document|right&gt;</div>\n";
			} else {
				$raccourci_doc .= "<div align='left'>&lt;img$id_document|left&gt;</div>\n".
					"<div align='center'>&lt;img$id_document|center&gt;</div>\n".
					"<div align='right'>&lt;img$id_document|right&gt;</div>\n";
			}
			$raccourci_doc .= "</font></div>\n";
		} else {
			$raccourci_doc = "<div><font size='1' color='#666666' face='arial,helvetica,sans-serif'>";
			$raccourci_doc .= "<div align='center'>&lt;img$id_document&gt;</div>\n";
			$raccourci_doc .= "</font></div>\n";
			
		}

		//
		// Afficher un apercu (pour les images)
		//


		if ($type_inclus == 'image') {
			echo "<div style='text-align: center; padding: 2px;'>\n";
			echo texte_vignette_document($largeur, $hauteur, $fichier,"");
			echo "</div>\n";
			echo "<font face='Verdana,Arial,Helvetica,sans-serif' size='2'>";
			if (strlen($descriptif)>0)
				echo propre($descriptif);
			
			if (!ereg(",$id_document,", "$doublons")) echo $raccourci_doc;
		}
	
		echo debut_block_invisible($block);
			if (ereg(",$id_document,", "$doublons")) echo $raccourci_doc;
			echo "\n<div align='center'><font face='Verdana,Arial,Helvetica,sans-serif' size='1'>$largeur x $hauteur pixels<br></font></div>\n";

			$link = new Link($redirect_url);
			$link->addVar('modif_document', 'oui');
			$link->addVar('id_document', $id_document);
			echo $link->getForm('POST');
		
			echo "<p></p><div class='iconeoff'>";	
			echo "<b>Titre de l'image&nbsp;:</b><br>\n";
			echo "<input type='text' name='titre_document' class='formo' style='font-size:9px;' value=\"".htmlspecialchars($titre)."\" size='40'><br>";
		
			echo "<b>Description&nbsp;:</b><br>\n";
			echo "<textarea name='descriptif_document' rows='4' class='formo' cols='*' style='font-size:9px;' wrap='soft'>";
			echo htmlspecialchars($descriptif);
			echo "</textarea>\n";
		
			echo "<div align='right'>";
			echo "<input class='fondo' style='font-size: 9px;' TYPE='submit' NAME='Valider' VALUE='Valider'>";
			echo "</div>";
			echo "</div>";
			echo "</form>";

			echo "<center>";
			$link = $image_link;
			$link->addVar('redirect', $redirect_url);
			$link->addVar('hash', calculer_action_auteur("supp_doc ".$id_document));
			$link->addVar('hash_id_auteur', $connect_id_auteur);
			$link->addVar('doc_supp', $id_document);
			icone_horizontale ("Supprimer cette image", $link->getUrl(), "image-24.gif", "supprimer.gif");
			echo "</center>\n";


			echo "<font size='1'>";

			
			if ($options != 'avancees'){
				debut_cadre_enfonce("doc-24.gif", false, "creer.gif");
				echo "<div style='padding: 5px; background-color: #999999; text-align: center; color: white;'>";	
				echo "<b><font size=1>JOINDRE UN DOCUMENT</font></b>";
				echo "</div>\n";
				
				echo "<div>";	
	
	
				$link = $image_link;
				$link->addVar('redirect', $redirect_url);
				$link->addVar('hash', calculer_action_auteur("ajout_doc"));
				$link->addVar('hash_id_auteur', $connect_id_auteur);
				$link->addVar('doc_vignette', $id_document);
				$link->addVar('titre_vignette', $titre);
				$link->addVar('descriptif_vignette', $descriptif);
				$link->addVar('joindre_doc', 'oui');
				
				afficher_upload($link, 'Vous pouvez associer un document &agrave; cette image&nbsp;:','',false);
	
				echo "</div>";
			fin_cadre_enfonce();
			}
			

		echo fin_block();

		
		//echo "</div>";
		fin_cadre_relief();
		
	}
}


?>