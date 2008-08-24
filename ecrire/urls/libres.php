<?php


/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


/*

Ce jeu d'URLs est une variation de inc-urls-propres, qui ajoute
le suffixe '.html' aux adresses ;

*/
if (!defined("_ECRIRE_INC_VERSION")) return; // securiser

if (!defined('_MARQUEUR_URL'))
	define('_MARQUEUR_URL', false);

include_once dirname(__FILE__).'/propres.php';

// http://doc.spip.org/@urls_libres_dist
function urls_libres_dist(&$fond, $url) {
	$f = charger_fonction('propres', 'urls', true);
	if ($f) $f($fond, $url);
}

?>
