<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined('_ECRIRE_INC_VERSION')) return;
if (!defined('_CRON_LOT_FICHIERS_VISITE')) define('_CRON_LOT_FICHIERS_VISITE', 100);

### Pour se debarrasser du md5, comment faire ? Un index sur 'referer' ?
### ou alors la meme notion, mais sans passer par des fonctions HEX ?

//
// prendre en compte un fichier de visite
//
// http://doc.spip.org/@compte_fichier_visite
function compte_fichier_visite($fichier, &$visites, &$visites_a, &$referers, &$referers_a) {

	// Noter la visite du site (article 0)
	$visites ++;

	$content = array();
	if (lire_fichier($fichier, $content))
		$content = @unserialize($content);
	if (!is_array($content)) return;

	foreach ($content as $source => $num) {
		list($log_type, $log_id_num, $log_referer)
			= preg_split(",\t,", $source, 3);
		
		// Noter le referer
		if ($log_referer)
			$referers[$log_referer]++;

		// S'il s'agit d'un article, noter ses visites
		if ($log_type == 'article'
		AND $id_article = intval($log_id_num)) {
			$visites_a[$id_article] ++;
			if ($log_referer)
				$referers_a[$id_article][$log_referer]++;
		}
	}
}


// http://doc.spip.org/@calculer_visites
function calculer_visites($t) {
	include_spip('base/abstract_sql');

	// Initialisations
	$visites = array(); # visites du site
	$visites_a = array(); # tableau des visites des articles
	$referers = array(); # referers du site
	$referers_a = array(); # tableau des referers des articles

	// charger un certain nombre de fichiers de visites,
	// et faire les calculs correspondants

	// Traiter jusqu'a 100 sessions datant d'au moins 30 minutes
	$sessions = preg_files(sous_repertoire(_DIR_TMP, 'visites'));

	$compteur = _CRON_LOT_FICHIERS_VISITE;
	$date_init = time()-30*60;
	foreach ($sessions as $item) {
		if (($d=@filemtime($item)) < $date_init) {
			if (!$d) $d = $date_init; // si le fs ne donne pas de date, on prend celle du traitement, mais tout cela risque d'etre bien douteux
			$d = date("Y-m-d",$d);
			spip_log("traite la session $item");
			compte_fichier_visite($item,
				$visites[$d], $visites_a[$d], $referers[$d], $referers_a[$d]);
			spip_unlink($item);
			if (--$compteur <= 0)
				break;
		}
		#else spip_log("$item pas vieux");
	}
	if (!count($visites))
		return;

	include_spip('genie/popularites');
	list($a,$b) = genie_popularite_constantes(24*3600);

	// Maintenant on dispose de plusieurs tableaux qu'il faut ventiler dans
	// les tables spip_visites, spip_visites_articles, spip_referers
	// et spip_referers_articles ; attention a affecter tout ca a la bonne
	// date (celle de la visite, pas celle du traitement)
	foreach(array_keys($visites) as $date)
		if ($visites[$date]) {

			// 1. les visites du site (facile)
			if (!sql_countsel('spip_visites', "date='$date'"))
				sql_insertq('spip_visites',
					array('date' => $date, 'visites' => $visites[$date]));
			else 
				sql_update('spip_visites', array('visites' => "visites+".intval($visites[$date])), "date='$date'");
		
			// 2. les visites des articles 
			if ($visites_a[$date]) {
				$ar = array();	# tableau num -> liste des articles ayant num visites
				foreach($visites_a[$date] as $id_article => $n) {
					if (!sql_countsel('spip_visites_articles',
						 "id_article=$id_article AND date='$date'")){
						sql_insertq('spip_visites_articles',
								array('id_article' => $id_article,
								      'visites' => 0,
								      'date' => $date));
					}
					$ar[$n][] = $id_article;
				}
				foreach ($ar as $n => $liste) {
					$tous = sql_in('id_article', $liste);
					sql_update('spip_visites_articles',
						array('visites' => "visites+$n"),
						   "date='$date' AND $tous");
		
					$ref = $noref = array();
					foreach($liste as $id) {
						if (isset($referers_a[$date][$id]))
							$ref[]= $id ;
						else $noref[]=$id;
					}
					// il faudrait ponderer la popularite ajoutee ($n) par son anciennete eventuelle
					// sur le modele de ce que fait genie/popularites
					if (count($noref))
						sql_update('spip_articles',
							array('visites' => "visites+$n",
							 'popularite' => "popularite+".round($n*$b,2),
							 'maj' => 'maj'),
							sql_in('id_article',$noref));
							   
					if (count($ref))
						sql_update('spip_articles',
							   array('visites' => "visites+".($n+1),
							 'popularite' => "popularite+".round($n*$b,2),
							 'maj' => 'maj'),
							sql_in('id_article',$ref));
							   
					## Ajouter un JOIN sur le statut de l'article ?
				}
			}
			// 3. Les referers du site
			// insertion pour les nouveaux, au tableau des increments sinon
			if ($referers[$date]) {
				$ar = array();
				$trouver_table = charger_fonction('trouver_table', 'base');
				$desc = $trouver_table('referers');
				$n = preg_match('/(\d+)/',$desc['field']['referer'], $r);
				$n = $n ? $r[1] : 255;
				foreach ($referers[$date] as $referer => $num) {
					$referer_md5 = sql_hex(substr(md5($referer), 0, 15));
					$referer = substr($referer,0,$n);
					if (!sql_countsel('spip_referers', "referer_md5=$referer_md5"))
						sql_insertq('spip_referers',
							array('visites' => $num,
							      'visites_jour' => $num,
							      'visites_veille' => $num,
							      'date' => $date,
							      'referer' => $referer,
							      'referer_md5' => $referer_md5));
					else $ar[$num][] = $referer_md5;
				}
		
			// appliquer les increments sur les anciens
			// attention on appelle sql_in en mode texte et pas array
			// pour ne pas passer sql_quote() sur les '0x1234' de referer_md5, cf #849
				foreach ($ar as $num => $liste) {
					sql_update('spip_referers', array('visites' => "visites+$num", 'visites_jour' => "visites_jour+$num"), sql_in('referer_md5',join(', ', $liste)));
				}
			}
			
			// 4. Les referers d'articles
			if ($referers_a[$date]) {
				$ar = array();
				$insert = array();
				// s'assurer d'un slot pour chacun
				foreach ($referers_a[$date] as $id_article => $referers)
					foreach ($referers as $referer => $num) {
						$referer_md5 = sql_hex(substr(md5($referer), 0, 15));
						$prim = "(id_article=$id_article AND referer_md5=$referer_md5)";
						if (!sql_countsel('spip_referers_articles', $prim))
							sql_insertq('spip_referers_articles',
							     array('visites' => $num,
								   'id_article' => $id_article,
								   'referer' => $referer,
								   'referer_md5' => $referer_md5));
						else $ar[$num][] = $prim;
					}
				// ajouter les visites
				foreach ($ar as $num => $liste) {
					sql_update('spip_referers_articles', array('visites' => "visites+$num"), join(" OR ", $liste));
					## Ajouter un JOIN sur le statut de l'article ?
				}
			}
		}

	// S'il reste des fichiers a manger, le signaler pour reexecution rapide
	if ($compteur==0) {
		spip_log("il reste des visites a traiter...");
		return -$t;
	}
}

//
// Calcule les stats en plusieurs etapes
//
// http://doc.spip.org/@genie_visites_dist
function genie_visites_dist($t) {
	$encore = calculer_visites($t);

	// Si ce n'est pas fini on redonne la meme date au fichier .lock
	// pour etre prioritaire lors du cron suivant
	if ($encore)
		return (0 - $t);

	return 1;
}
?>
