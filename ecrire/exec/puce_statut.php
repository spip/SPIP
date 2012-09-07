<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion des puces d'action rapide
 *
 * @package SPIP\Core\Puce_statut
**/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/presentation');

/**
 * Gestion de l'affichage ajax des puces d'action rapide
 *
 * Récupère l'identifiant id et le type d'objet dans les données postées
 * et appelle la fonction de traitement de cet exec.
 * 
 * @see exec_puce_statut_formulaires_args()
 * @return string Code HTML
**/
function exec_puce_statut_dist()
{
	exec_puce_statut_args(_request('id'),  _request('type'));
}

/**
 * Traitement de l'affichage ajax des puces d'action rapide
 *
 * Appelle la fonction de traitement des puces statuts
 * après avoir retrouvé le statut en cours de l'objet
 * et son parent (une rubrique)
 * 
 * @param int $id
 *     Identifiant de l'objet
 * @param string $type
 *     Type d'objet
 * @return string Code HTML
**/
function exec_puce_statut_args($id, $type)
{
	if ($table_objet_sql = table_objet_sql($type)
		AND $d = lister_tables_objets_sql($table_objet_sql)
		AND isset($d['statut_textes_instituer'])
		AND $d['statut_textes_instituer'])
	{
		$prim = id_table_objet($type);
		$id = intval($id);
		$r = sql_fetsel("id_rubrique,statut", $table_objet_sql, "$prim=$id");
		$statut = $r['statut'];
		$id_rubrique = $r['id_rubrique'];
	}
	else {
		$id_rubrique = intval($id);
		$statut = 'prop'; // arbitraire
	}
	$puce_statut = charger_fonction('puce_statut', 'inc');
	ajax_retour($puce_statut($id,$statut,$id_rubrique,$type, true));
}
?>
