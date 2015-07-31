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


// http://doc.spip.org/@creer_vignette
function creer_vignette($image, $maxWidth, $maxHeight, $format, $destdir, $destfile, $process='AUTO', $force=false, $test_cache_only = false) {
	// ordre de preference des formats graphiques pour creer les vignettes
	// le premier format disponible, selon la methode demandee, est utilise
	if ($format == 'png')
		$formats_sortie = array('png','jpg','gif');
	else
		$formats_sortie = array('jpg','png','gif');
		
	if (($process == 'AUTO') AND isset($GLOBALS['meta']['image_process']))
		$process = $GLOBALS['meta']['image_process'];

	// liste des formats qu'on sait lire
	$img = isset($GLOBALS['meta']['formats_graphiques'])
	  ? in_array($format,explode(',',$GLOBALS['meta']['formats_graphiques']))
	  : false;

	// si le doc n'est pas une image, refuser
	if (!$force AND !$img) return;

	$destination = sous_repertoire(_DIR_VAR, $destdir) . $destfile;

	// chercher un cache
	$vignette = '';
	foreach (array('gif','jpg','png') as $fmt)
		if (@file_exists($destination.'.'.$fmt)) {
			$vignette = $destination.'.'.$fmt;
			if ($force) @unlink($vignette);
		}

	if ($test_cache_only AND !$vignette) return;

	// utiliser le cache ?
	if (!$test_cache_only)
	if ($force OR !$vignette OR (@filemtime($vignette) < @filemtime($image))) {

		$creation = true;
		// calculer la taille
		if ($srcsize = @getimagesize($image)) {
			$srcWidth=$srcsize[0];
			$srcHeight=$srcsize[1];
			include_spip('inc/chercher_logo');
			list ($destWidth,$destHeight) = image_ratio($srcWidth, $srcHeight, $maxWidth, $maxHeight);
		} else if ($process == 'convert' OR $process == 'imagick') {
			$destWidth = $maxWidth;
			$destHeight = $maxHeight;
		} else {
			spip_log("echec $process sur $image");
			return;
		}

		// Si l'image est de la taille demandee (ou plus petite), simplement
		// la retourner
		if ($srcWidth
		AND $srcWidth <= $maxWidth AND $srcHeight <= $maxHeight) {
			$vignette = $destination.'.'.preg_replace(',^.*\.,', '', $image);
			@copy($image, $vignette);
		}

		// imagemagick en ligne de commande
		else if ($process == 'convert') {
			define('_CONVERT_COMMAND', 'convert');
			define ('_RESIZE_COMMAND', _CONVERT_COMMAND.' -quality 85 -resize %xx%y! %src %dest');
			$format = $formats_sortie[0];
			$vignette = $destination.".".$format;
			$commande = str_replace(
				array('%x', '%y', '%src', '%dest'),
				array(
					$destWidth,
					$destHeight,
					escapeshellcmd($image),
					escapeshellcmd($vignette)
				),
				_RESIZE_COMMAND);
			spip_log($commande);
			exec($commande);
			if (!@file_exists($vignette)) {
				spip_log("echec convert sur $vignette");
				return;	// echec commande
			}
		}
		else
		// imagick (php4-imagemagick)
		if ($process == 'imagick') {
			$format = $formats_sortie[0];
			$vignette = "$destination.".$format;
			$handle = imagick_readimage($image);
			imagick_resize($handle, $destWidth, $destHeight, IMAGICK_FILTER_LANCZOS, 0.75);
			imagick_write($handle, $vignette);
			if (!@file_exists($vignette)) {
				spip_log("echec imagick sur $vignette");
				return;
			}
		}
		else
		// netpbm
		if ($process == "netpbm") {
			define('_PNMSCALE_COMMAND', 'pnmscale'); // chemin a changer dans mes_options
			if (_PNMSCALE_COMMAND == '') return;
			$format_sortie = "jpg";
			$vignette = $destination.".".$format_sortie;
			$pnmtojpeg_command = str_replace("pnmscale", "pnmtojpeg", _PNMSCALE_COMMAND);
			if ($format == "jpg") {
				
				$jpegtopnm_command = str_replace("pnmscale", "jpegtopnm", _PNMSCALE_COMMAND);

				exec("$jpegtopnm_command $image | "._PNMSCALE_COMMAND." -width $destWidth | $pnmtojpeg_command > $vignette");
				if (!@file_exists($vignette)) {
					spip_log("echec netpbm-jpg sur $vignette");
					return;
				}
			} else if ($format == "gif") {
				$giftopnm_command = str_replace("pnmscale", "giftopnm", _PNMSCALE_COMMAND);
				exec("$giftopnm_command $image | "._PNMSCALE_COMMAND." -width $destWidth | $pnmtojpeg_command > $vignette");
				if (!@file_exists($vignette)) {
					spip_log("echec netpbm-gif sur $vignette");
					return;
				}
			} else if ($format == "png") {
				$pngtopnm_command = str_replace("pnmscale", "pngtopnm", _PNMSCALE_COMMAND);
				exec("$pngtopnm_command $image | "._PNMSCALE_COMMAND." -width $destWidth | $pnmtojpeg_command > $vignette");
				if (!@file_exists($vignette)) {
					spip_log("echec netpbm-png sur $vignette");
					return;
				}
			}
		}
		// gd ou gd2
		else if ($process == 'gd1' OR $process == 'gd2') {
			if (_IMG_GD_MAX_PIXELS && $srcWidth*$srcHeight>_IMG_GD_MAX_PIXELS){
				spip_log("vignette gd1/gd2 impossible : ".$srcWidth*$srcHeight."pixels");
				return;
			}

			// Choisir le format destination
			// - on sauve de preference en JPEG (meilleure compression)
			// - pour le GIF : les GD recentes peuvent le lire mais pas l'ecrire
			# bug : gd_formats contient la liste des fichiers qu'on sait *lire*,
			# pas *ecrire*
			$gd_formats = $GLOBALS['meta']["gd_formats"];
			foreach ($formats_sortie as $fmt) {
				if (ereg($fmt, $gd_formats)) {
					if ($format <> "gif" OR function_exists('ImageGif'))
						$destFormat = $fmt;
					break;
				}
			}

			if (!$destFormat) {
				spip_log("pas de format pour $image");
				return;
			}

			# calcul de memoire desactive car pas fiable
			#$memoryNeeded = round(($srcsize[0] * $srcsize[1] * $srcsize['bits'] * $srcsize['channels'] / 8 + 65536) * 1.65); 
			#spip_log("GD : memory need $memoryNeeded");
			#if (function_exists('memory_get_usage'))
				#spip_log("GD : memory usage ".memory_get_usage());
			#spip_log("GD : memory_limit ".ini_get('memory_limit'));
			#if (function_exists('memory_get_usage') && memory_get_usage() + $memoryNeeded > (integer) ini_get('memory_limit') * 1048576){
			#	spip_log("vignette gd1/gd2 impossible : memoire insuffisante $memoryNeeded necessaire");
			#	return;
			#}
			#else
			{
				$srcImage = recupere_image_originale($image, $format);
				if (!$srcImage) { 
					spip_log("echec gd1/gd2"); 
					return; 
				} 

				// Initialisation de l'image destination 
 				if ($process == 'gd2' AND $destFormat != "gif") 
					$destImage = ImageCreateTrueColor($destWidth, $destHeight); 
				if (!$destImage) 
					$destImage = ImageCreate($destWidth, $destHeight); 

				// Recopie de l'image d'origine avec adaptation de la taille 
				$ok = false; 
				if (($process == 'gd2') AND function_exists('ImageCopyResampled')) { 
					if ($format == "gif") { 
						// Si un GIF est transparent, 
						// fabriquer un PNG transparent  
						$transp = imagecolortransparent($srcImage); 
						if ($transp > 0) $destFormat = "png"; 
					}
					if ($destFormat == "png") { 
						// Conserver la transparence 
						if (function_exists("imageAntiAlias")) imageAntiAlias($destImage,true); 
						@imagealphablending($destImage, false); 
						@imagesavealpha($destImage,true); 
					}
					$ok = @ImageCopyResampled($destImage, $srcImage, 0, 0, 0, 0, $destWidth, $destHeight, $srcWidth, $srcHeight);
				}
				if (!$ok)
					$ok = ImageCopyResized($destImage, $srcImage, 0, 0, 0, 0, $destWidth, $destHeight, $srcWidth, $srcHeight);
			}

			// Sauvegarde de l'image destination
			$vignette = "$destination.$destFormat";
			$format = $destFormat;
			if ($destFormat == "jpg")
				ImageJPEG($destImage, $vignette, 85);
			else if ($destFormat == "gif")
				ImageGIF($destImage, $vignette);
			else if ($destFormat == "png")
				ImagePNG($destImage, $vignette);

			if ($srcImage)
				ImageDestroy($srcImage);
			ImageDestroy($destImage);
		}
	}

	$size = @getimagesize($vignette);
	
	// Gaffe: en safe mode, pas d'acces a la vignette,
	// donc risque de balancer "width='0'", ce qui masque l'image sous MSIE
	if ($size[0] < 1) $size[0] = $destWidth;
	if ($size[1] < 1) $size[1] = $destHeight;
	
	$retour['width'] = $largeur = $size[0];
	$retour['height'] = $hauteur = $size[1];
	
	$retour['fichier'] = $vignette;
	$retour['format'] = $format;
	$retour['date'] = @filemtime($vignette);
	

	// renvoyer l'image
	return $retour;
}

