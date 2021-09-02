<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion de l'action referencer_traduction gérant les liens de traductions
 *
 * @package SPIP\Core\Action
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Définir le lien de traduction vers un objet de réference
 *
 * Plusieurs cas :
 * - id_trad=0 : déréference le lien de traduction de id_objet
 * - id_trad=NN : référence le lien de traduction de id_objet vers NN
 * - id_objet=id_trad actuel et id_trad=new_id_trad : modifie la référence
 *   de tout le groupe de traduction
 *
 * @param string $objet
 *     Type d'objet
 * @param int $id_objet
 *     Identifiant de l'objet
 * @param int $id_trad
 *     Identifiant de la référence de traduction
 * @return bool
 *     - False si on ne trouve pas l'objet de référence
 *     - True sinon
 */
function action_referencer_traduction_dist($objet, $id_objet, $id_trad) {

	// ne rien faire si id_trad est ambigu
	if (!is_numeric($id_trad)) {
		return false;
	}

	$table_objet_sql = table_objet_sql($objet);
	$id_table_objet = id_table_objet($objet);

	// on a fourni un id_trad : affectation ou modification du groupe de trad
	if ($id_trad) {
		// selectionner l'objet cible, qui doit etre different de nous-meme,
		// et quitter s'il n'existe pas
		$id_lier = sql_getfetsel(
			'id_trad',
			$table_objet_sql,
			"$id_table_objet=" . intval($id_trad) . " AND NOT($id_table_objet=" . intval($id_objet) . ')'
		);
		if ($id_lier === null) {
			spip_log("echec lien de trad vers objet $objet/$id_objet incorrect ($id_trad)");

			return false;
		}

		// $id_lier est le numero du groupe de traduction
		// Si l'objet vise n'est pas deja traduit, son identifiant devient
		// le nouvel id_trad de ce nouveau groupe et on l'affecte aux deux
		// objets
		if ($id_lier == 0) {
			sql_updateq($table_objet_sql, ['id_trad' => $id_trad], "$id_table_objet IN ($id_trad, $id_objet)");
		} // si id_lier = id_objet alors on veut changer la reference de tout le groupe de trad
		elseif ($id_lier == $id_objet) {
			sql_updateq($table_objet_sql, ['id_trad' => $id_trad], "id_trad = $id_lier");
		} // sinon ajouter notre objet dans le groupe
		else {
			sql_updateq($table_objet_sql, ['id_trad' => $id_lier], "$id_table_objet=" . intval($id_objet));
		}
	} // on a fourni un id_trad nul : sortir id_objet du groupe de trad
	else {
		$old_id_trad = sql_getfetsel('id_trad', $table_objet_sql, "$id_table_objet=" . intval($id_objet));
		// supprimer le lien de traduction
		sql_updateq($table_objet_sql, ['id_trad' => 0], "$id_table_objet=" . intval($id_objet));

		// Verifier si l'ancien groupe ne comporte plus qu'un seul objet. Alors mettre a zero.
		$cpt = sql_countsel($table_objet_sql, 'id_trad=' . intval($old_id_trad));
		if ($cpt == 1) {
			sql_updateq($table_objet_sql, ['id_trad' => 0], 'id_trad=' . intval($old_id_trad));
		}
	}

	return true;
}
