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
	return "article$id_article.html";
}

function generer_url_rubrique($id_rubrique) {
	return "rubrique$id_rubrique.html";
}

function generer_url_breve($id_breve) {
	return "breve$id_breve.html";
}

function generer_url_mot($id_mot) {
	return "mot$id_mot.html";
}

function generer_url_auteur($id_auteur) {
	return "auteur$id_auteur.html";
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

// a mettre dans ecrire/inc_threads.php3 avec les autres trucs de forum
function racine_forum($id_forum){
	if (!$id_forum = intval($id_forum)) return;
	$query = "SELECT id_parent, id_rubrique, id_article, id_breve FROM spip_forum WHERE id_forum=".$id_forum;
	$result = spip_query($query);
	if($row = spip_fetch_array($result)){
		if($row['id_parent']) {
			return racine_forum($row['id_parent']);
		}
		else {
			if($row['id_rubrique']) return array('rubrique',$row['id_rubrique'], $id_forum);
 			if($row['id_article']) return array('article',$row['id_article'], $id_forum);
			if($row['id_breve']) return array('breve',$row['id_breve'], $id_forum);
		}
	}
} 

function generer_url_forum($id_forum, $show_thread=false) {
	list($type, $id, $id_thread) = racine_forum($id_forum);
	if ($id_thread>0 AND $show_thread)
		$id_forum = $id_thread;
	switch($type) {
		case 'article':
			return generer_url_article($id)."#forum$id_forum";
			break;
		case 'breve':
			return generer_url_breve($id)."#forum$id_forum";
			break;
		case 'rubrique':
			return generer_url_rubrique($id)."#forum$id_forum";
			break;
		default:
			return "forum$id_forum.html";
	}
}

?>
