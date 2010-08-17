<?php

//
// Afficher les referers d'un article (ou du site)
//

// http://doc.spip.org/@inc_referenceurs_dist
function inc_referenceurs_dist ($id_article, $select, $table, $where, $groupby, $limit, $serveur='') {

	$nbvisites = $lescriteres = array();

	$result = sql_select("maj, referer_md5, referer, $select AS vis", $table, $where, $groupby, "maj DESC", $limit,'',$serveur);
	while ($row = sql_fetch($result,$serveur)) {
		$referer = interdire_scripts($row['referer']);
		$buff = stats_show_keywords($referer, $referer);
		
		if ($buff["host"]) {
			$numero = $buff["hostname"];
			$visites = $row['vis'];
			$referermd5 = $row['referer_md5'];
			$lesreferermd5[$numero] = $referermd5;
			$lesliens[$numero] = $referer;
			$lesurls[$numero] = $buff["host"];
			if (!isset($nbvisites[$numero]))
				$nbvisites[$numero] = $visites;
			else
				$nbvisites[$numero] += $visites;
			if (!isset($lesreferers[$numero]))
				$lesreferers[$numero] = array();
			if (!isset($lesliensracine[$numero]))
				$lesliensracine[$numero]=0;

			if (isset($buff["keywords"])
			AND  $c = $buff["keywords"]) {
				if (!isset($lescriteres[$numero][$c])) {
					$lescriteres[$numero][$c] = true;
					$tmp= " &laquo;&nbsp;$c&nbsp;&raquo;";
				} else 	$tmp = "";
			} else {
				$tmp = $buff["path"];
				if ($buff["query"])
					$tmp .= "?".$buff['query'];
				if (strlen($tmp) > 18)
					$tmp = "/".substr($tmp, 0, 15)."...";
				else if (strlen($tmp) > 0)
					$tmp = "/$tmp";
			}
			if ($tmp) {
				$lesreferers[$numero][] = "<a href=\"".attribut_html($referer)."\">".quote_amp(urldecode($tmp))."</a>" . (($visites > 1)?" ($visites)":""). ($id_article ? '' : referes($referermd5));
			} else $lesliensracine[$numero] += $visites;
		}
	}
	
	if (!count($nbvisites)) return array();
	arsort($nbvisites);
	return referers_group($nbvisites, $id_article, $lesliensracine, $lesreferermd5, $lesreferers, $lesurls);
}

