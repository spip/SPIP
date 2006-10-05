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

// Fonctions de traitement d'image
// uniquement pour GD2
// http://doc.spip.org/@image_valeurs_trans
function image_valeurs_trans($img, $effet, $forcer_format = false) {
	if (strlen($img)==0) return false;

	
	$fichier = extraire_attribut($img, 'src');
	if (($p=strpos($fichier,'?'))!==FALSE)
		$fichier=substr($fichier,0,$p);
	if (strlen($fichier) < 1) $fichier = $img;

	if (!file_exists($fichier)) return false;
	
	if (preg_match(",^.*+(?<=\.(gif|jpg|png)),", $fichier, $regs)) {
		$terminaison = $regs[1];
		$terminaison_dest = $terminaison;
		
		if ($terminaison == "gif") $terminaison_dest = "png";
	}
	if ($forcer_format) $terminaison_dest = $forcer_format;
	
	$term_fonction = $terminaison;
	if ($term_fonction == "jpg") $term_fonction = "jpeg";
	$term_fonction_dest = $terminaison_dest;
	if ($term_fonction_dest == "jpg") $term_fonction_dest = "jpeg";

	$nom_fichier = substr($fichier, 0, strlen($fichier) - 4);
	$fichier_dest = "$nom_fichier-$effet";
	$fichier_dest = md5($fichier_dest);
	$fichier_dest = sous_repertoire(_DIR_IMG, "cache-gd2") . $fichier_dest . "." .$terminaison_dest;
	
	$creer = true;
	if (@filemtime($fichier) < @filemtime($fichier_dest)) {
		$creer = false;
	}
	
	include_spip('inc/logos');
	list ($ret["hauteur"],$ret["largeur"]) = taille_image($img);
	$ret["fichier"] = $fichier;
	$ret["fonction_imagecreatefrom"] = "imagecreatefrom".$term_fonction;
	$ret["fonction_image"] = "image".$term_fonction_dest;
	$ret["fichier_dest"] = $fichier_dest;
	$ret["format_source"] = $terminaison;
	$ret["format_dest"] = $terminaison_dest;
	$ret["creer"] = $creer;
	$ret["class"] = extraire_attribut($img, 'class');
	$ret["alt"] = extraire_attribut($img, 'alt');
	$ret["style"] = extraire_attribut($img, 'style');
	return $ret;

}


// fonctions individuelles qui s'appliquent a une image
// http://doc.spip.org/@image_reduire
function image_reduire($img, $taille=-1, $taille_y=-1) {
	include_spip('inc/logos');

	$image = reduire_image_logo($img, $taille, $taille_y, false);

	// Cas du mouseover genere par les logos de survol de #LOGO_ARTICLE
	if (!eregi("onmouseover=\"this\.src=\'([^']+)\'\"", $img, $match))
		return $image;
	
	$mouseover = extraire_attribut(
		reduire_image_logo($match[1], $taille, $taille_y, false),
		'src');

	$mouseout = extraire_attribut($image, 'src');
	$js_mouseover = "onmouseover=\"this.src='$mouseover'\""
			." onmouseout=\"this.src='$mouseout'\" />";
	return preg_replace(",( /)?".">,", $js_mouseover, $image);
}

// Reduire une image d'un certain facteur
// http://doc.spip.org/@image_reduire_par
function image_reduire_par ($img, $val=1) {
	include_spip('inc/logos');
	list ($hauteur,$largeur) = taille_image($img);

	$l = round($largeur/$val);
	$h = round($hauteur/$val);
	
	if ($l > $h) $h = 0;
	else $l = 0;
	
	$img = image_reduire($img, $l, $h);

	return $img;
}

