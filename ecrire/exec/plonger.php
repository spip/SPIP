<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
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

	$rac = preg_replace(',[^\w\,/#&;-]+,', ' ', (string) _request('rac'));
	$id = (int) _request('id');
	$exclus = (int) _request('exclus');
	$col = (int) _request('col');
	$do = _request('do');
	if (preg_match('/^\w*$/', (string) $do)) {
		if (!$do) {
			$do = 'aff';
		}

		$plonger = charger_fonction('plonger', 'inc');
		$r = $plonger($id, spip_htmlentities($rac), [], $col, $exclus, $do);
	} else {
		$r = '';
	}

	ajax_retour($r);
}
