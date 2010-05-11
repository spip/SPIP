<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * lecture du fichier de configuration d'un plugin
 *
 * @staticvar array $infos
 * @staticvar array $plugin_xml_cache
 * @param string $plug
 * @param bool $force_reload
 * @param string $dir_plugins
 * @return array
 */
function plugins_get_infos_dist($plug, $force_reload=false, $dir_plugins = _DIR_PLUGINS){
	include_spip('inc/xml');
	static $infos=array();
	static $plugin_xml_cache=NULL;
	// cas d'un appel en dehors du repertoire plugin
	// cette solution n'est pas ideale.
	if (!isset($infos[$dir_plugins][$plug]) OR $force_reload){
		if ($plugin_xml_cache==NULL){
			$plugin_xml_cache = array();
			if (is_file($f=_DIR_TMP."plugin_xml_cache.gz")){
				lire_fichier($f,$contenu);
				$plugin_xml_cache = unserialize($contenu);
				if (!is_array($plugin_xml_cache)) $plugin_xml_cache = array();
			}
		}
		$ret = array();
		if (isset($plugin_xml_cache[$dir_plugins][$plug])){
			$info = $plugin_xml_cache[$dir_plugins][$plug];
			if (!$force_reload
				AND isset($info['filemtime'])
				AND @file_exists($f = $dir_plugins."$plug/plugin.xml")
				AND (@filemtime($f)<=$info['filemtime']))
				$ret = $info;
		}
		if (!count($ret)){
			if ((@file_exists($dir_plugins))&&(is_dir($dir_plugins))){
				if (@file_exists($f = $dir_plugins."$plug/plugin.xml")) {
					$arbre = spip_xml_load($f);
					if (!$arbre OR !isset($arbre['plugin']) OR !is_array($arbre['plugin']))
						$arbre = array('erreur' => array(_T('erreur_plugin_fichier_def_incorrect')." : $plug/plugin.xml"));
				}
				else {
					// pour arriver ici on l'a vraiment cherche...
					$arbre = array('erreur' => array(_T('erreur_plugin_fichier_def_absent')." : $plug/plugin.xml"));
				}
				$verifie_conformite = charger_fonction('verifie_conformite','plugins');
				$verifie_conformite($plug,$arbre,$dir_plugins);

				include_spip('inc/charsets');
				$ret['nom'] = charset2unicode(spip_xml_aplatit($arbre['nom']));
				$ret['version'] = trim(end($arbre['version']));
				if (isset($arbre['auteur']))
					$ret['auteur'] = spip_xml_aplatit($arbre['auteur']);
				if (isset($arbre['icon']))
					$ret['icon'] = spip_xml_aplatit($arbre['icon']);
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

				if ($t=@filemtime($f)){
					$ret['filemtime'] = $t;
					$plugin_xml_cache[$dir_plugins][$plug]=$ret;
					ecrire_fichier(_DIR_TMP."plugin_xml_cache.gz",serialize($plugin_xml_cache));
				}
			}
		}
		$infos[$dir_plugins][$plug] = $ret;
	}

	return $infos[$dir_plugins][$plug];
}

?>
