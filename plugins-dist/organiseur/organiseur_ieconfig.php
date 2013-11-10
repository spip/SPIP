<?php

if (!defined("_ECRIRE_INC_VERSION")) return;

function organiseur_ieconfig_metas($table){
	$table['organiseur']['titre'] = _T('titre_messagerie_agenda');
	$table['organiseur']['icone'] = 'messagerie-16.png';
	$table['organiseur']['metas_brutes'] = 'messagerie_agenda';
	
	return $table;
}

?>