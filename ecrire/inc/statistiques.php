<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;

// Les deux fonctions suivantes sont adaptees du code des "Visiteurs",
// par Jean-Paul Dezelus (http://www.phpinfo.net/applis/visiteurs/)

// http://doc.spip.org/@stats_load_engines
function stats_load_engines() {
	$arr_engines = Array();
	lire_fichier(find_in_path('engines-list.txt'), $moteurs);
	foreach (array_filter(preg_split("/([\r\n]|#.*)+/", $moteurs)) as $ligne) {
		$ligne = trim($ligne);
		if (preg_match(',^\[([^][]*)\]$,S', $ligne, $regs)) {
			$moteur = $regs[1];
			$query = '';
		} else if (preg_match(',=$,', $ligne, $regs))
			$query = $ligne;
		else
			$arr_engines[] = array($moteur,$query,$ligne);
	}
	return $arr_engines;
}

// http://doc.spip.org/@stats_show_keywords
function stats_show_keywords($kw_referer, $kw_referer_host) {
	static $arr_engines;
	static $url_site;

	if (!$arr_engines) {
		// Charger les moteurs de recherche
		$arr_engines = stats_load_engines();

		// initialiser la recherche interne
		$url_site = $GLOBALS['meta']['adresse_site'];
		$url_site = preg_replace(",^((https?|ftp)://)?(www\.)?,", "", strtolower($url_site));
	}

	if ($url = @parse_url( $kw_referer )) {
		$query = $url['query'];
		$host  = strtolower($url['host']);
		$path  = $url['path'];
	} else $query = $host = $path ='';

	// Cette fonction affecte directement les variables selon la query-string !
	parse_str($query);

	$keywords = '';
	$found = false;
	
	if (!empty($url_site)) {
	if (strpos('-'.$kw_referer, preg_replace(",^(https?:?/?/?)?(www\.)?,", "",$url_site))!==false) {
		if (preg_match(",(s|search|r|recherche)=([^&]+),i", $kw_referer, $regs))
			$keywords = urldecode($regs[2]);
			
			
		else
			return '';
	} else
	for ($cnt = 0; $cnt < sizeof($arr_engines) && !$found; $cnt++)
	{
		if ( $found = preg_match(','.$arr_engines[$cnt][2].',', $host)
		  OR $found = preg_match(','.$arr_engines[$cnt][2].',', $path))
		{
			$kw_referer_host = $arr_engines[$cnt][0];
			
			if (strpos($arr_engines[$cnt][1],'=')!==false) {
			
				// Fonctionnement simple: la variable existe
				$keywords = ${str_replace('=', '', $arr_engines[$cnt][1])};
				
				// Si on a defini le nom de la variable en expression reguliere, chercher la bonne variable
				if (! strlen($keywords) > 0) {
					if (preg_match(",".$arr_engines[$cnt][1]."([^\&]*),", $query, $vals)) {
						$keywords = urldecode($vals[2]);
					}
				}
			} else {
				$keywords = "";
			}
						
			if ((  ($kw_referer_host == "Google")
				|| ($kw_referer_host == "AOL" && strpos($query,'enc=iso')===false)
				|| ($kw_referer_host == "MSN")
				)) {
				include_spip('inc/charsets');
				if (!$cset = $ie) $cset = 'utf-8';
				$keywords = importer_charset($keywords,$cset);
			}
			$buffer["hostname"] = $kw_referer_host;
		}
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
// http://doc.spip.org/@aff_referers
function aff_referers ($result, $limit, $plus) {
	global $spip_lang_right, $source_vignettes;
	// Charger les moteurs de recherche
	$arr_engines = stats_load_engines();
	$nbvisites = array();
	$aff = '';
	while ($row = sql_fetch($result)) {
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
				$lesreferers[$numero][] = "<a href='".quote_amp($referer)."'>".quote_amp(urldecode($tmp))."</a>" . (($visites > 1)?" ($visites)":"");
			else
				$lesliensracine[$numero] += $visites;
			$lesdomaines[$numero] = $buff["hostname"];
			$lesurls[$numero] = $buff["host"];
			$lesliens[$numero] = $referer;
		}
	}
	
	if (count($nbvisites) > 0) {
		arsort($nbvisites);

		$aff = '';
		for (reset($nbvisites); $numero = key($nbvisites); next($nbvisites)) {
			$dom =  $lesdomaines[$numero];
			if (!$dom) next;

			$visites = pos($nbvisites);
			$ret = "\n<li>";

			if (
			  (strlen($source_vignettes) > 0) && 
			  $GLOBALS['meta']["activer_captures_referers"]!='non')
				$ret .= "\n<a href=\"http://".$lesurls[$numero]."\"><img src=\"$source_vignettes".rawurlencode($lesurls[$numero])."\"\nstyle=\"float: $spip_lang_right; margin-bottom: 3px; margin-left: 3px;\" alt='' /></a>";

			$bouton = "";
			if ($visites > 5) $bouton .= "<span style='color: red'>$visites "._T('info_visites')."</span> ";
			else if ($visites > 1) $bouton .= "$visites "._T('info_visites')." ";
			else $bouton .= "<span style='color: #999999'>$visites "._T('info_visite')."</span> ";

			if ($dom == "(email)") {
				$aff .= $ret . $bouton . "<b>".$dom."</b>";
			}
			else {
			  $n = isset($lesreferers[$numero]) ? count($lesreferers[$numero]) : 0;
			  if (($n > 1) || ($n > 0 && substr(supprimer_tags($lesreferers[$numero][0]),0,1) != '/')) {
					$rac = isset($lesliensracine[$numero]);
					$bouton .= "<a href='http://".quote_amp($lesurls[$numero])."' style='font-weight: bold;'>".$dom."</a>"
					  . (!$rac ? '': (" <span class='spip_x-small'>(" . $lesliensracine[$numero] .")</span>"));
					$aff .= $ret . bouton_block_depliable($bouton,false)
					  . debut_block_depliable(false)
					  . "\n<ul><li>"
					  . join ("</li><li>",$lesreferers[$numero])
					  . "</li></ul>"
					  . fin_block();
				} else {
					$aff .= $ret . $bouton;
					$lien = $n ? $lesreferers[$numero][0] : '';
					if (preg_match(",^(<a [^>]+>)([^ ]*)( \([0-9]+\))?,i", $lien, $regs)) {
						$lien = quote_amp($regs[1]).$dom.$regs[2];
						if (!strpos($lien, '</a>')) $lien .= '</a>';
					} else
						$lien = "<a href='http://".$dom."'>".$dom."</a>";
					$aff .= "<b>".quote_amp($lien)."</b>";
				}
			}
			$aff .= "</li>\n";
		}

		if (preg_match(",</ul>\s*<ul style='font-size:small;'>\s*$,",$aff,$r))
		  $aff = substr($aff,0,(0-strlen($r[0])));
		if ($aff) $aff = "<ul class='referers'>$aff</ul>";

		// Le lien pour en afficher "plus"
		if ($plus AND (spip_num_rows($result) == $limit)) {
			$aff .= "<div style='text-align:right;'><b><a href='$plus'>+++</a></b></div>";
		}
	}

	return $aff;
}

?>
