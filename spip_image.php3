<?php

include ("ecrire/inc_version.php3");

include_ecrire("inc_connect.php3");
include_ecrire("inc_meta.php3");
include_ecrire("inc_admin.php3");
include_local("inc-cache.php3");


/* ResizeGif with (height % width) */
function RatioResizeImg( $image, $newWidth, $newHeight){ 

	if (function_exists("imagejpeg")){

		//Open the jpg file to resize 
		$srcImage = @ImageCreateFromJPEG( $image );		 
		
		//obtain the original image Height and Width 
		$srcWidth = ImageSX( $srcImage ); 
		$srcHeight = ImageSY( $srcImage ); 
		
		
		
		// the follwing portion of code checks to see if 
		// the width > height or if width < height 
		// if so it adjust accordingly to make sure the image 
		// stays smaller then the $newWidth and $newHeight 
		
		$ratioWidth = $srcWidth/$newWidth;
		$ratioHeight = $srcHeight/$newHeight;
		
		if( $ratioWidth < $ratioHeight){ 
			$destWidth = $srcWidth/$ratioHeight;
			$destHeight = $newHeight; 
		}else{ 
			$destWidth = $newWidth; 
			$destHeight = $srcHeight/$ratioWidth; 
		} 
		
		
		// creating the destination image with the new Width and Height 
		$destImage = imagecreate( $destWidth, $destHeight); 
		
		//copy the srcImage to the destImage 
		ImageCopyResized( $destImage, $srcImage, 0, 0, 0, 0, $destWidth, $destHeight, $srcWidth, $srcHeight ); 
		
		$destination = ereg_replace('\.(.*)$','-s.\1',$image);
		//Header("Content-type: image/jpeg");
		ImageJPEG($destImage, "$destination", 40);
	
		/*
		//create the gif 
		//ImageGif( $destImage ); 
		  if (function_exists("imagegif")) {
			Header("Content-type: image/gif");
			$fonction = ImageGIF($destImage);
		  }
		  elseif (function_exists("imagejpeg")) {
			Header("Content-type: image/jpeg");
			ImageJPEG($srcImage, "", 0.5);
		  }
		  elseif (function_exists("imagepng")) {
			Header("Content-type: image/png");
			ImagePNG($destImage);
		  }
		  */
		
		
		//fre the memory used for the images 
		ImageDestroy( $srcImage ); 
		ImageDestroy( $destImage ); 
	
		$retour['width'] = $destWidth;
		$retour['height'] = $destHeight;
		$retour['fichier'] = $destination;
		return $retour;
	}

}


//write $resizedImage to Database, file , echo to browser whatever you need to do with it




//
// Deplacer un fichier uploade
//

function deplacer_fichier_upload($source, $dest) {
	// Securite
	if (strstr($dest, "..")) {
		exit;
	}

	$ok = @copy($source, $dest);
	if (!$ok) $ok = @move_uploaded_file($source, $dest);
	if ($ok)
		@chmod($dest, '0666');
/*	else {
		$f = @fopen($dest,'w');
		if ($f)
			fclose ($f);
		else {
			@header ("Location: spip_test_dirs.php3?test_dir=".dirname($dest));
			exit;
		}
	}*/

	return $ok;
}


//
// Convertit le type numerique retourne par getimagesize() en extension fichier
//

function decoder_type_image($type, $strict = false) {
	switch ($type) {
	case 1:
		return "gif";
	case 2:
		return "jpg";
	case 3:
		return "png";
	case 4:
		return $strict ? "" : "swf";
	case 5:
		return "psd";
	case 6:
		return "bmp";
	case 7:
	case 8:
		return "tif";
	default:
		return "";
	}
}


//
// Corrige l'extension du fichier dans quelques cas particuliers
//

function corriger_extension($ext) {
	switch ($ext) {
	case 'htm':
		return 'html';
	case 'jpeg':
		return 'jpg';
	case 'tiff':
		return 'tif';
	default:
		return $ext;
	}
}


//
// Ajouter une image (logo)
//

