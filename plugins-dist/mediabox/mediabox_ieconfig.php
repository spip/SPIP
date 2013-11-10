<?php

if (!defined("_ECRIRE_INC_VERSION")) return;

// On déclare ici la config du core
function mediabox_ieconfig_metas($table){
	$table['mediabox']['titre'] = _T('mediabox:titre_menu_box');
	$table['mediabox']['icone'] = 'mediabox-16.png';
	$table['mediabox']['metas_serialize'] = 'mediabox';
	
	return $table;
}

?>