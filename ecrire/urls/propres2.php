<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

/*
Ce jeu d'URLs est une variation de inc-urls-propres, qui ajoute
le suffixe '.html' aux adresses ;
*/

define('URLS_PROPRES2_EXEMPLE', 'Titre-de-l-article.html -Rubrique-.html');

if (!defined('_terminaison_urls_propres'))
	define ('_terminaison_urls_propres', '.html');

// http://doc.spip.org/@urls_propres2_dist
function urls_propres2_dist($i, &$entite, $args='', $ancre='') {
	$f = charger_fonction('propres', 'urls');
	return $f($i, $entite, $args, $ancre);
}

?>
