<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_DOCUMENTS")) return;
define("_ECRIRE_INC_DOCUMENTS", "1");

include_ecrire ("inc_objet.php3");


//
// Vignette pour les documents lies
//

function vignette_par_defaut($type_extension) {
	if ($GLOBALS['flag_ecrire'])
		$img = "../IMG/icones";
	else
		$img = "IMG/icones";

	$filename = "$img/$type_extension";

	// Glurps !
	if (file_exists($filename.'.png')) {
		$vig = "$filename.png";
	}
	else if (file_exists($filename.'.gif')) {
		$vig = "$filename.gif";
	}
	else if (file_exists($filename.'-dist.png')) {
		$vig = "$filename-dist.png";
	}
	else if (file_exists($filename.'-dist.gif')) {
		$vig = "$filename-dist.gif";
	}
	else if (file_exists("$img/defaut.png")) {
		$vig = "$img/defaut.png";
	}
	else if (file_exists("$img/defaut.gif")) {
		$vig = "$img/defaut.gif";
	}
	else if (file_exists("$img/defaut-dist.png")) {
		$vig = "$img/defaut-dist.png";
	}
	else if (file_exists("$img/defaut-dist.gif")) {
		$vig = "$img/defaut-dist.gif";
	}

	if ($size = @getimagesize($vig)) {
		$largeur = $size[0];
		$hauteur = $size[1];
	}

	return array($vig, $largeur, $hauteur);
}


//
// Integration (embed) multimedia
//

function embed_document($id_document, $les_parametres="", $afficher_titre=true) {
	global $id_doublons;

	$id_doublons['documents'] .= ",$id_document";


	if ($les_parametres) {
		$parametres = explode("|",$les_parametres);
		
		for ($i = 0; $i < count($parametres); $i++) {
			$parametre = $parametres[$i];
			
			if (eregi("^left|right|center$", $parametre)) {
				$align = $parametre;
			}
			else {
				$params[] = $parametre;
			}
		}
	}

	$query = "SELECT * FROM spip_documents WHERE id_document = $id_document";
	$result = spip_query($query);
	if ($row = mysql_fetch_array($result)) {
		$id_document = $row['id_document'];
		$id_type = $row['id_type'];
		$titre = propre($row ['titre']);
		$descriptif = propre($row['descriptif']);
		$fichier = $row['fichier'];
		$largeur = $row['largeur'];
		$hauteur = $row['hauteur'];
		$taille = $row['taille'];
		$mode = $row['mode'];


		$query_type = "SELECT * FROM spip_types_documents WHERE id_type=$id_type";
		$result_type = spip_query($query_type);
		if ($row_type = @mysql_fetch_array($result_type)) {
			$type = $row_type['titre'];
			$inclus = $row_type['inclus'];
			$extension = $row_type['extension'];
		}
		else $type = 'fichier';

		// ajuster chemin d'acces au fichier
		if ($GLOBALS['flag_ecrire']) {
			if ($fichier) $fichier = "../$fichier";
		}
		// Pour RealVideo
		if ((!ereg("^controls", $les_parametres)) AND (ereg("^(rm|ra|ram)$", $extension))) {
			$real = true;
		}

		if ($inclus == "embed" AND !$real) {
		
				for ($i = 0; $i < count($params); $i++) {
					if (ereg("([^\=]*)\=([^\=]*)", $params[$i], $vals)){
						$nom = $vals[1];
						$valeur = $vals[2];
						$inserer_vignette .= "<param name='$nom' value='$valeur'>";
						$param_emb .= " $nom='$valeur'";
						if ($nom == "controls" AND $valeur == "PlayButton") { 
							$largeur = 40;
							$hauteur = 25;
						}
						else if ($nom == "controls" AND $valeur == "PositionSlider") { 
							$largeur = $largeur - 40;
							$hauteur = 25;
						}
					}
				}
				
				$vignette = "<object width='$largeur' height='$hauteur'>";
				$vignette .= "<param name='movie' value='$fichier'>";
				$vignette .= "<param name='src' value='$fichier'>";
				$vignette .= $inserer_vignette;
		
				$vignette .= "<embed src='$fichier' $param_emb width='$largeur' height='$hauteur'></embed></object>";
		
		}
		else if ($inclus == "embed" AND $real) {
			$vignette .= embed_document ($id_document, "controls=ImageWindow|type=audio/x-pn-realaudio-plugin|console=Console$id_document|nojava=true|$les_parametres", false);
			$vignette .= "<br>";
			$vignette .= embed_document ($id_document, "controls=PlayButton|type=audio/x-pn-realaudio-plugin|console=Console$id_document|nojava=true|$les_parametres", false);
			$vignette .= embed_document ($id_document, "controls=PositionSlider|type=audio/x-pn-realaudio-plugin|console=Console$id_document|nojava=true|$les_parametres", false);
		}
		else if ($inclus == "image") {
			$fichier_vignette = $fichier;
			$largeur_vignette = $largeur;
			$hauteur_vignette = $hauteur;
			if ($fichier_vignette) {
				$vignette = "<img src='$fichier_vignette' border=0";
				if ($largeur_vignette && $hauteur_vignette)
					$vignette .= " width='$largeur_vignette' height='$hauteur_vignette'";
				if ($titre) {
					$titre_ko = ($taille > 0) ? ($titre . " - ". taille_en_octets($taille)) : $titre;
					$vignette .= " alt=\"$titre_ko\" title=\"$titre_ko\"";
				}
				$vignette .= ">";
			}
		}
		
		if ($afficher_titre) {
			$retour = "<table cellpadding=5 cellspacing=0 border=0 align='$align'>\n";
			$retour .= "<tr><td align='center'>\n<div class='spip_documents'>\n";
			$retour .= $vignette;

			if ($titre) $retour .= "<br><b>$titre</b>";
			if ($descriptif) $retour .= "<br>$descriptif";

			$retour .= "</div>\n</td></tr>\n</table>\n";
		}
		else {
			$retour = $vignette;
		}

		return $retour;		

	}
}


