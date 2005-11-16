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

	// 2. Manger 1000 fichiers de ces paniers (sans ordre particulier)
	$compteur = 1000;
	$pasfini = false;
	foreach ($paniers as $panier) {
		$dir = opendir(_DIR_SESSIONS.$panier);
		while (($item = readdir($dir)) !== false) {
			if (is_file($f = _DIR_SESSIONS.$panier.'/'.$item)) {
				compte_fichier_visite($f,
					$visites, $visites_a, $referers, $referers_a, $articles);
				@unlink($f);
			}
			if (-- $compteur <= 0) {
				$pasfini = true;
				break;
			}
		}
		// effacer le panier, sauf si on a atteint la limite de fichiers vus
		closedir($dir);
		if (!$pasfini)
			@rmdir(_DIR_SESSIONS.$panier);
	}

	if (!$visites) return;

	// Maintenant on dispose de plusieurs tableaux qu'il faut ventiler dans
	// les tables spip_visites, spip_visites_articles, spip_referers
	// et spip_referers_articles ; attention a affecter tout ca a la bonne
	// date quand on est a cheval (entre minuit et 1 h du mat)
	$date = date("Y-m-d", time() - 3600);

	// 1. les visites du site (facile)
	spip_query("INSERT IGNORE INTO spip_visites
	(date) VALUES ('$date')");
	spip_query("UPDATE spip_visites SET visites = visites+$visites
	WHERE date='$date'");

	// pour calcul_mysql_in
	include_ecrire('inc_db_mysql.php3');

	// 2. les visites des articles (en deux passes pour minimiser
	// le nombre de requetes)
	if ($articles) {
		// s'assurer qu'un slot (date, visites, id) existe pour
		// chaque article vu
		spip_query("INSERT IGNORE INTO spip_visites_articles
		(date, id_article) VALUES ('$date',"
		. join("), ('$date',", $articles)
		. ")");

		// enregistrer les visites
		$ar = array(); # tableau num -> liste des articles ayant num visites
		foreach($visites_a as $id_article => $num)
			$ar[$num][] = $id_article;
		foreach ($ar as $num => $liste) {
			spip_query("UPDATE spip_visites_articles
			SET visites = visites+$num
			WHERE date='$date' AND ".
			calcul_mysql_in('id_article', join(',',$liste)));
			## Ajouter un JOIN sur le statut de l'article ?
		}
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

	// 5. Calculer les popularites ; ici c'est presque comme les visites,
	// sauf qu'on ajoute 1 point par referer
	if ($visites_a) {
		$points = array();
		foreach ($visites_a as $id_article => $v) {
			// ajouter un point aux articles ayant un referer
			if ($r = $referers_a[$id_article])
				$v += 1; // ou array_pop($r);
			$points[$v][] = $id_article;
		}
		foreach ($points as $num => $liste) {
			spip_query("UPDATE spip_articles
			SET popularite = popularite + $num
			WHERE ".calcul_mysql_in('id_article', join(',',$liste)));
		}
	}

	// S'il reste des fichiers a manger, le signaler pour reexecution rapide
	return $pasfini;
}



//
// Popularite, modele logarithmique
//

function calculer_popularites() {
	include_ecrire('inc_meta.php3');

	// Si c'est le premier appel, ne pas calculer
	$t = lire_meta('date_popularites');
	ecrire_meta('date_popularites', time());
	ecrire_metas();
	if (!$t)
		return;

	$duree = time() - $t;
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
	spip_query("UPDATE spip_articles SET maj=maj, popularite = popularite * $a");

	// enregistrer les metas...
	list($maxpop, $totalpop) = spip_fetch_array(spip_query("SELECT MAX(popularite), SUM(popularite) FROM spip_articles"));
	ecrire_meta("popularite_max", $maxpop);
	ecrire_meta("popularite_total", $totalpop);


	// Une fois par jour purger les referers du jour ; qui deviennent
	// donc ceux de la veille ; au passage on stocke une date_statistiques
	// dans spip_meta - cela permet au code d'etre "reentrant", ie ce cron
	// peut etre appele par deux bases SPIP ne partageant pas le meme
	// _DIR_SESSIONS, sans tout casser...
	$aujourdhui = date("Y-m-d");
	if ($date = lire_meta('date_statistiques')
	AND $date != $aujourdhui) {
		spip_query("UPDATE spip_referers SET visites_veille=visites_jour, visites_jour=0");
	}
	ecrire_meta('date_statistiques', $aujourdhui);

	// et c'est fini pour cette fois-ci
	ecrire_metas();
	return 1;

}

?>
