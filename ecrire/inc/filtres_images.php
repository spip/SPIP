<?php
/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

include_spip('inc/filtres_images_mini');

// Transforme l'image en PNG transparent
// alpha = 0: aucune transparence
// alpha = 127: completement transparent
// http://doc.spip.org/@image_alpha
function image_alpha($im, $alpha = 63)
{
	$fonction = array('image_alpha', func_get_args());
	$image = image_valeurs_trans($im, "alpha-$alpha", "png",$fonction);
	if (!$image) return("");
	
	$x_i = $image["largeur"];
	$y_i = $image["hauteur"];
	
	$im = $image["fichier"];
	$dest = $image["fichier_dest"];
	
	$creer = $image["creer"];
	
	if ($creer) {
		// Creation de l'image en deux temps
		// de facon a conserver les GIF transparents
		$im = $image["fonction_imagecreatefrom"]($im);
		imagepalettetotruecolor($im);
		$im2 = imagecreatetruecolor($x_i, $y_i);
		@imagealphablending($im2, false);
		@imagesavealpha($im2,true);
		$color_t = ImageColorAllocateAlpha( $im2, 255, 255, 255 , 127 );
		imagefill ($im2, 0, 0, $color_t);
		imagecopy($im2, $im, 0, 0, 0, 0, $x_i, $y_i);

		$im_ = imagecreatetruecolor($x_i, $y_i);
		imagealphablending ($im_, FALSE );
		imagesavealpha ( $im_, TRUE );



		for ($x = 0; $x < $x_i; $x++) {
			for ($y = 0; $y < $y_i; $y++) {
				$rgb = ImageColorAt($im2, $x, $y);
				
				if (function_exists('imagecolorallocatealpha')) {
					$a = ($rgb >> 24) & 0xFF;
					$r = ($rgb >> 16) & 0xFF;
					$g = ($rgb >> 8) & 0xFF;
					$b = $rgb & 0xFF;
					
					
					$a_ = $alpha + $a - round($a*$alpha/127);
					$rgb = imagecolorallocatealpha($im_, $r, $g, $b, $a_);
				}
				imagesetpixel ( $im_, $x, $y, $rgb );
			}
		}
		image_gd_output($im_,$image);
		imagedestroy($im_);
		imagedestroy($im);
		imagedestroy($im2);
	}
	

	return image_ecrire_tag($image,array('src'=>$dest));
	
}

// http://doc.spip.org/@image_recadre
function image_recadre($im,$width,$height,$position='center', $background_color='white')
{
	$fonction = array('image_recadre', func_get_args());
	$image = image_valeurs_trans($im, "recadre-$width-$height-$position-$background_color",false,$fonction);
	
	if (!$image) return("");
	
	$x_i = $image["largeur"];
	$y_i = $image["hauteur"];
	
	if ($width==0) $width=$x_i;
	if ($height==0) $height=$y_i;
	
	$offset_width = $x_i-$width;
	$offset_height = $y_i-$height;
	$position=strtolower($position);
	if (strpos($position,'left')!==FALSE){
		if (preg_match(';left=(\d{1}\d+);', $position, $left)){
			$offset_width=$left[1];	
		}
		else{
			$offset_width=0;
		}
	}
	elseif (strpos($position,'right')!==FALSE)
		$offset_width=$offset_width;
	else
		$offset_width=intval(ceil($offset_width/2));

	if (strpos($position,'top')!==FALSE){
		if (preg_match(';top=(\d{1}\d+);', $position, $top)){
			$offset_height=$top[1];
		}
		else{
			$offset_height=0;
		}
	}
	elseif (strpos($position,'bottom')!==FALSE)
		$offset_height=$offset_height;
	else
		$offset_height=intval(ceil($offset_height/2));
	
	$im = $image["fichier"];
	$dest = $image["fichier_dest"];
	
	$creer = $image["creer"];
	
	if ($creer) {
		$im = $image["fonction_imagecreatefrom"]($im);
		imagepalettetotruecolor($im);
		$im_ = imagecreatetruecolor($width, $height);
		@imagealphablending($im_, false);
		@imagesavealpha($im_,true);

		if ($background_color=='transparent')
			$color_t = imagecolorallocatealpha( $im_, 255, 255, 255 , 127 );
		else {
			$bg = couleur_hex_to_dec($background_color);
			$color_t = imagecolorallocate( $im_, $bg['red'], $bg['green'], $bg['blue']);
		}
		imagefill ($im_, 0, 0, $color_t);
		imagecopy($im_, $im, max(0,-$offset_width), max(0,-$offset_height), max(0,$offset_width), max(0,$offset_height), min($width,$x_i), min($height,$y_i));

		image_gd_output($im_,$image);
		imagedestroy($im_);
		imagedestroy($im);
	}
	
	return image_ecrire_tag($image,array('src'=>$dest,'width'=>$width,'height'=>$height));
}

// http://doc.spip.org/@image_flip_vertical
function image_flip_vertical($im)
{
	$fonction = array('image_flip_vertical', func_get_args());
	$image = image_valeurs_trans($im, "flip_v", false,$fonction);
	if (!$image) return("");
	
	$x_i = $image["largeur"];
	$y_i = $image["hauteur"];
	
	$im = $image["fichier"];
	$dest = $image["fichier_dest"];
	
	$creer = $image["creer"];
	
	if ($creer) {
		$im = $image["fonction_imagecreatefrom"]($im);
		imagepalettetotruecolor($im);
		$im_ = imagecreatetruecolor($x_i, $y_i);
		@imagealphablending($im_, false);
		@imagesavealpha($im_,true);
	
		$color_t = ImageColorAllocateAlpha( $im_, 255, 255, 255 , 127 );
		imagefill ($im_, 0, 0, $color_t);

		for ($x = 0; $x < $x_i; $x++) {
			for ($y = 0; $y < $y_i; $y++) {
				imagecopy($im_, $im, $x_i - $x - 1, $y, $x, $y, 1, 1);
			}
		}

		image_gd_output($im_,$image);
		imagedestroy($im_);
		imagedestroy($im);
	}
	
	return image_ecrire_tag($image,array('src'=>$dest));
}

// http://doc.spip.org/@image_flip_horizontal
function image_flip_horizontal($im)
{
	$fonction = array('image_flip_horizontal', func_get_args());
	$image = image_valeurs_trans($im, "flip_h",false,$fonction);
	if (!$image) return("");
	
	$x_i = $image["largeur"];
	$y_i = $image["hauteur"];
	
	$im = $image["fichier"];
	$dest = $image["fichier_dest"];
	
	$creer = $image["creer"];
	
	if ($creer) {
		$im = $image["fonction_imagecreatefrom"]($im);
		imagepalettetotruecolor($im);
		$im_ = imagecreatetruecolor($x_i, $y_i);
		@imagealphablending($im_, false);
		@imagesavealpha($im_,true);
	
		$color_t = ImageColorAllocateAlpha( $im_, 255, 255, 255 , 127 );
		imagefill ($im_, 0, 0, $color_t);

		for ($x = 0; $x < $x_i; $x++) {
			for ($y = 0; $y < $y_i; $y++) {
   				imagecopy($im_, $im, $x, $y_i - $y - 1, $x, $y, 1, 1);
			}
		}
		image_gd_output($im_,$image);
		imagedestroy($im_);
		imagedestroy($im);
	}
	
	return image_ecrire_tag($image,array('src'=>$dest));
}

