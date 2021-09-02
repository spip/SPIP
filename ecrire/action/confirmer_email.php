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
 * Gestion de l'action confirmer_email
 *
 * @package SPIP\Core\Inscription
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Confirmer un changement d'email
 *
 * @global array $GLOBALS ['visiteur_session']
 * @global string $GLOBALS ['redirect']
 * @return void
 */
function action_confirmer_email_dist() {
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	include_spip('inc/filtres');
	if ($GLOBALS['visiteur_session']['id_auteur'] and email_valide($arg)) {
		include_spip('action/editer_auteur');
		auteur_modifier($GLOBALS['visiteur_session']['id_auteur'], ['email' => $arg]);
	}
	// verifier avant de rediriger pour invalider le message de confirmation
	// si ca n'a pas marche
	if (
		$redirect = _request('redirect') and !$arg == sql_getfetsel(
			'email',
			'spip_auteurs',
			'id_auteur=' . intval($GLOBALS['visiteur_session'])
		)
	) {
		$GLOBALS['redirect'] = parametre_url($redirect, 'email_modif', '');
	}
}
