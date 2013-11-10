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

function action_traiter_lot_signature_dist($arg=null) {

	if (is_null($arg)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	/**
	 * $arg contient l'action relancer/supprimer/valider
	 * les id sont dans un tableau non signe ids[]
	 */
	if (preg_match(",^(\w+)$,",$arg,$match)
	 AND in_array($statut=$match[1],array('relancer','supprimer','valider'))
	 AND autoriser('modererlot','petition')){
		$where = '';
		if (intval($id_petition = _request('id_petition'))){
			$where="id_petition=".intval($id_petition);
			// pour relancer ou valider on ne prend que celles en attente
			if (in_array($statut,array('relancer','valider')))
				$where.=" AND statut!='publie' AND statut!='poubelle'";
		}
		else {
			$ids=_request('ids');
			if (is_array($ids)) {
				$ids = array_map('intval',$ids);
				$where = Sql::in('id_signature',$ids);
			}
		}

		if ($where){
			$rows = Sql::allfetsel("id_signature", "spip_signatures", $where);
			if (!count($rows)) return;
			$rows = array_map('reset',$rows);
			if ($action = charger_fonction($statut."_signature",'action',true))
				foreach ($rows as $id_signature) {
					$action($id_signature);
				}
		}
	}
}

?>
