<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// fonction produisant les URL d'acces en lecture ou en ecriture 
// des items des tables SQL principales, selon le statut de publication

// http://doc.spip.org/@generer_url_ecrire_article
function generer_url_ecrire_article($id, $suite='', $ancre='', $statut='') {
	$a = "id_article=" . intval($id);
	if (!$statut) {
		$statut = sql_fetch(spip_query("SELECT statut FROM spip_articles WHERE $a"));
		$statut = $statut['statut'];
	}
	spip_log("url $id $statut");
	if ($suite) $a .= "&$suite";
	if ($statut == 'publie') {
		$a .= "&ancre=" . $ancre;
		return generer_url_action('redirect', $a);
	} else	return generer_url_ecrire('articles', $a) . ($ancre ? "#$ancre" : '');
}

// http://doc.spip.org/@generer_url_ecrire_rubrique
function generer_url_ecrire_rubrique($id, $suite='', $ancre='', $statut='') {
	$a = "id_rubrique=" . intval($id);
	if (!$statut) {
		$statut = sql_fetch(spip_query("SELECT statut FROM spip_rubriques WHERE $a"));
		$statut = $statut['statut'];
	}
	if ($suite) $a .= "&$suite";
	if ($statut == 'publie') {
		$a .= "&ancre=" . $ancre;
		return generer_url_action('redirect', $a);
	} else	return generer_url_ecrire('naviguer',$a) . ($ancre ? "#$ancre" : '');
}

// http://doc.spip.org/@generer_url_ecrire_breve
function generer_url_ecrire_breve($id, $suite='', $ancre='', $statut='') {
	$a = "id_breve=" . intval($id);
	if (!$statut) {
		$statut = sql_fetch(spip_query("SELECT statut FROM spip_breves WHERE $a"));
		$statut = $statut['statut'];
	}
	if ($suite) $a .= "&$suite";
	if ($statut == 'publie') {
		$a .= "&ancre=" . $ancre;
		return generer_url_action('redirect', $a);
	} else	return generer_url_ecrire('breves_voir',$a) . ($ancre ? "#$ancre" : '');
}

// http://doc.spip.org/@generer_url_ecrire_mot
function generer_url_ecrire_mot($id, $suite='', $ancre='', $statut='') {
	$a = "id_mot=" . intval($id);
	if ($suite) $a .= "&$suite";
	if (!$statut) {
		$a .= "&ancre=" . $ancre;
		return generer_url_action('redirect', $a);
	} else	return generer_url_ecrire('mots_edit',$a) . ($ancre ? "#$ancre" : '');
}

// http://doc.spip.org/@generer_url_ecrire_site
function generer_url_ecrire_site($id, $suite='', $ancre='', $statut='') {
	$a = "id_syndic=" . intval($id);
	if ($suite) $a .= "&$suite";
	if (!$statut) {
		$a .= "&ancre=" . $ancre;
		return generer_url_action('redirect', $a);
	} else	return generer_url_ecrire('sites',$a) . ($ancre ? "#$ancre" : '');
}

// http://doc.spip.org/@generer_url_ecrire_auteur
function generer_url_ecrire_auteur($id, $suite='', $ancre='', $statut='') {
	$a = "id_auteur=" . intval($id);
	if ($suite) $a .= "&$suite";
	if (!$statut) {
		$a .= "&ancre=" . $ancre;
		return generer_url_action('redirect', $a);
	} else	return generer_url_ecrire('auteur_infos',$a) . ($ancre ? "#$ancre" : '');
}

// http://doc.spip.org/@generer_url_ecrire_forum
function generer_url_ecrire_forum($id, $suite='', $ancre='', $statut='') {
	$a = "id_forum=" . intval($id);
	if ($suite) $a .= "&$suite"; 
	$a .= "&ancre=" . $ancre;
	return generer_url_action('redirect', $a);
}

// http://doc.spip.org/@generer_url_ecrire_document
function generer_url_ecrire_document($id, $suite='', $ancre='', $statut='') {
	include_spip('inc/documents');
	return generer_url_document_dist($id);
}

// http://doc.spip.org/@generer_url_ecrire_statistiques
function generer_url_ecrire_statistiques($id_article) {
	return generer_url_ecrire('statistiques_visites', "id_article=$id_article");
}

// en cas de chargement a partir de l'espace de redac, rabattre la production
// des URL publiques vers les URL privees en cas d'item non publies 

if (!_DIR_RESTREINT) {

  if (!function_exists('generer_url_article')) {
// http://doc.spip.org/@generer_url_article
	function generer_url_article($id, $args='', $ancre='', $stat='')
		{ return generer_url_ecrire_article($id, $args, $ancre, $stat);}
  }
  if (!function_exists('generer_url_rubrique')) {
// http://doc.spip.org/@generer_url_rubrique
	function generer_url_rubrique($id, $args='', $ancre='', $stat='')
		{ return generer_url_ecrire_rubrique($id, $args, $ancre, $stat);}
  }
  if (!function_exists('generer_url_breve')) {
// http://doc.spip.org/@generer_url_breve
	function generer_url_breve($id, $args='', $ancre='', $stat='')
		{ return generer_url_ecrire_breve($id, $args, $ancre, $stat);}
  }
  if (!function_exists('generer_url_mot')) {
// http://doc.spip.org/@generer_url_mot
	function generer_url_mot($id, $args='', $ancre='', $stat='')
		{ return generer_url_ecrire_mot($id, $args, $ancre, $stat);}
  }
  if (!function_exists('generer_url_site')) {
// http://doc.spip.org/@generer_url_site
	function generer_url_site($id, $args='', $ancre='', $stat='')
		{ return generer_url_ecrire_site($id, $args, $ancre, $stat);}
  }
  if (!function_exists('generer_url_auteur')) {
// http://doc.spip.org/@generer_url_auteur
	function generer_url_auteur($id, $args='', $ancre='', $stat='')
		{ return generer_url_ecrire_auteur($id, $args, $ancre, $stat);}
  }
  if (!function_exists('generer_url_forum')) {
// http://doc.spip.org/@generer_url_forum
	function generer_url_forum($id, $args='', $ancre='', $stat='')
		{ return generer_url_ecrire_forum($id, $args, $ancre, $stat);}
  }
  if (!function_exists('generer_url_document')) {
// http://doc.spip.org/@generer_url_document
	function generer_url_document($id, $args='', $ancre='', $stat='')
		{ return generer_url_ecrire_document($id, $args, $ancre, $stat);}
  }
 }
?>
