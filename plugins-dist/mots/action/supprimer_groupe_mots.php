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

include_spip('inc/filtres');


function action_supprimer_groupe_mots_dist($id_groupe=null){

	if (is_null($id_groupe)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$id_groupe = $securiser_action();
	}

	if (autoriser('supprimer','groupemots',$id_groupe)){
		Sql::delete("spip_groupes_mots", "id_groupe=" .intval($id_groupe));
	}
	else
	 spip_log("action_supprimer_groupe_mots_dist $id_groupe interdit",_LOG_INFO_IMPORTANTE);
}


?>
