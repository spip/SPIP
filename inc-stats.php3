<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_STATS")) return;
define("_INC_STATS", "1");

function ecrire_stats() {
	global $id_article, $id_breve, $id_rubrique, $admin_ok;
	
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
	else return;

	$url_site_spip = lire_meta('adresse_site');
	$url_site_spip = eregi_replace("http://www\.","",$url_site_spip);
	$log_referer = $GLOBALS['HTTP_REFERER'];
	if ($url_site_spip == '' OR eregi($url_site_spip, $log_referer)) $log_referer = "";
	
	$log_date = date("Y-m-d");

	$query = "INSERT DELAYED INTO spip_visites_temp (date, ip, type, referer) ".
		"VALUES ('$log_date', INET_ATON('$log_ip'), '$log_type$log_id_num', '$log_referer')";
	spip_query($query);
}


function afficher_raccourci_stats($id_article) {
	$query = "SELECT visites, referers FROM spip_articles WHERE id_article=$id_article AND statut='publie'";
	$result = spip_query($query);
	if ($row = mysql_fetch_array($result)) {
		$visites = $row['visites'];
		$referers = $row['referers'];
		
		if ($visites > 0) bouton_admin("Evolution des visites", "./ecrire/statistiques_visites.php3?id_article=$id_article");

		$query = "SELECT COUNT(DISTINCT ip) AS c FROM spip_visites_temp WHERE type = 'article$id_article'";
		$result = spip_query($query);
		if ($row = mysql_fetch_array($result)) {
			$visites = $visites + $row['c'];
		}
		echo "[$visites visites / $referers entr&eacute;es directes]";
	}
}



?>
