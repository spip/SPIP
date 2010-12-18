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

// http://doc.spip.org/@action_supprimer_traduction_dist
function action_supprimer_traduction_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	if (!preg_match(",^(\d+)\D(\d+)$,", $arg, $r)) 
		spip_log("action supprimer_traduction: $arg pas compris");
	else action_supprimer_traduction_post($r);
}

// http://doc.spip.org/@action_supprimer_traduction_post
function action_supprimer_traduction_post($r)
{
	sql_updateq("spip_articles", array("id_trad" => 0), "id_article=" . $r[1]);
	// Si l'ancien groupe ne comporte plus qu'un seul article
	// mettre a zero.

	$cpt = sql_countsel("spip_articles", "id_trad=" . $r[2]);

	if ($cpt == 1)
		sql_updateq("spip_articles", array("id_trad" => 0), "id_trad=" . $r[2]);
}
?>
