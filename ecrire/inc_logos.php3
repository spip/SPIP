<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_LOGOS")) return;
define("_ECRIRE_INC_LOGOS", "1");


function cherche_image_nommee($nom, $formats = array ('gif', 'jpg', 'png')) {
	// _DIR_IMG contient deja le ../ dans ecrire (PREFIX1
	//	if (ereg("^../",$nom))	$nom = substr($nom,3);
	if (ereg("^" . _DIR_IMG, $nom)) {
		$nom = substr($nom,strlen(_DIR_IMG));
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
		$d = _DIR_IMG . "$chemin$nom.$format";
		if (@file_exists($d)){ 
			return array(_DIR_IMG."$chemin", $nom, $format);
		}
	}
}



function decrire_logo($racine) {
	global $connect_id_auteur;
		
	if ($img = cherche_image_nommee($racine)) {
		list($dir, $racine, $fmt) = $img;
		$fid = $dir . "$racine.".$fmt; 
		if ($taille = @getimagesize($fid))
			$xy = _T('info_largeur_vignette', array('largeur_vignette' => $taille[0], 'hauteur_vignette' => $taille[1]));

		return array("$racine.$fmt", $xy);
	}
	return '';
}


function afficher_boite_logo($type, $id_objet, $id, $texteon, $texteoff) {
	global $options, $spip_display;

	$logon = $type.'on'.$id;
	$logoff = $type.'off'.$id;

	if ($spip_display != 4) {
	
		echo "<p>";
		debut_cadre_relief("image-24.gif");
		echo "<div class='verdana1' style='text-align: center;'>";
		$desc = decrire_logo($logon);
		afficher_logo($logon, $texteon, $desc, $id_objet, $id);

		if ($desc AND $texteoff) {
			echo "<br /><br />";
			$desc = decrire_logo($logoff);
			afficher_logo($logoff, $texteoff, $desc, $id_objet, $id);
		}
	
		echo "</div>";
		fin_cadre_relief();
		echo "</p>";
	}
}


function afficher_logo($racine, $titre, $logo, $id_objet, $id) {
	global $connect_id_auteur;
	global $couleur_foncee, $couleur_claire;
	global $clean_link;

	include_ecrire('inc_admin.php3');
 
	$redirect = $clean_link->getUrl();

	echo "<b>";
	echo bouton_block_invisible(md5($titre));
	echo $titre;
	echo "</b>";
	echo "<font size=1>";

	if ($logo) {
		list ($fichier, $taille) =  $logo;
		$hash = calculer_action_auteur("supp_logo $fichier");

		echo "<p><center><div><a href='"._DIR_IMG.$fichier."'>";
		echo reduire_image_logo(_DIR_IMG.$fichier, 170);
		echo "</a></div>";
		echo debut_block_invisible(md5($titre));
		echo $taille;
		echo "\n<br />[<a href='../spip_image.php3?";
		echo "$id_objet=$id&";
		echo "image_supp=$fichier&hash_id_auteur=$connect_id_auteur&hash=$hash&redirect=".urlencode($redirect)."'>"._T('lien_supprimer')."</A>]";
		echo fin_block();
		echo "</center></p>";
	}
	else {
		$hash = calculer_action_auteur("ajout_logo $racine");
		echo debut_block_invisible(md5($titre));

		echo "\n\n<FORM ACTION='../spip_image.php3' METHOD='POST'
			ENCTYPE='multipart/form-data'>";
		echo "\n<INPUT NAME='redirect' TYPE=Hidden VALUE='$redirect'>";
		echo "\n<INPUT NAME='$id_objet' TYPE=Hidden VALUE='$id'>";
		echo "\n<INPUT NAME='hash_id_auteur' TYPE=Hidden VALUE='$connect_id_auteur'>";
		echo "\n<INPUT NAME='hash' TYPE=Hidden VALUE='$hash'>";
		echo "\n<INPUT NAME='ajout_logo' TYPE=Hidden VALUE='oui'>";
		echo "\n<INPUT NAME='logo' TYPE=Hidden VALUE='$racine'>";
		if (tester_upload()){
			echo "\n"._T('info_telecharger_nouveau_logo')."<BR>";
			echo "\n<INPUT NAME='image' TYPE=File CLASS='forml' style='font-size:9px;' SIZE=15>";
			echo "\n <div align='right'><INPUT NAME='ok' TYPE=Submit VALUE='"._T('bouton_telecharger')."' CLASS='fondo' style='font-size:9px;'></div>";
		} else {

			$myDir = opendir(_DIR_TRANSFERT);
			while($entryName = readdir($myDir)){
				if (!ereg("^\.",$entryName) AND eregi("(gif|jpg|png)$",$entryName)){
					$entryName = addslashes($entryName);
					$afficher .= "\n<OPTION VALUE='" .
						_DIR_TRANSFERT .
						"$entryName'>$entryName";
				}
			}
			closedir($myDir);

			if (strlen($afficher) > 10){
				echo "\n"._T('info_selectionner_fichier_2');
				echo "\n<SELECT NAME='image' CLASS='forml' SIZE=1>";
				echo $afficher;
				echo "\n</SELECT>";
				echo "\n  <INPUT NAME='ok' TYPE=Submit VALUE='"._T('bouton_choisir')."' CLASS='fondo'>";
			} else {
				echo _T('info_installer_images_dossier');
			}

		}
		echo fin_block();
		echo "</FORM>\n";
	}

	echo "</font>";
}


