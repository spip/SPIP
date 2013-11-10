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

// http://doc.spip.org/@action_instituer_syndic_article_dist
function action_urls_actualiser_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	if (!defined('_VAR_URLS')) define('_VAR_URLS',true);
	$type = $id = "";
	$res = Sql::select("type,id_objet","spip_urls","","","type,id_objet");
	while ($row = Sql::fetch($res)){
		if ($row['id_objet']!==$id
			OR $row['type']!==$type){
			$id = $row['id_objet'];
			$type = $row['type'];
			generer_url_entite($id,$type,"","",true);
		}
	}
}

?>
