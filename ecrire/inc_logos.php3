<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_LOGOS")) return;
define("_ECRIRE_INC_LOGOS", "1");
global $flag_ecrire;
define('_DIR_IMG', ($GLOBALS['flag_ecrire'] ? "../" : "")."IMG/");

function decrire_logo($racine) {
	global $connect_id_auteur;

	foreach (array('gif','jpg','png') as $fmt) {
		$fichier = "$racine.".$fmt;
		$fid = _DIR_IMG . $fichier;
		if (@file_exists($fid)) {
			// contrer le cache du navigateur
			$contre = @filesize($fid) . @filemtime($fid);
			if ($taille = @getimagesize($fid)) {
				list($x, $y, $w, $h) = resize_logo($taille);
				$xy = "$x x $y "._T('info_pixels');
				$taille = " width='$w' height='$h'";
			} else { $xy =''; $w = 0; $h = 0;}
			return array($fichier, 
				     $xy, 
				     "<img src='../spip_image_reduite.php3?img=" .
				     $fid . "&taille_x=$w&taille_y=$h&hash=" .
				     calculer_action_auteur ("reduire $w $h") .
				     "&hash_id_auteur=$connect_id_auteur" .
				     (!$contre ? '' : ("&".md5($contre))) .
				     "'$taille alt='' />");
			  }
	}
	return '';
}

function resize_logo($limage, $maxi=190) {

	$limagelarge = $limage[0];
	$limagehaut = $limage[1];

	if ($limagelarge > $maxi){
		$limagehaut = $limagehaut * $maxi / $limagelarge;
		$limagelarge = $maxi;
	}

	if ($limagehaut > $maxi){
		$limagelarge = $limagelarge * $maxi / $limagehaut;
		$limagehaut = $maxi;
	}

	// arrondir a l'entier superieur
	$limagehaut = ceil($limagehaut);
	$limagelarge = ceil($limagelarge);

	return (array($limage[0],$limage[1],$limagelarge,$limagehaut));
}


function afficher_boite_logo($logo, $survol, $texteon, $texteoff) {
	global $options, $spip_display;


	if ($spip_display != 4) {
	
		echo "<p>";
		debut_cadre_relief("image-24.gif");
		echo "<div class='verdana1' style='text-align: center;'>";
		$desc = decrire_logo($logo);
		afficher_logo($logo, $texteon, $desc);

		if ($desc) {
			echo "<br /><br />";
			$desc = decrire_logo($survol);
			afficher_logo($survol, $texteoff, $desc);   
		}
	
		echo "</div>";
		fin_cadre_relief();
		echo "</p>";
	}
}


