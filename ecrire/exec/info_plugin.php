<?php

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/plugin');

function exec_info_plugin() {
	$plug = _request('plug');
	$info = plugin_get_infos($plug);
	ajax_retour(affiche_bloc_plugin($plug_file, $info));
}

?>