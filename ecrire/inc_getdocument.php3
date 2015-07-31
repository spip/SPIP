<?php

//
// Fonctions de spip_image.php3

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_GETDOCUMENT")) return;
define("_ECRIRE_INC_GETDOCUMENT", "1");


// Creer IMG/pdf/
function creer_repertoire_documents($ext) {
	$rep = _DIR_DOC . creer_repertoire(_DIR_DOC, $ext);

	if (!$ext OR !$rep) {
		spip_log("creer_repertoire_documents interdit");
		exit;
	}

	if (lire_meta("creer_htaccess") == 'oui') {
		include_ecrire('inc_acces.php3');
		verifier_htaccess($rep);
	}

	return $rep;
}

// Efface le repertoire de maniere recursive !
function effacer_repertoire_temporaire($nom) {
	$d = opendir($nom);
	while ($f = readdir($d)) {
		if (is_file("$nom/$f"))
			@unlink("$nom/$f");
		else if ($f <> '.' AND $f <> '..'
		AND is_dir("$nom/$f"))
			effacer_repertoire_temporaire("$nom/$f");
	}
	@rmdir($nom);
}

function copier_document($ext, $orig, $source) {

	$dir = creer_repertoire_documents($ext);
	$dest = $dir .
		ereg_replace("[^.a-zA-Z0-9_=-]+", "_", 
			translitteration(ereg_replace("\.([^.]+)$", "", 
						      ereg_replace("<[^>]*>", '', basename($orig)))));

	// Si le document "source" est deja au bon endroit, ne rien faire
	if ($source == ($dest . '.' . $ext))
		return $source;

	// sinon tourner jusqu'a trouver un numero correct
	$n = 0;
	while (@file_exists($newFile = $dest.($n++ ? '-'.$n : '').'.'.$ext));

	$newFile = preg_replace('/[.]+/', '.', $newFile);

	if ($r = deplacer_fichier_upload($source, $newFile))
		return $newFile;
}

//
// Deplacer un fichier
//

function deplacer_fichier_upload($source, $dest) {
	// Securite
	if (strstr($dest, "..")) { exit; }

	$ok = @copy($source, $dest);
	if (!$ok) $ok = @move_uploaded_file($source, $dest);
	if ($ok)
		@chmod($dest, 0666);
	else {
		$f = @fopen($dest,'w');
		if ($f) {
			fclose ($f);
		} else {
			redirige_par_entete("spip_test_dirs.php3?test_dir=".
				dirname($dest));
		}
		@unlink($dest);
	}

	return $ok;
}


// Erreurs d'upload
// renvoie false si pas d'erreur
// et true si erreur = pas de fichier
// pour les autres erreurs affiche le message d'erreur et meurt
function check_upload_error($error, $msg='') {
	switch ($error) {
		case 0:
			return false;
		case 4: /* UPLOAD_ERR_NO_FILE */
			return true;

		# on peut affiner les differents messages d'erreur
		case 1: /* UPLOAD_ERR_INI_SIZE */
			$msg = _T('upload_limit',
			array('max' => ini_get('upload_max_filesize')));
			break;
		case 2: /* UPLOAD_ERR_FORM_SIZE */
			$msg = _T('upload_limit',
			array('max' => ini_get('upload_max_filesize')));
			break;
		case 3: /* UPLOAD_ERR_PARTIAL  */
			$msg = _T('upload_limit',
			array('max' => ini_get('upload_max_filesize')));
			break;
	}

	spip_log ("erreur upload $error");

	include_ecrire('inc_presentation.php3');
	install_debut_html(_T('forum_titre_erreur'));
	echo "<p>$msg</p>\n";

	install_fin_html(_DIR_RESTREINT_ABS . $GLOBALS['redirect']);
	exit;
}


