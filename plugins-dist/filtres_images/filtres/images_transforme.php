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
 * Toutes les fonctions image_xx de ce fichier :
 *  - prennent une image en entree
 *  - fournissent une image en sortie
 *  - sont chainables les unes derrieres les autres dans toutes les combinaisons possibles
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

// librairie de base du core
include_spip('inc/filtres_images_mini');

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
	$image = _image_valeurs_trans($im, "aplatir-$format-$coul-$qualite-$transparence", $format, $fonction);

	if (!$image) return("");

	include_spip('inc/filtres');
	$couleurs = _couleur_hex_to_dec($coul);
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
			_image_gd_output($im, $image, $qualite);
		}
		else
			_image_gd_output($im_, $image, $qualite);
		imagedestroy($im_);
		imagedestroy($im);
	}
	return _image_ecrire_tag($image,array('src'=>$dest));
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


// Transforme l'image en PNG transparent
// alpha = 0: aucune transparence
// alpha = 127: completement transparent
// http://doc.spip.org/@image_alpha
function image_alpha($im, $alpha = 63)
{
	$fonction = array('image_alpha', func_get_args());
	$image = _image_valeurs_trans($im, "alpha-$alpha", "png",$fonction);
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
		_image_gd_output($im_,$image);
		imagedestroy($im_);
		imagedestroy($im);
		imagedestroy($im2);
	}
	

	return _image_ecrire_tag($image,array('src'=>$dest));
	
}

// http://doc.spip.org/@image_recadre
function image_recadre($im,$width,$height,$position='center', $background_color='white')
{
	$fonction = array('image_recadre', func_get_args());
	$image = _image_valeurs_trans($im, "recadre-$width-$height-$position-$background_color",false,$fonction);
	
	if (!$image) return("");
	
	$x_i = $image["largeur"];
	$y_i = $image["hauteur"];

	if (_IMG_GD_MAX_PIXELS && $x_i*$y_i>_IMG_GD_MAX_PIXELS){
		spip_log("image_recadre impossible sur $im : ".$srcWidth*$srcHeight."pixels");
		// on se rabat sur une reduction CSS
		return _image_tag_changer_taille($im,$width,$height);
	}

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
			$bg = _couleur_hex_to_dec($background_color);
			$color_t = imagecolorallocate( $im_, $bg['red'], $bg['green'], $bg['blue']);
		}
		imagefill ($im_, 0, 0, $color_t);
		imagecopy($im_, $im, max(0,-$offset_width), max(0,-$offset_height), max(0,$offset_width), max(0,$offset_height), min($width,$x_i), min($height,$y_i));

		_image_gd_output($im_,$image);
		imagedestroy($im_);
		imagedestroy($im);
	}
	
	return _image_ecrire_tag($image,array('src'=>$dest,'width'=>$width,'height'=>$height));
}


/**
 * Recadrer une image dans le rectangle le plus petit possible sans perte
 * de pixels non transparent
 *
 * @param string $im
 * @return string
 */
