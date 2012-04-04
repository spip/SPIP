<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/actions');
// http://doc.spip.org/@exec_info_plugin_dist
function exec_info_plugin_dist() {
	if (!autoriser('configurer', '_plugins')) {
		include_spip('inc/minipres');
		echo minipres();
	} else {
		$plug = _DIR_RACINE . _request('plugin');
		$get_infos = charger_fonction('get_infos','plugins');
		$dir = "";
		if (strncmp($plug,_DIR_PLUGINS,strlen(_DIR_PLUGINS))==0)
			$dir = _DIR_PLUGINS;
		elseif (strncmp($plug,_DIR_PLUGINS_DIST,strlen(_DIR_PLUGINS_DIST))==0)
			$dir = _DIR_PLUGINS_DIST;
		if ($dir)
			$plug = substr($plug,strlen($dir));
		$info = $get_infos($plug,false,$dir);
		$afficher_plugin = charger_fonction("afficher_plugin","plugins");
		ajax_retour(affiche_bloc_plugin($plug, $info, $dir));
	}
}

?>
