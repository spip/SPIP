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

### Pour se dŽbarrasser du md5, comment faire ? Un index sur 'referer' ?
### ou alors la meme notion, mais sans passer par des fonctions HEX ?


//
// prendre en compte un fichier de visite
//
function compte_fichier_visite($fichier,
&$visites, &$visites_a, &$referers, &$referers_a, &$articles) {

	// Noter la visite du site (article 0)
	$visites ++;

	$content = array();
	if (lire_fichier($fichier, $content))
		$content = unserialize($content);

	foreach ($content as $source => $num) {
		list($log_type, $log_id_num, $log_referer)
			= preg_split(",\t,", $source, 3);
		
		// Noter le referer
		if ($log_referer)
			$referers[$log_referer]++;

		// S'il s'agit d'un article, noter ses visites
		if ($log_type == 'article'
		AND $id_article = intval($log_id_num)) {
			$articles[] = $id_article;
			$visites_a[$id_article] ++;
			if ($log_referer)
				$referers_a[$id_article][$log_referer]++;
		}
	}
}


function calculer_visites($t) {

	// Initialisations
	$visites = ''; # visites du site
	$visites_a = array(); # tableau des visites des articles
	$referers = array(); # referers du site
	$referers_a = array(); # tableau des referers des articles
	$articles = array(); # articles vus dans ce lot de visites

	// charger un certain nombre de fichiers de visites,
	// et faire les calculs correspondants

	// 1. Chercher les paniers datant d'au moins 30 minutes
	$date_init = date('YmdHi', time()-30*60);
	$paniers = array();
	$dir = opendir(_DIR_SESSIONS);
	while (($item = readdir($dir)) !== false) {
		if (preg_match(',^stats_([0-9]{12})$,', $item, $regs)
		AND $regs[1]<$date_init
		AND is_dir(_DIR_SESSIONS.$item))
			$paniers[] = $item;
	}
	closedir($dir);

	// 2. Manger 100 fichiers de ces paniers (sans ordre particulier)
	$compteur = 100;
	$pasfini = false;
	foreach ($paniers as $panier) {
		spip_log("traite le panier $panier");
		$dir = opendir(_DIR_SESSIONS.$panier);
		while (($item = readdir($dir)) !== false) {
			if ($compteur-- < 0) {
				$pasfini = true;
				break;
			}
			if (is_file($f = _DIR_SESSIONS.$panier.'/'.$item)) {
				compte_fichier_visite($f,
					$visites, $visites_a, $referers, $referers_a, $articles);
				@unlink($f);
			}
		}
		// effacer le panier, sauf si on a atteint la limite de fichiers vus
		closedir($dir);
		if ($pasfini)
			break;
		else
			@rmdir(_DIR_SESSIONS.$panier);
	}

	if (!$visites) return;
	spip_log("analyse $visites visites");

	// Maintenant on dispose de plusieurs tableaux qu'il faut ventiler dans
	// les tables spip_visites, spip_visites_articles, spip_referers
	// et spip_referers_articles ; attention a affecter tout ca a la bonne
	// date quand on est a cheval (entre minuit et 0h30)
	$date = date("Y-m-d", time() - 1800);

	// 1. les visites du site (facile)
	spip_query("INSERT IGNORE INTO spip_visites
	(date) VALUES ('$date')");
	spip_query("UPDATE spip_visites SET visites = visites+$visites
	WHERE date='$date'");

	// 2. les visites des articles (en deux passes pour minimiser
	// le nombre de requetes)
	if ($articles) {
		// s'assurer qu'un slot (date, visites, id) existe pour
		// chaque article vu
		spip_query("INSERT IGNORE INTO spip_visites_articles
		(date, id_article) VALUES ('$date',"
		. join("), ('$date',", $articles)
		. ")");

		// enregistrer les visites dans les deux tables
		$ar = array();	# tableau num -> liste des articles ayant num visites
		$tous = array();# liste des articles ayant des visites
		foreach($visites_a as $id_article => $num) {
			$ar[$num][] = $id_article;
			$tous[] = $id_article;
		}
		$tous = calcul_mysql_in('id_article', join(',', $tous));
		$sum = '';
		$in = array();
		foreach ($ar as $num => $liste)
			$sum .= ' + '.$num.'*'
				. calcul_mysql_in('id_article', join(',',$liste));

		# pour les popularites ajouter 1 point par referer
		$sumref = '';
		if ($referers_a)
			$sumref = ' + '.calcul_mysql_in('id_article',
			join(',',array_keys($referers_a)));

		spip_query("UPDATE spip_visites_articles
			SET visites = visites $sum
			WHERE date='$date' AND $tous");
		spip_query("UPDATE spip_articles
			SET visites = visites $sum$sumref,
			popularite = popularite $sum,
			maj = maj
			WHERE $tous");
			## Ajouter un JOIN sur le statut de l'article ?
	}

	// 3. Les referers du site
	if ($referers) {
		$ar = array();
		// s'assurer d'un slot pour chacun
		foreach ($referers as $referer => $num) {
			$referer_md5 = '0x'.substr(md5($referer), 0, 15);
			$insert[] = "('$date', '".addslashes($referer)."',
				$referer_md5)";
			$ar[$num][] = $referer_md5;
		}
		spip_query("INSERT IGNORE INTO spip_referers
			(date, referer, referer_md5) VALUES "
			. join(', ', $insert));
		
		// ajouter les visites
		foreach ($ar as $num => $liste) {
			spip_query("UPDATE spip_referers
				SET visites = visites+$num, visites_jour = visites_jour+$num
				WHERE ".calcul_mysql_in('referer_md5',join(',',$liste)));
		}
	}
	
	// 4. Les referers d'articles
	if ($referers_a) {
		$ar = array();
		$insert = array();
		// s'assurer d'un slot pour chacun
		foreach ($referers_a as $id_article => $referers)
		foreach ($referers as $referer => $num) {
			$referer_md5 = '0x'.substr(md5($referer), 0, 15);
			$insert[] = "('$date', '".addslashes($referer)."',
				$referer_md5, $id_article)";
			$ar[$num][] = "(id_article=$id_article AND referer_md5=$referer_md5)";
		}
		spip_query("INSERT IGNORE INTO spip_referers_articles
			(date, referer, referer_md5, id_article) VALUES "
			. join(', ', $insert));
		
		// ajouter les visites
		foreach ($ar as $num => $liste) {
			spip_query("UPDATE spip_referers_articles
			SET visites = visites+$num
			WHERE ".join(" OR ", $liste));
			## Ajouter un JOIN sur le statut de l'article ?
		}
	}

	// S'il reste des fichiers a manger, le signaler pour reexecution rapide
	return $pasfini;
}

?>
