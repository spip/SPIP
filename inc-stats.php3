<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_STATS")) return;
define("_INC_STATS", "1");

function ecrire_stats() {
	global $HTTP_REFERER;
	global $id_article;
	global $admin_ok;

	$my_ref = $HTTP_REFERER;
	$my_ref = "\n".substr(md5($my_ref), 0, 15);

	$query = "SELECT visites, referers FROM spip_articles WHERE id_article=$id_article AND statut='publie'";
	$result = mysql_query($query);

	if ($row = mysql_fetch_array($result)) {
		$visites = $row[0];
		$referers = $row[1];

		$visites++;

		if (!ereg($my_ref, $referers)) {
			$referers .= $my_ref;
			mysql_query("UPDATE spip_articles SET visites=$visites, referers='$referers' WHERE id_article=$id_article");
		}
		else {
			mysql_query("UPDATE spip_articles SET visites=$visites WHERE id_article=$id_article");
		}

		$num_ref = strlen($referers) / 16;
		if ($admin_ok) echo "<small>[$visites visites - $num_ref referers]</small>";
	}
}


?>
