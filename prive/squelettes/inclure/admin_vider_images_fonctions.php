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

// http://doc.spip.org/@calculer_taille_dossier
function calculer_taille_dossier ($dir) {
	$handle = @opendir($dir);
	if (!$handle) return;
	$taille = 0;
	while (($fichier = @readdir($handle)) !== false) {
		// Eviter ".", "..", ".htaccess", etc.
		if ($fichier[0] == '.') continue;
		if (is_file($d = "$dir/$fichier")) {
			$taille += filesize($d);
		}
		else if (is_dir($d))
			$taille += calculer_taille_dossier($d);
	}
	closedir($handle);
	return $taille;
}



// http://doc.spip.org/@afficher_taille_cache_vignettes
function afficher_taille_cache_vignettes() {
	$taille = calculer_taille_dossier(_DIR_VAR);
	return _T('ecrire:taille_cache_image',
		array(
			'dir' => joli_repertoire(_DIR_VAR),
			'taille' => "<b>".taille_en_octets($taille)."</b>"
			)
		);
}


?>
