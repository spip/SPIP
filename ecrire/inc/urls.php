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


//
if (!defined("_ECRIRE_INC_VERSION")) return;


function generer_url_article($id_article) {
	if (($row = spip_fetch_array(spip_query(
	"SELECT statut FROM spip_articles WHERE id_article=$id_article"
	))) AND ($row['statut'] == 'publie'))
	  return generer_url_action('redirect', "id_article=$id_article");
	else	return generer_url_ecrire('articles',"id_article=$id_article");
}

function generer_url_rubrique($id_rubrique) {
	if (($row = spip_fetch_array(spip_query(
	"SELECT statut FROM spip_rubriques WHERE id_rubrique=$id_rubrique"
	))) AND ($row['statut'] == 'publie'))
	  return generer_url_action('redirect', "id_rubrique=$id_rubrique");
	else
	  return generer_url_ecrire('naviguer',"id_rubrique=$id_rubrique");
}

function generer_url_breve($id_breve) {
	if (($row = spip_fetch_array(spip_query(
	"SELECT statut FROM spip_breves WHERE id_breve=$id_breve"
	))) AND ($row['statut'] == 'publie'))
	  return generer_url_action('redirect', "id_breve=$id_breve");
	else
		return generer_url_ecrire('breves_voir',"id_breve=$id_breve");
}

function generer_url_forum($id_forum) {
  return generer_url_action('redirect', "id_forum=$id_forum");
}

function generer_url_mot($id_mot) {
  return  generer_url_action('redirect', "id_mot=$id_mot");
}

function generer_url_site($id_syndic) {
  return  generer_url_action('redirect', "id_syndic=$id_syndic");
}

function generer_url_auteur($id_auteur) {
  return generer_url_action('redirect', "id_auteur=$id_auteur");
}

function generer_url_document($id_document) {
	if (intval($id_document) <= 0) 
		return '';
	if ($row = @spip_fetch_array(spip_query("SELECT fichier,distant
	FROM spip_documents WHERE id_document = $id_document"))) {
		if ($row['distant'] == 'oui') {
			return $row['fichier'];
		} else {
			if (($GLOBALS['meta']["creer_htaccess"]) != 'oui')
				return _DIR_RACINE . ($row['fichier']);
			else 	return generer_url_action('autoriser', "arg=$id_document");
		}
	}

}

?>
