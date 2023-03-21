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
 * Gestion d'affichage ajax d'une rubrique sélectionnée dans le mini navigateur
 *
 * @package SPIP\Core\Exec
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/actions');

/**
 * Affiche en ajax des informations d'une rubrique selectionnée dans le mini navigateur
 *
 * @uses inc_informer_dist()
 * @uses ajax_retour()
 **/
function exec_informer_dist() {
	$id = (int) _request('id');
	$col = (int) _request('col');
	$exclus = (int) _request('exclus');
	$do = _request('do');

	if (preg_match('/^\w*$/', (string) $do)) {
		if (!$do) {
			$do = 'aff';
		}

		$informer = charger_fonction('informer', 'inc');
		$res = $informer($id, $col, $exclus, _request('rac'), _request('type'), $do);
	} else {
		$res = '';
	}
	ajax_retour($res);
}
