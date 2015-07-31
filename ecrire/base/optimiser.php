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
include_spip('inc/indexation');

function optimiser_base() {
	spip_log ("optimisation de la base");

	$mydate = date("YmdHis", time() - 24 * 3600);

	//
	// Rubriques
	//

	$query = "SELECT id_rubrique FROM spip_rubriques";
	$result = spip_query($query);
	$rubriques = '0';
	while ($row = spip_fetch_array($result)) $rubriques .= ','.$row['id_rubrique'];

	if ($rubriques) {
		$query = "DELETE FROM spip_articles WHERE id_rubrique NOT IN ($rubriques) AND maj < $mydate";
		spip_query($query);
		$query = "DELETE FROM spip_breves WHERE id_rubrique NOT IN ($rubriques) AND maj < $mydate";
		spip_query($query);
		$query = "DELETE FROM spip_forum WHERE id_rubrique NOT IN (0,$rubriques)";
		spip_query($query);
		$query = "DELETE FROM spip_auteurs_rubriques WHERE id_rubrique NOT IN ($rubriques)";
		spip_query($query);
		$query = "DELETE FROM spip_documents_rubriques WHERE id_rubrique NOT IN ($rubriques)";
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
	$articles = '0';
	while ($row = spip_fetch_array($result)) $articles .= ','.$row['id_article'];

	if ($articles) {
		$query = "DELETE FROM spip_auteurs_articles WHERE id_article NOT IN ($articles)";
		spip_query($query);
		$query = "DELETE FROM spip_documents_articles WHERE id_article NOT IN ($articles)";
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
	$breves = '0';
	$query = "SELECT id_breve FROM spip_breves";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) $breves .= ','.$row['id_breve'];

	if ($breves) {
		$query = "DELETE FROM spip_documents_breves WHERE id_breve NOT IN ($breves)";
		spip_query($query);
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

	$syndic = '0';
	$query = "SELECT id_syndic FROM spip_syndic";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) $syndic .= ','.$row['id_syndic'];

	if ($syndic) {
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

	$auteurs = '0';
	$query = "SELECT id_auteur FROM spip_auteurs";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) $auteurs .= ','.$row['id_auteur'];

	if ($auteurs) {
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

	$mots = '0';
	$query = "SELECT id_mot FROM spip_mots";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) $mots .= ','.$row['id_mot'];

	if ($mots) {
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

	$forums = '0';
	$query = "SELECT id_forum FROM spip_forum";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) $forums .= ','.$row[0];

	if ($forums) {
		$query = "DELETE FROM spip_forum WHERE id_parent NOT IN (0,$forums)";
		spip_query($query);
		$query = "DELETE FROM spip_mots_forum WHERE id_forum NOT IN ($forums)";
		spip_query($query);
	}


	//
	// Indexation
	//

	// les objets inutiles
	$liste_tables = liste_index_tables();
	//$types = array('article','auteur','breve','mot','rubrique','forum','signature','syndic');
	while (list($id_table,$table_objet) = each($liste_tables)) {
		$table_index = 'spip_index';
		$col_id = primary_index_table($table_objet);
		$critere = critere_optimisation($table_objet);
		if (strlen($critere)>0)
		  $critere = "AND $critere";

		spip_query("UPDATE $table_objet SET idx='' WHERE idx<>'non' $critere");

		$suppr = '';
		$s = spip_query("SELECT $col_id FROM $table_objet WHERE idx='' $critere");
		while ($t = spip_fetch_array($s))
			$suppr .= ','.$t[0];
		$s = spip_query("SELECT $col_id FROM $table_objet WHERE idx='non'");
		while ($t = spip_fetch_array($s))
			$suppr .= ','.$t[0];
		if ($suppr)
			spip_query("DELETE FROM $table_index WHERE id_objet IN (0$suppr) AND id_table=$id_table");
	}

	//
	// MySQL
	//
	if ($GLOBALS['table_prefix']) $table_pref = $GLOBALS['table_prefix']."_";
	else $table_pref = "";

	$query = "SHOW TABLES LIKE '$table_pref%'";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) $tables[] = $row[0];

	if ($tables) {
		$tables = join(",", $tables);
		$query = "OPTIMIZE TABLE ".$tables;
		spip_query($query);
	}

	spip_log("optimisation ok");
}

	## debug
	if ($GLOBALS['auteur_session']['statut'] == '0minirezo'
	AND $_GET['optimiser'] == 'oui')
		optimiser_base();

?>
