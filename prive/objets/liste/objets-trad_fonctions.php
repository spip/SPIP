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

function lister_traductions($id_trad,$objet){
	$table_objet_sql = table_objet_sql($objet);
	$primary = id_table_objet($objet);

	$rows = sql_allfetsel("$primary as id,lang",$table_objet_sql,'id_trad='.intval($id_trad));
	lang_select();
	return $rows;
}
?>
