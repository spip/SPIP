<?php

/**
 * Gestion d'affichage d'un descriptif de plugin en ajax
 *
 * @package SPIP\Core\Exec
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/actions');

/**
 * Affichage de la description d'un plugin (en ajax)
 *
 * @uses plugins_get_infos_dist()
 * @uses plugins_afficher_plugin_dist()
 * @uses affiche_bloc_plugin()
 * @uses ajax_retour()
 */
function exec_info_plugin_dist() {
	if (!autoriser('configurer', '_plugins')) {
		include_spip('inc/minipres');
		echo minipres();
	} else {
		$plug = _DIR_RACINE . htmlspecialchars(_request('plugin'));
		$get_infos = charger_fonction('get_infos', 'plugins');
		$dir = '';
		if (strncmp($plug, _DIR_PLUGINS, strlen(_DIR_PLUGINS)) == 0) {
			$dir = _DIR_PLUGINS;
		} elseif (strncmp($plug, _DIR_PLUGINS_DIST, strlen(_DIR_PLUGINS_DIST)) == 0) {
			$dir = _DIR_PLUGINS_DIST;
		}
		if ($dir) {
			$plug = substr($plug, strlen($dir));
		}
		$info = $get_infos($plug, false, $dir);
		$afficher_plugin = charger_fonction('afficher_plugin', 'plugins');
		ajax_retour(affiche_bloc_plugin($plug, $info, $dir));
	}
}