function image_recadre_mini($im)
{
	$fonction = array('image_recadre_mini', func_get_args());
	$image = _image_valeurs_trans($im, "recadre_mini",false,$fonction);

	if (!$image) return("");

	$width = $image["largeur"];
	$height = $image["hauteur"];

	$im = $image["fichier"];
	$dest = $image["fichier_dest"];

	$creer = $image["creer"];
	if ($creer) {
		$im = $image["fonction_imagecreatefrom"]($im);
		imagepalettetotruecolor($im);

		// trouver le rectangle mini qui contient des infos de couleur
		// recherche optimisee qui ne balaye que par zone
		$min_x = $width;
		$min_y = $height;
		$max_y = $max_x = 0;
		$yy = 0;
		while ($yy<=$height/2 AND $max_y<=$min_y){
			if($yy<$min_y)
				for ($xx = 0; $xx < $width; $xx++) {
					$color_index = imagecolorat($im, $xx, $yy);
					$color_tran = imagecolorsforindex($im, $color_index);
					if ($color_tran['alpha']!==127){
						$min_y = min($yy,$min_y);
						$max_y = max($height-1-$yy,$max_y);
						break;
					}
				}
			if($height-1-$yy>$max_y)
				for ($xx = 0; $xx < $width; $xx++) {
					$color_index = imagecolorat($im, $xx, $height-1-$yy);
					$color_tran = imagecolorsforindex($im, $color_index);
					if ($color_tran['alpha']!==127){
						$min_y = min($yy,$min_y);
						$max_y = max($height-1-$yy,$max_y);
						break;
					}
				}
			$yy++;
		}
		$min_y = min($max_y,$min_y); // tout a 0 aucun pixel trouve

		$xx = 0;
		while ($xx<=$width/2 AND $max_x<=$min_x){
			if ($xx<$min_x)
				for ($yy = $min_y; $yy < $max_y; $yy++) {
					$color_index = imagecolorat($im, $xx, $yy);
					$color_tran = imagecolorsforindex($im, $color_index);
					if ($color_tran['alpha']!==127){
						$min_x = min($xx,$min_x);
						$max_x = max($xx,$max_x);
						break; // inutile de continuer sur cette colonne
					}
				}
			if ($width-1-$xx>$max_x)
				for ($yy = $min_y; $yy < $max_y; $yy++) {
					$color_index = imagecolorat($im, $width-1-$xx, $yy);
					$color_tran = imagecolorsforindex($im, $color_index);
					if ($color_tran['alpha']!==127){
						$min_x = min($width-1-$xx,$min_x);
						$max_x = max($width-1-$xx,$max_x);
						break; // inutile de continuer sur cette colonne
					}
				}
			$xx++;
		}
		$min_x = min($max_x,$min_x); // tout a 0 aucun pixel trouve

		$width = $max_x-$min_x+1;
		$height = $max_y-$min_y+1;

		$im_ = imagecreatetruecolor($width, $height);
		@imagealphablending($im_, false);
		@imagesavealpha($im_,true);

		$color_t = imagecolorallocatealpha( $im_, 255, 255, 255 , 127 );
		imagefill ($im_, 0, 0, $color_t);
		imagecopy($im_, $im, 0, 0, $min_x, $min_y, $width, $height);

		_image_gd_output($im_,$image);
		imagedestroy($im_);
		imagedestroy($im);
	}
	else {
		list ($height,$width) = taille_image($image['fichier_dest']);
	}

	return _image_ecrire_tag($image,array('src'=>$dest,'width'=>$width,'height'=>$height));
}


// http://doc.spip.org/@image_flip_vertical
function image_flip_vertical($im)
{
	$fonction = array('image_flip_vertical', func_get_args());
	$image = _image_valeurs_trans($im, "flip_v", false,$fonction);
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

		_image_gd_output($im_,$image);
		imagedestroy($im_);
		imagedestroy($im);
	}
	
	return _image_ecrire_tag($image,array('src'=>$dest));
}

