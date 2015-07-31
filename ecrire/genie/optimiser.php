<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('base/abstract_sql');

function genie_optimiser_dist($t) {

	optimiser_base_une_table();
	optimiser_base();
	return 1;
}

// heure de reference pour le garbage collector = 24h auparavant
// http://doc.spip.org/@optimiser_base
function optimiser_base($attente = 86400) {

	optimiser_base_disparus($attente);

	// optimisation a passer dans le plugin indexation
	include_spip('inc/indexation');
	if (function_exists('optimiser_base_indexation')) {
		optimiser_base_indexation();
	}
}


// http://doc.spip.org/@optimiser_base_une_table
function optimiser_base_une_table() {

	$tables = array();
	$result = sql_showbase();

	// on n'optimise qu'une seule table a chaque fois,
	// pour ne pas vautrer le systeme
	// lire http://dev.mysql.com/doc/refman/5.0/fr/optimize-table.html
	while ($row = sql_fetch($result))
		$tables[] = array_shift($row);

	if ($tables) {
		$table_op = intval($GLOBALS['meta']['optimiser_table']+1) % sizeof($tables);
		ecrire_meta('optimiser_table', $table_op);
		$q = $tables[$table_op];
		spip_log("debut d'optimisation de la table $q");
		if (sql_optimize($q))
			spip_log("fin d'optimisation de la table $q");
		else spip_log("Pas d'optimiseur necessaire");
	}
}

// mysql < 4.0 refuse les requetes DELETE multi table 
// et elles ont une syntaxe differente entre 4.0 et 4.1
// On passe donc par un SELECT puis DELETE avec IN 

// Utilitaire exploitant le SELECT et appliquant DELETE
// L'index du SELECT doit s'appeler "id"

// http://doc.spip.org/@optimiser_sansref
function optimiser_sansref($table, $id, $sel)
{
	$in = array();
	while ($row = sql_fetch($sel)) $in[$row['id']]=true;

	if ($in) {
		$in = join(',', array_keys($in));
		sql_delete($table,  sql_in($id,$in));
		spip_log("Numeros des entrees $id supprimees dans la table $table: $in");
	}
	return count($in);
}

// Nomenclature des liens morts entre les tables,
// suite a la suppresion d'articles, d'auteurs etc
// Maintenant que MySQL 5 a des Cascades on pourrait faire autrement
// mais on garde la compatibilite avec les versions precedentes.

