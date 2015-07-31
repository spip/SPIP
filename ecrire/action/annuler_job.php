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
 * Annuler un travail
 * @return void
 */
function action_annuler_job_dist(){
	$securiser_action = charger_fonction('securiser_action','inc');
	$id_job = $securiser_action();

	if ($id_job = intval($id_job)
		AND autoriser('annuler','job',$id_job)
	){
		job_queue_remove($id_job);
	}
}

?>