//
// Creation automatique d'une vignette
//

// Calculer le ratio
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

function creer_vignette($image, $maxWidth, $maxHeight, $format, $destdir, $destfile, $process='AUTO', $force=false, $test_cache_only = false) {
	global $convert_command, $pnmscale_command;
	// ordre de preference des formats graphiques pour creer les vignettes
	// le premier format disponible, selon la methode demandee, est utilise
	if ($format == 'png')
		$formats_sortie = array('png','jpg','gif');
	else
		$formats_sortie = array('jpg','png','gif');
		
	if ($process == 'AUTO')
		$process = lire_meta('image_process');

	// liste des formats qu'on sait lire
	$formats_graphiques = lire_meta('formats_graphiques');

	// si le doc n'est pas une image, refuser
	if (!$force AND !eregi(",$format,", ",$formats_graphiques,"))
		return;
	// normalement il a ete cree
	if ($destdir) {
	  $destdir = creer_repertoire(_DIR_IMG, $destdir);
	} 
	$destination = _DIR_IMG . $destdir . $destfile;
#	spip_log("$dir $destination");
	// chercher un cache
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
			$format = $formats_sortie[0];
			$vignette = $destination.".".$format;
			$commande = "$convert_command -size ${destWidth}x${destHeight} ./$image -geometry ${destWidth}x${destHeight} +profile \"*\" ./".escapeshellcmd($vignette);
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
		else if ($process == "netpbm") {
			$format_sortie = "jpg";
			$vignette = $destination.".".$format_sortie;
			$pnmtojpeg_command = ereg_replace("pnmscale", "pnmtojpeg", $pnmscale_command);
			if ($format == "jpg") {
				
				$jpegtopnm_command = ereg_replace("pnmscale", "jpegtopnm", $pnmscale_command);

				exec("$jpegtopnm_command $image | $pnmscale_command -width $destWidth | $pnmtojpeg_command > $vignette");
				if (!@file_exists($vignette)) {
					spip_log("echec netpbm-jpg sur $vignette");
					return;
				}
			} else if ($format == "gif") {
				$giftopnm_command = ereg_replace("pnmscale", "giftopnm", $pnmscale_command);
				exec("$giftopnm_command $image | $pnmscale_command -width $destWidth | $pnmtojpeg_command > $vignette");
				if (!@file_exists($vignette)) {
					spip_log("echec netpbm-gif sur $vignette");
					return;
				}
			} else if ($format == "png") {
				$pngtopnm_command = ereg_replace("pnmscale", "pngtopnm", $pnmscale_command);
				exec("$pngtopnm_command $image | $pnmscale_command -width $destWidth | $pnmtojpeg_command > $vignette");
				if (!@file_exists($vignette)) {
					spip_log("echec netpbm-png sur $vignette");
					return;
				}
			}
		}
		// gd ou gd2
		else if ($process == 'gd1' OR $process == 'gd2') {

			// Recuperer l'image d'origine
			if ($format == "jpg") {
				$srcImage = @ImageCreateFromJPEG($image);
			}
			else if ($format == "gif"){
				$srcImage = @ImageCreateFromGIF($image);
			}
			else if ($format == "png"){
				$srcImage = @ImageCreateFromPNG($image);
			}
			if (!$srcImage) {
				spip_log("echec gd1/gd2");
				return;
			}
			// Choisir le format destination
			// - on sauve de preference en JPEG (meilleure compression)
			// - pour le GIF : les GD recentes peuvent le lire mais pas l'ecrire
			# bug : gd_formats contient la liste des fichiers qu'on sait *lire*,
			# pas *ecrire*
			$gd_formats = lire_meta("gd_formats");
			foreach ($formats_sortie as $fmt) {
				if (ereg($fmt, $gd_formats)) {
					if ($format <> "gif" OR $GLOBALS['flag_ImageGif'])
						$destFormat = $fmt;
					break;
				}
			}

			if (!$destFormat) {
				spip_log("pas de format pour $image");
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
			$vignette = "$destination.$destFormat";
			$format = $destFormat;
			if ($destFormat == "jpg")
				ImageJPEG($destImage, $vignette, 70);
			else if ($destFormat == "gif")
				ImageGIF($destImage, $vignette);
			else if ($destFormat == "png")
				ImagePNG($destImage, $vignette);

			ImageDestroy($srcImage);
			ImageDestroy($destImage);
		}
	}

	$size = @getimagesize($vignette);
	$retour['width'] = $largeur = $size[0];
	$retour['height'] = $hauteur = $size[1];
	$retour['fichier'] = $vignette;
	$retour['format'] = $format;
	$retour['date'] = @filemtime($vignette);
	

	// renvoyer l'image
	return $retour;
}



