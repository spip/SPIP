<?php

include ("inc_export.php3");

// Liste un sommaire d'objets de n'importe quel type
// a la condition d'etre publics et plus recents que $maj
function liste_objets($result, $type) {
	global $maj;
	global $articles;
	if ($result) while ($row = spip_fetch_array($result)) {
		$t_id = $row["id_$type"];
		$t_statut = $row["statut"];
		$t_maj = mysql_timestamp_to_time($row["maj"]);
		if ($t_maj > $maj && (!$t_statut || $t_statut == "publie")) {
			echo "$type $t_id $t_maj\n";
			if ($type == "article") $articles[]=$t_id;
		}
	}
	spip_free_result($result);
}

// Liste un sommaire recursif de rubriques
// a condition que la mise a jour soit plus recente que $maj
function liste_rubriques($result) {
	global $maj;
	global $rubriques;
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
	}
	spip_free_result($result);
 	if ($t_rubriques) {
 		$t_rubriques = join(",", $t_rubriques);
 		$rubriques[] = $t_rubriques;
 		$query = "SELECT * FROM spip_rubriques WHERE id_parent IN ($t_rubriques)";
		liste_rubriques(spip_query($query));
	}
}



Header("Content-Type: text/plain");

if ($id_rubrique)
	$query="SELECT * FROM spip_rubriques WHERE id_rubrique='$id_rubrique'";
else
	$query="SELECT * FROM spip_rubriques WHERE id_parent=0";

liste_rubriques(spip_query($query));

if ($rubriques) {
	$rubriques = join(",", $rubriques);

	$query = "SELECT id_article, statut, maj FROM spip_articles WHERE id_rubrique IN ($rubriques)";
	liste_objets(spip_query($query), "article");

	$query = "SELECT id_breve, statut, maj FROM spip_breves WHERE id_rubrique IN ($rubriques)";
	liste_objets(spip_query($query), "breve");

	if ($articles) {
		$articles = join(",", $articles);

		$query = "SELECT DISTINCT spip_auteurs.id_auteur, maj FROM spip_auteurs, spip_auteurs_articles AS lien WHERE id_article IN ($articles) AND spip_auteurs.id_auteur=lien.id_auteur";
		liste_objets(spip_query($query), "auteur");
	}
}


exit;

?>