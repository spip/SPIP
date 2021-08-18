<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
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

	list($objet, $id_objet, $statut) = preg_split('/\W/', $arg);
	if (!$statut) {
		$statut = _request('statut_nouv');
	} // cas POST
	if (!$statut) {
		return;
	} // impossible mais sait-on jamais

	if ($id_objet = intval($id_objet)
		and autoriser('instituer', $objet, $id_objet, '', array('statut' => $statut))
	) {
		include_spip('action/editer_objet');
		objet_modifier($objet, $id_objet, array('statut' => $statut));
	}
}
