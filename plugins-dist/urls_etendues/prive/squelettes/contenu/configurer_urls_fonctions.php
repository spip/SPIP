<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

function choisir_form_configuration($type_url){
	if (include_spip("urls/$type_url")
	  AND defined($c='URLS_'.strtoupper($type_url).'_CONFIG'))
		return "configurer_urls_".strtolower(constant($c));

	return '';
}
?>