<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_STATS")) return;
define("_INC_STATS", "1");

function ecrire_stats() {
	global $id_article, $id_breve, $id_rubrique, $admin_ok;
	include_ecrire("inc_db_mysql.php3");	// necessaire si on tombe dans le cache
	include_ecrire("inc_connect.php3");
	if (!$GLOBALS['db_ok'])
		return;

	// Essai de fichier de log simplifie
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
	if (ereg("^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)$", $log_ip, $r)) {
		$log_ip = sprintf("0x%02x%02x%02x%02x", $r[1], $r[2], $r[3], $r[4]);
	}
	else return;

	// Archivage des visites temporaires
	$date = date("Y-m-d");
	$last_date = lire_meta("date_statistiques");

	if (lire_meta('calculer_referers_now') == 'oui') {
		include_ecrire("inc_meta.php3");
		include_ecrire("inc_statistiques.php3");
		ecrire_meta('calculer_referers_now', 'non');
		ecrire_metas();
		calculer_referers();
	} else if ($date != $last_date) {
		include_ecrire("inc_meta.php3");
		include_ecrire("inc_statistiques.php3");
		ecrire_meta("date_statistiques", $date);
		ecrire_metas();
		calculer_visites($last_date);
		// poser un message pour le prochain hit
		if (lire_meta('activer_statistiques_ref') == 'oui') {
			ecrire_meta('calculer_referers_now','oui');
			ecrire_metas();
		}
	}

	// Log simple des visites
	if ($log_type != "autre") {
		$query = "INSERT IGNORE INTO spip_visites_temp (ip, type, id_objet) ".
			"VALUES ($log_ip, '$log_type', $log_id_num)";
		spip_query($query);
	}

	// Log complexe (referers)
	if (lire_meta('activer_statistiques_ref') == 'oui') {
		$url_site_spip = lire_meta('adresse_site');
		$url_site_spip = eregi_replace("^(https?|ftp://)www\.", "\\1(www)?\.", $url_site_spip);
		$log_referer = $GLOBALS['HTTP_REFERER'];
		if (eregi($url_site_spip, $log_referer) AND !$GLOBALS['var_recherche']) $log_referer = "";
		if ($log_referer) {
			$referer_md5 = '0x'.substr(md5($log_referer), 0, 16);
			$query = "INSERT IGNORE INTO spip_referers_temp (ip, referer, referer_md5, type, id_objet) ".
				"VALUES ($log_ip, '$log_referer', $referer_md5, '$log_type', $log_id_num)";
			spip_query($query);
		}
	}


	// popularite, mise a jour dix minutes
	$date_popularite = lire_meta('date_stats_popularite');
	if ((time() - $date_popularite) > 600) {
		include_ecrire("inc_statistiques.php3");
		calculer_popularites();
	}


	// traiter les referers toutes les heures
	$date_refs = lire_meta('date_stats_referers');
	if ((time() - $date_refs) > 3600) {
		include_ecrire("inc_meta.php3");
		ecrire_meta("date_stats_referers", time());
		ecrire_meta('calculer_referers_now', 'oui');
		ecrire_metas();
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

		bouton_admin("$visites visites&nbsp;; popularit&eacute;&nbsp;: $popularite", "./ecrire/statistiques_visites.php3?id_article=$id_article");
	}
}

?>
