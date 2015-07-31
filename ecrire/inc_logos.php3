<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_LOGOS")) return;
define("_ECRIRE_INC_LOGOS", "1");


function get_image($racine) {
	if (@file_exists("../IMG/$racine.gif")) {
		$fichier = "$racine.gif";
	}
	else if (@file_exists("../IMG/$racine.jpg")) {
		$fichier = "$racine.jpg";
	}
	else if (@file_exists("../IMG/$racine.png")) {
		$fichier = "$racine.png";
	}

	if ($fichier) {
		$taille = resize_logo($fichier);

		// contrer le cache du navigateur
		if ($fid = @filesize("../IMG/$fichier") . @filemtime("../IMG/$fichier")) {
			$fid = "?".md5($fid);
		}
		return array($fichier, $taille, $fid);
	}
	else return;
}


function resize_logo($image) {
	$limage = @getimagesize("../IMG/$image");
	if (!$limage) return;
	$limagelarge = $limage[0];
	$limagehaut = $limage[1];

	if ($limagelarge > 190){
		$limagehaut = $limagehaut * 190 / $limagelarge;
		$limagelarge = 190;
	}

	if ($limagehaut > 190){
		$limagelarge = $limagelarge * 190 / $limagehaut;
		$limagehaut = 190;
	}

	// arrondir a l'entier superieur
	$limagehaut = ceil($limagehaut);
	$limagelarge = ceil($limagelarge);

	return (array($limage[0],$limage[1],$limagelarge,$limagehaut));
}


function afficher_boite_logo($logo, $survol, $texteon, $texteoff) {
	global $options, $spip_display;


	if ($spip_display != 4) {
		$logo_ok = get_image($logo);
		if ($logo_ok) $survol_ok = get_image($survol);
	
		echo "<p>";
		debut_cadre_relief("image-24.gif");
		echo "<center><font size='2' FACE='Verdana,Arial,Sans,sans-serif'>";
		echo "<b>";
		echo bouton_block_invisible(md5($texteon));
		echo $texteon;
		echo "</b>";
	
		afficher_logo($logo, $texteon);
	
		if ($logo_ok OR $survol_ok) {
			echo "<br><br><b>";
			echo bouton_block_invisible(md5($texteoff));
			echo $texteoff;
			echo "</b>";
			afficher_logo($survol, $texteoff);
		}
	
		echo "</font></center>";
		fin_cadre_relief();
	}
}

function afficher_logo($racine, $titre) {
	global $id_article, $coll, $id_breve, $id_auteur, $id_mot, $id_syndic, $connect_id_auteur;
	global $couleur_foncee, $couleur_claire;
	global $clean_link;

	include_ecrire('inc_admin.php3');

	$redirect = $clean_link->getUrl();
	$logo = get_image($racine);
	if ($logo) {
		$fichier = $logo[0];
		$taille = $logo[1];
		$fid = $logo[2];
		if ($taille) {
			$taille_html = " WIDTH=$taille[2] HEIGHT=$taille[3] ";
			$taille_txt = "$taille[0] x $taille[1] "._T('info_pixels');
		}
	}

	echo "<font size=1>";

	if ($fichier) {
		$hash = calculer_action_auteur("supp_image $fichier");

		echo "<P><CENTER><IMG SRC='../IMG/$fichier$fid' $taille_html alt='' />";

		echo debut_block_invisible(md5($titre));
		echo "$taille_txt\n";
		echo "<BR>[<A HREF='../spip_image.php3?";
		$elements = array('id_article', 'id_breve', 'id_syndic', 'coll', 'id_mot', 'id_auteur');
		while (list(,$element) = each ($elements)) {
			if ($$element) {
				echo $element.'='.$$element.'&';
			}
		}
		echo "image_supp=$fichier&hash_id_auteur=$connect_id_auteur&id_auteur=$id_auteur&hash=$hash&redirect=$redirect'>"._T('lien_supprimer')."</A>]";
		echo fin_block();
		echo "</CENTER>";
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

	if ($process == 'AUTO')
		$process = lire_meta('image_process');

	// liste des formats qu'on sait lire
	$formats_graphiques = lire_meta('formats_graphiques');
	$formats_sortie = array('jpg','png','gif');

	// si le doc n'est pas une image, refuser
	if (!$force AND !eregi(",$format,", ",$formats_graphiques,"))
		return;

	// chercher un cache
	while (list(,$fmt) = each ($formats_sortie))
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
			$vignette = $destination.".jpg";
			$commande = "$convert_command -size ${destWidth}x${destHeight} $image -geometry ${destWidth}x${destHeight} +profile \"*\" ".escapeshellcmd($vignette);
			spip_log($commande);
			exec($commande);
			if (!@file_exists($vignette))
				return;	// echec commande
		}
		else
		 // imagick (php4-imagemagick)
		 if ($process == 'imagick') {
			$vignette = "$destination.jpg";
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
			if (ereg("jpg", $gd_formats))
				$destFormat = "jpg";
			else if ($format == "gif" AND ereg("gif", $gd_formats) AND $GLOBALS['flag_ImageGif'])
				$destFormat = "gif";
			else if (ereg("png", $gd_formats))
				$destFormat = "png";
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

?>
