<?php

include ("ecrire/inc_version.php3");

include_ecrire("inc_filtres.php3");
include_ecrire("inc_charsets.php3");
include_ecrire("inc_meta.php3");
include_ecrire("inc_admin.php3");
include_local("inc-cache.php3");



$taille_preview = lire_meta("taille_preview");
if ($taille_preview < 10) $taille_preview = 120;


if ($test_vignette) {
// verifier les formats acceptes par GD
	if ($test_vignette == "gd1") {
		$gd_formats = Array();
		if (function_exists('ImageCreateFromJPEG')) {
			$srcImage = @ImageCreateFromJPEG("IMG/test.jpg");
			if ($srcImage) {
				$gd_formats[] = "jpg";
				ImageDestroy( $srcImage );
			}
		}
		if (function_exists('ImageCreateFromGIF')) {
			$srcImage = @ImageCreateFromGIF("IMG/test.gif");
			if ($srcImage) {
				$gd_formats[] = "gif";
				ImageDestroy( $srcImage );
			}
		}
		if (function_exists('ImageCreateFromPNG')) {
			$srcImage = @ImageCreateFromPNG("IMG/test.png");
			if ($srcImage) {
				$gd_formats[] = "png";
				ImageDestroy( $srcImage );
			}
		}

		if ($gd_formats) $gd_formats = join(",", $gd_formats);
		ecrire_meta("gd_formats", $gd_formats);
		ecrire_metas();
	}

	// et maintenant envoyer la vignette de tests
	if (ereg("^(gd1|gd2|imagick|convert)$", $test_vignette)) {
		include_ecrire('inc_logos.php3');
		if ($preview = creer_vignette('IMG/test_image.jpg', $taille_preview, $taille_preview, 'jpg', "IMG/test_$test_vignette", $test_vignette, true))
			@header('Location: IMG/test_'.$test_vignette.'.'.$preview['format']);
	}
	exit;
}






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
		@chmod($dest, 0666);
	else {
		$f = @fopen($dest,'w');
		if ($f)
			fclose ($f);
		else {
			@header ("Location: spip_test_dirs.php3?test_dir=".dirname($dest));
			exit;
		}
		@unlink($dest);

		if ($GLOBALS['_FILES']['size'] == 0) {
			echo _L("Ce fichier est trop gros pour le serveur, upload limit&eacute; &agrave; ").ini_get('upload_max_filesize');
		}
	}

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

