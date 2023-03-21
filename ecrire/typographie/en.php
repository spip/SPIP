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

// rien sauf les "~" et "-,"

function typographie_en_dist($letexte) {

	// zouli apostrophe
	$letexte = str_replace("'", '&#8217;', (string) $letexte);

	$cherche1 = [
		'/ --?,/S'
	];
	$remplace1 = [
		'~\0'
	];
	$letexte = preg_replace($cherche1, $remplace1, $letexte);

	$letexte = str_replace('&nbsp;', '~', $letexte);
	$letexte = preg_replace('/ *~+ */', '~', $letexte);

	$cherche2 = [
		'/([^-\n]|^)--([^-]|$)/',
		'/~/'
	];
	$remplace2 = [
		'\1&mdash;\2',
		'&nbsp;'
	];

	return preg_replace($cherche2, $remplace2, $letexte);
}
