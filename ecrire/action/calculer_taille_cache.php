<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;


// Cette action permet de confirmer un changement d'email

function action_calculer_taille_cache_dist($arg=null){
	if (is_null($arg)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}
	include_spip('inc/filtres');

	if ($arg=='images'){
		$taille = calculer_taille_dossier(_DIR_VAR);
		$res = _T('ecrire:taille_cache_image',
		array(
			'dir' => joli_repertoire(_DIR_VAR),
			'taille' => "<b>".taille_en_octets($taille)."</b>"
			)
		);
	}
	else {
		include_spip('inc/invalideur');
		$taille = intval(taille_du_cache());
		$res = ($taille<=250000) ?
			_T('taille_cache_vide')
			:
			_T('taille_cache_octets',array('octets'=>taille_en_octets($taille)));
		$res = "<b>$res</b>";
	}
	
	$res = "<p>$res</p>";
	ajax_retour($res);
}



// http://doc.spip.org/@calculer_taille_dossier
function calculer_taille_dossier ($dir) {
	$handle = @opendir($dir);
	if (!$handle) return 0;
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

?>