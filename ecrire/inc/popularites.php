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


//
// Popularite, modele logarithmique
//

function calculer_popularites() {

	// Si c'est le premier appel, ne pas calculer
	$t = $GLOBALS['meta']['date_popularites'];
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
	spip_query("UPDATE spip_articles SET maj=maj,
		popularite = popularite * $a");

	// enregistrer les metas...
	list($maxpop, $totalpop) = spip_fetch_array(spip_query(
	"SELECT MAX(popularite), SUM(popularite) FROM spip_articles"
	));
	ecrire_meta("popularite_max", $maxpop);
	ecrire_meta("popularite_total", $totalpop);


	// Une fois par jour purger les referers du jour ; qui deviennent
	// donc ceux de la veille ; au passage on stocke une date_statistiques
	// dans spip_meta - cela permet au code d'etre "reentrant", ie ce cron
	// peut etre appele par deux bases SPIP ne partageant pas le meme
	// _DIR_SESSIONS, sans tout casser...
	$aujourdhui = date("Y-m-d");
	if ($date = $GLOBALS['meta']['date_statistiques']
	AND $date != $aujourdhui)
		spip_query("UPDATE spip_referers
		SET visites_veille=visites_jour, visites_jour=0");
	ecrire_meta('date_statistiques', $aujourdhui);

	// et c'est fini pour cette fois-ci
	ecrire_metas();
	return 1;

}

//
// Applique la regle de decroissance des popularites
//
function cron_popularites($t) {
	calculer_popularites();
	return 1;
}


?>
