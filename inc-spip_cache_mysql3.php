<?php
// Ce fichier ne sera execute qu'une fois
if (defined("_SPIP_CACHE_MYSQL3")) return;
define("_SPIP_CACHE_MYSQL3", "1");

function changer_statut_forum($id_forum, $statut) {

	$forum_parents = array('id_rubrique', 'id_article', 'id_breve', 'id_syndic');

$result = spip_query("
SELECT	id_parent, " . join(",", $forum_parents) . "
FROM	spip_forum 
WHERE	id_forum=$id_forum
");

 	if (!($row = spip_fetch_array($result))) return;
	$id_parent = $row['id_parent'];
	if (file_exists('inc-invalideur.php3'))
	  {
	    include('inc-invalideur.php3');
	    foreach ($forum_parents as $id)
	      { if ($id_num = $row[$id])
		  suivre_invalideur("$id='$id_num'", 
				    "spip_" . $id . '_caches');
	      }
	  }

	// signaler au moteur de recherche qu'il faut reindexer le thread
	// (en fait on se contente de demander une reindexation du parent)
	include_ecrire('inc_index.php3');
	marquer_indexer ('forum', $id_parent);

	// changer le statut de toute l'arborescence dependant de ce message
	$id_messages = array($id_forum);
	while ($id_messages) {
		$id_messages = join(',', $id_messages);
		$query_forum = "UPDATE spip_forum SET statut='$statut' WHERE id_forum IN ($id_messages)";
		$result_forum = spip_query($query_forum);
		$query_forum = "SELECT id_forum FROM spip_forum WHERE id_parent IN ($id_messages)";
		$result_forum = spip_query($query_forum);
		unset($id_messages);
		while ($row = spip_fetch_array($result_forum)) {
			$id_messages[] = $row['id_forum'];
		}
	}
}

?>
