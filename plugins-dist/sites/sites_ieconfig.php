<?php

if (!defined("_ECRIRE_INC_VERSION")) return;

function sites_ieconfig_metas($table){
	$table['sites']['titre'] = _T('sites:titre_referencement_sites');
	$table['sites']['icone'] = 'site-16.png';
	$table['sites']['metas_brutes'] = 'activer_sites,activer_syndic,proposer_sites,moderation_sites';
	
	return $table;
}

?>