<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_STATISTIQUES")) return;
define("_ECRIRE_INC_STATISTIQUES", "1");


// Les deux fonctions suivantes sont adaptees du code des "Visiteurs",
// par Jean-Paul Dezelus (http://www.phpinfo.net/applis/visiteurs/)

function stats_load_engines() {
	// le moteur de recherche interne
	$arr_engines = Array();
	$arr_engines[] = Array('Recherche SPIP','recherche=',ereg_replace('^http://','',lire_meta('adresse_site')));

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
			if ($flag_utf8_decode
				&& (($kw_referer_host == "Google" && ereg('[io]e=UTF-8', $query))
				|| ($kw_referer_host == "AOL" && !ereg('enc=iso', $query))
				|| ($kw_referer_host == "MSN")
				))
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
// Optimiser les informations liees aux referers
//

function supprimer_referers($type = "") {
	$table = 'spip_referers';
	if ($type) {
		$table .= '_'. $type . 's';
		$col_id = 'id_' . $type;
		$query = "SELECT COUNT(DISTINCT $col_id) AS count FROM $table";
		$result = spip_query($query);
		if ($row = @spip_fetch_array($result)) {
			$count = $row['count'];
		}
	}
	if (!$count) $count = 1;

	$query = "SELECT visites FROM $table ".
		"ORDER BY visites LIMIT ".intval($count * 100).",1";
	$result = spip_query($query);
	$visites_min =  1;
	if ($row = @spip_fetch_array($result)) {
		$visites_min = $row['visites'];
	}

	$query = "DELETE FROM $table WHERE (date < DATE_SUB(NOW(),INTERVAL 7 DAY) AND visites <= $visites_min) OR (date < DATE_SUB(NOW(),INTERVAL 30 DAY)";
	$result = spip_query($query);
}



//
// Compiler les statistiques temporaires : referers (si active)
//

function calculer_n_referers($nb_referers) {
	$date = date("Y-m-d");

	// Selectionner 100 referers sur tout le site
	$query = "SELECT COUNT(DISTINCT ip) AS visites, referer, HEX(referer_md5) AS md5 ".
		"FROM spip_referers_temp GROUP BY referer_md5 LIMIT 0,$nb_referers";
	$result = spip_query($query);

	$encore = (spip_num_rows($result) == $nb_referers);

	$referer_insert = "";
	$referer_update = "";

	while ($row = @spip_fetch_array($result)) {
		$visites = $row['visites'];
		$referer = addslashes($row['referer']);
		$referer_md5 = '0x'.$row['md5'];

		$referer_update[$visites][] = $referer_md5;
		$referer_insert[] = "('$date', '$referer', $referer_md5, $visites)";
		$referer_vus[] = $referer_md5;
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

	// Ventiler ces referers article par article
	$where = (is_array($referer_vus)) ? "AND referer_md5 IN (".join(',',$referer_vus).")" : "";
	$query = "SELECT COUNT(DISTINCT ip) AS visites, id_objet, referer, HEX(referer_md5) AS md5 ".
		"FROM spip_referers_temp WHERE type='article' $where GROUP BY id_objet, referer_md5";
	$result = spip_query($query);

	$referer_insert = "";
	$referer_update = "";

	while ($row = @spip_fetch_array($result)) {
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

	// Effacer les referers traites
	if (is_array($referer_vus)) {
		$query_effacer = "DELETE FROM spip_referers_temp WHERE referer_md5 IN (".join(",",$referer_vus).")";
		$result_effacer = spip_query($query_effacer);
	}

	// A-t-on atteint le dernier referer ?
	return $encore;
}


function calculer_referers() {
	$encore = calculer_n_referers(100);
	if ($encore) {
		include_ecrire("inc_meta.php3");
		ecrire_meta ("calculer_referers_now", "oui");
		ecrire_metas();
	} else {
		// Supprimer les referers trop vieux
		supprimer_referers();
		supprimer_referers("article");
	}
}


//
// Compiler les statistiques temporaires : visites
//

function calculer_visites($date = "") {

	// calculer les popularites avant d'effacer les donnees
	calculer_popularites();

	// Date par defaut = hier
	if (!$date) $date = date("Y-m-d", time() - 24 * 3600);

	// Sur tout le site, nombre de visiteurs uniques pendant la journee
	$query = "SELECT COUNT(DISTINCT ip) AS total_visites FROM spip_visites_temp";
	$result = spip_query($query);
	if ($row = @spip_fetch_array($result))
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

	while ($row = @spip_fetch_array($result)) {
		$id_article = $row['id_objet'];
		$visites = $row['visites'];

		$visites_update[$visites][] = $id_article;
		$visites_insert[] = "('$date', $id_article, $visites)";
	}

	$query_effacer = "DELETE FROM spip_visites_temp";
	$result_effacer = spip_query($query_effacer);

	// Mise a jour de la base
	if (is_array($visites_update)) {
		while (list($visites, $articles) = each($visites_update)) {
			$query = "UPDATE spip_articles SET visites = visites + $visites ".
				"WHERE id_article IN (".join(', ', $articles).")";
			$result = spip_query($query);
		}
	}
	if (is_array($visites_insert)) {
		$query_insert = "INSERT DELAYED IGNORE INTO spip_visites_articles (date, id_article, visites) ".
				"VALUES ".join(', ', $visites_insert);
		$result_insert = spip_query($query_insert);
	}
}


//
// Popularite, modele logarithmique
//

function calculer_popularites() {
	$date = lire_meta('date_stats_popularite');
	include_ecrire("inc_meta.php3");
	ecrire_meta("date_stats_popularite", time());
	ecrire_metas();	// il faut le marquer de suite pour eviter les acces concurrents

	$duree = time() - $date;
	// duree de demi-vie d'une visite dans le calcul de la popularite (en jours)
	$demivie = 1;
	// periode de reference en jours
	$periode = 1;
	// $a est le coefficient d'amortissement depuis la derniere mesure
	$a = pow(2, - $duree / ($demivie * 24 * 3600));
	// $b est la constante multiplicative permettant d'avoir
	// une visite par jour (periode de reference) = un point de popularite
	// (en regime stationnaire)
	// or, magie des maths, ca vaut log(2) * duree journee/demi-vie
	// si la demi-vie n'est pas trop proche de la seconde ;)
	$b = log(2) * $periode / $demivie;

	// oublier un peu le passe
	spip_query("UPDATE spip_articles SET popularite = popularite * $a");

	// ajouter les points visites
	$count_article = Array();
	$query = "SELECT COUNT(*) as count,id_objet FROM spip_visites_temp WHERE maj > DATE_SUB(NOW(), INTERVAL $duree SECOND) AND type='article' GROUP BY id_objet";
	$res = spip_query($query);
	while ($row = @spip_fetch_array($res)) {
		$count_article[$row['count']] .= ','.$row['id_objet'];	// l'objet a count visites
	}

	reset ($count_article);
	while (list($count,$articles) = each($count_article)) {
		$query = "UPDATE spip_articles
			SET popularite = GREATEST(1,popularite) + $b * $count
			WHERE id_article IN (0$articles)";
		spip_query($query);
	}

	// ajouter les points referers
	$count_article = Array();
	$query = "SELECT COUNT(*) as count,id_objet FROM spip_referers_temp WHERE maj > DATE_SUB(NOW(), INTERVAL $duree SECOND) AND type='article' GROUP BY id_objet";
	$res = spip_query($query);
	while ($row = @spip_fetch_array($res)) {
		$count_article[$row['count']] .= ','.$row['id_objet'];	// l'objet a count referers
	}

	reset ($count_article);
	while (list($count,$articles) = each($count_article)) {
		$query = "UPDATE spip_articles
			SET popularite = GREATEST(1,popularite) + $b * $count
			WHERE id_article IN (0$articles)";
		spip_query($query);
	}

	// et enregistrer les metas...
	list($maxpop, $totalpop) = spip_fetch_array(spip_query("SELECT MAX(popularite), SUM(popularite) FROM spip_articles"));
	ecrire_meta("popularite_max", $maxpop);
	ecrire_meta("popularite_total", $totalpop);
	ecrire_metas();
}

?>
