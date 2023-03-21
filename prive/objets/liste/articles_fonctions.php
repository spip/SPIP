<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function defaut_tri_defined($defaut) {
	if (!defined('_TRI_ARTICLES_RUBRIQUE')) {
		return $defaut;
	}

	$sens = 1;
	$tri = trim((string) _TRI_ARTICLES_RUBRIQUE);
	$tri = explode(' ', $tri);
	if (strncasecmp(end($tri), 'DESC', 4) == 0) {
		$sens = -1;
		array_pop($tri);
	} elseif (strncasecmp(end($tri), 'ASC', 3) == 0) {
		$sens = 1;
		array_pop($tri);
	}
	$tri = implode(' ', $tri);
	$tri = [$tri => $sens];
	foreach ($defaut as $n => $s) {
		if (!isset($tri[$n])) {
			$tri[$n] = $s;
		}
	}

	return $tri;
}

function defaut_tri_par($par, $defaut) {
	if (!defined('_TRI_ARTICLES_RUBRIQUE')) {
		return $par;
	}
	$par = array_keys($defaut);

	return reset($par);
}
