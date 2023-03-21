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
 * Gestion de l'action debloquer_edition
 *
 * @package SPIP\Core\Edition
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Lever les blocages d'édition pour l'utilisateur courant
 *
 * @uses debloquer_tous()
 * @uses debloquer_edition()
 *
 * @global array visiteur_session
 * @return void
 */
function action_debloquer_edition_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	if ($arg) {
		include_spip('inc/drapeau_edition');
		if ($arg == 'tous') {
			debloquer_tous($GLOBALS['visiteur_session']['id_auteur']);
		} else {
			$arg = explode('-', (string) $arg);
			[$objet, $id_objet] = $arg;
			debloquer_edition($GLOBALS['visiteur_session']['id_auteur'], $id_objet, $objet);
		}
	}
}
