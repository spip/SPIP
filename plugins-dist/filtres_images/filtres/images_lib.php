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

// librairie de base du core
include_spip('inc/filtres_images_lib_mini');

function multiple_de_trois($val) {
	return intval(round($val / 3) * 3);
}

/**
 * Transformation d'une couleur vectorielle RGB en HSV
 * RGB entiers entre 0 et 255
 * HSV float entre 0 et 1
 * 
 * @param int $R
 * @param int $G
 * @param int $B
 * @return array
 */
function _couleur_rgb2hsv ($R,$G,$B) {
	$var_R = ( $R / 255 ) ;                    //Where RGB values = 0 Ã· 255
	$var_G = ( $G / 255 );
	$var_B = ( $B / 255 );

	$var_Min = min( $var_R, $var_G, $var_B ) ;   //Min. value of RGB
	$var_Max = max( $var_R, $var_G, $var_B ) ;   //Max. value of RGB
	$del_Max = $var_Max - $var_Min  ;           //Delta RGB value

	$V = $var_Max;
	$L = ( $var_Max + $var_Min ) / 2;
	
	if ( $del_Max == 0 )                     //This is a gray, no chroma...
	{
	   $H = 0 ;                            //HSL results = 0 Ã· 1
	   $S = 0 ;
	}
	else                                    //Chromatic data...
	{
	   $S = $del_Max / $var_Max;
	
	   $del_R = ( ( ( $var_Max - $var_R ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;
	   $del_G = ( ( ( $var_Max - $var_G ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;
	   $del_B = ( ( ( $var_Max - $var_B ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;
	
	   if      ( $var_R == $var_Max ) $H = $del_B - $del_G;
	   else if ( $var_G == $var_Max ) $H = ( 1 / 3 ) + $del_R - $del_B;
	   else if ( $var_B == $var_Max ) $H = ( 2 / 3 ) + $del_G - $del_R;
	
	   if ( $H < 0 )  $H =  $H + 1;
	   if ( $H > 1 )  $H = $H - 1;
	}
				
	$ret["h"] = $H;
	$ret["s"] = $S;
	$ret["v"] = $V;
	
	return $ret;
}

/**
 * Transformation d'une couleur vectorielle HSV en RGB
 * HSV float entre 0 et 1
 * RGB entiers entre 0 et 255
 * 
 * @param float $H
 * @param float $S
 * @param float $V
 * @return array
 */
function _couleur_hsv2rgb ($H,$S,$V) {
	
	if ( $S == 0 )                       //HSV values = 0 Ã· 1
	{
	   $R = $V * 255;
	   $G = $V * 255;
	   $B = $V * 255;
	}
	else
	{
	   $var_h = $H * 6;
	   if ( $var_h == 6 ) $var_h = 0 ;     //H must be < 1
	   $var_i = floor( $var_h )  ;           //Or ... var_i = floor( var_h )
	   $var_1 = $V * ( 1 - $S );
	   $var_2 = $V * ( 1 - $S * ( $var_h - $var_i ) );
	   $var_3 = $V * ( 1 - $S * ( 1 - ( $var_h - $var_i ) ) );
	
	
	   if      ( $var_i == 0 ) { $var_r = $V     ; $var_g = $var_3 ; $var_b = $var_1 ; }
	   else if ( $var_i == 1 ) { $var_r = $var_2 ; $var_g = $V     ; $var_b = $var_1 ; }
	   else if ( $var_i == 2 ) { $var_r = $var_1 ; $var_g = $V     ; $var_b = $var_3 ; }
	   else if ( $var_i == 3 ) { $var_r = $var_1 ; $var_g = $var_2 ; $var_b = $V ;     }
	   else if ( $var_i == 4 ) { $var_r = $var_3 ; $var_g = $var_1 ; $var_b = $V ; }
	   else                   { $var_r = $V     ; $var_g = $var_1 ; $var_b = $var_2; }
	
	   $R = $var_r * 255;                  //RGB results = 0 Ã· 255
	   $G = $var_g * 255;
	   $B = $var_b * 255;
	}
	$ret["r"] = floor($R);
	$ret["g"] = floor($G);
	$ret["b"] = floor($B);
	
	return $ret;
}


/**
 * Transformation d'une couleur RGB en HSL
 * HSL float entre 0 et 1
 * RGB entiers entre 0 et 255
 * 
 * @param int $R
 * @param int $G
 * @param int $B
 * @return array
 */
function _couleur_rgb2hsl ($R,$G,$B) {
	$var_R = ( $R / 255 ) ;                    //Where RGB values = 0 Ã· 255
	$var_G = ( $G / 255 );
	$var_B = ( $B / 255 );

	$var_Min = min( $var_R, $var_G, $var_B ) ;   //Min. value of RGB
	$var_Max = max( $var_R, $var_G, $var_B ) ;   //Max. value of RGB
	$del_Max = $var_Max - $var_Min  ;           //Delta RGB value

	$L = ( $var_Max + $var_Min ) / 2;
	
	if ( $del_Max == 0 )                     //This is a gray, no chroma...
	{
	   $H = 0 ;                            //HSL results = 0 Ã· 1
	   $S = 0 ;
	}
	else                                    //Chromatic data...
	{
		if ($L < 0.5 ) $S = $del_Max / ( $var_Max+ $var_Min);
		else $S = $del_Max/ ( 2 - $var_Max - $var_Min);

		$del_R = ( ( ( $var_Max- $var_R) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;
		$del_G = ( ( ( $var_Max- $var_G) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;
		$del_B = ( ( ( $var_Max- $var_B) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;

		if ( $var_R == $var_Max) $H= $del_B - $del_G;
		else if ( $var_G == $var_Max) $H= ( 1 / 3 ) + $del_R - $del_B;
		else if ( $var_B == $var_Max) $H= ( 2 / 3 ) + $del_G - $del_R;

		if ( $H < 0 ) $H+= 1;
		if ( $H > 1 ) $H-= 1;
	}
				
	$ret["h"] = $H;
	$ret["s"] = $S;
	$ret["l"] = $L;
	
	return $ret;
}

/**
 * Calcul d'une composante R, G ou B 
 *
 * @param unknown_type $v1
 * @param unknown_type $v2
 * @param unknown_type $vH
 * @return float
 */
function hue_2_rgb( $v1, $v2, $vH ) {
   if ( $vH < 0 ) $vH += 1;
   if ( $vH > 1 ) $vH -= 1;
   if ( ( 6 * $vH ) < 1 ) return ( $v1 + ( $v2 - $v1 ) * 6 * $vH );
   if ( ( 2 * $vH ) < 1 ) return ( $v2 );
   if ( ( 3 * $vH ) < 2 ) return ( $v1 + ( $v2 - $v1 ) * ( ( 2 / 3 ) - $vH ) * 6 );
   return ( $v1 );
}


/**
 * Transformation d'une couleur HSL en RGB
 * HSL float entre 0 et 1
 * RGB entiers entre 0 et 255
 *
 * @param float $H
 * @param float $S
 * @param float $L
 * @return array
 */
function _couleur_hsl2rgb ($H,$S,$L) {
	
	if ( $S == 0 )                       //HSV values = 0 -> 1
	{
	   $R = $L * 255;
	   $G = $L * 255;
	   $B = $L * 255;
	}
	else
	{
		if ( $L < 0.5 ) $var_2 = $L * ( 1 + $S );
		else            $var_2 = ( $L + $S ) - ( $S * $L );

		$var_1 = 2 * $L - $var_2;

		$R = 255 * hue_2_rgb( $var_1, $var_2, $H + ( 1 / 3 ) ) ;
		$G = 255 * hue_2_rgb( $var_1, $var_2, $H );
		$B = 255 * hue_2_rgb( $var_1, $var_2, $H - ( 1 / 3 ) );
	}
	$ret["r"] = floor($R);
	$ret["g"] = floor($G);
	$ret["b"] = floor($B);
	
	return $ret;
}

// A partir d'une image,
// recupere une couleur
// renvoit sous la forme hexadecimale ("F26C4E" par exemple).
// Par defaut, la couleur choisie se trouve un peu au-dessus du centre de l'image.
// On peut forcer un point en fixant $x et $y, entre 0 et 20.
// http://doc.spip.org/@image_couleur_extraire

function _image_couleur_extraire($img, $x=10, $y=6) {
	static $couleur_extraite = array();
	
	if (isset($couleur_extraite["$img-$x-$y"]))
		return $couleur_extraite["$img-$x-$y"];

	// valeur par defaut si l'image ne peut etre lue
	$defaut = "F26C4E";

	$cache = _image_valeurs_trans($img, "coul-$x-$y", "txt");
	if (!$cache) 
		return $couleur_extraite["$img-$x-$y"] = $defaut;

	
	$fichier = $cache["fichier"];	
	$dest = $cache["fichier_dest"];

	if (isset($couleur_extraite["$fichier-$x-$y"]))
		return $couleur_extraite["$fichier-$x-$y"];
	
	$creer = $cache["creer"];
	
	if ($creer) {
		if (@file_exists($fichier)) {
			$width = $cache["largeur"];
			$height = $cache["hauteur"];
		
			$newwidth = 20;
			$newheight = 20;
		
			$thumb = imagecreate($newwidth, $newheight);

			$source = $cache["fonction_imagecreatefrom"]($fichier);
			
			imagepalettetotruecolor($source);

			imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

			do {
				// get a color
				$color_index = imagecolorat($thumb, $x, $y);

				// make it human readable
				$color_tran = imagecolorsforindex($thumb, $color_index);
				$x++; $y++;
			} while ($color_tran['alpha']==127 AND $x<$newwidth AND $y<$newheight);
			
			$couleur = _couleur_dec_to_hex($color_tran["red"], $color_tran["green"], $color_tran["blue"]);
		}
		else {
			$couleur = $defaut;
		}
		
		// Mettre en cache le resultat
		$couleur_extraite["$fichier-$x-$y"] = $couleur;
		ecrire_fichier($dest,$couleur_extraite["$fichier-$x-$y"]);
	}
	else {
		lire_fichier($dest,$couleur_extraite["$fichier-$x-$y"]);
	}
	
	return $couleur_extraite["$img-$x-$y"]=$couleur_extraite["$fichier-$x-$y"];
}

// $src_img - a GD image resource
// $angle - degrees to rotate clockwise, in degrees
// returns a GD image resource
// script de php.net lourdement corrig'e
// (le bicubic deconnait completement,
// et j'ai ajoute la ponderation par la distance au pixel)
function _image_distance_pixel($xo, $yo, $x0, $y0) {
	$vx = $xo - $x0;
	$vy = $yo - $y0;
	$d = 1 - (sqrt(($vx)*($vx) + ($vy)*($vy)) / sqrt(2));
	return $d;
}


/**
 * Decale une composante de couleur
 * entier de 0 a 255
 *
 * @param int $coul
 * @param int $gamma
 * @return int
 */
function _image_decale_composante($coul, $gamma) {
	$coul = $coul + $gamma;
	
	if ($coul > 255) $coul = 255;
	if ($coul < 0) $coul = 0;
	return $coul;
}

/**
 * Decalage d'une composante de couleur en sepia
 * entier de 0 a 255
 * 
 * @param int $coul
 * @param int $val
 * @return int
 */
function _image_decale_composante_127($coul, $val) {
	if ($coul < 127) $y = round((($coul - 127) / 127) * $val) + $val;
	else if ($coul >= 127) $y = round((($coul - 127) / 128) * (255-$val)) + $val;
	else $y= $coul;
	
	if ($y < 0) $y = 0;
	if ($y > 255) $y = 255;
	return $y;
}

?>
