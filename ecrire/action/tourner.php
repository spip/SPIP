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

// http://doc.spip.org/@action_tourner_dist
function action_tourner_dist() {
	include_spip('inc/distant'); # pour copie_locale

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	if (!preg_match(",^\W*(\d+)\W?(-?\d+)$,", $arg, $r)) {
		spip_log("action_tourner_dist $arg pas compris");
	} else  action_tourner_post($r);
}

// http://doc.spip.org/@action_tourner_post
function action_tourner_post($r)
{
	$arg = $r[1];
	$result = spip_query("SELECT fichier FROM spip_documents WHERE id_document=$arg");

	if (!$row = sql_fetch($result))
		return;

	include_spip('inc/charsets');	# pour le nom de fichier
	include_spip('inc/documents'); 
	include_spip('base/abstract_sql');
	// Fichier destination : on essaie toujours de repartir de l'original
	$var_rot = $r[2];
	$src = copie_locale(get_spip_doc($row['fichier']));
	if (preg_match(',^(.*)-r(90|180|270)\.([^.]+)$,', $src, $match)) {
		$effacer = $src;
		$src = $match[1].'.'.$match[3];
		$var_rot += intval($match[2]);
	}
	$var_rot = ((360 + $var_rot) % 360); // 0, 90, 180 ou 270

	if ($var_rot > 0) {
		$dest = preg_replace(',\.[^.]+$,', '-r'.$var_rot.'$0', $src);
		spip_log("rotation $var_rot $src : $dest");

		$process = $GLOBALS['meta']['image_process'];

		// imagick (php4-imagemagick)
		if ($process == 'imagick') {
			$handle = imagick_readimage($src);
			imagick_rotate($handle, $var_rot);
			imagick_write($handle, $dest);
			if (!@file_exists($dest)) return;	// echec imagick
		}
		else if ($process == "gd2") { // theoriquement compatible gd1, mais trop forte degradation d'image
			gdRotate ($src, $dest, $var_rot);
		}
		else if ($process = "convert") {
			if (_CONVERT_COMMAND!='') {
				define ('_CONVERT_COMMAND', 'convert');
				define ('_ROTATE_COMMAND', _CONVERT_COMMAND.' -rotate %t %src %dest');
			} else
				define ('_ROTATE_COMMAND', '');
			if (_ROTATE_COMMAND!=='') {
				$commande = str_replace(
					array('%t', '%src', '%dest'),
					array(
						$var_rot,
						escapeshellcmd($src),
						escapeshellcmd($dest)
					),
					_ROTATE_COMMAND);
				spip_log($commande);
				exec($commande);
			} else
				$dest = $src;
		}
	}
	else
		$dest = $src;

	$size_image = @getimagesize($dest);
	$largeur = $size_image[0];
	$hauteur = $size_image[1];

	// succes !
	if ($largeur>0 AND $hauteur>0) {
		$f = "'" . addslashes(set_spip_doc($dest)) . "'";
		spip_query("UPDATE spip_documents SET fichier=$f, largeur=$largeur, hauteur=$hauteur WHERE id_document=$arg");
		if ($effacer) {
			spip_log("j'efface $effacer");
			spip_unlink($effacer);
		}
	}

}


/////////////////////////////////////////////////////////////////////
//
// Faire tourner une image
//
// http://doc.spip.org/@gdRotate
function gdRotate ($src, $dest, $rtt){
	$src_img = '';
	if(preg_match("/\.(png|gif|jpe?g|bmp)$/i", $src, $regs)) {
		switch($regs[1]) {
			case 'png':
			  if (function_exists('ImageCreateFromPNG')) {
				$src_img=ImageCreateFromPNG($src);
				$save = 'imagepng';
			  }
			  break;
			case 'gif':
			  if (function_exists('ImageCreateFromGIF')) {
				$src_img=ImageCreateFromGIF($src);
				$save = 'imagegif';
			  }
			  break;
			case 'jpeg':
			case 'jpg':
			  if (function_exists('ImageCreateFromJPEG')) {
				$src_img=ImageCreateFromJPEG($src);
				$save = 'Imagejpeg';
			  }
			  break;
			case 'bmp':
			  if (function_exists('ImageCreateFromWBMP')) {
				$src_img=@ImageCreateFromWBMP($src);
				$save = 'imagewbmp';
			  }
			  break;
		}
	}

	if (!$src_img) {
		spip_log("gdrotate: image non lue, $src");
		return false;
	}

	$size=@getimagesize($src);
	if (!($size[0] * $size[1])) return false;

	if (function_exists('imagerotate')) {
		$dst_img = imagerotate($src_img, -$rtt, 0);
	} else {

		// Creer l'image destination (hauteur x largeur) et la parcourir
		// pixel par pixel (un truc de fou)
		if ($rtt == 180)
			$size_dest = $size;
		else
			$size_dest = array($size[1],$size[0]);
		
		if ($GLOBALS['meta']['image_process'] == "gd2")
			$dst_img=ImageCreateTrueColor($size_dest[0],$size_dest[1]);
		else
			$dst_img=ImageCreate($size_dest[0],$size_dest[1]);

		// t=top; b=bottom; r=right; l=left
		for ($t=0;$t<=$size_dest[0]-1; $t++) {
			$b = $size_dest[0] -1 - $t;
			for ($l=0;$l<=$size_dest[1]-1; $l++) {
				$r = $size_dest[1] -1 - $l;
				switch ($rtt) {
					case 90:
						imagecopy($dst_img,$src_img,$t,$r,$r,$b,1,1);
						break;
					case 270:
						imagecopy($dst_img,$src_img,$t,$l,$r,$t,1,1);
						break;
					case 180:
						imagecopy($dst_img,$src_img,$t,$l,$b,$r,1,1);
						break;
				}
			}
		}
	}
	ImageDestroy($src_img);
	ImageInterlace($dst_img,0);

	// obligatoire d'enregistrer dans le meme format, puisqu'on change le doc
	// mais pas son extension
	$save($dst_img,$dest);
}

?>
