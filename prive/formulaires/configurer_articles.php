<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

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

	$res['message_ok'] = _T('config_info_enregistree');
	return $res;
}

