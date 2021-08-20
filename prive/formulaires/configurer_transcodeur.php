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

function formulaires_configurer_transcodeur_charger_dist() {
	$valeurs = [
		'charset' => $GLOBALS['meta']['charset'],
	];

	return $valeurs;
}

function formulaires_configurer_transcodeur_verifier_dist() {
	include_spip('inc/charsets');

	$erreurs = [];
	if (!$charset = _request('charset')) {
		$erreurs['charset'] = _T('info_obligatoire');
	} elseif ($charset != 'utf-8' and !load_charset($charset)) {
		$erreurs['charset'] = _T('utf8_convert_erreur_orig', ['charset' => entites_html($charset)]);
	}

	return $erreurs;
}


function formulaires_configurer_transcodeur_traiter_dist() {
	$res = ['editable' => true];
	ecrire_meta('charset', _request('charset'));
	$res['message_ok'] = _T('config_info_enregistree');

	return $res;
}
