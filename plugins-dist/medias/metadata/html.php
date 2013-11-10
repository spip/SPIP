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
include_spip('inc/autoriser');

/**
 * enlever les scripts de html si necessaire
 * on utilise safehtml
 *
 * @param string $file
 * @return array
 */
function medata_html_dist($file){
	$meta = array();

	// Securite si pas autorise : virer les scripts et les references externes
	// sauf si on est en mode javascript 'ok' (1), cf. inc_version
	if ($GLOBALS['filtrer_javascript'] < 1
	  AND !autoriser('televerser','script')) {
		$texte = spip_file_get_contents($file);
		include_spip('inc/texte');
		$new = trim(safehtml($texte));
		// petit bug safehtml
		if ($new != $texte) ecrire_fichier($file, $new);
	}
	
	return $meta;
}