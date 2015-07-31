<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_URLS")) return;
define("_ECRIRE_INC_URLS", "1");


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
		return "naviguer.php3?coll=$id_rubrique";
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

function generer_url_auteur($id_auteur) {
	$url = "../spip_redirect.php3?id_auteur=$id_auteur";
	return $url;
}

function generer_url_document($id_document) {
	if (intval($id_document) <= 0)
		return '';
	if ((lire_meta("creer_htpasswd")) == 'oui')
		return "../spip_acces_doc.php3?id_document=$id_document";
	if ($row = @spip_fetch_array(spip_query("SELECT fichier FROM spip_documents WHERE id_document = $id_document")))
		return '../' . ($row['fichier']);
	return '';
}

?>
