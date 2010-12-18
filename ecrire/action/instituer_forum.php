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

// http://doc.spip.org/@action_instituer_forum_dist
function action_instituer_forum_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	list($id_forum, $statut) = preg_split('/\W/', $arg);
	$id_forum = intval($id_forum);
	$row = sql_fetsel("*", "spip_forum", "id_forum=$id_forum");
	if (!$row) return;

	instituer_un_forum($statut,$row);
}

function instituer_un_forum($statut,$row){

	$id_forum = $row['id_forum'];
	$old = $row['statut'];
 // rien a faire si pas de changement de statut
 	if ($old==$statut)
		return;

	// changer le statut de toute l'arborescence dependant de ce message
	$id_messages = array($id_forum);
	while ($id_messages) {
		sql_updateq("spip_forum", array("statut" => $statut), sql_in("id_forum", $id_messages) ." AND statut = '$old'");

		$id_messages = array_map('reset',sql_allfetsel("id_forum", "spip_forum", sql_in("id_parent", $id_messages)));
	}

	// Notifier de la publication du message, s'il etait 'prop'
	if ($old=='prop' AND $statut=='publie') {
		if ($notifications = charger_fonction('notifications', 'inc')) {
			$notifications('forumvalide', $id_forum);
		}
	}

	// mettre a jour la date du thread
	// si publie, ou que tout le thread est prive,
	// mettre la date du thread a 'maintenant' (date de publi du message)
	// sinon prendre la date_heure du dernier message public
	// c'est imparfait dans le cas ou les crayons ont ete utilises pour modifier ce message entre temps
	// car la date_thread aurait cette derniere date alors que pas le message
	// mais c'est au mieux de ce que l'on peut faire quand on depublie un SPAM ou supprime un message
	if ($statut=='publie' OR $old=='publie') {
		if ($statut=='publie'
			OR !($date_thread = sql_getfetsel("date_heure", "spip_forum", "statut='publie' AND id_thread=".$row['id_thread'], "", "date_heure DESC","0,1"))){
			$date_thread = date('Y-m-d H:i:s');
		}
		sql_updateq("spip_forum", array("date_thread" => $date_thread), "id_thread=".$row['id_thread']);
	}

	// invalider les pages comportant ce forum
	include_spip('inc/invalideur');
	include_spip('inc/forum');
	$index_forum = calcul_index_forum($row['id_article'], $row['id_breve'], $row['id_rubrique'], $row['id_syndic']);
	suivre_invalideur("id='id_forum/$index_forum'");

	// Reindexation du thread (par exemple)
	pipeline('post_edition',
		array(
			'args' => array(
				'table' => 'spip_forum',
				'id_objet' => $id_forum,
				'action' => 'instituer',
			),
			'data' => null
		)
	);
}

?>
