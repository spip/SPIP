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

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@exec_memoriser_dist
function exec_memoriser_dist()
{
	$id_ajax = intval(_request('id_ajax_fonc'));

	$res = spip_fetch_array(spip_query($q = "SELECT variables, hash FROM spip_ajax_fonc WHERE id_ajax_fonc = $id_ajax"));

	if ($res) {
		
		include_spip('inc/presentation');
		list($t,$r,$p,$f) = unserialize($res["variables"]);

		$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM " . $r['FROM'] . ($r['WHERE'] ? (' WHERE ' . $r['WHERE']) : '') . ($r['GROUP BY'] ? (' GROUP BY ' . $r['GROUP BY']) : '')));

		ajax_retour(afficher_articles_trad($t, $r, $f, $p, $id_ajax, $cpt['n'], _request('trad')));

	} else spip_log("memoriser $q vide");
}
?>
