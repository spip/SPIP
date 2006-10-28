<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
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

	// le champ id_auteur sert finalement a memoriser le nombre de lignes
	// (a renommer)

	$res = spip_fetch_array(spip_query($q = "SELECT variables, id_auteur, hash FROM spip_ajax_fonc WHERE id_ajax_fonc = $id_ajax"));

	if ($res) {
		
		include_spip('inc/presentation');
		list($t,$r,$p,$f) = unserialize($res["variables"]);
		ajax_retour(afficher_articles_trad($t, $r, $f, $p, $id_ajax, $res['id_auteur'], _request('trad')));

	} else spip_log("memoriser $q vide");
}
?>
