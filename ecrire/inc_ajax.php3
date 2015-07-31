<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_AJAX")) return;
define("_ECRIRE_INC_AJAX", "1");

function nettoyer_ajax() {
	$query = "DELETE FROM spip_ajax_fonc  WHERE date < DATE_SUB(NOW(), INTERVAL 2 HOUR)";
	$res = spip_query($query);
}



?>