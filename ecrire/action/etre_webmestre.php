<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/actions');

/**
 * Prouver qu'on a les droits de webmestre via un ftp, et
 * devenir webmestre sans refaire l'install
 * @return void
 */
function action_etre_webmestre_dist() {
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$time = $securiser_action();

	if (time()-$time<15*60
		AND $GLOBALS['visiteur_session']['statut']=='0minirezo'
		AND $GLOBALS['visiteur_session']['webmestre']!=='oui') {
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

function base_etre_webmestre_dist() {
	if ($GLOBALS['visiteur_session']['statut']=='0minirezo' AND $GLOBALS['visiteur_session']['webmestre']!=='oui') {
		include_spip('action/editer_auteur');
		instituer_auteur($GLOBALS['visiteur_session']['id_auteur'], array('webmestre'=>'oui'), true);
	}
}
?>
