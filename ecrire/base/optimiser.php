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


	// Les requetes DELETE multi table ne sont pas supportees par mysql<4.0
	// et ont une syntaxe differente entre 4.0 et 4.1
	// On passe donc par un SELECT puis DELETE unitaire dans une boucle while
	// en pariant sur le fait que le nombre d'objets a supprimer est marginal
	
	//
	// Rubriques
	//

	# les articles qui sont dans une id_rubrique inexistante
	$query = "SELECT articles.id_article 
		        FROM spip_articles AS articles
		        LEFT JOIN spip_rubriques AS rubriques
		          ON articles.id_rubrique=rubriques.id_rubrique
		       WHERE rubriques.id_rubrique IS NULL
		         AND articles.maj < $mydate";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_articles
		WHERE id_article=".$row['id_article']);

	# les breves qui sont dans une id_rubrique inexistante
	$query = "SELECT breves.id_breve 
		        FROM spip_breves AS breves
		        LEFT JOIN spip_rubriques AS rubriques
		          ON breves.id_rubrique=rubriques.id_rubrique
		       WHERE rubriques.id_rubrique IS NULL
		         AND breves.maj < $mydate";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_breves
		WHERE id_breve=".$row['id_breve']);

	# les forums lies a une id_rubrique inexistante
	$query = "SELECT forum.id_forum FROM spip_forum AS forum
		        LEFT JOIN spip_rubriques AS rubriques
		          ON forum.id_rubrique=rubriques.id_rubrique
		       WHERE rubriques.id_rubrique IS NULL
		         AND forum.id_rubrique>0";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_forum
		WHERE id_forum=".$row['id_forum']);

	# les droits d'auteurs sur une id_rubrique inexistante
	$query = "SELECT auteurs_rubriques.id_rubrique,auteurs_rubriques.id_auteur
	 	        FROM spip_auteurs_rubriques AS auteurs_rubriques
		        LEFT JOIN spip_rubriques AS rubriques
		          ON auteurs_rubriques.id_rubrique=rubriques.id_rubrique
		       WHERE rubriques.id_rubrique IS NULL";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_auteurs_rubriques
		WHERE id_auteur=".$row['id_auteur']
		." AND id_rubrique=".$row['id_rubrique']);


	# les liens des documents qui sont dans une id_rubrique inexistante
	$query = "SELECT documents_rubriques.id_document,documents_rubriques.id_rubrique
		      FROM spip_documents_rubriques AS documents_rubriques
		        LEFT JOIN spip_rubriques AS rubriques
		          ON documents_rubriques.id_rubrique=rubriques.id_rubrique
		       WHERE rubriques.id_rubrique IS NULL";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_documents_rubriques
		WHERE id_document=".$row['id_document']
		." AND id_rubrique=".$row['id_rubrique']);

	# les liens des mots affectes a une id_rubrique inexistante
	$query = "SELECT mots_rubriques.id_mot,mots_rubriques.id_rubrique
		      FROM spip_mots_rubriques AS mots_rubriques
		        LEFT JOIN spip_rubriques AS rubriques
		          ON mots_rubriques.id_rubrique=rubriques.id_rubrique
		       WHERE rubriques.id_rubrique IS NULL";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_mots_rubriques
		WHERE id_mot=".$row['id_mot']
		." AND id_rubrique=".$row['id_rubrique']);


	//
	// Articles
	//

	$query = "DELETE FROM spip_articles
		WHERE statut='poubelle' AND maj < $mydate";
	spip_query($query);

	# les liens d'auteurs d'articles effaces
	$query = "SELECT auteurs_articles.id_auteur,auteurs_articles.id_article
		      FROM spip_auteurs_articles AS auteurs_articles
		        LEFT JOIN spip_articles AS articles
		          ON auteurs_articles.id_article=articles.id_article
		       WHERE articles.id_article IS NULL";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_auteurs_articles
		WHERE id_auteur=".$row['id_auteur']
		." AND id_article=".$row['id_article']);

	# les liens de documents d'articles effaces
	$query = "SELECT documents_articles.id_document,documents_articles.id_article
		      FROM spip_documents_articles AS documents_articles
		        LEFT JOIN spip_articles AS articles
		          ON documents_articles.id_article=articles.id_article
		       WHERE articles.id_article IS NULL";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_documents_articles
		WHERE id_document=".$row['id_document']
		." AND id_article=".$row['id_article']);

	# les liens de mots affectes a des articles effaces
	$query = "SELECT mots_articles.id_mot,mots_articles.id_article 
		        FROM spip_mots_articles AS mots_articles
		        LEFT JOIN spip_articles AS articles
		          ON mots_articles.id_article=articles.id_article
		       WHERE articles.id_article IS NULL";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_mots_articles
		WHERE id_mot=".$row['id_mot']
		." AND id_article=".$row['id_article']);

	# les forums lies a des articles effaces
	$query = "SELECT forum.id_forum 
		        FROM spip_forum AS forum
		        LEFT JOIN spip_articles AS articles
		          ON forum.id_article=articles.id_article
		       WHERE articles.id_article IS NULL
		         AND forum.id_article>0";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_forum
		WHERE id_forum=".$row['id_forum']);


	//
	// Breves
	//

	$query = "DELETE FROM spip_breves
		WHERE statut='refuse' AND maj < $mydate";
	spip_query($query);

	# les liens de documents sur des breves effacees
	$query = "SELECT documents_breves.id_document,documents_breves.id_breve
		      FROM spip_documents_breves AS documents_breves
		        LEFT JOIN spip_breves AS breves
		          ON documents_breves.id_breve=breves.id_breve
		       WHERE breves.id_breve IS NULL";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_documents_breves
		WHERE id_document=".$row['id_document']
		." AND id_breve=".$row['id_breve']);

	# les liens de mots affectes a des breves effacees
	$query = "SELECT mots_breves.id_mot,mots_breves.id_breve
		        FROM spip_mots_breves AS mots_breves
		        LEFT JOIN spip_breves AS breves
		          ON mots_breves.id_breve=breves.id_breve
		       WHERE breves.id_breve IS NULL";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_mots_breves
		WHERE id_mot=".$row['id_mot']
		." AND id_breve=".$row['id_breve']);

	# les forums lies a des breves effacees
	$query = "SELECT forum.id_forum
		        FROM spip_forum AS forum
		        LEFT JOIN spip_breves AS breves
		          ON forum.id_breve=breves.id_breve
		       WHERE breves.id_breve IS NULL
		         AND forum.id_breve>0";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_forum
		WHERE id_forum=".$row['id_forum']);


	//
	// Sites
	//

	$query = "DELETE FROM spip_syndic
		WHERE maj < $mydate AND statut = 'refuse'";
	spip_query($query);

	# les articles syndiques appartenant a des sites effaces
	$query = "SELECT syndic_articles.id_syndic_article,syndic_articles.id_syndic
		      FROM spip_syndic_articles AS syndic_articles
		        LEFT JOIN spip_syndic AS syndic
		          ON syndic_articles.id_syndic=syndic.id_syndic
		       WHERE syndic.id_syndic IS NULL";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_syndic_articles
		WHERE id_syndic_article=".$row['id_syndic_article']
		." AND id_syndic=".$row['id_syndic']);

	# les liens de mots affectes a des sites effaces
	$query = "SELECT mots_syndic.id_mot,mots_syndic.id_syndic
		        FROM spip_mots_syndic AS mots_syndic
		        LEFT JOIN spip_syndic AS syndic
		          ON mots_syndic.id_syndic=syndic.id_syndic
		       WHERE syndic.id_syndic IS NULL";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_mots_syndic
		WHERE id_mot=".$row['id_mot']
		." AND id_syndic=".$row['id_syndic']);

	# les forums lies a des sites effaces
	$query = "SELECT forum.id_forum
		        FROM spip_forum AS forum
		        LEFT JOIN spip_syndic AS syndic
		          ON forum.id_syndic=syndic.id_syndic
		       WHERE syndic.id_syndic IS NULL
		         AND forum.id_syndic>0";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_forum
		WHERE id_forum=".$row['id_forum']);


	//
	// Auteurs
	//

	# les liens d'articles sur des auteurs effaces
	$query = "SELECT auteurs_articles.id_auteur,auteurs_articles.id_article
		      FROM spip_auteurs_articles AS auteurs_articles
		        LEFT JOIN spip_auteurs AS auteurs
		          ON auteurs_articles.id_auteur=auteurs.id_auteur
		       WHERE auteurs.id_auteur IS NULL";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_auteurs_articles
		WHERE id_auteur=".$row['id_auteur']
		." AND id_article=".$row['id_article']);

	# les liens de messages sur des auteurs effaces
	$query = "SELECT auteurs_messages.id_auteur,auteurs_messages.id_message
		      FROM spip_auteurs_messages AS auteurs_messages
		        LEFT JOIN spip_auteurs AS auteurs
		          ON auteurs_messages.id_auteur=auteurs.id_auteur
		       WHERE auteurs.id_auteur IS NULL";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_auteurs_messages
		WHERE id_auteur=".$row['id_auteur']
		." AND id_message=".$row['id_message']);

	# les liens d'articles sur des auteurs effaces
	$query = "SELECT auteurs_rubriques.id_auteur,auteurs_rubriques.id_rubrique
		      FROM spip_auteurs_rubriques AS auteurs_rubriques
		        LEFT JOIN spip_rubriques AS rubriques
		          ON auteurs_rubriques.id_rubrique=rubriques.id_rubrique
		       WHERE rubriques.id_rubrique IS NULL";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_auteurs_rubriques
		WHERE id_auteur=".$row['id_auteur']
		." AND id_rubrique=".$row['id_rubrique']);

	# effacer les auteurs poubelle qui ne sont lies a aucun article
	$query = "SELECT auteurs.id_auteur
		      	FROM spip_auteurs AS auteurs
		      	LEFT JOIN spip_auteurs_articles AS auteurs_articles
		          ON auteurs_articles.id_auteur=auteurs.id_auteur
		       WHERE auteurs_articles.id_auteur IS NULL
		       	AND auteurs.statut='5poubelle' AND auteurs.maj < $mydate";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_auteurs
		WHERE id_auteur=".$row['id_auteur']);


	//
	// Messages prives
	//

	# supprimer les messages lies a un auteur disparu
	$query = "SELECT messages.id_message
		      FROM spip_messages AS messages
		        LEFT JOIN spip_auteurs AS auteurs
		          ON auteurs.id_auteur=messages.id_auteur
		       WHERE auteurs.id_auteur IS NULL";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_messages
		WHERE id_message=".$row['id_message']);


	//
	// Mots-cles
	//

	$query = "DELETE FROM spip_mots
		WHERE titre='' AND maj < $mydate";
	$result = spip_query($query);

	# les liens mots-articles sur des mots effaces
	$query = "SELECT mots_articles.id_mot,mots_articles.id_article
		        FROM spip_mots_articles AS mots_articles
		        LEFT JOIN spip_mots AS mots
		          ON mots_articles.id_mot=mots.id_mot
		       WHERE mots.id_mot IS NULL";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_mots_articles
		WHERE id_mot=".$row['id_mot']
		." AND id_article=".$row['id_article']);

	# les liens mots-breves sur des mots effaces
	$query = "SELECT mots_breves.id_mot,mots_breves.id_breve
		        FROM spip_mots_breves AS mots_breves
		        LEFT JOIN spip_mots AS mots
		          ON mots_breves.id_mot=mots.id_mot
		       WHERE mots.id_mot IS NULL";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_mots_breves
		WHERE id_mot=".$row['id_mot']
		." AND id_breve=".$row['id_breve']);

	# les liens mots-forum sur des mots effaces
	$query = "SELECT mots_forum.id_mot,mots_forum.id_forum
		        FROM spip_mots_forum AS mots_forum
		        LEFT JOIN spip_mots AS mots
		          ON mots_forum.id_mot=mots.id_mot
		       WHERE mots.id_mot IS NULL";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_mots_forum
		WHERE id_mot=".$row['id_mot']
		." AND id_forum=".$row['id_forum']);

	# les liens mots-rubriques sur des mots effaces
	$query = "SELECT mots_rubriques.id_mot,mots_rubriques.id_rubrique
		      FROM spip_mots_rubriques AS mots_rubriques
		        LEFT JOIN spip_mots AS mots
		          ON mots_rubriques.id_mot=mots.id_mot
		       WHERE mots.id_mot IS NULL";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_mots_rubriques
		WHERE id_mot=".$row['id_mot']
		." AND id_rubrique=".$row['id_rubrique']);

	# les liens mots-syndic sur des mots effaces
	$query = "SELECT mots_syndic.id_mot,mots_syndic.id_syndic
		        FROM spip_mots_syndic AS mots_syndic
		        LEFT JOIN spip_mots AS mots
		          ON mots_syndic.id_mot=mots.id_mot
		       WHERE mots.id_mot IS NULL";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_mots_syndic
		WHERE id_mot=".$row['id_mot']
		." AND id_syndic=".$row['id_syndic']);


	//
	// Forums
	//

	$query = "DELETE FROM spip_forum
		WHERE statut='redac' AND maj < $mydate";
	spip_query($query);

	# les liens mots-forum sur des forums effaces
	$query = "SELECT mots_forum.id_mot,mots_forum.id_forum
		        FROM spip_mots_forum AS mots_forum
		        LEFT JOIN spip_forum AS forum
		          ON mots_forum.id_forum=forum.id_forum
		       WHERE forum.id_forum IS NULL";
	$res = spip_query($query);
	while ($row = spip_fetch_array($res,SPIP_ASSOC))
		spip_query("DELETE FROM spip_mots_forum
		WHERE id_mot=".$row['id_mot']
		." AND id_forum=".$row['id_forum']);

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

		spip_query("UPDATE $table_objet SET idx=''
		WHERE idx<>'non' $critere");

		$suppr = '';
		$s = spip_query("SELECT $col_id FROM $table_objet
			WHERE idx='' $critere");
		while ($t = spip_fetch_array($s))
			$suppr .= ','.$t[0];
		$s = spip_query("SELECT $col_id FROM $table_objet
			WHERE idx='non'");
		while ($t = spip_fetch_array($s))
			$suppr .= ','.$t[0];
		if ($suppr)
			spip_query("DELETE FROM spip_index
				WHERE id_objet IN (0$suppr) AND id_table=$id_table");
	}

	spip_log("optimisation terminee");
}

?>
