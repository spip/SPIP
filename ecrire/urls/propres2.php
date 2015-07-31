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


/*

Ce jeu d'URLs est une variation de inc-urls-propres, qui ajoute
le suffixe '.html' aux adresses ;

*/
if (!defined("_ECRIRE_INC_VERSION")) return; // securiser

if (!defined('_terminaison_urls_propres'))
	define ('_terminaison_urls_propres', '.html');

include_spip('urls/propres');

?>
