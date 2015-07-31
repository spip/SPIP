<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/actions');

// http://doc.spip.org/@exec_grouper_mots_dist
function exec_grouper_mots_dist()
{
	$id_groupe = intval(_request('id_groupe'));
	$cpt = spip_abstract_fetch(spip_query("SELECT COUNT(*) AS n FROM spip_mots WHERE id_groupe=$id_groupe"));
	if (! ($cpt = $cpt['n'])) ajax_retour('') ;
	$grouper_mots = charger_fonction('grouper_mots', 'inc');
	ajax_retour($grouper_mots($id_groupe, $cpt));
}
?>
