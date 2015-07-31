<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_STATISTIQUES")) return;
define("_ECRIRE_INC_STATISTIQUES", "1");


// Les deux fonctions suivantes sont adaptees du code des "Visiteurs",
// par Jean-Paul Dezelus (http://www.phpinfo.net/applis/visiteurs/)

function stats_load_engines() {
	$file_name = 'data/engines-list.ini';
	if ($fp = @fopen($file_name, 'r'))
	{
		while ($data = fgets($fp, 256))
		{
			$data = trim(chop($data));

			if (!ereg('^#', $data) && $data != '')
			{
				if (ereg('^\[(.*)\]$', $data, $engines))
				{
					// engine
					$engine = $engines[1];

					// query | dir
					if (!feof($fp))
					{
						$data = fgets($fp, 256);
						$query_or_dir = trim(chop($data));
					}
				}
				else
				{
					$host = $data;
					$arr_engines[] = Array($engine, $query_or_dir, $host);
				}
			}
		}
		fclose($fp);
	}
	return $arr_engines;
}

function stats_show_keywords($kw_referer, $kw_referer_host) {
	global $arr_engines;
	global $flag_utf8_decode;
	
	if (sizeof($arr_engines) == 0) {
		// Charger les moteurs de recherche
		$arr_engines = stats_load_engines();
	}
 
	$url   = parse_url( $kw_referer );
	$query = $url['query'];
	$host  = $url['host'];
	
	parse_str($query);
  
	$keywords = '';
	$found = false;
  
	for ($cnt = 0; $cnt < sizeof($arr_engines) && !$found; $cnt++)
	{
		if ($found = ($host == $arr_engines[$cnt][2]))
		{
			$kw_referer_host = $arr_engines[$cnt][0];
			$keywords = ereg('=', $arr_engines[$cnt][1])
				? ${str_replace('=', '', $arr_engines[$cnt][1])}
				: $lvm_directory;
			if ($flag_utf8_decode && $kw_referer_host == "Google" && ereg('[io]e=UTF-8', $query))
				$keywords = utf8_decode($keywords);
		}
	}

	$nom_url = strip_tags($kw_referer_host);
	$title = "Aller &agrave; ".$kw_referer;
	if (strlen($nom_url) > 50) $nom_url = substr($nom_url, 0, 48) . "...";

	$buffer = "&nbsp;<a title=\"$title\" href='".strip_tags($kw_referer)."'>".$nom_url."</a>\n";

	if ($keywords != '')
	{
		$buffer .= "(<b>" .trim(htmlspecialchars(stripslashes($keywords)))."</b>)\n";
	}

	return( $buffer );

}


//
// Compiler les statistiques temporaires : visites et referers (si active)
//

function calculer_referers($date) {
	// Referers sur tout le site
	$query = "SELECT COUNT(DISTINCT ip) AS visites, referer, HEX(referer_md5) AS md5 ".
		"FROM spip_referers_temp GROUP BY referer_md5";
	$result = spip_query($query);

	$referer_insert = "";
	$referer_update = "";

	while ($row = @mysql_fetch_array($result)) {
		$visites = $row['visites'];
		$referer = addslashes($row['referer']);
		$referer_md5 = '0x'.$row['md5'];

		$referer_update[$visites][] = $referer_md5;
		$referer_insert[] = "('$date', '$referer', $referer_md5, $visites)";
	}

	// Mise a jour de la base
	if (is_array($referer_update)) {
		while (list($visites, $referers) = each($referer_update)) {
			$query = "UPDATE spip_referers SET visites = visites + $visites ".
				"WHERE referer_md5 IN (".join(', ', $referers).")";
			$result = spip_query($query);
		}
	}
	if (is_array($referer_insert)) {
		$query_insert = "INSERT DELAYED IGNORE INTO spip_referers ".
			"(date, referer, referer_md5, visites) VALUES ".join(', ', $referer_insert);
		$result_insert = spip_query($query_insert);
	}

	// Referers par article
	$query = "SELECT COUNT(DISTINCT ip) AS visites, id_objet, referer, HEX(referer_md5) AS md5 ".
		"FROM spip_referers_temp WHERE type='article' GROUP BY id_objet, referer_md5";
	$result = spip_query($query);

	$referer_insert = "";
	$referer_update = "";

	while ($row = @mysql_fetch_array($result)) {
		$id_article = $row['id_objet'];
		$visites = $row['visites'];
		$referer = addslashes($row['referer']);
		$referer_md5 = '0x'.$row['md5'];

		$referer_update[$visites][] = "(id_article=$id_article AND referer_md5=$referer_md5)";
		$referer_insert[] = "('$date', '$referer', $referer_md5, $id_article, $visites)";
	}

	// Mise a jour de la base
	if (is_array($referer_update)) {
		while (list($visites, $where) = each($referer_update)) {
			$query = "UPDATE spip_referers_articles SET visites = visites + $visites ".
				"WHERE ".join(' OR ', $where);
			$result = spip_query($query);
		}
	}
	if (is_array($referer_insert)) {
		$query_insert = "INSERT DELAYED IGNORE INTO spip_referers_articles ".
			"(date, referer, referer_md5, id_article, visites) VALUES ".join(', ', $referer_insert);
		$result_insert = spip_query($query_insert);
	}

	$query_effacer = "DELETE FROM spip_referers_temp";
	$result_effacer = spip_query($query_effacer);	
}