// Transforme l'image en PNG transparent
// alpha = 0: aucune transparence
// alpha = 127: completement transparent
// http://doc.spip.org/@image_alpha
function image_alpha($im, $alpha = 63)
{
	$image = image_valeurs_trans($im, "alpha-$alpha", "png");
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
				
				if (function_exists(imagecolorallocatealpha)) {
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
		$image["fonction_image"]($im_, "$dest");
		imagedestroy($im_);
		imagedestroy($im);
		imagedestroy($im2);
	}
	
	$class = $image["class"];
	if (strlen($class) > 1) $tags=" class='$class'";
	$tags = "$tags alt='".$image["alt"]."'";
	$style = $image["style"];
	if (strlen($style) > 1) $tags="$tags style='$style'";
	
	return "<img src='$dest'$tags />";
}

// http://doc.spip.org/@image_flip_vertical
function image_flip_vertical($im)
{
	$image = image_valeurs_trans($im, "flip_v");
	if (!$image) return("");
	
	$x_i = $image["largeur"];
	$y_i = $image["hauteur"];
	
	$im = $image["fichier"];
	$dest = $image["fichier_dest"];
	
	$creer = $image["creer"];
	
	if ($creer) {
		$im = $image["fonction_imagecreatefrom"]($im);
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

		$image["fonction_image"]($im_, "$dest");
		imagedestroy($im_);
		imagedestroy($im);
	}
	
	$class = $image["class"];
	if (strlen($class) > 1) $tags=" class='$class'";
	$tags = "$tags alt='".$image["alt"]."'";
	$style = $image["style"];
	if (strlen($style) > 1) $tags="$tags style='$style'";
	
	return "<img src='$dest'$tags />";
}

// http://doc.spip.org/@image_flip_horizontal
function image_flip_horizontal($im)
{
	$image = image_valeurs_trans($im, "flip_h");
	if (!$image) return("");
	
	$x_i = $image["largeur"];
	$y_i = $image["hauteur"];
	
	$im = $image["fichier"];
	$dest = $image["fichier_dest"];
	
	$creer = $image["creer"];
	
	if ($creer) {
		$im = $image["fonction_imagecreatefrom"]($im);
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
		$image["fonction_image"]($im_, "$dest");
		imagedestroy($im_);
		imagedestroy($im);
	}
	$class = $image["class"];
	if (strlen($class) > 1) $tags=" class='$class'";
	$tags = "$tags alt='".$image["alt"]."'";
	$style = $image["style"];
	if (strlen($style) > 1) $tags="$tags style='$style'";
	
	return "<img src='$dest'$tags />";
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
		if (ereg("\=", $arg_list[$i])) {
			$nom_variable = substr($arg_list[$i], 0, strpos($arg_list[$i], "="));
			$val_variable = substr($arg_list[$i], strpos($arg_list[$i], "=")+1, strlen($arg_list[$i]));
			$variable["$nom_variable"] = $val_variable;
			$defini["$nom_variable"] = 1;
		}
	}
	if ($defini["mode"]) $mode = $variable["mode"];

	$pos = md5(serialize($variable));

	$image = image_valeurs_trans($im, "masque-$masque-$pos", "png");
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

	include_spip('inc/logos'); // bicoz presence reduire_image, taille_image
	if ($creer) {
		
		$masque = find_in_path($masque);
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
						} else {
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

		$image["fonction_image"]($im_, "$dest");
		imagedestroy($im_);
		imagedestroy($im);
		imagedestroy($im2);

	}

	$class = $image["class"];
	if (strlen($class) > 1) $tags=" class='$class'";
	$tags = "$tags alt='".$image["alt"]."'";
	$style = $image["style"];
	if (strlen($style) > 1) $tags="$tags style='$style'";	
	list ($y_dest,$x_dest) = taille_image($dest);
	return "<img src='$dest' width='".$x_dest."' height='".$y_dest."'$tags />";

}

