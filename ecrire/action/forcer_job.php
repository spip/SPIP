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
 * Action pour exécuter un job en attente, tout de suite
 *
 * @package SPIP\Core\Job
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Executer un travaille immediatement
 *
 * @return void
 */
function action_forcer_job_dist() {
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$id_job = $securiser_action();

	if ($id_job = intval($id_job)
		and autoriser('forcer', 'job', $id_job)
	) {
		include_spip('inc/queue');
		include_spip('inc/genie');
		queue_schedule(array($id_job));
	}
}
