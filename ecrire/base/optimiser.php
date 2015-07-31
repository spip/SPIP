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

function optimiser_base() {
	spip_log ("optimisation de la base");

	$mydate = date("YmdHis", time() - 24 * 3600);

	//
	// Rubriques
	//

	# les articles qui sont dans une id_rubrique inexistante
	$query = "DELETE spip_articles FROM spip_articles AS articles
		        LEFT JOIN spip_rubriques AS rubriques
		          ON articles.id_rubrique=rubriques.id_rubrique
		       WHERE rubriques.id_rubrique IS NULL
		         AND articles.maj < $mydate";
	spip_query($query);

	# les breves qui sont dans une id_rubrique inexistante
	$query = "DELETE spip_breves FROM spip_breves AS breves
		        LEFT JOIN spip_rubriques AS rubriques
		          ON breves.id_rubrique=rubriques.id_rubrique
		       WHERE rubriques.id_rubrique IS NULL
		         AND breves.maj < $mydate";
	spip_query($query);

	# les forums lies a une id_rubrique inexistante
	$query = "DELETE spip_forum FROM spip_forum AS forum
		        LEFT JOIN spip_rubriques AS rubriques
		          ON forum.id_rubrique=rubriques.id_rubrique
		       WHERE rubriques.id_rubrique IS NULL
		         AND forum.id_rubrique>0";
	spip_query($query);

	# les droits d'auteurs sur une id_rubrique inexistante
	$query = "DELETE spip_auteurs_rubriques
		      FROM spip_auteurs_rubriques AS auteurs_rubriques
		        LEFT JOIN spip_rubriques AS rubriques
		          ON auteurs_rubriques.id_rubrique=rubriques.id_rubrique
		       WHERE rubriques.id_rubrique IS NULL";
	spip_query($query);

	# les liens des documents qui sont dans une id_rubrique inexistante
	$query = "DELETE spip_documents_rubriques
		      FROM spip_documents_rubriques AS documents_rubriques
		        LEFT JOIN spip_rubriques AS rubriques
		          ON documents_rubriques.id_rubrique=rubriques.id_rubrique
		       WHERE rubriques.id_rubrique IS NULL";
	spip_query($query);

	# les liens des mots affectes a une id_rubrique inexistante
	$query = "DELETE spip_mots_rubriques
		      FROM spip_mots_rubriques AS mots_rubriques
		        LEFT JOIN spip_rubriques AS rubriques
		          ON mots_rubriques.id_rubrique=rubriques.id_rubrique
		       WHERE rubriques.id_rubrique IS NULL";
	spip_query($query);


	//
	// Articles
	//

	$query = "DELETE FROM spip_articles WHERE statut='poubelle' AND maj < $mydate";
	spip_query($query);

	# les liens d'auteurs d'articles effaces
	$query = "DELETE spip_auteurs_articles
		      FROM spip_auteurs_articles AS auteurs_articles
		        LEFT JOIN spip_articles AS articles
		          ON auteurs_articles.id_article=articles.id_article
		       WHERE articles.id_article IS NULL";
	spip_query($query);

	# les liens de documents d'articles effaces
	$query = "DELETE spip_documents_articles
		      FROM spip_documents_articles AS documents_articles
		        LEFT JOIN spip_articles AS articles
		          ON documents_articles.id_article=articles.id_article
		       WHERE articles.id_article IS NULL";
	spip_query($query);

	# les liens de mots affectes a des articles effaces
	$query = "DELETE spip_mots_articles FROM spip_mots_articles AS mots_articles
		        LEFT JOIN spip_articles AS articles
		          ON mots_articles.id_article=articles.id_article
		       WHERE articles.id_article IS NULL";
	spip_query($query);

	# les forums lies a des articles effaces
	$query = "DELETE spip_forum FROM spip_forum AS forum
		        LEFT JOIN spip_articles AS articles
		          ON forum.id_article=articles.id_article
		       WHERE articles.id_article IS NULL
		         AND forum.id_article>0";
	spip_query($query);


	//
	// Breves
	//

	$query = "DELETE FROM spip_breves WHERE statut='refuse' AND maj < $mydate";
	spip_query($query);

	# les liens de documents sur des breves effacees
	$query = "DELETE spip_documents_breves
		      FROM spip_documents_breves AS documents_breves
		        LEFT JOIN spip_breves AS breves
		          ON documents_breves.id_breve=breves.id_breve
		       WHERE breves.id_breve IS NULL";
	spip_query($query);

	# les liens de mots affectes a des breves effacees
	$query = "DELETE spip_mots_breves FROM spip_mots_breves AS mots_breves
		        LEFT JOIN spip_breves AS breves
		          ON mots_breves.id_breve=breves.id_breve
		       WHERE breves.id_breve IS NULL";
	spip_query($query);

	# les forums lies a des breves effacees
	$query = "DELETE spip_forum FROM spip_forum AS forum
		        LEFT JOIN spip_breves AS breves
		          ON forum.id_breve=breves.id_breve
		       WHERE breves.id_breve IS NULL
		         AND forum.id_breve>0";
	spip_query($query);


	//
	// Sites
	//

	$query = "DELETE FROM spip_syndic WHERE maj < $mydate AND statut = 'refuse'";
	spip_query($query);

	# les articles syndiques appartenant a des sites effaces
	$query = "DELETE spip_syndic_articles
		      FROM spip_syndic_articles AS syndic_articles
		        LEFT JOIN spip_syndic AS syndic
		          ON syndic_articles.id_syndic=syndic.id_syndic
		       WHERE syndic.id_syndic IS NULL";
	spip_query($query);

	# les liens de mots affectes a des sites effaces
	$query = "DELETE spip_mots_syndic FROM spip_mots_syndic AS mots_syndic
		        LEFT JOIN spip_syndic AS syndic
		          ON mots_syndic.id_syndic=syndic.id_syndic
		       WHERE syndic.id_syndic IS NULL";
	spip_query($query);

	# les forums lies a des sites effaces
	$query = "DELETE spip_forum FROM spip_forum AS forum
		        LEFT JOIN spip_syndic AS syndic
		          ON forum.id_syndic=syndic.id_syndic
		       WHERE syndic.id_syndic IS NULL
		         AND forum.id_syndic>0";
	spip_query($query);


	//
	// Auteurs
	//

	# les liens d'articles sur des auteurs effaces
	$query = "DELETE spip_auteurs_articles
		      FROM spip_auteurs_articles AS auteurs_articles
		        LEFT JOIN spip_auteurs AS auteurs
		          ON auteurs_articles.id_auteur=auteurs.id_auteur
		       WHERE auteurs.id_auteur IS NULL";
	spip_query($query);

	# les liens de messages sur des auteurs effaces
	$query = "DELETE spip_auteurs_messages
		      FROM spip_auteurs_messages AS auteurs_messages
		        LEFT JOIN spip_auteurs AS auteurs
		          ON auteurs_messages.id_auteur=auteurs.id_auteur
		       WHERE auteurs.id_auteur IS NULL";
	spip_query($query);

	# les liens d'articles sur des auteurs effaces
	$query = "DELETE spip_auteurs_rubriques
		      FROM spip_auteurs_rubriques AS auteurs_rubriques
		        LEFT JOIN spip_rubriques AS rubriques
		          ON auteurs_rubriques.id_rubrique=rubriques.id_rubrique
		       WHERE rubriques.id_rubrique IS NULL";
	spip_query($query);

	# effacer les auteurs poubelle qui ne sont lies a aucun article
	$query = "DELETE spip_auteurs
		      FROM spip_auteurs AS auteurs
		        LEFT JOIN spip_auteurs_articles AS auteurs_articles
		          ON auteurs_articles.id_auteur=auteurs.id_auteur
		       WHERE auteurs_articles.id_auteur IS NULL
		       AND auteurs.statut='5poubelle' AND auteurs.maj < $mydate";
	spip_query($query);


	//
	// Messages prives
	//

	# supprimer les messages lies a un auteur disparu
	$query = "DELETE spip_messages
		      FROM spip_messages AS messages
		        LEFT JOIN spip_auteurs AS auteurs
		          ON auteurs.id_auteur=messages.id_auteur
		       WHERE auteurs.id_auteur IS NULL";
	spip_query($query);


	//
	// Mots-cles
	//

	$query = "DELETE FROM spip_mots WHERE titre='' AND maj < $mydate";
	$result = spip_query($query);

	# les liens mots-articles sur des mots effaces
	$query = "DELETE spip_mots_articles FROM spip_mots_articles AS mots_articles
		        LEFT JOIN spip_mots AS mots
		          ON mots_articles.id_mot=mots.id_mot
		       WHERE mots.id_mot IS NULL";
	spip_query($query);

	# les liens mots-breves sur des mots effaces
	$query = "DELETE spip_mots_breves FROM spip_mots_breves AS mots_breves
		        LEFT JOIN spip_mots AS mots
		          ON mots_breves.id_mot=mots.id_mot
		       WHERE mots.id_mot IS NULL";
	spip_query($query);

	# les liens mots-forum sur des mots effaces
	$query = "DELETE spip_mots_forum FROM spip_mots_forum AS mots_forum
		        LEFT JOIN spip_mots AS mots
		          ON mots_forum.id_mot=mots.id_mot
		       WHERE mots.id_mot IS NULL";
	spip_query($query);

	# les liens mots-rubriques sur des mots effaces
	$query = "DELETE spip_mots_rubriques
		      FROM spip_mots_rubriques AS mots_rubriques
		        LEFT JOIN spip_mots AS mots
		          ON mots_rubriques.id_mot=mots.id_mot
		       WHERE mots.id_mot IS NULL";
	spip_query($query);

	# les liens mots-syndic sur des mots effaces
	$query = "DELETE spip_mots_syndic FROM spip_mots_syndic AS mots_syndic
		        LEFT JOIN spip_mots AS mots
		          ON mots_syndic.id_mot=mots.id_mot
		       WHERE mots.id_mot IS NULL";
	spip_query($query);


	//
	// Forums
	//

	$query = "DELETE FROM spip_forum WHERE statut='redac' AND maj < $mydate";
	spip_query($query);

######### Ce code reste a verifier ; mais il semble marcher
/*
	# les reponses a des forums effaces
	$query = "DELETE spip_forum FROM spip_forum AS sf1
		        LEFT JOIN spip_forum AS sf2
		          ON sf1.id_parent=sf2.id_forum
		       WHERE sf2.id_forum IS NULL
		         AND sf1.id_parent>0";
	spip_query($query);


*/
#########

	# ALTERNATIVEMENT (semblerait logique, mais horriblement lent....)
/*
	$query = "CREATE TEMPORARY TABLE temp SELECT id_forum FROM spip_forum";
	spip_query($query);
	$query = "DELETE spip_forum FROM spip_forum AS sf1
	            LEFT JOIN temp
	              ON sf1.id_parent = temp.id_forum
	           WHERE temp.id_forum IS NULL
	             AND sf1.id_parent > 0";
	spip_query($query);
	#	spip_query("DROP TABLE temp");
*/

	# les liens mots-forum sur des forums effaces
	$query = "DELETE spip_mots_forum FROM spip_mots_forum AS mots_forum
		        LEFT JOIN spip_forum AS forum
		          ON mots_forum.id_forum=forum.id_forum
		       WHERE forum.id_forum IS NULL";
	spip_query($query);



	//
	// Indexation
	//

	// les objets inutiles
	include_spip('inc/indexation');
	$liste_tables = liste_index_tables();
	foreach ($liste_tables as $id_table => $table_objet) {
		$col_id = primary_index_table($table_objet);
		$critere = critere_optimisation($table_objet);
		if (strlen($critere)>0)
			$critere = "AND $critere";

		spip_query("UPDATE $table_objet SET idx='' WHERE idx<>'non' $critere");

		$suppr = '';
		$s = spip_query("SELECT $col_id FROM $table_objet
			WHERE idx='' $critere");
		while ($t = spip_fetch_array($s))
			$suppr .= ','.$t[0];
		$s = spip_query("SELECT $col_id FROM $table_objet WHERE idx='non'");
		while ($t = spip_fetch_array($s))
			$suppr .= ','.$t[0];
		if ($suppr)
			spip_query("DELETE FROM spip_index
				WHERE id_objet IN (0$suppr) AND id_table=$id_table");
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

	spip_log("optimisation terminee");
}

?>
