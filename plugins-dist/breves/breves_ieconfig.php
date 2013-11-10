<?php

if (!defined("_ECRIRE_INC_VERSION")) return;

// On déclare ici la config du core
function breves_ieconfig_metas($table){
	$table['breves']['titre'] = _T('breves:titre_breves');
	$table['breves']['icone'] = 'breve-16.png';
	$table['breves']['metas_brutes'] = 'activer_breves';
	
	return $table;
}

?>