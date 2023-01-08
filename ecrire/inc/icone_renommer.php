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
		preg_match('/(?:-([0-9]{1,3}))?([.](gif|png|svg))?$/i', $fond, $match)
		and ((isset($match[0]) and $match[0]) or (isset($match[1]) and $match[1]))
	) {
		if (isset($match[1]) and $match[1]) {
			$size = $match[1];
		}
		$type = substr($fond, 0, -strlen($match[0]));
		if (!isset($match[2]) or !$match[2]) {
			$fond .= '.png';
		}
	} else {
		$type = $fond;
		$fond .= '.png';
	}

	$rtl = false;
	if (preg_match(',[-_]rtl$,i', $type, $match)) {
		$rtl = true;
		$type = substr($type, 0, -strlen($match[0]));
	}

	// objet_type garde invariant tout ce qui ne commence par par id_, spip_
	// et ne finit pas par un s, sauf si c'est une exception declaree
	$type = objet_type($type, false);

	$dir = 'images/';
	$f = "$type-$size.png";
	if ($icone = find_in_theme($dir . $f)) {
		$dir = dirname($icone);
		$fond = $icone;

		if (
			$rtl
			and $fr = $dir . '/' . str_replace("$type-", "$type-rtl-", basename($icone))
			and file_exists($fr)
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