//
// Gestion des fichiers ZIP
//
function verifier_compactes($zip) {
	if ($list = $zip->listContent()) {
	// si pas possible de decompacter: installer comme fichier zip joint
	// Verifier si le contenu peut etre uploade (verif extension)
		$aff_fichiers = array();
		for ($i=0; $i<sizeof($list); $i++) {
			for(reset($list[$i]); $key = key($list[$i]); next($list[$i])) {
			
				if ($key == "stored_filename") {
					$f =  $list[$i][$key];
					// Regexp des fichiers a ignorer
					if (!ereg("^(\.|.*/\.|.*__MACOSX/)", $f)) {
						if (ereg("\.([^.]+)$", $f, $match)) {
							$result = spip_query("SELECT * FROM spip_types_documents WHERE extension='"
. corriger_extension(addslashes(strtolower($match[1]))) 
									     . "' AND upload='oui'");
							if ($row = @spip_fetch_array($result))
								$aff_fichiers[]= $f;
							else
							spip_log("chargement de $f interdit");
							
						}
					}
				}
			}
		}
	}

	return $aff_fichiers ;
}

function afficher_compactes($image_name /* not used */, $fichiers, $link) {
// presenter une interface pour choisir si fichier joint ou decompacter
// passer ca en squelette un de ces jours.

	include_ecrire ("inc_presentation.php3");
	install_debut_html(_T('upload_fichier_zip'));
	echo "<p>",
		_T('upload_fichier_zip_texte'),
		"</p>",
		"<p>",
		_T('upload_fichier_zip_texte2'),
		"</p>",
		$link->getForm('POST'),
		"<div><input type='radio' checked name='action_zip' value='telquel'>",
		_T('upload_zip_telquel'),
		"</div>",
		"<div><input type='radio' name='action_zip' value='decompacter'>",
		_T('upload_zip_decompacter'),
		"</div>",
		"<ul><li>" ,
		 join("</li>\n<li>",$fichiers) ,
		 "</li></ul>",
		"<div>&nbsp;</div>",
		"<div style='text-align: right;'><input class='fondo' style='font-size: 9px;' type='submit' value='",
		_T('bouton_valider'),
		"'></div>",
		"</form>";
	install_fin_html();
}