// http://doc.spip.org/@image_flip_horizontal
function image_flip_horizontal($im)
{
	$fonction = array('image_flip_horizontal', func_get_args());
	$image = _image_valeurs_trans($im, "flip_h",false,$fonction);
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
		_image_gd_output($im_,$image);
		imagedestroy($im_);
		imagedestroy($im);
	}
	
	return _image_ecrire_tag($image,array('src'=>$dest));
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
	// dans ce cas, le masque est place a ces positions sur l'image d'origine,
	// et evidemment cette image d'origine n'est pas redimensionnee
	// 
	// Positionnement horizontal: text-align=left, right, center
	// Positionnement vertical : vertical-align=top, bottom, middle
	// (les positionnements left, right, top, left sont relativement inutiles, mais coherence avec CSS)
	//
	// Choix du mode de fusion: mode=masque, normal, eclaircir, obscurcir, produit, difference, ecran, superposer, lumiere_dure, teinte, saturation, valeur
	// https://en.wikipedia.org/wiki/Blend_modes
	// masque: mode par defaut
	// normal: place la nouvelle image par dessus l'ancienne
	// eclaircir: place uniquement les points plus clairs
	// obscurcir: place uniquement les points plus fonc'es
	// produit: multiplie par le masque (points noirs rendent l'image noire, points blancs ne changent rien)
	// difference: remplit avec l'ecart entre les couleurs d'origine et du masque
	// ecran: effet inverse de 'produit' -> l'image resultante est plus claire
	// superposer: combine les modes 'produit' et 'ecran' -> les parties claires sont eclaircies, les parties sombres assombries.
	// lumiere_dure: equivalent a 'superposer', sauf que l'image du bas et du haut sont inversees.
	// teinte: utilise la teinte du masque
	// saturation: utilise la saturation du masque
	// valeur: utilise la valeur du masque

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

	// utiliser _image_valeurs_trans pour accepter comme masque :
	// - une balise <img src='...' />
	// - une image avec un timestamp ?01234
	$mask = _image_valeurs_trans($masque, "source-image_masque", "png",null, true);
	if (!$mask) return("");
	$masque = $mask['fichier'];

	$pos = md5(serialize($variable).$mask['date_src']);
	$fonction = array('image_masque', func_get_args());
	$image = _image_valeurs_trans($im, "masque-$masque-$pos", "png",$fonction);
	if (!$image) return("");

	$x_i = $image["largeur"];
	$y_i = $image["hauteur"];

	$im = $image["fichier"];
	$dest = $image["fichier_dest"];

	$creer = $image["creer"];

	// doit-on positionner l'image ?
	$placer = false;
	foreach (array("right", "left", "bottom", "top", "text-align", "vertical-align") as $pl) {
		if (isset($defini[$pl]) and $defini[$pl]) {
			$placer = true;
			break;
		}
	}

	if ($creer) {
		
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
			
			if (isset($defini["right"]) and $defini["right"]) {
				$right = $variable["right"];
				$dx = ($x_i - $x_m) - $right;
			}
			if (isset($defini["bottom"]) and $defini["bottom"]) {
				$bottom = $variable["bottom"];
				$dy = ($y_i - $y_m) - $bottom;
				}
			if (isset($defini["top"]) and $defini["top"]) {
				$top = $variable["top"];
				$dy = $top;
			}
			if (isset($defini["left"]) and $defini["left"]) {
				$left = $variable["left"];
				$dx = $left;
			}
			if (isset($defini["text-align"]) and $defini["text-align"]) {
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
			if (isset($defini["vertical-align"]) and $defini["vertical-align"]) {
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


		$nouveau = _image_valeurs_trans(image_reduire($im, $x_d, $y_d),"");
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


		// calcul couleurs de chaque pixel selon les modes de fusion
		for ($x = 0; $x < $x_dest; $x++) {
			for ($y=0; $y < $y_dest; $y++) {
				$rgb = ImageColorAt($im2, $x, $y); // image au dessus
				$a = ($rgb >> 24) & 0xFF;
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;

				$rgb2 = ImageColorAt($im, $x+$x_dec, $y+$y_dec); // image en dessous
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
						} else if ($v2 == 0) {
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

				} elseif (in_array($mode, array("produit","difference","superposer","lumiere_dure","ecran"))) {
					if ($mode == "produit") {
						$r = ($r/255) * $r2;
						$g = ($g/255) * $g2;
						$b = ($b/255) * $b2;
					} elseif ($mode == "difference") {
						$r = abs($r-$r2);
						$g = abs($g-$g2);
						$b = abs($b-$b2);
					} elseif ($mode == "superposer") {
						$r = ($r2 < 128) ? 2 * $r * $r2 / 255 : 255 - (2 * (255 - $r) * (255 - $r2) / 255);
						$g = ($g2 < 128) ? 2 * $g * $g2 / 255 : 255 - (2 * (255 - $g) * (255 - $g2) / 255);
						$b = ($b2 < 128) ? 2 * $b * $b2 / 255 : 255 - (2 * (255 - $b) * (255 - $b2) / 255);
					} elseif ($mode == "lumiere_dure") {
						$r = ($r < 128) ? 2 * $r * $r2 / 255 : 255 - (2 * (255 - $r2) * (255 - $r) / 255);
						$g = ($g < 128) ? 2 * $g * $g2 / 255 : 255 - (2 * (255 - $g2) * (255 - $g) / 255);
						$b = ($b < 128) ? 2 * $b * $b2 / 255 : 255 - (2 * (255 - $b2) * (255 - $b) / 255);
					} elseif ($mode == "ecran") {
						$r = 255 - (((255 - $r) * (255 - $r2)) / 255);
						$g = 255 - (((255 - $g) * (255 - $g2)) / 255);
						$b = 255 - (((255 - $b) * (255 - $b2)) / 255);
					}
					$r = max(0, min($r, 255));
					$g = max(0, min($g, 255));
					$b = max(0, min($b, 255));

					// melange en fonction de la transparence du masque
					$v = (127 - $a) / 127;
					if ($v == 1) { // melange complet
						$r_ = $r;
						$g_ = $g;
						$b_ = $b;
					} else {
						$v2 = (127 - $a2) / 127;
						if ($v+$v2 == 0) { // ??
							$r_ = $r2;
							$g_ = $g2;
							$b_ = $b2;
						} else { // pas de melange (transparence du masque)
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

				} elseif (in_array($mode, array("teinte","saturation","valeur"))) {
					include_spip("filtres/images_lib");
					$hsv = _couleur_rgb2hsv($r, $g, $b); // image au dessus
					$h = $hsv["h"];
					$s = $hsv["s"];
					$v = $hsv["v"];
					$hsv2 = _couleur_rgb2hsv($r2, $g2, $b2); // image en dessous
					$h2 = $hsv2["h"];
					$s2 = $hsv2["s"];
					$v2 = $hsv2["v"];
					switch ($mode)  {
						case "teinte";
							$rgb3 = _couleur_hsv2rgb($h, $s2, $v2);
							break;
						case "saturation";
							$rgb3 = _couleur_hsv2rgb($h2, $s, $v2);
							break;
						case "valeur";
							$rgb3 = _couleur_hsv2rgb($h2, $s2, $v);
							break;
					}
					$r = $rgb3["r"];
					$g = $rgb3["g"];
					$b = $rgb3["b"];

					// melange en fonction de la transparence du masque
					$v = (127 - $a) / 127;
					if ($v == 1) { // melange complet
						$r_ = $r;
						$g_ = $g;
						$b_ = $b;
					} else {
						$v2 = (127 - $a2) / 127;
						if ($v+$v2 == 0) { // ??
							$r_ = $r2;
							$g_ = $g2;
							$b_ = $b2;
						} else { // pas de melange (transparence du masque)
							$r_ = $r + (($r2 - $r) * $v2 * (1 - $v));
							$g_ = $g + (($g2 - $g) * $v2 * (1 - $v));
							$b_ = $b + (($b2 - $b) * $v2 * (1 - $v));
						}
					}
					$a_ = $a2;

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

		_image_gd_output($im_,$image);
		imagedestroy($im_);
		imagedestroy($im);
		imagedestroy($im2);

	}
	$x_dest = largeur($dest);
	$y_dest = hauteur($dest);
	return _image_ecrire_tag($image,array('src'=>$dest,'width'=>$x_dest,'height'=>$y_dest));
}

// Passage de l'image en noir et blanc
// un noir & blanc "photo" n'est pas "neutre": les composantes de couleur sont
// ponderees pour obtenir le niveau de gris;
// on peut ici regler cette ponderation en "pour mille"
// http://doc.spip.org/@image_nb
function image_nb($im, $val_r = 299, $val_g = 587, $val_b = 114)
{
	$fonction = array('image_nb', func_get_args());
	$image = _image_valeurs_trans($im, "nb-$val_r-$val_g-$val_b",false,$fonction);
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
		_image_gd_output($im_,$image);
		imagedestroy($im_);
		imagedestroy($im);
	}

	return _image_ecrire_tag($image,array('src'=>$dest));
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
	$image = _image_valeurs_trans($im, "flou-$niveau", false,$fonction);
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
	
		_image_gd_output($temp2,$image);
		imagedestroy($temp1);	
		imagedestroy($temp2);	
	}
	
	return _image_ecrire_tag($image,array('src'=>$dest,'width'=>($x_i+$niveau),'height'=>($y_i+$niveau)));
}

// http://doc.spip.org/@image_RotateBicubic
function image_RotateBicubic($src_img, $angle, $bicubic=0) {
	include_spip('filtres/images_lib');
   
	if (round($angle/90)*90 == $angle) {
		$droit = true;
		if (round($angle/180)*180 == $angle) $rot = 180;
		else $rot = 90;
	}
	else 
		$droit = false;
   
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
					$d1 = _image_distance_pixel($xo, $yo, $x0, $y0);
					
					$rgb = ImageColorAt($src_img, $x1, $y0); 
					$a2 = ($rgb >> 24) & 0xFF;
					$r2 = ($rgb >> 16) & 0xFF;
					$g2 = ($rgb >> 8) & 0xFF;
					$b2 = $rgb & 0xFF;
					$d2 = _image_distance_pixel($xo, $yo, $x1, $y0);
					
					$rgb = ImageColorAt($src_img,$x0, $y1); 
					$a3 = ($rgb >> 24) & 0xFF;
					$r3 = ($rgb >> 16) & 0xFF;
					$g3 = ($rgb >> 8) & 0xFF;
					$b3 = $rgb & 0xFF;
					$d3 = _image_distance_pixel($xo, $yo, $x0, $y1);
					
					$rgb = ImageColorAt($src_img,$x1, $y1);
					$a4 = ($rgb >> 24) & 0xFF;
					$r4 = ($rgb >> 16) & 0xFF;
					$g4 = ($rgb >> 8) & 0xFF;
					$b4 = $rgb & 0xFF;
					$d4 = _image_distance_pixel($xo, $yo, $x1, $y1);
					
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
				} 
				else {
					$color = imagecolorat($src_img, round($old_x), round($old_y));
				}
			}
			else {
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
	$image = _image_valeurs_trans($im, "rot-$angle-$crop", "png", $fonction);
	if (!$image) return("");
	
	$im = $image["fichier"];
	$dest = $image["fichier_dest"];
	
	$creer = $image["creer"];
	
	if ($creer) {
		$effectuer_gd = true;

		if(is_callable(array('Imagick','rotateImage'))){
			$imagick = new Imagick();
			$imagick->readImage($im);
			$imagick->rotateImage(new ImagickPixel('none'), $angle);
			$imagick->writeImage($dest);
			$effectuer_gd = false;
		}
		else if ($GLOBALS['meta']['image_process'] == "convert") {
			if (_CONVERT_COMMAND!='') {
				@define ('_CONVERT_COMMAND', 'convert');
				@define ('_ROTATE_COMMAND', _CONVERT_COMMAND.' -background none -rotate %t %src %dest');
			} else
				@define ('_ROTATE_COMMAND', '');
			if (_ROTATE_COMMAND!=='') {
				$commande = str_replace(
					array('%t', '%src', '%dest'),
					array(
						$angle,
						escapeshellcmd($im),
						escapeshellcmd($dest)
					),
					_ROTATE_COMMAND);
				spip_log($commande);
				exec($commande);
				if (file_exists($dest)) // precaution
					$effectuer_gd = false;
			}
		}
		// cette variante genere-t-elle un fond transparent
		// dans les coins vide issus de la rotation ?
		elseif (function_exists('imagick_rotate')) {
			$handle = imagick_readimage ($im);
			if ($handle && imagick_isopaqueimage( $handle )) {
				imagick_setfillcolor($handle, 'transparent');
				imagick_rotate( $handle, $angle);
				imagick_writeimage( $handle, $dest);
				$effectuer_gd = false;
			}
		}
		if ($effectuer_gd) {
			// Creation de l'image en deux temps
			// de facon a conserver les GIF transparents
			$im = $image["fonction_imagecreatefrom"]($im);
			imagepalettetotruecolor($im);
			$im = image_RotateBicubic($im, $angle, true);
			_image_gd_output($im,$image);
			imagedestroy($im);
		}
	}
	list ($src_y,$src_x) = taille_image($dest);
	return _image_ecrire_tag($image,array('src'=>$dest,'width'=>$src_x,'height'=>$src_y));
}

// Permet d'appliquer un filtre php_imagick a une image
// par exemple: [(#LOGO_ARTICLE|image_imagick{imagick_wave,20,60})]
// liste des fonctions: http://www.linux-nantes.org/~fmonnier/doc/imagick/
// http://doc.spip.org/@image_imagick
function image_imagick () {
	$tous = func_get_args();
	$img = $tous[0];
	$fonc = $tous[1];
	$tous[0]="";
	$tous_var = join($tous, "-");

	$fonction = array('image_imagick', func_get_args());
	$image = _image_valeurs_trans($img, "$tous_var", "png",$fonction);
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
	return _image_ecrire_tag($image,array('src'=>$dest,'width'=>$src_x,'height'=>$src_y));

}

// Permet de rendre une image
// plus claire (gamma > 0)
// ou plus foncee (gamma < 0)
// http://doc.spip.org/@image_gamma
function image_gamma($im, $gamma = 0){
	include_spip('filtres/images_lib');
	$fonction = array('image_gamma', func_get_args());
	$image = _image_valeurs_trans($im, "gamma-$gamma",false,$fonction);
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

				$r = _image_decale_composante($r, $gamma);
				$g = _image_decale_composante($g, $gamma);
				$b = _image_decale_composante($b, $gamma);

				$color = ImageColorAllocateAlpha( $im_, $r, $g, $b , $a );
				imagesetpixel ($im_, $x, $y, $color);			
			}
		}
		_image_gd_output($im_,$image);
	}
	return _image_ecrire_tag($image,array('src'=>$dest));
}