// http://doc.spip.org/@image_masque
function image_masque($im, $masque, $pos="") {
	// Passer, en plus de l'image d'origine,
	// une image de "masque": un fichier PNG24 transparent.
	// Le decoupage se fera selon la transparence du "masque",
	// et les couleurs seront eclaircies/foncees selon de couleur du masque.
	// Pour ne pas modifier la couleur, le masque doit etre en gris 50%.
	//
	// Si l'image source est plus grande que le masque, alors cette image est reduite a la taille du masque.
	// Sinon, c'est la taille de l'image source qui est utilisee.
	//
	// $pos est une variable libre, qui permet de passer left=..., right=..., bottom=..., top=...
	// dans ce cas, le pasque est place a ces positions sur l'image d'origine,
	// et evidemment cette image d'origine n'est pas redimensionnee
	// 
	// Positionnement horizontal: text-align=left, right, center
	// Positionnement vertical : vertical-align: top, bottom, middle
	// (les positionnements left, right, top, left sont relativement inutiles, mais coherence avec CSS)
	//
	// Choix du mode de fusion: mode=masque, normal, eclaircir, obscurcir, produit, difference
	// masque: mode par defaut
	// normal: place la nouvelle image par dessus l'ancienne
	// eclaircir: place uniquement les points plus clairs
	// obscurcir: place uniquement les points plus fonc'es
	// produit: multiplie par le masque (points noirs rendent l'image noire, points blancs ne changent rien)
	// difference: remplit avec l'ecart entre les couleurs d'origine et du masque

	$mode = "masque";


	$numargs = func_num_args();
	$arg_list = func_get_args();
	$texte = $arg_list[0];
	for ($i = 1; $i < $numargs; $i++) {
		if ( ($p = strpos($arg_list[$i],"=")) !==false) {
			$nom_variable = substr($arg_list[$i], 0, $p);
			$val_variable = substr($arg_list[$i], $p+1);
			$variable["$nom_variable"] = $val_variable;
			$defini["$nom_variable"] = 1;
		}
	}
	if ($defini["mode"]) $mode = $variable["mode"];

	$masque = find_in_path($masque);
	$pos = md5(serialize($variable).@filemtime($masque));

	$fonction = array('image_masque', func_get_args());
	$image = image_valeurs_trans($im, "masque-$masque-$pos", "png",$fonction);
	if (!$image) return("");

	$x_i = $image["largeur"];
	$y_i = $image["hauteur"];
	
	$im = $image["fichier"];
	$dest = $image["fichier_dest"];
	
	$creer = $image["creer"];


	if ($defini["right"] OR $defini["left"] OR $defini["bottom"] OR $defini["top"] OR $defini["text-align"] OR $defini["vertical-align"]) {
		$placer = true;
	}
	else $placer = false;

	if ($creer) {
		
		$mask = image_valeurs_trans($masque,"");
		if (!is_array($mask)) return("");
		$im_m = $mask["fichier"];
		$x_m = $mask["largeur"];
		$y_m = $mask["hauteur"];
	
		$im2 = $mask["fonction_imagecreatefrom"]($masque);
		if ($mask["format_source"] == "gif" AND function_exists('ImageCopyResampled')) { 
			$im2_ = imagecreatetruecolor($x_m, $y_m);
			// Si un GIF est transparent, 
			// fabriquer un PNG transparent  
			// Conserver la transparence 
			if (function_exists("imageAntiAlias")) imageAntiAlias($im2_,true); 
			@imagealphablending($im2_, false); 
			@imagesavealpha($im2_,true); 
			@ImageCopyResampled($im2_, $im2, 0, 0, 0, 0, $x_m, $y_m, $x_m, $y_m);
			imagedestroy($im2);
			$im2 = $im2_;
		}
		
		if ($placer) {
			// On fabriquer une version "agrandie" du masque,
			// aux dimensions de l'image source
			// et on "installe" le masque dans cette image
			// ainsi: aucun redimensionnement
			
			$dx = 0;
			$dy = 0;
			
			if ($defini["right"]) {
				$right = $variable["right"];
				$dx = ($x_i - $x_m) - $right;
			}
			if ($defini["bottom"]) {
				$bottom = $variable["bottom"];
				$dy = ($y_i - $y_m) - $bottom;
				}
			if ($defini["top"]) {
				$top = $variable["top"];
				$dy = $top;
			}
			if ($defini["left"]) {
				$left = $variable["left"];
				$dx = $left;
			}
			if ($defini["text-align"]) {
				$align = $variable["text-align"];
				if ($align == "right") {
					$right = 0;
					$dx = ($x_i - $x_m);
				} else if ($align == "left") {
					$left = 0;
					$dx = 0;
				} else if ($align = "center") {
					$dx = round( ($x_i - $x_m) / 2 ) ;
				}
			}
			if ($defini["vertical-align"]) {
				$valign = $variable["vertical-align"];
				if ($valign == "bottom") {
					$bottom = 0;
					$dy = ($y_i - $y_m);
				} else if ($valign == "top") {
					$top = 0;
					$dy = 0;
				} else if ($valign = "middle") {
					$dy = round( ($y_i - $y_m) / 2 ) ;
				}
			}
			
			
			$im3 = imagecreatetruecolor($x_i, $y_i);
			@imagealphablending($im3, false);
			@imagesavealpha($im3,true);
			if ($mode == "masque") $color_t = ImageColorAllocateAlpha( $im3, 128, 128, 128 , 0 );
			else $color_t = ImageColorAllocateAlpha( $im3, 128, 128, 128 , 127 );
			imagefill ($im3, 0, 0, $color_t);

			

			imagecopy ( $im3, $im2, $dx, $dy, 0, 0, $x_m, $y_m);	

			imagedestroy($im2);
			$im2 = imagecreatetruecolor($x_i, $y_i);
			@imagealphablending($im2, false);
			@imagesavealpha($im2,true);
			
			
			
			imagecopy ( $im2, $im3, 0, 0, 0, 0, $x_i, $y_i);			
			imagedestroy($im3);
			$x_m = $x_i;
			$y_m = $y_i;
		}
		
	
		$rapport = $x_i / $x_m;
		if (($y_i / $y_m) < $rapport ) {
			$rapport = $y_i / $y_m;
		}
			
		$x_d = ceil($x_i / $rapport);
		$y_d = ceil($y_i / $rapport);
		

		if ($x_i < $x_m OR $y_i < $y_m) {
			$x_dest = $x_i;
			$y_dest = $y_i;
			$x_dec = 0;
			$y_dec = 0;
		} else {
			$x_dest = $x_m;
			$y_dest = $y_m;
			$x_dec = round(($x_d - $x_m) /2);
			$y_dec = round(($y_d - $y_m) /2);
		}


		$nouveau = image_valeurs_trans(image_reduire($im, $x_d, $y_d),"");
		if (!is_array($nouveau)) return("");
		$im_n = $nouveau["fichier"];
		
	
		$im = $nouveau["fonction_imagecreatefrom"]($im_n);
		imagepalettetotruecolor($im);
		if ($nouveau["format_source"] == "gif" AND function_exists('ImageCopyResampled')) { 
			$im_ = imagecreatetruecolor($x_dest, $y_dest);
			// Si un GIF est transparent, 
			// fabriquer un PNG transparent  
			// Conserver la transparence 
			if (function_exists("imageAntiAlias")) imageAntiAlias($im_,true); 
			@imagealphablending($im_, false); 
			@imagesavealpha($im_,true); 
			@ImageCopyResampled($im_, $im, 0, 0, 0, 0, $x_dest, $y_dest, $x_dest, $y_dest);
			imagedestroy($im);
			$im = $im_;
		}
		$im_ = imagecreatetruecolor($x_dest, $y_dest);
		@imagealphablending($im_, false);
		@imagesavealpha($im_,true);
		$color_t = ImageColorAllocateAlpha( $im_, 255, 255, 255 , 127 );
		imagefill ($im_, 0, 0, $color_t);


		for ($x = 0; $x < $x_dest; $x++) {
			for ($y=0; $y < $y_dest; $y++) {
				$rgb = ImageColorAt($im2, $x, $y);
				$a = ($rgb >> 24) & 0xFF;
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				

				$rgb2 = ImageColorAt($im, $x+$x_dec, $y+$y_dec);
				$a2 = ($rgb2 >> 24) & 0xFF;
				$r2 = ($rgb2 >> 16) & 0xFF;
				$g2 = ($rgb2 >> 8) & 0xFF;
				$b2 = $rgb2 & 0xFF;
				
				
				
				if ($mode == "normal") {
					$v = (127 - $a) / 127;
					if ($v == 1) {
						$r_ = $r;
						$g_ = $g;
						$b_ = $b;
					} else {
						$v2 = (127 - $a2) / 127;
						if ($v+$v2 == 0) {
							$r_ = $r2;
							$g_ = $g2;
							$b_ = $b2;
						} else if ($v2 ==0) {
							$r_ = $r;
							$g_ = $g;
							$b_ = $b;
						} else if ($v == 0) {
							$r_ = $r2;
							$g_ = $g2;
							$b_ = $b2;
						}else {
							$r_ = $r + (($r2 - $r) * $v2 * (1 - $v));
							$g_ = $g + (($g2 - $g) * $v2 * (1 - $v));
							$b_ = $b + (($b2 - $b) * $v2 * (1 - $v));
						}
					}
					$a_ = min($a,$a2);
				} elseif ($mode == "produit" OR $mode == "difference") {					

					if ($mode == "produit") {
						$r = ($r/255) * $r2;
						$g = ($g/255) * $g2;
						$b = ($b/255) * $b2;
					} else if ($mode == "difference") {
						$r = abs($r-$r2);
						$g = abs($g-$g2);
						$b = abs($b-$b2);				
					}

					$r = max(0, min($r, 255));
					$g = max(0, min($g, 255));
					$b = max(0, min($b, 255));

					$v = (127 - $a) / 127;
					if ($v == 1) {
						$r_ = $r;
						$g_ = $g;
						$b_ = $b;
					} else {
						$v2 = (127 - $a2) / 127;
						if ($v+$v2 == 0) {
							$r_ = $r2;
							$g_ = $g2;
							$b_ = $b2;
						} else {
							$r_ = $r + (($r2 - $r) * $v2 * (1 - $v));
							$g_ = $g + (($g2 - $g) * $v2 * (1 - $v));
							$b_ = $b + (($b2 - $b) * $v2 * (1 - $v));
						}
					}


					$a_ = $a2;
				} elseif ($mode == "eclaircir" OR $mode == "obscurcir") {
					$v = (127 - $a) / 127;
					if ($v == 1) {
						$r_ = $r;
						$g_ = $g;
						$b_ = $b;
					} else {
						$v2 = (127 - $a2) / 127;
						if ($v+$v2 == 0) {
							$r_ = $r2;
							$g_ = $g2;
							$b_ = $b2;
						} else {
							$r_ = $r + (($r2 - $r) * $v2 * (1 - $v));
							$g_ = $g + (($g2 - $g) * $v2 * (1 - $v));
							$b_ = $b + (($b2 - $b) * $v2 * (1 - $v));
						}
					}
					if ($mode == "eclaircir") {
						$r_ = max ($r_, $r2);
						$g_ = max ($g_, $g2);
						$b_ = max ($b_, $b2);
					} else {
						$r_ = min ($r_, $r2);
						$g_ = min ($g_, $g2);
						$b_ = min ($b_, $b2);					
					}
					
					$a_ = min($a,$a2);
				} else {
					$r_ = $r2 + 1 * ($r - 127);
					$r_ = max(0, min($r_, 255));
					$g_ = $g2 + 1 * ($g - 127);
					$g_ = max(0, min($g_, 255));
					$b_ = $b2 + 1 * ($b - 127);
					$b_ = max(0, min($b_, 255));
					
					$a_ = $a + $a2 - round($a*$a2/127);
				}

				$color = ImageColorAllocateAlpha( $im_, $r_, $g_, $b_ , $a_ );
				imagesetpixel ($im_, $x, $y, $color);			
			}
		}

		image_gd_output($im_,$image);
		imagedestroy($im_);
		imagedestroy($im);
		imagedestroy($im2);

	}
	$x_dest = largeur($dest);
	$y_dest = hauteur($dest);
	return image_ecrire_tag($image,array('src'=>$dest,'width'=>$x_dest,'height'=>$y_dest));
}

// Passage de l'image en noir et blanc
// un noir & blanc "photo" n'est pas "neutre": les composantes de couleur sont
// ponderees pour obtenir le niveau de gris;
// on peut ici regler cette ponderation en "pour mille"
// http://doc.spip.org/@image_nb
function image_nb($im, $val_r = 299, $val_g = 587, $val_b = 114)
{
	$fonction = array('image_nb', func_get_args());
	$image = image_valeurs_trans($im, "nb-$val_r-$val_g-$val_b",false,$fonction);
	if (!$image) return("");
	
	$x_i = $image["largeur"];
	$y_i = $image["hauteur"];
	
	$im = $image["fichier"];
	$dest = $image["fichier_dest"];
	
	$creer = $image["creer"];
	
	// Methode precise
	// resultat plus beau, mais tres lourd
	// Et: indispensable pour preserver transparence!

	if ($creer) {
		// Creation de l'image en deux temps
		// de facon a conserver les GIF transparents
		$im = $image["fonction_imagecreatefrom"]($im);
		imagepalettetotruecolor($im);
		$im_ = imagecreatetruecolor($x_i, $y_i);
		@imagealphablending($im_, false);
		@imagesavealpha($im_,true);
		$color_t = ImageColorAllocateAlpha( $im_, 255, 255, 255 , 127 );
		imagefill ($im_, 0, 0, $color_t);
		imagecopy($im_, $im, 0, 0, 0, 0, $x_i, $y_i);
		
		for ($x = 0; $x < $x_i; $x++) {
			for ($y=0; $y < $y_i; $y++) {
				$rgb = ImageColorAt($im_, $x, $y);
				$a = ($rgb >> 24) & 0xFF;
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;

				$c = round(($val_r * $r / 1000) + ($val_g * $g / 1000) + ($val_b * $b / 1000));
				if ($c < 0) $c = 0;
				if ($c > 254) $c = 254;
				
				
				$color = ImageColorAllocateAlpha( $im_, $c, $c, $c , $a );
				imagesetpixel ($im_, $x, $y, $color);			
			}
		}
		image_gd_output($im_,$image);
		imagedestroy($im_);
		imagedestroy($im);
	}

	return image_ecrire_tag($image,array('src'=>$dest));
}

