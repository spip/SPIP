<?php

if (!defined('_ECRIRE_INC_VERSION')) return;


function compagnon_upgrade($nom_meta_base_version, $version_cible){
	
	$maj = array();
	$maj['create'] = array(
		array('compagnon_create')
	);
	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

function compagnon_create() {
	include_spip('inc/config');
	if (Sql::getfetsel('id_rubrique', 'spip_rubriques', '', '', '', '0,1')) {
		ecrire_config('compagnon/config/activer', 'non');
	} else {
		ecrire_config('compagnon/config/activer', 'oui');
	}
}

function compagnon_vider_tables($nom_meta_base_version) {
	effacer_meta("compagnon");
	effacer_meta($nom_meta_base_version);
}
?>
