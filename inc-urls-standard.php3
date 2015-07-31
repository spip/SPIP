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

function generer_url_mot($id_mot) {
	return "mot.php3?id_mot=$id_mot";
}

function generer_url_auteur($id_auteur) {
	return "auteur.php3?id_auteur=$id_auteur";
}

function generer_url_document($id_document) {
	if (intval($id_document) <= 0)
		return '';
	if ((lire_meta("creer_htaccess")) == 'oui')
		return "spip_acces_doc.php3?id_document=$id_document";
	if ($row = @spip_fetch_array(spip_query("SELECT fichier FROM spip_documents WHERE id_document = $id_document")))
		return ($row['fichier']);
	return '';
}

function recuperer_parametres_url($fond, $url) {
	global $contexte;
	return;
}

//
// URLs des forums
//

function generer_url_forum($id_forum, $show_thread=false) {
	include_ecrire('inc_forum.php3');
	return generer_url_forum_dist($id_forum, $show_thread);
}

?>
