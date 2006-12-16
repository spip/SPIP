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

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();
	// arg = l'eventuel mot a supprimer pour d'eventuelles Row SQL
	if (!preg_match(',^(\d*)\D(-?\d*)\W(\w*)\W(\w*)\W(\w*)\W?(\d*)$,', $arg, $r)) 
		spip_log("action editer_mot: $arg pas compris");
	else action_editer_mot_post($r);
}

// http://doc.spip.org/@action_editer_mot_post
function action_editer_mot_post($r)
{
	$redirect = _request('redirect');
	$cherche_mot = _request('cherche_mot');
	$select_groupe = _request('select_groupe');

	list($x, $id_objet, $id_mot, $table, $table_id, $objet, $nouv_mot) = $r;
	if ($id_mot) {
			if ($objet)
			  // desassocier un/des mot d'un objet precis
				spip_query("DELETE FROM spip_mots_$table WHERE $table_id=$id_objet" . (($id_mot <= 0) ? "" : " AND id_mot=$id_mot"));
			else {
			  // disparition complete d'un mot
			spip_query("DELETE FROM spip_mots WHERE id_mot=$id_mot");
			spip_query("DELETE FROM spip_mots_articles WHERE id_mot=$id_mot");
			spip_query("DELETE FROM spip_mots_rubriques WHERE id_mot=$id_mot");
			spip_query("DELETE FROM spip_mots_syndic WHERE id_mot=$id_mot");
			spip_query("DELETE FROM spip_mots_forum WHERE id_mot=$id_mot");
			}
	}
	if ($nouv_mot ? $nouv_mot : ($nouv_mot = _request('nouv_mot'))) {
		  // recopie de:
		  // inserer_mot("spip_mots_$table", $table_id, $id_objet, $nouv_mot);
			$result = spip_num_rows(spip_query("SELECT id_mot FROM spip_mots_$table WHERE id_mot=$nouv_mot AND $table_id=$id_objet"));
			if (!$result) 
				spip_query("INSERT INTO spip_mots_$table (id_mot,$table_id) VALUES ($nouv_mot, $id_objet)");
		}

		if ($table AND $GLOBALS['meta']['activer_moteur'] == 'oui') {
			include_spip("inc/indexation");
			marquer_indexer("spip_$table", $id_objet);
		}

	$redirect = rawurldecode($redirect);

	// hack du retour croise editer/grouper 

	if (preg_match('/^(.*exec=)editer_mot(&.*)script=(grouper_mots)(.*)$/', $redirect, $r))
	    $redirect = $r[1] . $r[3] . $r[2] . $r[4];
	    
	if ($cherche_mot) {
		if ($p = strpos($redirect, '#')) {
			$a = substr($redirect,$p);
			$redirect = substr($redirect,0,$p);
		} else $a='';
		$redirect .= "&cherche_mot=$cherche_mot&select_groupe=$select_groupe$a";
	}
	redirige_par_entete($redirect);
}
?>