// http://doc.spip.org/@optimiser_base_disparus
function optimiser_base_disparus($attente = 86400) {

	# format = 20060610110141, si on veut forcer une optimisation tout de suite
	$mydate = date("YmdHis", time() - $attente);

	$n = 0;

	//
	// Rubriques 
	//

	# les articles qui sont dans une id_rubrique inexistante
	$res = sql_select("articles.id_article AS id",
		        "spip_articles AS articles
		        LEFT JOIN spip_rubriques AS rubriques
		          ON articles.id_rubrique=rubriques.id_rubrique",
			"rubriques.id_rubrique IS NULL
		         AND articles.maj < $mydate");

	$n+= optimiser_sansref('spip_articles', 'id_article', $res);

	# les breves qui sont dans une id_rubrique inexistante
	$res = sql_select("breves.id_breve AS id",
		        "spip_breves AS breves
		        LEFT JOIN spip_rubriques AS rubriques
		          ON breves.id_rubrique=rubriques.id_rubrique",
			"rubriques.id_rubrique IS NULL
		         AND breves.maj < $mydate");

	$n+= optimiser_sansref('spip_breves', 'id_breve', $res);

	# les forums lies a une id_rubrique inexistante
	$res = sql_select("forum.id_forum AS id",
			"spip_forum AS forum
		        LEFT JOIN spip_rubriques AS rubriques
		          ON forum.id_rubrique=rubriques.id_rubrique",
			"rubriques.id_rubrique IS NULL
		         AND forum.id_rubrique>0");

	$n+= optimiser_sansref('spip_forum', 'id_forum', $res);

	# les droits d'auteurs sur une id_rubrique inexistante
	# (plusieurs entrees seront eventuellement detruites pour chaque rub)
	$res = sql_select("auteurs_rubriques.id_rubrique AS id",
	 	        "spip_auteurs_rubriques AS auteurs_rubriques
		        LEFT JOIN spip_rubriques AS rubriques
		          ON auteurs_rubriques.id_rubrique=rubriques.id_rubrique",
			"rubriques.id_rubrique IS NULL");

	$n+= optimiser_sansref('spip_auteurs_rubriques', 'id_rubrique', $res);

	# les liens des documents qui sont dans une id_rubrique inexistante
	# (meme remarque)
	$res = sql_select("documents_rubriques.id_rubrique AS id",
		      "spip_documents_rubriques AS documents_rubriques
		        LEFT JOIN spip_rubriques AS rubriques
		          ON documents_rubriques.id_rubrique=rubriques.id_rubrique",
			"rubriques.id_rubrique IS NULL");

	$n+= optimiser_sansref('spip_documents_rubriques', 'id_rubrique', $res);

	# les liens des mots affectes a une id_rubrique inexistante
	$res = sql_select("mots_rubriques.id_rubrique AS id",
		      "spip_mots_rubriques AS mots_rubriques
		        LEFT JOIN spip_rubriques AS rubriques
		          ON mots_rubriques.id_rubrique=rubriques.id_rubrique",
			"rubriques.id_rubrique IS NULL");

	$n+= optimiser_sansref('spip_mots_rubriques', 'id_rubrique', $res);

	//
	// Articles
	//

	sql_delete("spip_articles", "statut='poubelle' AND maj < $mydate");

	# les liens d'auteurs d'articles effaces
	$res = sql_select("auteurs_articles.id_article AS id",
		      "spip_auteurs_articles AS auteurs_articles
		        LEFT JOIN spip_articles AS articles
		          ON auteurs_articles.id_article=articles.id_article",
			"articles.id_article IS NULL");

	$n+= optimiser_sansref('spip_auteurs_articles', 'id_article', $res);

	# les liens de documents d'articles effaces
	$res = sql_select("documents_articles.id_article AS id",
		      "spip_documents_articles AS documents_articles
		        LEFT JOIN spip_articles AS articles
		          ON documents_articles.id_article=articles.id_article",
			"articles.id_article IS NULL");

	$n+= optimiser_sansref('spip_documents_articles', 'id_article', $res);

	# les liens de mots affectes a des articles effaces
	$res = sql_select("mots_articles.id_article AS id",
		        "spip_mots_articles AS mots_articles
		        LEFT JOIN spip_articles AS articles
		          ON mots_articles.id_article=articles.id_article",
			"articles.id_article IS NULL");

	$n+= optimiser_sansref('spip_mots_articles', 'id_article', $res);

	# les forums lies a des articles effaces
	$res = sql_select("forum.id_forum AS id",
		        "spip_forum AS forum
		        LEFT JOIN spip_articles AS articles
		          ON forum.id_article=articles.id_article",
			"articles.id_article IS NULL
		         AND forum.id_article>0");

	$n+= optimiser_sansref('spip_forum', 'id_forum', $res);

	//
	// Breves
	//

	sql_delete("spip_breves", "statut='refuse' AND maj < $mydate");


	# les liens de documents sur des breves effacees
	$res = sql_select("documents_breves.id_breve AS id",
		      "spip_documents_breves AS documents_breves
		        LEFT JOIN spip_breves AS breves
		          ON documents_breves.id_breve=breves.id_breve",
			"breves.id_breve IS NULL");

	$n+= optimiser_sansref('spip_documents_breves', 'id_breve', $res);

	# les liens de mots affectes a des breves effacees
	$res = sql_select("mots_breves.id_breve AS id",
		        "spip_mots_breves AS mots_breves
		        LEFT JOIN spip_breves AS breves
		          ON mots_breves.id_breve=breves.id_breve",
			"breves.id_breve IS NULL");

	$n+= optimiser_sansref('spip_mots_breves', 'id_breve', $res);

	# les forums lies a des breves effacees
	$res = sql_select("forum.id_forum AS id",
		        "spip_forum AS forum
		        LEFT JOIN spip_breves AS breves
		          ON forum.id_breve=breves.id_breve",
			"breves.id_breve IS NULL
		         AND forum.id_breve>0");

	$n+= optimiser_sansref('spip_forum', 'id_forum', $res);


	//
	// Sites
	//

	sql_delete("spip_syndic", "maj < $mydate AND statut = 'refuse'");


	# les articles syndiques appartenant a des sites effaces
	$res = sql_select("syndic_articles.id_syndic AS id",
		      "spip_syndic_articles AS syndic_articles
		        LEFT JOIN spip_syndic AS syndic
		          ON syndic_articles.id_syndic=syndic.id_syndic",
			"syndic.id_syndic IS NULL");

	$n+= optimiser_sansref('spip_syndic_articles', 'id_syndic', $res);

	# les liens de mots affectes a des sites effaces
	$res = sql_select("mots_syndic.id_syndic AS id",
		        "spip_mots_syndic AS mots_syndic
		        LEFT JOIN spip_syndic AS syndic
		          ON mots_syndic.id_syndic=syndic.id_syndic",
			"syndic.id_syndic IS NULL");

	$n+= optimiser_sansref('spip_mots_syndic', 'id_syndic', $res);

	# les forums lies a des sites effaces
	$res = sql_select("forum.id_forum AS id",
		        "spip_forum AS forum
		        LEFT JOIN spip_syndic AS syndic
		          ON forum.id_syndic=syndic.id_syndic",
			"syndic.id_syndic IS NULL
		         AND forum.id_syndic>0");

	$n+= optimiser_sansref('spip_forum', 'id_forum', $res);

	//
	// Auteurs
	//

	# les liens d'articles sur des auteurs effaces
	$res = sql_select("auteurs_articles.id_auteur AS id",
		      "spip_auteurs_articles AS auteurs_articles
		        LEFT JOIN spip_auteurs AS auteurs
		          ON auteurs_articles.id_auteur=auteurs.id_auteur",
			"auteurs.id_auteur IS NULL");

	$n+= optimiser_sansref('spip_auteurs_articles', 'id_auteur', $res);

	# les liens de messages sur des auteurs effaces
	$res = sql_select("auteurs_messages.id_auteur AS id",
		      "spip_auteurs_messages AS auteurs_messages
		        LEFT JOIN spip_auteurs AS auteurs
		          ON auteurs_messages.id_auteur=auteurs.id_auteur",
			"auteurs.id_auteur IS NULL");

	$n+= optimiser_sansref('spip_auteurs_messages', 'id_auteur', $res);

	# les liens de rubriques sur des auteurs effaces
	$res = sql_select("auteurs_rubriques.id_rubrique AS id",
		      "spip_auteurs_rubriques AS auteurs_rubriques
		        LEFT JOIN spip_rubriques AS rubriques
		          ON auteurs_rubriques.id_rubrique=rubriques.id_rubrique",
			"rubriques.id_rubrique IS NULL");

	$n+= optimiser_sansref('spip_auteurs_rubriques', 'id_rubrique', $res);

	# effacer les auteurs poubelle qui ne sont lies a aucun article
	$res = sql_select("auteurs.id_auteur AS id",
		      	"spip_auteurs AS auteurs
		      	LEFT JOIN spip_auteurs_articles AS auteurs_articles
		          ON auteurs_articles.id_auteur=auteurs.id_auteur",
			"auteurs_articles.id_auteur IS NULL
		       	AND auteurs.statut='5poubelle' AND auteurs.maj < $mydate");

	$n+= optimiser_sansref('spip_auteurs', 'id_auteur', $res);

	# supprimer les auteurs 'nouveau' qui n'ont jamais donne suite
	# au mail de confirmation (45 jours pour repondre, ca devrait suffire)
	sql_delete("spip_auteurs", "statut='nouveau' AND maj < ". sql_quote(date('Y-m-d', time()-45*24*3600)));


	//
	// Messages prives
	//

	# supprimer les messages lies a un auteur disparu
	$res = sql_select("messages.id_message AS id",
		      "spip_messages AS messages
		        LEFT JOIN spip_auteurs AS auteurs
		          ON auteurs.id_auteur=messages.id_auteur",
			"auteurs.id_auteur IS NULL");

	$n+= optimiser_sansref('spip_messages', 'id_message', $res);

	//
	// Mots-cles
	//

	$result = sql_delete("spip_mots", "titre='' AND maj < $mydate");


	# les liens mots-articles sur des mots effaces
	$res = sql_select("mots_articles.id_mot AS id",
		        "spip_mots_articles AS mots_articles
		        LEFT JOIN spip_mots AS mots
		          ON mots_articles.id_mot=mots.id_mot",
			"mots.id_mot IS NULL");

	$n+= optimiser_sansref('spip_mots_articles', 'id_mot', $res);

	# les liens mots-breves sur des mots effaces
	$res = sql_select("mots_breves.id_mot AS id",
		        "spip_mots_breves AS mots_breves
		        LEFT JOIN spip_mots AS mots
		          ON mots_breves.id_mot=mots.id_mot",
			"mots.id_mot IS NULL");

	$n+= optimiser_sansref('spip_mots_breves', 'id_mot', $res);

	# les liens mots-forum sur des mots effaces
	$res = sql_select("mots_forum.id_mot AS id",
		        "spip_mots_forum AS mots_forum
		        LEFT JOIN spip_mots AS mots
		          ON mots_forum.id_mot=mots.id_mot",
			"mots.id_mot IS NULL");

	$n+= optimiser_sansref('spip_mots_forum', 'id_mot', $res);

	# les liens mots-rubriques sur des mots effaces
	$res = sql_select("mots_rubriques.id_mot AS id",
		      "spip_mots_rubriques AS mots_rubriques
		        LEFT JOIN spip_mots AS mots
		          ON mots_rubriques.id_mot=mots.id_mot",
			"mots.id_mot IS NULL");

	$n+= optimiser_sansref('spip_mots_rubriques', 'id_mot', $res);

	# les liens mots-syndic sur des mots effaces
	$res = sql_select("mots_syndic.id_mot AS id",
		        "spip_mots_syndic AS mots_syndic
		        LEFT JOIN spip_mots AS mots
		          ON mots_syndic.id_mot=mots.id_mot",
			"mots.id_mot IS NULL");

	$n+= optimiser_sansref('spip_mots_syndic', 'id_mot', $res);


	//
	// Forums
	//

	sql_delete("spip_forum", "statut='redac' AND maj < $mydate");


	# les liens mots-forum sur des forums effaces
	$res = sql_select("mots_forum.id_forum AS id",
		        "spip_mots_forum AS mots_forum
		        LEFT JOIN spip_forum AS forum
		          ON mots_forum.id_forum=forum.id_forum",
			"forum.id_forum IS NULL");

	$n+= optimiser_sansref('spip_mots_forum', 'id_forum', $res);

	if (!$n) spip_log("Optimisation des tables: aucun lien mort");
}
?>
