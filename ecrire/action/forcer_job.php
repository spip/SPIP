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

/**
 * Executer un travaille immediatement
 * @return void
 */
function action_forcer_job_dist(){
	$securiser_action = charger_fonction('securiser_action','inc');
	$id_job = $securiser_action();

	if ($id_job = intval($id_job)
		AND autoriser('forcer','job',$id_job)
	){
		include_spip('inc/queue');
		include_spip('inc/genie');
		queue_schedule(array($id_job));
	}

}

?>