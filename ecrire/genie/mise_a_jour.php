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


// Determiner si une nouvelle version de SPIP est disponible
// sans demander tout le temps au serveur de versions si leur liste a change'.

define('_VERSIONS_SERVEUR', 'http://files.spip.org/');
define('_VERSIONS_LISTE', 'archives.xml');

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

// Verifie que la liste $page des versions dans le fichier $nom est a jour
// Ce fichier rajoute dans ce fichier l'alea ephemere courant;
// on teste la nouveaute par If-Modified-Since,
// et seulement quand celui-ci a change' pour limiter les acces HTTP

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
