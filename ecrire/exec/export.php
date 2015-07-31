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

// Conversion timestamp MySQL (format ascii) en Unix (format integer)
function mysql_timestamp_to_time($maj)
{
	$t_an = substr($maj, 0, 4);
	$t_mois = substr($maj, 4, 2);
	$t_jour = substr($maj, 6, 2);
	$t_h = substr($maj, 8, 2);
	$t_min = substr($maj, 10, 2);
	$t_sec = substr($maj, 12, 2);
	return mktime ($t_h, $t_min, $t_sec, $t_mois, $t_jour, $t_an, 0);
}



// Liste un sommaire d'objets de n'importe quel type
// a la condition d'etre publics et plus recents que $maj
function liste_objets($result, $type, $maj) {

	$res = array();
	if ($result)
	  while ($row = spip_fetch_array($result)) {
		$t_id = $row["id_$type"];
		$t_statut = $row["statut"];
		$t_maj = mysql_timestamp_to_time($row["maj"]);
		if (!$maj ||
			($t_maj > $maj && 
			 (!$t_statut || $t_statut == "publie"))) {
		  echo "$type $t_id ", ($maj ? $t_maj : ""), "\n";
			if ($type == "article") $res[]=$t_id;
		}
	}
	spip_free_result($result);
	return $res;
}

// Liste un sommaire recursif de rubriques
// a condition que la mise a jour soit plus recente que $maj
function liste_rubriques($id_rubrique) {
	global $maj;
	static $rubriques = array();
	if ($id_rubrique)
		$result = spip_query("SELECT * FROM spip_rubriques WHERE id_rubrique='$id_rubrique'");
	else
		$result = spip_query("SELECT * FROM spip_rubriques WHERE id_parent=0");

	if ($result) while ($row=spip_fetch_array($result)) {
		$id_rubrique = $row['id_rubrique'];
		$id_parent = $row['id_parent'];
		$titre = $row['titre'];
		$descriptif = $row['descriptif'];
		$texte = $row['texte'];
		$rubrique_maj = mysql_timestamp_to_time($row["maj"]);
		if ($rubrique_maj > $maj) {
			echo "rubrique $id_rubrique $rubrique_maj\n";
		}
		$t_rubriques[] = $id_rubrique;
		$rubriques[] = $id_rubrique;
	}
	spip_free_result($result);
 	if ($t_rubriques) {
 		$t_rubriques = join(",", $t_rubriques);
		liste_rubriques(spip_query("SELECT * FROM spip_rubriques WHERE id_parent IN ($t_rubriques)"));

	}
	return $rubriques;
}

function exec_export_dist()
{

	global $id_rubrique, $maj;
	$id_rubrique = intval($id_rubrique);

	header("Content-Type: text/plain");

	$rubriques = liste_rubriques($id_rubriques);

	if ($rubriques) {
		$rubriques = join(",", $rubriques);

		$query = spip_query("SELECT id_article, statut, maj FROM spip_articles WHERE id_rubrique IN ($rubriques)");
		$articles = liste_objets($query, "article", $maj);

		$query = spip_query("SELECT id_breve, statut, maj FROM spip_breves WHERE id_rubrique IN ($rubriques)");
		liste_objets($query, "breve", $maj);

		if ($articles) {
			$articles = join(",", $articles);

			$query = spip_query("SELECT DISTINCT id_auteur FROM spip_auteurs_articles  WHERE id_article IN ($articles)");
			liste_objets($query, "auteur", 0);
		}
	}

}
?>
