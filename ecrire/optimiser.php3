<?

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_OPTIMISER")) return;
define("_ECRIRE_OPTIMISER", "1");


function optimiser_base() {

	$mydate = date("YmdHis", time() - 24 * 3600);
	
	//
	// Rubriques
	//
	
	$query = "SELECT id_rubrique FROM spip_rubriques";
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) $rubriques[] = $row[0];
	
	if ($rubriques) {
		$rubriques = join(",", $rubriques);
	
		$query = "DELETE FROM spip_articles WHERE id_rubrique NOT IN ($rubriques) AND maj < $mydate";
		mysql_query($query);
		$query = "DELETE FROM spip_breves WHERE id_rubrique NOT IN ($rubriques) AND maj < $mydate";
		mysql_query($query);
		$query = "DELETE FROM spip_forum WHERE id_rubrique NOT IN (0,$rubriques)";
		mysql_query($query);
		$query = "DELETE FROM spip_auteurs_rubriques WHERE id_rubrique NOT IN ($rubriques)";
		mysql_query($query);
	}
	
	
	//
	// Articles
	//
	
	$query = "SELECT id_article FROM spip_articles";
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) $articles[] = $row[0];
	
	if ($articles) {
		$articles = join(",", $articles);
	
		$query = "DELETE FROM spip_auteurs_articles WHERE id_article NOT IN ($articles)";
		mysql_query($query);
		$query = "DELETE FROM spip_mots_articles WHERE id_article NOT IN ($articles)";
		mysql_query($query);
		$query = "DELETE FROM spip_forum WHERE id_article NOT IN (0,$articles)";
		mysql_query($query);
	}
	
	
	//
	// Breves
	//
	
	$query = "SELECT id_breve FROM spip_breves";
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) $breves[] = $row[0];
	
	if ($breves) {
		$breves = join(",", $breves);
	
		$query = "DELETE FROM spip_forum WHERE id_breve NOT IN (0,$breves)";
		mysql_query($query);
	}
	
	
	//
	// Sites
	//
	
	
	$query = "DELETE FROM spip_syndic WHERE maj < $mydate AND statut = 'refuse'";
	mysql_query($query);
	
	$query = "SELECT id_syndic FROM spip_syndic";
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) $syndic[] = $row[0];
	
	if ($syndic) {
		$syndic = join(",", $syndic);
	
		$query = "DELETE FROM spip_syndic_articles WHERE id_syndic NOT IN (0,$syndic)";
		mysql_query($query);
	}
	
	
	//
	// Auteurs
	//
	
	$query = "SELECT id_auteur FROM spip_auteurs";
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) $auteurs[] = $row[0];
	
	if ($auteurs) {
		$auteurs = join(",", $auteurs);
	
		$query = "DELETE FROM spip_auteurs_articles WHERE id_auteur NOT IN ($auteurs)";
		mysql_query($query);
		$query = "DELETE FROM spip_auteurs_messages WHERE id_auteur NOT IN ($auteurs)";
		mysql_query($query);
		$query = "DELETE FROM spip_auteurs_rubriques WHERE id_auteur NOT IN ($auteurs)";
		mysql_query($query);
	}
	
	$query = "SELECT id_auteur FROM spip_auteurs WHERE statut='5poubelle' AND maj < $mydate";
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		$id_auteur = $row[0];
	
		$query2 = "SELECT * FROM spip_auteurs_articles WHERE id_auteur=$id_auteur";
		$result2 = mysql_query($query2);
		if (!mysql_num_rows($result2)) {
			$query3 = "DELETE FROM spip_auteurs WHERE id_auteur=$id_auteur";
			$result3 = mysql_query($query3);
		}
	}
	
	
	//
	// Forums
	//
	
	$query = "SELECT id_forum FROM spip_forum";
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) $forums[] = $row[0];
	
	if ($forums) {
		$forums = join(",", $forums);
	
		$query = "DELETE FROM spip_forum WHERE id_parent NOT IN (0,$forums)";
		mysql_query($query);

		mysql_query("DELETE FROM spip_forum WHERE statut='redac' AND  date_time<DATE_SUB(NOW(),INTERVAL 1 DAY)");

	}
	
	
	
	
	//
	// Messages
	//
	
	$query = "SELECT m.id_message FROM spip_messages AS m, spip_auteurs_messages AS lien ".
		"WHERE m.id_message = lien.id_message GROUP BY m.id_message";
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) $messages[] = $row[0];
	
	$query = "SELECT id_message FROM spip_messages ".
		"WHERE type ='affich'";
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) $messages[] = $row[0];
	
	if ($messages) {
		$messages = join(",", $messages);
	
		$query = "DELETE FROM spip_messages WHERE id_message NOT IN ($messages)";
		mysql_query($query);
		$query = "DELETE FROM spip_forum WHERE id_message NOT IN (0,$messages)";
		mysql_query($query);
	}
	
	
	//
	// Mots-cles
	//
	
	$query = "DELETE FROM spip_mots WHERE titre='' AND maj < $mydate";
	$result = mysql_query($query);
	
	$query = "SELECT id_mot FROM spip_mots";
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) $mots[] = $row[0];
	
	if ($mots) {
		$mots = join(",", $mots);
	
		$query = "DELETE FROM spip_mots_articles WHERE id_mot NOT IN ($mots)";
		mysql_query($query);
	}
	
	//
	// MySQL
	//
	
	$query = "OPTIMIZE TABLE spip_meta, "
		. "spip_articles, spip_rubriques, spip_breves, spip_auteurs, spip_auteurs_articles, spip_forum, spip_forum_cache, spip_mots, spip_mots_articles, "
		. "spip_index_dico, spip_index_articles, spip_index_rubriques, spip_index_breves, spip_index_auteurs, spip_index_mots, spip_index_syndic";
	mysql_query($query);
	
	echo "\n\n<!-- Optimisation ok. -->\n";
}

optimiser_base();

?>