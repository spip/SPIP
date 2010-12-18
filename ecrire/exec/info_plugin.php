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

include_spip('inc/actions');
// http://doc.spip.org/@exec_info_plugin_dist
function exec_info_plugin_dist() {
	if (!autoriser('configurer', 'plugins')) {
		include_spip('inc/minipres');
		echo minipres();
	} else {
		$plug = _request('plugin');
		$dir_plugins = _DIR_PLUGINS;
		if (strncmp($plug, _DIR_EXTENSIONS,strlen(_DIR_EXTENSIONS)) === 0) {
			$dir_plugins = _DIR_EXTENSIONS;
			$plug = substr($plug,strlen(_DIR_EXTENSIONS));
		}
		else if (strncmp($plug, _DIR_PLUGINS,strlen(_DIR_PLUGINS)) === 0) {
			$dir_plugins = _DIR_PLUGINS;
			$plug = substr($plug,strlen(_DIR_PLUGINS));
		}
		// sinon c'est louche, mais on essaye quand meme

		$get_infos = charger_fonction('get_infos','plugins');
		$info = $get_infos($plug, false, $dir_plugins);
		$afficher_plugin = charger_fonction("afficher_plugin","plugins");
		ajax_retour(affiche_bloc_plugin($plug, $info));
	}
}

?>
