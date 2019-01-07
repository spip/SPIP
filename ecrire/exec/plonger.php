<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2019                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion d'affichage ajax des sous rubriques dans le mini navigateur
 *
 * @package SPIP\Core\Exec
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Afficher en ajax les sous-rubriques d'une rubrique (composant du mini-navigateur)
 *
 * @uses inc_plonger_dist()
 * @uses ajax_retour()
 **/
function exec_plonger_dist() {
	include_spip('inc/actions');

	$rac = preg_replace(',[^\w\,/#&;-]+,', ' ', _request('rac'));
	$id = intval(_request('id'));
	$exclus = intval(_request('exclus'));
	$col = intval(_request('col'));
	$do = _request('do');
	if (preg_match('/^\w*$/', $do)) {
		if (!$do) {
			$do = 'aff';
		}

		$plonger = charger_fonction('plonger', 'inc');
		$r = $plonger($id, spip_htmlentities($rac), array(), $col, $exclus, $do);
	} else {
		$r = '';
	}

	ajax_retour($r);
}