// Passage de l'image en noir et blanc
// un noir & blanc "photo" n'est pas "neutre": les composantes de couleur sont
// ponderees pour obtenir le niveau de gris;
// on peut ici regler cette ponderation en "pour mille"
// http://doc.spip.org/@image_nb
function image_nb($im, $val_r = 299, $val_g = 587, $val_b = 114)
{
	$image = image_valeurs_trans($im, "nb-$val_r-$val_g-$val_b");
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
		$image["fonction_image"]($im_, "$dest");
		imagedestroy($im_);
		imagedestroy($im);
	}

	$class = $image["class"];
	if (strlen($class) > 1) $tags=" class='$class'";
	$tags = "$tags alt='".$image["alt"]."'";
	$style = $image["style"];
	if (strlen($style) > 1) $tags="$tags style='$style'";
	
	return "<img src='$dest'$tags />";
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
	
	$image = image_valeurs_trans($im, "flou-$niveau");
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
					
					$suma += $a*$coeffs[$niveau][$k];
					$ac = ((127-$a) / 127);
										
					$sumr += $r * $coeffs[$niveau][$k] * $ac;
					$sumg += $g * $coeffs[$niveau][$k] * $ac;
					$sumb += $b * $coeffs[$niveau][$k] * $ac;
					$sum += $coeffs[$niveau][$k] * $ac;
					$sum_ += $coeffs[$niveau][$k];
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
	
		$image["fonction_image"]($temp2, "$dest");
		imagedestroy($temp1);	
	}
	
	$class = $image["class"];
	if (strlen($class) > 1) $tags=" class='$class'";
	$tags = "$tags alt='".$image["alt"]."'";
//	$style = $image["style"]; // on force le remplacement par nouvelles valeurs...
	$style = "height: ".($y_i+$niveau)."px; width: ".($x_i+$niveau)."px;";
	if (strlen($style) > 1) $tags="$tags style='$style'";
	
	return "<img src='$dest'$tags />";
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

	$image = image_valeurs_trans($im, "rot-$angle-$crop", "png");
	if (!$image) return("");
	
	$im = $image["fichier"];
	$dest = $image["fichier_dest"];
	
	$creer = $image["creer"];
	
	if ($creer) {
		// Creation de l'image en deux temps
		// de facon a conserver les GIF transparents
		$im = $image["fonction_imagecreatefrom"]($im);
		$im = image_RotateBicubic($im, $angle, true);
		$image["fonction_image"]($im, "$dest");
		imagedestroy($im);
	}
	include_spip('inc/logos');
	list ($src_y,$src_x) = taille_image($dest);
	
	$class = $image["class"];
	if (strlen($class) > 1) $tags=" class='$class'";
	$tags = "$tags alt='".$image["alt"]."'";
//	$style = $image["style"]; // on force le remplacement par nouvelles valeurs...
	$style = "height: ".$src_y."px; width: ".$src_x."px;";
	if (strlen($style) > 1) $tags="$tags style='$style'";
	
	return "<img src='$dest'$tags />";
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
	$image = image_valeurs_trans($im, "gamma-$gamma");
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
		$image["fonction_image"]($im_, "$dest");
	}
	$class = $image["class"];
	if (strlen($class) > 1) $tags=" class='$class'";
	$tags = "$tags alt='".$image["alt"]."'";
	$style = $image["style"];
	if (strlen($style) > 1) $tags="$tags style='$style'";
	
	return "<img src='$dest'$tags />";
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
	
	$couleurs = couleur_hex_to_dec($rgb);
	$dr= $couleurs["red"];
	$dv= $couleurs["green"];
	$db= $couleurs["blue"];
		
	$image = image_valeurs_trans($im, "sepia-$dr-$dv-$db");
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
		$image["fonction_image"]($im_, "$dest");
		imagedestroy($im_);
		imagedestroy($im);
	}
	$class = $image["class"];
	if (strlen($class) > 1) $tags=" class='$class'";
	$tags = "$tags alt='".$image["alt"]."'";
	$style = $image["style"];
	if (strlen($style) > 1) $tags="$tags style='$style'";
	
	return "<img src='$dest'$tags />";
}

