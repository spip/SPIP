<?php

include ("ecrire/inc_version.php3");
include_ecrire("inc_charsets.php3");
include_ecrire("inc_meta.php3");
include_ecrire("inc_admin.php3");

function copier_document($ext, $orig, $source) {

	$dir = creer_repertoire_documents($ext);
	$dest = $dir .
		ereg_replace("[^.a-zA-Z0-9_=-]+", "_", 
			translitteration(ereg_replace("\.([^.]+)$", "", 
						      ereg_replace("<[^>]*>", '', basename($orig)))));
	// a améliorer au plus  vite
	$secure = lire_meta("creer_htpasswd") == 'oui';
	if ($secure)
	  verifier_htaccess($dir);
	else @unlink("$dir/.htaccess");

	# bien vu ?
	if ($orig == ($dest . '.' . $ext)) return $orig;
	$n = 0;
	while (@file_exists($newFile = $dest.($n++ ? '-'.$n : '').'.'.$ext));
	$r = deplacer_fichier_upload($source, $newFile);
	spip_log("ajout_doc: copie de $source dans $newFile impossible");
	return (!$r ? '' : $newFile);
}

function effacer_repertoire_temporaire($nom) {
	$d = opendir($nom);
	while ($f = readdir($d)) {
		if (is_file($f = "$nom/$f")) @unlink($f);
		}
	@rmdir($nom);
}

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

		$vignette = _DIR_IMG . "test.jpg";
		$dest = _DIR_IMG . "test-jpg.jpg";
		exec("$djpeg_command $vignette | $pnmscale_command -width 10 | $cjpeg_command -outfile $dest");
		if ($taille = @getimagesize($dest)) {
			if ($taille[1] == 10) $netpbm_formats[] = "jpg";
		}
		ImageDestroy( $dest );
	
		
		$giftopnm_command = ereg_replace("pnmscale", "giftopnm", $pnmscale_command);
		$vignette = _DIR_IMG . "test.gif";
		$dest = _DIR_IMG . "test-gif.jpg";
		exec("$giftopnm_command $vignette | $pnmscale_command -width 10 | $cjpeg_command -outfile $dest");
		if ($taille = @getimagesize($dest)) {
			if ($taille[1] == 10) $netpbm_formats[] = "gif";
		}
		ImageDestroy( $dest );
		$pngtopnm_command = ereg_replace("pnmscale", "pngtopnm", $pnmscale_command);
		$vignette = _DIR_IMG . "test.png";
		$dest = _DIR_IMG . "test-gif.jpg";
		exec("$pngtopnm_command $vignette | $pnmscale_command -width 10 | $cjpeg_command -outfile $dest");
		if ($taille = @getimagesize($dest)) {
			if ($taille[1] == 10) $netpbm_formats[] = "png";
		}
		ImageDestroy( $dest );
		

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
	global $redirect_url, $hash_id_auteur, $hash, $num_img;

	// Securite
	if (verifier_action_auteur("ajout_image $dest", $hash, $hash_id_auteur)) {

	// analyse le type de l'image (on ne fait pas confiance au nom de
	// fichier envoye par le browser : pour les Macs c'est plus sur)

		$size = @getimagesize($source);
		$type = decoder_type_image($size[2], true);

		if ($type) deplacer_fichier_upload($source,
						   _DIR_IMG . $dest . ".$type");
	}
}

//
// Ajouter un document
//

