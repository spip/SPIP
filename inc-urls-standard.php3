<?php

// executer une seule fois
if (defined("_INC_URLS2")) return;
define("_INC_URLS2", "1");


function generer_url_article($id_article) {
	return "article.php3?id_article=$id_article";
}

function generer_url_rubrique($id_rubrique) {
	return "rubrique.php3?id_rubrique=$id_rubrique";
}

function generer_url_breve($id_breve) {
	return "breve.php3?id_breve=$id_breve";
}

function generer_url_forum($id_forum) {
	return "forum.php3?id_forum=$id_forum";
}

function generer_url_mot($id_mot) {
	return "mot.php3?id_mot=$id_mot";
}

function generer_url_auteur($id_auteur) {
	return "auteur.php3?id_auteur=$id_auteur";
}

function generer_url_document($id_document) {
	if ($id_document > 0) {
		$query = "SELECT fichier FROM spip_documents WHERE id_document = $id_document";
		$result = spip_query($query);
		if ($row = spip_fetch_array($result)) {
			$url = $row['fichier'];
		}
	}
	return $url;
}

function recuperer_parametres_url($fond, $url) {
	global $contexte;
	return;
}

?>