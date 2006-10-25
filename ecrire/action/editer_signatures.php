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

include_spip('inc/actions');

// Modifier le reglage des forums publics de l'article x
// http://doc.spip.org/@action_poster_dist
function action_editer_signatures_dist()
{
	$var_f = charger_fonction('controler_action_auteur', 'inc');
	$var_f();

	$arg = _request('arg');

	if (!preg_match(",^(-?\d+)$,", $arg, $r)) {
		 spip_log("action_editer_signature_dist $arg pas compris");
	} else action_editer_signatures_post($r);
}

// http://doc.spip.org/@action_poster_post
function action_editer_signatures_post($r)
{
	$id = intval($r[1]);

	if ($id < 0){
		$id = 0 - $id;
		$result_forum = spip_query("UPDATE spip_signatures SET statut='poubelle' WHERE id_signature=$id");

	} elseif ($id > 0){
		$result_forum = spip_query("UPDATE spip_signatures SET statut='publie' WHERE id_signature=$id");

	}

	// Invalider les pages ayant trait aux petitions
	if ($id) {
		include_spip('inc/invalideur');
		$id_article = spip_fetch_array(spip_query("SELECT id_article FROM spip_signatures WHERE id_signature=$id"));
		$id_article = $id_article['id_article'];
		suivre_invalideur("id='varia/pet$id_article'");
	}

	# cette requete devrait figurer dans l'optimisation
	spip_query("DELETE FROM spip_signatures WHERE NOT (statut='publie' OR statut='poubelle') AND date_time<DATE_SUB(NOW(),INTERVAL 10 DAY)");
}
?>
