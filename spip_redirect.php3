<?php
// redirige vers l'URL canonique de l'article,
// en passant le parametre recalcul=oui

include ("ecrire/inc_version.php3");

if (file_exists("inc-urls.php3")) {
	include_local ("inc-urls.php3");
}
else {
	include_local ("inc-urls-dist.php3");
}

if ($id_article) {
	$url = generer_url_article($id_article);
}
else if ($id_breve) {
	$url = generer_url_breve($id_breve);
}
else if ($id_forum) {
	$url = generer_url_forum($id_forum);
}
else if ($id_rubrique) {
	$url = generer_url_rubrique($id_rubrique);
}
else if ($id_mot) {
	$url = generer_url_mot($id_mot);
}
else if ($id_auteur) {
	$url = generer_url_auteur($id_auteur);
}
else {
	$url = 'ecrire/';
}
if (strpos($url,'?')) {
	$super='&';
}
else {
	$super='?';
}
if ($recalcul) $url .= $super."recalcul=$recalcul";
@header("Location: $url");

?>