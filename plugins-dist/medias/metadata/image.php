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

if (!defined('_ECRIRE_INC_VERSION')) return;

function metadata_image_dist($fichier){
	$meta = array();

	if ($size_image = @getimagesize($fichier)) {
		$meta['largeur'] = intval($size_image[0]);
		$meta['hauteur'] = intval($size_image[1]);
		$meta['type_image'] = decoder_type_image($size_image[2]);
	}

	return $meta;
}

/**
 * Convertit le type numerique retourne par getimagesize() en extension fichier
 *
 * @param int $type
 * @param bool $strict
 * @return string
 */
// http://doc.spip.org/@decoder_type_image
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
?>