<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion des optimisations de la base de données en cron
 *
 * @package SPIP\Core\Genie\Optimiser
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('base/abstract_sql');
include_spip('inc/config');

/**
 * Cron d'optimisation de la base de données
 *
 * Tache appelée régulièrement
 *
 * @param int $t
 *     Timestamp de la date de dernier appel de la tâche
 * @return int
 *     Timestamp de la date du prochain appel de la tâche
 **/
function genie_optimiser_dist($t) {

	optimiser_base_une_table();
	optimiser_base();
	optimiser_caches_contextes();

	// la date souhaitee pour le tour suivant = apres-demain a 4h du mat ;
	// sachant qu'on a un delai de 48h, on renvoie aujourd'hui a 4h du mat
	// avec une periode de flou entre 2h et 6h pour ne pas saturer un hebergeur
	// qui aurait beaucoup de sites SPIP
	return -(mktime(2, 0, 0) + random_int(0, 3600 * 4));
}

/**
 * Vider les contextes ajax de plus de 48h
 */
function optimiser_caches_contextes() {
	sous_repertoire(_DIR_CACHE, 'contextes');
	if (is_dir($d = _DIR_CACHE . 'contextes')) {
		include_spip('inc/invalideur');
		purger_repertoire($d, ['mtime' => time() - 48 * 3600, 'limit' => 10000]);
	}
}

/**
 * Optimise la base de données
 *
 * Supprime les relicats d'éléments qui ont disparu
 *
 * @note
 *     Heure de référence pour le garbage collector = 24h auparavant
 * @param int $attente
 *     Attente entre 2 exécutions de la tache en secondes
 * @return void
 **/
function optimiser_base($attente = 86400) {
	optimiser_base_disparus($attente);
}


/**
 * Lance une requête d'optimisation sur une des tables SQL de la
 * base de données.
 *
 * À chaque appel, une nouvelle table est optimisée (la suivante dans la
 * liste par rapport à la dernière fois).
 *
 * @see sql_optimize()
 *
 * @global int $GLOBALS ['meta']['optimiser_table']
 **/
function optimiser_base_une_table() {

	$tables = [];
	$result = sql_showbase();

	// on n'optimise qu'une seule table a chaque fois,
	// pour ne pas vautrer le systeme
	// lire http://dev.mysql.com/doc/refman/5.0/fr/optimize-table.html
	while ($row = sql_fetch($result)) {
		$tables[] = array_shift($row);
	}

	spip_log('optimiser_base_une_table ' . json_encode($tables, JSON_THROW_ON_ERROR), 'genie' . _LOG_DEBUG);
	if ($tables) {
		$table_op = intval(lire_config('optimiser_table', 0) + 1) % sizeof($tables);
		ecrire_config('optimiser_table', $table_op);
		$q = $tables[$table_op];
		spip_log("optimiser_base_une_table : debut d'optimisation de la table $q", 'genie' . _LOG_DEBUG);
		if (sql_optimize($q)) {
			spip_log("optimiser_base_une_table : fin d'optimisation de la table $q", 'genie' . _LOG_DEBUG);
		} else {
			spip_log("optimiser_base_une_table : Pas d'optimiseur necessaire", 'genie' . _LOG_DEBUG);
		}
	}
}


/**
 * Supprime des enregistrements d'une table SQL dont les ids à supprimer
 * se trouvent dans les résultats de ressource SQL transmise, sous la colonne 'id'
 *
 * @note
 *     Mysql < 4.0 refuse les requetes DELETE multi table
 *     et elles ont une syntaxe differente entre 4.0 et 4.1
 *     On passe donc par un SELECT puis DELETE avec IN
 *
 * @param string $table
 *     Nom de la table SQL, exemple : spip_articles
 * @param string $id
 *     Nom de la clé primaire de la table, exemple : id_article
 * @param Object $sel
 *     Ressource SQL issue d'une sélection (sql_select) et contenant une
 *     colonne 'id' ayant l'identifiant de la clé primaire à supprimer
 * @param string $and
 *     Condition AND à appliquer en plus sur la requête de suppression
 * @return int
 *     Nombre de suppressions
 **/
