<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;

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

	if (!$arr_engines) {
		// Charger les moteurs de recherche
		$arr_engines = stats_load_engines();

		// initialiser la recherche interne
		$url_site = $GLOBALS['meta']['adresse_site'];
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
			$keywords = rawurldecode($regs[2]);
			
			
		else
			return '';
	} else
	for ($cnt = 0; $cnt < sizeof($arr_engines) && !$found; $cnt++)
	{
		if ($found = (ereg($arr_engines[$cnt][2], $host)) OR $found = (ereg($arr_engines[$cnt][2], $path)))
		{
			$kw_referer_host = $arr_engines[$cnt][0];
			
			if (ereg('=', $arr_engines[$cnt][1])) {
			
				// Fonctionnement simple: la variable existe
				$keywords = ${str_replace('=', '', $arr_engines[$cnt][1])};
				
				// Si on a defini le nom de la variable en expression reguliere, chercher la bonne variable
				if (! strlen($keywords) > 0) {
					if (ereg($arr_engines[$cnt][1]."([^\&]*)", $query, $vals)) {
						$keywords = rawurldecode($vals[2]);
					}
				}
			} else {
				$keywords = "";
			}
						
			if ((  ($kw_referer_host == "Google")
				|| ($kw_referer_host == "AOL" && !ereg('enc=iso', $query))
				|| ($kw_referer_host == "MSN")
				)) {
				include_spip('inc/charsets');
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
		$buffer["keywords"] = trim(entites_html(urldecode(stripslashes($keywords))));
	}

	return $buffer;

}

//
// Afficher les referers d'un article (ou du site)
//
function aff_referers ($vis, $table, $where, $limit, $plus) {
	global $spip_lang_right, $source_vignettes;
	// Charger les moteurs de recherche
	$arr_engines = stats_load_engines();

	$result = spip_query("SELECT referer, $vis AS vis FROM $table WHERE $where ORDER BY $vis DESC LIMIT $limit");
	
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
					$tmp = "/".quote_amp(substr($tmp, 0, 27))."...";
				else if (strlen($tmp) > 0)
					$tmp = quote_amp("/$tmp");
			}

			if ($tmp)
				$lesreferers[$numero][] = "<a href='".quote_amp($referer)."'>$tmp</a>" . (($visites > 1)?" ($visites)":"");
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

			if (strlen($source_vignettes) > 0) $ret = "\n<div style='clear: $spip_lang_right;'></div><a href=\"http://".$lesurls[$numero]."\"><img src=\"$source_vignettes".rawurlencode($lesurls[$numero])."\" style=\"float: $spip_lang_right; border: 0; margin-bottom: 3px; margin-left: 3px;\" /></a>";

			$ret .= "\n<li>";

			if ($visites > 5) $ret .= "<font color='red'>$visites "._T('info_visites')."</font> ";
			else if ($visites > 1) $ret .= "$visites "._T('info_visites')." ";
			else $ret .= "<font color='#999999'>$visites "._T('info_visite')."</font> ";
		
		
			if ($lesdomaines[$numero] == "(email)") {
				$aff .= $ret;
				$aff .= "<b>".$lesdomaines[$numero]."</b>";
			}
			else if ((count($lesreferers[$numero]) > 1) || ((substr(supprimer_tags($lesreferers[$numero][0]),0,1) != '/') && (count($lesreferers[$numero]) > 0))) {
				global $couleur_foncee;
				$referers = join ("</li><li>",$lesreferers[$numero]);
				$aff .= $ret;
				$aff .= "<a href='http://".quote_amp($lesurls[$numero])."'><b><font color='$couleur_foncee'>".$lesdomaines[$numero]."</font></b></a>";
				if ($rac = $lesliensracine[$numero]) $aff .= " <font size='1'>($rac)</font>";
				$aff .= "<ul style='font-size:x-small;'><li>$referers</li></ul>";
				$aff .= "</li></ul><ul style='font-size:small;'>\n";
			} else {
				$aff .= $ret;
				$lien = $lesreferers[$numero][0];
				if (eregi("^(<a [^>]+>)([^ ]*)( \([0-9]+\))?", $lien, $regs)) {
					$lien = quote_amp($regs[1]).$lesdomaines[$numero].$regs[2];
					if (!strpos($lien, '</a>')) $lien .= '</a>';
				} else
					$lien = "<a href='http://".$lesdomaines[$numero]."'>".$lesdomaines[$numero]."</a>";
				$aff .= "<b>".quote_amp($lien)."</b>";
				$aff .= "</li>\n";
			}
		}
		$aff .= "</ul>";

		// Le lien pour en afficher "plus"
		if ($plus AND (spip_num_rows($result) == $limit)) {
			$aff .= "<div style='text-align:right;'><b><a href='$plus'>+++</a></b></div>";
		}
	}


	return $aff;
}

?>