//
// Retourner taille d'une image
// pour les filtres |largeur et |hauteur
//
function taille_image($img) {

	if (eregi("width *= *['\"]?( *[0-9]+ *)", $img, $regs))
		$srcWidth = intval(trim($regs[1]));
	if (eregi("height *= *['\"]?( *[0-9]+ *)", $img, $regs))
		$srcHeight = intval(trim($regs[1]));

	// recuperer le nom du fichier
	if (eregi("src='([^']+)'", $img, $regs)) $logo = $regs[1];
	if (!$logo) $logo = $img;

	if (!$srcWidth
	AND $srcsize = @getimagesize($logo))
		$srcWidth = $srcsize[0];

	if (!$srcHeight
	AND $srcsize = @getimagesize($logo))
		$srcHeight = $srcsize[1];

	return array($srcHeight, $srcWidth);
	
}




//
// Reduire la taille d'un logo
// [(#LOGO_ARTICLE||reduire_image{100,60})]
//

// Cette fonction accepte en entree un nom de fichier ou un tag <img ...>

function reduire_image_logo($img, $taille = -1, $taille_y = -1) {

	// Determiner la taille x,y maxi
	if ($taille == -1) {
		$taille = lire_meta('taille_preview');
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
	if ($src = extraire_attribut($img, 'src'))
		$logo = $src;
	else
		$logo = $img;
	if (!$logo) return '';

	// Si c'est une image distante, la recuperer (si possible)
	if (!$local = copie_locale($logo)) {
		spip_log("pas de version locale de $logo");
		return $img;
	}
	$logo = $local;


	$attributs = '';

	// preserver le name='...' et le mettre en alt le cas echant
	if ($name = extraire_attribut($img, 'name')) {
		$attributs .= ' name="'.entites_html($name).'"'; 
		$attributs_alt = ' alt="'.entites_html($name).'"'; 
	}
	// si un alt (meme vide) etait present, le recuperer
	if (($alt = extraire_attribut($img, 'alt')) !== NULL)
		$attributs_alt = ' alt="'.entites_html($alt).'"'; 

	$attributs .= $attributs_alt;

	// attributs deprecies. Transformer en CSS
	if ($espace = extraire_attribut($img, 'hspace'))
		$attributs .= " style='margin: $espace" . "px; border-width: 0px;'";
	else 
		$attributs .=  " style='border-width: 0px;' class='spip_logos'";
	// attribut deprecie mais equivalent CSS pas clair
	if ($align = extraire_attribut($img, 'align'))
		$attributs .= " align='$align'";

	if (eregi("(.*)\.(jpg|gif|png)$", $logo, $regs)) {
		if ($i = cherche_image_nommee($regs[1], array($regs[2]))) {
			list(,$nom,$format) = $i;
			if ($taille_origine = @getimagesize($logo)) {
				list ($destWidth,$destHeight, $ratio) = image_ratio(
					$taille_origine[0], $taille_origine[1], $taille, $taille_y);

				// Creer effectivement la vignette reduite
				$suffixe = '-'.$destWidth.'x'.$destHeight;
				$preview = creer_vignette($logo, $taille, $taille_y,
					$format, ('cache'.$suffixe), $nom.$suffixe);
				if ($preview) {
					$logo = $preview['fichier'];
					$destWidth = $preview['width'];
					$destHeight = $preview['height'];
				}

				if (!_DIR_RESTREINT)
					$date = '?date='.filemtime($logo);
				return "<img src='$logo$date' width='$destWidth' height='$destHeight'$attributs />";
			}
		}
	}
}

?>
