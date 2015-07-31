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

include_spip('inc/actions');

# Les informations d'une rubrique selectionnee dans le mini navigateur

// http://doc.spip.org/@exec_informer_dist
function exec_informer_dist()
{
	$id = intval(_request('id'));
	$col = intval(_request('col'));
	$exclus = intval(_request('exclus'));
	$do = _request('do');

	if (preg_match('/^\w*$/', $do)) {
		if (!$do) $do = 'aff';
	
		$informer = charger_fonction('informer', 'inc');
		$res = $informer($id, $col, $exclus, _request('rac'), _request('type'), $do);
	} else $res = '';
	ajax_retour($res);
}
?>
