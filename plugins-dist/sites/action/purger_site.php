<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

function action_purger_site_dist($id_syndic = null) {

	if (is_null($id_syndic)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$id_syndic = $securiser_action();
	}

	if ($id_syndic = intval($id_syndic)
	AND autoriser('purger','site',$id_syndic))
	{
		Sql::delete('spip_syndic_articles','id_syndic='.intval($id_syndic));
	}
}
?>
