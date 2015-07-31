<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// fonction produisant les URL d'acces en lecture ou en ecriture 
// des items des tables SQL principales, selon le statut de publication

// http://doc.spip.org/@generer_url_ecrire_article
function generer_url_ecrire_article($id, $suite='', $ancre='', $statut='', $connect='') {
	$a = "id_article=" . intval($id);
	if (!$statut) {
		$statut = sql_getfetsel('statut', 'spip_articles', $a,'','','','',$connect);
	}
	if ($suite) $a .= "&$suite";
	if ($statut == 'publie') {
		return url_absolue(_DIR_RACINE . generer_url_entite($id, 'article', $suite, $ancre, false));
	} else	return generer_url_ecrire('articles', $a) . ($ancre ? "#$ancre" : '');
}

// http://doc.spip.org/@generer_url_ecrire_rubrique
function generer_url_ecrire_rubrique($id, $suite='', $ancre='', $statut='', $connect='') {
	$a = "id_rubrique=" . intval($id);
	if (!$statut) {
		$statut = sql_getfetsel('statut', 'spip_rubriques', $a,'','','','',$connect);
	}
	if ($suite) $a .= "&$suite";
	if ($statut == 'publie') {
		return url_absolue(_DIR_RACINE . generer_url_entite($id, 'rubrique', $suite, $ancre, false));
	} else	return generer_url_ecrire('naviguer',$a) . ($ancre ? "#$ancre" : '');
}

// http://doc.spip.org/@generer_url_ecrire_breve
function generer_url_ecrire_breve($id, $suite='', $ancre='', $statut='', $connect='') {
	$a = "id_breve=" . intval($id);
	if (!$statut) {
		$statut = sql_getfetsel('statut', 'spip_breves', $a,'','','','',$connect);
	}
	if ($suite) $a .= "&$suite";
	if ($statut == 'publie') {
		return url_absolue(_DIR_RACINE . generer_url_entite($id, 'breve', $suite, $ancre, false));
	} else	return generer_url_ecrire('breves_voir',$a) . ($ancre ? "#$ancre" : '');
}

// http://doc.spip.org/@generer_url_ecrire_mot
function generer_url_ecrire_mot($id, $suite='', $ancre='', $statut='', $connect='') {
	$a = "id_mot=" . intval($id);
	if ($suite) $a .= "&$suite";
	if (!$statut) {
		return url_absolue(_DIR_RACINE . generer_url_entite($id, 'mot', $suite, $ancre, false));
	} else	return generer_url_ecrire('mots_edit',$a) . ($ancre ? "#$ancre" : '');
}

// http://doc.spip.org/@generer_url_ecrire_site
function generer_url_ecrire_site($id, $suite='', $ancre='', $statut='', $connect='') {
	$a = "id_syndic=" . intval($id);
	if ($suite) $a .= "&$suite";
	if (!$statut) {
		return url_absolue(_DIR_RACINE . generer_url_entite($id, 'site', $suite, $ancre, false));
	} else	return generer_url_ecrire('sites',$a) . ($ancre ? "#$ancre" : '');
}

// http://doc.spip.org/@generer_url_ecrire_auteur
function generer_url_ecrire_auteur($id, $suite='', $ancre='', $statut='', $connect='') {
	$a = "id_auteur=" . intval($id);
	if ($suite) $a .= "&$suite";
	if (!$statut) {
		return url_absolue(_DIR_RACINE . generer_url_entite($id, 'auteur', $suite, $ancre, false));
	} else	return generer_url_ecrire('auteur_infos',$a) . ($ancre ? "#$ancre" : '');
}

// http://doc.spip.org/@generer_url_ecrire_forum
function generer_url_ecrire_forum($id, $suite='', $ancre='', $statut='', $connect='') {
	$a = "id_forum=" . intval($id);
	if (!$statut) {
		$statut = sql_getfetsel('statut', 'spip_forum', $a,'','','','',$connect);
	}
	if ($suite) $a .= "&$suite";
	if ($statut == 'publie') {
		return url_absolue(_DIR_RACINE . generer_url_entite($id, 'forum', $suite, $ancre, false));
	}  else return generer_url_ecrire('controle_forum', "debut_id_forum=$id");
}

// http://doc.spip.org/@generer_url_ecrire_document
function generer_url_ecrire_document($id, $suite='', $ancre='', $statut='', $connect='') {
	include_spip('inc/documents');
	return generer_url_document_dist($id);
}

// http://doc.spip.org/@generer_url_ecrire_statistiques
function generer_url_ecrire_statistiques($id_article) {
	return generer_url_ecrire('statistiques_visites', "id_article=$id_article");
}

?>
