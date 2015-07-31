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
 * Purger la liste des travaux en attente
 * @return void
 */
function action_purger_queue_dist(){
	$securiser_action = charger_fonction('securiser_action','inc');
	$securiser_action();

	if (autoriser('purger','queue')){
		include_spip('inc/queue');
		queue_purger();
	}

}

?>