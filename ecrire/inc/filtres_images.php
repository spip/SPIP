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
include_spip('inc/filtres'); // par precaution

// http://doc.spip.org/@cherche_image_nommee
function cherche_image_nommee($nom, $formats = array ('gif', 'jpg', 'png')) {

	if (strncmp(_DIR_IMG, $nom,$n=strlen(_DIR_IMG))==0) {
		$nom = substr($nom,$n);
	} else 	if (strncmp(_DIR_IMG_PACK, $nom,$n=strlen(_DIR_IMG_PACK))==0) {
		$nom = substr($nom,$n);
	} else if (strncmp(_DIR_IMG_ICONE_DIST, $nom,$nstrlen(_DIR_IMG_ICONES_DIST))==0) {
		$nom = substr($nom,$n);
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

// Fonctions de traitement d'image
// uniquement pour GD2
// http://doc.spip.org/@image_valeurs_trans
function image_valeurs_trans($img, $effet, $forcer_format = false, $fonction_creation = NULL) {
	static $images_recalcul = array();
	if (strlen($img)==0) return false;
	
	$source = trim(extraire_attribut($img, 'src'));
	if (($p=strpos($source,'?'))!==FALSE)
		$source=substr($source,0,$p);
	if (strlen($source) < 1){
		$source = $img;
		$img = "<img src='$source' />";
	}

	// les protocoles web prennent au moins 3 lettres
	if (preg_match(';^(\w{3,7}://);', $source)){
		include_spip("inc/distant");
		$fichier = copie_locale($source);
		if (!$fichier) return "";
	} else 	$fichier = $source;

	$terminaison_dest = "";
	if (preg_match(",^(?>.*)(?<=\.(gif|jpg|png)),", $fichier, $regs)) {
		$terminaison = $regs[1];
		$terminaison_dest = $terminaison;
		
		if ($terminaison == "gif") $terminaison_dest = "png";
	}
	if ($forcer_format!==false) $terminaison_dest = $forcer_format;

	if (!$terminaison_dest) return false;

	$term_fonction = $terminaison;
	if ($term_fonction == "jpg") $term_fonction = "jpeg";

	$nom_fichier = substr($fichier, 0, strlen($fichier) - 4);
	$fichier_dest = $nom_fichier;
	list ($ret["hauteur"],$ret["largeur"]) = taille_image($img);
	// cas general :
	// on a un dossier cache commun et un nom de fichier qui varie avec l'effet
	// cas particulier de reduire :
	// un cache par dimension, et le nom de fichier est conserve, suffixe par la dimension aussi
	$cache = "cache-gd2";
	if (substr($effet,0,7)=='reduire') {
		list(,$maxWidth,$maxHeight) = explode('-',$effet);
		list ($destWidth,$destHeight) = image_ratio($ret['largeur'], $ret['hauteur'], $maxWidth, $maxHeight);
		$ret['largeur_dest'] = $destWidth;
		$ret['hauteur_dest'] = $destHeight;
		$effet = "L{$destWidth}xH$destHeight";
		$cache = "cache-vignettes";
		$fichier_dest = basename($fichier_dest);
		if (($ret['largeur']<=$maxWidth)&&($ret['hauteur']<=$maxHeight))
			// on garde la terminaison initiale car image simplement copiee, et on ne change pas son nom
			$terminaison_dest = $terminaison;
		else
			$fichier_dest .= '-'.substr(md5("$fichier_dest-$effet"),0,5);
		$cache = sous_repertoire(_DIR_VAR, $cache);
		$cache = sous_repertoire($cache, $effet);
		# cherche un cache existant
		/*foreach (array('gif','jpg','png') as $fmt)
			if (@file_exists($cache . $fichier_dest . '.' . $fmt)) {
				$terminaison_dest = $fmt;
			}*/
	}
	else 	{
		$fichier_dest = md5("$fichier_dest-$effet");
		$cache = sous_repertoire(_DIR_VAR, $cache);
	}
	
	$fichier_dest = $cache . $fichier_dest . "." .$terminaison_dest;
	
	$GLOBALS["images_calculees"][] =  $fichier_dest;
	
	
	$creer = true;
	// si recalcul des images demande, recalculer chaque image une fois
	if ($GLOBALS['var_images'] && !isset($images_recalcul[$fichier_dest])){
		$images_recalcul[$fichier_dest] = true;
	}
	else {
		$date_src = 0;
		$date_dest = 0;
		if (@file_exists($f = $fichier) OR @file_exists($f = "$fichier.src"))
			$date_src = @filemtime($f);
		if (@file_exists($f = $fichier_dest) OR @file_exists($f = "$fichier_dest.src"))
			$date_dest = @filemtime($f);
		# il peut y avoir egalite de date si l'on compare deux .src crees dans la foulee
		if ( $date_src <= $date_dest ){
			$creer = false;
		}
	}
	if ($creer) {
		if (!file_exists($fichier)) {
			if (!file_exists("$fichier.src")) {
				spip_log("Image absente : $fichier");
				return false;
			}
			# on reconstruit l'image source absente a partir de la chaine des .src
			reconstruire_image_intermediaire($fichier);
		}
	}
	
	$ret["fichier"] = $fichier;
	$ret["fonction_imagecreatefrom"] = "imagecreatefrom".$term_fonction;
	$ret["fonction_image"] = "image_image".$terminaison_dest;
	$ret["fichier_dest"] = $fichier_dest;
	$ret["format_source"] = $terminaison;
	$ret["format_dest"] = $terminaison_dest;
	$ret["date_src"] = $date_src;
	$ret["creer"] = $creer;
	$ret["class"] = extraire_attribut($img, 'class');
	$ret["alt"] = extraire_attribut($img, 'alt');
	$ret["style"] = extraire_attribut($img, 'style');
	$ret["tag"] = $img;
	if ($fonction_creation){
		$ret["reconstruction"] = $fonction_creation;
		# ecrire ici comment creer le fichier, car il est pas sur qu'on l'ecrira reelement 
		# cas de image_reduire qui finalement ne reduit pas l'image source
		# ca evite d'essayer de le creer au prochain hit si il n'est pas la
		#ecrire_fichier($ret['fichier_dest'].'.src',serialize($ret),true);
	}
	return $ret;
}

// http://doc.spip.org/@image_imagepng
function image_imagepng($img,$fichier) {
	$tmp = $fichier.".tmp";
	$ret = imagepng($img,$tmp);
	spip_unlink($fichier); // le fichier peut deja exister
	@rename($tmp, $fichier);
	return $ret;
}

// http://doc.spip.org/@image_imagegif
function image_imagegif($img,$fichier) {
	$tmp = $fichier.".tmp";
	$ret = imagegif($img,$tmp);
	spip_unlink($fichier); // le fichier peut deja exister
	@rename($tmp, $fichier);
	return $ret;
}
// http://doc.spip.org/@image_imagejpg
function image_imagejpg($img,$fichier,$qualite=85) {
	$tmp = $fichier.".tmp";
	$ret = imagejpeg($img,$tmp, $qualite);
	spip_unlink($fichier); // le fichier peut deja exister
	@rename($tmp, $fichier);
	return $ret;
}

// $qualite est utilise pour la qualite de compression des jpeg
// http://doc.spip.org/@image_gd_output
function image_gd_output($img,$valeurs, $qualite=85){
	$fonction = "image_image".$valeurs['format_dest'];
	$ret = false;
	#un flag pour reperer les images gravees
	$lock = file_exists($valeurs['fichier_dest']) AND !file_exists($valeurs['fichier_dest'].'.src');
	if (
	     function_exists($fonction) 
			  && ($ret = $fonction($img,$valeurs['fichier_dest'],$qualite)) # on a reussi a creer l'image
			  && isset($valeurs['reconstruction']) # et on sait comment la resonctruire le cas echeant
			  && !$lock
	  )
		if (file_exists($valeurs['fichier_dest'])) ecrire_fichier($valeurs['fichier_dest'].'.src',serialize($valeurs),true);
	return $ret;
}

// http://doc.spip.org/@reconstruire_image_intermediaire
function reconstruire_image_intermediaire($fichier_manquant){
	$reconstruire = array();
	$fichier = $fichier_manquant;
	while (
		!file_exists($fichier)
		AND lire_fichier($src = "$fichier.src",$source)
		AND $valeurs=unserialize($source)
    AND ($fichier = $valeurs['fichier']) # l'origine est connue (on ne verifie pas son existence, qu'importe ...)
    ) {
			spip_unlink($src); // si jamais on a un timeout pendant la reconstruction, elle se fera naturellement au hit suivant
			$reconstruire[] = $valeurs['reconstruction'];
   }
	while (count($reconstruire)){
		$r = array_pop($reconstruire);
		$fonction = $r[0];
		$args = $r[1];
		call_user_func_array($fonction, $args);
	}
	// cette image intermediaire est commune a plusieurs series de filtre, il faut la conserver
	// mais l'on peut nettoyer les miettes de sa creation
	ramasse_miettes($fichier_manquant);
}

// http://doc.spip.org/@ramasse_miettes
function ramasse_miettes($fichier){
	if (!lire_fichier($src = "$fichier.src",$source) 
		OR !$valeurs=unserialize($source)) return;
	spip_unlink($src); # on supprime la reference a sa source pour marquer cette image comme non intermediaire
	while (
	     ($fichier = $valeurs['fichier']) # l'origine est connue (on ne verifie pas son existence, qu'importe ...)
		AND (substr($fichier,0,strlen(_DIR_VAR))==_DIR_VAR) # et est dans local
		AND (lire_fichier($src = "$fichier.src",$source)) # le fichier a une source connue (c'est donc une image calculee intermediaire)
		AND ($valeurs=unserialize($source))  # et valide
		) {
		# on efface le fichier
		spip_unlink($fichier);
		# mais laisse le .src qui permet de savoir comment reconstruire l'image si besoin
		#spip_unlink($src);
	}
}

// http://doc.spip.org/@image_graver
function image_graver($img){
	$fichier = extraire_attribut($img, 'src');
	if (($p=strpos($fichier,'?'))!==FALSE)
		$fichier=substr($fichier,0,$p);
	if (strlen($fichier) < 1)
		$fichier = $img;
	# si jamais le fichier final n'a pas ete calcule car suppose temporaire
	if (!file_exists($fichier)) 
		reconstruire_image_intermediaire($fichier);
	ramasse_miettes($fichier);
	return $img; // on ne change rien
}

// Transforme une image a palette indexee (256 couleurs max) en "vraies" couleurs RGB
// http://doc.spip.org/@imagepalettetotruecolor
 function imagepalettetotruecolor(&$img) {
	if (!imageistruecolor($img) AND function_exists(imagecreatetruecolor)) {
		$w = imagesx($img);
		$h = imagesy($img);
		$img1 = imagecreatetruecolor($w,$h);
		imagecopy($img1,$img,0,0,0,0,$w,$h);
		$img = $img1;
	}
}

// http://doc.spip.org/@image_tag_changer_taille
function image_tag_changer_taille($tag,$width,$height,$style=false){
	if ($style===false) $style = extraire_attribut($tag,'style');
	// enlever le width et height du style
	$style = preg_replace(",(^|;)\s*(width|height)\s*:\s*[^;]+,ims","",$style);
	if ($style AND $style{0}==';') $style=substr($style,1);
	// mettre des attributs de width et height sur les images, 
	// ca accelere le rendu du navigateur
	// ca permet aux navigateurs de reserver la bonne taille 
	// quand on a desactive l'affichage des images.
	$tag = inserer_attribut($tag,'width',$width);
	$tag = inserer_attribut($tag,'height',$height);
	$style = "height:".$height."px;width:".$width."px;".$style;
	// attributs deprecies. Transformer en CSS
	if ($espace = extraire_attribut($tag, 'hspace')){
		$style = "margin:${espace}px;".$style;
		$tag = inserer_attribut($tag,'hspace','');
	}
	$tag = inserer_attribut($tag,'style',$style);
	return $tag;
}

// function d'ecriture du tag img en sortie des filtre image
// reprend le tag initial et surcharge les tags modifies
// http://doc.spip.org/@image_ecrire_tag
function image_ecrire_tag($valeurs,$surcharge){
	$tag = 	str_replace(">","/>",str_replace("/>",">",$valeurs['tag'])); // fermer les tags img pas bien fermes;
	
	// le style
	$style = $valeurs['style'];
	if (isset($surcharge['style'])){
		$style = $surcharge['style'];
		unset($surcharge['style']);
	}
	
	// traiter specifiquement la largeur et la hauteur
	$width = $valeurs['largeur'];
	if (isset($surcharge['width'])){
		$width = $surcharge['width'];
		unset($surcharge['width']);
	}
	$height = $valeurs['hauteur'];
	if (isset($surcharge['height'])){
		$height = $surcharge['height'];
		unset($surcharge['height']);
	}

	$tag = image_tag_changer_taille($tag,$width,$height,$style);
	// traiter specifiquement le src qui peut etre repris dans un onmouseout
	// on remplace toute les ref a src dans le tag
	$src = extraire_attribut($tag,'src');
	if (isset($surcharge['src'])){
		$tag = str_replace($src,$surcharge['src'],$tag);
		$src = $surcharge['src'];
		unset($surcharge['src']);
	}
	
	// regarder la class pour gerer le 'format_png' en fonction du format de l'image
	// (et le remettre sinon)
	$class = $valeurs['class'];
	if (isset($surcharge['class'])){
		$class = $surcharge['class'];
		unset($surcharge['class']);
	}
	$is_png = preg_match(',[.]png($|\?),i',$src);
	$p = strpos($class,'format_png');
	if ($is_png && $p===FALSE)
		$class .= " format_png";
	if (!$is_png && $p!==FALSE)
		$class = preg_replace(",\s*format_png,","",$class);
	if(strlen($class))
		$tag = inserer_attribut($tag,'class',$class);

	if (count($surcharge))
		foreach($surcharge as $attribut=>$valeur)
			$tag = inserer_attribut($tag,$attribut,$valeur);

	return $tag;
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

// http://doc.spip.org/@image_creer_vignette
function image_creer_vignette($valeurs, $maxWidth, $maxHeight, $process='AUTO', $force=false, $test_cache_only = false) {
	// ordre de preference des formats graphiques pour creer les vignettes
	// le premier format disponible, selon la methode demandee, est utilise
	$image = $valeurs['fichier'];
	$format = $valeurs['format_source'];
	$destdir = dirname($valeurs['fichier_dest']);
	$destfile = basename($valeurs['fichier_dest'],".".$valeurs["format_dest"]);
	
	$format_sortie = $valeurs['format_dest'];
	
	// liste des formats qu'on sait lire
	$img = isset($GLOBALS['meta']['formats_graphiques'])
	  ? (strpos($GLOBALS['meta']['formats_graphiques'], $format)!==false)
	  : false;

	// si le doc n'est pas une image, refuser
	if (!$force AND !$img) return;
	$destination = "$destdir/$destfile";

	// chercher un cache
	$vignette = '';
	if ($test_cache_only AND !$vignette) return;

	// utiliser le cache ?
	if (!$test_cache_only)
	if ($force OR !$vignette OR (@filemtime($vignette) < @filemtime($image))) {

		$creation = true;
		// calculer la taille
		if (($srcWidth=$valeurs['largeur']) && ($srcHeight=$valeurs['hauteur'])){
			if (!($destWidth=$valeurs['largeur_dest']) || !($destHeight=$valeurs['hauteur_dest']))
				list ($destWidth,$destHeight) = image_ratio($valeurs['largeur'], $valeurs['hauteur'], $maxWidth, $maxHeight);
		}
		elseif ($process == 'convert' OR $process == 'imagick') {
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
			$vignette = $destination.'.'.$format;
			@copy($image, $vignette);
		}
		// imagemagick en ligne de commande
		else if ($process == 'convert') {
			define('_CONVERT_COMMAND', 'convert');
			define ('_RESIZE_COMMAND', _CONVERT_COMMAND.' -quality 85 -resize %xx%y! %src %dest');
			$vignette = $destination.".".$format_sortie;
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
			$vignette = "$destination.".$format_sortie;
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
			$vignette = $destination.".".$format_sortie;
			$pnmtojpeg_command = str_replace("pnmscale", "pnmtojpeg", _PNMSCALE_COMMAND);
			if ($format == "jpg") {
				
				$jpegtopnm_command = str_replace("pnmscale", "jpegtopnm", _PNMSCALE_COMMAND);
				exec("$jpegtopnm_command $image | "._PNMSCALE_COMMAND." -width $destWidth | $pnmtojpeg_command > $vignette");
				if (!($s = @filesize($vignette)))
					spip_unlink($vignette);
				if (!@file_exists($vignette)) {
					spip_log("echec netpbm-jpg sur $vignette");
					return;
				}
			} else if ($format == "gif") {
				$giftopnm_command = str_replace("pnmscale", "giftopnm", _PNMSCALE_COMMAND);
				exec("$giftopnm_command $image | "._PNMSCALE_COMMAND." -width $destWidth | $pnmtojpeg_command > $vignette");
				if (!($s = @filesize($vignette)))
					spip_unlink($vignette);
				if (!@file_exists($vignette)) {
					spip_log("echec netpbm-gif sur $vignette");
					return;
				}
			} else if ($format == "png") {
				$pngtopnm_command = str_replace("pnmscale", "pngtopnm", _PNMSCALE_COMMAND);
				exec("$pngtopnm_command $image | "._PNMSCALE_COMMAND." -width $destWidth | $pnmtojpeg_command > $vignette");
				if (!($s = @filesize($vignette)))
					spip_unlink($vignette);
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
			$destFormat = $format_sortie;
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
				$fonction_imagecreatefrom = $valeurs['fonction_imagecreatefrom'];
				if (!function_exists($fonction_imagecreatefrom))
					return '';
				$srcImage = $fonction_imagecreatefrom($image);
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
			$valeurs['fichier_dest'] = $vignette = "$destination.$destFormat";
			$valeurs['format_dest'] = $format = $destFormat;
			image_gd_output($destImage,$valeurs);

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

// Calculer le ratio ajuste sur la plus petite dimension
// http://doc.spip.org/@ratio_passe_partout
function ratio_passe_partout ($srcWidth, $srcHeight, $maxWidth, $maxHeight) {
	$ratioWidth = $srcWidth/$maxWidth;
	$ratioHeight = $srcHeight/$maxHeight;

	if ($ratioWidth <=1 AND $ratioHeight <=1) {
		$destWidth = $srcWidth;
		$destHeight = $srcHeight;
	} else if ($ratioWidth > $ratioHeight) {
		$destWidth = $srcWidth/$ratioHeight;
		$destHeight = $maxHeight;
	}
	else {
		$destWidth = $maxWidth;
		$destHeight = $srcHeight/$ratioWidth;
	}
	return array (floor($destWidth), floor($destHeight),
		min($ratioWidth,$ratioHeight));
}

// http://doc.spip.org/@image_passe_partout
function image_passe_partout($img,$taille_x = -1, $taille_y = -1,$force = false,$cherche_image=false,$process='AUTO'){
	list ($hauteur,$largeur) = taille_image($img);
	if ($taille_x == -1)
		$taille_x = isset($GLOBALS['meta']['taille_preview'])?$GLOBALS['meta']['taille_preview']:150;
	if ($taille_y == -1)
		$taille_y = $taille;

	if ($taille_x == 0 AND $taille_y > 0)
		$taille_x = 1; # {0,300} -> c'est 300 qui compte
	elseif ($taille_x > 0 AND $taille_y == 0)
		$taille_y = 1; # {300,0} -> c'est 300 qui compte
	elseif ($taille_x == 0 AND $taille_y == 0)
		return '';
	
	list($destWidth,$destHeight,$ratio) = ratio_passe_partout($largeur,$hauteur,$taille_x,$taille_y);
	$fonction = array('image_passe_partout', func_get_args());
	return process_image_reduire($fonction,$img,$taille_x,$taille_y,$force,$cherche_image,$process);
}

// http://doc.spip.org/@image_reduire
function image_reduire($img, $taille = -1, $taille_y = -1, $force=false, $cherche_image=false, $process='AUTO') {
	// Determiner la taille x,y maxi
	// prendre le reglage de previsu par defaut
	if ($taille == -1)
		$taille = isset($GLOBALS['meta']['taille_preview'])?$GLOBALS['meta']['taille_preview']:150;
	if ($taille_y == -1)
		$taille_y = $taille;

	if ($taille == 0 AND $taille_y > 0)
		$taille = 100000; # {0,300} -> c'est 300 qui compte
	elseif ($taille > 0 AND $taille_y == 0)
		$taille_y = 100000; # {300,0} -> c'est 300 qui compte
	elseif ($taille == 0 AND $taille_y == 0)
		return '';

	$fonction = array('image_reduire', func_get_args());
	return process_image_reduire($fonction,$img,$taille,$taille_y,$force,$cherche_image,$process);
}

// http://doc.spip.org/@process_image_reduire
function process_image_reduire($fonction,$img,$taille,$taille_y,$force,$cherche_image,$process){
	$image = false;
	if (($process == 'AUTO') AND isset($GLOBALS['meta']['image_process']))
		$process = $GLOBALS['meta']['image_process'];
	# determiner le format de sortie
	$format_sortie = false; // le choix par defaut sera bon
	if ($process == "netpbm") $format_sortie = "jpg";
	else if ($process == 'gd1' OR $process == 'gd2') {
		$image = image_valeurs_trans($img, "reduire-{$taille}-{$taille_y}",$format_sortie,$fonction);
		// on verifie que l'extension choisie est bonne (en principe oui)
		$gd_formats = explode(',',$GLOBALS['meta']["gd_formats"]);
		if (!in_array($image['format_dest'],$gd_formats)
		  OR ($image['format_dest']=='gif' AND !function_exists('ImageGif'))
		  ) {
			if ($image['format_source'] == 'jpg')
				$formats_sortie = array('jpg','png','gif');
			else // les gif sont passes en png preferentiellement pour etre homogene aux autres filtres images
				$formats_sortie = array('png','jpg','gif');
			// Choisir le format destination
			// - on sauve de preference en JPEG (meilleure compression)
			// - pour le GIF : les GD recentes peuvent le lire mais pas l'ecrire
			# bug : gd_formats contient la liste des fichiers qu'on sait *lire*,
			# pas *ecrire*
			$format_sortie = "";
			foreach ($formats_sortie as $fmt) {
				if (in_array($fmt, $gd_formats)) {
					if ($fmt <> "gif" OR function_exists('ImageGif'))
						$format_sortie = $fmt;
					break;
				}
			}
			$image = false;
		}
	}
	if (!$image)
		$image = image_valeurs_trans($img, "reduire-{$taille}-{$taille_y}",$format_sortie,$fonction);

	if (!$image){
		spip_log("image_reduire_src:pas de version locale de $img");
		// on peut resizer en mode html si on dispose des elements
		if ($srcw = extraire_attribut($img, 'width')
		AND $srch = extraire_attribut($img, 'height')) {
			list($w,$h) = image_ratio($srcw, $srch, $taille, $taille_y);
			return image_tag_changer_taille($img,$w,$h);
		}
		// la on n'a pas d'infos sur l'image source... on refile le truc a css
		// sous la forme style='max-width: NNpx;'
		return inserer_attribut($img, 'style',
			"max-width: ${taille}px; max-height: ${taille_y}px");
	}
	
	// si l'image est plus petite que la cible retourner une copie cachee de l'image
	if (($image['largeur']<=$taille)&&($image['hauteur']<=$taille_y)){
		if ($image['creer']){
			@copy($image['fichier'], $image['fichier_dest']);
		}
		return image_ecrire_tag($image,array('src'=>$image['fichier_dest']));
	}
	
	if ($image['creer']==false && !$force)
		return image_ecrire_tag($image,array('src'=>$image['fichier_dest'],'width'=>$image['largeur_dest'],'height'=>$image['hauteur_dest']));

	if ($cherche_image){
		$cherche = cherche_image_nommee(substr($image['fichier'],0,-4), array($image["format_source"]));
		if (!$cherche) return $img;
		//list($chemin,$nom,$format) = $cherche;
	}
	if (in_array($image["format_source"],array('jpg','gif','png'))){
		$destWidth = $image['largeur_dest'];
		$destHeight = $image['hauteur_dest'];
		$logo = $image['fichier'];
		$date = $image["date_src"];
		$preview = image_creer_vignette($image, $taille, $taille_y,$process,$force);
		if ($preview) {
			$logo = $preview['fichier'];
			$destWidth = $preview['width'];
			$destHeight = $preview['height'];
			$date = $preview['date'];
		}
		// dans l'espace prive mettre un timestamp sur l'adresse 
		// de l'image, de facon a tromper le cache du navigateur
		// quand on fait supprimer/reuploader un logo
		// (pas de filemtime si SAFE MODE)
		$date = test_espace_prive() ? ('?date='.$date) : '';
		return image_ecrire_tag($image,array('src'=>"$logo$date",'width'=>$destWidth,'height'=>$destHeight));
	}
	else
		# SVG par exemple ? BMP, tiff ... les redacteurs osent tout!
		return $img;
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
		image_gd_output($im_,$image);
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
	if (strpos($position,'left')!==FALSE)
		$offset_width=0;
	elseif (strpos($position,'right')!==FALSE)
		$offset_width=$offset_width;
	else
		$offset_width=intval(ceil($offset_width/2));

	if (strpos($position,'top')!==FALSE)
		$offset_height=0;
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

	$pos = md5(serialize($variable));

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
function image_aplatir($im, $format='jpg', $coul='000000', $qualite=NULL)
{
	if ($qualite===NULL){
		if ($format=='jpg') $qualite=85;
		elseif ($format=='png') $qualite=0;
		else $qualite=128;
	}
	$fonction = array('image_aplatir', func_get_args());
	$image = image_valeurs_trans($im, "aplatir-$format-$coul-$qualite", $format, $fonction);

	if (!$image) return("");

	include_spip("filtres");
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
			if (function_exists("imageAntiAlias")) imageAntiAlias($im_,true); 
			@imagealphablending($im_, false); 
			@imagesavealpha($im_,true); 
			@ImageCopyResampled($im_, $im, 0, 0, 0, 0, $x_i, $y_i, $x_i, $y_i);
			imagedestroy($im);
			$im = $im_;
		}
		// allouer la couleur de fond
		$color_t = ImageColorAllocate( $im_, $dr, $dv, $db);
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
				}
				else if ($a == 0) { // Limiter calculs
					$r = $dr;
					$g = $dv;
					$b = $db;
				} else {
					$r = round($a * $r + $dr * (1-$a));
					$g = round($a * $g + $dv * (1-$a));
					$b = round($a * $b + $db * (1-$a));
				}
						
				$color = ImageColorAllocate( $im_, $r, $g, $b);
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
			// produire le resultat
			$image["fonction_image"]($im, "$dest");
		}
		image_gd_output($im_, $image, $qualite);
		imagedestroy($im_);
		imagedestroy($im);
	}

	return image_ecrire_tag($image,array('src'=>$dest));
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

				if (eregi("\.je?pg$", $fichier)) $source = imagecreatefromjpeg($fichier);
				if (eregi("\.gif$", $fichier)) $source = imagecreatefromgif($fichier);
				if (eregi("\.png$", $fichier)) $source = imagecreatefrompng($fichier);
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
	substr($font,-4) == ".pfb"
	AND function_exists("imagepstext")) {
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
		$line = preg_replace("/~/", " ", $line);
		$dimensions = imageftbbox($textSize, 0, $font, $line, array());
		$largeur_ligne = $dimensions[2] - $dimensions[0];
		if ($largeur_ligne > $largeur_max) $largeur_max = $largeur_ligne;
	}

	foreach ($lines as $line) {
		$line = preg_replace("/~/", " ", $line);
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
		if (($p = strpos($arg_list[$i], "="))!==FALSE) {
			$nom_variable = substr($arg_list[$i], 0, $p);
			$val_variable = substr($arg_list[$i], $p+1);
		
			$variable["$nom_variable"] = $val_variable;
		}
		
	}

	// Construire requete et nom fichier
	$text = str_replace("&nbsp;", "~", $texte);	
	$text = preg_replace(",(\r|\n)+,ms", " ", $text);
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
	return inserer_attribut("<img src='$image' width='$largeur' height='$hauteur' style='width:".$largeur."px;height:".$hauteur."px;' class='format_png' />", 'alt', $alt);
}

?>
