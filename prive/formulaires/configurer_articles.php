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

function formulaires_configurer_articles_charger_dist(){
	foreach(array(
		"articles_surtitre",
		"articles_soustitre",
		"articles_descriptif",
		"articles_chapeau",
		"articles_texte",
		"articles_ps",
		"articles_redac",
		"articles_urlref",
		"post_dates",
		"articles_redirection",
		) as $m)
		$valeurs[$m] = $GLOBALS['meta'][$m];

	return $valeurs;
}


function formulaires_configurer_articles_traiter_dist(){
	$res = array('editable'=>true);
	$purger_skel = false;
	// Purger les squelettes si un changement de meta les affecte
	if ($i = _request('post_dates') AND ($i != $GLOBALS['meta']["post_dates"]))
		$purger_skel = true;

	foreach(array(
		"articles_surtitre",
		"articles_soustitre",
		"articles_descriptif",
		"articles_chapeau",
		"articles_texte",
		"articles_ps",
		"articles_redac",
		"articles_urlref",
		"post_dates",
		"articles_redirection",
		) as $m)
		if (!is_null($v=_request($m)))
			ecrire_meta($m, $v=='oui'?'oui':'non');

	if ($purger_skel) {
		include_spip('inc/invalideur');
		purger_repertoire(_DIR_SKELS);
	}

	$res['message_ok'] = _T('config_info_enregistree');
	return $res;
}

