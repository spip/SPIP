<?php

include ("ecrire/inc_version.php3");

include_ecrire("inc_meta.php3");
include_ecrire("inc_admin.php3");
include_local("inc-cache.php3");

if ($purger_cache == "oui") {
	if (verifier_action_auteur("purger_cache", $hash, $id_auteur)) {
		include_ecrire('inc_invalideur.php3');
		supprime_invalideurs();
		purger_repertoire('CACHE', 0);
	}
}

if ($purger_squelettes == "oui") {
	if (verifier_action_auteur("purger_squelettes", $hash, $id_auteur))
		purger_repertoire('CACHE', 0, '^skel_');
}


//
// Suppression de forums
//
function changer_statut_forum($id_forum, $statut) {
	$forum_parents = array('id_rubrique', 'id_article', 'id_breve', 'id_syndic');
	$result = spip_query("SELECT id_parent, " . join(",", $forum_parents) .
	" FROM spip_forum WHERE id_forum=$id_forum");

	if (!($row = spip_fetch_array($result)))
		return;

	$id_parent = $row['id_parent'];
	include_ecrire('inc_invalideur.php3');
	foreach ($forum_parents as $id) {
		if ($id_num = $row[$id])
			suivre_invalideur("$id='$id_num'", "spip_" . $id . '_caches');
	}

	// Signaler au moteur de recherche qu'il faut reindexer le thread
	include_ecrire('inc_index.php3');
	marquer_indexer ('forum', $id_parent);

	// changer le statut de toute l'arborescence dependant de ce message
	$id_messages = array($id_forum);
	while ($id_messages) {
		$id_messages = join(',', $id_messages);
		$query_forum = "UPDATE spip_forum SET statut='$statut'
		WHERE id_forum IN ($id_messages)";
		$result_forum = spip_query($query_forum);
		$query_forum = "SELECT id_forum FROM spip_forum
		WHERE id_parent IN ($id_messages)";
		$result_forum = spip_query($query_forum);
		unset($id_messages);
		while ($row = spip_fetch_array($result_forum))
			$id_messages[] = $row['id_forum'];
	}
}

if ($supp_forum OR $supp_forum_priv OR $valid_forum) {
	$verif = $supp_forum ? "supp_forum $supp_forum" : ($supp_forum_priv ? "supp_forum_priv $supp_forum_priv" : "valid_forum $valid_forum");
	if (verifier_action_auteur($verif, $hash, $id_auteur)) {
		if ($supp_forum) 
			changer_statut_forum($supp_forum, 'off');
		else if ($supp_forum_priv)
			changer_statut_forum($supp_forum_priv, 'privoff');
		else if ($valid_forum)
			changer_statut_forum($valid_forum, 'publie');
	}
}
 

@header ("Location: ./ecrire/" . $redirect);

?>
