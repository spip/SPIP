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

function action_valider_signature_dist($id_signature=null){
	if (!$id_signature){
		$securiser_action = charger_fonction('securiser_action','inc');
		$id_signature = $securiser_action();
	}

	if (autoriser('publier','signature',$id_signature)){
		include_spip('action/editer_signature');
		signature_modifier($id_signature, array('statut'=>'publie'));
	}
}

?>