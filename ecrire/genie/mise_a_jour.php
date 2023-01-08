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
 * Vérification en tâche de fond des différentes mise à jour.
 *
 * @package SPIP\Core\Genie\Mise_a_jour
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Verifier si une mise a jour est disponible
 *
 * @param int $t
 * @return int
 */
function genie_mise_a_jour_dist($t) {
	include_spip('inc/meta');
	$maj = info_maj('spip', 'SPIP', $GLOBALS['spip_version_branche']);
	ecrire_meta('info_maj_spip', $maj ? ($GLOBALS['spip_version_branche'] . "|$maj") : '', 'non');

	mise_a_jour_ecran_securite();

	spip_log('Verification version SPIP : ' . ($maj ?: 'version a jour'), 'verifie_maj');

	return 1;
}

// TODO : fournir une URL sur spip.net pour maitriser la diffusion d'une nouvelle version de l'ecran via l'update auto
// ex : https://www.spip.net/auto-update/ecran_securite.php
define('_URL_ECRAN_SECURITE', 'https://git.spip.net/spip-contrib-outils/securite/raw/branch/master/ecran_securite.php');
define('_VERSIONS_SERVEUR', 'https://files.spip.net/');
define('_VERSIONS_LISTE', 'archives.xml');

/**
 * Mise a jour automatisee de l'ecran de securite
 * On se base sur le filemtime de l'ecran source avec un en-tete if_modified_since
 * Mais on fournit aussi le md5 de notre ecran actuel et la version branche de SPIP
 * Cela peut permettre de diffuser un ecran different selon la version de SPIP si besoin
 * ou de ne repondre une 304 que si le md5 est bon
 */
function mise_a_jour_ecran_securite() {
	// TODO : url https avec verification du certificat
	return;

	// si l'ecran n'est pas deja present ou pas updatable, sortir
	if (
		!_URL_ECRAN_SECURITE
		or !file_exists($filename = _DIR_ETC . 'ecran_securite.php')
		or !is_writable($filename)
		or !$last_modified = filemtime($filename)
		or !$md5 = md5_file($filename)
	) {
		return false;
	}

	include_spip('inc/distant');
	$tmp_file = _DIR_TMP . 'ecran_securite.php';
	$url = parametre_url(_URL_ECRAN_SECURITE, 'md5', $md5);
	$url = parametre_url($url, 'vspip', $GLOBALS['spip_version_branche']);
	$res = recuperer_url($url, [
		'if_modified_since' => $last_modified,
		'file' => $tmp_file
	]);

	// si il y a une version plus recente que l'on a recu correctement
	if (
		$res['status'] == 200
		and $res['length']
		and $tmp_file = $res['file']
	) {
		if ($md5 !== md5_file($tmp_file)) {
			// on essaye de l'inclure pour verifier que ca ne fait pas erreur fatale
			include_once $tmp_file;
			// ok, on le copie a la place de l'ecran existant
			// en backupant l'ecran avant, au cas ou
			@copy($filename, $filename . '-bck-' . date('Y-m-d-His', $last_modified));
			@rename($tmp_file, $filename);
		} else {
			@unlink($tmp_file);
		}
	}
}

/**
 * Vérifier si une nouvelle version de SPIP est disponible
 *
 * Repérer aussi si cette version est une version majeure de SPIP.
 *
 * @param string $dir
 * @param string $file
 * @param string $version
 *      La version reçue ici est sous la forme x.y.z
 *      On la transforme par la suite pour avoir des integer ($maj, $min, $rev)
 *      et ainsi pouvoir mieux les comparer
 *
 * @return string
 */
function info_maj($dir, $file, $version) {
	include_spip('inc/plugin');

	[$maj, $min, $rev] = preg_split('/\D+/', $version);

	$nom = _DIR_CACHE_XML . _VERSIONS_LISTE;
	$page = !file_exists($nom) ? '' : file_get_contents($nom);
	$page = info_maj_cache($nom, $dir, $page);

	// reperer toutes les versions de numero majeur superieur ou egal
	// (a revoir quand on arrivera a SPIP V10 ...)
	$p = substr('0123456789', intval($maj));
	$p = ',/' . $file . '\D+([' . $p . ']+)\D+(\d+)(\D+(\d+))?.*?[.]zip",i';
	preg_match_all($p, $page, $m, PREG_SET_ORDER);
	$page = $page_majeure = '';

	// branche en cours d'utilisation
	$branche = implode('.', array_slice(explode('.', $version, 3), 0, 2));

	foreach ($m as $v) {
		$v = array_pad($v, 5, 0);
		[, $maj2, $min2, , $rev2] = $v;
		$branche_maj = $maj2 . '.' . $min2;
		$version_maj = $maj2 . '.' . $min2 . '.' . $rev2;
		// d'abord les mises à jour de la même branche
		if (
			(spip_version_compare($version, $version_maj, '<'))
			and (spip_version_compare($page, $version_maj, '<'))
			and spip_version_compare($branche, $branche_maj, '=')
		) {
			$page = $version_maj;
		}
		// puis les mises à jours majeures
		if (
			(spip_version_compare($version, $version_maj, '<'))
			and (spip_version_compare($page, $version_maj, '<'))
			and spip_version_compare($branche, $branche_maj, '<')
		) {
			$page_majeure = $version_maj;
		}
	}
	if (!$page and !$page_majeure) {
		return '';
	}

	$message = $page ? _T('nouvelle_version_spip', ['version' => $page]) . ($page_majeure ? ' | ' : '') : '';
	$message .= $page_majeure ? _T('nouvelle_version_spip_majeure', ['version' => $page_majeure]) : '';

	return "<a class='info_maj_spip' href='https://www.spip.net/fr_update' title='$page'>" . $message . '</a>';
}

/**
 * Vérifie que la liste $page des versions dans le fichier $nom est à jour.
 *
 * Ce fichier rajoute dans ce fichier l'aléa éphémère courant;
 * on teste la nouveauté par If-Modified-Since,
 * et seulement quand celui-ci a changé pour limiter les accès HTTP.
 * Si le fichier n'a pas été modifié, on garde l'ancienne version.
 *
 * @see info_maj()
 *
 * @param string $nom
 *     Nom du fichier contenant les infos de mise à jour.
 * @param string $dir
 * @param string $page
 * @return string
 *     Contenu du fichier de cache de l'info de maj de SPIP.
 */
function info_maj_cache($nom, $dir, $page = '') {
	include_spip('inc/acces');
	$alea_ephemere = charger_aleas();
	$re = '<archives id="a' . $alea_ephemere . '">';
	if (preg_match("/$re/", $page)) {
		return $page;
	}

	$url = _VERSIONS_SERVEUR . $dir . '/' . _VERSIONS_LISTE;
	$a = file_exists($nom) ? filemtime($nom) : '';
	include_spip('inc/distant');
	$res = recuperer_url_cache($url, ['if_modified_since' => $a]);
	// Si rien de neuf (ou inaccessible), garder l'ancienne
	if ($res) {
		$page = $res['page'] ?: $page;
	}
	// Placer l'indicateur de fraicheur
	$page = preg_replace('/^<archives.*?>/', $re, $page);
	sous_repertoire(_DIR_CACHE_XML);
	ecrire_fichier($nom, $page);

	return $page;
}
