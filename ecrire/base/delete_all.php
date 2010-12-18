<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// http://doc.spip.org/@base_delete_all_dist
function base_delete_all_dist($titre)
{
	$delete = _request('delete');
	$res = array();
	if (is_array($delete)) {
		foreach ($delete as $table) {
			if (sql_drop_table($table))
				$res[] = $table;
			else spip_log("SPIP n'a pas pu detruire $table.");
		}

	// un pipeline pour detruire les tables installees par les plugins
		pipeline('delete_tables', '');

		spip_unlink(_FILE_CONNECT);
		spip_unlink(_FILE_CHMOD);
		spip_unlink(_FILE_META);
		spip_unlink(_ACCESS_FILE_NAME);
		spip_unlink(_CACHE_RUBRIQUES);
	}
	$d = count($delete);
	$r = count($res);
	spip_log("Tables detruites: $r sur $d: " . join(', ',$res));
}
?>
