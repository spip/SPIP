<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return; // securiser

// http://doc.spip.org/@base_delete_all_dist
function base_delete_all_dist($titre)
{
	$delete = _request('delete');
	if (is_array($delete)) {
		$res = array();
		foreach ($delete as $table) {
		  if (spip_query("DROP TABLE $table"))
		    $res[] = $table;
		}
	}

	// un pipeline pour detruire les tables installees par les plugins
	pipeline('delete_tables', '');

	spip_unlink(_ACCESS_FILE_NAME);
	spip_unlink(_FILE_CONNECT);
	$d = count($delete);
	$r = count($res);
	spip_log("Tables detruites: $r sur $d: " . join(', ',$res));
}
?>
