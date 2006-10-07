<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

function exec_gadgets_dist()
{
	$id_rubrique = intval(_request('id_rubrique'));
	$gadget = _request('gadget');
	$gadgets = charger_fonction('gadgets', 'inc');

	ajax_retour($gadgets($id_rubrique, $gadget));
}
?>
