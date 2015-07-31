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

### Pour se dŽbarrasser du md5, comment faire ? Un index sur 'referer' ?
### ou alors la meme notion, mais sans passer par des fonctions HEX ?


//
// prendre en compte un fichier de visite
//
// http://doc.spip.org/@compte_fichier_visite
function compte_fichier_visite($fichier,
&$visites, &$visites_a, &$referers, &$referers_a, &$articles) {

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
			$articles[] = $id_article;
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
	$visites = ''; # visites du site
	$visites_a = array(); # tableau des visites des articles
	$referers = array(); # referers du site
	$referers_a = array(); # tableau des referers des articles
	$articles = array(); # articles vus dans ce lot de visites

	// charger un certain nombre de fichiers de visites,
	// et faire les calculs correspondants

	// Traiter jusqu'a 100 sessions datant d'au moins 30 minutes
	$sessions = preg_files(sous_repertoire(_DIR_TMP, 'visites'));

	$compteur = 100;
	$date_init = time()-30*60;

	foreach ($sessions as $item) {
		if (@filemtime($item) < $date_init) {
			spip_log("traite la session $item");
			compte_fichier_visite($item,
				$visites, $visites_a, $referers, $referers_a, $articles);
			@unlink($item);
			if (--$compteur <= 0)
				break;
		}
		#else spip_log("$item pas vieux");
	}

	if (!$visites) return;
	spip_log("analyse $visites visites");

	// Maintenant on dispose de plusieurs tableaux qu'il faut ventiler dans
	// les tables spip_visites, spip_visites_articles, spip_referers
	// et spip_referers_articles ; attention a affecter tout ca a la bonne
	// date quand on est a cheval (entre minuit et 0h30)
	$date = date("Y-m-d", time() - 1800);

	// 1. les visites du site (facile)
	spip_query("INSERT IGNORE INTO spip_visites (date) VALUES ('$date')");
	spip_query("UPDATE spip_visites SET visites = visites+$visites WHERE date='$date'");

	// 2. les visites des articles (en deux passes pour minimiser
	// le nombre de requetes)
	if ($articles) {
		// s'assurer qu'un slot (date, visites, id) existe pour
		// chaque article vu
		spip_query("INSERT IGNORE INTO spip_visites_articles (date, id_article) VALUES ('$date',". join("), ('$date',", $articles) . ")");

		// enregistrer les visites dans les deux tables
		$ar = array();	# tableau num -> liste des articles ayant num visites
		$tous = array();# liste des articles ayant des visites
		foreach($visites_a as $id_article => $num) {
			$ar[$num][] = $id_article;
			$tous[] = $id_article;
		}
		$tous = calcul_mysql_in('id_article', $tous);
		$sum = '';
		foreach ($ar as $num => $liste)
			$sum .= ' + '.$num.'*'
				. calcul_mysql_in('id_article', $liste);

		# pour les popularites ajouter 1 point par referer
		$sumref = '';
		if ($referers_a)
			$sumref = ' + '.calcul_mysql_in('id_article',
			array_keys($referers_a));

		spip_query("UPDATE spip_visites_articles SET visites = visites $sum WHERE date='$date' AND $tous");

		spip_query("UPDATE spip_articles SET visites = visites $sum$sumref, popularite = popularite $sum, maj = maj WHERE $tous");
			## Ajouter un JOIN sur le statut de l'article ?
	}

	// 3. Les referers du site
	if ($referers) {
		$ar = array();
		// s'assurer d'un slot pour chacun
		foreach ($referers as $referer => $num) {
			$referer_md5 = '0x'.substr(md5($referer), 0, 15);
			$insert[] = "('$date', " . _q($referer) . ",
				$referer_md5)";
			$ar[$num][] = $referer_md5;
		}
		spip_query("INSERT IGNORE INTO spip_referers (date, referer, referer_md5) VALUES " . join(', ', $insert));
		
		// ajouter les visites
		foreach ($ar as $num => $liste) {
			spip_query("UPDATE spip_referers SET visites = visites+$num, visites_jour = visites_jour+$num	WHERE ".calcul_mysql_in('referer_md5',$liste));
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
			$insert[] = "('$date', " . _q($referer) . ",
				$referer_md5, $id_article)";
			$ar[$num][] = "(id_article=$id_article AND referer_md5=$referer_md5)";
		}
		spip_query("INSERT IGNORE INTO spip_referers_articles (date, referer, referer_md5, id_article) VALUES " . join(', ', $insert));
		
		// ajouter les visites
		foreach ($ar as $num => $liste) {
			spip_query("UPDATE spip_referers_articles SET visites = visites+$num	WHERE ".join(" OR ", $liste));
			## Ajouter un JOIN sur le statut de l'article ?
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
// http://doc.spip.org/@cron_visites
function cron_visites($t) {
	$encore = calculer_visites($t);

	// Si ce n'est pas fini on redonne la meme date au fichier .lock
	// pour etre prioritaire lors du cron suivant
	if ($encore)
		return (0 - $t);

	return 1;
}


?>
