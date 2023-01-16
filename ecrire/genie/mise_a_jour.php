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
	$maj = info_maj($GLOBALS['spip_version_branche']);
	ecrire_meta('info_maj_spip', $maj ? ($GLOBALS['spip_version_branche'] . "|$maj") : '', 'non');

	mise_a_jour_ecran_securite();

	spip_log('Verification version SPIP : ' . ($maj ?: 'version a jour'), 'verifie_maj');

	return 1;
}

// TODO : fournir une URL sur spip.net pour maitriser la diffusion d'une nouvelle version de l'ecran via l'update auto
// ex : https://www.spip.net/auto-update/ecran_securite.php
define('_URL_ECRAN_SECURITE', 'https://git.spip.net/spip-contrib-outils/securite/raw/branch/master/ecran_securite.php');
define('_VERSIONS_SERVEUR', 'https://www.spip.net/spip_loader.api');
define('_VERSIONS_LISTE', 'spip_versions_list.json');

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
 * @param string $version
 *      La version reçue ici est sous la forme x.y.z
 *      On la transforme par la suite pour avoir des integer ($maj, $min, $rev)
 *      et ainsi pouvoir mieux les comparer
 *
 * @return string
 */
function info_maj($version) {
	include_spip('inc/plugin');

	$nom = _DIR_CACHE . _VERSIONS_LISTE;
	$contenu = !file_exists($nom) ? '' : file_get_contents($nom);
	$contenu = info_maj_cache($nom, $contenu);
	try {
		$contenu = json_decode($contenu, true, 512, JSON_THROW_ON_ERROR);
	} catch (JsonException $e) {
		spip_log('Failed to parse Json data : ' . $e->getMessage(), 'verifie_maj');
		return '';
	}

	$liste = $liste_majeure = '';

	// branche en cours d'utilisation
	$branche = implode('.', array_slice(explode('.', $version, 3), 0, 2));

	foreach ($contenu['versions'] as $v => $path) {
		$v = explode('.', $v);
		[$maj2, $min2, $rev2] = $v;
		$branche_maj = $maj2 . '.' . $min2;
		$version_maj = $maj2 . '.' . $min2 . '.' . $rev2;
		// d'abord les mises à jour de la même branche
		if (
			(spip_version_compare($version, $version_maj, '<'))
			and (spip_version_compare($liste, $version_maj, '<'))
			and spip_version_compare($branche, $branche_maj, '=')
		) {
			$liste = $version_maj;
		}
		// puis les mises à jours majeures
		if (
			(spip_version_compare($version, $version_maj, '<'))
			and (spip_version_compare($liste, $version_maj, '<'))
			and spip_version_compare($branche, $branche_maj, '<')
		) {
			$liste_majeure = $version_maj;
		}
	}
	if (!$liste and !$liste_majeure) {
		return '';
	}

	$message = $liste ? _T('nouvelle_version_spip', ['version' => $liste]) . ($liste_majeure ? ' | ' : '') : '';
	$message .= $liste_majeure ? _T('nouvelle_version_spip_majeure', ['version' => $liste_majeure]) : '';

	return "<a class='info_maj_spip' href='https://www.spip.net/fr_update' title='$liste'>" . $message . '</a>';
}

/**
 * Vérifie que la liste $liste des versions dans le fichier $nom est à jour.
 *
 * On teste la nouveauté par If-Modified-Since,
 * et seulement quand celui-ci a changé pour limiter les accès HTTP.
 * Si le fichier n'a pas été modifié, on garde l'ancienne version.
 *
 * @see info_maj()
 *
 * @param string $nom
 *     Nom du fichier contenant les infos de mise à jour.
 * @param string $dir
 * @param string $contenu
 * @return string
 *     Contenu du fichier de cache de l'info de maj de SPIP.
 */
function info_maj_cache($nom, $contenu = '') {
	$a = file_exists($nom) ? filemtime($nom) : '';
	include_spip('inc/distant');
	$res = recuperer_url_cache(_VERSIONS_SERVEUR, ['if_modified_since' => $a]);
	// Si rien de neuf (ou inaccessible), garder l'ancienne
	if ($res) {
		$contenu = $res['page'] ?: $contenu;
	}
	ecrire_fichier($nom, $contenu);

	return $contenu;
}
