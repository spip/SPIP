<?php

if (!defined("_ECRIRE_INC_VERSION")) return;

function msie_compat_ieconfig_metas($table){
	$table['msiecompat']['titre'] = _T('msiecompat:choix_titre');
	$table['msiecompat']['icone'] = 'msiecompat-16.png';
	$table['msiecompat']['metas_brutes'] = 'iecompat';
	
	return $table;
}

?>