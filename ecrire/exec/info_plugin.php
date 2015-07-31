<?php

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/plugin');
include_spip('inc/actions');

// http://doc.spip.org/@exec_info_plugin_dist
function exec_info_plugin_dist() {
	if (!autoriser('configurer', 'plugins')) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}

	$plug = _request('plug');
	$info = plugin_get_infos($plug);
	ajax_retour(affiche_bloc_plugin($plug, $info));
}

?>