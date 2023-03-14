<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
include_spip('inc/presentation');

function formulaires_configurer_visiteurs_charger_dist() {
	$valeurs = [];
	if (avoir_visiteurs(false, false)) {
		$valeurs['editable'] = false;
	}

	foreach (
		[
			'accepter_visiteurs'
		] as $m
	) {
		$valeurs[$m] = $GLOBALS['meta'][$m];
	}

	return $valeurs;
}


function formulaires_configurer_visiteurs_traiter_dist() {
	$res = ['editable' => true];
	// Modification du reglage accepter_inscriptions => vider le cache
	// (pour repercuter la modif sur le panneau de login)
	if (
		(($i = _request('accepter_visiteurs'))
		&& $i != $GLOBALS['meta']['accepter_visiteurs'])
	) {
		include_spip('inc/invalideur');
		suivre_invalideur('1'); # tout effacer
	}

	foreach (
		[
			'accepter_visiteurs',
		] as $m
	) {
		if (!is_null($v = _request($m))) {
			ecrire_meta($m, $v == 'oui' ? 'oui' : 'non');
		}
	}

	$res['message_ok'] = _T('config_info_enregistree');

	return $res;
}
