<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function formulaires_configurer_flux_charger_dist() {
	$valeurs = [];
	foreach (
		[
			'syndication_integrale'
		] as $m
	) {
		$valeurs[$m] = $GLOBALS['meta'][$m];
	}

	return $valeurs;
}


function formulaires_configurer_flux_traiter_dist() {
	$res = ['editable' => true];
	foreach (
		[
			'syndication_integrale',
		] as $m
	) {
		if (!is_null($v = _request($m))) {
			ecrire_meta($m, $v == 'oui' ? 'oui' : 'non');
		}
	}

	$res['message_ok'] = _T('config_info_enregistree');

	return $res;
}