//
// Integration des images et documents
//

function integre_image($id_document, $align, $type_aff = 'IMG') {
	global $id_doublons;
	
	$id_doublons['documents'] .= ",$id_document";
	
	$query = "SELECT * FROM spip_documents WHERE id_document = $id_document";
	$result = spip_query($query);
	if ($row = mysql_fetch_array($result)) {
		$id_document = $row['id_document'];
		$id_type = $row['id_type'];
		$titre = typo($row['titre']);
		$descriptif = propre($row['descriptif']);
		$fichier = $row['fichier'];
		$largeur = $row['largeur'];
		$hauteur = $row['hauteur'];
		$taille = $row['taille'];
		$mode = $row['mode'];
		$id_vignette = $row['id_vignette'];

		// type d'affichage : IMG, DOC
		$affichage_detaille = (strtoupper($type_aff) == 'DOC');

		// on construira le lien en fonction du type de doc
		$result_type = spip_query("SELECT * FROM spip_types_documents WHERE id_type = $id_type");
		if ($type = @mysql_fetch_object($result_type)) {
			$extension = $type->extension;
		}

		// recuperer la vignette pour affichage inline
		if ($id_vignette) {
			$query_vignette = "SELECT * FROM spip_documents WHERE id_document = $id_vignette";
			$result_vignette = spip_query($query_vignette);
			if ($row_vignette = @mysql_fetch_array($result_vignette)) {
				$fichier_vignette = $row_vignette['fichier'];
				$largeur_vignette = $row_vignette['largeur'];
				$hauteur_vignette = $row_vignette['hauteur'];
			}
		}
		else if ($mode == 'vignette') {
			$fichier_vignette = $fichier;
			$largeur_vignette = $largeur;
			$hauteur_vignette = $hauteur;
		}

		// ajuster chemin d'acces au fichier
		if ($GLOBALS['flag_ecrire']) {
			if ($fichier) $fichier = "../$fichier";
			if ($fichier_vignette) $fichier_vignette = "../$fichier_vignette";
		}

		// si pas de vignette, utiliser la vignette par defaut du type du document
		if (!$fichier_vignette) {
			list($fichier_vignette, $largeur_vignette, $hauteur_vignette) = vignette_par_defaut($extension);
		}

		if ($fichier_vignette) {
			$vignette = "<img src='$fichier_vignette' border=0";
			if ($largeur_vignette && $hauteur_vignette)
				$vignette .= " width='$largeur_vignette' height='$hauteur_vignette'";
			if ($titre) {
				$titre_ko = ($taille > 0) ? ($titre . " - ". taille_en_octets($taille)) : $titre;
				$vignette .= " alt=\"$titre_ko\" title=\"$titre_ko\"";
			}
			if ($affichage_detaille)
				$vignette .= ">";
			else {
				if ($align) $vignette .= " align='$align'";
				$vignette .= " hspace='5' vspace='3'>";
				if ($align == 'center') $vignette = "<p align='center'>$vignette</p>";
			}
		}

		if ($mode == 'document')
			$vignette = "<a href='$fichier'>$vignette</a>";

		// si affichage detaille ('DOC'), ajouter une legende
		if ($affichage_detaille) {
			$query_type = "SELECT * FROM spip_types_documents WHERE id_type=$id_type";
			$result_type = spip_query($query_type);
			if ($row_type = @mysql_fetch_array($result_type)) {
				$type = $row_type['titre'];
			}
			else $type = 'fichier';

			$retour = "<table cellpadding=5 cellspacing=0 border=0 align='$align'>\n";
			$retour .= "<tr><td align='center'>\n<div class='spip_documents'>\n";
			$retour .= $vignette;

			if ($titre) $retour .= "<br><b>$titre</b>";
			if ($descriptif) $retour .= "<br>$descriptif";
			
			if ($mode == 'document')
				$retour .= "<br>(<a href='$fichier'>$type, ".taille_en_octets($taille)."</a>)";

			$retour .= "</div>\n</td></tr>\n</table>\n";
		}
		else $retour = $vignette;
	}
	return $retour;
}




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


