<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined('_ECRIRE_INC_VERSION')) return;
include_spip('inc/filtres'); // par precaution


/**
 * Transforme une couleur vectorielle R,G,B en hexa
 *
 * @param int $red
 * @param int $green
 * @param int $blue
 * @return string
 */
function _couleur_dec_to_hex($red, $green, $blue) {
	$red = dechex($red);
	$green = dechex($green);
	$blue = dechex($blue);
	
	if (strlen($red) == 1) $red = "0".$red;
	if (strlen($green) == 1) $green = "0".$green;
	if (strlen($blue) == 1) $blue = "0".$blue;
	
	return "$red$green$blue";
}

/**
 * Transforme une couleur hexa en vectorielle R,G,B
 *
 * @param string $couleur
 * @return array
 */
function _couleur_hex_to_dec($couleur) {
	include_spip("inc/filtres_images_mini");
	$couleur = couleur_html_to_hex($couleur);
	$couleur = preg_replace(",^#,","",$couleur);
	$retour["red"] = hexdec(substr($couleur, 0, 2));
	$retour["green"] = hexdec(substr($couleur, 2, 2));
	$retour["blue"] = hexdec(substr($couleur, 4, 2));
	
	return $retour;
}


function statut_effacer_images_temporaires($stat){
	static $statut = false; // par defaut on grave toute les images
	// une constante a utiliser lorsqu'on a des filtres image_xxx qui ne produisent pas des images
	if (defined('_CONSERVER_IMAGES_TEMPORAIRES'))
		$statut = false;
	if ($stat==='get') return $statut;
	$statut = $stat?true:false;
}

