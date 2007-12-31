<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@action_instituer_forum_dist
function action_instituer_forum_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	list($id_forum, $statut) = preg_split('/\W/', $arg);
	$id_forum = intval($id_forum);
	$result = sql_select("*", "spip_forum", "id_forum=$id_forum");
	if (!($row = sql_fetch($result)))
		return;

	// invalider les pages comportant ce forum
	include_spip('inc/invalideur');
	include_spip('inc/forum');
	$index_forum = calcul_index_forum($row['id_article'], $row['id_breve'], $row['id_rubrique'], $row['id_syndic']);
	suivre_invalideur("id='id_forum/$index_forum'");

	// changer le statut de toute l'arborescence dependant de ce message
	$id_messages = array($id_forum);
	$old = $row['statut'];
	while ($id_messages) {
		$id_messages = join(',', $id_messages);
		sql_updateq("spip_forum", array("statut" => $statut), "id_forum IN ($id_messages) AND statut = '$old'");

		$result_forum = sql_select("id_forum", "spip_forum", "id_parent IN ($id_messages)");
		$id_messages = array();
		while ($row = sql_fetch($result_forum))
			$id_messages[] = $row['id_forum'];
	}

	// Notifier de la publication du message, s'il etait 'prop'
	if ($old=='prop' AND $statut=='publie') {
		if ($notifications = charger_fonction('notifications', 'inc')) {
			$notifications('forumvalide', $id_forum);
		}
	}

	// Reindexation du thread (par exemple)
	pipeline('post_edition',
		array(
			'args' => array(
				'table' => 'spip_forum',
				'id_objet' => $id_forum
			),
			'data' => null
		)
	);
}

?>
