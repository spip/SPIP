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
 * @staticvar array $infos
 * @staticvar array $plugin_xml_cache
 * @param string $plug
 * @param bool $force_reload
 * @param string $dir_plugins
 * @param string $filename
 * @return array
 */
function plugins_get_infos_dist($plug, $force_reload=false, $dir_plugins = _DIR_PLUGINS, $filename='plugin.xml'){

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
	$force_reload |= !isset($cache[$dir_plugins][$plug]['filemtime']);
 
	$desc = "$dir_plugins$plug/$filename";
	if (!file_exists($desc))
		return false;
	$time = intval(@filemtime($desc));

	if (!$force_reload
	AND ($time > 0)
	AND ($time <= $cache[$dir_plugins][$plug]['filemtime'])) {
		return $cache[$dir_plugins][$plug];
	}

	include_spip('inc/xml');
	$arbre = ($time < 0) ? false : spip_xml_load($desc);
	$verifie_conformite = charger_fonction('verifie_conformite','plugins');
	$verifie_conformite($plug, $arbre, $dir_plugins);

	include_spip('inc/charsets');

	$ret = array('nom' => charset2unicode(spip_xml_aplatit($arbre['nom'])),
		     'version' => trim(end($arbre['version'])),
		     'filemtime' => $time
		     );

	if (isset($arbre['auteur']))
		$ret['auteur'] = spip_xml_aplatit($arbre['auteur']);
	if (isset($arbre['icon']))
		$ret['icon'] = trim(spip_xml_aplatit($arbre['icon']));
	if (isset($arbre['description']))
		$ret['description'] = spip_xml_aplatit($arbre['description']);
	if (isset($arbre['lien']))
		$ret['lien'] = join(' ',$arbre['lien']);
	if (isset($arbre['etat']))
		$ret['etat'] = trim(end($arbre['etat']));
	if (isset($arbre['options']))
		$ret['options'] = $arbre['options'];
	if (isset($arbre['licence']))
		$ret['licence'] = spip_xml_aplatit($arbre['licence']);
	if (isset($arbre['install']))
		$ret['install'] = $arbre['install'];
	if (isset($arbre['config']))
		$ret['config'] = spip_xml_aplatit($arbre['config']);
	if (isset($arbre['meta']))
		$ret['meta'] = spip_xml_aplatit($arbre['meta']);
	if (isset($arbre['fonctions']))
		$ret['fonctions'] = $arbre['fonctions'];
	$ret['prefix'] = trim(array_pop($arbre['prefix']));
	if (isset($arbre['pipeline']))
		$ret['pipeline'] = $arbre['pipeline'];
	if (isset($arbre['erreur']))
		$ret['erreur'] = $arbre['erreur'];
	if (isset($arbre['version_base']))
		$ret['version_base'] = trim(end($arbre['version_base']));
	$ret['necessite'] = $arbre['necessite'];
	$ret['utilise'] = $arbre['utilise'];
	$ret['path'] = $arbre['path'];
	if (isset($arbre['noisette']))
		$ret['noisette'] = $arbre['noisette'];

	$extraire_boutons = charger_fonction('extraire_boutons','plugins');
	$les_boutons = $extraire_boutons($arbre);
	$ret['bouton'] = $les_boutons['bouton'];
	$ret['onglet'] = $les_boutons['onglet'];

	if (isset($arbre['erreur'])) {
		spip_log("get_infos $plug " . @join(' ', $arbre['erreur']));
	} else {
		$cache[$dir_plugins][$plug] = $ret;
		ecrire_fichier($filecache, serialize($cache));
	}
	return $ret;
}
?>
