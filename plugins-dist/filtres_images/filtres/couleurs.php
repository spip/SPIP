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

include_spip('inc/filtres_images_lib_mini');

/**
 * Toutes les fonctions couleur_xx de ce fichier :
 * 	- prennent une couleur hexa sur 6 caracteres en entree (les couleurs web nommees sont admises aussi)
 *  - fournissent une couleur hexa en sortie
 *  - sont chainables les unes derrieres les autres dans toutes les combinaisons possibles
 */

// http://doc.spip.org/@couleur_extraire
function couleur_extraire ($img, $x=10, $y=6) {
	include_spip('filtres/images_lib');
	return _image_couleur_extraire($img, $x, $y);
}



// http://doc.spip.org/@couleur_web
function couleur_web($couleur) {
	include_spip('filtres/images_lib');
	$rvb = _couleur_hex_to_dec($couleur);
	
	$rvb = array_map('multiple_de_trois',$rvb);
	
	return _couleur_dec_to_hex($rvb['red'],$rvb['green'],$rvb['blue']);
}

// http://doc.spip.org/@couleur_4096
function couleur_4096($couleur) {
	$r = (substr($couleur, 0, 1));
	$v = (substr($couleur, 2, 1));
	$b = (substr($couleur, 4, 1));
	
	return "$r$r$v$v$b$b";
}


// http://doc.spip.org/@couleur_extreme
function couleur_extreme ($couleur, $limite=0.5) {
	// force la couleur au noir ou au blanc le plus proche
	// -> donc couleur foncee devient noire
	//    et couleur claire devient blanche
	// -> la limite est une valeur de 0 a 255, permettant de regler le point limite entre le passage noir ou blanc

	$couleurs = _couleur_hex_to_dec($couleur);
	$red = $couleurs["red"];
	$green = $couleurs["green"];
	$blue = $couleurs["blue"];
	
	
	/*	
	$moyenne = round(($red+$green+$blue)/3);

	if ($moyenne > $limite) $couleur_texte = "ffffff";
	else $couleur_texte = "000000";
	*/

	include_spip('filtres/images_lib');
	$hsl = _couleur_rgb2hsl ($red, $green, $blue);

	if ($hsl["l"] > $limite) $couleur_texte = "ffffff";
	else $couleur_texte = "000000";

	return $couleur_texte;
}

// http://doc.spip.org/@couleur_inverser
function couleur_inverser ($couleur) {
	$couleurs = _couleur_hex_to_dec($couleur);
	$red = 255 - $couleurs["red"];
	$green = 255 - $couleurs["green"];
	$blue = 255 - $couleurs["blue"];

	$couleur = _couleur_dec_to_hex($red, $green, $blue);
	
	return $couleur;
}

// http://doc.spip.org/@couleur_foncer_si_claire
function couleur_foncer_si_claire ($couleur, $seuil=122) {
	// ne foncer que les couleurs claires
	// utile pour ecrire sur fond blanc, 
	// mais sans changer quand la couleur est deja foncee
	$couleurs = _couleur_hex_to_dec($couleur);
	$red = $couleurs["red"];
	$green = $couleurs["green"];
	$blue = $couleurs["blue"];
	
	$moyenne = round(($red+$green+$blue)/3);
	
	if ($moyenne > $seuil) {
		include_spip("inc/filtres_images_mini");
		return couleur_foncer($couleur);
	}
	else return $couleur;
}

// http://doc.spip.org/@couleur_eclaircir_si_foncee
function couleur_eclaircir_si_foncee ($couleur, $seuil=123) {
	$couleurs = _couleur_hex_to_dec($couleur);
	$red = $couleurs["red"];
	$green = $couleurs["green"];
	$blue = $couleurs["blue"];
	
	$moyenne = round(($red+$green+$blue)/3);
	
	if ($moyenne < $seuil) {
		include_spip("inc/filtres_images_mini");
		return couleur_eclaircir($couleur);
	}
	else return $couleur;
}

// http://doc.spip.org/@couleur_saturation
function couleur_saturation($couleur, $val) {
	if ($couleur == "ffffff") $couleur = "eeeeee";

	$couleurs = _couleur_hex_to_dec($couleur);
	$r= 255 - $couleurs["red"];
	$g= 255 - $couleurs["green"];
	$b= 255 - $couleurs["blue"];

	$max = max($r,$g,$b);

	$r = 255 - $r / $max * 255 * $val;
	$g = 255 - $g / $max * 255 * $val;
	$b = 255 - $b / $max * 255 * $val;
	
	$couleur = _couleur_dec_to_hex($r, $g, $b);
	
	return $couleur;
		
}

// http://doc.spip.org/@couleur_luminance
function couleur_luminance($couleur, $val) {
	include_spip('filtres/images_lib');

	$couleurs = _couleur_hex_to_dec($couleur);
	$r= $couleurs["red"];
	$g= $couleurs["green"];
	$b= $couleurs["blue"];

	// Cas etonnant: quand gris parfait, la correction de HSL ne fonctionne pas
	// en revanche, couleur_saturation retourne exactement la bonne valeur
	if ($r == $g && $g == $b) return couleur_saturation($couleur, $val);

	
	$couleur = _couleur_rgb2hsl($r, $g, $b);
	$h = $couleur["h"];
	$s = $couleur["s"];
	$l = $couleur["l"];
	
	$rgb = _couleur_hsl2rgb ($h, $s, 1-$val);
	$r = $rgb["r"];
	$g = $rgb["g"];
	$b = $rgb["b"];
	
	$retour = _couleur_dec_to_hex($r, $g, $b);
	
	return $retour;
}

?>
