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
 * Action pour dissocier un lien entre 2 objets
 *
 * @package SPIP\Core\Liens\API
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Action pour dissocier 2 objets entre eux
 *
 * L'argument attendu est `objet1-id1-objet2-id2` (type d'objet, identifiant)
 * tel que `mot-7-rubrique-3`.
 *
 * @uses objet_dissocier()
 *
 * @param null|string $arg
 *     Clé des arguments. En absence utilise l'argument
 *     de l'action sécurisée.
 * @return void
 */
function action_supprimer_lien_dist($arg = null) {
	if (is_null($arg)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	$arg = explode('-', (string) $arg);
	[$objet_source, $ids, $objet_lie, $idl] = $arg;

	include_spip('action/editer_liens');
	objet_dissocier([$objet_source => $ids], [$objet_lie => $idl]);
}
