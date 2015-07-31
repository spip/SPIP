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

// http://doc.spip.org/@action_instituer_site_dist
function action_instituer_site_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	list($id_syndic, $statut) = preg_split('/\W/', $arg);

	$cond = "id_syndic=" . intval($id_syndic);
	$row = sql_fetsel("statut, id_rubrique", "spip_syndic", $cond);
	if (!$row OR ($row['statut'] == $statut)) return;

	sql_updateq("spip_syndic", array("statut" => $statut), $cond);
	include_spip('inc/rubriques');
	calculer_rubriques_if($row['id_rubrique'], array('statut' => $statut), $row['statut']);


}
?>
