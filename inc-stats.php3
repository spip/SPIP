<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_STATS")) return;
define("_INC_STATS", "1");

function ecrire_stats() {
	global $id_article, $id_breve, $id_rubrique, $admin_ok;

	$activer_referers = lire_meta('activer_statistiques_ref');

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

	$date = date("Y-m-d");
	$last_date = lire_meta("date_statistiques");

	// Archivage des visites temporaires
	if ($date != $last_date) {
		include_ecrire("inc_connect.php3");
		if ($GLOBALS['db_ok']) {
			include_ecrire("inc_meta.php3");
			include_ecrire("inc_statistiques.php3");
			ecrire_meta("date_statistiques", $date);
			ecrire_metas();
			calculer_visites($last_date);
		}
	}

	// Log simple des visites
	if ($log_type != "autre") {
		include_ecrire("inc_connect.php3");
		if ($GLOBALS['db_ok']) {
			$query = "INSERT DELAYED IGNORE INTO spip_visites_temp (ip, type, id_objet) ".
				"VALUES (INET_ATON('$log_ip'), '$log_type', $log_id_num)";
			spip_query($query);
		}
	}

	// Log complexe (referers)
	if ($activer_referers == 'oui') {
		$url_site_spip = lire_meta('adresse_site');
		$url_site_spip = eregi_replace("^(https?|ftp://)www\.", "\\1(www)?\.", $url_site_spip);
		$log_referer = $GLOBALS['HTTP_REFERER'];
		if (eregi($url_site_spip, $log_referer)) $log_referer = "";
		if ($log_referer) {
			include_ecrire("inc_connect.php3");
			if ($GLOBALS['db_ok']) {
				$referer_md5 = '0x'.substr(md5($log_referer), 0, 16);
				$query = "INSERT DELAYED IGNORE INTO spip_referers_temp (ip, referer, referer_md5, type, id_objet) ".
					"VALUES (INET_ATON('$log_ip'), '$log_referer', $referer_md5, '$log_type', $log_id_num)";
				spip_query($query);
			}
		}

		// Optimiser les referers
		$date_refs = $meta['date_stats_referers'];
		if ((time() - $date_refs) > 24 * 3600) {
			include_ecrire("inc_connect.php3");
			if ($GLOBALS['db_ok']) {
				include_ecrire("inc_meta.php3");
				ecrire_meta("date_stats_referers", time());
				ecrire_metas();
				include_ecrire ("inc_statistiques.php3");
				optimiser_referers();
			}
		}
	}
}


function afficher_raccourci_stats($id_article) {
	$query = "SELECT visites, popularite FROM spip_articles WHERE id_article=$id_article AND statut='publie'";
	$result = spip_query($query);
	if ($row = mysql_fetch_array($result)) {
		$visites = intval($row['visites']);
		$popularite = intval($row['popularite']);

		if ($visites > 0) bouton_admin("Evolution des visites", "./ecrire/statistiques_visites.php3?id_article=$id_article");

		$query = "SELECT COUNT(DISTINCT ip) AS c FROM spip_visites_temp WHERE type='article' AND id_objet=$id_article";
		$result = spip_query($query);
		if ($row = @mysql_fetch_array($result)) {
			$visites = $visites + $row['c'];
		}
		echo "[$visites visites&nbsp;; popularit&eacute;&nbsp;: $popularite&nbsp;%]";
	}
}



?>