function calculer_visites($date = "") {
	// Date par defaut = hier
	if (!$date) $date = date("Y-m-d", time() - 24 * 3600);

	// Sur tout le site, nombre de visiteurs uniques pendant la journee
	$query = "SELECT COUNT(DISTINCT ip) AS total_visites FROM spip_visites_temp";
	$result = spip_query($query);
	if ($row = @mysql_fetch_array($result))
		$total_visites = $row['total_visites'];
	else
		$total_visites = 0;
	$query_insert = "INSERT INTO spip_visites (date, visites) VALUES ('$date', $total_visites)";
	$result_insert = spip_query($query_insert);

	// Nombre de visiteurs uniques par article
	$query = "SELECT COUNT(DISTINCT ip) AS visites, id_objet FROM spip_visites_temp ".
		"WHERE type='article' GROUP BY id_objet";
	$result = spip_query($query);

	$visites_insert = "";
	$visites_update = "";

	while ($row = @mysql_fetch_array($result)) {
		$id_article = $row['id_objet'];
		$visites = $row['visites'];

		$visites_update[$visites][] = $id_article;
		$visites_insert[] = "('$date', $id_article, $visites)";
	}

	$query_effacer = "DELETE FROM spip_visites_temp";
	$result_effacer = spip_query($query_effacer);	

	// Mise a jour de la base
	if (is_array($visites_insert)) {
		$query_insert = "INSERT DELAYED IGNORE INTO spip_visites_articles (date, id_article, visites) ".
				"VALUES ".join(', ', $visites_insert);
		$result_insert = spip_query($query_insert);
	}
	if (is_array($visites_update)) {
		while (list($visites, $articles) = each($visites_update)) {
			$query = "UPDATE spip_articles SET visites = visites + $visites ".
				"WHERE id_article IN (".join(', ', $articles).")";
			$result = spip_query($query);
		}
	}

	if (lire_meta('activer_statistiques_ref') == 'oui') {
		calculer_referers($date);
	}
	else {
		$query = "UPDATE spip_articles SET popularite = 0";
		$result = spip_query($query);
	}
}


//
// Optimiser les informations liees aux referers (popularite...)
//

function supprimer_referers($type = "") {
	$table = 'spip_referers';
	if ($type) {
		$table .= '_'. $type . 's';
		$col_id = 'id_' . $type;
		$query = "SELECT COUNT(DISTINCT $col_id) AS count FROM $table";
		$result = spip_query($query);
		if ($row = @mysql_fetch_array($result)) {
			$count = $row['count'];
		}
	}
	if (!$count) $count = 1;

	$query = "SELECT visites FROM $table ".
		"ORDER BY visites LIMIT ".intval($count * 100).",1";
	$result = spip_query($query);
	$visites_min =  1;
	if ($row = @mysql_fetch_array($result)) {
		$visites_min = $row['visites'];
	}

	$query = "DELETE FROM $table WHERE date < DATE_SUB(NOW(),INTERVAL 7 DAY) OR visites <= $visites_min";
	$result = spip_query($query);
}


function optimiser_referers() {
	$popularite_update = "";

	// Calcul des gains en popularite
	$query = "SELECT id_article, COUNT(*) AS referers, SUM(visites) AS visites ".
		"FROM spip_referers_articles GROUP BY id_article";
	$result = spip_query($query);
	while ($row = mysql_fetch_array($result)) {
		$id_article = $row['id_article'];
		$referers = $row['referers'];
		$visites = $row['visites'];

		$popularite = intval(10 * sqrt(sqrt($referers + 1)) * $visites);
		$popularite_update[$popularite][] = $id_article;
		if ($max < $popularite) $max = $popularite;
	}

	// Mise a jour des valeurs de referers et popularite
	if (is_array($popularite_update)) {
		// Normalisation avant (limiter l'influence des visites recentes)
		if ($max < 100) $max = 100;

		while (list($popularite, $articles) = each($popularite_update)) {
			$query = "UPDATE spip_articles SET popularite = popularite + $popularite * 100 / $max ".
				"WHERE id_article IN (".join(', ', $articles).")";
			$result = spip_query($query);
		}

		// Normalisation apres
		$query = "SELECT MAX(popularite) AS max FROM spip_articles";
		$result = spip_query($query);
		if ($row = mysql_fetch_array($result)) {
			$max = $row['max'];
			if ($max > 100) {
				$query = "UPDATE spip_articles SET popularite = popularite * 100 / $max";
				$result = spip_query($query);
			}
		}
	}

	supprimer_referers();
	supprimer_referers("article");
}

?>
