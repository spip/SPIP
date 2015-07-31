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

# afficher un mini-navigateur de rubriques

// http://doc.spip.org/@exec_selectionner_dist
function exec_selectionner_dist()
{
	$id = intval(_request('id'));
	$exclus = intval(_request('exclus'));
	$type = _request('type');
	$rac = _request('racine');
	$do  = _request('do');
	if (preg_match('/^\w*$/', $do)) {
		if (!$do) $do = 'aff';

		$selectionner = charger_fonction('selectionner', 'inc');

		$r = $selectionner($id, "choix_parent", $exclus, $rac, $type!='breve', $do);
	} else $r = '';
	ajax_retour($r);
}
?>
