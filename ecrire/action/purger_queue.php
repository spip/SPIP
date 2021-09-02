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
 * Gestion de l'action de purge des travaux en attente
 *
 * @package SPIP\Core\Queue
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Purger la liste des travaux en attente
 *
 * @return void
 */
function action_purger_queue_dist() {
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$securiser_action();

	if (autoriser('purger', 'queue')) {
		include_spip('inc/queue');
		queue_purger();
	}
}
