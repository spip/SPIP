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


if (!defined("_ECRIRE_INC_VERSION")) return;

//
// Compiler les statistiques temporaires : visites
//

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

	$url   = @parse_url( $kw_referer );
	$query = $url['query'];
	$host  = strtolower($url['host']);
	$path  = $url['path'];

	// Cette fonction affecte directement les variables selon la query-string !
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
			
			if (ereg('=', $arr_engines[$cnt][1])) {
			
				// Fonctionnement simple: la variable existe
				$keywords = ${str_replace('=', '', $arr_engines[$cnt][1])};
				
				// Si on a defini le nom de la variable en expression reguliere, chercher la bonne variable
				if (! strlen($keywords) > 0) {
					if (ereg($arr_engines[$cnt][1]."([^\&]*)", $query, $vals)) {
						$keywords = urldecode($vals[2]);
					}
				}
			} else {
				$keywords = "";
			}
						
			if ((  ($kw_referer_host == "Google")
				|| ($kw_referer_host == "AOL" && !ereg('enc=iso', $query))
				|| ($kw_referer_host == "MSN")
				)) {
				include_ecrire('inc_charsets.php3');
				if (!$cset = $ie) $cset = 'utf-8';
				$keywords = importer_charset($keywords,$cset);
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
		if (strlen($keywords) > 150) {
			$keywords = spip_substr($keywords, 0, 148);
			// supprimer l'eventuelle entite finale mal coupee
			$keywords = preg_replace('/&#?[a-z0-9]*$/', '', $keywords);
		}
		$buffer["keywords"] = trim(entites_html(stripslashes($keywords)));
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
	$count = intval($count * 100);
	$query = "SELECT visites FROM $table ".
		"ORDER BY visites LIMIT 1 OFFSET $count";
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

	$result = spip_query("SELECT COUNT(DISTINCT ip) AS visites, referer, HEX(referer_md5) AS md5 ".
			     "FROM spip_referers_temp GROUP BY referer_md5 LIMIT $nb_referers");

	$tous = spip_num_rows($result);

	$referer_insert = "";
	$referer_update = "";
	$referer_vus = "";

	while ($row = @spip_fetch_array($result)) {
		$visites = $row['visites'];
		$referer = addslashes($row['referer']);
		$referer_md5 = '0x'.$row['md5'];
		$referer_update[$visites][] = $referer_md5;
		$referer_insert[] = "('$date', '$referer', $referer_md5, $visites, $visites)";
		$referer_vus .= "," . $referer_md5;
	}
	if ($referer_vus) 
	  $referer_vus = "referer_md5 IN (" . substr($referer_vus,1) . ")";

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
	$query = "SELECT COUNT(DISTINCT ip) AS visites, id_objet, referer, HEX(referer_md5) AS md5 FROM spip_referers_temp WHERE type='article'"
		  . ($referer_vus ? " AND $referer_vus" : '')
		  . " GROUP BY id_objet, referer_md5";
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
	if ($referer_vus) {
	  spip_query("DELETE FROM spip_referers_temp WHERE $referer_vus");
	}
	return  $tous ;
}


//
// Afficher les referers d'un article (ou du site)
//
function aff_referers ($query, $limit=10, $plus = true) {
	// Charger les moteurs de recherche
	$arr_engines = stats_load_engines();

	$query .= " LIMIT $limit";
	$result = spip_query($query);
	
	while ($row = spip_fetch_array($result)) {
		$referer = interdire_scripts($row['referer']);
		$visites = $row['vis'];
		$tmp = "";
		
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

		$aff = "<ul>";
		for (reset($nbvisites); $numero = key($nbvisites); next($nbvisites)) {
			if ($lesdomaines[$numero] == '') next;

			$visites = pos($nbvisites);
			$ret = "\n<li>";

			if ($visites > 5) $ret .= "<font color='red'>$visites "._T('info_visites')."</font> ";
			else if ($visites > 1) $ret .= "$visites "._T('info_visites')." ";
			else $ret .= "<font color='#999999'>$visites "._T('info_visite')."</font> ";

			if (count($lesreferers[$numero]) > 1) {
				$referers = join ("</li><li>",$lesreferers[$numero]);
				$aff .= "<p />";
				$aff .= $ret;
				$aff .= "<a href='http://".$lesurls[$numero]."'><b><font color='$couleur_foncee'>".$lesdomaines[$numero]."</font></b></a>";
				if ($rac = $lesliensracine[$numero]) $aff .= " <font size='1'>($rac)</font>";
				$aff .= "<ul><font size='1'><li>$referers</li></font></ul>";
				$aff .= "</li><p />\n";
			} else {
				$aff .= $ret;
				$lien = $lesreferers[$numero][0];
				if (eregi("^(<a [^>]+>)([^ ]*)( \([0-9]+\))?", $lien, $regs))
					$lien = $regs[1].$lesdomaines[$numero].$regs[2];
				else
					$lien = "<a href='http://".$lesdomaines[$numero]."'>".$lesdomaines[$numero]."</a>";
				$aff .= "<b>$lien</b>";
				$aff .= "</li>\n";
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
