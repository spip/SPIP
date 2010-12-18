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

# afficher les sous-rubriques d'une rubrique (composant du mini-navigateur)

// http://doc.spip.org/@exec_plonger_dist
function exec_plonger_dist()
{
	include_spip('inc/actions');
	
	$rac = _request('rac');
	$id = intval(_request('id'));
	$exclus = intval(_request('exclus'));
	$col = intval(_request('col'));
	$do  = _request('do');
	if (preg_match('/^\w*$/', $do)) {
		if (!$do) $do = 'aff';

		$plonger = charger_fonction('plonger', 'inc');
		$r = $plonger($id, htmlentities($rac), array(), $col, $exclus, $do);
	} else $r = '';

	ajax_retour($r);
}
?>
