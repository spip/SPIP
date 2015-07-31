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
 * Definir le lien de traduction ver sun objet de reference
 * si id_trad=0 : dereference le lien de traduction de id_objet
 * si id_trad=NN : reference le lien de traduction de id_objet vers NN
 * si id_objet=id_trad actuel et id_trad=new_id_trad : modifie la reference de tout le groupe de traduction
 * 
 * @param string $objet
 * @param int $id_objet
 * @param int $id_trad
 * @return bool
 */
function action_referencer_traduction_dist($objet, $id_objet, $id_trad) {

	// ne rien faire si id_trad est ambigu
	if (!is_numeric($id_trad)) return false;

	$table_objet_sql = table_objet_sql($objet);
	$id_table_objet = id_table_objet($objet);

	// on a fourni un id_trad : affectation ou modification du groupe de trad
	if ($id_trad) {
		// selectionner l'objet cible, qui doit etre different de nous-meme,
		// et quitter s'il n'existe pas
		$id_lier = sql_getfetsel('id_trad', $table_objet_sql, "$id_table_objet=".intval($id_trad)." AND NOT($id_table_objet=".intval($id_objet).")");
		if ($id_lier === NULL){
			spip_log("echec lien de trad vers objet $objet/$id_objet incorrect ($id_trad)");
			return false;
		}

		// $id_lier est le numero du groupe de traduction
		// Si l'objet vise n'est pas deja traduit, son identifiant devient
		// le nouvel id_trad de ce nouveau groupe et on l'affecte aux deux
		// objets
		if ($id_lier == 0) {
			sql_updateq($table_objet_sql, array("id_trad" => $id_trad), "$id_table_objet IN ($id_trad, $id_objet)");
		}
		// si id_lier = id_objet alors on veut changer la reference de tout le groupe de trad
		elseif ($id_lier == $id_objet) {
			sql_updateq($table_objet_sql, array("id_trad" => $id_trad), "id_trad = $id_lier");
		}
		// sinon ajouter notre objet dans le groupe
		else {
			sql_updateq($table_objet_sql, array("id_trad" => $id_lier), "$id_table_objet=".intval($id_objet));
		}
	}
	// on a fourni un id_trad nul : sortir id_objet du groupe de trad
	else {
		$old_id_trad = sql_getfetsel('id_trad',$table_objet_sql,"$id_table_objet=".intval($id_objet));
	  // supprimer le lien de traduction
		sql_updateq($table_objet_sql, array("id_trad" => 0), "$id_table_objet=".intval($id_objet));

		// Verifier si l'ancien groupe ne comporte plus qu'un seul objet. Alors mettre a zero.
		$cpt = sql_countsel($table_objet_sql, "id_trad=".intval($old_id_trad));
		if ($cpt == 1)
			sql_updateq($table_objet_sql, array("id_trad" => 0), "id_trad=".intval($old_id_trad));
	}

	return true;
}


?>
