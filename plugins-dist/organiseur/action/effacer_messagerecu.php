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
 * @param int $id_auteur
 * @param int $id_message
 * @return void
 */
function action_effacer_messagerecu_dist($id_auteur=null,$id_message=null){
	if (is_null($id_auteur) OR is_null($id_message)){
		$securiser_action = charger_fonction('securiser_action','inc');
		$arg = $securiser_action();
		list($id_auteur,$id_message) = explode('-',$arg);
	}


	include_spip('inc/autoriser');
	if (autoriser('effacer','messagerecu',$id_message,null,array('id_auteur'=>$id_auteur))){
		include_spip('inc/messages');
		messagerie_effacer_message_recu($id_auteur,$id_message);
	}
}