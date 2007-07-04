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

# afficher les sous-rubriques d'une rubrique (composant du mini-navigateur)

// http://doc.spip.org/@exec_plonger_dist
function exec_plonger_dist()
{
	$rac = _request('rac');
	$id = intval(_request('id'));
	$exclus = intval(_request('exclus'));
	$col = intval(_request('col'));

	include_spip('inc/actions');
	$plonger = charger_fonction('plonger', 'inc');
	ajax_retour($plonger($id, htmlentities($rac), array(), $col, $exclus));
}
?>