function ajout_doc($orig, $source, $dest, $mode, $id_document, $doc_vignette='', $titre_vignette='', $descriptif_vignette='', $titre_automatique=true) {
	global $hash_id_auteur, $hash, $id_article, $type;

	//die ("<li>$orig<li>$source<li>$dest<li>$mode<li>$id_document");


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

	$result = spip_query($query);
	if ($row = @spip_fetch_array($result)) {
		$id_type = $row['id_type'];
		$type_inclus = $row['inclus'];
	}
	else return false;

	//
	// Recopier le fichier
	//
	$dest = 'IMG/';
	if (creer_repertoire('IMG', $ext))
		$dest .= $ext.'/';
	$dest .= ereg_replace("[^.a-zA-Z0-9_=-]+", "_", translitteration(ereg_replace("\.([^.]+)$", "", supprimer_tags(basename($orig)))));
	$n = 0;
	while (@file_exists($newFile = $dest.($n++ ? '-'.$n : '').'.'.$ext));
	$dest_path = $newFile;

	if (!deplacer_fichier_upload($source, $dest_path)) return false;

	//
	// Preparation
	//
	if ($mode == 'vignette') {
		$id_document_lie = $id_document;
		$query = "UPDATE spip_documents SET mode='document' where id_document=$id_document_lie";
		spip_query($query); // requete inutile a mon avis (Fil)...
		$id_document = 0;
	}
	if (!$id_document) {
		$query = "INSERT INTO spip_documents (id_type, titre, date) VALUES ($id_type, '', NOW())";
		spip_query($query);
		$id_document = spip_insert_id();
		$nouveau = true;
		if ($id_article) {
			$query = "INSERT INTO spip_documents_".$type."s (id_document, id_".$type.") VALUES ($id_document, $id_article)";
			spip_query($query);
		}
	}


	//
	// Mettre a jour les infos du document uploade
	//
	$size_image = @getimagesize($dest_path);
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
		if (!$titre_automatique) $titre = "";
		//$update = "mode='$mode', titre='".addslashes($titre)."', ";
		$update = "mode='$mode', ";
	}

	$query = "UPDATE spip_documents SET $update taille='$taille', largeur='$largeur', hauteur='$hauteur', fichier='$dest_path' ".
		"WHERE id_document=$id_document";
	spip_query($query);

	if ($id_document_lie) {
		$query = "UPDATE spip_documents SET id_vignette=$id_document WHERE id_document=$id_document_lie";
		spip_query($query);
		$id_document = $id_document_lie; // pour que le 'return' active le bon doc.
	}

	if ($doc_vignette){
		$query = "UPDATE spip_documents SET id_vignette=$doc_vignette, titre='', descriptif='' WHERE id_document=$id_document";
		spip_query($query);

	}

	// Creer la vignette
	if ($mode == 'document' AND lire_meta('creer_preview') == 'oui'
	AND ereg(",$ext,", ','.lire_meta('formats_graphiques').',')) {
		include_ecrire('inc_logos.php3');
		if (eregi('^IMG/(.*/)?([^\./]+)\.([a-z0-9]+)$', $dest_path, $regs)) {
			$destination = 'IMG/'.creer_repertoire('IMG','vignettes').$regs[2].'-s';
			creer_vignette($dest_path, lire_meta('taille_preview'), lire_meta('taille_preview'), 'jpg', $destination, 'AUTO', true);
		}
	}
	return $id_document;
}


// image_name n'est valide que par POST http, mais pas par la methode ftp/upload
// par ailleurs, pour un fichier ftp/upload, il faut effacer l'original nous-memes
if (!$image_name AND $image2) {
	$image = "ecrire/upload/".$image2;
	$image_name = $image;
	$supprimer_ecrire_upload = $image;
} 
else {
	$supprimer_ecrire_upload = '';
}

//
// ajouter un document
//

