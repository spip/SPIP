<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/actions');
// http://doc.spip.org/@exec_info_plugin_dist
function exec_info_plugin_dist() {
	if (!autoriser('configurer', 'plugins')) {
		include_spip('inc/minipres');
		echo minipres();
	} else {
		$plug = _DIR_RACINE . _request('plugin');
		$get_infos = charger_fonction('get_infos','plugins');
		$info = $get_infos($plug);
		$afficher_plugin = charger_fonction("afficher_plugin","plugins");
		ajax_retour(affiche_bloc_plugin($plug, $info));
	}
}

?>
