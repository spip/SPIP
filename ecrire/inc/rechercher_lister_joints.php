<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;

function inc_rechercher_lister_joints_dist($table,$table_liee,&$ids_trouves, $serveur='') {
	include_spip('base/connect_sql');
	$cle_depart = id_table_objet($table);
	$cle_arrivee =  id_table_objet($table_liee);
	$table_sql = preg_replace('/^spip_/', '', table_objet_sql($table));
	$table_liee_sql = preg_replace('/^spip_/', '', table_objet_sql($table_liee));
	if ($table_liee == 'document')
		$s = sql_select("id_objet as $cle_depart, $cle_arrivee", "spip_documents_liens", array("objet='$table'",sql_in('id_'.${table_liee}, array_keys($ids_trouves))), '','','','',$serveur);
	else
		$s = sql_select("$cle_depart,$cle_arrivee", "spip_${table_liee_sql}_${table_sql}", sql_in('id_'.${table_liee}, array_keys($ids_trouves)), '','','','',$serveur);

	return array($cle_depart,$cle_arrivee,$s);
}


?>