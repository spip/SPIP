<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@action_editer_site_dist
function action_syndiquer_site_dist($id_syndic=null) {

	if (is_null($id_syndic)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$id_syndic = $securiser_action();
	}


	$id_job = job_queue_add('syndic_a_jour','syndic_a_jour',array($id_syndic),'genie/syndic',true);
	// l'executer immediatement si possible
	if ($id_job) {
		include_spip('inc/queue');
		queue_schedule(array($id_job));
	}
	else {
		spip_log("Erreur insertion syndic_a_jour($id_syndic) dans la file des travaux",_LOG_ERREUR);
	}

}

?>