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

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Vignette pour les documents lies
 * rechercher les fichiers d'icone au format png pour l'extension demandee
 *
 * on cherche prive/vignettes/ext.png dans le path
 *
 * @param string $ext
 * @param bool $size
 * @param bool $loop
 * @return array|bool|int|string
 */
function inc_vignette_dist($ext, $size=true, $loop = true) {

	if (!$ext)
		$ext = 'txt';

	// Chercher la vignette correspondant a ce type de document
	// dans les vignettes persos, ou dans les vignettes standard
	if (
	# installation dans un dossier /vignettes personnel, par exemple /squelettes/vignettes
	!$v = find_in_path("prive/vignettes/".$ext.".png")
	)
		if ($loop){
			$f = charger_fonction('vignette','inc');
			$v = $f('defaut', false, $loop=false);
		}
		else
			$v = false; # pas trouve l'icone de base

	if (!$size) return $v;

	$largeur = $hauteur = 0;
	if ($v AND $size = @getimagesize($v)) {
		$largeur = $size[0];
		$hauteur = $size[1];
	}

	return array($v, $largeur, $hauteur);
}

?>
