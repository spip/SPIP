<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_OPTIMISER")) return;
define("_ECRIRE_OPTIMISER", "1");


function optimiser_base() {
	spip_log ("optimisation de la base");

	$mydate = date("YmdHis", time() - 24 * 3600);

	//
	// Rubriques
	//

	$query = "SELECT id_rubrique FROM spip_rubriques";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) $rubriques[] = $row['id_rubrique'];

	if ($rubriques) {
		$rubriques = join(",", $rubriques);

		$query = "DELETE FROM spip_articles WHERE id_rubrique NOT IN ($rubriques) AND maj < $mydate";
		spip_query($query);
		$query = "DELETE FROM spip_breves WHERE id_rubrique NOT IN ($rubriques) AND maj < $mydate";
		spip_query($query);
		$query = "DELETE FROM spip_forum WHERE id_rubrique NOT IN (0,$rubriques)";
		spip_query($query);
		$query = "DELETE FROM spip_auteurs_rubriques WHERE id_rubrique NOT IN ($rubriques)";
		spip_query($query);
		$query = "DELETE FROM spip_mots_rubriques WHERE id_rubrique NOT IN ($rubriques)";
		spip_query($query);
	}


	//
	// Articles
	//

	$query = "DELETE FROM spip_articles WHERE statut='poubelle' AND maj < $mydate";
	spip_query($query);
	$query = "SELECT id_article FROM spip_articles";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) $articles[] = $row['id_article'];

	if ($articles) {
		$articles = join(",", $articles);

		$query = "DELETE FROM spip_auteurs_articles WHERE id_article NOT IN ($articles)";
		spip_query($query);
		$query = "DELETE FROM spip_mots_articles WHERE id_article NOT IN ($articles)";
		spip_query($query);
		$query = "DELETE FROM spip_forum WHERE id_article NOT IN (0,$articles)";
		spip_query($query);
	}


	//
	// Breves
	//

	$query = "DELETE FROM spip_breves WHERE statut='refuse' AND maj < $mydate";
	spip_query($query);
	$query = "SELECT id_breve FROM spip_breves";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) $breves[] = $row['id_breve'];

	if ($breves) {
		$breves = join(",", $breves);

		$query = "DELETE FROM spip_mots_breves WHERE id_breve NOT IN ($breves)";
		spip_query($query);
		$query = "DELETE FROM spip_forum WHERE id_breve NOT IN (0,$breves)";
		spip_query($query);
	}


	//
	// Sites
	//

	$query = "DELETE FROM spip_syndic WHERE maj < $mydate AND statut = 'refuse'";
	spip_query($query);

	$query = "SELECT id_syndic FROM spip_syndic";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) $syndic[] = $row['id_syndic'];

	if ($syndic) {
		$syndic = join(",", $syndic);

		$query = "DELETE FROM spip_syndic_articles WHERE id_syndic NOT IN (0,$syndic)";
		spip_query($query);
		$query = "DELETE FROM spip_mots_syndic WHERE id_syndic NOT IN ($syndic)";
		spip_query($query);
		$query = "DELETE FROM spip_forum WHERE id_syndic NOT IN (0,$syndic)";
		spip_query($query);
	}


	//
	// Auteurs
	//

	$query = "SELECT id_auteur FROM spip_auteurs";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) $auteurs[] = $row['id_auteur'];

	if ($auteurs) {
		$auteurs = join(",", $auteurs);

		$query = "DELETE FROM spip_auteurs_articles WHERE id_auteur NOT IN ($auteurs)";
		spip_query($query);
		$query = "DELETE FROM spip_auteurs_messages WHERE id_auteur NOT IN ($auteurs)";
		spip_query($query);
		$query = "DELETE FROM spip_auteurs_rubriques WHERE id_auteur NOT IN ($auteurs)";
		spip_query($query);
	}

	$query = "SELECT id_auteur,nom,email FROM spip_auteurs WHERE statut='5poubelle' AND maj < $mydate";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) {
		$id_auteur = $row['id_auteur'];
		$nom = $row['nom'];
		$email = $row['email'];

		$query2 = "SELECT * FROM spip_auteurs_articles WHERE id_auteur=$id_auteur";
		$result2 = spip_query($query2);
		if (!spip_num_rows($result2)) {
			$query3 = "DELETE FROM spip_auteurs WHERE id_auteur=$id_auteur";
			$result3 = spip_query($query3);
			spip_log ("suppression auteur $id_auteur ($nom, $email)");
		}
	}


	//
	// Messages prives
	//

	$query = "SELECT m.id_message FROM spip_messages AS m, spip_auteurs_messages AS lien ".
		"WHERE m.id_message = lien.id_message GROUP BY m.id_message";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) $messages[] = $row['id_message'];

	$query = "SELECT id_message FROM spip_messages ".
		"WHERE type ='affich'";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) $messages[] = $row['id_message'];

	if ($messages) {
		$messages = join(",", $messages);

		$query = "DELETE FROM spip_messages WHERE id_message NOT IN ($messages)";
		spip_query($query);
		$query = "DELETE FROM spip_forum WHERE id_message NOT IN (0,$messages)";
		spip_query($query);
	}


	//
	// Mots-cles
	//

	$query = "DELETE FROM spip_mots WHERE titre='' AND maj < $mydate";
	$result = spip_query($query);

	$query = "SELECT id_mot FROM spip_mots";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) $mots[] = $row['id_mot'];

	if ($mots) {
		$mots = join(",", $mots);

		$query = "DELETE FROM spip_mots_articles WHERE id_mot NOT IN ($mots)";
		spip_query($query);
		$query = "DELETE FROM spip_mots_breves WHERE id_mot NOT IN ($mots)";
		spip_query($query);
		$query = "DELETE FROM spip_mots_forum WHERE id_mot NOT IN ($mots)";
		spip_query($query);
		$query = "DELETE FROM spip_mots_rubriques WHERE id_mot NOT IN ($mots)";
		spip_query($query);
		$query = "DELETE FROM spip_mots_syndic WHERE id_mot NOT IN ($mots)";
		spip_query($query);
	}

	//
	// Forums
	//

	$query = "DELETE FROM spip_forum WHERE statut='redac' AND maj < $mydate";
	spip_query($query);

	$query = "SELECT id_forum FROM spip_forum";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) $forums[] = $row[0];

	if ($forums) {
		$forums = join(",", $forums);

		$query = "DELETE FROM spip_forum WHERE id_parent NOT IN (0,$forums)";
		spip_query($query);
		$query = "DELETE FROM spip_mots_forum WHERE id_forum NOT IN ($forums)";
		spip_query($query);
	}


	//
	// MySQL
	//

	$query = "SHOW TABLES LIKE '".$GLOBALS['table_prefix']."_%'";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) $tables[] = $row[0];

	if ($tables) {
		$tables = join(",", $tables);
		$query = "OPTIMIZE TABLE ".$tables;
		spip_query($query);
	}

	spip_log("optimisation ok");
}

optimiser_base();

?>