// Passe l'image en "sepia"
// On peut fixer les valeurs RGB 
// de la couleur "complementaire" pour forcer une dominante
//function image_sepia($im, $dr = 137, $dv = 111, $db = 94)
// http://doc.spip.org/@image_sepia
function image_sepia($im, $rgb = "896f5e"){
	include_spip('filtres/images_lib');
	
	if (!function_exists("imagecreatetruecolor")) return $im;
	
	$couleurs = _couleur_hex_to_dec($rgb);
	$dr= $couleurs["red"];
	$dv= $couleurs["green"];
	$db= $couleurs["blue"];
		
	$fonction = array('image_sepia', func_get_args());
	$image = _image_valeurs_trans($im, "sepia-$dr-$dv-$db",false,$fonction);
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


				$r = _image_decale_composante_127($r, $dr);
				$g = _image_decale_composante_127($g, $dv);
				$b = _image_decale_composante_127($b, $db);

				$color = ImageColorAllocateAlpha( $im_, $r, $g, $b , $a );
				imagesetpixel ($im_, $x, $y, $color);			
			}
		}
		_image_gd_output($im_,$image);
		imagedestroy($im_);
		imagedestroy($im);
	}
	
	return _image_ecrire_tag($image,array('src'=>$dest));
}


// Renforcer la nettete d'une image
// http://doc.spip.org/@image_renforcement
function image_renforcement($im, $k=0.5)
{
	$fonction = array('image_flou', func_get_args());
	$image = _image_valeurs_trans($im, "renforcement-$k",false,$fonction);
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
		_image_gd_output($im_,$image);
	}

	return _image_ecrire_tag($image,array('src'=>$dest));
}




