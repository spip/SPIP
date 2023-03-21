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
 * Gestion de l'action pour s'autoriser webmestre
 *
 * @package SPIP\Core\Autorisations
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/actions');

/**
 * Prouver qu'on a les droits de webmestre via un ftp, et
 * devenir webmestre sans refaire l'install
 *
 * @return void
 */
function action_etre_webmestre_dist() {
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$time = $securiser_action();

	if (
		time() - $time < 15 * 60
		&& $GLOBALS['visiteur_session']['statut'] == '0minirezo'
		&& $GLOBALS['visiteur_session']['webmestre'] !== 'oui'
	) {
		$action = _T('info_admin_etre_webmestre');
		$admin = charger_fonction('admin', 'inc');
		// lance la verif par ftp et l'appel
		// a base_etre_webmestre_dist quand c'est OK
		if ($r = $admin('etre_webmestre', $action)) {
			echo $r;
			exit;
		}
	}
}

/**
 * Passe l'administrateur connecté en webmestre.
 *
 * @return void
 */
function base_etre_webmestre_dist() {
	if ($GLOBALS['visiteur_session']['statut'] == '0minirezo' && $GLOBALS['visiteur_session']['webmestre'] !== 'oui') {
		include_spip('action/editer_auteur');
		auteur_instituer($GLOBALS['visiteur_session']['id_auteur'], ['webmestre' => 'oui'], true);
	}
}
