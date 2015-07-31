<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;
include_spip('inc/filtres_images_lib_mini'); // par precaution

// http://doc.spip.org/@couleur_html_to_hex
function couleur_html_to_hex($couleur){
	$couleurs_html=array(
		'aqua'=>'00FFFF','black'=>'000000','blue'=>'0000FF','fuchsia'=>'FF00FF','gray'=>'808080','green'=>'008000','lime'=>'00FF00','maroon'=>'800000',
		'navy'=>'000080','olive'=>'808000','purple'=>'800080','red'=>'FF0000','silver'=>'C0C0C0','teal'=>'008080','white'=>'FFFFFF','yellow'=>'FFFF00');
	if (isset($couleurs_html[$lc=strtolower($couleur)]))
		return $couleurs_html[$lc];
	return $couleur;
}

// http://doc.spip.org/@couleur_foncer
function couleur_foncer ($couleur, $coeff=0.5) {
	$couleurs = _couleur_hex_to_dec($couleur);

	$red = $couleurs["red"] - round(($couleurs["red"])*$coeff);
	$green = $couleurs["green"] - round(($couleurs["green"])*$coeff);
	$blue = $couleurs["blue"] - round(($couleurs["blue"])*$coeff);

	$couleur = _couleur_dec_to_hex($red, $green, $blue);
	
	return $couleur;
}

// http://doc.spip.org/@couleur_eclaircir
function couleur_eclaircir ($couleur, $coeff=0.5) {
	$couleurs = _couleur_hex_to_dec($couleur);

	$red = $couleurs["red"] + round((255 - $couleurs["red"])*$coeff);
	$green = $couleurs["green"] + round((255 - $couleurs["green"])*$coeff);
	$blue = $couleurs["blue"] + round((255 - $couleurs["blue"])*$coeff);

	$couleur = _couleur_dec_to_hex($red, $green, $blue);
	
	return $couleur;

}

// selectionner les images qui vont subir une transformation sur un critere de taille
// ls images exclues sont marquees d'une class no_image_filtrer qui bloque les filtres suivants
// dans la fonction image_filtrer
// http://doc.spip.org/@image_select
function image_select($img,$width_min=0, $height_min=0, $width_max=10000, $height_max=1000){
	if (!$img) return $img;
	list ($h,$l) = taille_image($img);
	$select = true;
	if ($l<$width_min OR $l>$width_max OR $h<$height_min OR $h>$height_max)
		$select = false;

	$class = extraire_attribut($img,'class');
	$p = strpos($class,'no_image_filtrer');
	if (($select==false) AND ($p===FALSE)){
		$class .= " no_image_filtrer";
		$img = inserer_attribut($img,'class',$class);
	}
	if (($select==true) AND ($p!==FALSE)){
		$class = preg_replace(",\s*no_image_filtrer,","",$class);
		$img = inserer_attribut($img,'class',$class);
	}
	return $img;
}


// http://doc.spip.org/@image_passe_partout
function image_passe_partout($img,$taille_x = -1, $taille_y = -1,$force = false,$cherche_image=false,$process='AUTO'){
	if (!$img) return '';
	list ($hauteur,$largeur) = taille_image($img);
	if ($taille_x == -1)
		$taille_x = isset($GLOBALS['meta']['taille_preview'])?$GLOBALS['meta']['taille_preview']:150;
	if ($taille_y == -1)
		$taille_y = $taille_x;

	if ($taille_x == 0 AND $taille_y > 0)
		$taille_x = 1; # {0,300} -> c'est 300 qui compte
	elseif ($taille_x > 0 AND $taille_y == 0)
		$taille_y = 1; # {300,0} -> c'est 300 qui compte
	elseif ($taille_x == 0 AND $taille_y == 0)
		return '';
	
	list($destWidth,$destHeight,$ratio) = ratio_passe_partout($largeur,$hauteur,$taille_x,$taille_y);
	$fonction = array('image_passe_partout', func_get_args());
	return process_image_reduire($fonction,$img,$destWidth,$destHeight,$force,$cherche_image,$process);
}

// http://doc.spip.org/@image_reduire
function image_reduire($img, $taille = -1, $taille_y = -1, $force=false, $cherche_image=false, $process='AUTO') {
	// Determiner la taille x,y maxi
	// prendre le reglage de previsu par defaut
	if ($taille == -1)
		$taille = (isset($GLOBALS['meta']['taille_preview']) AND intval($GLOBALS['meta']['taille_preview']))?intval($GLOBALS['meta']['taille_preview']):150;
	if ($taille_y == -1)
		$taille_y = $taille;

	if ($taille == 0 AND $taille_y > 0)
		$taille = 10000; # {0,300} -> c'est 300 qui compte
	elseif ($taille > 0 AND $taille_y == 0)
		$taille_y = 10000; # {300,0} -> c'est 300 qui compte
	elseif ($taille == 0 AND $taille_y == 0)
		return '';

	$fonction = array('image_reduire', func_get_args());
	return process_image_reduire($fonction,$img,$taille,$taille_y,$force,$cherche_image,$process);
}

// Reduire une image d'un certain facteur
// http://doc.spip.org/@image_reduire_par
function image_reduire_par ($img, $val=1, $force=false) {
	list ($hauteur,$largeur) = taille_image($img);

	$l = round($largeur/$val);
	$h = round($hauteur/$val);
	
	if ($l > $h) $h = 0;
	else $l = 0;
	
	$img = image_reduire($img, $l, $h, $force);

	return $img;
}

?>
