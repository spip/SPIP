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
	$query = "SELECT visites FROM spip_articles WHERE id_article=$id_article AND statut='publie'";
	$result = spip_query($query);
	if ($row = mysql_fetch_array($result)) {
		$visites = $row['visites'];
		echo "[$visites visites]";
		bouton_public("Evolution des visites", "./ecrire/statistiques_visites.php3?id_article=$id_article");
	}
}

	/*
	global $HTTP_REFERER;
	global $id_article;
	global $admin_ok;

	$my_ref = $HTTP_REFERER;
	$my_ref = "\n".substr(md5($my_ref), 0, 15);

	$query = "SELECT visites, referers FROM spip_articles WHERE id_article=$id_article AND statut='publie'";
	$result = spip_query($query);

	if ($row = mysql_fetch_array($result)) {
		$visites = $row['visites'];
		$referers = $row['referers'];

		$visites++;

		if (!ereg($my_ref, $referers)) {
			$referers .= $my_ref;
			spip_query("UPDATE spip_articles SET visites=$visites, referers='$referers' WHERE id_article=$id_article");
		}
		else {
			spip_query("UPDATE spip_articles SET visites=$visites WHERE id_article=$id_article");
		}

		$num_ref = strlen($referers) / 16;
		if ($admin_ok) echo "<small>[$visites visites - $num_ref referers]</small>";
	}
	*/


?>