// http://doc.spip.org/@cherche_image_nommee
function cherche_image_nommee($nom, $formats = array ('gif', 'jpg', 'png')) {

	if (strncmp(_DIR_IMG, $nom,$n=strlen(_DIR_IMG))==0) {
		$nom = substr($nom,$n);
	} else 	if (strncmp(_DIR_IMG_PACK, $nom,$n=strlen(_DIR_IMG_PACK))==0) {
		$nom = substr($nom,$n);
	} else if (strncmp(_DIR_IMG_ICONE_DIST, $nom,$n=strlen(_DIR_IMG_ICONES_DIST))==0) {
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
function _image_valeurs_trans($img, $effet, $forcer_format = false, $fonction_creation = NULL) {
	static $images_recalcul = array();
	if (strlen($img)==0) return false;

	$source = trim(extraire_attribut($img, 'src'));
	if (strlen($source) < 1){
		$source = $img;
		$img = "<img src='$source' />";
	}

	// les protocoles web prennent au moins 3 lettres
	if (preg_match(';^(\w{3,7}://);', $source)){
		include_spip('inc/distant');
		$fichier = _DIR_RACINE . copie_locale($source);
		if (!$fichier) return "";
	}	else {
		// enlever le timestamp eventuel
		$source=preg_replace(',[?][0-9]+$,','',$source);
		$fichier = $source;
	}

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

	if (@file_exists($f = $fichier)){
		list ($ret["hauteur"],$ret["largeur"]) = taille_image($img);
		$date_src = @filemtime($f);
	}
	elseif (@file_exists($f = "$fichier.src")
		AND lire_fichier($f,$valeurs)
		AND $valeurs=unserialize($valeurs)
		AND isset($valeurs["hauteur_dest"])
		AND isset($valeurs["largeur_dest"])) {
		$ret["hauteur"] = $valeurs["hauteur_dest"];
		$ret["largeur"] = $valeurs["largeur_dest"];
		$date_src = $valeurs["date"];
	}
	// pas de fichier source par la
	else
		return false;

	// pas de taille mesurable
	if (!($ret["hauteur"] OR $ret["largeur"]))
		return false;
		

	// cas general :
	// on a un dossier cache commun et un nom de fichier qui varie avec l'effet
	// cas particulier de reduire :
	// un cache par dimension, et le nom de fichier est conserve, suffixe par la dimension aussi
	$cache = "cache-gd2";
	if (substr($effet,0,7)=='reduire') {
		list(,$maxWidth,$maxHeight) = explode('-',$effet);
		list ($destWidth,$destHeight) = _image_ratio($ret['largeur'], $ret['hauteur'], $maxWidth, $maxHeight);
		$ret['largeur_dest'] = $destWidth;
		$ret['hauteur_dest'] = $destHeight;
		$effet = "L{$destWidth}xH$destHeight";
		$cache = "cache-vignettes";
		$fichier_dest = basename($fichier_dest);
		if (($ret['largeur']<=$maxWidth)&&($ret['hauteur']<=$maxHeight)){
			// on garde la terminaison initiale car image simplement copiee
			// et on postfixe son nom avec un md5 du path
			$terminaison_dest = $terminaison;
			$fichier_dest .= '-'.substr(md5("$fichier"),0,5);
		}
		else
			$fichier_dest .= '-'.substr(md5("$fichier-$effet"),0,5);
		$cache = sous_repertoire(_DIR_VAR, $cache);
		$cache = sous_repertoire($cache, $effet);
		# cherche un cache existant
		/*foreach (array('gif','jpg','png') as $fmt)
			if (@file_exists($cache . $fichier_dest . '.' . $fmt)) {
				$terminaison_dest = $fmt;
			}*/
	}
	else 	{
		$fichier_dest = md5("$fichier-$effet");
		$cache = sous_repertoire(_DIR_VAR, $cache);
	}
	
	$fichier_dest = $cache . $fichier_dest . "." .$terminaison_dest;
	$GLOBALS["images_calculees"][] =  $fichier_dest;
	
	$creer = true;
	// si recalcul des images demande, recalculer chaque image une fois
	if (isset($GLOBALS['var_images']) && $GLOBALS['var_images'] && !isset($images_recalcul[$fichier_dest])){
		$images_recalcul[$fichier_dest] = true;
	}
	else {
		if (@file_exists($f = $fichier_dest)){
			if (filemtime($f)>=$date_src)
				$creer = false;
		}
		else if (@file_exists($f = "$fichier_dest.src")
		  AND lire_fichier($f,$valeurs)
		  AND $valeurs=unserialize($valeurs)
			AND $valeurs["date"]>=$date_src)
				$creer = false;
	}
	if ($creer) {
		if (!@file_exists($fichier)) {
			if (!@file_exists("$fichier.src")) {
				spip_log("Image absente : $fichier");
				return false;
			}
			# on reconstruit l'image source absente a partir de la chaine des .src
			reconstruire_image_intermediaire($fichier);
		}
	}
	// todo: si une image png est nommee .jpg, le reconnaitre avec le bon $f
	$f = "imagecreatefrom".$term_fonction;
	if (!function_exists($f)) return false;
	$ret["fonction_imagecreatefrom"] = $f;
	$ret["fichier"] = $fichier;
	$ret["fonction_image"] = "_image_image".$terminaison_dest;
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
function _image_imagepng($img,$fichier) {
	if (!function_exists('imagepng')) return false;
	$tmp = $fichier.".tmp";
	$ret = imagepng($img,$tmp);
	
	$taille_test = getimagesize($tmp);
	if ($taille_test[0] < 1) return false;

	spip_unlink($fichier); // le fichier peut deja exister
	@rename($tmp, $fichier);
	return $ret;
}

// http://doc.spip.org/@image_imagegif
function _image_imagegif($img,$fichier) {
	if  (!function_exists('imagegif')) return false;
	$tmp = $fichier.".tmp";
	$ret = imagegif($img,$tmp);

	$taille_test = getimagesize($tmp);
	if ($taille_test[0] < 1) return false;


	spip_unlink($fichier); // le fichier peut deja exister
	@rename($tmp, $fichier);
	return $ret;
}
// http://doc.spip.org/@image_imagejpg
function _image_imagejpg($img,$fichier,$qualite=_IMG_GD_QUALITE) {
	if (!function_exists('imagejpeg')) return false;
	$tmp = $fichier.".tmp";
	$ret = imagejpeg($img,$tmp, $qualite);

	$taille_test = getimagesize($tmp);
	if ($taille_test[0] < 1) return false;

	spip_unlink($fichier); // le fichier peut deja exister
	@rename($tmp, $fichier);
	return $ret;
}
// http://doc.spip.org/@image_imageico
function _image_imageico($img, $fichier) {
	$gd_image_array = array($img);

	return ecrire_fichier($fichier, phpthumb_functions::GD2ICOstring($gd_image_array));
}

// $qualite est utilise pour la qualite de compression des jpeg
// http://doc.spip.org/@image_gd_output
function _image_gd_output($img,$valeurs, $qualite=_IMG_GD_QUALITE){
	$fonction = "_image_image".$valeurs['format_dest'];
	$ret = false;
	#un flag pour reperer les images gravees
	$lock = 
		!statut_effacer_images_temporaires('get') // si la fonction n'a pas ete activee, on grave tout
	  OR (@file_exists($valeurs['fichier_dest']) AND !@file_exists($valeurs['fichier_dest'].'.src'));
	if (
	     function_exists($fonction) 
			  && ($ret = $fonction($img,$valeurs['fichier_dest'],$qualite)) # on a reussi a creer l'image
			  && isset($valeurs['reconstruction']) # et on sait comment la resonctruire le cas echeant
			  && !$lock
	  )
		if (@file_exists($valeurs['fichier_dest'])){
			// dans tous les cas mettre a jour la taille de l'image finale
			list ($valeurs["hauteur_dest"],$valeurs["largeur_dest"]) = taille_image($valeurs['fichier_dest']);
			$valeurs['date'] = @filemtime($valeurs['fichier_dest']); // pour la retrouver apres disparition
			ecrire_fichier($valeurs['fichier_dest'].'.src',serialize($valeurs),true);
		}
		
	return $ret;
}

// http://doc.spip.org/@reconstruire_image_intermediaire
function reconstruire_image_intermediaire($fichier_manquant){
	$reconstruire = array();
	$fichier = $fichier_manquant;
	while (
		!@file_exists($fichier)
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
	// appeler le filtre post_image_filtrer qui permet de faire
	// des traitements auto a la fin d'une serie de filtres
	$img = pipeline('post_image_filtrer',$img);

	$fichier = extraire_attribut($img, 'src');
	if (($p=strpos($fichier,'?'))!==FALSE)
		$fichier=substr($fichier,0,$p);
	if (strlen($fichier) < 1)
		$fichier = $img;
	# si jamais le fichier final n'a pas ete calcule car suppose temporaire
	if (!@file_exists($fichier)) 
		reconstruire_image_intermediaire($fichier);
	ramasse_miettes($fichier);
	return $img; // on ne change rien
}

// Transforme une image a palette indexee (256 couleurs max) en "vraies" couleurs RGB
// http://doc.spip.org/@imagepalettetotruecolor
 function imagepalettetotruecolor(&$img) {
	if ($img AND !imageistruecolor($img) AND function_exists('imagecreatetruecolor')) {
		$w = imagesx($img);
		$h = imagesy($img);
		$img1 = imagecreatetruecolor($w,$h);
		//Conserver la transparence si possible
		if(function_exists('ImageCopyResampled')) {
			if (function_exists("imageAntiAlias")) imageAntiAlias($img1,true); 
			@imagealphablending($img1, false); 
			@imagesavealpha($img1,true); 
			@ImageCopyResampled($img1, $img, 0, 0, 0, 0, $w, $h, $w, $h);
		} else {
			imagecopy($img1,$img,0,0,0,0,$w,$h);
		}

		$img = $img1;
	}
}

// http://doc.spip.org/@image_tag_changer_taille
function _image_tag_changer_taille($tag,$width,$height,$style=false){
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

// function d'ecriture du de la balise img en sortie des filtre image
// reprend le tag initial et surcharge les tags modifies
function _image_ecrire_tag($valeurs,$surcharge=array()){
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

	$tag = _image_tag_changer_taille($tag,$width,$height,$style);
	// traiter specifiquement le src qui peut etre repris dans un onmouseout
	// on remplace toute les ref a src dans le tag
	$src = extraire_attribut($tag,'src');
	if (isset($surcharge['src'])){
		$tag = str_replace($src,$surcharge['src'],$tag);
		// si il y a des & dans src, alors ils peuvent provenir d'un &amp
		// pas garanti comme methode, mais mieux que rien
		if (strpos($src,'&') !== false)
			$tag = str_replace(str_replace("&","&amp;",$src),$surcharge['src'],$tag);
		$src = $surcharge['src'];
		unset($surcharge['src']);
	}

	$class = $valeurs['class'];
	if (isset($surcharge['class'])){
		$class = $surcharge['class'];
		unset($surcharge['class']);
	}
	if(strlen($class))
		$tag = inserer_attribut($tag,'class',$class);

	if (count($surcharge))
		foreach($surcharge as $attribut=>$valeur)
			$tag = inserer_attribut($tag,$attribut,$valeur);
	
	return $tag;
}

function _image_creer_vignette($valeurs, $maxWidth, $maxHeight, $process='AUTO', $force=false, $test_cache_only = false) {
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
				list ($destWidth,$destHeight) = _image_ratio($valeurs['largeur'], $valeurs['hauteur'], $maxWidth, $maxHeight);
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

			$fonction_imagecreatefrom = $valeurs['fonction_imagecreatefrom'];
			if (!function_exists($fonction_imagecreatefrom))
				return '';
			$srcImage = @$fonction_imagecreatefrom($image);
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

			// Sauvegarde de l'image destination
			$valeurs['fichier_dest'] = $vignette = "$destination.$destFormat";
			$valeurs['format_dest'] = $format = $destFormat;
			_image_gd_output($destImage,$valeurs);

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
function _image_ratio ($srcWidth, $srcHeight, $maxWidth, $maxHeight) {
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
	return array (ceil($destWidth), ceil($destHeight),
		min($ratioWidth,$ratioHeight));
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
		$image = _image_valeurs_trans($img, "reduire-{$taille}-{$taille_y}",$format_sortie,$fonction);

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
		$image = _image_valeurs_trans($img, "reduire-{$taille}-{$taille_y}",$format_sortie,$fonction);

	if (!$image OR !$image['largeur'] OR !$image['hauteur']){
		spip_log("image_reduire_src:pas de version locale de $img");
		// on peut resizer en mode html si on dispose des elements
		if ($srcw = extraire_attribut($img, 'width')
		AND $srch = extraire_attribut($img, 'height')) {
			list($w,$h) = _image_ratio($srcw, $srch, $taille, $taille_y);
			return _image_tag_changer_taille($img,$w,$h);
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
		return _image_ecrire_tag($image,array('src'=>$image['fichier_dest']));
	}

	if ($image['creer']==false && !$force)
		return _image_ecrire_tag($image,array('src'=>$image['fichier_dest'],'width'=>$image['largeur_dest'],'height'=>$image['hauteur_dest']));

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
		$preview = _image_creer_vignette($image, $taille, $taille_y,$process,$force);

		if ($preview && $preview['fichier']) {
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
		return _image_ecrire_tag($image,array('src'=>"$logo$date",'width'=>$destWidth,'height'=>$destHeight));
	}
	else
		# SVG par exemple ? BMP, tiff ... les redacteurs osent tout!
		return $img;
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