//
// Ajouter un document (au format $_FILES)
//
function ajouter_un_document ($source, $nom_envoye, $type_lien, $id_lien, $mode, $id_document, &$documents_actifs) {

	// type de document inconnu ?
	if (!ereg("\.([^.]+)$", $nom_envoye, $match)) {
		spip_log("nom envoye incorrect ($nom_envoye)");
		return;
	}

	// tester le type de document :
	// - interdit a l'upload ?
	// - quel numero dans spip_types_documents ?  =-(
	// - est-ce "inclus" comme une image ?
	$ext = corriger_extension(addslashes(strtolower($match[1])));

	if (!$row = spip_fetch_array(spip_query(
	"SELECT * FROM spip_types_documents
	WHERE extension='$ext' AND upload='oui'"))) {
		spip_log("Extension $ext interdite a l'upload");
		return;
	}
	$id_type = $row['id_type'];	# numero du type dans spip_types_documents :(
	$type_inclus_image = ($row['inclus'] == 'image');

	// Recopier le fichier a son emplacement definitif
	$definitif = copier_document($ext, $nom_envoye, $source);
	if (!$definitif)
		return;

	// Quelques infos sur le fichier
	if (!@file_exists($definitif)
	OR !$taille = @filesize($definitif))
		return;

	// Est-ce une image ?
	$size_image = @getimagesize($definitif);
	$type_image = decoder_type_image($size_image[2]);
	if ($type_image) {
		$largeur = $size_image[0];
		$hauteur = $size_image[1];
	}

	// Si on veut uploader une vignette, il faut qu'elle ait ete bien lue
	if ($mode == 'vignette' AND !($largeur * $hauteur)) {
		@unlink($definitif);
		return;
	}

	// regler l'ancre du retour
	if (!$GLOBALS['ancre']) {
		if ($mode=='vignette')
			$GLOBALS['ancre'] = 'images';
		else if ($type_image)
			$GLOBALS['ancre'] = 'portfolio';
		else
			$GLOBALS['ancre'] = 'documents';
	}

	// Preparation vignette du document $id_document
	$id_document=intval($id_document);
	if ($mode == 'vignette' AND $id_document_lie = $id_document) {
		# on force le statut "document" de ce fichier (inutile ?)
		spip_query("UPDATE spip_documents
			SET mode='document'
			WHERE id_document=$id_document");
		$id_document = 0;
	}

	// Installer le document dans la base
	// attention piege semantique : les images s'installent en mode 'vignette'
	// note : la fonction peut "mettre a jour un document" si on lui
	// passe "mode=document" et "id_document=.." (pas utilise)
	if (!$id_document) {
		// Inserer le nouveau doc et recuperer son id_
		$id_document = spip_abstract_insert("spip_documents",
		"(id_type, titre, date)", "($id_type, '', NOW())");

		if ($id_lien
		AND preg_match('/^[a-z0-9_]+$/i', $type_lien) # securite
		)
			spip_query("INSERT INTO spip_documents_".$type_lien."s
				(id_document, id_".$type_lien.")
				VALUES ($id_document, $id_lien)");

		// par defaut (upload ZIP ou ftp) integrer
		// les images en mode 'vignette' et le reste en mode document
		if (!$mode)
			if ($type_image AND $type_inclus_image)
				$mode = 'vignette';
			else
				$mode = 'document';
			$update = "mode='$mode', ";
	}

	// Mise a jour des donnees
	spip_query("UPDATE spip_documents
		SET $update
		taille='$taille', largeur='$largeur', hauteur='$hauteur',
		fichier='$definitif'
		WHERE id_document=$id_document");

	if ($id_document_lie) {
		spip_query ("UPDATE spip_documents
		SET id_vignette=$id_document
		WHERE id_document=$id_document_lie");
		// hack pour que le retour vers ecrire/ active le bon doc.
		$documents_actifs[] = $id_document_lie; 
	}
	else
		$documents_actifs[] = $id_document; 

	// Creer la vignette des images
	if ($mode == 'document'
	AND ereg(",$ext,", ','.lire_meta('formats_graphiques').',')
	AND $type_image)
		creer_fichier_vignette($definitif);

	return true;
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
// Ajouter un logo
//

// $source = $_FILES[0]
// $dest = arton12.xxx
function ajout_logo($source, $dest) {

	// Intercepter une erreur d'upload
	if (check_upload_error($source['error'])) return;

	// analyse le type de l'image (on ne fait pas confiance au nom de
	// fichier envoye par le browser : pour les Macs c'est plus sur)
	$f =_DIR_DOC . $dest . '.tmp';
	deplacer_fichier_upload($source['tmp_name'], $f);
	$size = @getimagesize($f);
	$type = decoder_type_image($size[2], true);

	if ($type) {
		$poids = filesize($f);
		if (_LOGO_MAX_SIZE > 0
		AND $poids > _LOGO_MAX_SIZE*1024) {
			@unlink ($f);
			check_upload_error(6,
			_T('info_logo_max_poids',
				array('maxi' => taille_en_octets(_LOGO_MAX_SIZE*1024),
				'actuel' => taille_en_octets($poids))));
		}

		if (_LOGO_MAX_WIDTH * _LOGO_MAX_HEIGHT
		AND ($size[0] > _LOGO_MAX_WIDTH
		OR $size[1] > _LOGO_MAX_HEIGHT)) {
			@unlink ($f);
			check_upload_error(6, 
			_T('info_logo_max_taille',
				array(
				'maxi' =>
					_T('info_largeur_vignette',
						array('largeur_vignette' => _LOGO_MAX_WIDTH,
						'hauteur_vignette' => _LOGO_MAX_HEIGHT)),
				'actuel' =>
					_T('info_largeur_vignette',
						array('largeur_vignette' => $size[0],
						'hauteur_vignette' => $size[1]))
			)));
		}
		@rename ($f, _DIR_DOC . $dest . ".$type");
	}
	else {
		@unlink ($f);
		check_upload_error(6,
			_T('info_logo_format_interdit',
			array ('formats' => 'GIF, JPG, PNG'))
		);
	}
}


function effacer_logo($nom) {
	if (!strstr($nom, ".."))
		@unlink(_DIR_IMG . $nom);
}



//
// Creation automatique de vignette
//

// Tester nos capacites
function tester_vignette ($test_vignette) {
	global $pnmscale_command;

	// verifier les formats acceptes par GD
	if ($test_vignette == "gd1") {
		// Si GD est installe et php >= 4.0.2
		if (function_exists('imagetypes')) {

			if (imagetypes() & IMG_GIF) {
				$gd_formats[] = "gif";
			} else {
				# Attention GD sait lire le gif mais pas forcement l'ecrire
				if (function_exists('ImageCreateFromGIF')) {
					$srcImage = @ImageCreateFromGIF(_DIR_IMG . "test.gif");
					if ($srcImage) {
						$gd_formats_read_gif = ",gif";
						ImageDestroy( $srcImage );
					}
				}
			}

			if (imagetypes() & IMG_JPG)
				$gd_formats[] = "jpg";
			if (imagetypes() & IMG_PNG)
				$gd_formats[] = "png";
		}

		else {	# ancienne methode de detection des formats, qui en plus
				# est bugguee car elle teste les formats en lecture
				# alors que la valeur deduite sert a identifier
				# les formats disponibles en ecriture... (cf. inc_logos.php3)
		
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
		}

		if ($gd_formats) $gd_formats = join(",", $gd_formats);
		ecrire_meta("gd_formats_read", $gd_formats.$gd_formats_read_gif);
		ecrire_meta("gd_formats", $gd_formats);
		ecrire_metas();
	}

	// verifier les formats netpbm
	else if ($test_vignette == "netpbm"
	AND $pnmscale_command) {
		$netpbm_formats= Array();

		$jpegtopnm_command = str_replace("pnmscale",
			"jpegtopnm", $pnmscale_command);
		$pnmtojpeg_command = str_replace("pnmscale",
			"pnmtojpeg", $pnmscale_command);

		$vignette = _DIR_IMG . "test.jpg";
		$dest = _DIR_IMG . "test-jpg.jpg";
		$commande = "$jpegtopnm_command $vignette | $pnmscale_command -width 10 | $pnmtojpeg_command > $dest";
		spip_log($commande);
		exec($commande);
		if ($taille = @getimagesize($dest)) {
			if ($taille[1] == 10) $netpbm_formats[] = "jpg";
		}
		$giftopnm_command = ereg_replace("pnmscale", "giftopnm", $pnmscale_command);
		$pnmtojpeg_command = ereg_replace("pnmscale", "pnmtojpeg", $pnmscale_command);
		$vignette = _DIR_IMG . "test.gif";
		$dest = _DIR_IMG . "test-gif.jpg";
		$commande = "$giftopnm_command $vignette | $pnmscale_command -width 10 | $pnmtojpeg_command > $dest";
		spip_log($commande);
		exec($commande);
		if ($taille = @getimagesize($dest)) {
			if ($taille[1] == 10) $netpbm_formats[] = "gif";
		}

		$pngtopnm_command = ereg_replace("pnmscale", "pngtopnm", $pnmscale_command);
		$vignette = _DIR_IMG . "test.png";
		$dest = _DIR_IMG . "test-gif.jpg";
		$commande = "$pngtopnm_command $vignette | $pnmscale_command -width 10 | $pnmtojpeg_command > $dest";
		spip_log($commande);
		exec($commande);
		if ($taille = @getimagesize($dest)) {
			if ($taille[1] == 10) $netpbm_formats[] = "png";
		}
		

		if ($netpbm_formats)
			$netpbm_formats = join(",", $netpbm_formats);
		else
			$netpbm_formats = '';
		ecrire_meta("netpbm_formats", $netpbm_formats);
		ecrire_metas();
	}

	// et maintenant envoyer la vignette de tests
	if (ereg("^(gd1|gd2|imagick|convert|netpbm)$", $test_vignette)) {
		include_ecrire('inc_logos.php3');
		//$taille_preview = lire_meta("taille_preview");
		if ($taille_preview < 10) $taille_preview = 150;
		if ($preview = creer_vignette(_DIR_IMG . 'test_image.jpg', $taille_preview, $taille_preview, 'jpg', '', "test_$test_vignette", $test_vignette, true))

			return ($preview['fichier']);
	}
	return '';
}

// Creation
function creer_fichier_vignette($vignette, $test_cache_only=false) {
	if ($vignette && lire_meta("creer_preview") == 'oui') {
		eregi('\.([a-z0-9]+)$', $vignette, $regs);
		$ext = $regs[1];
		$taille_preview = lire_meta("taille_preview");
		if ($taille_preview < 10) $taille_preview = 120;
		include_ecrire('inc_logos.php3');

		if ($preview = creer_vignette($vignette, $taille_preview, $taille_preview, $ext, 'vignettes', basename($vignette).'-s', 'AUTO', false, $test_cache_only))
		{
			inserer_vignette_base($vignette, $preview['fichier']);
			return $preview['fichier'];
		}
		include_ecrire('inc_documents.php3');
		return vignette_par_defaut($ext ? $ext : 'txt', false);
	}
}

// Effacer un doc (et sa vignette)
function supprime_document_et_vignette($doc_supp) {

	$result = spip_query("SELECT id_vignette, fichier
		FROM spip_documents
		WHERE id_document=$doc_supp");
	if ($row = spip_fetch_array($result)) {
		$fichier = $row['fichier'];
		$id_vignette = $row['id_vignette'];
		spip_query("DELETE FROM spip_documents
			WHERE id_document=$doc_supp");
		spip_query("UPDATE spip_documents SET id_vignette=0
			WHERE id_vignette=$doc_supp");
		spip_query("DELETE FROM spip_documents_articles
			WHERE id_document=$doc_supp");
		spip_query("DELETE FROM spip_documents_rubriques
			WHERE id_document=$doc_supp");
		spip_query("DELETE FROM spip_documents_breves
			WHERE id_document=$doc_supp");
		@unlink($fichier);

		if ($id_vignette > 0) {
			$query = "SELECT id_vignette, fichier FROM spip_documents
				WHERE id_document=$id_vignette";
			$result = spip_query($query);
			if ($row = spip_fetch_array($result)) {
				$fichier = $row['fichier'];
				@unlink($fichier);
			}
			spip_query("DELETE FROM spip_documents
				WHERE id_document=$id_vignette");
			spip_query("DELETE FROM spip_documents_articles
				WHERE id_document=$id_vignette");
			spip_query("DELETE FROM spip_documents_rubriques
				WHERE id_document=$id_vignette");
			spip_query("DELETE FROM spip_documents_breves
				WHERE id_document=$id_vignette");
		}
	}
}



/////////////////////////////////////////////////////////////////////
//
// Faire tourner une image
//
function gdRotate ($imagePath,$rtt){
	if(preg_match("/\.(png)/i", $imagePath))
		$src_img=ImageCreateFromPNG($imagePath);
	else if(preg_match("/\.(jpg)/i", $imagePath))
		$src_img=ImageCreateFromJPEG($imagePath);
	else if(preg_match("/\.(bmp)/i", $imagePath))
		$src_img=ImageCreateFromWBMP($imagePath);
	$size=@getimagesize($imagePath);

	// Sous GD2 : ImageCreateTrueColor
	$process = lire_meta('image_process');
	if ($process == "gd2")
		$dst_img=ImageCreateTrueColor($size[1],$size[0]);
	else
		$dst_img=ImageCreate($size[1],$size[0]);

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

function tourner_document($var_rot, $doc_rotate, $convert_command) {
	
	$var_rot = intval($var_rot);

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
		}

		spip_query("UPDATE spip_documents SET largeur=$largeur, hauteur=$hauteur WHERE id_document=$doc_rotate");

	}
}

?>