// http://doc.spip.org/@image_flou
function image_flou($im,$niveau=3)
{
	// Il s'agit d'une modification du script blur qu'on trouve un peu partout:
	// + la transparence est geree correctement
	// + les dimensions de l'image sont augmentees pour flouter les bords
	$coeffs = array (
				array ( 1),
				array ( 1, 1), 
				array ( 1, 2, 1),
				array ( 1, 3, 3, 1),
				array ( 1, 4, 6, 4, 1),
				array ( 1, 5, 10, 10, 5, 1),
				array ( 1, 6, 15, 20, 15, 6, 1),
				array ( 1, 7, 21, 35, 35, 21, 7, 1),
				array ( 1, 8, 28, 56, 70, 56, 28, 8, 1),
				array ( 1, 9, 36, 84, 126, 126, 84, 36, 9, 1),
				array ( 1, 10, 45, 120, 210, 252, 210, 120, 45, 10, 1),
				array ( 1, 11, 55, 165, 330, 462, 462, 330, 165, 55, 11, 1)
				);
	
	$fonction = array('image_flou', func_get_args());
	$image = image_valeurs_trans($im, "flou-$niveau", false,$fonction);
	if (!$image) return("");
	
	$x_i = $image["largeur"];
	$y_i = $image["hauteur"];
	$sum = pow (2, $niveau);

	$im = $image["fichier"];
	$dest = $image["fichier_dest"];
	
	$creer = $image["creer"];
	
	// Methode precise
	// resultat plus beau, mais tres lourd
	// Et: indispensable pour preserver transparence!

	if ($creer) {
		// Creation de l'image en deux temps
		// de facon a conserver les GIF transparents
		$im = $image["fonction_imagecreatefrom"]($im);
		imagepalettetotruecolor($im);
		$temp1 = imagecreatetruecolor($x_i+$niveau, $y_i);
		$temp2 = imagecreatetruecolor($x_i+$niveau, $y_i+$niveau);
		
		@imagealphablending($temp1, false);
		@imagesavealpha($temp1,true);
		@imagealphablending($temp2, false);
		@imagesavealpha($temp2,true);

		
		for ($i = 0; $i < $x_i+$niveau; $i++) {
			for ($j=0; $j < $y_i; $j++) {
				$suma=0;
				$sumr=0;
				$sumg=0;
				$sumb=0;
				$sum = 0;
				$sum_ = 0;
				for ( $k=0 ; $k <= $niveau ; ++$k ) {
					$color = imagecolorat($im, $i_ = ($i-$niveau)+$k , $j);

					$a = ($color >> 24) & 0xFF;
					$r = ($color >> 16) & 0xFF;
					$g = ($color >> 8) & 0xFF;
					$b = ($color) & 0xFF;
					
					if ($i_ < 0 OR $i_ >= $x_i) $a = 127;
					
					$coef = $coeffs[$niveau][$k];
					$suma += $a*$coef;
					$ac = ((127-$a) / 127);
					
					$ac = $ac*$ac;
					
					$sumr += $r * $coef * $ac;
					$sumg += $g * $coef * $ac;
					$sumb += $b * $coef * $ac;
					$sum += $coef * $ac;
					$sum_ += $coef;
				}
				if ($sum > 0) $color = ImageColorAllocateAlpha ($temp1, $sumr/$sum, $sumg/$sum, $sumb/$sum, $suma/$sum_);
				else $color = ImageColorAllocateAlpha ($temp1, 255, 255, 255, 127);
				imagesetpixel($temp1,$i,$j,$color);
			}
		}
		imagedestroy($im);
		for ($i = 0; $i < $x_i+$niveau; $i++) {
			for ($j=0; $j < $y_i+$niveau; $j++) {
				$suma=0;
				$sumr=0;
				$sumg=0;
				$sumb=0;
				$sum = 0;
				$sum_ = 0;
				for ( $k=0 ; $k <= $niveau ; ++$k ) {
					$color = imagecolorat($temp1, $i, $j_ = $j-$niveau+$k);
					$a = ($color >> 24) & 0xFF;
					$r = ($color >> 16) & 0xFF;
					$g = ($color >> 8) & 0xFF;
					$b = ($color) & 0xFF;
					if ($j_ < 0 OR $j_ >= $y_i) $a = 127;
					
					$suma += $a*$coeffs[$niveau][$k];
					$ac = ((127-$a) / 127);
										
					$sumr += $r * $coeffs[$niveau][$k] * $ac;
					$sumg += $g * $coeffs[$niveau][$k] * $ac;
					$sumb += $b * $coeffs[$niveau][$k] * $ac;
					$sum += $coeffs[$niveau][$k] * $ac;
					$sum_ += $coeffs[$niveau][$k];
					
				}
				if ($sum > 0) $color = ImageColorAllocateAlpha ($temp2, $sumr/$sum, $sumg/$sum, $sumb/$sum, $suma/$sum_);
				else $color = ImageColorAllocateAlpha ($temp2, 255, 255, 255, 127);
				imagesetpixel($temp2,$i,$j,$color);
			}
		}
	
		image_gd_output($temp2,$image);
		imagedestroy($temp1);	
		imagedestroy($temp2);	
	}
	
	return image_ecrire_tag($image,array('src'=>$dest,'width'=>($x_i+$niveau),'height'=>($y_i+$niveau)));
}

// http://doc.spip.org/@image_RotateBicubic
function image_RotateBicubic($src_img, $angle, $bicubic=0) {
   
   if (round($angle/90)*90 == $angle) {
		$droit = true;
   		if (round($angle/180)*180 == $angle) $rot = 180;
   		else $rot = 90;
   }
   else $droit = false;
   
  // convert degrees to radians
   $angle = $angle + 180;
   $angle = deg2rad($angle);
  


   $src_x = imagesx($src_img);
   $src_y = imagesy($src_img);
   
  
   $center_x = floor(($src_x-1)/2);
   $center_y = floor(($src_y-1)/2);

   $cosangle = cos($angle);
   $sinangle = sin($angle);

	// calculer dimensions en simplifiant angles droits, ce qui evite "floutage"
	// des rotations a angle droit
	if (!$droit) {
	   $corners=array(array(0,0), array($src_x,0), array($src_x,$src_y), array(0,$src_y));
	
	   foreach($corners as $key=>$value) {
		 $value[0]-=$center_x;        //Translate coords to center for rotation
		 $value[1]-=$center_y;
		 $temp=array();
		 $temp[0]=$value[0]*$cosangle+$value[1]*$sinangle;
		 $temp[1]=$value[1]*$cosangle-$value[0]*$sinangle;
		 $corners[$key]=$temp;    
	   }
	   
	   $min_x=1000000000000000;
	   $max_x=-1000000000000000;
	   $min_y=1000000000000000;
	   $max_y=-1000000000000000;
	   
	   foreach($corners as $key => $value) {
		 if($value[0]<$min_x)
		   $min_x=$value[0];
		 if($value[0]>$max_x)
		   $max_x=$value[0];
	   
		 if($value[1]<$min_y)
		   $min_y=$value[1];
		 if($value[1]>$max_y)
		   $max_y=$value[1];
	   }
	
	   $rotate_width=ceil($max_x-$min_x);
	   $rotate_height=ceil($max_y-$min_y);
   }
   else {
   	if ($rot == 180) {
   		$rotate_height = $src_y;
   		$rotate_width = $src_x;
   	} else {
   		$rotate_height = $src_x;
   		$rotate_width = $src_y;
   	}
   	$bicubic = false;
   }
   
   
   $rotate=imagecreatetruecolor($rotate_width,$rotate_height);
   imagealphablending($rotate, false);
   imagesavealpha($rotate, true);

   $cosangle = cos($angle);
   $sinangle = sin($angle);
   
	// arrondir pour rotations angle droit (car cos et sin dans {-1,0,1})
	if ($droit) {
		$cosangle = round($cosangle);
		$sinangle = round($sinangle);
	}

   $newcenter_x = ($rotate_width-1)/2;
   $newcenter_y = ($rotate_height-1)/2;

   
   for ($y = 0; $y < $rotate_height; $y++) {
     for ($x = 0; $x < $rotate_width; $x++) {
   // rotate...
       $old_x = ((($newcenter_x-$x) * $cosangle + ($newcenter_y-$y) * $sinangle))
         + $center_x;
       $old_y = ((($newcenter_y-$y) * $cosangle - ($newcenter_x-$x) * $sinangle))
         + $center_y;  
         
         $old_x = ceil($old_x);
         $old_y = ceil($old_y);
         
   if ( $old_x >= 0 && $old_x < $src_x
         && $old_y >= 0 && $old_y < $src_y ) {
     if ($bicubic == true) {
       $xo = $old_x;
       $x0 = floor($xo);
       $x1 = ceil($xo);
       $yo = $old_y;
       $y0 = floor($yo);
       $y1 = ceil($yo);
       
		// on prend chaque point, mais on pondere en fonction de la distance
		$rgb = ImageColorAt($src_img, $x0, $y0); 
		$a1 = ($rgb >> 24) & 0xFF;
		$r1 = ($rgb >> 16) & 0xFF;
		$g1 = ($rgb >> 8) & 0xFF;
		$b1 = $rgb & 0xFF;
		$d1 = image_distance_pixel($xo, $yo, $x0, $y0);

		$rgb = ImageColorAt($src_img, $x1, $y0); 
		$a2 = ($rgb >> 24) & 0xFF;
		$r2 = ($rgb >> 16) & 0xFF;
		$g2 = ($rgb >> 8) & 0xFF;
		$b2 = $rgb & 0xFF;
		$d2 = image_distance_pixel($xo, $yo, $x1, $y0);

		$rgb = ImageColorAt($src_img,$x0, $y1); 
		$a3 = ($rgb >> 24) & 0xFF;
		$r3 = ($rgb >> 16) & 0xFF;
		$g3 = ($rgb >> 8) & 0xFF;
		$b3 = $rgb & 0xFF;
		$d3 = image_distance_pixel($xo, $yo, $x0, $y1);

		$rgb = ImageColorAt($src_img,$x1, $y1);
		$a4 = ($rgb >> 24) & 0xFF;
		$r4 = ($rgb >> 16) & 0xFF;
		$g4 = ($rgb >> 8) & 0xFF;
		$b4 = $rgb & 0xFF;
		$d4 = image_distance_pixel($xo, $yo, $x1, $y1);

		$ac1 = ((127-$a1) / 127);
		$ac2 = ((127-$a2) / 127);
		$ac3 = ((127-$a3) / 127);
		$ac4 = ((127-$a4) / 127);
		
		// limiter impact des couleurs transparentes, 
		// mais attention tout transp: division par 0
		if ($ac1*$d1 + $ac2*$d2 + $ac3+$d3 + $ac4+$d4 > 0) {
			if ($ac1 > 0) $d1 = $d1 * $ac1;
			if ($ac2 > 0) $d2 = $d2 * $ac2;
			if ($ac3 > 0) $d3 = $d3 * $ac3;
			if ($ac4 > 0) $d4 = $d4 * $ac4;
		}
		
		$tot  = $d1 + $d2 + $d3 + $d4;

       $r = round((($d1*$r1)+($d2*$r2)+($d3*$r3)+($d4*$r4))/$tot);
       $g = round((($d1*$g1+($d2*$g2)+$d3*$g3+$d4*$g4))/$tot);
       $b = round((($d1*$b1+($d2*$b2)+$d3*$b3+$d4*$b4))/$tot);
       $a = round((($d1*$a1+($d2*$a2)+$d3*$a3+$d4*$a4))/$tot);
        $color = imagecolorallocatealpha($src_img, $r,$g,$b,$a);
     } else {
       $color = imagecolorat($src_img, round($old_x), round($old_y));
     }
   } else {
         // this line sets the background colour
     $color = imagecolorallocatealpha($src_img, 255, 255, 255, 127);
   }
   @imagesetpixel($rotate, $x, $y, $color);
     }
   }
   return $rotate;
}

