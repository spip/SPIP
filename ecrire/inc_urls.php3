<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_URLS")) return;
define("_ECRIRE_INC_URLS", "1");


function generer_url_article($id_article) {
	$url = "../spip_redirect.php3?id_article=$id_article";
	return $url;
}

function generer_url_rubrique($id_rubrique) {
	$url = "../spip_redirect.php3?id_rubrique=$id_rubrique";
	return $url;
}

function generer_url_breve($id_breve) {
	$url = "../spip_redirect.php3?id_breve=$id_breve";
	return $url;
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

?>
