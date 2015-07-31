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

function calculer_visites() {

	// La date des enregistrements de spip_visites_temp correspond a la veille
	// du calcul.
	$hier = date("Y-m-d", time() - 24*3600);

	// Sur tout le site, nombre de visiteurs uniques pendant la periode
	// qui precede (normalement, une journee)
	$query = "SELECT COUNT(DISTINCT ip) AS total_visites FROM spip_visites_temp";
	$result = spip_query($query);
	if ($row = @spip_fetch_array($result))
		$total_visites = $row['total_visites'];
	else
		$total_visites = 0;
	spip_query("INSERT IGNORE INTO spip_visites
		(date, visites) VALUES ('$hier', 0)");
	spip_query("UPDATE spip_visites SET visites = visites+$total_visites
		WHERE date='$hier'");

	// Nombre de visiteurs uniques par article
	$query = "SELECT COUNT(DISTINCT ip) AS visites, id_objet
		FROM spip_visites_temp WHERE type='article' GROUP BY id_objet";
	$result = spip_query($query);

	$visites_insert = array();
	$visites_update = array();

	while ($row = @spip_fetch_array($result)) {
		$id_article = $row['id_objet'];
		$visites = $row['visites'];
		$visites_update[$visites][] = $id_article;
	}

	$query_effacer = "DELETE FROM spip_visites_temp";
	$result_effacer = spip_query($query_effacer);

	// Mise a jour de la base
	foreach ($visites_update as $visites => $articles) {
		// Augmenter les stats totales des articles
		spip_query("UPDATE spip_articles SET maj=maj,
			visites = visites + $visites
			WHERE id_article IN (".join(',', $articles).")");
		// Inserer des visites pour la journee (si pas deja fait)
		$insert = "('$hier',0,". join ("),('$hier',0,", $articles) . ')';
		spip_query("INSERT IGNORE INTO spip_visites_articles
			(date, visites, id_article) VALUES $insert");
		// Augmenter les stats des visites de la journee
		spip_query("UPDATE spip_visites_articles
			SET visites=visites+$visites WHERE date='$hier'
			AND id_article IN (".join(',', $articles).")");
	}

	// Une fois par jour purger les referers du jour ; qui deviennent
	// donc ceux de la veille ; au passage on stocke une date_statistiques
	// dans spip_meta - cela permet au code d'etre "reentrant", ie ce cron
	// peut etre appele par deux bases SPIP ne partageant pas le meme
	// _DIR_SESSIONS, sans tout casser...
	$aujourdhui = date("Y-m-d");
	if ($date_referers = lire_meta('date_statistiques')
	AND $date_referers != $aujourdhui) {
		spip_query("UPDATE spip_referers SET visites_veille=visites_jour, visites_jour=0");
	}
	ecrire_meta('date_statistiques', $aujourdhui);
	ecrire_metas();
	return 1;
}

?>
