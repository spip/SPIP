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
include_ecrire("inc_meta");

// Tester nos capacites
function tester_dist() {
	global $pnmscale_command,$arg;

	// verifier les formats acceptes par GD
	if ($arg == "gd1") {
		// Si GD est installe et php >= 4.0.2
		if (function_exists('imagetypes')) {

			if (imagetypes() & IMG_GIF) {
				$gd_formats[] = "gif";
			} else {
				# Attention GD sait lire le gif mais pas forcement l'ecrire
				if (function_exists('ImageCreateFromGIF')) {
					$srcImage = @ImageCreateFromGIF(_ROOT_IMG_PACK."test.gif");
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
				# les formats disponibles en ecriture... (cf. inc_logos)
		
			$gd_formats = Array();
			if (function_exists('ImageCreateFromJPEG')) {
				$srcImage = @ImageCreateFromJPEG(_ROOT_IMG_PACK."test.jpg");
				if ($srcImage) {
					$gd_formats[] = "jpg";
					ImageDestroy( $srcImage );
				}
			}
			if (function_exists('ImageCreateFromGIF')) {
				$srcImage = @ImageCreateFromGIF(_ROOT_IMG_PACK."test.gif");
				if ($srcImage) {
					$gd_formats[] = "gif";
					ImageDestroy( $srcImage );
				}
			}
			if (function_exists('ImageCreateFromPNG')) {
				$srcImage = @ImageCreateFromPNG(_ROOT_IMG_PACK."test.png");
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
	else if ($arg == "netpbm"
	AND $pnmscale_command) {
		$netpbm_formats= Array();

		$jpegtopnm_command = str_replace("pnmscale",
			"jpegtopnm", $pnmscale_command);
		$pnmtojpeg_command = str_replace("pnmscale",
			"pnmtojpeg", $pnmscale_command);

		$vignette = _ROOT_IMG_PACK."test.jpg";
		$dest = _DIR_IMG . "test-jpg.jpg";
		$commande = "$jpegtopnm_command $vignette | $pnmscale_command -width 10 | $pnmtojpeg_command > $dest";
		spip_log($commande);
		exec($commande);
		if ($taille = @getimagesize($dest)) {
			if ($taille[1] == 10) $netpbm_formats[] = "jpg";
		}
		$giftopnm_command = ereg_replace("pnmscale", "giftopnm", $pnmscale_command);
		$pnmtojpeg_command = ereg_replace("pnmscale", "pnmtojpeg", $pnmscale_command);
		$vignette = _ROOT_IMG_PACK."test.gif";
		$dest = _DIR_IMG . "test-gif.jpg";
		$commande = "$giftopnm_command $vignette | $pnmscale_command -width 10 | $pnmtojpeg_command > $dest";
		spip_log($commande);
		exec($commande);
		if ($taille = @getimagesize($dest)) {
			if ($taille[1] == 10) $netpbm_formats[] = "gif";
		}

		$pngtopnm_command = ereg_replace("pnmscale", "pngtopnm", $pnmscale_command);
		$vignette = _ROOT_IMG_PACK."test.png";
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
	if (ereg("^(gd1|gd2|imagick|convert|netpbm)$", $arg)) {
		include_ecrire('inc_logos');
		//$taille_preview = $GLOBALS['meta']["taille_preview"];
		if ($taille_preview < 10) $taille_preview = 150;
		if ($preview = creer_vignette(
		_ROOT_IMG_PACK.'test_image.jpg',
		$taille_preview, $taille_preview, 'jpg', '', "test_$arg", $arg, true)
		AND ($preview['width'] * $preview['height'] > 0))
			redirige_par_entete($preview['fichier']);
	}

	# image echec
	redirige_par_entete(_DIR_IMG_PACK . 'puce-rouge-anim.gif');
}
?>