function optimiser_sansref($table, $id, $sel, $and = '') {
	$in = [];
	while ($row = sql_fetch($sel)) {
		$in[$row['id']] = true;
	}
	sql_free($sel);

	if ($in) {
		sql_delete($table, sql_in($id, array_keys($in)) . ($and ? " AND $and" : ''));
		spip_log("optimiser_sansref: Numeros des entrees $id supprimees dans la table $table: " . implode(', ', array_keys($in)), 'genie' . _LOG_DEBUG);
	}

	return count($in);
}


/**
 * Suppression des liens morts entre tables
 *
 * Supprime des liens morts entre tables suite à la suppression d'articles,
 * d'auteurs, etc...
 *
 * @note
 *     Maintenant que MySQL 5 a des Cascades on pourrait faire autrement
 *     mais on garde la compatibilité avec les versions précédentes.
 *
 * @pipeline_appel optimiser_base_disparus
 *
 * @param int $attente
 *     Attente entre 2 exécutions de la tache en secondes
 * @return void
 **/
function optimiser_base_disparus($attente = 86400) {

	# format = 20060610110141, si on veut forcer une optimisation tout de suite
	$mydate = date('Y-m-d H:i:s', time() - $attente);
	$mydate_quote = sql_quote($mydate);

	$n = 0;

	//
	// Rubriques
	//

	# les articles qui sont dans une id_rubrique inexistante
	# attention on controle id_rubrique>0 pour ne pas tuer les articles
	# specialement affectes a une rubrique non-existante (plugin,
	# cf. https://core.spip.net/issues/1549 )
	$res = sql_select(
		'A.id_article AS id',
		'spip_articles AS A
		        LEFT JOIN spip_rubriques AS R
		          ON A.id_rubrique=R.id_rubrique',
		"A.id_rubrique > 0
			 AND R.id_rubrique IS NULL
		         AND A.maj < $mydate_quote"
	);

	$n += optimiser_sansref('spip_articles', 'id_article', $res);

	// les articles a la poubelle
	sql_delete('spip_articles', "statut='poubelle' AND maj < $mydate_quote");

	//
	// Auteurs
	//

	include_spip('action/editer_liens');
	// optimiser les liens de tous les auteurs vers des objets effaces
	// et depuis des auteurs effaces
	$n += objet_optimiser_liens(['auteur' => '*'], '*');

	# effacer les auteurs poubelle qui ne sont lies a rien
	$res = sql_select(
		'A.id_auteur AS id',
		'spip_auteurs AS A
		      	LEFT JOIN spip_auteurs_liens AS L
		          ON L.id_auteur=A.id_auteur',
		"L.id_auteur IS NULL
		       	AND A.statut='5poubelle' AND A.maj < $mydate_quote"
	);

	$n += optimiser_sansref('spip_auteurs', 'id_auteur', $res);

	# supprimer les auteurs 'nouveau' qui n'ont jamais donne suite
	# au mail de confirmation (45 jours pour repondre, ca devrait suffire)
	if (!defined('_AUTEURS_DELAI_REJET_NOUVEAU')) {
		define('_AUTEURS_DELAI_REJET_NOUVEAU', 45 * 24 * 3600);
	}
	sql_delete('spip_auteurs', "statut='nouveau' AND maj < " . sql_quote(date('Y-m-d', time() - intval(_AUTEURS_DELAI_REJET_NOUVEAU))));

	/**
	 * Permet aux plugins de compléter l'optimisation suite aux éléments disparus
	 *
	 * L'index 'data' est un entier indiquant le nombre d'optimisations
	 * qui ont été réalisées (par exemple le nombre de suppressions faites)
	 * et qui doit être incrémenté par les fonctions
	 * utilisant ce pipeline si elles suppriment des éléments.
	 *
	 * @pipeline_appel optimiser_base_disparus
	 */
	$n = pipeline('optimiser_base_disparus', [
		'args' => [
			'attente' => $attente,
			'date' => $mydate
		],
		'data' => $n
	]);


	spip_log("optimiser_base_disparus : {$n} lien(s) mort(s)", 'genie' . _LOG_DEBUG);
}
