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

	$file_name = 'engines-list.txt';
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
	static $arr_engines;
	static $url_site;
	include_ecrire("inc_filtres.php3");

	if (!$arr_engines) {
		// Charger les moteurs de recherche
		$arr_engines = stats_load_engines();

		// initialiser la recherche interne
		$url_site = lire_meta('adresse_site');
		$url_site = strtolower(eregi_replace("^((https?|ftp)://)?(www\.)?", "", $url_site));
	}

	$url   = parse_url( $kw_referer );
	$query = $url['query'];
	$host  = strtolower($url['host']);
	$path  = $url['path'];

	parse_str($query);

	$keywords = '';
	$found = false;

	if (strpos('-'.$kw_referer, eregi_replace("^(https?:?/?/?)?(www\.)?", "",$url_site))) {
		if (eregi("(s|search|r|recherche)=([^&]+)", $kw_referer, $regs))
			$keywords = urldecode($regs[2]);
		else
			return '';
	} else
	for ($cnt = 0; $cnt < sizeof($arr_engines) && !$found; $cnt++)
	{
		if ($found = (ereg($arr_engines[$cnt][2], $host)))
		{
			$kw_referer_host = $arr_engines[$cnt][0];
			$keywords = ereg('=', $arr_engines[$cnt][1])
				? ${str_replace('=', '', $arr_engines[$cnt][1])}:'';
			if ((($kw_referer_host == "Google" && ereg('[io]e=UTF-8', $query))
				|| ($kw_referer_host == "AOL" && !ereg('enc=iso', $query))
				|| ($kw_referer_host == "MSN")
				)) {
				include_ecrire('inc_charsets.php3');
				$keywords = unicode2charset(charset2unicode($keywords,'utf-8'));
			}
			$buffer["hostname"] = $kw_referer_host;
		}
	}

	$buffer["host"] = $host;
	if (!$buffer["hostname"])
		$buffer["hostname"] = $host;
	
	$buffer["path"] = substr($path, 1, strlen($path));
	$buffer["query"] = $query;

	if ($keywords != '')
	{
		if (strlen($keywords) > 50) $keywords = substr($keywords, 0, 48);
		$buffer["keywords"] = trim(htmlspecialchars(stripslashes($keywords)));
	}

	return $buffer;

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

	$query = "DELETE FROM $table WHERE (date < DATE_SUB(NOW(),INTERVAL 7 DAY) AND visites <= $visites_min) OR (date < DATE_SUB(NOW(),INTERVAL 30 DAY))";
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
		$referer_insert[] = "('$date', '$referer', $referer_md5, $visites, $visites)";
		$referer_vus[] = $referer_md5;
	}

	// Mise a jour de la base
	if (is_array($referer_update)) {
		while (list($visites, $referers) = each($referer_update)) {
			$query = "UPDATE spip_referers SET visites = visites + $visites, visites_jour = visites_jour + $visites ".
				"WHERE referer_md5 IN (".join(', ', $referers).")";
			$result = spip_query($query);
		}
	}
	if (is_array($referer_insert)) {
		$query_insert = "INSERT IGNORE INTO spip_referers ".
			"(date, referer, referer_md5, visites, visites_jour) VALUES ".join(', ', $referer_insert);
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
		$query_insert = "INSERT IGNORE INTO spip_referers_articles ".
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
	spip_log("analyse referers");
	$encore = calculer_n_referers(100);
	if ($encore) {
		include_ecrire("inc_meta.php3");
		ecrire_meta ("calculer_referers_now", "oui");
		ecrire_metas();
	} else {
		// Supprimer les referers trop vieux
		spip_debug("supprimer referers site");
		supprimer_referers();
		spip_debug("supprimer referers articles");
		supprimer_referers("article");
	}
}


//
// Compiler les statistiques temporaires : visites
//

function calculer_visites($date = "") {
	spip_log("analyse visites $date");

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
		$query_insert = "INSERT IGNORE INTO spip_visites_articles (date, id_article, visites) ".
				"VALUES ".join(', ', $visites_insert);
		$result_insert = spip_query($query_insert);
	}
}


