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
if (!defined("_ECRIRE_INC_VERSION")) return;


function generer_url_article($id_article) {
	if (($row = spip_fetch_array(spip_query(
	"SELECT statut FROM spip_articles WHERE id_article=$id_article"
	))) AND ($row['statut'] == 'publie'))
		return "../spip_redirect.php3?id_article=$id_article";
	else
		return "articles.php3?id_article=$id_article";
}

function generer_url_rubrique($id_rubrique) {
	if (($row = spip_fetch_array(spip_query(
	"SELECT statut FROM spip_rubriques WHERE id_rubrique=$id_rubrique"
	))) AND ($row['statut'] == 'publie'))
		return "../spip_redirect.php3?id_rubrique=$id_rubrique";
	else
		return "naviguer.php3?id_rubrique=$id_rubrique";
}

function generer_url_breve($id_breve) {
	if (($row = spip_fetch_array(spip_query(
	"SELECT statut FROM spip_breves WHERE id_breve=$id_breve"
	))) AND ($row['statut'] == 'publie'))
		return "../spip_redirect.php3?id_breve=$id_breve";
	else
		return "breves_voir.php3?id_breve=$id_breve";
}

function generer_url_forum($id_forum) {
	$url = "../spip_redirect.php3?id_forum=$id_forum";
	return $url;
}

function generer_url_mot($id_mot) {
	$url = "../spip_redirect.php3?id_mot=$id_mot";
	return $url;
}

function generer_url_site($id_syndic) {
	$url = "../spip_redirect.php3?id_syndic=$id_syndic";
	return $url;
}

function generer_url_auteur($id_auteur) {
	$url = "../spip_redirect.php3?id_auteur=$id_auteur";
	return $url;
}

function generer_url_document($id_document) {
	if (intval($id_document) <= 0)
		return '';
	if ($row = @spip_fetch_array(spip_query("SELECT fichier,distant
	FROM spip_documents WHERE id_document = $id_document"))) {
		if ($row['distant'] == 'oui') {
			$url = $row['fichier'];
		} else {
			$url = '../' . ($row['fichier']);
			if (($GLOBALS['meta']["creer_htaccess"]) == 'oui')
				$url = "../spip_image.php3?action=autoriser&doc=$id_document";
		}
	}
	return $url;
}

?>
