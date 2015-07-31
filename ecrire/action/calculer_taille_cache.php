<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;



/**
 * Calculer la taille du cache ou du cache image pour l'afficher en ajax sur la page d'admin de SPIP
 *
 * @param string|null $arg
 */
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
			'taille' => "<b>".(taille_en_octets($taille) > 0 ? taille_en_octets($taille) : "0 octet")."</b>"
			)
		);
	}
	else {
		include_spip('inc/invalideur');
		$taille =
			calculer_taille_dossier(_DIR_CACHE_XML)
			+calculer_taille_dossier(_DIR_CACHE.'skel/')
			+calculer_taille_dossier(_DIR_CACHE.'wheels/')
			+calculer_taille_dossier(_DIR_CACHE.'contextes/')
		;
		$taille += intval(taille_du_cache());
		if ($taille<=150000){
			$res = _T('taille_cache_vide');
		}
		elseif ($taille<=1024*1024){
			$res = _T('taille_cache_moins_de',array('octets'=>taille_en_octets(1024*1024)));
		}
		else {
			$res = _T('taille_cache_octets',array('octets'=>taille_en_octets($taille)));
		}
		$res = "<b>$res</b>";
	}
	
	$res = "<p>$res</p>";
	ajax_retour($res);
}


/**
 * Calculer la taille d'un dossier, sous dossiers inclus
 *
 * http://doc.spip.org/@calculer_taille_dossier
 *
 * @param $dir
 * @return int
 */
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