function ajout_doc($orig, $source, $mode, $id_document) {
	global $hash_id_auteur, $hash, $id_article, $type;

	//
	// Securite
	//
	if (!verifier_action_auteur("ajout_doc", $hash, $hash_id_auteur))
		return;

	// type de document inconnu ?

	if (!ereg("\.([^.]+)$", $orig, $match)) return;

	$ext = corriger_extension(addslashes(strtolower($match[1])));
	$row = @spip_fetch_array(spip_query("SELECT * FROM spip_types_documents WHERE extension='$ext' AND upload='oui'" . (($mode != 'vignette') ? '' : " AND inclus='image'")));

	// type de document invalide ?

	if (!$row) return;

	// Recopier le fichier

	$dest_path = copier_document($ext,$orig, $source);
	if (!$dest_path) return;

	// Preparation

	if ($mode == 'vignette') {
		$id_document_lie = $id_document;
		$query = "UPDATE spip_documents SET mode='document' where id_document=$id_document_lie";
		spip_query($query); // requete inutile a mon avis (Fil)...
		$id_document = 0;
	}
	if (!$id_document) {
		$id_type = $row['id_type'];
		$query = "INSERT INTO spip_documents (id_type, titre, date) VALUES ($id_type, '', NOW())";
		spip_query($query);
		$id_document = spip_insert_id();
		$nouveau = true;
		if ($id_article && isset($type)) {
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
		$type_inclus = $row['inclus'];
		if (!$mode) $mode = ($type_image AND $type_inclus == 'image') ? 'vignette' : 'document';
			$update = "mode='$mode', ";
	}

	spip_query("UPDATE spip_documents SET $update taille='$taille', largeur='$largeur', hauteur='$hauteur', fichier='$dest_path' WHERE id_document=$id_document");


	if ($id_document_lie) {
		$query = "UPDATE spip_documents SET id_vignette=$id_document WHERE id_document=$id_document_lie";
		spip_query($query);
		$id_document = $id_document_lie; // pour que le 'return' active le bon doc.
	}
	// Creer la vignette
	if ($mode == 'document' AND lire_meta('creer_preview') == 'oui'
	    AND ereg(",$ext,", ','.lire_meta('formats_graphiques').',')) {
		include_ecrire('inc_logos.php3');
		$f = ereg_replace(".$ext$", '-s', basename($dest_path));
		$d = lire_meta('taille_preview');
		creer_fichier_vignette($dest_path);
		//creer_vignette($dest_path, $d, $d, 'jpg', 'vignettes', $f, 'AUTO', true);
	}
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

//
// Creation automatique de vignette new style
// Normalement le test est vérifié donc on ne rend rien sinon

function creer_fichier_vignette($vignette) {
  spip_log("creer_fich $vignette " . lire_meta("creer_preview"));
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
			spip_log($commande);
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

function afficher_compactes($image_name) {

	$afficher_message_zip = false;
	$zip = new PclZip($image_name);

	if ($list = $zip->listContent()) {
	// si pas possible de decompacter: installer comme fichier zip joint
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

	if (!$afficher_message_zip) return false;
	  
		// presenter une interface pour choisir si fichier joint ou decompacter
		include_ecrire ("inc_presentation.php3");
		install_debut_html(_T('upload_fichier_zip'));
		
		echo "<p>"._T('upload_fichier_zip_texte')."</p>";
		echo "<p>"._T('upload_fichier_zip_texte2')."</p>";
		
		$link = new Link();
		$link->delVar("image");
		$link->delVar("image2");
		$link->addVar("image_name", $image_name);

		echo $link->getForm('POST');
		
		echo _L('')."<div><input type='radio' checked name='action_zip' value='telquel'>"._T('upload_zip_telquel')."</div>";
		echo "<div><input type='radio' name='action_zip' value='decompacter'>"._T('upload_zip_decompacter')."</div>";
		
		echo "<ul>$aff_fichiers</ul>";
		
		echo "<div>&nbsp;</div>";
		echo "<div style='text-align: right;'><input class='fondo' style='font-size: 9px;' TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."'></div>";
		
		echo "</form>";
		install_fin_html();
			
		return true;
}

//
// ajouter un document
//

if ($ajout_doc == 'oui') {

// image_name n'est valide que par POST http, mais pas par la methode ftp/upload
// par ailleurs, pour un fichier ftp/upload, il faut effacer l'original nous-memes
	if (!$image_name AND $image2) {
		$image = "ecrire/upload/".$image2;
		$image_name = $image;
	} 
	if (eregi("\.zip$",$image_name))
	  # action_zip indique un rappel par la fonction affiche_compactes
	  if (!$action_zip){
		// on va se rappeler: copier le fichier car PHP va le virer
		$image_name = copier_document("zip", $image_name, $image);
		// anormal, on se tire
		if (!$image_name) exit;
		// renvoyer un formulaire demandant si on deballe ou pas
		require_once('ecrire/pclzip.lib.php');
		if (afficher_compactes($image_name)) exit;
		// pas possible de deballer, on continue
		$forcer_document = 'oui';
		$image = $image_name;
	}
	  else {
	    // reponse au formulaire
		if ($action_zip == "telquel") {
			$forcer_document = 'oui';
			
		} else {
			require_once('ecrire/pclzip.lib.php');
  			$archive = new PclZip($image_name);
			$tmp_dir = creer_repertoire_documents($hash);
			$archive->extract(PCLZIP_OPT_PATH, $tmp_dir, PCLZIP_OPT_REMOVE_ALL_PATH);
			# virer le zip après le déballage
			@unlink($image_name);
			$image_name = $tmp_dir;

		}
	  }

	if (!is_dir($image_name)) {
		ajout_doc($image_name,
			  $image,
			  ($forcer_document == 'oui' ? "document" : $mode),
			  $id_document);
	} else {
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
					ajout_doc($f, $f, 'document', false);
			}
		}

# détruire le repertoire de deballage
		if ($tmp_dir) effacer_repertoire_temporaire($tmp_dir);
	}
 }

$redirect = '';

if ($test_vignette) // appel de ecrire/config-fonction
	$redirect = tester_vignette($test_vignette);
elseif ($vignette) // appels de ecrire/inc_logo
	$redirect = creer_fichier_vignette($vignette);
elseif ($ajout_logo == "oui")
	ajout_image($image, $logo);
elseif ($image_supp)
	effacer_image($image_supp);
elseif ($doc_supp) // appels de ecrire/inc_document
	supprime_document_et_vignette($doc_supp);
elseif ($doc_rotate)
	tourner_document($var_rot, $doc_rotate, $convert_command);

if (!$redirect) {
	if ($HTTP_POST_VARS) $vars = $HTTP_POST_VARS;
	else $vars = $HTTP_GET_VARS;

	$link = new Link("ecrire/" . $vars["redirect"]);
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
header("Location: $redirect");
# QQ trucs a vérifier avant de lancer ça:
#header("Connection: close");
#taches_de_fond();

?>
