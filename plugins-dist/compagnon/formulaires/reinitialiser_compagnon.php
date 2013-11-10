<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

function formulaires_reinitialiser_compagnon_charger() {
	return array('qui' => 'moi');
}

function formulaires_reinitialiser_compagnon_traiter() {
	$qui = _request('qui');
	include_spip('inc/config');
	if ($qui == 'moi') {
		effacer_config('compagnon/' . $GLOBALS['visiteur_session']['id_auteur']);
	}
	if ($qui == 'tous') {
		$config = lire_config('compagnon/config');
		effacer_config('compagnon');
		ecrire_config('compagnon/config', $config);
	}
	
	return array(
		'message_ok' => _T('compagnon:reinitialisation_ok')
	);
}

?>
