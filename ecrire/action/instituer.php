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

#
# Gere les actions cachees derrire le petit iframe de l'espace prive (faux Ajax)
#

function action_instituer_dist() {
	global $action, $arg, $hash, $id_auteur;
	include_spip('inc/session');
	if (!verifier_action_auteur("$action $arg", $hash, $id_auteur)) {
		include_spip('inc/minipres');
		minipres(_T('info_acces_interdit'));
	}

	ereg("^([^ ]*) (.*)$", $arg, $r);
	$var_nom = 'instituer_' . $r[1];
	if (function_exists($var_nom)) {
		spip_log("$var_nom $r[2]");
		$var_nom($r[2]);
	}
	else
		spip_log("action $action: $arg incompris");
}

function instituer_collaboration($debloquer_article) {
	global $id_auteur;
	if ($debloquer_article AND ($id_auteur = intval($id_auteur))) {
		if ($debloquer_article <> 'tous')
			$where_id = "AND id_article=".intval($debloquer_article);
		spip_query ("UPDATE spip_articles SET auteur_modif='0'
		WHERE auteur_modif=$id_auteur $where_id");
	}
}

function instituer_forum($arg) {
	list($id_forum, $statut) = split(' ', $arg);
	$id_forum = intval($id_forum);
	$result = spip_query("SELECT * FROM spip_forum WHERE id_forum=$id_forum");
	if (!($row = spip_fetch_array($result)))
		return;

	$id_parent = $row['id_parent'];

	// invalider les pages comportant ce forum
	include_spip('inc/invalideur');
	include_spip('inc/forum');
	$index_forum = calcul_index_forum($row['id_article'], $row['id_breve'], $row['id_rubrique'], $row['id_syndic']);
	suivre_invalideur("id='id_forum/$index_forum'");

	// Signaler au moteur de recherche qu'il faut reindexer le thread
	if ($id_parent) {
		include_spip('inc/indexation');
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

function instituer_article($arg) {
	list($id_article, $statut) = split(' ', $arg);
	if (!$statut) $statut = _request('statut_nouv'); // cas POST
	if (!$statut) return; // impossible mais sait-on jamais

	$id_article = intval($id_article);
	$result = spip_query("SELECT statut FROM spip_articles WHERE id_article=$id_article");

	if ($row = spip_fetch_array($result)) {
		$statut_ancien = $row['statut'];
		}

	if ($statut != $statut_ancien) {
		spip_query("UPDATE spip_articles SET statut='$statut',
		date=NOW() WHERE id_article=$id_article");

		include_spip('inc/rubriques');
		calculer_rubriques();

		if ($statut == 'publie') {
			if ($GLOBALS['meta']['activer_moteur'] == 'oui') {
			include_spip("inc/indexation");
			marquer_indexer('article', $id_article);
			}
			include_spip('inc/lang');
			include_spip('inc/texte');
			include_spip('inc/mail');
			envoyer_mail_publication($id_article);
		}

		if ($statut_ancien == 'publie') {
			include_spip('inc/invalideur');
			suivre_invalideur("id='id_article/$id_article'");
		}

		if ($statut == "prop" AND $statut_ancien != 'publie') {
			include_spip('inc/lang');
			include_spip('inc/texte');
			include_spip('inc/mail');
			envoyer_mail_proposition($id_article);
		}
	}
}

function instituer_breve($arg) {
	list($id_breve, $statut) = split(' ', $arg);

	$id_breve = intval($id_breve);
	$query = "SELECT statut FROM spip_breves WHERE id_breve=$id_breve";
	$result = spip_query($query);
	if ($row = spip_fetch_array($result)) {
		$statut_ancien = $row['statut'];
		}

	if ($statut != $statut_ancien) {
		spip_query("UPDATE spip_breves SET date_heure=NOW(),
		statut='$statut' WHERE id_breve=$id_breve");

		include_spip('inc/rubriques');
		calculer_rubriques();
	}
}
?>
