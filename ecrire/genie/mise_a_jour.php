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

/**
 * Vérification en tâche de fond des différentes mise à jour.
 *
 * @package SPIP\Core\Genie\Mise_a_jour
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Verifier si une mise a jour est disponible
 *
 * @param int $t
 * @return int
 */
function genie_mise_a_jour_dist($t) {
	include_spip('inc/meta');
	$maj = info_maj ('spip', 'SPIP', $GLOBALS['spip_version_branche']);
	ecrire_meta('info_maj_spip',$maj?($GLOBALS['spip_version_branche']."|$maj"):"",'non');

	spip_log("Verification version SPIP : ".($maj?$maj:"version a jour"),"verifie_maj");
	return 1;
}



define('_VERSIONS_SERVEUR', 'http://files.spip.org/');
define('_VERSIONS_LISTE', 'archives.xml');

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
function info_maj ($dir, $file, $version){
	include_spip('inc/plugin');

	list($maj,$min,$rev) = preg_split('/\D+/', $version);

	$nom = _DIR_CACHE_XML . _VERSIONS_LISTE;
	$page = !file_exists($nom) ? '' : file_get_contents($nom);
	$page = info_maj_cache($nom, $dir, $page);

	// reperer toutes les versions de numero majeur superieur ou egal
	// (a revoir quand on arrivera a SPIP V10 ...)
	$p = substr("0123456789", intval($maj));
	$p = ',/' . $file . '\D+([' . $p . ']+)\D+(\d+)(\D+(\d+))?.*?[.]zip",i';
	preg_match_all($p, $page, $m,  PREG_SET_ORDER);
	$page = '';
	foreach ($m as $v) {
		list(, $maj2, $min2,, $rev2) = $v;
		$version_maj = $maj2 . '.' . $min2 . '.' . $rev2;
		if ((spip_version_compare($version, $version_maj, '<'))
		AND (spip_version_compare($page, $version_maj, '<')))
			$page = $version_maj;
	}

	if (!$page) return "";
	return "<a class='info_maj_spip' href='"._VERSIONS_SERVEUR."$dir' title='$page'>" .
		_T('nouvelle_version_spip',array('version'=>$page)) .
	    '</a>';
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
function info_maj_cache($nom, $dir, $page='')
{
	$re = '<archives id="a' . $GLOBALS['meta']["alea_ephemere"] . '">';
	if (preg_match("/$re/", $page)) return $page;

	$url = _VERSIONS_SERVEUR . $dir . '/' . _VERSIONS_LISTE;
	$a = file_exists($nom) ? filemtime($nom) : '';
	include_spip('inc/distant');
	$res = recuperer_lapage($url, false, 'GET', _COPIE_LOCALE_MAX_SIZE, '',false, $a);
	// Si rien de neuf (ou inaccessible), garder l'ancienne
	if ($res) list(, $page) = $res;
	// Placer l'indicateur de fraicheur
	$page = preg_replace('/^<archives.*?>/', $re, $page);
	sous_repertoire(_DIR_CACHE_XML);
	ecrire_fichier($nom, $page);
	return $page;
}

?>
