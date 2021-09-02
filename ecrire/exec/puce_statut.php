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
 * Gestion des puces d'action rapide
 *
 * @package SPIP\Core\Puce_statut
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/presentation');

/**
 * Gestion de l'affichage ajax des puces d'action rapide
 *
 * Récupère l'identifiant id et le type d'objet dans les données postées
 * et appelle la fonction de traitement de cet exec.
 *
 * @uses exec_puce_statut_args()
 * @return string Code HTML
 **/
function exec_puce_statut_dist() {
	exec_puce_statut_args(_request('id'), _request('type'));
}

/**
 * Traitement de l'affichage ajax des puces d'action rapide
 *
 * Appelle la fonction de traitement des puces statuts
 * après avoir retrouvé le statut en cours de l'objet
 * et son parent (une rubrique)
 *
 * @uses inc_puce_statut_dist()
 * @uses ajax_retour()
 *
 * @param int $id
 *     Identifiant de l'objet
 * @param string $type
 *     Type d'objet
 * @return string Code HTML
 **/
function exec_puce_statut_args($id, $type) {
	$id = intval($id);
	if (
		$table_objet_sql = table_objet_sql($type)
		and $d = lister_tables_objets_sql($table_objet_sql)
		and isset($d['statut_textes_instituer'])
		and $d['statut_textes_instituer']
	) {
		$prim = id_table_objet($type);
		if (isset($d['field']['id_rubrique'])) {
			$select = 'id_rubrique,statut';
		} else {
			$select = '0 as id_rubrique,statut';
		}
		$r = sql_fetsel($select, $table_objet_sql, "$prim=$id");
		$statut = $r['statut'];
		$id_rubrique = $r['id_rubrique'];
	} else {
		$id_rubrique = $id;
		$statut = 'prop'; // arbitraire
	}
	$puce_statut = charger_fonction('puce_statut', 'inc');
	ajax_retour($puce_statut($id, $statut, $id_rubrique, $type, true));
}