function ajout_image($source, $dest) {
	global $redirect_url, $hash_id_auteur, $hash, $num_img;

	// Securite
	if (!verifier_action_auteur("ajout_image $dest", $hash, $hash_id_auteur)) {
		exit;
	}

	$loc = "IMG/$dest";
	if (!deplacer_fichier_upload($source, $loc)) return;

	// analyse le type de l'image (on ne fait pas confiance au nom de
	// fichier envoye par le browser : pour les Macs c'est plus sur)
	$size = @getimagesize($loc);
	$type = decoder_type_image($size[2], true);

	if ($type) {
		rename($loc, "$loc.$type");
		$dest = "$dest.$type";
		$loc = "$loc.$type";
	}
	else {
		unlink($loc);
	}
}


//
// Ajouter un document
//

function ajout_doc($orig, $source, $dest, $mode, $id_document, $doc_vignette='', $titre_vignette='', $descriptif_vignette='') {
	global $hash_id_auteur, $hash, $id_article;

	//
	// Securite
	//
	if (!verifier_action_auteur("ajout_doc", $hash, $hash_id_auteur)) {
		exit;
	}

	if (ereg("\.([^.]+)$", $orig, $match)) {
		$ext = addslashes(strtolower($match[1]));
		$ext = corriger_extension($ext);
	}
	$query = "SELECT * FROM spip_types_documents WHERE extension='$ext' AND upload='oui'";

	if ($mode == 'vignette')
		$query .= " AND inclus='image'";

	$result = mysql_query($query);
	if ($row = @mysql_fetch_array($result)) {
		$id_type = $row['id_type'];
		$type_inclus = $row['inclus'];
	}
	else return false;

	//
	// Preparation
	//

	if ($mode == 'vignette') {
		$id_document_lie = $id_document;
		$query = "UPDATE spip_documents SET mode='document' where id_document=$id_document_lie";
		mysql_query($query); // requete inutile a mon avis (Fil)...
		$id_document = 0;
	}
	if (!$id_document) {
		$query = "INSERT spip_documents (id_type, titre) VALUES ($id_type, 'sans titre')";
		mysql_query($query);
		$id_document = mysql_insert_id();
		$nouveau = true;
		if ($id_article) {
			$query = "INSERT spip_documents_articles (id_document, id_article) VALUES ($id_document, $id_article)";
			mysql_query($query);
		}
	}
	if (!$dest) {
		if ($id_document_lie)
			$dest = "doc-$id_document_lie-prv";
		else
			$dest = "doc-$id_document";
	}
	$dest = ereg_replace("\.([^.]+)$", "", $dest) . ".$ext";

	if (creer_repertoire("IMG", $ext))
		$dest_path = "IMG/$ext/$dest";
	else
		$dest_path = "IMG/$dest";

	if (!deplacer_fichier_upload($source, $dest_path)) return false;

	// Creer une vignette automatiquement
	$creer_preview=lire_meta("creer_preview");
	$taille_preview=lire_meta("taille_preview");
	if ($taille_preview < 15) $taille_preview = 120;

	if ($mode == 'document' AND ereg("\.jpg$",$dest_path) AND $creer_preview == 'oui') {

		$preview = RatioResizeImg($dest_path, $taille_preview, $taille_preview);
		$hauteur_prev = $preview['height'];
		$largeur_prev = $preview['width'];
		$fichier_prev = $preview['fichier'];
		$query = "INSERT spip_documents (id_type, titre, largeur, hauteur, fichier) VALUES ('1', 'vignette', '$largeur_prev', '$hauteur_prev', '$fichier_prev')";
		mysql_query($query);
		$id_preview = mysql_insert_id();
		$query = "UPDATE spip_documents SET id_vignette = '$id_preview' WHERE id_document = $id_document";
		mysql_query($query);
	}

	//
	// Recopier le fichier
	//

	$size_image = getimagesize($dest_path);
	$type_image = decoder_type_image($size_image[2]);
	if ($type_image) {
		$largeur = $size_image[0];
		$hauteur = $size_image[1];
	}
	$taille = filesize($dest_path);

	if ($nouveau) {
		if (!$mode) $mode = ($type_image AND $type_inclus == 'image') ? 'vignette' : 'document';
		$titre = ereg_replace("\..*$", "", $orig);
		$titre = ereg_replace("ecrire/|upload/", "", $titre);
		$titre = strtr($titre, "_", " ");
		$update = "mode='$mode', titre='".addslashes($titre)."', ";
	}

	$query = "UPDATE spip_documents SET $update taille='$taille', largeur='$largeur', hauteur='$hauteur', fichier='$dest_path' ".
		"WHERE id_document=$id_document";
	mysql_query($query);

	if ($id_document_lie) {
		$query = "UPDATE spip_documents SET id_vignette=$id_document WHERE id_document=$id_document_lie";
		mysql_query($query);
		$id_document = $id_document_lie; // pour que le 'return' active le bon doc.
	}
	
	if ($doc_vignette){
		$query = "UPDATE spip_documents SET id_vignette=$doc_vignette, titre='$titre', descriptif='$descriptif' WHERE id_document=$id_document";
		mysql_query($query);
	
	}


	return $id_document;
}



