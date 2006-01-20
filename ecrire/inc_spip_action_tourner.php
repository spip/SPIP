<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_ecrire("inc_charsets");	# pour le nom de fichier
include_ecrire("inc_abstract_sql");# spip_insert / spip_fetch...

function spip_action_tourner_dist() {
	
	global $arg, $var_rot, $convert_command;
	$var_rot = intval($var_rot);
	$arg = intval($arg);

	$query = "SELECT id_vignette, fichier FROM spip_documents WHERE id_document=$arg";
	$result = spip_query($query);
	if ($row = spip_fetch_array($result)) {
		$id_vignette = $row['id_vignette'];
		$image = $row['fichier'];

		$process = $GLOBALS['meta']['image_process'];

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

/*
	A DESACTIVER PEUT-ETRE ? QUE SE PASSE--IL SI JE TOURNE UNE IMAGE AYANT UNE VGNETTE "MANUELLE" -> NE PAS CREER DE VIGNETTE TOURNEE -- EN VERITE IL NE FAUT PAS PERMETTRE DE TOURNER UNE IMAGE AYANT UNE VIGNETTE MANUELLE
		if ($id_vignette > 0) {
			creer_fichier_vignette($image);
		}
*/

		spip_query("UPDATE spip_documents SET largeur=$largeur, hauteur=$hauteur WHERE id_document=$arg");

	}
        $GLOBALS['redirect'] = _DIR_RESTREINT_ABS . $GLOBALS['redirect'];
	$GLOBALS['redirect'] .=	'&show_docs='.$arg;
        if ($GLOBALS['ancre'])  $GLOBALS['redirect'] .= '#'. $GLOBALS['ancre'];
}


/////////////////////////////////////////////////////////////////////
//
// Faire tourner une image
//
function gdRotate ($imagePath,$rtt){
	if(preg_match("/\.(png|gif|jpe?g|bmp)$/i", $imagePath, $regs)) {
		switch($regs[1]) {
			case 'png':
				$src_img=ImageCreateFromPNG($imagePath);
				$save = 'imagepng';
				break;
			case 'gif':
				$src_img=ImageCreateFromGIF($imagePath);
				$save = 'imagegif';
				break;
			case 'jpeg':
			case 'jpg':
				$src_img=ImageCreateFromJPEG($imagePath);
				$save = 'Imagejpeg';
				break;
			case 'bmp':
				$src_img=ImageCreateFromWBMP($imagePath);
				$save = 'imagewbmp';
				break;
			default:
				return false;
		}
	}

	if (!$src_img) {
		spip_log("gdrotate: image non lue, $imagePath");
		return false;
	}

	$size=@getimagesize($imagePath);
	if (!($size[0] * $size[1])) return false;

	if (function_exists('imagerotate')) {
		$dst_img = imagerotate($src_img, -$rtt, 0);
	} else {

	// Creer l'image destination (hauteur x largeur) et la parcourir
	// pixel par pixel (un truc de fou)
	$process = $GLOBALS['meta']['image_process'];
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
	}
	ImageDestroy($src_img);
	ImageInterlace($dst_img,0);

	# obligatoire d'enregistrer dans le meme format, puisque c'est
	# dans le fichier de depart...
	$save($dst_img,$imagePath);
}

// Creation
function creer_fichier_vignette($vignette, $test_cache_only=false) {
	if ($vignette && $GLOBALS['meta']["creer_preview"] == 'oui') {
		eregi('\.([a-z0-9]+)$', $vignette, $regs);
		$ext = $regs[1];
		$taille_preview = $GLOBALS['meta']["taille_preview"];
		if ($taille_preview < 10) $taille_preview = 120;
		include_ecrire('inc_logos');

		if ($preview = creer_vignette($vignette, $taille_preview, $taille_preview, $ext, 'vignettes', basename($vignette).'-s', 'AUTO', false, $test_cache_only))
		{
			inserer_vignette_base($vignette, $preview['fichier']);
			return $preview['fichier'];
		}
		include_ecrire('inc_documents');
		return vignette_par_defaut($ext ? $ext : 'txt', false);
	}
}


// Insertion d'une vignette dans la base
function inserer_vignette_base($image, $vignette) {

	$taille = @filesize($vignette);
	
	$size = @getimagesize($vignette);
	$largeur = $size[0];
	$hauteur = $size[1];
	$type = $size[2];

	if ($type == "2") $format = 1;			# spip_types_documents
	else if ($type == "3") $format = 2;
	else if ($type == "1") $format = 3;
	else return;

	$vignette = str_replace(_DIR_RACINE, '', $vignette);

	spip_log("creation vignette($image) -> $vignette");

	if ($t = spip_query("SELECT id_document FROM spip_documents
	WHERE fichier='".addslashes($image)."'")) {
		if ($row = spip_fetch_array($t)) {
			$id_document = $row['id_document'];
			$id_vignette = spip_abstract_insert("spip_documents", 
				"(mode)",
				"('vignette')");
			spip_query("UPDATE spip_documents
				SET id_vignette=$id_vignette WHERE id_document=$id_document");
			spip_query("UPDATE spip_documents SET
				id_type = '$format',
				largeur = '$largeur',
				hauteur = '$hauteur',
				taille = '$taille',
				fichier = '$vignette',
				date = NOW()
				WHERE id_document = $id_vignette");
			spip_log("(document=$id_document, vignette=$id_vignette)");
		}
	}
}
?>
