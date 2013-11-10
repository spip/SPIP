<?php

if (!defined("_ECRIRE_INC_VERSION")) return;

function mots_ieconfig_metas($table){
	$table['mots']['titre'] = _T('mots:info_mots_cles');
	$table['mots']['icone'] = 'mot-16.png';
	$table['mots']['metas_brutes'] = 'articles_mots,config_precise_groupes,mots_cles_forums';
	
	return $table;
}

?>