// image_name n'est valide que par POST http, mais pas par la methode ftp/upload
// par ailleurs, pour un fichier ftp/upload, il faut effacer l'original nous-memes
if (!$image_name AND $image2) {
	$image = "ecrire/upload/".$image2;
	$image_name = $image;
	$supprimer_ecrire_upload = $image;
} else {
	$supprimer_ecrire_upload = '';
}

//
// ajouter un document
//
if ($ajout_doc == 'oui') {
	if ($forcer_document == 'oui')
		$id_document = ajout_doc($image_name, $image, $fichier, "document", $id_document);
	else
		$id_document = ajout_doc($image_name, $image, $fichier, $mode, $id_document);
}


// joindre un document
if ($joindre_doc == 'oui'){
	$id_document = ajout_doc($image_name, $image, $fichier, "document", $id_document, $doc_vignette, $titre_vignette, $descriptif_vignette);
}


//
// ajouter un logo
//
if ($ajout_logo == "oui") {
	ajout_image($image, $logo);
}

//
// supprimer un logo
//
if ($image_supp) {
	// Securite
	if (strstr($image_supp, "..")) {
		exit;
	}
	if (!verifier_action_auteur("supp_image $image_supp", $hash, $hash_id_auteur)) {
		exit;
	}
	@unlink("IMG/$image_supp");
}

//
// supprimer un doc
//
if ($doc_supp) {
	// Securite
	if (!verifier_action_auteur("supp_doc $doc_supp", $hash, $hash_id_auteur)) {
		exit;
	}
	$query = "SELECT fichier FROM spip_documents WHERE id_document=$doc_supp";
	$result = mysql_query($query);
	if ($row = mysql_fetch_array($result)) {
		$fichier = $row['fichier'];
		mysql_query("DELETE FROM spip_documents WHERE id_document=$doc_supp");
		mysql_query("UPDATE spip_documents SET id_vignette=0 WHERE id_vignette=$doc_supp");
		mysql_query("DELETE FROM spip_documents_articles WHERE id_document=$doc_supp");
		unlink($fichier);
	}
}


// supprimer le fichier original si pris dans ecrire/upload
/*if ($supprimer_ecrire_upload)
	@unlink ($supprimer_ecrire_upload);*/

//
// redirection
//
if ($HTTP_POST_VARS) $vars = $HTTP_POST_VARS;
else $vars = $HTTP_GET_VARS;
$redirect_url = "ecrire/" . $vars["redirect"];
$link = new Link($redirect_url);
reset($vars);
while (list ($key, $val) = each ($vars)) {
	if (!ereg("^(redirect|image.*|hash.*|ajout.*|doc.*|transformer.*)$", $key)) {
		$link->addVar($key, $val);
	}
}
if ($id_document)
	$link->addVar('id_document',$id_document);

@header ("Location: ".$link->getUrl());

exit;
?>