// 1/ Aplatir une image semi-transparente (supprimer couche alpha)
// en remplissant la transparence avec couleur choisir $coul.
// 2/ Forcer le format de sauvegarde (jpg, png, gif)
// http://doc.spip.org/@image_aplatir
function image_aplatir($im, $format='jpg', $coul='000000')
{
	$image = image_valeurs_trans($im, "aplatir-$coul", $format);
	if (!$image) return("");

	include_ecrire("filtres");
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
		$im = $image["fonction_imagecreatefrom"]($im);
		$im_ = imagecreatetruecolor($x_i, $y_i);
		if ($image["format_source"] == "gif" AND function_exists('ImageCopyResampled')) { 
			// Si un GIF est transparent, 
			// fabriquer un PNG transparent  
			// Conserver la transparence 
			if (function_exists("imageAntiAlias")) imageAntiAlias($im_,true); 
			@imagealphablending($im_, false); 
			@imagesavealpha($im_,true); 
			@ImageCopyResampled($im_, $im, 0, 0, 0, 0, $x_i, $y_i, $x_i, $y_i);
			imagedestroy($im);
			$im = $im_;
			$im_ = imagecreatetruecolor($x_i, $y_i);
		}
		@imagealphablending($im_, false);
		@imagesavealpha($im_,true);
		$color_t = ImageColorAllocateAlpha( $im_, $dr, $dv, $db , 0 );
		imagefill ($im_, 0, 0, $color_t);

		$dist = abs($trait);
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
				} else {
					$r = round($a * $r + $dr * (1-$a));
					$g = round($a * $g + $dv * (1-$a));
					$b = round($a * $b + $db * (1-$a));
				}
								
				$color = ImageColorAllocateAlpha( $im_, $r, $g, $b , 0 );
				imagesetpixel ($im_, $x, $y, $color);	
			}
		}
		$image["fonction_image"]($im_, "$dest");
	}

	$class = $image["class"];
	if (strlen($class) > 1) $tags=" class='$class'";
	$tags = "$tags alt='".$image["alt"]."'";
	$style = $image["style"];
	
	return "<img src='$dest'$tags />";
}
// A partir d'une image,
// recupere une couleur
// renvoit sous la forme hexadecimale ("F26C4E" par exemple).
// Par defaut, la couleur choisie se trouve un peu au-dessus du centre de l'image.
// On peut forcer un point en fixant $x et $y, entre 0 et 20.
// http://doc.spip.org/@image_couleur_extraire
function image_couleur_extraire($img, $x=10, $y=6) {
	$fichier = extraire_attribut($img, 'src');
	if (strlen($fichier) < 1) $fichier = $img;

	if (!file_exists($fichier)) return "F26C4E";

	$cache = image_valeurs_trans($img, "coul-$x-$y", "php");
	
	$dest = $cache["fichier_dest"];
	
	$creer = $cache["creer"];
	if ($creer) {
		if (!$GLOBALS["couleur_extraite"]["$fichier-$x-$y"]) {	
			if (file_exists($fichier)) {
				list($width, $height) = getimagesize($fichier);
			
			
				$newwidth = 20;
				$newheight = 20;
			
				$thumb = imagecreate($newwidth, $newheight);
	
				if (ereg("\.jpg", $fichier)) $source = imagecreatefromjpeg($fichier);
				if (ereg("\.gif", $fichier)) $source = imagecreatefromgif($fichier);
				if (ereg("\.png", $fichier)) $source = imagecreatefrompng($fichier);
	
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

// http://doc.spip.org/@couleur_extreme
function couleur_extreme ($couleur) {
	// force la couleur au noir ou au blanc le plus proche
	// -> donc couleur foncee devient noire
	//    et couleur claire devient blanche

	$couleurs = couleur_hex_to_dec($couleur);
	$red = $couleurs["red"];
	$green = $couleurs["green"];
	$blue = $couleurs["blue"];
	
	$moyenne = round(($red+$green+$blue)/3);

	if ($moyenne > 122) $couleur_texte = "ffffff";
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

// Image typographique

// http://doc.spip.org/@printWordWrapped
function printWordWrapped($image, $top, $left, $maxWidth, $font, $couleur, $text, $textSize, $align="left", $hauteur_ligne = 0) {
	
	// calculer les couleurs ici, car fonctionnement different selon TTF ou PS
	$black = imagecolorallocatealpha($image, hexdec("0x{".substr($couleur, 0,2)."}"), hexdec("0x{".substr($couleur, 2,2)."}"), hexdec("0x{".substr($couleur, 4,2)."}"), 0);
	$grey2 = imagecolorallocatealpha($image, hexdec("0x{".substr($couleur, 0,2)."}"), hexdec("0x{".substr($couleur, 2,2)."}"), hexdec("0x{".substr($couleur, 4,2)."}"), 127);

	// Gaffe, T1Lib ne fonctionne carrement pas bien des qu'on sort de ASCII
	// C'est dommage, parce que la rasterisation des caracteres est autrement plus jolie qu'avec TTF.
	// A garder sous le coude en attendant que ca ne soit plus une grosse bouse.
	// Si police Postscript et que fonction existe...
	if (ereg("\.pfb$", $font) AND function_exists("imagepstext") AND 1==2) {
		// Traitement specifique pour polices PostScript (experimental)
		$textSizePs = round(1.32 * $textSize);
		if ($GLOBALS["font"]["$font"]) {
			$fontps = $GLOBALS["font"]["$font"];
		}
		else  {
			$fontps = imagepsloadfont($font);
			// Est-ce qu'il faut reencoder? Pas testable proprement, alors... 
			// imagepsencodefont($fontps,find_in_path('polices/standard.enc'));
			$GLOBALS["font"]["$font"] = $fontps;
		}
	}
	$words = explode(' ', strip_tags($text)); // split the text into an array of single words
	if ($hauteur_ligne == 0) 	$lineHeight = floor($textSize * 1.3);
	else $lineHeight = $hauteur_ligne;

	$dimensions_espace = imageftbbox($textSize, 0, $font, ' ', array());
	$largeur_espace = $dimensions_espace[2] - $dimensions_espace[0];
	$retour["espace"] = $largeur_espace;


	$line = '';
	while (count($words) > 0) {
		$dimensions = imageftbbox($textSize, 0, $font, $line.' '.$words[0], array());
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
		$line = ereg_replace("~", " ", $line);
		$dimensions = imageftbbox($textSize, 0, $font, $line, array());
		$largeur_ligne = $dimensions[2] - $dimensions[0];
		if ($largeur_ligne > $largeur_max) $largeur_max = $largeur_ligne;
	}

	foreach ($lines as $line) {
		$line = ereg_replace("~", " ", $line);
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


		$i++;
	}
	$retour["height"] = $height + round(0.3 * $hauteur_ligne);
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
		if (ereg("\=", $arg_list[$i])) {
			$nom_variable = substr($arg_list[$i], 0, strpos($arg_list[$i], "="));
			$val_variable = substr($arg_list[$i], strpos($arg_list[$i], "=")+1, strlen($arg_list[$i]));
		
			$variable["$nom_variable"] = $val_variable;
		}
		
	}

	// Construire requete et nom fichier
	$text = ereg_replace("\&nbsp;", "~", $texte);	
	$text = ereg_replace("(\r|\n)+", " ", $text);
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
	$dossier = sous_repertoire(_DIR_IMG, 'cache-texte');
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
		ImageFilledRectangle ($im,0,0,$largeur+(2*$padding),$hauteur+5+(2*$padding),$grey2);
		
		// Le texte à dessiner
		// Remplacez le chemin par votre propre chemin de police
		//global $text;
				
		printWordWrapped($im, $taille+5+$padding, $padding, $largeur, $font, $couleur, $text, $taille, $align, $hauteur_ligne);
		
		
		// Utiliser imagepng() donnera un texte plus claire,
		// comparé à l'utilisation de la fonction imagejpeg()
		imagepng($im, $fichier);
		imagedestroy($im);
		
		$image = $fichier;
	}


	$dimensions = getimagesize($image);
	$largeur = $dimensions[0];
	$hauteur = $dimensions[1];
	return inserer_attribut("<img src='$image' style='width: ".$largeur."px; height: ".$hauteur.px."' class='format_png' />", 'alt', $alt);
}

?>
