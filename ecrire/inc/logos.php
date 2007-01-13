<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;


// http://doc.spip.org/@cherche_image_nommee
function cherche_image_nommee($nom, $formats = array ('gif', 'jpg', 'png')) {

	if (ereg("^" . _DIR_IMG, $nom)) {
		$nom = substr($nom,strlen(_DIR_IMG));
	} else 	if (ereg("^" . _DIR_IMG_PACK, $nom)) {
		$nom = substr($nom,strlen(_DIR_IMG_PACK));
	} else if (ereg("^" . _DIR_IMG_ICONES_DIST, $nom)) {
		$nom = substr($nom,strlen(_DIR_IMG_ICONES_DIST));
	}
	$pos = strrpos($nom, "/");
	if ($pos > 0) {
		$chemin = substr($nom, 0, $pos+1);
		$nom = substr($nom, $pos+1);
	} else {
		$chemin = "";
	}

	reset($formats);
	while (list(, $format) = each($formats)) {
		if (@file_exists(_DIR_IMG . "$chemin$nom.$format")){ 
			return array((_DIR_IMG . $chemin), $nom, $format);
		} else if (@file_exists(_DIR_IMG_PACK . "$chemin$nom.$format")){ 
			return array((_DIR_IMG_PACK . $chemin), $nom, $format);
		} else if (@file_exists(_DIR_IMG_ICONES_DIST . "$chemin$nom.$format")){ 
			return array((_DIR_IMG_ICONES_DIST . $chemin), $nom, $format);
		}
	}
}

//
// Retourner taille d'une image
// pour les filtres |largeur et |hauteur
//
// http://doc.spip.org/@taille_image
function taille_image($img) {
	static $largeur_img =array(), $hauteur_img= array();

	if (eregi("width *= *['\"]?( *[0-9]+ *)", $img, $regs))
		$srcWidth = intval(trim($regs[1]));
	if (eregi("height *= *['\"]?( *[0-9]+ *)", $img, $regs))
		$srcHeight = intval(trim($regs[1]));

	// recuperer le nom du fichier
	if (eregi("src=[\"']([^'\"]+)[\"']", $img, $regs)) $logo = $regs[1];
	if (!$logo) $logo = $img;
	
	// pour essayer de limiter les lectures disque
	// $meme remplace $logo, pour unifier certains fichiers dont on sait qu'ils ont la meme taille
	$mem = $logo;
	if (strrpos($mem,"/") > 0) $mem = substr($mem, strrpos($mem,"/")+1, strlen($mem));
	$mem = ereg_replace("\-flip\_v|\-flip\_h", "", $mem);
	$mem = ereg_replace("\-nb\-[0-9]+(\.[0-9]+)?\-[0-9]+(\.[0-9]+)?\-[0-9]+(\.[0-9]+)?", "", $mem);

	$srcsize = false;
	if ($largeur_img[$mem] > 0) {
		$srcWidth = $largeur_img[$mem];
	} else {
		if (!$srcWidth AND $srcsize = @getimagesize($logo)) {
			$srcWidth = $srcsize[0];
		 	$largeur_img[$mem] = $srcWidth;
		 }
	}
	if ($hauteur_img[$mem] > 0) {
		$srcHeight = $hauteur_img[$mem];
	} else {
		if (!$srcHeight AND ($srcsize OR ($srcsize = @getimagesize($logo)))) {
			$srcHeight = $srcsize[1];
			$hauteur_img[$mem] = $srcHeight;
		}
	}
	return array($srcHeight, $srcWidth);
}

// http://doc.spip.org/@ratio_image
function ratio_image($logo, $nom, $format, $taille, $taille_y, $attributs) {
	// $logo est le nom complet du logo ($logo = "chemin/$nom.$format)
	// $nom et $format ne servent plus du fait du passage par le filtre image_reduire
	include_spip('inc/filtres');
	$res = filtrer('image_reduire',"<img src='$logo' $attributs />", $taille, $taille_y);
	return $res;
	/*if (!$taille_origine = @getimagesize($logo)) return '';
	include_spip('inc/filtres_images');
	list ($destWidth,$destHeight, $ratio) = image_ratio($taille_origine[0], $taille_origine[1], $taille, $taille_y);

	// Creer effectivement la vignette reduite
	$suffixe = '-'.$destWidth.'x'.$destHeight;
	$preview = creer_vignette($logo, $taille, $taille_y, $format, ('cache'.$suffixe), $nom.$suffixe);
	if ($preview) {
		$logo = $preview['fichier'];
		$destWidth = $preview['width'];
		$destHeight = $preview['height'];
	}

	// dans l'espace prive mettre un timestamp sur l'adresse 
	// de l'image, de facon a tromper le cache du navigateur
	// quand on fait supprimer/reuploader un logo
	// (pas de filemtime si SAFE MODE)
	$date = _DIR_RESTREINT ? '' : ('?date='.@filemtime($logo));
	return "<img src='$logo$date' width='".$destWidth."' height='".$destHeight."' $attributs />";
	*/
}

?>