// permet de faire tourner une image d'un angle quelconque
// la fonction "crop" n'est pas implementee...
// http://doc.spip.org/@image_rotation
function image_rotation($im, $angle, $crop=false)
{
	$fonction = array('image_rotation', func_get_args());
	$image = image_valeurs_trans($im, "rot-$angle-$crop", "png", $fonction);
	if (!$image) return("");
	
	$im = $image["fichier"];
	$dest = $image["fichier_dest"];
	
	$creer = $image["creer"];
	
	if ($creer) {
		$effectuer_gd = true;

		if (function_exists('imagick_rotate')) {
			$mask = imagick_getcanvas( "#ff0000", $x, $y );
			$handle = imagick_readimage ($im);
			if ($handle && imagick_isopaqueimage( $handle )) {
				imagick_rotate( $handle, $angle);
				imagick_writeimage( $handle, $dest);
				$effectuer_gd = false;
			}
		}
		elseif(is_callable(array('Imagick','rotateImage'))){
			$imagick = new Imagick();
			$imagick->readImage($im);
			$imagick->rotateImage(new ImagickPixel('#ffffff'), $angle);
			$imagick->writeImage($dest);
			$effectuer_gd = false;
		}
		if ($effectuer_gd) {
			// Creation de l'image en deux temps
			// de facon a conserver les GIF transparents
			$im = $image["fonction_imagecreatefrom"]($im);
			imagepalettetotruecolor($im);
			$im = image_RotateBicubic($im, $angle, true);
			image_gd_output($im,$image);
			imagedestroy($im);
		}
	}
	list ($src_y,$src_x) = taille_image($dest);
	return image_ecrire_tag($image,array('src'=>$dest,'width'=>$src_x,'height'=>$src_y));
}

// Permet d'appliquer un filtre php_imagick a une image
// par exemple: [(#LOGO_ARTICLE||image_imagick{imagick_wave,20,60})]
// liste des fonctions: http://www.linux-nantes.org/~fmonnier/doc/imagick/
// http://doc.spip.org/@image_imagick
function image_imagick () {
	$tous = func_get_args();
	$img = $tous[0];
	$fonc = $tous[1];
	$tous[0]="";
	$tous_var = join($tous, "-");

	$fonction = array('image_imagick', func_get_args());
	$image = image_valeurs_trans($img, "$tous_var", "png",$fonction);
	if (!$image) return("");
	
	$im = $image["fichier"];
	$dest = $image["fichier_dest"];
	
	$creer = $image["creer"];
	
	if ($creer) {
		if (function_exists($fonc)) {

			$handle = imagick_readimage ($im);
			$arr[0] = $handle;
			for ($i=2; $i < count($tous); $i++) $arr[] = $tous[$i];
			call_user_func_array($fonc, $arr);
			// Creer image dans fichier temporaire, puis renommer vers "bon" fichier
			// de facon a eviter time_out pendant creation de l'image definitive
			$tmp = preg_replace(",[.]png$,i", "-tmp.png", $dest);
			imagick_writeimage( $handle, $tmp);
			rename($tmp, $dest);
			ecrire_fichier($dest.".src",serialize($image));
		} 
	}
	list ($src_y,$src_x) = taille_image($dest);
	return image_ecrire_tag($image,array('src'=>$dest,'width'=>$src_x,'height'=>$src_y));

}


// $src_img - a GD image resource
// $angle - degrees to rotate clockwise, in degrees
// returns a GD image resource
// script de php.net lourdement corrig'e
// (le bicubic deconnait completement,
// et j'ai ajoute la ponderation par la distance au pixel)

// http://doc.spip.org/@image_distance_pixel
function image_distance_pixel($xo, $yo, $x0, $y0) {
	$vx = $xo - $x0;
	$vy = $yo - $y0;
	$d = 1 - (sqrt(($vx)*($vx) + ($vy)*($vy)) / sqrt(2));
	return $d;
}

// http://doc.spip.org/@image_decal_couleur
function image_decal_couleur($coul, $gamma) {
	$coul = $coul + $gamma;
	
	if ($coul > 255) $coul = 255;
	if ($coul < 0) $coul = 0;
	return $coul;
}
// Permet de rendre une image
// plus claire (gamma > 0)
// ou plus foncee (gamma < 0)
// http://doc.spip.org/@image_gamma
function image_gamma($im, $gamma = 0)
{
	$fonction = array('image_gamma', func_get_args());
	$image = image_valeurs_trans($im, "gamma-$gamma",false,$fonction);
	if (!$image) return("");
	
	$x_i = $image["largeur"];
	$y_i = $image["hauteur"];
	
	$im = $image["fichier"];
	$dest = $image["fichier_dest"];
	
	$creer = $image["creer"];
	
	if ($creer) {
		// Creation de l'image en deux temps
		// de facon a conserver les GIF transparents
		$im = $image["fonction_imagecreatefrom"]($im);
		imagepalettetotruecolor($im);
		$im_ = imagecreatetruecolor($x_i, $y_i);
		@imagealphablending($im_, false);
		@imagesavealpha($im_,true);
		$color_t = ImageColorAllocateAlpha( $im_, 255, 255, 255 , 127 );
		imagefill ($im_, 0, 0, $color_t);
		imagecopy($im_, $im, 0, 0, 0, 0, $x_i, $y_i);
	
		for ($x = 0; $x < $x_i; $x++) {
			for ($y=0; $y < $y_i; $y++) {
				$rgb = ImageColorAt($im_, $x, $y);
				$a = ($rgb >> 24) & 0xFF;
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;

				$r = image_decal_couleur($r, $gamma);
				$g = image_decal_couleur($g, $gamma);
				$b = image_decal_couleur($b, $gamma);

				$color = ImageColorAllocateAlpha( $im_, $r, $g, $b , $a );
				imagesetpixel ($im_, $x, $y, $color);			
			}
		}
		image_gd_output($im_,$image);
	}
	return image_ecrire_tag($image,array('src'=>$dest));
}

// Passe l'image en "sepia"
// On peut fixer les valeurs RVB 
// de la couleur "complementaire" pour forcer une dominante

// http://doc.spip.org/@image_decal_couleur_127
function image_decal_couleur_127 ($coul, $val) {
	if ($coul < 127) $y = round((($coul - 127) / 127) * $val) + $val;
	else if ($coul >= 127) $y = round((($coul - 127) / 128) * (255-$val)) + $val;
	else $y= $coul;
	
	if ($y < 0) $y = 0;
	if ($y > 255) $y = 255;
	return $y;
}
//function image_sepia($im, $dr = 137, $dv = 111, $db = 94)
// http://doc.spip.org/@image_sepia
function image_sepia($im, $rgb = "896f5e")
{
	
	if (!function_exists("imagecreatetruecolor")) return $im;
	
	$couleurs = couleur_hex_to_dec($rgb);
	$dr= $couleurs["red"];
	$dv= $couleurs["green"];
	$db= $couleurs["blue"];
		
	$fonction = array('image_sepia', func_get_args());
	$image = image_valeurs_trans($im, "sepia-$dr-$dv-$db",false,$fonction);
	if (!$image) return("");
	
	$x_i = $image["largeur"];
	$y_i = $image["hauteur"];
	
	$im = $image["fichier"];
	$dest = $image["fichier_dest"];
	
	$creer = $image["creer"];
	
	if ($creer) {
		// Creation de l'image en deux temps
		// de facon a conserver les GIF transparents
		$im = $image["fonction_imagecreatefrom"]($im);
		imagepalettetotruecolor($im);
		$im_ = imagecreatetruecolor($x_i, $y_i);
		@imagealphablending($im_, false);
		@imagesavealpha($im_,true);
		$color_t = ImageColorAllocateAlpha( $im_, 255, 255, 255 , 127 );
		imagefill ($im_, 0, 0, $color_t);
		imagecopy($im_, $im, 0, 0, 0, 0, $x_i, $y_i);
	
		for ($x = 0; $x < $x_i; $x++) {
			for ($y=0; $y < $y_i; $y++) {
				$rgb = ImageColorAt($im_, $x, $y);
				$a = ($rgb >> 24) & 0xFF;
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;

				$r = round(.299 * $r + .587 * $g + .114 * $b);
				$g = $r;
				$b = $r;


				$r = image_decal_couleur_127($r, $dr);
				$g = image_decal_couleur_127($g, $dv);
				$b = image_decal_couleur_127($b, $db);

				$color = ImageColorAllocateAlpha( $im_, $r, $g, $b , $a );
				imagesetpixel ($im_, $x, $y, $color);			
			}
		}
		image_gd_output($im_,$image);
		imagedestroy($im_);
		imagedestroy($im);
	}
	
	return image_ecrire_tag($image,array('src'=>$dest));
}


// Renforcer la nettete d'une image
// http://doc.spip.org/@image_renforcement
function image_renforcement($im, $k=0.5)
{
	$fonction = array('image_flou', func_get_args());
	$image = image_valeurs_trans($im, "renforcement-$k",false,$fonction);
	if (!$image) return("");
	
	$x_i = $image["largeur"];
	$y_i = $image["hauteur"];
	$im = $image["fichier"];
	$dest = $image["fichier_dest"];
	$creer = $image["creer"];
	
	if ($creer) {
		$im = $image["fonction_imagecreatefrom"]($im);
		imagepalettetotruecolor($im);
		$im_ = imagecreatetruecolor($x_i, $y_i);
		@imagealphablending($im_, false);
		@imagesavealpha($im_,true);
		$color_t = ImageColorAllocateAlpha( $im_, 255, 255, 255 , 127 );
		imagefill ($im_, 0, 0, $color_t);

		for ($x = 0; $x < $x_i; $x++) {
			for ($y=0; $y < $y_i; $y++) {		

                $rgb[1][0]=imagecolorat($im,$x,$y-1);
                $rgb[0][1]=imagecolorat($im,$x-1,$y);
                $rgb[1][1]=imagecolorat($im,$x,$y);
                $rgb[2][1]=imagecolorat($im,$x+1,$y);
                $rgb[1][2]=imagecolorat($im,$x,$y+1);
                
                
                if ($x-1 < 0) $rgb[0][1] = $rgb[1][1];
                if ($y-1 < 0) $rgb[1][0] = $rgb[1][1];
                if ($x+1 == $x_i) $rgb[2][1] = $rgb[1][1];
                if ($y+1 == $y_i) $rgb[1][2] = $rgb[1][1];

                $a = ($rgb[0][0] >> 24) & 0xFF;
                $r = -$k *(($rgb[1][0] >> 16) & 0xFF) +
                         -$k *(($rgb[0][1] >> 16) & 0xFF) +
                        (1+4*$k) *(($rgb[1][1] >> 16) & 0xFF) +
                         -$k *(($rgb[2][1] >> 16) & 0xFF) +
                         -$k *(($rgb[1][2] >> 16) & 0xFF) ;

                $g = -$k *(($rgb[1][0] >> 8) & 0xFF) +
                         -$k *(($rgb[0][1] >> 8) & 0xFF) +
                         (1+4*$k) *(($rgb[1][1] >> 8) & 0xFF) +
                         -$k *(($rgb[2][1] >> 8) & 0xFF) +
                         -$k *(($rgb[1][2] >> 8) & 0xFF) ;

                $b = -$k *($rgb[1][0] & 0xFF) +
                         -$k *($rgb[0][1] & 0xFF) +
                        (1+4*$k) *($rgb[1][1] & 0xFF) +
                         -$k *($rgb[2][1] & 0xFF) +
                         -$k *($rgb[1][2] & 0xFF) ;

                $r=min(255,max(0,$r));
                $g=min(255,max(0,$g));
                $b=min(255,max(0,$b));


		$color = ImageColorAllocateAlpha( $im_, $r, $g, $b , $a );
		imagesetpixel ($im_, $x, $y, $color);			
			}
		}		
		image_gd_output($im_,$image);
	}

	return image_ecrire_tag($image,array('src'=>$dest));
}


