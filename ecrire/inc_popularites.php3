<?php

// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_POPULARITES")) return;
define("_ECRIRE_INC_POPULARITES", "1");

//
// Popularite, modele logarithmique
//

function calculer_popularites($t) {

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

	// ajouter les points visites
	$count_article = Array();
	$query = "SELECT COUNT(*) as count,id_objet FROM spip_visites_temp WHERE maj > DATE_SUB(NOW(), INTERVAL $duree SECOND) AND type='article' GROUP BY id_objet";
	$res = spip_query($query);
	while ($row = @spip_fetch_array($res)) {
		$count_article[$row['count']] .= ','.$row['id_objet'];	// l'objet a count visites
	}

	foreach($count_article as $count => $articles) {
		$query = "UPDATE spip_articles
			SET maj=maj, popularite = GREATEST(1,popularite) + $b * $count
			WHERE id_article IN (0$articles)";
		spip_query($query);
	}

	// ajouter les points referers
	$count_article = Array();
	$query = "SELECT COUNT(*) as count,id_objet FROM spip_referers_temp WHERE maj > DATE_SUB(NOW(), INTERVAL $duree SECOND) AND type='article' GROUP BY id_objet";
	$res = spip_query($query);
	while ($row = @spip_fetch_array($res)) {
		$count_article[$row['count']] .= ','.$row['id_objet'];	// l'objet a count referers
	}

	foreach($count_article as $count => $articles) {
		$query = "UPDATE spip_articles
			SET maj=maj, popularite = GREATEST(1,popularite) + $b * $count
			WHERE id_article IN (0$articles)";
		spip_query($query);
	}

	// et enregistrer les metas...
	list($maxpop, $totalpop) = spip_fetch_array(spip_query("SELECT MAX(popularite), SUM(popularite) FROM spip_articles"));
	ecrire_meta("popularite_max", $maxpop);
	ecrire_meta("popularite_total", $totalpop);
	ecrire_metas();
}
?>

