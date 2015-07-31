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

Ce jeu d'URLs est une variation de inc-urls-propres mais les urls 
de differents types ne sont PAS distinguees par des marqueurs (_,-,+, etc.) ;

*/
if (!defined("_ECRIRE_INC_VERSION")) return; // securiser

if (!defined('_MARQUEUR_URL'))
	define('_MARQUEUR_URL', false);

// http://doc.spip.org/@urls_libres_dist
function urls_libres_dist($i, &$entite, $args='', $ancre='') {
	$f = charger_fonction('propres', 'urls');
	return $f($i, $entite, $args, $ancre);
}

?>
