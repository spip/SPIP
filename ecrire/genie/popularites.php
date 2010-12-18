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

//
// Popularite, modele logarithmique
//
function genie_popularite_constantes($duree){
	// duree de demi-vie d'une visite dans le calcul de la popularite (en jours)
	$demivie = 0.5;
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

	return array($a,$b);
}

// http://doc.spip.org/@genie_popularites_dist
function genie_popularites_dist($t) {

	// Si c'est le premier appel, ne pas calculer
	$t = $GLOBALS['meta']['date_popularites'];
	ecrire_meta('date_popularites', time());

	if (!$t)
		return 1;

	$duree = time() - $t;
	list($a,$b) = genie_popularite_constantes($duree);

	// du passe, faisons table (SQL) rase
	sql_update('spip_articles', array('maj'=>'maj', 'popularite' => "popularite * $a"), 'popularite>1');

	// enregistrer les metas...
	$row = sql_fetsel('MAX(popularite) AS max, SUM(popularite) AS tot', "spip_articles");
	ecrire_meta("popularite_max", $row['max']);
	ecrire_meta("popularite_total", $row['tot']);


	// Une fois par jour purger les referers du jour ; qui deviennent
	// donc ceux de la veille ; au passage on stocke une date_statistiques
	// dans spip_meta - cela permet au code d'etre "reentrant", ie ce cron
	// peut etre appele par deux bases SPIP ne partageant pas le meme
	// _DIR_TMP, sans tout casser...

	$aujourdhui = date("Y-m-d");
	if (($d = $GLOBALS['meta']['date_statistiques']) != $aujourdhui) {
		spip_log("Popularite: purger referer depuis $d");
		ecrire_meta('date_statistiques', $aujourdhui);
		if (strncmp($GLOBALS['connexions'][0]['type'],'sqlite',6)==0)
			spip_query("UPDATE spip_referers SET visites_veille=visites_jour, visites_jour=0");
	  	else
			// version 3 fois plus rapide, mais en 2 requetes
			#spip_query("ALTER TABLE spip_referers CHANGE visites_jour visites_veille INT( 10 ) UNSIGNED NOT NULL DEFAULT '0',CHANGE visites_veille visites_jour INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'");
			#spip_query("UPDATE spip_referers SET visites_jour=0");
			// version 4 fois plus rapide que la premiere, en une seule requete
			sql_alter("TABLE spip_referers DROP visites_veille,
			CHANGE visites_jour visites_veille INT(10) UNSIGNED NOT NULL DEFAULT '0',
			ADD visites_jour INT(10) UNSIGNED NOT NULL DEFAULT '0'");
	}
 
	// et c'est fini pour cette fois-ci
	return 1;

}

?>
