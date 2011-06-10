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

/**
 * lecture du fichier de configuration d'un plugin
 *
 * @staticvar string $filecache
 * @staticvar array $cache
 * @param string|array|false $plug
 * @param bool $reload
 * @param string $dir
 * @return array 
 */
function plugins_get_infos_dist($plug=false, $reload=false, $dir = _DIR_PLUGINS){
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

	if ($plug===false) {
		ecrire_fichier($filecache, serialize($cache));
		return $cache;
	} elseif (is_string($plug)) {
		$res = plugins_get_infos_un($plug, $reload, $dir, $cache);
	} else  {
		$res = false;
		if (!$reload) $reload = -1;
		foreach($plug as $nom)
		  $res |= plugins_get_infos_un($nom, $reload, $dir, $cache);
	}
	if ($res) {
		ecrire_fichier($filecache, serialize($cache));
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

	$pcache = isset($cache[$dir][$plug]) 
	  ? $cache[$dir][$plug] : array('filemtime' => 0);

	if (((intval($reload) <= 0)
		AND ($time > 0)
		AND ($time <= $pcache['filemtime']))
	OR (!($texte = spip_file_get_contents($file)))) {
		return false;
	}

	$f = charger_fonction('infos_' . $desc, 'plugins');
	$ret = $f($texte, $plug, $dir);
	$ret['filemtime'] = $time;
	$diff = ($ret != $pcache);

	if ($diff) {
		$cache[$dir][$plug] = $ret;
#		echo count($cache[$dir]), $dir,$plug, " $reloadc<br>"; 
	}
	return $diff;
}
?>