//
// Afficher un formulaire d'upload
//

function afficher_upload($link, $intitule, $inclus = '', $afficher_texte_ftp = true, $forcer_document = false, $dossier_complet = false) {
	global $clean_link, $connect_statut;

	if (!$link->getVar('redirect')) {
		$link->addVar('redirect', $clean_link->getUrl());
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
	global $clean_link;
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
	
			if ($type == "article") echo propre("<font size=2
			face='Verdana,Arial,Helvetica,sans-serif'>Les documents suivants
			sont associ&eacute;s &agrave; votre article, mais ils n'y ont
			pas &eacute;t&eacute; directement ins&eacute;r&eacute;s. Ils
			appara&icirc;tront donc sous forme de &laquo;documents
			joints&raquo; (remarque: il se peut que ce site n'ait pas
			&eacute;t&eacute; programm&eacute; pour afficher les documents
			joints - dans ce cas ils n'appara&icirc;tront pas du tout).</font>");

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
			if ($type == "article") echo typo("<b> ces documents pourront &ecirc;tre par la suite ins&eacute;r&eacute;s <i>&agrave; l'int&eacute;rieur</i> du texte si vous le d&eacute;sirez (&laquo;Modifier cet article&raquo; pour acc&eacute;der &agrave; cette option), ou affich&eacute;s hors du texte de l'article.</b>");
	
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
						echo '<p>'.propre("La cr&eacute;ation automatique de vignettes de pr&eacute;visualisation est d&eacute;sactiv&eacute;e sur ce site (r&eacute;glage sur la page &laquo;[Configuration du site / contenu->config-contenu.php3]&raquo;). Cette fonction facilite la mise en ligne d'un portfolio (collection de photographies pr&eacute;sent&eacute;es sous forme de vignettes cliquables).");
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
	global $clean_link;
	global $options, $id_document_deplie;


	if ($GLOBALS['id_document'] > 0) {
		$id_document_deplie = $GLOBALS['id_document'];
	}
	if ($id_document == $id_document_deplie) $flag_deplie = true;
	
	if (!$redirect_url) $redirect_url = $clean_link->getUrl();

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
		echo "<div style='padding: 2px; background-color: #aaaaaa; text-align: left; color: black;'>";	
		if ($flag_deplie) echo bouton_block_visible("doc_vignette $id_document,document $id_document");
		else echo bouton_block_invisible("doc_vignette $id_document,document $id_document");
		
		echo "<font size=1 face='arial,helvetica,sans-serif'>Document : </font> <b><font size=2>".typo($titre_aff)."</font></b>";
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
			if ($flag_deplie) echo debut_block_visible($block);
			else  echo debut_block_invisible($block);

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
				if ($flag_deplie) echo debut_block_visible($block);
				else  echo debut_block_invisible($block);
			
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

		if ($flag_deplie) echo debut_block_visible($block);
		else  echo debut_block_invisible($block);
		
		echo "<p></p><div style='border: 1px solid #666666; padding: 0px; background-color: #f0f0f0;'>";	

		echo "<div style='padding: 5px;'>";	
		if (strlen($descriptif)>0) echo propre($descriptif)."<br>";

		if ($type_titre)
			echo "$type_titre";
		else 
			echo "Document ".majuscules($type_extension);
		echo " : <a href='../$fichier'>".taille_en_octets($taille)."</a>";

		$link = new Link($redirect_url);
		$link->addVar('modif_document', 'oui');
		$link->addVar('id_document', $id_document);
		echo $link->getForm('POST');

		echo "<b>Titre du document&nbsp;:</b><br>\n";
		echo "<input type='text' name='titre_document' class='formo' style='font-size:9px;' value=\"".entites_html($titre)."\" size='40'><br>";

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
		echo entites_html($descriptif);
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


		$link_supp = $image_link;
		$link_supp->addVar('redirect', $redirect_url);
		$link_supp->addVar('hash', calculer_action_auteur("supp_doc ".$id_document));
		$link_supp->addVar('hash_id_auteur', $connect_id_auteur);
		$link_supp->addVar('doc_supp', $id_document);

		echo "</font></center>\n";
		echo "</div>";
		echo "</div>";

		echo "<p></p><div align='center'>";
		icone_horizontale("Supprimer ce document", $link_supp->getUrl(), "doc-24.gif", "supprimer.gif");
		echo "</div>";
		echo fin_block();

		fin_cadre_enfonce();
	}

}






//
// Afficher un document dans la colonne de gauche
// (edition des articles)

function afficher_documents_colonne($id_article, $type="article", $flag_modif = true) {
	global $connect_id_auteur, $connect_statut;
	global $couleur_foncee, $couleur_claire, $options;
	global $clean_link;
	
	
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
			global $descriptif, $texte, $chapo;
			$pour_documents_doublons = propre("$descriptif$texte$chapo");

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


?>