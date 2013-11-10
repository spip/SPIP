<?php

if (!defined("_ECRIRE_INC_VERSION")) return;

function stats_ieconfig_metas($table){
	$table['statistiques']['titre'] = _T('statistiques:info_forum_statistiques');
	$table['statistiques']['icone'] = 'statistique-16.png';
	$table['statistiques']['metas_brutes'] = 'activer_statistiques,activer_captures_referers';
	
	return $table;
}

?>