// 1/ Aplatir une image semi-transparente (supprimer couche alpha)
// en remplissant la transparence avec couleur choisir $coul.
// 2/ Forcer le format de sauvegarde (jpg, png, gif)
// pour le format jpg, $qualite correspond au niveau de compression (defaut 85)
// pour le format gif, $qualite correspond au nombre de couleurs dans la palette (defaut 128)
// pour le format png, $qualite correspond au nombre de couleur dans la palette ou si 0 a une image truecolor (defaut truecolor)
// attention, seul 128 est supporte en l'etat (production d'images avec palette reduite pas satisfaisante)
// http://doc.spip.org/@image_aplatir
// 3/ $transparence a "true" permet de conserver la transparence (utile pour conversion GIF)
// http://doc.spip.org/@image_aplatir
function image_aplatir($im, $format='jpg', $coul='000000', $qualite=NULL, $transparence=false)
{
	if ($qualite===NULL){
		if ($format=='jpg') $qualite=_IMG_GD_QUALITE;
		elseif ($format=='png') $qualite=0;
		else $qualite=128;
	}
	$fonction = array('image_aplatir', func_get_args());
	$image = image_valeurs_trans($im, "aplatir-$format-$coul-$qualite-$transparence", $format, $fonction);

	if (!$image) return("");

	include_spip('inc/filtres');
	$couleurs = couleur_hex_to_dec($coul);
	$dr= $couleurs["red"];
	$dv= $couleurs["green"];
	$db= $couleurs["blue"];

	$x_i = $image["largeur"];
	$y_i = $image["hauteur"];
	
	$im = $image["fichier"];
	$dest = $image["fichier_dest"];
	
	$creer = $image["creer"];

	if ($creer) {
		$im = @$image["fonction_imagecreatefrom"]($im);
		imagepalettetotruecolor($im);
		$im_ = imagecreatetruecolor($x_i, $y_i);
		if ($image["format_source"] == "gif" AND function_exists('ImageCopyResampled')) { 
			// Si un GIF est transparent, 
			// fabriquer un PNG transparent  
			// Conserver la transparence 
			@imagealphablending($im_, false); 
			@imagesavealpha($im_,true); 
			if (function_exists("imageAntiAlias")) imageAntiAlias($im_,true); 
			@ImageCopyResampled($im_, $im, 0, 0, 0, 0, $x_i, $y_i, $x_i, $y_i);
			imagedestroy($im);
			$im = $im_;
		}
		
		// allouer la couleur de fond
		if ($transparence) {
			@imagealphablending($im_, false); 
			@imagesavealpha($im_,true); 
			$color_t = imagecolorallocatealpha( $im_, $dr, $dv, $db, 127);
		}
		else $color_t = ImageColorAllocate( $im_, $dr, $dv, $db);
		
		imagefill ($im_, 0, 0, $color_t);

		//??
		//$dist = abs($trait);
		
		$transp_x = false;
		
		for ($x = 0; $x < $x_i; $x++) {
			for ($y=0; $y < $y_i; $y++) {
			
				$rgb = ImageColorAt($im, $x, $y);
				$a = ($rgb >> 24) & 0xFF;
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;

				$a = (127-$a) / 127;
				
				if ($a == 1) { // Limiter calculs
					$r = $r;
					$g = $g;
					$b = $b;
				}
				else if ($a == 0) { // Limiter calculs
					$r = $dr;
					$g = $dv;
					$b = $db;
					
					$transp_x = $x; // Memoriser un point transparent
					$transp_y = $y;
					
				} else {
					$r = round($a * $r + $dr * (1-$a));
					$g = round($a * $g + $dv * (1-$a));
					$b = round($a * $b + $db * (1-$a));
				}
				$a = (1-$a) *127;
				$color = ImageColorAllocateAlpha( $im_, $r, $g, $b, $a);
				imagesetpixel ($im_, $x, $y, $color);	
			}
		}
		// passer en palette si besoin
		if ($format=='gif' OR ($format=='png' AND $qualite!==0)){
			// creer l'image finale a palette (on recycle l'image initiale)			


			@imagetruecolortopalette($im,true,$qualite);


			//$im = imagecreate($x_i, $y_i);
			// copier l'image true color vers la palette
			imagecopy($im, $im_, 0, 0, 0, 0, $x_i, $y_i);
			// matcher les couleurs au mieux par rapport a l'image initiale
			// si la fonction est disponible (php>=4.3)
			if (function_exists('imagecolormatch'))
				@imagecolormatch($im_, $im);
				
			if ($format=='gif' && $transparence && $transp_x) {	
				$color_t = ImagecolorAt( $im, $transp_x, $transp_y);
				if ($format == "gif" && $transparence) @imagecolortransparent($im, $color_t);
			}
				
				
			// produire le resultat
			image_gd_output($im, $image, $qualite);
		}
		else
			image_gd_output($im_, $image, $qualite);
		imagedestroy($im_);
		imagedestroy($im);
	}
	return image_ecrire_tag($image,array('src'=>$dest));
}


// Enregistrer une image dans un format donne
// (conserve la transparence gif, png, ico)
// utilise [->@image_aplatir]
// http://doc.spip.org/@image_format
function image_format($img, $format='png') {
	$qualite = null;
	if ($format=='png8') {$format='png';$qualite=128;}
	return image_aplatir($img, $format, 'cccccc', $qualite, true);
}



// A partir d'une image,
// recupere une couleur
// renvoit sous la forme hexadecimale ("F26C4E" par exemple).
// Par defaut, la couleur choisie se trouve un peu au-dessus du centre de l'image.
// On peut forcer un point en fixant $x et $y, entre 0 et 20.
// http://doc.spip.org/@image_couleur_extraire
function image_couleur_extraire($img, $x=10, $y=6) {

	$cache = image_valeurs_trans($img, "coul-$x-$y", "php");
	if (!$cache) return "F26C4E";
	
	$fichier = $cache["fichier"];
	$dest = $cache["fichier_dest"];
	$terminaison = $cache["format_source"];
	
	$creer = $cache["creer"];
	if ($creer) {
		if (!$GLOBALS["couleur_extraite"]["$fichier-$x-$y"]) {	
			if (file_exists($fichier)) {
				list($width, $height) = getimagesize($fichier);
			
			
				$newwidth = 20;
				$newheight = 20;
			
				$thumb = imagecreate($newwidth, $newheight);

				if (preg_match(",\.jpe?g$,i", $fichier)) $source = imagecreatefromjpeg($fichier);
				if (preg_match(",\.gif$,i", $fichier)) $source = imagecreatefromgif($fichier);
				if (preg_match(",\.png$,i", $fichier)) $source = imagecreatefrompng($fichier);
				imagepalettetotruecolor($source);

				imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
			
				// get a color
				$color_index = imagecolorat($thumb, $x, $y);
				
				// make it human readable
				$color_tran = imagecolorsforindex($thumb, $color_index);
				
				$couleur = couleur_dec_to_hex($color_tran["red"], $color_tran["green"], $color_tran["blue"]);
			}
			else {
				$couleur = "F26C4E";
			}
			$GLOBALS["couleur_extraite"]["$fichier-$x-$y"] = $couleur;

			$handle = fopen($dest, 'w');
			fwrite($handle, "<"."?php \$GLOBALS[\"couleur_extraite\"][\"".$fichier."-".$x."-".$y."\"] = \"".$couleur."\"; ?".">");
			fclose($handle);
		
		}
		// Mettre en cache le resultat
		
	} else {
		include("$dest");
	}
	
	
	return $GLOBALS["couleur_extraite"]["$fichier-$x-$y"];
}

// http://doc.spip.org/@couleur_html_to_hex
function couleur_html_to_hex($couleur){
	$couleurs_html=array(
		'aqua'=>'00FFFF','black'=>'000000','blue'=>'0000FF','fuchsia'=>'FF00FF','gray'=>'808080','green'=>'008000','lime'=>'00FF00','maroon'=>'800000',
		'navy'=>'000080','olive'=>'808000','purple'=>'800080','red'=>'FF0000','silver'=>'C0C0C0','teal'=>'008080','white'=>'FFFFFF','yellow'=>'FFFF00');
	if (isset($couleurs_html[$lc=strtolower($couleur)]))
		return $couleurs_html[$lc];
	return $couleur;
}

// http://doc.spip.org/@couleur_dec_to_hex
function couleur_dec_to_hex($red, $green, $blue) {
	$red = dechex($red);
	$green = dechex($green);
	$blue = dechex($blue);
	
	if (strlen($red) == 1) $red = "0".$red;
	if (strlen($green) == 1) $green = "0".$green;
	if (strlen($blue) == 1) $blue = "0".$blue;
	
	return "$red$green$blue";
}

// http://doc.spip.org/@couleur_hex_to_dec
function couleur_hex_to_dec($couleur) {
	$couleur = couleur_html_to_hex($couleur);
	$couleur = preg_replace(",^#,","",$couleur);
	$retour["red"] = hexdec(substr($couleur, 0, 2));
	$retour["green"] = hexdec(substr($couleur, 2, 2));
	$retour["blue"] = hexdec(substr($couleur, 4, 2));
	
	return $retour;
}

// http://doc.spip.org/@couleur_multiple_de_trois
function couleur_multiple_de_trois($val) {
	$val = hexdec($val);
	$val = round($val / 3) * 3;
	$val = dechex($val);
	return $val;
	
}

// http://doc.spip.org/@couleur_web
function couleur_web($couleur) {
	$r = couleur_multiple_de_trois(substr($couleur, 0, 1));
	$v = couleur_multiple_de_trois(substr($couleur, 2, 1));
	$b = couleur_multiple_de_trois(substr($couleur, 4, 1));
	
	return "$r$r$v$v$b$b";
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

	$couleurs = couleur_hex_to_dec($couleur);
	$red = $couleurs["red"];
	$green = $couleurs["green"];
	$blue = $couleurs["blue"];
	
	
	/*	
	$moyenne = round(($red+$green+$blue)/3);

	if ($moyenne > $limite) $couleur_texte = "ffffff";
	else $couleur_texte = "000000";
	*/

	$hsl = couleur_rgb2hsl ($red, $green, $blue);

	if ($hsl["l"] > $limite) $couleur_texte = "ffffff";
	else $couleur_texte = "000000";

	return $couleur_texte;
}

