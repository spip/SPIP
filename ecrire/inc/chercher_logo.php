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

include_spip('inc/actions');

// http://doc.spip.org/@inc_chercher_logo_dist
function inc_chercher_logo_dist($id, $type, $mode='on') {
	global $formats_logos;
	# attention au cas $id = '0' pour LOGO_SITE_SPIP : utiliser intval()
	$type = $GLOBALS['table_logos'][$type];
	$nom = $type . $mode . intval($id);

	foreach ($formats_logos as $format) {
		if (@file_exists($d = (_DIR_LOGOS . $nom . '.' . $format)))
			return array($d, _DIR_LOGOS, $nom, $format);
	}
	# coherence de type pour servir comme filtre (formulaire_login)
	return array();
}

global $table_logos;

$table_logos = array( 
		     'id_article' => 'art', 
		     'id_auteur' => 'aut', 
		     'id_breve' => 'breve', 
		     'id_mot' => 'mot', 
		     'id_syndic'=> 'site',
		     'id_rubrique' => 'rub'
		     );

// http://doc.spip.org/@decrire_logo
function decrire_logo($id_objet, $mode, $id, $width, $height, $img, $titre="", $script="") {

	list($fid, $dir, $nom, $format) = $img;

	$res = ratio_image($fid, $nom, $format, $width, $height, "alt=''");
	if (!$titre)
		return $res;
	else {
	  if ($res)
	    $res = "<div><a href='" .	$fid . "'>$res</a></div>";
	  else
	    $res = "<img src='$fid' width='$width' height='$height' alt='" . htmlentities($titre) . "' />";
	  if ($taille = @getimagesize($fid))
			$taille = _T('info_largeur_vignette', array('largeur_vignette' => $taille[0], 'hauteur_vignette' => $taille[1]));
	  return "<p><center>$res" .
		debut_block_invisible($mode) .
		"<font size='1'>" .
		$taille .
		"\n<br />[<a href='" .
		redirige_action_auteur("iconifier", "unlink $nom.$format", $script, "$id_objet=$id") .
		"'>".
		_T('lien_supprimer') .
		"</a>]</font>" .
		fin_block() .
		"</center></p>";
	}
}


// http://doc.spip.org/@afficher_boite_logo
function afficher_boite_logo($id_objet, $id, $texteon, $texteoff, $script) {

	$logo_f = charger_fonction('chercher_logo', 'inc');
	
	$res = "<p>" .
		debut_cadre_relief("image-24.gif", true) .
		"<div class='verdana1' style='text-align: center;'>" .
		bouton_block_invisible('on') . "<b>" .  $texteon . "</b>";

	if ($logo = $logo_f($id, $id_objet, 'on'))
	  $logo = decrire_logo($id_objet,'on',$id, 170, 170, $logo, $texteon, $script);

	if (!$logo) {
		$res .= indiquer_logo($texteon, $id_objet, 'on', $id, $script);
		
	} else {
		$res .= $logo;
		if ($texteoff) {
			$res .=  "<br /><br />" .
				bouton_block_invisible('off') . "<b>" .  $texteoff . "</b>";
			if ($logo = $logo_f($id, $id_objet, 'off'))
			  $logo = decrire_logo($id_objet,'off',$id, 170, 170, $logo, $texteoff, $script);
			if ($logo) {
			  $res .= $logo;
			}
			else $res .= indiquer_logo($texteoff, $id_objet, 'off', $id, $script);
		}
	}

	$res .= "</div>" .  fin_cadre_relief(true) .  "</p>";
	return $res;
}

// http://doc.spip.org/@indiquer_logo
function indiquer_logo($titre, $id_objet, $mode, $id, $script) {

	global $formats_logos;
	$dir_ftp = determine_upload();
	$afficher = "";
	$reg = '[.](' . join('|', $formats_logos) . ')$';

	if ($dir_ftp
	AND $fichiers = preg_files($dir_ftp, $reg)) {
		foreach ($fichiers as $f) {
			$f = substr($f, strlen($dir_ftp));
			$afficher .= "\n<option value='$f'>$f</option>";
		}
	}
	if (!$afficher) {
		  if ($dir_ftp) 
			$afficher = _T('info_installer_images_dossier',
				array('upload' => '<b>' . joli_repertoire($dir_ftp) . '</b>'));
		} else {
		$afficher = "\n<div style='text-align: left'>" .
			_T('info_selectionner_fichier',
				array('upload' => '<b>' . joli_repertoire($dir_ftp) . '</b>')) .
			":</div>" .
			"\n<select name='source' CLASS='forml' size='1'>$afficher\n</select>" .
			"\n<div align='" .
			$GLOBALS['spip_lang_right'] .
			"'><input name='sousaction2' type='submit' value='".
			_T('bouton_choisir') .
			"' CLASS='fondo'  style='font-size:9px' /></div>";
		}
		$afficher = "\n" .
			_T('info_telecharger_nouveau_logo') .
			"<br />" .
			"\n<input name='image' type='File' class='forml' style='font-size:9px;' size='15'>" .
			"<div align='" .  $GLOBALS['spip_lang_right'] . "'>" .
			"\n<input name='sousaction1' type='submit' value='" .
			_T('bouton_telecharger') .
			"' class='fondo' style='font-size:9px' /></div>" .
			$afficher;

		$type = $GLOBALS['table_logos'][$id_objet];
		return debut_block_invisible($mode) .
		  generer_action_auteur('iconifier',
			"$type$mode$id",
			generer_url_ecrire($script, "$id_objet=$id"), 
			$afficher,
			" method='POST' ENCTYPE='multipart/form-data'") .
		  fin_block();
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
function ratio_image($logo, $nom, $format, $taille, $taille_y, $attributs)
{
	if (!$taille_origine = @getimagesize($logo)) return '';
	list ($destWidth,$destHeight, $ratio) = image_ratio($taille_origine[0], $taille_origine[1], $taille, $taille_y);

		// Creer effectivement la vignette reduite

	include_spip('inc/logos');
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
