<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Recuperer la liste des moteurs de recherche depuis un fichier txt
 * Adaptees du code des "Visiteurs",
 * par Jean-Paul Dezelus (http://www.phpinfo.net/applis/visiteurs/)
 *
 * http://doc.spip.org/@stats_load_engines
 *
 * @return array
 */
function stats_load_engines(){
	$arr_engines = Array();
	lire_fichier(find_in_path('engines-list.txt'), $moteurs);
	foreach (array_filter(preg_split("/([\r\n]|#.*)+/", $moteurs)) as $ligne){
		$ligne = trim($ligne);
		if (preg_match(',^\[([^][]*)\]$,S', $ligne, $regs)){
			$moteur = $regs[1];
			$query = '';
		} else if (preg_match(',=$,', $ligne, $regs))
			$query = $ligne;
		else
			$arr_engines[] = array($moteur, $query, $ligne);
	}
	return $arr_engines;
}

/**
 * Retrouver les mots cles de recherche dans une url de referer
 * 
 * Adaptees du code des "Visiteurs",
 * par Jean-Paul Dezelus (http://www.phpinfo.net/applis/visiteurs/)
 *
 * http://doc.spip.org/@stats_show_keywords
 *
 * @param string $kw_referer
 * @return array
 */
function stats_show_keywords($kw_referer){
	static $arr_engines = '';
	static $url_site;

	if (!is_array($arr_engines)){
		// Charger les moteurs de recherche
		$arr_engines = stats_load_engines();

		// initialiser la recherche interne
		$url_site = $GLOBALS['meta']['adresse_site'];
		$url_site = preg_replace(",^((https?|ftp):?/?/?)?(www\.)?,", "", strtolower($url_site));
	}

	if ($url = @parse_url($kw_referer)){
		$query = isset($url['query']) ? $url['query'] : "";
		$host = strtolower($url['host']);
		$path = $url['path'];
		$scheme = $url['scheme'];
	}
	else
		$scheme = $query = $host = $path = '';

	// construire un array des variables directement depuis la query-string
	parse_str($query, $Tquery);

	$keywords = '';
	$found = false;

	if (!empty($url_site)){
		if (strpos('-'.$kw_referer, $url_site)!==false){
			if (preg_match(",(s|search|r|recherche)=([^&]+),i", $kw_referer, $regs))
				$keywords = urldecode($regs[2]);
			else
				return array('host' => '');
		}
		else {
			for ($cnt = 0; $cnt<sizeof($arr_engines) && !$found; $cnt++)
			{
				if ($found = preg_match(','.$arr_engines[$cnt][2].',', $host)
				    OR $found = preg_match(','.$arr_engines[$cnt][2].',', $path)){
					$kw_referer_host = $arr_engines[$cnt][0];

					if (strpos($arr_engines[$cnt][1], '=')!==false){

						// Fonctionnement simple: la variable existe dans l'array
						$v = str_replace('=', '', $arr_engines[$cnt][1]);
						$keywords = isset($Tquery[$v]) ? $Tquery[$v]: "";

						// Si on a defini le nom de la variable en expression reguliere, chercher la bonne variable
						if (!strlen($keywords)>0){
							if (preg_match(",".$arr_engines[$cnt][1]."([^\&]*),", $query, $vals)){
								$keywords = urldecode($vals[2]);
							}
						}
					} else {
						$keywords = "";
					}

					if (($kw_referer_host=="Google")
					     || ($kw_referer_host=="AOL" && strpos($query, 'enc=iso')===false)
					     || ($kw_referer_host=="MSN")
					){
						include_spip('inc/charsets');
						if (!isset($ie) OR !$cset = $ie) $cset = 'utf-8';
						$keywords = importer_charset($keywords, $cset);
					}
					$buffer["hostname"] = $kw_referer_host;
				}
			}
		}
	}

	$buffer["host"] = $host;
	$buffer["scheme"] = $scheme;
	if (!isset($buffer["hostname"]) OR !$buffer["hostname"])
		$buffer["hostname"] = $host;

	$buffer["path"] = substr($path, 1, strlen($path));
	$buffer["query"] = $query;

	if ($keywords!=''){
		if (strlen($keywords)>150){
			$keywords = spip_substr($keywords, 0, 148);
			// supprimer l'eventuelle entite finale mal coupee
			$keywords = preg_replace('/&#?[a-z0-9]*$/', '', $keywords);
		}
		$buffer["keywords"] = trim(entites_html(urldecode(stripslashes($keywords))));
	}

	return $buffer;

}

/**
 * Recherche des articles pointes par le referer
 * http://doc.spip.org/@referes
 *
 * @param  $referermd5
 * @param string $serveur
 * @return string
 */
function referes($referermd5, $serveur = ''){
	$retarts = Sql::allfetsel('J2.id_article, J2.titre', 'spip_referers_articles AS J1 LEFT JOIN spip_articles AS J2 ON J1.id_article = J2.id_article', "(referer_md5='$referermd5' AND J1.maj>=DATE_SUB(".Sql::quote(date('Y-m-d H:i:s')).", INTERVAL 2 DAY))", '', "titre", '', '', $serveur);

	foreach ($retarts as $k => $rowart){
		$titre = typo($rowart['titre']);
		$url = generer_url_entite($rowart['id_article'], 'article', '', '', TRUE);
		$retarts[$k] = "<a href='$url'><i>$titre</i></a>";
	}

	if (count($retarts)>1)
		return '&rarr; '.join(',<br />&rarr; ', $retarts);
	if (count($retarts)==1)
		return '&rarr; '.array_shift($retarts);
	return '';
}


?>
