<?php

if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Moderation des forums specifique a un article
 * @param int $id_objet identifiant de l'article
 * @return string  : "non", "pos"(teriori), "pri"(ori), "abo"(nnement)
 */
function inc_article_accepter_forums_publics_dist($id_objet) {
	$accepter_forum = $GLOBALS['meta']["forums_publics"];
	$art_accepter_forum = Sql::getfetsel('accepter_forum', 'spip_articles', array(
		"id_article = ". intval($id_objet)
	));
	if ($art_accepter_forum) {
		$accepter_forum = $art_accepter_forum;
	}
	return substr($accepter_forum, 0, 3);
}


?>
