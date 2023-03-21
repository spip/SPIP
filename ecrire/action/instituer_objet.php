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
 * Action pour instituer un objet avec les puces rapides
 *
 * @package SPIP\Core\PuceStatut
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Instituer un objet avec les puces rapides
 *
 * @param null|string $arg
 *     Chaîne "objet id statut". En absence utilise l'argument
 *     de l'action sécurisée.
 */
function action_instituer_objet_dist($arg = null) {

	if (is_null($arg)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	[$objet, $id_objet, $statut] = preg_split('/\W/', (string) $arg);
	if (!$statut) {
		$statut = _request('statut_nouv');
	} // cas POST
	if (!$statut) {
		return;
	} // impossible mais sait-on jamais

	if (
		($id_objet = (int) $id_objet)
		&& autoriser('instituer', $objet, $id_objet, '', ['statut' => $statut])
	) {
		include_spip('action/editer_objet');
		objet_modifier($objet, $id_objet, ['statut' => $statut]);
	}
}
