<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_STATS")) return;
define("_INC_STATS", "1");

function ecrire_stats() {
	global $id_article, $id_breve, $id_rubrique;

	if ($GLOBALS['HTTP_X_FORWARDED_FOR'])
		$log_ip = $GLOBALS['HTTP_X_FORWARDED_FOR'];
	else
		$log_ip = $GLOBALS['REMOTE_ADDR'];
	if ($id_rubrique > 0) {
		$log_type = "rubrique";
		$log_id_num = $id_rubrique;
	}
	else if ($id_article > 0) {
		$log_type = "article";
		$log_id_num = $id_article;
	}
	else if ($id_breve > 0) {
		$log_type = "breve";
		$log_id_num = $id_breve;
	}
	else {
		$log_type = "autre";
		$log_id_num = 0;
	}

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
	if (lire_meta('activer_statistiques_ref') == 'oui') {
		$url_site_spip = lire_meta('adresse_site');
		$url_site_spip = eregi_replace("^((https?|ftp)://)?(www\.)?", "", $url_site_spip);
		$log_referer = $GLOBALS['HTTP_REFERER'];
		if (($url_site_spip<>'') AND strpos('-'.strtolower($log_referer), strtolower($url_site_spip)) AND !$GLOBALS['var_recherche']) $log_referer = "";
		if ($log_referer) {
			$referer_md5 = '0x'.substr(md5($log_referer), 0, 16);
			$query = "INSERT IGNORE INTO spip_referers_temp (ip, referer, referer_md5, type, id_objet) ".
				"VALUES ($log_ip, '$log_referer', $referer_md5, '$log_type', $log_id_num)";
			spip_query($query);
		}
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

		bouton_admin(_T('stats_visites_et_popularite', array('visites' => $visites, 'popularite' => $popularite)), "./ecrire/statistiques_visites.php3?id_article=$id_article");
	}
}

?>