// http://doc.spip.org/@couleur_inverser
function couleur_inverser ($couleur) {
	$couleurs = couleur_hex_to_dec($couleur);
	$red = 255 - $couleurs["red"];
	$green = 255 - $couleurs["green"];
	$blue = 255 - $couleurs["blue"];

	$couleur = couleur_dec_to_hex($red, $green, $blue);
	
	return $couleur;
}

// http://doc.spip.org/@couleur_eclaircir
function couleur_eclaircir ($couleur) {
	$couleurs = couleur_hex_to_dec($couleur);

	$red = $couleurs["red"] + round((255 - $couleurs["red"])/2);
	$green = $couleurs["green"] + round((255 - $couleurs["green"])/2);
	$blue = $couleurs["blue"] + round((255 - $couleurs["blue"])/2);

	$couleur = couleur_dec_to_hex($red, $green, $blue);
	
	return $couleur;

}
// http://doc.spip.org/@couleur_foncer
function couleur_foncer ($couleur) {
	$couleurs = couleur_hex_to_dec($couleur);

	$red = $couleurs["red"] - round(($couleurs["red"])/2);
	$green = $couleurs["green"] - round(($couleurs["green"])/2);
	$blue = $couleurs["blue"] - round(($couleurs["blue"])/2);

	$couleur = couleur_dec_to_hex($red, $green, $blue);
	
	return $couleur;
}
// http://doc.spip.org/@couleur_foncer_si_claire
function couleur_foncer_si_claire ($couleur) {
	// ne foncer que les couleurs claires
	// utile pour ecrire sur fond blanc, 
	// mais sans changer quand la couleur est deja foncee
	$couleurs = couleur_hex_to_dec($couleur);
	$red = $couleurs["red"];
	$green = $couleurs["green"];
	$blue = $couleurs["blue"];
	
	$moyenne = round(($red+$green+$blue)/3);
	
	if ($moyenne > 122) return couleur_foncer($couleur);
	else return $couleur;
}
// http://doc.spip.org/@couleur_eclaircir_si_foncee
function couleur_eclaircir_si_foncee ($couleur) {
	$couleurs = couleur_hex_to_dec($couleur);
	$red = $couleurs["red"];
	$green = $couleurs["green"];
	$blue = $couleurs["blue"];
	
	$moyenne = round(($red+$green+$blue)/3);
	
	if ($moyenne < 123) return couleur_eclaircir($couleur);
	else return $couleur;
}


// http://doc.spip.org/@couleur_saturation
function couleur_saturation($couleur, $val) {
	if ($couleur == "ffffff") $couleur = "eeeeee";

	$couleurs = couleur_hex_to_dec($couleur);
	$r= 255 - $couleurs["red"];
	$g= 255 - $couleurs["green"];
	$b= 255 - $couleurs["blue"];

	$max = max($r,$g,$b);

	$r = 255 - $r / $max * 255 * $val;
	$g = 255 - $g / $max * 255 * $val;
	$b = 255 - $b / $max * 255 * $val;
	
	$couleur = couleur_dec_to_hex($r, $g, $b);
	
	return $couleur;
		
}



