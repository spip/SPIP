<?php

include ("ecrire/inc_version.php3");
include_ecrire("inc_charsets.php3");
include_ecrire("inc_meta.php3");
include_ecrire("inc_admin.php3");
include_ecrire("inc_abstract_sql.php3");

function effacer_image($nom) {
	global $hash_id_auteur, $hash;

	if ((!strstr($nom, "..")) AND
	    verifier_action_auteur("supp_image $nom", $hash, $hash_id_auteur))
		@unlink(_DIR_IMG . $nom);
}

function tester_vignette ($test_vignette) {
	global $djpeg_command, $cjpeg_command, $pnmscale_command;
	// verifier les formats acceptes par GD
	if ($test_vignette == "gd1") {
		$gd_formats = Array();
		if (function_exists('ImageCreateFromJPEG')) {
			$srcImage = @ImageCreateFromJPEG(_DIR_IMG . "test.jpg");
			if ($srcImage) {
				$gd_formats[] = "jpg";
				ImageDestroy( $srcImage );
			}
		}
		if (function_exists('ImageCreateFromGIF')) {
			$srcImage = @ImageCreateFromGIF(_DIR_IMG . "test.gif");
			if ($srcImage) {
				$gd_formats[] = "gif";
				ImageDestroy( $srcImage );
			}
		}
		if (function_exists('ImageCreateFromPNG')) {
			$srcImage = @ImageCreateFromPNG(_DIR_IMG . "test.png");
			if ($srcImage) {
				$gd_formats[] = "png";
				ImageDestroy( $srcImage );
			}
		}

		if ($gd_formats) $gd_formats = join(",", $gd_formats);
		ecrire_meta("gd_formats", $gd_formats);
		ecrire_metas();
	}
	// verifier les formats netpbm
	else if ($test_vignette == "netpbm") {
		$netpbm_formats= Array();

		$jpegtopnm_command = ereg_replace("pnmscale", "jpegtopnm", $pnmscale_command);
		$pnmtojpeg_command = ereg_replace("pnmscale", "pnmtojpeg", $pnmscale_command);

		$vignette = _DIR_IMG . "test.jpg";
		$dest = _DIR_IMG . "test-jpg.jpg";
		exec("$jpegtopnm_command $vignette | $pnmscale_command -width 10 | $pnmtojpeg_command > $dest");
		if ($taille = @getimagesize($dest)) {
			if ($taille[1] == 10) $netpbm_formats[] = "jpg";
		}	
		
		$giftopnm_command = ereg_replace("pnmscale", "giftopnm", $pnmscale_command);
		$pnmtojpeg_command = ereg_replace("pnmscale", "pnmtojpeg", $pnmscale_command);
		$vignette = _DIR_IMG . "test.gif";
		$dest = _DIR_IMG . "test-gif.jpg";
		exec("$giftopnm_command $vignette | $pnmscale_command -width 10 | $pnmtojpeg_command > $dest");
		if ($taille = @getimagesize($dest)) {
			if ($taille[1] == 10) $netpbm_formats[] = "gif";
		}

		$pngtopnm_command = ereg_replace("pnmscale", "pngtopnm", $pnmscale_command);
		$vignette = _DIR_IMG . "test.png";
		$dest = _DIR_IMG . "test-gif.jpg";
		exec("$pngtopnm_command $vignette | $pnmscale_command -width 10 | $cjpeg_command -outfile $dest");
		if ($taille = @getimagesize($dest)) {
			if ($taille[1] == 10) $netpbm_formats[] = "png";
		}
		

		if ($netpbm_formats) $netpbm_formats = join(",", $netpbm_formats);
		ecrire_meta("netpbm_formats", $netpbm_formats);
		ecrire_metas();
	}

	// et maintenant envoyer la vignette de tests
	if (ereg("^(gd1|gd2|imagick|convert|netpbm)$", $test_vignette)) {
		include_ecrire('inc_logos.php3');
		//$taille_preview = lire_meta("taille_preview");
		if ($taille_preview < 10) $taille_preview = 120;
		if ($preview = creer_vignette(_DIR_IMG . 'test_image.jpg', $taille_preview, $taille_preview, 'jpg', '', "test_$test_vignette", $test_vignette, true))

			return ($preview['fichier']);
	}
	return '';
}


