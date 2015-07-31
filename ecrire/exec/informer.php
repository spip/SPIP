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

# Les informations d'une rubrique selectionnee dans le mini navigateur

function exec_informer_dist()
{

	$id = intval(_request('id'));
	$col = intval(_request('col'));
	$exclus = intval(_request('exclus'));

	$f = charger_fonction('informer', 'inc');
	return $f($id, $col, $exclus, _request('rac'), _request('type'));
}

?>
