<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;
include_spip('inc/presentation');

function formulaires_configurer_moderniseur_charger_dist(){
	$valeurs = array(
		'version_html_max' => html5_permis() ? 'html5' : 'html4',
		);
	return $valeurs;
}


function formulaires_configurer_moderniseur_traiter_dist(){
	$res = array('editable'=>true);
	foreach(array(
		"version_html_max",
		) as $m)
		if (!is_null($v=_request($m)))
			ecrire_meta($m, $v=='html5'?'html5':'html4');

	$res['message_ok'] = _T('config_info_enregistree');
	return $res;
}

