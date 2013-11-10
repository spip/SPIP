<?php

if (!defined("_ECRIRE_INC_VERSION")) return;

function urls_ieconfig_metas($table){
	$table['urls']['titre'] = _T('urls:titre_type_urls');
	$table['urls']['icone'] = 'url-16.png';
	$table['urls']['metas_brutes'] = 'type_urls,urls_activer_controle';
	$table['urls']['metas_serialize'] = 'urls_propres,urls_arbo';
	
	return $table;
}

?>