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

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function lister_traductions($id_trad, $objet) {
	$table_objet_sql = table_objet_sql($objet);
	$primary = id_table_objet($objet);

	$select = "$primary as id,lang";
	$where = 'id_trad=' . intval($id_trad);
	$trouver_table = charger_fonction('trouver_table', 'base');
	$desc = $trouver_table($table_objet_sql);
	if (isset($desc['field']['statut'])) {
		$select .= ',statut';
		$where .= ' AND statut!='.sql_quote('poubelle');
	}

	$rows = sql_allfetsel($select, $table_objet_sql, $where);
	lang_select();

	return $rows;
}
