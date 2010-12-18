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
Ce jeu d'URLs est une variante de inc-urls-propres, qui ajoute
le prefixe './?' aux adresses, ce qui permet de l'utiliser en
mode "Query-String", sans .htaccess ;

	<http://mon-site-spip/?-Rubrique->

Attention : le mode 'propres_qs' est moins fonctionnel que le mode 'propres' ou
'propres2'. Si vous pouvez utiliser le .htaccess, ces deux derniers modes sont
preferables au mode 'propres_qs'.
*/

define('URLS_PROPRES_QS_EXEMPLE', '?Titre-de-l-article');

if (!defined('_terminaison_urls_propres'))
	define ('_terminaison_urls_propres', '');

define ('_debut_urls_propres', './?');

// http://doc.spip.org/@urls_propres_qs_dist
function urls_propres_qs_dist($i, &$entite, $args='', $ancre='') {
	$f = charger_fonction('propres', 'urls');
	return $f($i, $entite, $args, $ancre);
}
?>
