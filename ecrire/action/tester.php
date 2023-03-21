<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Gestion de l'action testant une librairie graphique
 *
 * @package SPIP\Core\Configurer
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Tester les capacités du serveur à utiliser une librairie graphique
 *
 * L'argument transmis dans la clé `arg` est le type de librairie parmi
 * gd2, netpbm, imagick ou convert
 *
 * L'action crée une vignette en utilisant la librairie indiquée puis
 * redirige sur l'image ainsi créée (sinon sur une image d'echec).
 **/
function action_tester_dist() {
	$arg = _request('arg');

	// verifier les formats acceptes par GD
	if ($arg === 'gd2') {
		$gd_formats = [];

		if (function_exists('imagetypes')) {
			if (imagetypes() & IMG_GIF) {
				$gd_formats[] = 'gif';
			}
			if (imagetypes() & IMG_JPG) {
				$gd_formats[] = 'jpg';
			}
			if (imagetypes() & IMG_PNG) {
				$gd_formats[] = 'png';
			}
			if (imagetypes() & IMG_WEBP) {
				$gd_formats[] = 'webp';
			}
		}

		$gd_formats = implode(',', $gd_formats);
		ecrire_meta('gd_formats_read', $gd_formats);
		ecrire_meta('gd_formats', $gd_formats);
	} elseif ($arg == 'netpbm') {
		// verifier les formats netpbm
		if (!defined('_PNMSCALE_COMMAND')) {
			define('_PNMSCALE_COMMAND', 'pnmscale');
		} // Securite : mes_options.php peut preciser le chemin absolu
		if (_PNMSCALE_COMMAND == '') {
			return;
		}
		$netpbm_formats = [];

		$jpegtopnm_command = str_replace(
			'pnmscale',
			'jpegtopnm',
			(string) _PNMSCALE_COMMAND
		);
		$pnmtojpeg_command = str_replace(
			'pnmscale',
			'pnmtojpeg',
			(string) _PNMSCALE_COMMAND
		);

		$vignette = _ROOT_IMG_PACK . 'test.jpg';
		$dest = _DIR_VAR . 'test-jpg.jpg';
		$commande = "$jpegtopnm_command $vignette | " . _PNMSCALE_COMMAND . " -width 10 | $pnmtojpeg_command > $dest";
		spip_log($commande);
		exec($commande);
		if (($taille = @getimagesize($dest)) && $taille[1] == 10) {
			$netpbm_formats[] = 'jpg';
		}
		$giftopnm_command = str_replace('pnmscale', 'giftopnm', (string) _PNMSCALE_COMMAND);
		$pnmtojpeg_command = str_replace('pnmscale', 'pnmtojpeg', (string) _PNMSCALE_COMMAND);
		$vignette = _ROOT_IMG_PACK . 'test.gif';
		$dest = _DIR_VAR . 'test-gif.jpg';
		$commande = "$giftopnm_command $vignette | " . _PNMSCALE_COMMAND . " -width 10 | $pnmtojpeg_command > $dest";
		spip_log($commande);
		exec($commande);
		if (($taille = @getimagesize($dest)) && $taille[1] == 10) {
			$netpbm_formats[] = 'gif';
		}

		$pngtopnm_command = str_replace('pnmscale', 'pngtopnm', (string) _PNMSCALE_COMMAND);
		$vignette = _ROOT_IMG_PACK . 'test.png';
		$dest = _DIR_VAR . 'test-gif.jpg';
		$commande = "$pngtopnm_command $vignette | " . _PNMSCALE_COMMAND . " -width 10 | $pnmtojpeg_command > $dest";
		spip_log($commande);
		exec($commande);
		if (($taille = @getimagesize($dest)) && $taille[1] == 10) {
			$netpbm_formats[] = 'png';
		}

		ecrire_meta('netpbm_formats', implode(',', $netpbm_formats ?: []));
	}

	// et maintenant envoyer la vignette de tests
	if (in_array($arg, ['gd2', 'imagick', 'convert', 'netpbm'])) {
		include_spip('inc/filtres');
		include_spip('inc/filtres_images_mini');
		$taille_preview = 150;
		$image = _image_valeurs_trans(_DIR_IMG_PACK . 'test_image.jpg', "reduire-$taille_preview-$taille_preview", 'jpg');

		$image['fichier_dest'] = _DIR_VAR . "test_$arg";

		if (
			($preview = _image_creer_vignette($image, $taille_preview, $taille_preview, $arg, true))
			&& $preview['width'] * $preview['height'] > 0
		) {
			redirige_par_entete($preview['fichier']);
		}
	}

	# image echec
	redirige_par_entete(chemin_image('echec-reducteur-xx.svg'));
}
