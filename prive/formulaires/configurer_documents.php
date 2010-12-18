<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

function formulaires_configurer_documents_charger_dist(){
	foreach(array(
		"documents_article",
		"documents_rubrique",
		"documents_date",
		) as $m)
		$valeurs[$m] = $GLOBALS['meta'][$m];

	return $valeurs;
}


function formulaires_configurer_documents_traiter_dist(){
	$res = array('editable'=>true);
	foreach(array(
		"documents_article",
		"documents_rubrique",
		"documents_date",
		) as $m)
		if (!is_null($v=_request($m)))
			ecrire_meta($m, $v=='oui'?'oui':'non');

	$res['message_ok'] = _T('config_info_enregistree');
	return $res;
}