//
// Deplacer un fichier
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
			redirige_par_entete("spip_test_dirs.php3?test_dir=".
					    dirname($dest));
		}
		@unlink($dest);

		if (($GLOBALS['_FILES']['size'] == 0) AND !$GLOBALS['action_zip']) {
			include_ecrire('inc_presentation.php3');
			install_debut_html(_T('forum_titre_erreur'));
			echo "<p>"._T('upload_limit',
				array('max' => ini_get('upload_max_filesize')));
			install_fin_html();
			exit;
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
	global $redirect_url, $hash_id_auteur, $hash, $num_img, $dossier_squelettes;

	// Securite
	if (!(verifier_action_auteur("ajout_image $dest", $hash, $hash_id_auteur)
	      AND _DIR_DOC != $dossier_squelettes)) {
	  spip_log("interdiction ajout_image($source, $dest)");
	  return;
	}

	// analyse le type de l'image (on ne fait pas confiance au nom de
	// fichier envoye par le browser : pour les Macs c'est plus sur)

	$f =_DIR_DOC . $dest . '.tmp';
	deplacer_fichier_upload($source, $f);
	$size = @getimagesize($f);
	$type = decoder_type_image($size[2], true);
	if ($type)
		@rename ($f, _DIR_DOC . $dest . ".$type");
	else
		@unlink ($f);
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
	$size=@getimagesize($imagePath);
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

//
// Creation automatique de vignette new style
// Normalement le test est vérifié donc on ne rend rien sinon

function creer_fichier_vignette($vignette) {
	if ($vignette && lire_meta("creer_preview") == 'oui') {
		eregi('\.([a-z0-9]+)$', $vignette, $regs);
		$ext = $regs[1];
		$taille_preview = lire_meta("taille_preview");
		if ($taille_preview < 10) $taille_preview = 120;
		include_ecrire('inc_logos.php3');

		if ($preview = creer_vignette($vignette, $taille_preview, $taille_preview, $ext, 'vignettes', basename($vignette).'-s'))
		{
			inserer_vignette_base($vignette, $preview['fichier']);
			return $preview['fichier'];
		}
		include_ecrire('inc_documents.php3');
		return vignette_par_defaut($ext ? $ext : 'txt', false);
	}
}

function supprime_document_et_vignette($doc_supp) {
	global $hash_id_auteur, $hash;
	// Securite
	if (verifier_action_auteur("supp_doc $doc_supp", $hash, $hash_id_auteur)) {
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
	}
}

function tourner_document($var_rot, $doc_rotate, $convert_command) {
	global $hash_id_auteur, $hash;
	// Securite
	if (!verifier_action_auteur("rotate $doc_rotate", $hash, $hash_id_auteur)) {
		return '';
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
			$commande = "$convert_command -rotate $var_rot ./"
				. escapeshellcmd($image).' ./'.escapeshellcmd($image);
#			spip_log($commande);
			exec($commande);
		}

		$size_image = @getimagesize($image);
		$largeur = $size_image[0];
		$hauteur = $size_image[1];

		if ($id_vignette > 0) {
			creer_fichier_vignette($image);
/*			$query = "SELECT id_vignette, fichier FROM spip_documents WHERE id_document=$id_vignette";
			$result = spip_query($query);
			if ($row = spip_fetch_array($result)) {
				$fichier = $row['fichier'];
				@unlink($fichier);
			}
			spip_query("DELETE FROM spip_documents WHERE id_document=$id_vignette");
			spip_query("DELETE FROM spip_documents_articles WHERE id_document=$id_vignette");
			spip_query("DELETE FROM spip_documents_rubriques WHERE id_document=$id_vignette");
			spip_query("DELETE FROM spip_documents_breves WHERE id_document=$id_vignette");
*/		}


		spip_query("UPDATE spip_documents SET largeur=$largeur, hauteur=$hauteur WHERE id_document=$doc_rotate");

	}
}

if ($test_vignette) // appel de config-fonction
	$redirect = tester_vignette($test_vignette);
elseif ($vignette) // appels de inc_logo
	$redirect = creer_fichier_vignette($vignette);
else {
   $retour = $redirect;
   $redirect = '';
   if ($ajout_doc == 'oui') {
	include_ecrire('inc_getdocument.php3');
	if (!$image_name AND $image2) {
		$image = _DIR_TRANSFERT . $image2;
		$image_name = $image;
	} 
	if (!eregi("\.zip$",$image_name))
	  ajout_doc_s($image, $image_name, $mode, $forcer_document, $id_document, $hash);
	else {
	  // bizarre: clean_link ne recupere pas les variables
	  $link = new Link('spip_image.php3');
	  $link->addVar("ajout_doc", "oui");
	  $link->addVar("redirect", $retour);
	  $link->addVar('id_document', $id_document);
	  $link->addVar('id_article', $id_article);
	  $link->addVar('mode', $mode);
	  $link->addVar('type', $type);
	  $link->addVar('hash', $hash);
	  $link->addVar('hash_id_auteur', $hash_id_auteur);
	  ajout_doc_zip($image, $image_name, $mode, $forcer_document, $action_zip, $id_document, $hash, $link);
	}
   }
   elseif ($ajout_logo == "oui")
	ajout_image($image, $logo);
   elseif ($image_supp)
	effacer_image($image_supp);
   elseif ($doc_supp) // appels de inc_document
	supprime_document_et_vignette($doc_supp);
   elseif ($doc_rotate)
	tourner_document($var_rot, $doc_rotate, $convert_command);
 }

if (!($redirect)) {
	if ($HTTP_POST_VARS) $vars = $HTTP_POST_VARS;
	else $vars = $HTTP_GET_VARS;
	$redirect = $vars["redirect"];
	$link = new Link(_DIR_RESTREINT_ABS . $redirect);
	reset($vars);
	while (list ($key, $val) = each ($vars)) {
	  if (!ereg("^(redirect|image.*|hash.*|ajout.*|doc.*|transformer.*|modifier_.*|ok|type|forcer_.*|var_rot|action_zip)$", $key)) {
	    $link->addVar($key, $val);
	  }
	}
	if ($id_document)
	  $link->addVar('id_document',$id_document);
	if ($type == 'rubrique')
	  $link->delVar('id_article');
	
	$redirect = $link->getUrl();
 }


redirige_par_entete($redirect);

?>
