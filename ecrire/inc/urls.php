<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// fonction produisant les URL d'acces en lecture ou en ecriture 
// des items des tables SQL principales, selon le statut de publication

// http://doc.spip.org/@generer_url_ecrire_article
function generer_url_ecrire_article($id, $args='', $ancre='', $statut='', $connect='') {
	$a = "id_article=" . intval($id);
	if (!$statut) {
		$statut = sql_getfetsel('statut', 'spip_articles', $a,'','','','',$connect);
	}
	$h = ($statut == 'publie' OR $connect)
	? generer_url_entite_absolue($id, 'article', $args, $ancre, $connect)
	: (generer_url_ecrire('articles', $a . ($args ? "&$args" : ''))
		. ($ancre ? "#$ancre" : ''));
	return $h;
}

// http://doc.spip.org/@generer_url_ecrire_rubrique
function generer_url_ecrire_rubrique($id, $args='', $ancre='', $statut='', $connect='') {
	$a = "id_rubrique=" . intval($id);
	if (!$statut) {
		$statut = sql_getfetsel('statut', 'spip_rubriques', $a,'','','','',$connect);
	}
	$h = ($statut == 'publie' OR $connect)
	? generer_url_entite_absolue($id, 'rubrique', $args, $ancre, $connect)
	: (generer_url_ecrire('naviguer',$a . ($args ? "&$args" : ''))
		. ($ancre ? "#$ancre" : ''));
	return $h;
}

// http://doc.spip.org/@generer_url_ecrire_breve
function generer_url_ecrire_breve($id, $args='', $ancre='', $statut='', $connect='') {
	$a = "id_breve=" . intval($id);
	if (!$statut) {
		$statut = sql_getfetsel('statut', 'spip_breves', $a,'','','','',$connect);
	}
	$h = ($statut == 'publie' OR $connect)
	?  generer_url_entite_absolue($id, 'breve', $args, $ancre, $connect)
	: (generer_url_ecrire('breves_voir',$a . ($args ? "&$args" : ''))
		. ($ancre ? "#$ancre" : ''));
	return $h;
}

// http://doc.spip.org/@generer_url_ecrire_mot
function generer_url_ecrire_mot($id, $args='', $ancre='', $statut='', $connect='') {
	$a = "id_mot=" . intval($id);
	$h = (!$statut OR $connect)
	?  generer_url_entite_absolue($id, 'mot', $args, $ancre, $connect)
	: (generer_url_ecrire('mots_edit',$a . ($args ? "&$args" : ''))
		. ($ancre ? "#$ancre" : ''));
	return $h;
}

// http://doc.spip.org/@generer_url_ecrire_site
function generer_url_ecrire_site($id, $args='', $ancre='', $statut='', $connect='') {
	$a = "id_syndic=" . intval($id);
	$h = (!$statut OR $connect)
	?  generer_url_entite_absolue($id, 'site', $args, $ancre, $connect)
	: (generer_url_ecrire('sites',$a . ($args ? "&$args" : ''))
		. ($ancre ? "#$ancre" : ''));
	return $h;
}

// http://doc.spip.org/@generer_url_ecrire_auteur
function generer_url_ecrire_auteur($id, $args='', $ancre='', $statut='', $connect='') {
	$a = "id_auteur=" . intval($id);
	$h = (!$statut OR $connect)
	?  generer_url_entite_absolue($id, 'auteur', $args, $ancre, $connect)
	: (generer_url_ecrire('auteur_infos',$a . ($args ? "&$args" : ''))
		. ($ancre ? "#$ancre" : ''));
	return $h;
}

// http://doc.spip.org/@generer_url_ecrire_forum
function generer_url_ecrire_forum($id, $args='', $ancre='', $statut='', $connect='') {
	$a = "id_forum=" . intval($id);
	if (!$statut) {
		$statut = sql_getfetsel('statut', 'spip_forum', $a,'','','','',$connect);
	}
	$h = ($statut == 'publie' OR $connect)
	?  generer_url_entite_absolue($id, 'forum', $args, $ancre, $connect)
	: (generer_url_ecrire('controle_forum', "debut_id_forum=$id" . ($args ? "&$args" : ''))
		. ($ancre ? "#$ancre" : ''));
	return $h;
}

// http://doc.spip.org/@generer_url_ecrire_document
function generer_url_ecrire_document($id, $args='', $ancre='', $statut='', $connect='') {
	include_spip('inc/documents');
	return generer_url_document_dist($id);
}

// http://doc.spip.org/@generer_url_ecrire_statistiques
function generer_url_ecrire_statistiques($id_article) {
	return generer_url_ecrire('statistiques_visites', "id_article=$id_article");
}

?>