// 
// alpha = 0: aucune transparence
// alpha = 127: completement transparent
/**
 * Transforme la couleur de fond de l'image en transparence
 * Le filtre ne g�re pas la notion de contiguite aux bords, et affectera tous les pixels de l'image dans la couleur visee
 * $background_color : couleur cible
 * $tolerance : distance L1 dans l'espace RGB des couleur autour de la couleur $background_color pour lequel la transparence sera appliquee
 * $alpha : transparence a appliquer pour les pixels de la couleur cibles avec la tolerance ci-dessus
 * $coeff_lissage : coeff applique a la tolerance pour determiner la decroissance de la transparence fonction de la distance L1 entre la couleur du pixel et la couleur cible
 *
 * @param string $im
 * @param string $background_color
 * @param int $tolerance
 * @param int $alpha
 * @param float $coeff_lissage
 * @return string
 */
function image_fond_transparent($im, $background_color, $tolerance=12, $alpha = 127, $coeff_lissage=7)
{
	$fonction = array('image_fond_transparent', func_get_args());
	$image = _image_valeurs_trans($im, "fond_transparent-$background_color-$tolerance-$coeff_lissage-$alpha", "png", $fonction);
	if (!$image) return("");
	
	$x_i = $image["largeur"];
	$y_i = $image["hauteur"];
	
	$im = $image["fichier"];
	$dest = $image["fichier_dest"];
	
	$creer = $image["creer"];
	
	if (true OR $creer) {
		$bg = _couleur_hex_to_dec($background_color);
		$bg_r = $bg['red'];
		$bg_g = $bg['green'];
		$bg_b = $bg['blue'];
	
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
		$color_f = ImageColorAllocateAlpha( $im_, 255, 255, 255 , $alpha );

		for ($x = 0; $x < $x_i; $x++) {
			for ($y = 0; $y < $y_i; $y++) {
				$rgb = ImageColorAt($im2, $x, $y);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				if ((($d=abs($r-$bg_r)+abs($g-$bg_g)+abs($b-$bg_b))<=$tolerance)){
					imagesetpixel ( $im_, $x, $y, $color_f );
				}
				elseif ($tolerance AND $d<=($coeff_lissage+1)*$tolerance){
					$transp = round($alpha*(1-($d-$tolerance)/($coeff_lissage*$tolerance)));
					$color_p = ImageColorAllocateAlpha( $im_, $r, $g, $b , $transp);					
					imagesetpixel ( $im_, $x, $y, $color_p );
				}
				else
					imagesetpixel ( $im_, $x, $y, $rgb );
			}
		}
		_image_gd_output($im_,$image);
		imagedestroy($im_);
		imagedestroy($im);
		imagedestroy($im2);
	}
	
	return _image_ecrire_tag($image,array('src'=>$dest));
}
?>
