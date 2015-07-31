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


global $array_server;

// fonction de declaration du connecteur, utilisee a la place de config/array.php
// http://doc.spip.org/@req_for_connect_dist
function req_for_connect_dist() {
	if (!defined("_ECRIRE_INC_VERSION")) return;
	$GLOBALS['spip_connect_version'] = 0.1;
	spip_connect_db('host','port','login','pass','base','array', '','');
}

?>