if ($ajout_doc == 'oui') {
	if (eregi("\.zip$",$image_name) AND !$action_zip){
		// Pretraitement des fichiers ZIP
		// Recopier le fichier
		creer_repertoire('IMG', "tmp");
		creer_repertoire('IMG', "tmp_zip");
		
		$dest = 'IMG/tmp_zip/';
		$dest .= ereg_replace("[^.a-zA-Z0-9_=-]+", "_", translitteration(ereg_replace("\.([^.]+)$", "", supprimer_tags(basename($image_name)))));
		$dest .= ".zip";
		$n = 0;
		if (!deplacer_fichier_upload($image, $dest)) 
			exit;


		$image_name = "$dest";

		require_once('ecrire/pclzip.lib.php');
		$zip = new PclZip($image_name);

		if (($list = $zip->listContent()) == 0) {
			// pas possible de decompacter: installer comme fichier zip joint
			$afficher_message_zip = false;
		}
		else {
			// Verifier si le contenu peut etre uploade (verif extension)
			for ($i=0; $i<sizeof($list); $i++) {
				for(reset($list[$i]); $key = key($list[$i]); next($list[$i])) {
				
					if ($key == "stored_filename") {
						if (ereg("\.([^.]+)$", $list[$i][$key], $match)) {
							$ext = addslashes(strtolower($match[1]));
							$ext = corriger_extension($ext);

							// Regexp des fichiers a ignorer
							if (!ereg("^(\.|.*/\.|.*__MACOSX/)",
							$list[$i][$key])) {
								$query = "SELECT * FROM spip_types_documents WHERE extension='$ext' AND upload='oui'";
								$result = spip_query($query);
								if ($row = @spip_fetch_array($result)) {
									$afficher_message_zip = true;
									$aff_fichiers .= "<li>".$list[$i][$key]."</li>";
								}
							}
						}
					}
				}
			}
		}

		if ($afficher_message_zip) {
			// presenter une interface pour choisir si fichier joint ou decompacter
			include_ecrire ("inc_presentation.php3");
			install_debut_html("Fichier ZIP");
		
			
			echo _L("<p>Le fichier que vous proposez d'installer est un fichier Zip.</p><p> Ce fichier peut &ecirc;tre :</p>\n\n");
			
		
			if ($HTTP_POST_VARS) $vars = $HTTP_POST_VARS;
			else $vars = $HTTP_GET_VARS;
			
			$link = new Link("spip_image.php3");
			$link->addVar("image_name", $image_name);
			while (list ($key, $val) = each ($vars)) {
				if ($key == "image" OR $key == "image2") {
					//$link->addVar("image_name", $image_name);
				}
				else {
					$link->addVar($key, $val);
				}
			}		

			echo $link->getForm('POST');
			
			echo _L('')."<div><input type='radio' checked name='action_zip' value='telquel'>install&eacute; tel quel, en tant qu'archive compress&eacute;e Zip,</div>";
			echo "<div><input type='radio' name='action_zip' value='decompacter'>d&eacute;compress&eacute; et chaque &eacute;l&eacute;ment qu'il contient install&eacute; sur le site. Les fichiers qui seront alors install&eacute;s sur le site sont&nbsp;:</div>";
			
			echo "<ul>$aff_fichiers</ul>";
			
			echo "<div>&nbsp;</div>";
			echo "<div style='text-align: right;'><input class='fondo' style='font-size: 9px;' TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."'></div>";
			
			echo "</form>";
			install_fin_html();
				
			exit();
		}
		else {
			$image = $image_name;
			$supprimer_ecrire_upload = $image;
		}
	}
	else if (eregi("\.zip$",$image_name)) {
		if ($action_zip == "telquel") {
			$effacer_tmp = true;
			
			$id_document = ajout_doc($image_name, $image_name, $fichier, "document", $id_document);
			
		} else {
		
			require_once('ecrire/pclzip.lib.php');
  			$archive = new PclZip($image_name);
			$list = $archive->extract(PCLZIP_OPT_PATH, "IMG/tmp", PCLZIP_OPT_REMOVE_ALL_PATH);
			$image_name = "IMG/tmp";
			$effacer_tmp = true;
		}
	}


	if (is_dir($image_name)) {
		include_ecrire('inc_documents.php3');
		$fichiers = fichiers_upload($image_name);
		while (list(,$f) = each($fichiers)) {
			if (ereg("\.([^.]+)$", $f, $match)) {
				$ext = strtolower($match[1]);
				if ($ext == 'jpeg')
					$ext = 'jpg';
				$req = "SELECT extension FROM spip_types_documents WHERE extension='$ext'";
				if ($inclus)
					$req .= " AND inclus='$inclus'";
				if (@spip_fetch_array(spip_query($req)))
					$id_document = ajout_doc($f, $f, '', 'document', '','','','',false);
			}
		}
	} else {
		if ($forcer_document == 'oui')
			$id_document = ajout_doc($image_name, $image, $fichier, "document", $id_document);
		else
			$id_document = ajout_doc($image_name, $image, $fichier, $mode, $id_document);
	}
	
	
	if ($effacer_tmp) {
		$d = opendir("IMG/tmp");
		while ($f = readdir($d)) {
			if (is_file("IMG/tmp/$f")) @unlink("IMG/tmp/$f");
		}
		$d = opendir("IMG/tmp_zip");
		while ($f = readdir($d)) {
			if (is_file("IMG/tmp/$f")) @unlink("IMG/tmp/$f");
		}
	}
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
// Faire tourner une image
//

//$imagePath - path to your image; function will save rotated image overwriting the old one
//$rtt - should be 90 or -90 - cw/ccw
function gdRotate($imagePath,$rtt){
	if(preg_match("/\.(png)/i", $imagePath)) $src_img=ImageCreateFromPNG($imagePath);
	elseif(preg_match("/\.(jpg)/i", $imagePath)) $src_img=ImageCreateFromJPEG($imagePath);
	elseif(preg_match("/\.(bmp)/i", $imagePath)) $src_img=ImageCreateFromWBMP($imagePath);
	$size=GetImageSize($imagePath);
	//note: to make it work on GD 2.xx properly change ImageCreate to ImageCreateTrueColor

	$process = lire_meta('image_process');
	if ($process == "gd2") $dst_img=ImageCreateTrueColor($size[1],$size[0]);
	else  $dst_img=ImageCreate($size[1],$size[0]);
	if($rtt==90){
		$t=0;
		$b=$size[1]-1;
		while($t<=$b){
			$l=0;
			$r=$size[0]-1;
			while($l<=$r){
				imagecopy($dst_img,$src_img,$t,$r,$r,$b,1,1);
				imagecopy($dst_img,$src_img,$t,$l,$l,$b,1,1);
				imagecopy($dst_img,$src_img,$b,$r,$r,$t,1,1);
				imagecopy($dst_img,$src_img,$b,$l,$l,$t,1,1);
				$l++;
				$r--;
			}
			$t++;
			$b--;
		}
	}
	elseif($rtt==-90){
		$t=0;
		$b=$size[1]-1;
		while($t<=$b){
			$l=0;
			$r=$size[0]-1;
			while($l<=$r){
				imagecopy($dst_img,$src_img,$t,$l,$r,$t,1,1);
				imagecopy($dst_img,$src_img,$t,$r,$l,$t,1,1);
				imagecopy($dst_img,$src_img,$b,$l,$r,$b,1,1);
				imagecopy($dst_img,$src_img,$b,$r,$l,$b,1,1);
				$l++;
				$r--;
			}
			$t++;
			$b--;
		}
	}
	ImageDestroy($src_img);
	ImageInterlace($dst_img,0);
	ImageJPEG($dst_img,$imagePath);
}


if ($doc_rotate) {
	// Securite
	if (!verifier_action_auteur("rotate $doc_rotate", $hash, $hash_id_auteur)) {
		exit;
	}
	
	if (!$var_rot) $var_rot = 0;

	$query = "SELECT id_vignette, fichier FROM spip_documents WHERE id_document=$doc_rotate";
	$result = spip_query($query);
	if ($row = spip_fetch_array($result)) {
		$id_vignette = $row['id_vignette'];
		$image = $row['fichier'];

		$process = lire_meta('image_process');
		 // imagick (php4-imagemagick)
		 if ($process == 'imagick') {
			$handle = imagick_readimage($image);
			imagick_rotate($handle, $var_rot);
			imagick_write($handle, $image);
			if (!@file_exists($image)) return;	// echec imagick
		}
		else if ($process == "gd2") { // theoriquement compatible gd1, mais trop forte degradation d'image
			if ($var_rot == 180) { // 180 = 90+90
				gdRotate ($image, 90);
				gdRotate ($image, 90);
			} else {
				gdRotate ($image, $var_rot);
			}
		}
		else if ($process = "convert") {
			$commande = "$convert_command -rotate $var_rot $image $image";
			exec($commande);
		}

		$size_image = @getimagesize($image);
		$largeur = $size_image[0];
		$hauteur = $size_image[1];

		if ($id_vignette > 0) {
			$query = "SELECT id_vignette, fichier FROM spip_documents WHERE id_document=$id_vignette";
			$result = spip_query($query);
			if ($row = spip_fetch_array($result)) {
				$fichier = $row['fichier'];
				@unlink($fichier);
			}
			spip_query("DELETE FROM spip_documents WHERE id_document=$id_vignette");
			spip_query("DELETE FROM spip_documents_articles WHERE id_document=$id_vignette");
			spip_query("DELETE FROM spip_documents_rubriques WHERE id_document=$id_vignette");
			spip_query("DELETE FROM spip_documents_breves WHERE id_document=$id_vignette");
		}


		spip_query("UPDATE spip_documents SET id_vignette=0, largeur=$largeur, hauteur=$hauteur WHERE id_document=$doc_rotate");

	}

}


//
// Supprimer un document
//
if ($doc_supp) {
	// Securite
	if (!verifier_action_auteur("supp_doc $doc_supp", $hash, $hash_id_auteur)) {
		exit;
	}
	$query = "SELECT id_vignette, fichier FROM spip_documents WHERE id_document=$doc_supp";
	$result = spip_query($query);
	if ($row = spip_fetch_array($result)) {
		$fichier = $row['fichier'];
		$id_vignette = $row['id_vignette'];
		spip_query("DELETE FROM spip_documents WHERE id_document=$doc_supp");
		spip_query("UPDATE spip_documents SET id_vignette=0 WHERE id_vignette=$doc_supp");
		spip_query("DELETE FROM spip_documents_articles WHERE id_document=$doc_supp");
		spip_query("DELETE FROM spip_documents_rubriques WHERE id_document=$doc_supp");
		spip_query("DELETE FROM spip_documents_breves WHERE id_document=$doc_supp");
		@unlink($fichier);
	}

	if ($id_vignette > 0) {
		$query = "SELECT id_vignette, fichier FROM spip_documents WHERE id_document=$id_vignette";
		$result = spip_query($query);
		if ($row = spip_fetch_array($result)) {
			$fichier = $row['fichier'];
			@unlink($fichier);
		}
		spip_query("DELETE FROM spip_documents WHERE id_document=$id_vignette");
		spip_query("DELETE FROM spip_documents_articles WHERE id_document=$id_vignette");
		spip_query("DELETE FROM spip_documents_rubriques WHERE id_document=$id_vignette");
		spip_query("DELETE FROM spip_documents_breves WHERE id_document=$id_vignette");
	}
}

//
// Creation automatique de vignette new style
//
if ($vignette) {
	// securite
	$fichier_vignette = '';
	if (eregi('^IMG/(.*/)?([^\./]+)\.([a-z0-9]+)$', $vignette, $regs)) {
		$source = $regs[0];
		$format = $regs[3];
		include_local('inc-cache.php3');
		$destination = 'IMG/'.creer_repertoire('IMG','vignettes').$regs[2].'-s';	// adresse new style

		if (lire_meta("creer_preview") == 'oui') {
			$taille_preview = lire_meta("taille_preview");
			if ($taille_preview < 10) $taille_preview = 120;
			include_ecrire('inc_logos.php3');
			if ($preview = creer_vignette($source, $taille_preview, $taille_preview, $format, $destination))
				$fichier_vignette = $preview['fichier'];
		}
	}

	if (!$fichier_vignette) {
		include_ecrire('inc_documents.php3');
		list($fichier_vignette) = vignette_par_defaut($format);
		if (!$fichier_vignette)
			list($fichier_vignette) = vignette_par_defaut('txt');
	}

	@header("Location: $fichier_vignette");
	exit;
}


//
// redirection
//
if ($HTTP_POST_VARS) $vars = $HTTP_POST_VARS;
else $vars = $HTTP_GET_VARS;
$redirect_url = "ecrire/" . $vars["redirect"];
$link = new Link($redirect_url);
reset($vars);
while (list ($key, $val) = each ($vars)) {
	if (!ereg("^(redirect|image.*|hash.*|ajout.*|doc.*|transformer.*|modifier_.*|ok|type|forcer_.*|var_rot|action_zips)$", $key)) {
		$link->addVar($key, $val);
	}
}
if ($id_document)
	$link->addVar('id_document',$id_document);
if ($type == 'rubrique')
	$link->delVar('id_article');

@header ("Location: ".$link->getUrl());

exit;
?>
