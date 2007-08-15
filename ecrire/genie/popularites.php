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

//
// Popularite, modele logarithmique
//

// http://doc.spip.org/@calculer_popularites
function genie_popularites_dist($t) {

	// Si c'est le premier appel, ne pas calculer
	$t = $GLOBALS['meta']['date_popularites'];
	ecrire_meta('date_popularites', time());
	ecrire_metas();
	if (!$t)
		return 1;

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
	$row = sql_fetch(spip_query("SELECT MAX(popularite) AS max, SUM(popularite) AS tot FROM spip_articles"));
	ecrire_meta("popularite_max", $row['max']);
	ecrire_meta("popularite_total", $row['tot']);


	// Une fois par jour purger les referers du jour ; qui deviennent
	// donc ceux de la veille ; au passage on stocke une date_statistiques
	// dans spip_meta - cela permet au code d'etre "reentrant", ie ce cron
	// peut etre appele par deux bases SPIP ne partageant pas le meme
	// _DIR_TMP, sans tout casser...
	$aujourdhui = date("Y-m-d");
	if ($date = $GLOBALS['meta']['date_statistiques']
	AND $date != $aujourdhui) {
		ecrire_meta('date_statistiques', $aujourdhui);
		ecrire_metas();
		#spip_query("UPDATE spip_referers SET visites_veille=visites_jour, visites_jour=0");
		// version 3 fois plus rapide, mais en 2 requetes
		#spip_query("ALTER TABLE spip_referers CHANGE visites_jour visites_veille INT( 10 ) UNSIGNED NOT NULL DEFAULT '0',CHANGE visites_veille visites_jour INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'");
		#spip_query("UPDATE spip_referers SET visites_jour=0");
		// version 4 fois plus rapide que la premiere, en une seule requete
		spip_query("ALTER TABLE spip_referers DROP visites_veille,
		CHANGE visites_jour visites_veille INT(10) UNSIGNED NOT NULL DEFAULT '0',
		ADD visites_jour INT(10) UNSIGNED NOT NULL DEFAULT '0'");
	}
 
	// et c'est fini pour cette fois-ci
	return 1;

}

?>
