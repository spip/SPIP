<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_FORUM")) return;
define("_ECRIRE_INC_FORUM", "1");


//
// Suppression de forums
//
function changer_statut_forum($id_forum, $statut) {
	$result = spip_query("SELECT * FROM spip_forum WHERE id_forum=$id_forum");

	if (!($row = spip_fetch_array($result)))
		return;

	$id_parent = $row['id_parent'];

	// invalider les pages comportant ce forum
	include_ecrire('inc_invalideur.php3');
	$index_forum = calcul_index_forum($row['id_article'], $row['id_breve'], $row['id_rubrique'], $row['id_syndic']);
	suivre_invalideur("id='id_forum/$index_forum'");

	// Signaler au moteur de recherche qu'il faut reindexer le thread
	if ($id_parent) {
		include_ecrire('inc_index.php3');
		marquer_indexer ('forum', $id_parent);
	}

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

// Installer un bouton de moderation (securise) dans l'espace prive
function controle_cache_forum($action, $id, $texte, $lien, $fond, $fonc) {
	$link = $GLOBALS['clean_link'];
	$link->addvar('controle_forum', $action);
	$link->addvar('id_controle_forum', $id);
	$link->addvar('hash', calculer_action_auteur("$action$id"));
	return icone($texte,
		$link->geturl(),
		$fond,
		$fonction,
		"right",
		'non');
}

// Index d'invalidation des forums
function calcul_index_forum($id_article, $id_breve, $id_rubrique, $id_syndic) {
	return
	($id_article ? 'a'.$id_article : '') .
	($id_breve ? 'b'.$id_breve : '') .
	($id_rubrique ? 'r'.$id_rubrique : '') . 
	($id_syndic ? 's'.$id_syndic : '');
}

 
?>
