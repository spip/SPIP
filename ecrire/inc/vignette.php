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

//
// Vignette pour les documents lies
//
function inc_vignette_dist($ext, $size=true, $loop = true) {

	if (!$ext)
		$ext = 'txt';

	// Chercher la vignette correspondant a ce type de document
	// dans les vignettes persos, ou dans les vignettes standard
	if (
	# installation dans un dossier /vignettes personnel, par exemple /squelettes/vignettes
	!@file_exists($v = find_in_path("vignettes/".$ext.".png"))
	AND !@file_exists($v = find_in_path("vignettes/".$ext.".gif"))
	# dans /icones (n'existe plus)
	AND !@file_exists($v = _DIR_IMG_ICONES . $ext.'.png')
	AND !@file_exists($v = _DIR_IMG_ICONES . $ext.'.gif')
	# icones standard
	AND !@file_exists($v = _DIR_IMG_ICONES_DIST . $ext.'.png')
	# cas d'une install dans un repertoire "applicatif"...
	AND !@file_exists(_ROOT_IMG_ICONES_DIST . $v)
	)
		if ($loop){
			$f = charger_fonction('vignette','inc');
			$v = $f('defaut', false, $loop=false);
		}
		else
			$v = false; # pas trouve l'icone de base

	if (!$size) return $v;

	if ($size = @getimagesize($v)) {
		$largeur = $size[0];
		$hauteur = $size[1];
	}

	return array($v, $largeur, $hauteur);
}

?>
