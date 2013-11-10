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

if (!defined('_ECRIRE_INC_VERSION')) return;


/**
 * @param int $id_message
 * @return void
 */
function action_supprimer_message_dist($id_message=null){
	if (is_null($id_message)){
		$securiser_action = charger_fonction('securiser_action','inc');
		$id_message = $securiser_action();
	}


	include_spip('inc/autoriser');
	if (autoriser('supprimer','message',$id_message)){
		include_spip('action/editer_objet');
		objet_modifier('message',$id_message,array('statut'=>'poub'));
	}
}