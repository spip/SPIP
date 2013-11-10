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

function liens_implicite_site_dist($texte,$id,$type,$args,$ancre,$connect=''){
	if (!$id = intval($id))
		return false;
	$url = Sql::getfetsel('url_site', 'spip_syndic', "id_syndic=".intval($id),'','','','',$connect);
	return $url;
}

?>