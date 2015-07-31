<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_RUBRIQUES")) return;
define("_ECRIRE_INC_RUBRIQUES", "1");

//
// Recalculer les secteurs de chaque article, rubrique, syndication
//

function calculer_secteurs() {
	$query = "SELECT id_rubrique FROM spip_rubriques WHERE id_parent=0";
	$result = spip_query($query);

	while ($row = spip_fetch_array($result)) $secteurs[] = $row['id_rubrique'];
	if (!$secteurs) return;

	while (list(, $id_secteur) = each($secteurs)) {
		$rubriques = "$id_secteur";
		$rubriques_totales = $rubriques;
		while ($rubriques) {
			$query = "SELECT id_rubrique FROM spip_rubriques WHERE id_parent IN ($rubriques)";
			$result = spip_query($query);

			unset($rubriques);
			while ($row = spip_fetch_array($result)) $rubriques[] = $row['id_rubrique'];
			if ($rubriques) {
				$rubriques = join(',', $rubriques);
				$rubriques_totales .= ",".$rubriques;
			}
		}
		$query = "UPDATE spip_articles SET id_secteur=$id_secteur WHERE id_rubrique IN ($rubriques_totales)";
		$result = spip_query($query);
		$query = "UPDATE spip_breves SET id_rubrique=$id_secteur WHERE id_rubrique IN ($rubriques_totales)";
		$result = spip_query($query);
		$query = "UPDATE spip_rubriques SET id_secteur=$id_secteur WHERE id_rubrique IN ($rubriques_totales)";
		$result = spip_query($query);
		$query = "UPDATE spip_syndic SET id_secteur=$id_secteur WHERE id_rubrique IN ($rubriques_totales)";
		$result = spip_query($query);
	}
}


function calculer_dates_rubriques($id_rubrique = 0, $date_parent = "0000-00-00") {
	$date_rubrique = "0000-00-00";
	if ($id_rubrique) {

		// breves
		$query = "SELECT MAX(date_heure) as date_h FROM spip_breves WHERE id_rubrique=$id_rubrique AND statut='publie'";
		$result = spip_query($query);
		while ($row = spip_fetch_array($result)) {
			$date_breves = $row['date_h'];
			if ($date_breves > $date_rubrique) $date_rubrique = $date_breves;
		}

		// recuperer l'article le plus recent syndique dans un site reference dans cette rubrique (ouf)
		$result = spip_query("SELECT id_syndic FROM spip_syndic WHERE id_rubrique=$id_rubrique AND statut='publie'");
		$syndic = '';
		while ($row = spip_fetch_array($result))
			$syndic[] = $row['id_syndic'];
		if ($syndic) {
			$row = spip_fetch_array (spip_query ("SELECT MAX(date) AS date_h FROM spip_syndic_articles WHERE id_syndic IN(".join(',',$syndic).") AND statut='publie'"));
			$date_syndic_article = $row['date_h'];
			if ($date_syndic_article > $date_rubrique) $date_rubrique = $date_syndic_article;
		}

		// articles post-dates
		$post_dates = lire_meta("post_dates");
		if ($post_dates != "non") {
			$query = "SELECT MAX(date) AS date_h FROM spip_articles ".
				"WHERE id_rubrique=$id_rubrique AND statut = 'publie'";
		}
		else {
			$query = "SELECT MAX(date) AS date_h FROM spip_articles ".
				"WHERE id_rubrique=$id_rubrique AND statut = 'publie' AND date < NOW()";
		}
		$result = spip_query($query);
		while ($row = spip_fetch_array($result)) {
			$date_article = $row['date_h'];
			if ($date_article > $date_rubrique) $date_rubrique = $date_article;
		}

		// documents de rubrique
		if ($row = spip_fetch_array(spip_query("SELECT MAX(date) AS date_h FROM spip_documents WHERE id_rubrique=$id_rubrique")))
			if ($row['date_h'] > $date_rubrique) $date_rubrique = $row['date_h'];

	}

	$query = "SELECT id_rubrique FROM spip_rubriques WHERE id_parent=$id_rubrique";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) {
		$date_rubrique = calculer_dates_rubriques($row['id_rubrique'], $date_rubrique);
	}
	if ($id_rubrique) {
		spip_query("UPDATE spip_rubriques SET date='$date_rubrique' WHERE id_rubrique=$id_rubrique");
	}

	if ($date_rubrique > $date_parent) $date_parent = $date_rubrique;

	return $date_parent;
}


function calculer_rubriques_publiques() {
	$post_dates = lire_meta("post_dates");

	if ($post_dates != "non") {
		$query = "SELECT DISTINCT id_rubrique FROM spip_articles WHERE statut = 'publie'";
	}
	else {
		$query = "SELECT DISTINCT id_rubrique FROM spip_articles WHERE statut = 'publie' AND date <= NOW()";
	}
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) {
		if ($row['id_rubrique']) $rubriques[] = $row['id_rubrique'];
	}
	$query = "SELECT DISTINCT id_rubrique FROM spip_breves WHERE statut = 'publie'";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) {
		if ($row['id_rubrique']) $rubriques[] = $row['id_rubrique'];
	}
	$query = "SELECT DISTINCT id_rubrique FROM spip_syndic WHERE statut = 'publie'";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) {
		if ($row['id_rubrique']) $rubriques[] = $row['id_rubrique'];
	}
	$query = "SELECT DISTINCT id_rubrique FROM spip_documents_rubriques";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) {
		if ($row['id_rubrique']) $rubriques[] = $row['id_rubrique'];
	}

	while ($rubriques) {
		$rubriques = join(",", $rubriques);
		if ($rubriques_publiques) $rubriques_publiques .= ",$rubriques";
		else $rubriques_publiques = $rubriques;
		$query = "SELECT DISTINCT id_parent FROM spip_rubriques WHERE (id_rubrique IN ($rubriques)) AND (id_parent NOT IN ($rubriques_publiques))";
		$result = spip_query($query);
		unset($rubriques);
		while ($row = spip_fetch_array($result)) {
			if ($row['id_parent']) $rubriques[] = $row['id_parent'];
		}
	}
	if ($rubriques_publiques) {
		$query = "UPDATE spip_rubriques SET statut='prive' WHERE id_rubrique NOT IN ($rubriques_publiques)";
		spip_query($query);
		$query = "UPDATE spip_rubriques SET statut='publie' WHERE id_rubrique IN ($rubriques_publiques)";
		spip_query($query);
	}
}


//
// Recalculer l'ensemble des donnees associees a l'arborescence des rubriques
// (cette fonction est a appeler a chaque modification sur les rubriques)
//

function calculer_rubriques() {
	calculer_secteurs();
	calculer_rubriques_publiques();
	calculer_dates_rubriques();
}

?>