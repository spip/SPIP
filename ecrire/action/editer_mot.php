<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@action_editer_mot_dist
function action_editer_mot_dist() {

	include_spip('inc/actions');
	$var_f = charger_fonction('controler_action_auteur', 'inc');
	$var_f();

	$arg = _request('arg');

	if (!preg_match(',^(-?\d+)\D(\d+)\W(\w+)\W(\w+)\W(\w+)$,', $arg, $r)) 
		spip_log("action editer_mot: $arg pas compris");
	else {
		list($x, $id_mot, $id_objet, $table, $table_id, $objet) = $r;
		spip_log("$id_mot, $id_objet, $table, $table_id, $objet");
		spip_query("DELETE FROM spip_mots_$table WHERE $table_id=$id_objet" . (($id_mot <= 0) ?  "" :  " AND id_mot=$id_mot"));
		if ($nouv_mot) {
		  // recopie de:
		  // inserer_mot("spip_mots_$table", $table_id, $id_objet, $nouv_mot);
			$result = spip_num_rows(spip_query("SELECT id_mot FROM spip_mots_$table WHERE id_mot=$nouv_mot AND $table_id=$id_objet"));
			if (!$result) 
				spip_query("INSERT INTO spip_mots_$table (id_mot,$table_id) VALUES ($nouv_mot, $id_objet)");
		}

		if ($GLOBALS['meta']['activer_moteur'] == 'oui') {
			include_spip("inc/indexation");
			marquer_indexer($objet, $id_objet);
		}
	}
}
?>