function afficher_logo($racine, $titre, $logo) {
	global $id_article, $coll, $id_breve, $id_auteur, $id_mot, $id_syndic, $connect_id_auteur;
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
		list($fichier, $taille, $img) =  $logo;
		spip_log("$fichier, $taille, $img");
		$hash = calculer_action_auteur("supp_image $fichier");

		echo "<p><center>$img";
		echo debut_block_invisible(md5($titre));
		echo $taille;
		echo "\n<br />[<a href='../spip_image.php3?";
		$elements = array('id_article', 'id_breve', 'id_syndic', 'coll', 'id_mot', 'id_auteur');
		while (list(,$element) = each ($elements)) {
			if ($$element) {
				echo $element.'='.$$element.'&';
			}
		}
		echo "image_supp=$fichier&hash_id_auteur=$connect_id_auteur&id_auteur=$id_auteur&hash=$hash&redirect=$redirect'>"._T('lien_supprimer')."</A>]";
		echo fin_block();
		echo "</center></p>";
	}
	else {
		$hash = calculer_action_auteur("ajout_image $racine");
		echo debut_block_invisible(md5($titre));

		echo "\n\n<FORM ACTION='../spip_image.php3' METHOD='POST' ENCTYPE='multipart/form-data'>";
		echo "\n<INPUT NAME='redirect' TYPE=Hidden VALUE='$redirect'>";
		if ($id_auteur > 0) echo "\n<INPUT NAME='id_auteur' TYPE=Hidden VALUE='$id_auteur'>";
		if ($id_article > 0) echo "\n<INPUT NAME='id_article' TYPE=Hidden VALUE='$id_article'>";
		if ($id_breve > 0) echo "\n<INPUT NAME='id_breve' TYPE=Hidden VALUE='$id_breve'>";
		if ($id_mot > 0) echo "\n<INPUT NAME='id_mot' TYPE=Hidden VALUE='$id_mot'>";
		if ($id_syndic > 0) echo "\n<INPUT NAME='id_syndic' TYPE=Hidden VALUE='$id_syndic'>";
		if ($coll > 0) echo "\n<INPUT NAME='coll' TYPE=Hidden VALUE='$coll'>";
		echo "\n<INPUT NAME='hash_id_auteur' TYPE=Hidden VALUE='$connect_id_auteur'>";
		echo "\n<INPUT NAME='hash' TYPE=Hidden VALUE='$hash'>";
		echo "\n<INPUT NAME='ajout_logo' TYPE=Hidden VALUE='oui'>";
		echo "\n<INPUT NAME='logo' TYPE=Hidden VALUE='$racine'>";
		if (tester_upload()){
			echo "\n"._T('info_telecharger_nouveau_logo')."<BR>";
			echo "\n<INPUT NAME='image' TYPE=File CLASS='forml' style='font-size:9px;' SIZE=15>";
			echo "\n <div align='right'><INPUT NAME='ok' TYPE=Submit VALUE='"._T('bouton_telecharger')."' CLASS='fondo' style='font-size:9px;'></div>";
		} else {

			$myDir = opendir("upload");
			while($entryName = readdir($myDir)){
				if (!ereg("^\.",$entryName) AND eregi("(gif|jpg|png)$",$entryName)){
					$entryName = addslashes($entryName);
					$afficher .= "\n<OPTION VALUE='ecrire/upload/$entryName'>$entryName";
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
	return array (ceil($destWidth), ceil($destHeight));
}

function creer_vignette($image, $maxWidth, $maxHeight, $format, $destination, $process='AUTO', $force=false) {
	global $convert_command;

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

	// chercher un cache
	foreach (array('gif','jpg','png') as $fmt)
		if (@file_exists($destination.'.'.$fmt)) {
			$vignette = $destination.'.'.$fmt;
			if ($force) @unlink($vignette);
		}

	// utiliser le cache ?
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
			return;
		}

		// imagemagick en ligne de commande
		if ($process == 'convert') {
			$format = $formats_sortie[0];
			$vignette = $destination.".".$format;
			$commande = "$convert_command -size ${destWidth}x${destHeight} ./$image -geometry ${destWidth}x${destHeight} +profile \"*\" ./".escapeshellcmd($vignette);
			spip_log($commande);
			exec($commande);
			if (!@file_exists($vignette))
				return;	// echec commande
		}
		else
		 // imagick (php4-imagemagick)
		 if ($process == 'imagick') {
			$format = $formats_sortie[0];
			$vignette = "$destination.".$format;
			$handle = imagick_readimage($image);
			imagick_resize($handle, $destWidth, $destHeight, IMAGICK_FILTER_LANCZOS, 0.75);
			imagick_write($handle, $vignette);
			if (!@file_exists($vignette)) return;	// echec imagick
		}
		else
		// gd ou gd2
		if ($process == 'gd1' OR $process == 'gd2') {

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
			if (!$srcImage) return;

			// Choisir le format destination
			// - on sauve de preference en JPEG (meilleure compression)
			// - pour le GIF : les GD recentes peuvent le lire mais pas l'ecrire
			$gd_formats = lire_meta("gd_formats");
			foreach ($formats_sortie as $fmt) {
				if (ereg($fmt, $gd_formats)) {
					if ($format <> "gif" OR $GLOBALS['flag_ImageGif'])
						$destFormat = $fmt;
					break;
				}
			}

			if (!$destFormat) return;

			// Initialisation de l'image destination
			if ($process == 'gd2' AND $destFormat != "gif")
				$destImage = ImageCreateTrueColor($destWidth, $destHeight);
			if (!$destImage)
				$destImage = ImageCreate($destWidth, $destHeight);

			// Recopie de l'image d'origine avec adaptation de la taille
			$ok = false;
			if (($process == 'gd2') AND function_exists('flag_ImageCopyResampled'))
				$ok = @ImageCopyResampled($destImage, $srcImage, 0, 0, 0, 0, $destWidth, $destHeight, $srcWidth, $srcHeight);
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


	// mettre a jour la base si creation
	if ($creation AND $vignette) {
		if ($format == "jpg") $format = 1;
		else if ($format == "png") $format = 2;
		else if ($format == "gif") $format = 3;

		$taille = @filesize($vignette);
		$vignette = str_replace('../', '', $vignette);
		$image = str_replace('../', '', $image);

		spip_log("creation vignette($image) -> $vignette");

		if ($t = spip_query("SELECT id_vignette, id_document FROM spip_documents WHERE fichier='".addslashes($image)."'"))
		if ($row = spip_fetch_array($t)) {
			$id_document = $row['id_document'];
			if (!$id_vignette = $row['id_vignette']) {
				spip_query("INSERT INTO spip_documents (mode) VALUES ('vignette')");
				$id_vignette = spip_insert_id();
				spip_query("UPDATE spip_documents SET id_vignette=$id_vignette WHERE id_document=$id_document");
			}
			spip_query("UPDATE spip_documents SET
				id_type = '$format',
				largeur = '$largeur',
				hauteur = '$hauteur',
				taille = '$taille',
				fichier = '$vignette',
				date = NOW()
				WHERE id_document = $id_vignette");
			spip_log("(document=$id_document, vignette=$id_vignette)");
		}
	}

	// renvoyer l'image
	return $retour;
}


//
// Reduire la taille d'un logo
// [(#LOGO_ARTICLE||reduire_image{100,60})]
//

function reduire_image_logo($img, $taille = 120, $taille_y=0) {
	if (!$taille_y)
		$taille_y = $taille;

	// recuperer le nom du fichier
	if (eregi("src=\'([^']+)\'", $img, $regs)) $logo = $regs[1];
	if (eregi("align=\'([^']+)\'", $img, $regs)) $align = $regs[1];
	if (eregi("name=\'([^']+)\'", $img, $regs)) $name = $regs[1];
	if (eregi("hspace=\'([^']+)\'", $img, $regs)) $espace = $regs[1];

	if (!$logo)
		$logo = $img; // [(#LOGO_ARTICLE|fichier|reduire_image{100})]


	if (ereg("^../",$logo))
		$logo = substr($logo,3);

	if (ereg("^" . _DIR_IMG, $logo))
		$img = substr($logo,strlen(_DIR_IMG));
	else { $img = $logo; $logo = _DIR_IMG . $logo;}

	if (@file_exists($logo) AND
	eregi("^(.*)\.(jpg|gif|png)$", $img, $regs)) {
		include_local('inc-public-global.php3');
		$nom = $regs[1];
		$format = $regs[2];
		$suffixe = '-'.$taille.'x'.$taille_y;
		$cache_folder=  _DIR_IMG . creer_repertoire(_DIR_IMG, 'cache'.$suffixe);
		$preview = creer_vignette($logo, $taille, $taille_y, $format, $cache_folder.$nom.$suffixe);

		if ($preview) {
			$vignette = $preview['fichier'];
			$width = $preview['width'];
			$height = $preview['height'];
			return "<img src='$vignette' name='$name' border='0' align='$align' alt='' hspace='$espace' vspace='$espace' width='$width' height='$height' class='spip_logos' />";
		}
		else if ($taille_origine = getimagesize($logo)) {
			list ($destWidth,$destHeight) = image_ratio($taille_origine[0], $taille_origine[1], $taille, $taille_y);
			return "<img src='$logo' name='$name' width='$destWidth' height='$destHeight' border='0' align='$align' alt='' hspace='$espace' vspace='$espace' class='spip_logos' />";
		}
	}
}

?>
