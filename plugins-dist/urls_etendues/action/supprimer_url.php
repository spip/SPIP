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

function action_supprimer_url_dist($arg=null) {

	if (is_null($arg)){
		// Rien a faire ici pour le moment
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}
	$arg = explode('-',$arg);
	$id_parent = array_shift($arg);
	$url = implode('-',$arg);

	$where = 'id_parent='.intval($id_parent)." AND url=".Sql::quote($url);
	if($row = Sql::fetsel('*','spip_urls',$where)){

		if (autoriser('modifier',$row['type'],$row['id_objet'])){
			Sql::delete('spip_urls',$where);
		}
		else
			spip_log('supprimer sans autorisation l\'URL '.$id_parent."://".$url,"urls."._LOG_ERREUR);

	}
	else
		spip_log('Impossible de supprimer une URL inconnue '.$id_parent."://".$url,"urls."._LOG_INFO_IMPORTANTE);
	

}