function referers_group($nbvisites, $id_article, $lesliensracine, $lesreferermd5, $lesreferers, $lesurls)
{
	global $spip_lang_right, $source_vignettes;
	$vign = ((strlen($source_vignettes) > 0) && 
		 $GLOBALS['meta']["activer_captures_referers"]!='non');
	$aff = array();
	foreach($nbvisites as $numero => $visites) {
		if (!$numero) next;
		$referermd5 = $lesreferermd5[$numero];
		$bouton = $ret = "";

		if ($vign)
			$ret = "\n<a href=\"http://".$lesurls[$numero]."\"><img src=\"$source_vignettes".rawurlencode($lesurls[$numero])."\"\nstyle=\"float: $spip_lang_right; margin-bottom: 3px; margin-left: 3px;\" alt='' /></a>";

		if ($visites > 5) $bouton .= "<span class='visites visites3'>$visites "._T('info_visites')."</span> ";
		else if ($visites > 1) $bouton .= "<span class='visites visites2'>$visites "._T('info_visites')."</span> ";
		else $bouton .= "<span class='visites visites1'>$visites "._T('info_visite')."</span> ";

		if ($numero == "(email)") {
			$ret .=  $bouton . "<b>".$numero."</b>";
		} else {
			$n = count($lesreferers[$numero]);
			if (($n > 1) || ($n > 0 && substr(supprimer_tags($lesreferers[$numero][0]),0,1) != '/')) {
				$rac = $lesliensracine[$numero];
				$bouton .= "<a href=\"http://".attribut_html($lesurls[$numero])."\" style='font-weight: bold;'>".$numero."</a>"
				  . (!$rac ? '': (" <span class='spip_x-small'>(" . $rac .")</span>"));
				 $ret .= bouton_block_depliable($bouton,false)
				  . debut_block_depliable(false)
				  . "\n<ul><li>"
				  . join ("</li><li>",$lesreferers[$numero])
				  . "</li></ul>"
				  . fin_block();
			} else {
				$ret .= $bouton;
				$lien = $n ? $lesreferers[$numero][0] : '';
				if (preg_match(",^(<a [^>]+>)([^ ]*)( \([0-9]+\))?,i", $lien, $regs)) {
					$lien = quote_amp($regs[1]).$numero.$regs[2];
					if (!strpos($lien, '</a>')) $lien .= '</a>';
				} else
					$lien = "<a href=\"http://".attribut_html($numero)."\">".$numero."</a>";
				$ret .= "<b>".quote_amp($lien)."</b>"
				  . ($id_article ? '' : referes($referermd5));
			}
		}
		$aff[]= $ret;
	}
	return $aff;
}

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
	static $arr_engines = '';
	static $url_site;

	if (!is_array($arr_engines)) {
		// Charger les moteurs de recherche
		$arr_engines = stats_load_engines();

		// initialiser la recherche interne
		$url_site = $GLOBALS['meta']['adresse_site'];
		$url_site = preg_replace(",^((https?|ftp):?/?/?)?(www\.)?,", "", strtolower($url_site));
	}

	if ($url = @parse_url( $kw_referer )) {
		$query = isset($url['query'])?$url['query']:"";
		$host  = strtolower($url['host']);
		$path  = $url['path'];
	} else $query = $host = $path ='';

	// Cette fonction affecte directement les variables selon la query-string !
	parse_str($query);

	$keywords = '';
	$found = false;
	
	if (!empty($url_site)) {
	if (strpos('-'.$kw_referer, $url_site)!==false) {
		if (preg_match(",(s|search|r|recherche)=([^&]+),i", $kw_referer, $regs))
			$keywords = urldecode($regs[2]);
			
			
		else
			return array('host' => '');
	} else
	for ($cnt = 0; $cnt < sizeof($arr_engines) && !$found; $cnt++)
	{
		if ( $found = preg_match(','.$arr_engines[$cnt][2].',', $host)
		  OR $found = preg_match(','.$arr_engines[$cnt][2].',', $path))
		{
			$kw_referer_host = $arr_engines[$cnt][0];
			
			if (strpos($arr_engines[$cnt][1],'=')!==false) {
			
				// Fonctionnement simple: la variable existe
				$v = str_replace('=', '', $arr_engines[$cnt][1]);
				$keywords = isset($$v)?$$v:"";
				
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
				if (!isset($ie) OR !$cset = $ie) $cset = 'utf-8';
				$keywords = importer_charset($keywords,$cset);
			}
			$buffer["hostname"] = $kw_referer_host;
		}
	}
	}

	$buffer["host"] = $host;
	if (!isset($buffer["hostname"]) OR !$buffer["hostname"])
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
// Recherche des articles pointes par le referer
//
// http://doc.spip.org/@referes
function referes($referermd5, $serveur='') {
	$retarts = sql_allfetsel('J2.id_article, J2.titre', 'spip_referers_articles AS J1 LEFT JOIN spip_articles AS J2 ON J1.id_article = J2.id_article', "(referer_md5='$referermd5' AND J1.maj>=DATE_SUB(".sql_quote(date('Y-m-d H:i:s')).", INTERVAL 2 DAY))", '', "titre",'','',$serveur);

	foreach ($retarts as $k => $rowart) {
		$titre = typo($rowart['titre']);
		$url = generer_url_entite($rowart['id_article'], 'article');
		$retarts[$k] = "<a href='$url'><i>$titre</i></a>";
	}

	if (count($retarts) > 1)
		return '<br />&rarr; '.join(',<br />&rarr; ',$retarts);
	if (count($retarts) == 1)
		return '<br />&rarr; '. array_shift($retarts);
	return '';
}


?>