//
// Popularite, modele logarithmique
//

function calculer_popularites() {
	spip_log("analyse popularites");

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


//
// Afficher les referers d'un article (ou du site)
//
function aff_referers ($query, $limit=10, $plus = true) {
	// Charger les moteurs de recherche
	$arr_engines = stats_load_engines();

	$query .= " LIMIT 0,$limit";
	$result = spip_query($query);
	
	while ($row = spip_fetch_array($result)) {
		$referer = interdire_scripts($row['referer']);
		$visites = $row['vis'];
		$tmp = "";
		$aff = "";
		
		$buff = stats_show_keywords($referer, $referer);

		if ($buff["host"]) {
			$numero = substr(md5($buff["hostname"]),0,8);
	
			$nbvisites[$numero] = $nbvisites[$numero] + $visites;

			if (strlen($buff["keywords"]) > 0) {
				$criteres = substr(md5($buff["keywords"]),0,8);
				if (!$lescriteres[$numero][$criteres])
					$tmp = " &laquo;&nbsp;".$buff["keywords"]."&nbsp;&raquo;";
				$lescriteres[$numero][$criteres] = true;
			} else {
				$tmp = $buff["path"];
				if (strlen($buff["query"]) > 0) $tmp .= "?".$buff['query'];
		
				if (strlen($tmp) > 30)
					$tmp = "/".substr($tmp, 0, 27)."...";
				else if (strlen($tmp) > 0)
					$tmp = "/$tmp";
			}

			if ($tmp)
				$lesreferers[$numero][] = "<a href='$referer'>$tmp</a>" . (($visites > 1)?" ($visites)":"");
			else
				$lesliensracine[$numero] += $visites;
			$lesdomaines[$numero] = $buff["hostname"];
			$lesurls[$numero] = $buff["host"];
			$lesliens[$numero] = $referer;
		}
	}
	
	if (count($nbvisites) > 0) {
		arsort($nbvisites);

		$aff .= "<ul>";
		for (reset($nbvisites); $numero = key($nbvisites); next($nbvisites)) {
			if ($lesdomaines[$numero] == '') next;

			$visites = pos($nbvisites);
			$ret = "\n<li>";
		
			if ($visites > 5) $ret .= "<font color='red'>$visites "._T('lnfo_liens')."</font> ";
			else if ($visites > 1) $ret .= "$visites "._T('lnfo_liens')." ";
			else $ret .= "<font color='#999999'>$visites "._T('info_lien')."</font> ";
			
			if (count($lesreferers[$numero]) > 1) {
				$referers = join ($lesreferers[$numero],"</li><li>");
				$aff .= "<p />";
				$aff .= $ret;
				$aff .= "<a href='http://".$lesurls[$numero]."'><b><font color='$couleur_foncee'>".$lesdomaines[$numero]."</font></b></a>";
				if ($rac = $lesliensracine[$numero]) $aff .= " <font size='1'>($rac)</font>";
				$aff .= "<ul><font size='1'><li>$referers</li></font></ul>";
				$aff .= "</li><p />\n";
			} else {
				$aff .= $ret;
				$aff .= "<a href='".$lesliens[$numero]."'><b>".$lesdomaines[$numero]."</b></a>";
				if ($lien = ereg_replace(" \([0-9]+\)$", "",$lesreferers[$numero][0]));
					$aff .= "<font size='1'>$lien</font>";
				$aff .= "</li>";
			}
		}
		$aff .= "</ul>";

		// Le lien pour en afficher "plus"
		if ($plus AND (spip_num_rows($result) == $limit)) {
			$lien = $GLOBALS['clean_link'];
			$lien->addVar('limit',$limit+200);
			$aff .= "<div style='text-align:right;'><b><a href='".$lien->getUrl()."'>+++</a></b></div>";
		}
	}


	return $aff;
}


?>
