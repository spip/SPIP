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


//
if (!defined("_ECRIRE_INC_VERSION")) return;

function ecrire_stats() {
	global $id_article, $id_breve, $id_rubrique;

	if ($GLOBALS['HTTP_X_FORWARDED_FOR'])
		$log_ip = $GLOBALS['HTTP_X_FORWARDED_FOR'];
	else
		$log_ip = $GLOBALS['REMOTE_ADDR'];

	if ($log_id_num = intval($id_rubrique))
		$log_type = "rubrique";
	else if ($log_id_num = intval($id_article))
		$log_type = "article";
	else if ($log_id_num = intval($id_breve))
		$log_type = "breve";
	else
		$log_type = "autre";

	// Conversion IP 4 octets -> entier 32 bits
	if (ereg("^(::ffff:)?([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)$", $log_ip, $r)) {
		$log_ip = sprintf("0x%02x%02x%02x%02x", $r[2], $r[3], $r[4], $r[5]);
	}
	else return;

	//
	// Loguer la visite dans la base si possible
	//
	if ($log_type != "autre") {
		$query = "INSERT IGNORE INTO spip_visites_temp (ip, type, id_objet) ".
			"VALUES ($log_ip, '$log_type', $log_id_num)";
		spip_query($query);
	}

	//
	// Loguer le referer
	//
	$url_site_spip = lire_meta('adresse_site');
	$url_site_spip = eregi_replace("^((https?|ftp)://)?(www\.)?", "", $url_site_spip);
	$log_referer = $GLOBALS['HTTP_REFERER'];
	if (($url_site_spip<>'') AND strpos('-'.strtolower($log_referer), strtolower($url_site_spip)) AND !$GLOBALS['var_recherche']) $log_referer = "";
	if ($log_referer) {
		$referer_md5 = '0x'.substr(md5($log_referer), 0, 15);
		$query = "INSERT IGNORE INTO spip_referers_temp (ip, referer, referer_md5, type, id_objet) ".
			"VALUES ($log_ip, '".addslashes($log_referer)."', $referer_md5, '$log_type', $log_id_num)";
		spip_query($query);
	}
}


function afficher_raccourci_stats($id_article) {
	$query = "SELECT visites, popularite FROM spip_articles WHERE id_article=$id_article AND statut='publie'";
	$result = spip_query($query);
	if ($row = @spip_fetch_array($result)) {
		$visites = intval($row['visites']);
		$popularite = ceil($row['popularite']);

		$query = "SELECT COUNT(DISTINCT ip) AS c FROM spip_visites_temp WHERE type='article' AND id_objet=$id_article";
		$result = spip_query($query);
		if ($row = @spip_fetch_array($result)) {
			$visites = $visites + $row['c'];
		}

		return array('visites' => $visites, 'popularite' => $popularite);
	}
}

?>
