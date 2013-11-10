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

function action_supprimer_signature_dist($id_signature=null){
	if (!$id_signature){
		$securiser_action = charger_fonction('securiser_action','inc');
		$id_signature = $securiser_action();
	}

	if (autoriser('supprimer','signature',$id_signature)){
		$id_article = Sql::getfetsel('P.id_article','spip_signatures AS S JOIN spip_petitions AS P ON S.id_petition=P.id_petition','S.id_signature='.intval($id_signature));
		if ($id_article AND autoriser('modererpetition','article',$id_article)) {
			include_spip('action/editer_signature');
			signature_modifier($id_signature, array('statut'=>'poubelle'));
		}
	}
}

?>