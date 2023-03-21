<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Gestion de l'action calculer_taille_cache
 *
 * @package SPIP\Core\Cache
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Calculer la taille du cache ou du cache image pour l'afficher en ajax sur la page d'admin de SPIP
 *
 * Si l'argument reçu est 'images', c'est la taille du cache _DIR_VAR qui est calculé,
 * sinon celle du cache des squelettes (approximation)
 *
 * @param string|null $arg
 *     Argument attendu. En absence utilise l'argument
 *     de l'action sécurisée.
 */
function action_calculer_taille_cache_dist($arg = null) {
	if (is_null($arg)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}
	include_spip('inc/filtres');

	if ($arg == 'images') {
		$taille = calculer_taille_dossier(_DIR_VAR);
		$res = _T(
			'ecrire:taille_cache_image',
			[
				'dir' => joli_repertoire(_DIR_VAR),
				'taille' => '<b>' . (taille_en_octets($taille) > 0 ? taille_en_octets($taille) : '0 octet') . '</b>'
			]
		);
	} else {
		include_spip('inc/invalideur');
		$taille =
			calculer_taille_dossier(_DIR_CACHE_XML)
			+ calculer_taille_dossier(_DIR_CACHE . 'skel/')
			+ calculer_taille_dossier(_DIR_CACHE . 'wheels/')
			+ calculer_taille_dossier(_DIR_CACHE . 'contextes/');
		$taille += (int) taille_du_cache();
		if ($taille <= 150000) {
			$res = _T('taille_cache_vide');
		} elseif ($taille <= 1024 * 1024) {
			$res = _T('taille_cache_moins_de', ['octets' => taille_en_octets(1024 * 1024)]);
		} else {
			$res = _T('taille_cache_octets', ['octets' => taille_en_octets($taille)]);
		}
		$res = "<b>$res</b>";
	}

	$res = "<p>$res</p>";
	ajax_retour($res);
}


/**
 * Calculer la taille d'un dossier, sous dossiers inclus
 *
 * @param string $dir Répertoire dont on souhaite évaluer la taille
 * @return int Taille en octets
 */
function calculer_taille_dossier($dir) {
	if (!is_dir($dir) || !is_readable($dir)) {
		return 0;
	}
	$handle = opendir($dir);
	if (!$handle) {
		return 0;
	}
	$taille = 0;
	while (($fichier = @readdir($handle)) !== false) {
		// Eviter ".", "..", ".htaccess", etc.
		if ($fichier[0] == '.') {
			continue;
		}
		if (is_file($d = "$dir/$fichier")) {
			$taille += filesize($d);
		} else {
			if (is_dir($d)) {
				$taille += calculer_taille_dossier($d);
			}
		}
	}
	closedir($handle);

	return $taille;
}