// http://doc.spip.org/@couleur_rgb2hsv
function couleur_rgb2hsv ($R,$G,$B) {
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


// http://doc.spip.org/@couleur_hsv2rgb
function couleur_hsv2rgb ($H,$S,$V) {
	
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


// http://doc.spip.org/@couleur_rgb2hsl
function couleur_rgb2hsl ($R,$G,$B) {
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


// http://doc.spip.org/@hue_2_rgb
function hue_2_rgb( $v1, $v2, $vH ) {
   if ( $vH < 0 ) $vH += 1;
   if ( $vH > 1 ) $vH -= 1;
   if ( ( 6 * $vH ) < 1 ) return ( $v1 + ( $v2 - $v1 ) * 6 * $vH );
   if ( ( 2 * $vH ) < 1 ) return ( $v2 );
   if ( ( 3 * $vH ) < 2 ) return ( $v1 + ( $v2 - $v1 ) * ( ( 2 / 3 ) - $vH ) * 6 );
   return ( $v1 );
}

// http://doc.spip.org/@couleur_hsl2rgb
function couleur_hsl2rgb ($H,$S,$L) {
	
	if ( $S == 0 )                       //HSV values = 0 Ã· 1
	{
	   $R = $V * 255;
	   $G = $V * 255;
	   $B = $V * 255;
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




// Image typographique

// Fonctions pour l'arabe


// http://doc.spip.org/@rtl_mb_ord
function rtl_mb_ord($char){

	if (($c = ord($char)) < 216) return $c;
	else return 256 * rtl_mb_ord(substr($char, 0, -1)) + ord(substr($char, -1));

/*	return (strlen($char) < 2) ?
		ord($char) : 256 * mb_ord(substr($char, 0, -1))
			+ ord(substr($char, -1));
			
*/			
  }



// http://doc.spip.org/@rtl_reverse
function rtl_reverse($mot, $rtl_global) {

	$ponctuations = array("«","»", "“", "”", ",", ".", " ", ":", ";", "(", ")", "،", "؟", "?", "!", " ");
	foreach($ponctuations as $ponct) {
		$ponctuation[$ponct] = true;
	}

		

	for ($i = 0; $i < spip_strlen($mot); $i++) {
		$lettre = spip_substr($mot, $i, 1);
		
		
		$code = rtl_mb_ord($lettre);
//		echo "<li>$lettre - $code";

		
//			echo "<li>$code";
		if (($code >= 54928 && $code <= 56767) ||  ($code >= 15707294 && $code <= 15711164)) {
			$rtl = true;
		}
		else $rtl = false;
		
		if ($lettre == "٠" || $lettre == "١" || $lettre == "٢" || $lettre == "٣" || $lettre == "٤" || $lettre == "٥"
				 || $lettre == "٦" || $lettre == "٧" || $lettre == "٨" || $lettre == "٩") $rtl = false;
		
		if ($ponctuation[$lettre]) {
			$rtl = $rtl_global;
			
			if ($rtl) {
				switch ($lettre) {
					case "(": $lettre = ")"; break;
					case ")": $lettre = "("; break;
					case "«": $lettre = "»"; break;
					case "»": $lettre = "«"; break;
					case "“": $lettre = "”"; break;
					case "”": $lettre = "“"; break;
				}
			}
		}
		
		
		if ($rtl) $res = $lettre.$res;
		else $res = $res.$lettre;
		
	}
	return $res;
}



// http://doc.spip.org/@rtl_visuel
function rtl_visuel($texte, $rtl_global) {
	// hebreu + arabe: 54928 => 56767
	// hebreu + presentation A: 15707294 => 15710140
	// arabe presentation: 15708336 => 15711164
	
//	echo hexdec("efb7bc");

	// premiere passe pour determiner s'il y a du rtl
	// de facon a placer ponctuation et mettre les mots dans l'ordre
	



	$arabic_letters = array(
		array("ي", // lettre 0
			"ﻱ",  // isolee 1
			"ﻳ", // debut 2
			"ﻴ", // milieu 3
			"ﻲ"),
		array("ب", // lettre 0
			"ﺏ",  // isolee 1
			"ﺑ", // debut 2
			"ﺒ", // milieu 3
			"ﺐ"),
		array("ا", // lettre 0
			"ا",  // isolee 1
			"ﺍ", // debut 2
			"ﺍ", // milieu 3
			"ﺎ"),
		array("إ", // lettre 0
			"إ",  // isolee 1
			"إ", // debut 2
			"ﺈ", // milieu 3
			"ﺈ"),
		array("ل", // lettre 0
			"ﻝ",  // isolee 1
			"ﻟ", // debut 2
			"ﻠ", // milieu 3
			"ﻞ"),
		array("خ", // lettre 0
			"ﺥ",  // isolee 1
			"ﺧ", // debut 2
			"ﺨ", // milieu 3
			"ﺦ"),
		array("ج", // lettre 0
			"ﺝ",  // isolee 1
			"ﺟ", // debut 2
			"ﺠ", // milieu 3
			"ﺞ"),
		array("س", // lettre 0
			"ﺱ",  // isolee 1
			"ﺳ", // debut 2
			"ﺴ", // milieu 3
			"ﺲ"),
		array("ن", // lettre 0
			"ﻥ",  // isolee 1
			"ﻧ", // debut 2
			"ﻨ", // milieu 3
			"ﻦ"),
		array("ش", // lettre 0
			"ﺵ",  // isolee 1
			"ﺷ", // debut 2
			"ﺸ", // milieu 3
			"ﺶ"),
		array("ق", // lettre 0
			"ﻕ",  // isolee 1
			"ﻗ", // debut 2
			"ﻘ", // milieu 3
			"ﻖ"),
		array("ح", // lettre 0
			"ﺡ",  // isolee 1
			"ﺣ", // debut 2
			"ﺤ", // milieu 3
			"ﺢ"),
		array("م", // lettre 0
			"ﻡ",  // isolee 1
			"ﻣ", // debut 2
			"ﻤ", // milieu 3
			"ﻢ"),
		array("ر", // lettre 0
			"ر",  // isolee 1
			"ﺭ", // debut 2
			"ﺮ", // milieu 3
			"ﺮ"),
		array("ع", // lettre 0
			"ع",  // isolee 1
			"ﻋ", // debut 2
			"ﻌ", // milieu 3
			"ﻊ"),
		array("و", // lettre 0
			"و",  // isolee 1
			"ﻭ", // debut 2
			"ﻮ", // milieu 3
			"ﻮ"),
		array("ة", // lettre 0
			"ة",  // isolee 1
			"ة", // debut 2
			"ﺔ", // milieu 3
			"ﺔ"),
		array("ف", // lettre 0
			"ﻑ",  // isolee 1
			"ﻓ", // debut 2
			"ﻔ", // milieu 3
			"ﻒ"),
		array("ﻻ", // lettre 0
			"ﻻ",  // isolee 1
			"ﻻ", // debut 2
			"ﻼ", // milieu 3
			"ﻼ"),
		array("ح", // lettre 0
			"ﺡ",  // isolee 1
			"ﺣ", // debut 2
			"ﺤ", // milieu 3
			"ﺢ"),
		array("ت", // lettre 0
			"ﺕ",  // isolee 1
			"ﺗ", // debut 2
			"ﺘ", // milieu 3
			"ﺖ"),
		array("ض", // lettre 0
			"ﺽ",  // isolee 1
			"ﺿ", // debut 2
			"ﻀ", // milieu 3
			"ﺾ"),
		array("ك", // lettre 0
			"ك",  // isolee 1
			"ﻛ", // debut 2
			"ﻜ", // milieu 3
			"ﻚ"),
		array("ه", // lettre 0
			"ﻩ",  // isolee 1
			"ﻫ", // debut 2
			"ﻬ", // milieu 3
			"ﻪ"),
		array("ي", // lettre 0
			"ي",  // isolee 1
			"ﻳ", // debut 2
			"ﻴ", // milieu 3
			"ﻲ"),
		array("ئ", // lettre 0
			"ﺉ",  // isolee 1
			"ﺋ", // debut 2
			"ﺌ", // milieu 3
			"ﺊ"),
		array("ص", // lettre 0
			"ﺹ",  // isolee 1
			"ﺻ", // debut 2
			"ﺼ", // milieu 3
			"ﺺ"),
		array("ث", // lettre 0
			"ﺙ",  // isolee 1
			"ﺛ", // debut 2
			"ﺜ", // milieu 3
			"ﺚ"),
		array("ﻷ", // lettre 0
			"ﻷ",  // isolee 1
			"ﻷ", // debut 2
			"ﻸ", // milieu 3
			"ﻸ"),
		array("د", // lettre 0
			"ﺩ",  // isolee 1
			"ﺩ", // debut 2
			"ﺪ", // milieu 3
			"ﺪ"),
		array("ذ", // lettre 0
			"ﺫ",  // isolee 1
			"ﺫ", // debut 2
			"ﺬ", // milieu 3
			"ﺬ"),
		array("ط", // lettre 0
			"ﻁ",  // isolee 1
			"ﻃ", // debut 2
			"ﻄ", // milieu 3
			"ﻂ"),
		array("آ", // lettre 0
			"آ",  // isolee 1
			"آ", // debut 2
			"ﺂ", // milieu 3
			"ﺂ"),
		array("أ", // lettre 0
			"أ",  // isolee 1
			"أ", // debut 2
			"ﺄ", // milieu 3
			"ﺄ"),
		array("ؤ", // lettre 0
			"ؤ",  // isolee 1
			"ؤ", // debut 2
			"ﺆ", // milieu 3
			"ﺆ"),
		array("ز", // lettre 0
			"ز",  // isolee 1
			"ز", // debut 2
			"ﺰ", // milieu 3
			"ﺰ"),
		array("ظ", // lettre 0
			"ظ",  // isolee 1
			"ﻇ", // debut 2
			"ﻈ", // milieu 3
			"ﻆ"),
		array("غ", // lettre 0
			"غ",  // isolee 1
			"ﻏ", // debut 2
			"ﻐ", // milieu 3
			"ﻎ"),
		array("ى", // lettre 0
			"ى",  // isolee 1
			"ﯨ", // debut 2
			"ﯩ", // milieu 3
			"ﻰ"),
		array("پ", // lettre 0
			"پ",  // isolee 1
			"ﭘ", // debut 2
			"ﭙ", // milieu 3
			"ﭗ"),
		array("چ", // lettre 0
			"چ",  // isolee 1
			"ﭼ", // debut 2
			"ﭽ", // milieu 3
			"ﭻ")
	);
	
	if(init_mb_string() AND mb_regex_encoding() !== "UTF-8") echo "Attention: dans php.ini, il faut indiquer:<br /><strong>mbstring.internal_encoding = UTF-8</strong>";
	
	
	$texte = explode(" ", $texte);
	
	foreach ($texte as $mot) {
		$res = "";



		// Inserer des indicateurs de debut/fin
		$mot = "^".$mot."^";

		$mot = preg_replace(",&nbsp;,u", " ", $mot);
		$mot = preg_replace(",&#171;,u", "«", $mot);
		$mot = preg_replace(",&#187;,u", "»", $mot);




		// ponctuations
		$ponctuations = array("«","»", "“", "”", ",", ".", " ", ":", ";", "(", ")", "،", "؟", "?", "!"," ");
		foreach($ponctuations as $ponct) {
			$mot = str_replace("$ponct", "^$ponct^", $mot);
		}

		// lettres forcant coupure
		$mot = preg_replace(",ا,u", "ا^", $mot);
		$mot = preg_replace(",د,u", "د^", $mot);
		$mot = preg_replace(",أ,u", "أ^", $mot);
		$mot = preg_replace(",إ,u", "إ^", $mot);
		$mot = preg_replace(",أ,u", "أ^", $mot);
		$mot = preg_replace(",ر,u", "ر^", $mot);
		$mot = preg_replace(",ذ,u", "ذ^", $mot);
		$mot = preg_replace(",ز,u", "ز^", $mot);
		$mot = preg_replace(",و,u", "و^", $mot);
		$mot = preg_replace(",و,u", "و^", $mot);
		$mot = preg_replace(",ؤ,u", "ؤ^", $mot);
		$mot = preg_replace(",ة,u", "ة^", $mot);
		//		$mot = preg_replace(",ل,u", "^ل", $mot);
		//		$mot = preg_replace(",,", "^", $mot);


		$mot = preg_replace(",٠,u", "^٠^", $mot);
		$mot = preg_replace(",١,u", "^١^", $mot);
		$mot = preg_replace(",٢,u", "^٢^", $mot);
		$mot = preg_replace(",٣,u", "^٣^", $mot);
		$mot = preg_replace(",٤,u", "^٤^", $mot);
		$mot = preg_replace(",٥,u", "^٥^", $mot);
		$mot = preg_replace(",٦,u", "^٦^", $mot);
		$mot = preg_replace(",٧,u", "^٧^", $mot);
		$mot = preg_replace(",٨,u", "^٨^", $mot);
		$mot = preg_replace(",٩,u", "^٩^", $mot);


		// Ligatures
		$mot = preg_replace(",لا,u", "ﻻ", $mot);
		$mot = preg_replace(",لأ,u", "ﻷ", $mot);
		
		
		foreach ($arabic_letters as $a_l) {
			$mot = preg_replace(",([^\^])".$a_l[0]."([^\^]),u", "\\1".$a_l[3]."\\2", $mot);
			$mot = preg_replace(",\^".$a_l[0]."([^\^]),u", "^".$a_l[2]."\\1", $mot);
			$mot = preg_replace(",([^\^])".$a_l[0]."\^,u", "\\1".$a_l[4]."^", $mot);
			// il semble qu'il ne soit pas necessaire de remplacer
			// la lettre isolee
			//			$mot = preg_replace(",\^".$a_l[0]."\^,u", "^".$a_l[1]."^", $mot);
		}
		
		

		$mot = preg_replace(",\^,u", "", $mot);
		
		$res = $mot;
		$res = rtl_reverse($mot, $rtl_global);

		/*
		$rtl = false;		
		for ($i = 0; $i < spip_strlen($mot); $i++) {
			$lettre = spip_substr($mot, $i, 1);
			$code = rtl_mb_ord($lettre);
			if (($code >= 54928 && $code <= 56767) ||  ($code >= 15708336 && $code <= 15711164)) $rtl = true;
		}
		*/
		
		
		if ($rtl_global) $retour = $res . " " . $retour;
		else $retour = $retour. " ".$res;
	}
	
	
	return $retour;
}




// http://doc.spip.org/@printWordWrapped
function printWordWrapped($image, $top, $left, $maxWidth, $font, $couleur, $text, $textSize, $align="left", $hauteur_ligne = 0) {
	static $memps = array();

	// imageftbbox exige un float, et settype aime le double pour php < 4.2.0
	settype($textSize, 'double');

	// calculer les couleurs ici, car fonctionnement different selon TTF ou PS
	$black = imagecolorallocatealpha($image, hexdec("0x{".substr($couleur, 0,2)."}"), hexdec("0x{".substr($couleur, 2,2)."}"), hexdec("0x{".substr($couleur, 4,2)."}"), 0);
	$grey2 = imagecolorallocatealpha($image, hexdec("0x{".substr($couleur, 0,2)."}"), hexdec("0x{".substr($couleur, 2,2)."}"), hexdec("0x{".substr($couleur, 4,2)."}"), 127);

	// Gaffe, T1Lib ne fonctionne carrement pas bien des qu'on sort de ASCII
	// C'est dommage, parce que la rasterisation des caracteres est autrement plus jolie qu'avec TTF.
	// A garder sous le coude en attendant que ca ne soit plus une grosse bouse.
	// Si police Postscript et que fonction existe...
	if (
	false AND
	strtolower(substr($font,-4)) == ".pfb"
	AND function_exists("imagepstext")) {
		// Traitement specifique pour polices PostScript (experimental)
		$textSizePs = round(1.32 * $textSize);
		if (!$fontps = $memps["$font"]) {
			$fontps = imagepsloadfont($font);
			// Est-ce qu'il faut reencoder? Pas testable proprement, alors... 
			// imagepsencodefont($fontps,find_in_path('polices/standard.enc'));
			$memps["$font"] = $fontps;
		}
	}

	$rtl_global = false;
	for ($i = 0; $i < spip_strlen($text); $i++) {
		$lettre = spip_substr($text, $i, 1);
		$code = rtl_mb_ord($lettre);
		if (($code >= 54928 && $code <= 56767) ||  ($code >= 15707294 && $code <= 15711164)) {
			$rtl_global = true;
		}
	}


	// split the text into an array of single words
	$words = explode(' ', $text);

	// les espaces
	foreach($words as $k=>$v)
		$words[$k] = str_replace(array('~'), array(' '), $v);


	if ($hauteur_ligne == 0) $lineHeight = floor($textSize * 1.3);
	else $lineHeight = $hauteur_ligne;

	$dimensions_espace = imageftbbox($textSize, 0, $font, ' ', array());
	$largeur_espace = $dimensions_espace[2] - $dimensions_espace[0];
	$retour["espace"] = $largeur_espace;


	$line = '';
	while (count($words) > 0) {
		
		$mot = $words[0];
		
		if ($rtl_global) $mot = rtl_visuel($mot,$rtl_global);
	
		$dimensions = imageftbbox($textSize, 0, $font, $line.' '.$mot, array());
		$lineWidth = $dimensions[2] - $dimensions[0]; // get the length of this line, if the word is to be included
		if ($lineWidth > $maxWidth) { // if this makes the text wider that anticipated
			$lines[] = $line; // add the line to the others
			$line = ''; // empty it (the word will be added outside the loop)
		}
		$line .= ' '.$words[0]; // add the word to the current sentence
		$words = array_slice($words, 1); // remove the word from the array
	}
	if ($line != '') { $lines[] = $line; } // add the last line to the others, if it isn't empty
	$height = count($lines) * $lineHeight; // the height of all the lines total
	// do the actual printing
	$i = 0;

	// Deux passes pour recuperer, d'abord, largeur_ligne
	// necessaire pour alignement right et center
	foreach ($lines as $line) {
		if ($rtl_global) $line = rtl_visuel($line, $rtl_global);
		
		$dimensions = imageftbbox($textSize, 0, $font, $line, array());
		$largeur_ligne = $dimensions[2] - $dimensions[0];
		if ($largeur_ligne > $largeur_max) $largeur_max = $largeur_ligne;
	}

	foreach ($lines as $i => $line) {
		if ($rtl_global) $line = rtl_visuel($line, $rtl_global);

		$dimensions = imageftbbox($textSize, 0, $font, $line, array());
		$largeur_ligne = $dimensions[2] - $dimensions[0];
		if ($align == "right") $left_pos = $largeur_max - $largeur_ligne;
		else if ($align == "center") $left_pos = floor(($largeur_max - $largeur_ligne)/2);
		else $left_pos = 0;
		
		
		if ($fontps) {
			$line = trim($line);
			imagepstext ($image, "$line", $fontps, $textSizePs, $black, $grey2, $left + $left_pos, $top + $lineHeight * $i, 0, 0, 0, 16);
		}
		else imagefttext($image, $textSize, 0, $left + $left_pos, $top + $lineHeight * $i, $black, $font, trim($line), array());
	}
	$retour["height"] = $height;# + round(0.3 * $hauteur_ligne);
	$retour["width"] = $largeur_max;
                 
	return $retour;
}
//array imagefttext ( resource image, float size, float angle, int x, int y, int col, string font_file, string text [, array extrainfo] )
//array imagettftext ( resource image, float size, float angle, int x, int y, int color, string fontfile, string text )

// http://doc.spip.org/@produire_image_typo
function produire_image_typo() {
	/*
	arguments autorises:
	
	$texte : le texte a transformer; attention: c'est toujours le premier argument, et c'est automatique dans les filtres
	$couleur : la couleur du texte dans l'image - pas de dieze
	$police: nom du fichier de la police (inclure terminaison)
	$largeur: la largeur maximale de l'image ; attention, l'image retournee a une largeur inferieure, selon les limites reelles du texte
	$hauteur_ligne: la hauteur de chaque ligne de texte si texte sur plusieurs lignes
	(equivalent a "line-height")
	$padding: forcer de l'espace autour du placement du texte; necessaire pour polices a la con qui "depassent" beaucoup de leur boite 
	$align: alignement left, right, center
	*/



	// Recuperer les differents arguments
	$numargs = func_num_args();
	$arg_list = func_get_args();
	$texte = $arg_list[0];
	for ($i = 1; $i < $numargs; $i++) {
		if (($p = strpos($arg_list[$i], "="))!==FALSE) {
			$nom_variable = substr($arg_list[$i], 0, $p);
			$val_variable = substr($arg_list[$i], $p+1);
		
			$variable["$nom_variable"] = $val_variable;
		}
		
	}

	// Construire requete et nom fichier
	$text = str_replace("&nbsp;", "~", $texte);	
	$text = preg_replace(",(\r|\n)+,ms", " ", $text);
	include_spip('inc/charsets');
	$text = html2unicode(strip_tags($text));
	if (strlen($text) == 0) return "";

	$taille = $variable["taille"];
	if ($taille < 1) $taille = 16;

	$couleur = couleur_html_to_hex($variable["couleur"]);
	if (strlen($couleur) < 6) $couleur = "000000";

	$alt = $texte;
		
	$align = $variable["align"];
	if (!$variable["align"]) $align="left";
	 
	$police = $variable["police"];
	if (strlen($police) < 2) $police = "dustismo.ttf";

	$largeur = $variable["largeur"];
	if ($largeur < 5) $largeur = 600;

	if ($variable["hauteur_ligne"] > 0) $hauteur_ligne = $variable["hauteur_ligne"];
	else $hauteur_ligne = 0;
	if ($variable["padding"] > 0) $padding = $variable["padding"];
	else $padding = 0;
	


	$string = "$text-$taille-$couleur-$align-$police-$largeur-$hauteur_ligne-$padding";
	$query = md5($string);
	$dossier = sous_repertoire(_DIR_VAR, 'cache-texte');
	$fichier = "$dossier$query.png";

	$flag_gd_typo = function_exists("imageftbbox")
		&& function_exists('imageCreateTrueColor');

	
	if (file_exists($fichier))
		$image = $fichier;
	else if (!$flag_gd_typo)
		return $texte;
	else {
		$font = find_in_path('polices/'.$police);
		if (!$font) {
			spip_log(_T('fichier_introuvable', array('fichier' => $police)));
			$font = find_in_path('polices/'."dustismo.ttf");
		}

		$imgbidon = imageCreateTrueColor($largeur, 45);
		$retour = printWordWrapped($imgbidon, $taille+5, 0, $largeur, $font, $couleur, $text, $taille, 'left', $hauteur_ligne);
		$hauteur = $retour["height"];
		$largeur_reelle = $retour["width"];
		$espace = $retour["espace"];
		imagedestroy($imgbidon);

		$im = imageCreateTrueColor($largeur_reelle-$espace+(2*$padding), $hauteur+5+(2*$padding));
		imagealphablending ($im, FALSE );
		imagesavealpha ( $im, TRUE );
		
		// Creation de quelques couleurs
		
		$grey2 = imagecolorallocatealpha($im, hexdec("0x{".substr($couleur, 0,2)."}"), hexdec("0x{".substr($couleur, 2,2)."}"), hexdec("0x{".substr($couleur, 4,2)."}"), 127);
		ImageFilledRectangle ($im,0,0,$largeur_reelle+(2*$padding),$hauteur+5+(2*$padding),$grey2);
		
		// Le texte a dessiner
		printWordWrapped($im, $taille+5+$padding, $padding, $largeur, $font, $couleur, $text, $taille, $align, $hauteur_ligne);
		
		
		// Utiliser imagepng() donnera un texte plus claire,
		// compare a l'utilisation de la fonction imagejpeg()
		imagepng($im, $fichier);
		imagedestroy($im);
		
		$image = $fichier;
	}


	$dimensions = getimagesize($image);
	$largeur = $dimensions[0];
	$hauteur = $dimensions[1];
	return inserer_attribut("<img src='$image' width='$largeur' height='$hauteur' style='width:".$largeur."px;height:".$hauteur."px;' />", 'alt', $alt);
}



//
// Produire des fichiers au format .ico
// avec du code recupere de :
//
//////////////////////////////////////////////////////////////
///  phpThumb() by James Heinrich <info@silisoftware.com>   //
//        available at http://phpthumb.sourceforge.net     ///
//////////////////////////////////////////////////////////////
class phpthumb_functions {
// http://doc.spip.org/@GetPixelColor
	function GetPixelColor(&$img, $x, $y) {
		if (!is_resource($img)) {
			return false;
		}
		return @ImageColorsForIndex($img, @ImageColorAt($img, $x, $y));
	}
// http://doc.spip.org/@LittleEndian2String
	function LittleEndian2String($number, $minbytes=1) {
		$intstring = '';
		while ($number > 0) {
			$intstring = $intstring.chr($number & 255);
			$number >>= 8;
		}
		return str_pad($intstring, $minbytes, "\x00", STR_PAD_RIGHT);
	}
// http://doc.spip.org/@GD2ICOstring
	function GD2ICOstring(&$gd_image_array) {
		foreach ($gd_image_array as $key => $gd_image) {

			$ImageWidths[$key]  = ImageSX($gd_image);
			$ImageHeights[$key] = ImageSY($gd_image);
			$bpp[$key]		  = ImageIsTrueColor($gd_image) ? 32 : 24;
			$totalcolors[$key]  = ImageColorsTotal($gd_image);

			$icXOR[$key] = '';
			for ($y = $ImageHeights[$key] - 1; $y >= 0; $y--) {
				for ($x = 0; $x < $ImageWidths[$key]; $x++) {
					$argb = phpthumb_functions::GetPixelColor($gd_image, $x, $y);
					$a = round(255 * ((127 - $argb['alpha']) / 127));
					$r = $argb['red'];
					$g = $argb['green'];
					$b = $argb['blue'];

					if ($bpp[$key] == 32) {
						$icXOR[$key] .= chr($b).chr($g).chr($r).chr($a);
					} elseif ($bpp[$key] == 24) {
						$icXOR[$key] .= chr($b).chr($g).chr($r);
					}

					if ($a < 128) {
						@$icANDmask[$key][$y] .= '1';
					} else {
						@$icANDmask[$key][$y] .= '0';
					}
				}
				// mask bits are 32-bit aligned per scanline
				while (strlen($icANDmask[$key][$y]) % 32) {
					$icANDmask[$key][$y] .= '0';
				}
			}
			$icAND[$key] = '';
			foreach ($icANDmask[$key] as $y => $scanlinemaskbits) {
				for ($i = 0; $i < strlen($scanlinemaskbits); $i += 8) {
					$icAND[$key] .= chr(bindec(str_pad(substr($scanlinemaskbits, $i, 8), 8, '0', STR_PAD_LEFT)));
				}
			}

		}

		foreach ($gd_image_array as $key => $gd_image) {
			$biSizeImage = $ImageWidths[$key] * $ImageHeights[$key] * ($bpp[$key] / 8);

			// BITMAPINFOHEADER - 40 bytes
			$BitmapInfoHeader[$key]  = '';
			$BitmapInfoHeader[$key] .= "\x28\x00\x00\x00";							  // DWORD  biSize;
			$BitmapInfoHeader[$key] .= phpthumb_functions::LittleEndian2String($ImageWidths[$key], 4);	  // LONG   biWidth;
			// The biHeight member specifies the combined
			// height of the XOR and AND masks.
			$BitmapInfoHeader[$key] .= phpthumb_functions::LittleEndian2String($ImageHeights[$key] * 2, 4); // LONG   biHeight;
			$BitmapInfoHeader[$key] .= "\x01\x00";									  // WORD   biPlanes;
			   $BitmapInfoHeader[$key] .= chr($bpp[$key])."\x00";						  // wBitCount;
			$BitmapInfoHeader[$key] .= "\x00\x00\x00\x00";							  // DWORD  biCompression;
			$BitmapInfoHeader[$key] .= phpthumb_functions::LittleEndian2String($biSizeImage, 4);			// DWORD  biSizeImage;
			$BitmapInfoHeader[$key] .= "\x00\x00\x00\x00";							  // LONG   biXPelsPerMeter;
			$BitmapInfoHeader[$key] .= "\x00\x00\x00\x00";							  // LONG   biYPelsPerMeter;
			$BitmapInfoHeader[$key] .= "\x00\x00\x00\x00";							  // DWORD  biClrUsed;
			$BitmapInfoHeader[$key] .= "\x00\x00\x00\x00";							  // DWORD  biClrImportant;
		}


		$icondata  = "\x00\x00";									  // idReserved;   // Reserved (must be 0)
		$icondata .= "\x01\x00";									  // idType;	   // Resource Type (1 for icons)
		$icondata .= phpthumb_functions::LittleEndian2String(count($gd_image_array), 2);  // idCount;	  // How many images?

		$dwImageOffset = 6 + (count($gd_image_array) * 16);
		foreach ($gd_image_array as $key => $gd_image) {
			// ICONDIRENTRY   idEntries[1]; // An entry for each image (idCount of 'em)

			$icondata .= chr($ImageWidths[$key]);					 // bWidth;		  // Width, in pixels, of the image
			$icondata .= chr($ImageHeights[$key]);					// bHeight;		 // Height, in pixels, of the image
			$icondata .= chr($totalcolors[$key]);					 // bColorCount;	 // Number of colors in image (0 if >=8bpp)
			$icondata .= "\x00";									  // bReserved;	   // Reserved ( must be 0)

			$icondata .= "\x01\x00";								  // wPlanes;		 // Color Planes
			$icondata .= chr($bpp[$key])."\x00";					  // wBitCount;	   // Bits per pixel

			$dwBytesInRes = 40 + strlen($icXOR[$key]) + strlen($icAND[$key]);
			$icondata .= phpthumb_functions::LittleEndian2String($dwBytesInRes, 4);	   // dwBytesInRes;	// How many bytes in this resource?

			$icondata .= phpthumb_functions::LittleEndian2String($dwImageOffset, 4);	  // dwImageOffset;   // Where in the file is this image?
			$dwImageOffset += strlen($BitmapInfoHeader[$key]);
			$dwImageOffset += strlen($icXOR[$key]);
			$dwImageOffset += strlen($icAND[$key]);
		}

		foreach ($gd_image_array as $key => $gd_image) {
			$icondata .= $BitmapInfoHeader[$key];
			$icondata .= $icXOR[$key];
			$icondata .= $icAND[$key];
		}

		return $icondata;
	}

}

?>