// http://doc.spip.org/@recupere_image_originale
function recupere_image_originale($image, $format)
{
	if ($format == "jpg") { 
		return function_exists('ImageCreateFromJPEG') ? @ImageCreateFromJPEG($image) : '';
	}
	else if ($format == "gif"){ 
		return function_exists('ImageCreateFromGIF') ? @ImageCreateFromGIF($image) : '';
	}
	else if ($format == "png"){ 
		return function_exists('ImageCreateFromPNG') ? @ImageCreateFromPNG($image) : '' ;
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
		if (!$srcHeight AND $srcsize = @getimagesize($logo)) {
			$srcHeight = $srcsize[1];
			$hauteur_img[$mem] = $srcHeight;
		}
	}
	return array($srcHeight, $srcWidth);
}

//
// Reduire la taille d'un logo
// [(#LOGO_ARTICLE||reduire_image{100,60})]
//

// Cette fonction accepte en entree un nom de fichier ou un tag <img ...>

// http://doc.spip.org/@reduire_image_logo
function reduire_image_logo($img, $taille = -1, $taille_y = -1, $cherche_image=true) {

	// Determiner la taille x,y maxi
	if ($taille == -1) {
		$taille = $GLOBALS['meta']['taille_preview'];
		if (!$taille)
			$taille = 150;
	}
	if ($taille_y == -1)
		$taille_y = $taille;

	if ($taille == 0 AND $taille_y > 0)
		$taille = 100000; # {0,300} -> c'est 300 qui compte
	else
	if ($taille > 0 AND $taille_y == 0)
		$taille_y = 100000; # {300,0} -> c'est 300 qui compte
	else if ($taille == 0 AND $taille_y == 0)
		return '';

	// recuperer le nom du fichier
	if ($src = extraire_attribut($img, 'src')) {
		$logo = $src;
		// Cas d'une reduction HTML, ne pas agrandir
		$w_html = extraire_attribut($img, 'width');
		$h_html = extraire_attribut($img, 'height');
		if ($w_html AND $h_html
		AND $w_html<=$taille
		AND $h_html<=$taille_y)
			return $img;
	} else
		$logo = $img;
	if (!$logo) return '';

	// Si c'est une image distante, la recuperer
	include_spip('inc/distant');

	if (!$local = copie_locale($logo)) {
		spip_log("pas de version locale de $logo");
		// on peut resizer en mode html si on dispose des elements
		if ($srcw = extraire_attribut($img, 'width')
		AND $srch = extraire_attribut($img, 'height')) {
			include_spip('inc/chercher_logo');
			list($w,$h) = image_ratio($srcw, $srch, $taille, $taille_y);
			$img = inserer_attribut(
				inserer_attribut($img, 'width', $w),
				'height', $h);
			return $img;
		}

		// la on n'a pas d'infos sur l'image source... on refile le truc a css
		// sous la forme style='max-width: NNpx;'
		return inserer_attribut($img, 'style',
			"max-width: ${taille}px; max-height: ${taille_y}px");
	}
	$logo = $local;

	$attributs = '';

	// preserver le name='...' et le mettre en alt le cas echant
	if ($name = extraire_attribut($img, 'name')) {
		$attributs .= ' name="'.entites_html($name).'"'; 
		$attributs_alt = ' alt="'.entites_html($name).'"'; 
	}
	// si un alt (meme vide) etait present, le recuperer
	// si pas de alt, en mettre un vide (compliance W3)
	$alt = extraire_attribut($img, 'alt');
	$attributs_alt = ' alt="'.entites_html($alt).'"'; 

	$attributs .= $attributs_alt;

	$style = extraire_attribut($img, 'style');

	// attributs deprecies. Transformer en CSS
	if ($espace = extraire_attribut($img, 'hspace'))
		$style .= "margin:${espace}px;";

	// Conserver la class et le title
	if ($class = extraire_attribut($img, 'class'))
		$attributs .=  " class='$class'";
	if (strlen($title = extraire_attribut($img, 'title')))
		$attributs .=  ' title="'.texte_backend($title).'"';

	// attribut deprecie mais equivalent CSS pas clair
	if ($align = extraire_attribut($img, 'align'))
		$attributs .= " align='$align'";

	$style = trim(
		preg_replace(',(^|[[:space:]])(width|height):[^;]+;?,ims', '', $style)
	);
	if ($style)
		$attributs .= " style='$style'";

	if (preg_match(",(.*)\.(jpg|gif|png)$,", $logo, $regs)) {
		$i = array(dirname($regs[1]),basename($regs[1]),$regs[2]);
		if ($cherche_image)
			$i = cherche_image_nommee($regs[1], array($regs[2]));
		if ($i) {
			list(,$nom,$format) = $i;
			include_spip('inc/chercher_logo');
			return ratio_image($logo, $nom, $format, $taille, $taille_y, $attributs);
		}
	}
	else
		# SVG par exemple ?
		return "<img src='$logo$date'$attributs />";
}


// Calculer le ratio
// http://doc.spip.org/@image_ratio
function image_ratio ($srcWidth, $srcHeight, $maxWidth, $maxHeight) {
	$ratioWidth = $srcWidth/$maxWidth;
	$ratioHeight = $srcHeight/$maxHeight;

	if ($ratioWidth <=1 AND $ratioHeight <=1) {
		$destWidth = $srcWidth;
		$destHeight = $srcHeight;
	} else if ($ratioWidth < $ratioHeight) {
		$destWidth = $srcWidth/$ratioHeight;
		$destHeight = $maxHeight;
	}
	else {
		$destWidth = $maxWidth;
		$destHeight = $srcHeight/$ratioWidth;
	}
	return array (ceil($destWidth), ceil($destHeight),
		max($ratioWidth,$ratioHeight));
}

// http://doc.spip.org/@ratio_image
function ratio_image($logo, $nom, $format, $taille, $taille_y, $attributs) {
	if (!$taille_origine = @getimagesize($logo)) return '';
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
	return "<img src='$logo$date' width='".$destWidth."' height='".$destHeight."'$attributs />";
}

?>
