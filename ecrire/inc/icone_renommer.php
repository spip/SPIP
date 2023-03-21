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

include_spip('inc/boutons');
include_spip('base/objets');

function inc_icone_renommer_dist($fond, $fonction) {
	$size = 24;
	if (
		preg_match('/(?:-(\d{1,3}))?([.](gif|png|svg))?$/i', (string) $fond, $match)
		&& (isset($match[0]) && $match[0] || isset($match[1]) && $match[1])
	) {
		if (isset($match[1]) && $match[1]) {
			$size = $match[1];
		}
		$type = substr((string) $fond, 0, -strlen($match[0]));
		if (!isset($match[2]) || !$match[2]) {
			$fond .= '.png';
		}
	} else {
		$type = $fond;
		$fond .= '.png';
	}

	$rtl = false;
	if (preg_match(',[-_]rtl$,i', (string) $type, $match)) {
		$rtl = true;
		$type = substr((string) $type, 0, -strlen($match[0]));
	}

	// objet_type garde invariant tout ce qui ne commence par par id_, spip_
	// et ne finit pas par un s, sauf si c'est une exception declaree
	$type = objet_type($type, false);

	$dir = 'images/';
	$f = "$type-$size.png";
	if ($icone = find_in_theme($dir . $f)) {
		$dir = dirname((string) $icone);
		$fond = $icone;

		if (
			$rtl
			&& ($fr = $dir . '/' . str_replace("$type-", "$type-rtl-", basename((string) $icone)))
			&& file_exists($fr)
		) {
			$fond = $fr;
		}

		$action = $fonction;
		if ($action == 'supprimer.gif') {
			$action = 'del';
		} elseif ($action == 'creer.gif') {
			$action = 'new';
		} elseif ($action == 'edit.gif') {
			$action = 'edit';
		}

		$fonction = '';
		if (in_array($action, ['add','del', 'new', 'edit', 'config'])) {
			$fonction = $action;
		}

		// c'est bon !
		return [$fond, $fonction];
	}

	return [$fond, $fonction];
}
