<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_STATISTIQUES")) return;
define("_ECRIRE_INC_STATISTIQUES", "1");


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
		}
	}

	$nom_url = substr(strip_tags($kw_referer_host),0,40);

	$buffer = "&nbsp;<a href='".strip_tags($kw_referer)."'>".$nom_url."</a>\n";

	if ($keywords != '')
	{
		$buffer .= "(<b>" .trim(stripslashes(htmlentities($keywords)))."</b>)\n";
	}

	return( $buffer );

}



function calculer_visites() {

	// Selectionner les dates > 24 heures
	$query_date= "SELECT date FROM spip_visites_temp WHERE date < DATE_SUB(NOW(),INTERVAL 1 DAY) GROUP BY date";
	$result_date = spip_query($query_date);
	while ($row_date = mysql_fetch_array($result_date)) {
		$visites = "";
		$referers = "";
		
		$date = $row_date['date'];
			
		
		// Nombre de visiteurs uniques sur le site
		$query = "SELECT ip AS total_visites FROM spip_visites_temp WHERE date='$date' GROUP BY ip";
		$result = spip_query($query);
		$total_visites = mysql_num_rows($result);
		$query_insert = "INSERT INTO spip_visites (date, type, visites) VALUES ('$date', 'tout', '$total_visites');";
		$result_insert = spip_query($query_insert);
	
	
		// Recuperer les donnees du log	
		$query = "SELECT * FROM spip_visites_temp WHERE date='$date'";
		$result = spip_query($query);
	
		while ($row = mysql_fetch_array($result)) {
			$ip = $row['ip'];
			$type = $row['type'];
			$referer = $row['referer'];
			
			$visites[$type][$ip] = 1;
			if (strlen($referer) > 0) {
				$referers[$referer][$type][$ip] = 1;
			}
		}
		
		// Nombre de visiteurs par articles

		$query = "SELECT id_article, visites FROM spip_articles";
		$result = spip_query($query);
		
		while ($row = mysql_fetch_array($result)) {
			$id_article = $row['id_article'];
			$vis_article = $row['visites'];
			$visites_articles[$id_article] = $vis_article;
		}

		while (list($key, $value) = each($visites)) {
			$type_page = $key;
			$visites_uniques = count($value);

			if (ereg("^article([0-9]+)", $type_page, $regs)){
				$id_article = $regs[1];
				$total_article = $visites_articles[$id_article] + $visites_uniques;
				$query_insert = "INSERT INTO spip_visites (date, type, visites) VALUES ('$date', 'article$id_article', '$visites_uniques');";
				$result_insert = spip_query($query_insert);
				$query_insert = "UPDATE spip_articles SET visites = '$total_article' WHERE id_article = '$id_article'";
				$result_insert = spip_query($query_insert);
			}
		}
	
		$activer_statistiques_ref=lire_meta("activer_statistiques_ref");
		
		if ($referers && $activer_statistiques_ref == "oui"){
			while (list($key, $value) = each($referers)) {
				$referer = $key;
				$ref_md5 = substr(md5($referer), 0, 15);

				$total_ref = 0;
				while (list($key2,$value2) = each ($value)) {
					$value2 = count($value2);
					$total_ref = $total_ref + $value2;
					
					if (ereg("^article([0-9]+)", $key2, $regs)){
						$id_article = $regs[1];
						$query = "SELECT id_referer, visites FROM spip_visites_referers WHERE type = 'article$id_article' AND referer_md5 = '$ref_md5'";
						$result = spip_query($query);
						if ($row = mysql_fetch_array($result)) {
							$id_referer = $row['id_referer'];
							$total_visites = $row['visites'] + $value2;
							$query_insert = "UPDATE spip_visites_referers SET visites = $total_visites WHERE id_referer = '$id_referer'";
							$result_insert = spip_query($query_insert);
						}
						else {
							$query_insert = "INSERT INTO spip_visites_referers (date, referer, referer_md5, type, visites) VALUES ('$date', '$referer', '$ref_md5', 'article$id_article', '$value2');";
							$result_insert = spip_query($query_insert);
						}
					}
				}
	
				$query = "SELECT id_referer, visites FROM spip_visites_referers WHERE type = 'tout' AND referer_md5 = '$ref_md5'";
				$result = spip_query($query);
				if ($row = mysql_fetch_array($result)) {
					$id_referer = $row['id_referer'];
					$total_visites = $row['visites'] + $total_ref;
					$query_insert = "UPDATE spip_visites_referers SET visites = $total_visites WHERE id_referer = '$id_referer'";
					$result_insert = spip_query($query_insert);
				}
				else {
					$query_insert = "INSERT INTO spip_visites_referers (date, referer, referer_md5, type, visites) VALUES ('$date', '$referer', '$ref_md5', 'tout', '$total_ref');";
					$result_insert = spip_query($query_insert);
				}
			}
		}

		$query_effacer = "DELETE FROM spip_visites_temp WHERE date = '$date'";
		$result_effacer = spip_query($query_effacer);	

	}
}


function supprimer_referers($type){

	// Recuperer les 100 plus gros referers de ce type
	$query = "SELECT id_referer, visites FROM spip_visites_referers WHERE type ='$type' ORDER BY visites DESC LIMIT 0,100";
	$result = spip_query($query);
	while ($row = mysql_fetch_array($result)) {
		$id_referer[] = $row['id_referer'];
		$visites = $visites + $row['visites'];
	}
	
	// Supprimer les autres s'ils datent de plus d'une semaine
	if ($id_referer){
		$referers = join($id_referer, ",");
		$query = "DELETE FROM spip_visites_referers WHERE type ='$type' AND id_referer NOT IN ($referers) AND date < DATE_SUB(NOW(),INTERVAL 7 DAY)";
		$result = spip_query($query);		
	}
	
	// Reinjecter total des visites des referers dans spip_articles
	if (ereg("article([0-9]+)", $type, $regs)){
		$id_article = $regs[1];
		if ($visites<1) $visites = 1;
		$query = "UPDATE spip_articles SET referers = '$visites' WHERE id_article = '$id_article'";
		$result = spip_query($query);
	}
	
}

function optimiser_referers(){

	// Supprimer referers inutiles
	
	supprimer_referers("tout");
	
	$query = "SELECT id_article FROM spip_articles WHERE statut = 'publie'";
	$result = spip_query($query);
	while ($row = mysql_fetch_array($result)) {
		$id_article = $row['id_article'];
		supprimer_referers("article$id_article");
	}
	
	
	// Calculer et reinjecter popularite
	$query = "SELECT id_article, visites, referers FROM spip_articles WHERE statut = 'publie'";
	$result = spip_query($query);
	while ($row = mysql_fetch_array($result)) {
		$id_article = $row['id_article'];
		$visites = $row['visites'];
		$referers  = $row['referers'];
		$popularite[$id_article] = $visites * $referers;
	}
	if (count($popularite)>0){
		$facteur_pop = 1000000 / max(max($popularite),1);
		while (list($id_article, $pop) = each($popularite)) {
			$relatif = round($pop * $facteur_pop);
			$query = "UPDATE spip_articles SET popularite = '$relatif' WHERE id_article = '$id_article'";
			$result = spip_query($query);
		}
	}
}




?>

