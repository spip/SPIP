<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


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
function controle_cache_forum($action, $id, $texte, $fond, $fonction, $redirect='') {
	$link = new Link();

	$link->addvar('controle_forum', $action);
	$link->addvar('id_controle_forum', $id);
	$link->addvar('hash', calculer_action_auteur("$action$id"));

	if ($redirect)
		$link->addvar('redirect', $redirect);

	return icone($texte,
		$link->geturl(),
		$fond,
		$fonction,
		"right",
		'non');
}

// tous les boutons de controle d'un forum
function boutons_controle_forum($id_forum, $forum_stat, $forum_id_auteur=0, $ref, $forum_ip) {
	$controle = '';

	// selection du logo et des boutons correspondant a l'etat du forum
	switch ($forum_stat) {
		# forum sous un article dans l'espace prive
		case "prive":
			$logo = "forum-interne-24.gif";
			$valider = false;
			$valider_repondre = false;
			$supprimer = 'supp_forum_priv';
			break;
		# forum des administrateurs
		case "privadmin":
			$logo = "forum-admin-24.gif";
			$valider = false;
			$valider_repondre = false;
			$supprimer = false;
			break;
		# forum de l'espace prive, supprime (non revalidable,
		# d'ailleurs on ne sait plus a quel type de forum il appartenait)
		case "privoff":
			$logo = "forum-interne-24.gif";
			$valider = false;
			$valider_repondre = false;
			$supprimer = false;
			break;
		# forum general de l'espace prive
		case "privrac":
			$logo = "forum-interne-24.gif";
			$valider = false;
			$valider_repondre = false;
			$supprimer = 'supp_forum_priv';
			break;

		# forum publie sur le site public
		case "publie":
			$logo = "forum-public-24.gif";
			$valider = false;
			$valider_repondre = false;
			$supprimer = 'supp_forum';
			break;
		# forum supprime sur le site public
		case "off":
			$logo = "forum-public-24.gif";
			$valider = 'valid_forum';
			$valider_repondre = false;
			$supprimer = false;
			$message = "<BR><FONT COLOR='red'><B>"._T('info_message_supprime')." $forum_ip</B></FONT>";
			if($forum_id_auteur)
				$message .= " - <A HREF='auteurs_edit.php3?id_auteur="
				.$forum_id_auteur."'>" ._T('lien_voir_auteur'). "</A>";
			break;
		# forum propose (a moderer) sur le site public
		case "prop":
			$logo = "forum-public-24.gif";
			$valider = 'valid_forum';
			$valider_repondre = true;
			$supprimer = 'supp_forum';
			break;
		default:
			return;
	}

	if ($message)
		$controle .= $message;

	if ($supprimer)
		$controle .= controle_cache_forum($supprimer,
			$id_forum,
			_T('icone_supprimer_message'), 
			$logo,
			"supprimer.gif");

	if ($valider)
		$controle .= controle_cache_forum($valider,
			$id_forum,
			_T('icone_valider_message'), 
			$logo,
			"creer.gif");

	if ($valider_repondre) {
		$link = new Link();
		$redirect = "../forum.php3?$ref&id_forum=$id_forum&retour=ecrire/"
			.urlencode($link->getUrl());

		$controle .= controle_cache_forum($valider,
			$id_forum,
			_T('icone_valider_message') . " &amp; " .
			_T('lien_repondre_message'),
			$logo,
			"creer.gif",
			$redirect
		);
	}

	return $controle;
}

// Index d'invalidation des forums
function calcul_index_forum($id_article, $id_breve, $id_rubrique, $id_syndic) {
	if ($id_article) return 'a'.$id_article; 
	if ($id_breve) return 'b'.$id_breve;
	if ($id_rubrique) return 'r'.$id_rubrique;
	if ($id_syndic) return 's'.$id_syndic;
}

//
// Recalculer tous les threads
//
function calculer_threads() {
	// fixer les id_thread des debuts de discussion
	spip_query("UPDATE spip_forum SET id_thread=id_forum
	WHERE id_parent=0");

	// reparer les messages qui n'ont pas l'id_secteur de leur parent
	do {
		$discussion = "0";
		$precedent = 0;
		$r = spip_query("SELECT fille.id_forum AS id,
		maman.id_thread AS thread
		FROM spip_forum AS fille, spip_forum AS maman
		WHERE fille.id_parent = maman.id_forum
		AND fille.id_thread <> maman.id_thread
		ORDER BY thread");
		while (list($id, $thread) = spip_fetch_array($r)) {
			if ($thread == $precedent)
				$discussion .= ",$id";
			else {
				if ($precedent)
					spip_query("UPDATE spip_forum SET id_thread=$precedent
					WHERE id_forum IN ($discussion)");
				$precedent = $thread;
				$discussion = "$id";
			}
		}
		spip_query("UPDATE spip_forum SET id_thread=$precedent
		WHERE id_forum IN ($discussion)");
	} while ($discussion != "0");
}


?>
