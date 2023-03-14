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

/**
 * Tester le deplacement restreint ou non
 * de l'objet en fonction de son statut
 *
 * @param string $objet
 * @param string $statut
 * @return bool
 */
function deplacement_restreint($objet, $statut) {

	return match ($objet) {
		'rubrique' => !$GLOBALS['connect_toutes_rubriques'],
		'article', 'site', 'syndic', 'breve' => $statut == 'publie',
		default => $statut ? $statut == 'publie' : false,
	};
}
