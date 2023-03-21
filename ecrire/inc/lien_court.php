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

/*
 * Cette fonction prend une URL et la raccourcit si elle est trop longue
 * de cette maniere au lieu d'afficher
 * "http://zoumzamzouilam/truc/chose/machin/qui/fait/peur/a/tout/le/monde.mp3"
 * on affiche
 * http://zoumzamzouilam/truc/chose/machin..."
 */
function inc_lien_court($url) {
	$long_url = defined('_MAX_LONG_URL') ? _MAX_LONG_URL : 40;
	$coupe_url = defined('_MAX_COUPE_URL') ? _MAX_COUPE_URL : 35;

	if (strlen((string) $url) > $long_url) {
		$url = substr((string) $url, 0, $coupe_url) . '...';
	}

	return $url;
}
