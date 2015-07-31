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
 * lecture du fichier de configuration d'un plugin
 *
 * @staticvar string $filecache
 * @staticvar array $cache
 * @param string|array|bool $plug
 * @param bool $reload
 * @param string $dir
 * @return array
 */
function plugins_get_infos_dist($plug=false, $reload=false, $dir = _DIR_PLUGINS, $clean_old=false){
	static $cache='';
	static $filecache = '';

	if ($cache===''){
		$filecache = _DIR_TMP."plugin_xml_cache.gz";
		if (is_file($filecache)){
			lire_fichier($filecache, $contenu);
			$cache = unserialize($contenu);
		}
		if (!is_array($cache)) $cache = array();
	}

	if (defined('_VAR_MODE') AND _VAR_MODE=='recalcul')
		$reload = true;

	if ($plug===false) {
		ecrire_fichier($filecache, serialize($cache));
		return $cache;
	}
	elseif (is_string($plug)) {
		$res = plugins_get_infos_un($plug, $reload, $dir, $cache);
	}
	elseif (is_array($plug)) {
		$res = false;
		if (!$reload) $reload = -1;
		foreach($plug as $nom)
		  $res |= plugins_get_infos_un($nom, $reload, $dir, $cache);
		// Nettoyer le cache des vieux plugins qui ne sont plus la
		if ($clean_old and count($cache[$dir])) {
			foreach (array_keys($cache[$dir]) as $p) {
				if (!in_array($p,$plug)) {
					unset($cache[$dir][$p]);
				}
			}
		}
	}
	if ($res) {
		ecrire_fichier($filecache, serialize($cache));
	}
	if (!isset($cache[$dir])) {
		return array();
	}
	return is_string($plug) ? $cache[$dir][$plug] : $cache[$dir];
}


function plugins_get_infos_un($plug, $reload, $dir, &$cache)
{
	if (!is_readable($file = "$dir$plug/" . ($desc = "paquet") . ".xml")) {
	  if (!is_readable($file = "$dir$plug/" . ($desc = "plugin") . ".xml"))
	    return false;
	}

	if (($time = intval(@filemtime($file))) < 0) return false;
	$md5 = md5_file($file);

	$pcache = isset($cache[$dir][$plug]) 
	  ? $cache[$dir][$plug] : array('filemtime' => 0, 'md5_file' => '');

	// si le cache est valide
	if ((intval($reload) <= 0)
		AND ($time > 0)
		AND ($time <= $pcache['filemtime'])
	  AND $md5==$pcache['md5_file'])
		return false;

	// si on arrive pas a lire le fichier, se contenter du cache
	if (!($texte = spip_file_get_contents($file))) {
		return false;
	}

	$f = charger_fonction('infos_' . $desc, 'plugins');
	$ret = $f($texte, $plug, $dir);
	$ret['filemtime'] = $time;
	$ret['md5_file'] = $md5;
	$diff = ($ret != $pcache);

	if ($diff) {
		$cache[$dir][$plug] = $ret;
#		echo count($cache[$dir]), $dir,$plug, " $reloadc<br>"; 
	}
	return $diff;
}
?>
