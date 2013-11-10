<?php
/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Ce fichier ne sert pas
 * Il est maintenu pour assurer la compatibilite des anciens scripts avec les anciens nommages de fonction
 *  
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

function couleur_dec_to_hex($red, $green, $blue) {include_spip('filtres/images_lib');return _couleur_dec_to_hex($red, $green, $blue);}
function couleur_hex_to_dec($couleur) {include_spip('filtres/images_lib');return _couleur_hex_to_dec($couleur);}
function couleur_rgb2hsv($R,$G,$B) {include_spip('filtres/images_lib');return _couleur_rgb2hsv($R,$G,$B);}
function couleur_hsv2rgb($H,$S,$V) {include_spip('filtres/images_lib');return _couleur_hsv2rgb($H,$S,$V);}
function couleur_rgb2hsl($R,$G,$B) {include_spip('filtres/images_lib');return _couleur_rgb2hsl($R,$G,$B);}
function couleur_hsl2rgb($H,$S,$L) {include_spip('filtres/images_lib');return _couleur_hsl2rgb($H,$S,$L);}
function image_couleur_extraire($img, $x=10, $y=6) {include_spip('filtres/images_lib');return _image_couleur_extraire($img, $x, $y);}
function image_distance_pixel($xo, $yo, $x0, $y0) {include_spip('filtres/images_lib');return _image_distance_pixel($xo, $yo, $x0, $y0);}
function image_decal_couleur($coul, $gamma) {include_spip('filtres/images_lib');return _image_decale_composante($coul, $gamma);}
function image_decal_couleur_127($coul, $val) {include_spip('filtres/images_lib');return _image_decale_composante_127 ($coul, $val);}

function image_creer_vignette($valeurs, $maxWidth, $maxHeight, $process='AUTO', $force=false, $test_cache_only = false) {include_spip('inc/filtres_images_lib'); return _image_creer_vignette($valeurs, $maxWidth, $maxHeight, $process, $force, $test_cache_only);}
function image_ecrire_tag($valeurs,$surcharge){include_spip('inc/filtres_images_lib'); return _image_ecrire_tag($valeurs,$surcharge);}
function image_gd_output($img,$valeurs, $qualite=_IMG_GD_QUALITE){include_spip('inc/filtres_images_lib'); return _image_gd_output($img,$valeurs, $qualite);}
function image_imagepng($img,$fichier) {include_spip('inc/filtres_images_lib'); return _image_imagepng($img,$fichier);}
function image_imagegif($img,$fichier) {include_spip('inc/filtres_images_lib'); return _image_imagegif($img,$fichier);}
function image_imagejpg($img,$fichier,$qualite=_IMG_GD_QUALITE) {include_spip('inc/filtres_images_lib'); return image_imagejpg($img,$fichier,$qualite=_IMG_GD_QUALITE);}
function image_imageico($img, $fichier) {include_spip('inc/filtres_images_lib'); return _image_imageico($img, $fichier);}
function image_ratio ($srcWidth, $srcHeight, $maxWidth, $maxHeight) {include_spip('inc/filtres_images_lib');return _image_ratio ($srcWidth, $srcHeight, $maxWidth, $maxHeight);}
function image_tag_changer_taille($tag,$width,$height,$style=false){include_spip('inc/filtres_images_lib');return _image_tag_changer_taille($tag,$width,$height,$style);}
function image_valeurs_trans($img, $effet, $forcer_format = false, $fonction_creation = NULL) {include_spip('inc/filtres_images_lib'); return _image_valeurs_trans($img, $effet, $forcer_format, $fonction_creation);}

// Pour assurer la compatibilite avec les anciens nom des filtres image_xxx
// commencent par "image_"
// http://doc.spip.org/@reduire_image
function reduire_image($texte, $taille = -1, $taille_y = -1) {
	return filtrer('image_graver',
		filtrer('image_reduire',$texte, $taille, $taille_y)
	);
}
// http://doc.spip.org/@valeurs_image_trans
function valeurs_image_trans($img, $effet, $forcer_format = false) {
	include_spip('inc/filtres_images_lib_mini');
	return _image_valeurs_trans($img, $effet, $forcer_format = false);
}

?>