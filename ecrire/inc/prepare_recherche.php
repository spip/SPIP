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
 * Gestion des préparatifs de recherches
 *
 * @package SPIP\Core\Recherche
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/rechercher');
if (!defined('_DELAI_CACHE_resultats')) {
	define('_DELAI_CACHE_resultats', 600);
}

/**
 * Préparer les listes `id_article IN (...)` pour les parties WHERE
 * et calcul des `points` pour la partie SELECT des requêtes du moteur de recherche
 *
 * Le paramètre $serveur est utilisé pour savoir sur quelle base on cherche
 * mais l'index des résultats est toujours stocké sur le serveur principal
 * car on ne sait pas si la base distante dispose d'une table spip_resultats
 * ni meme si on aurait le droit d'ecrire dedans
 *
 * @param string $recherche
 *    chaine recherchee
 * @param string $table
 *    table dans laquelle porte la recherche
 * @param bool $cond
 *    critere conditionnel sur {recherche?}
 * @param string $serveur
 *    serveur de base de donnees
 * @param array $modificateurs
 *    modificateurs de boucle, ie liste des criteres presents
 * @param string $primary
 *    cle primaire de la table de recherche
 * @return array
 */
function inc_prepare_recherche_dist(
	$recherche,
	$table = 'articles',
	$cond = false,
	$serveur = '',
	$modificateurs = [],
	$primary = ''
) {
	static $cache = [];
	$delai_fraicheur = min(
		_DELAI_CACHE_resultats,
		time() - (isset($GLOBALS['meta']['derniere_modif']) ? $GLOBALS['meta']['derniere_modif'] : 0)
	);

	// si recherche n'est pas dans le contexte, on va prendre en globals
	// ca permet de faire des inclure simple.
	if (!isset($recherche) and isset($GLOBALS['recherche'])) {
		$recherche = $GLOBALS['recherche'];
	}

	// traiter le cas {recherche?}
	if ($cond and !strlen($recherche)) {
		return [
			'0 as points' /* as points */, /* where */
			''
		];
	}


	$rechercher = false;

	$where_resultat_recent = sql_date_proche('maj', (0 - ($delai_fraicheur + 100)), ' SECOND');
	if (!isset($cache[$serveur][$table][$recherche])) {
		$hash_serv = ($serveur ? substr(md5($serveur), 0, 16) : '');
		$hash = substr(md5($recherche . $table), 0, 16);
		$where = "(resultats.recherche='$hash' AND resultats.table_objet=" . sql_quote($table) . " AND resultats.serveur='$hash_serv')";
		$row = sql_fetsel(
			'recherche',
			'spip_resultats AS resultats',
			$where . " AND $where_resultat_recent",
			'',
			'',
			'0,1'
		);
		if (
			!$row
			or (defined('_VAR_MODE') and _VAR_MODE == 'recalcul')
		) {
			$rechercher = true;
		}
	}

	// si on n'a pas encore traite les donnees dans une boucle precedente
	if ($rechercher) {
		//$tables = liste_des_champs();
		$x = objet_type($table);
		$points = recherche_en_base(
			$recherche,
			$x,
			[
				'score' => true,
				'toutvoir' => true,
				'jointures' => true
			],
			$serveur
		);
		// pas de résultat, pas de point
		$points = isset($points[$x]) ? $points[$x] : [];

		// permettre aux plugins de modifier le resultat
		$points = pipeline('prepare_recherche', [
			'args' => [
				'type' => $x,
				'recherche' => $recherche,
				'serveur' => $serveur,
				'modificateurs' => $modificateurs
			],
			'data' => $points
		]);

		// supprimer les anciens resultats de cette recherche
		// et les resultats trop vieux avec une marge
		// pas de AS resultats dans un delete (mysql)
		$whered = str_replace(
			['resultats.recherche', 'resultats.table_objet', 'resultats.serveur'],
			['recherche', 'table_objet', 'serveur'],
			$where
		);

		sql_delete(
			'spip_resultats',
			"NOT($where_resultat_recent) OR ($whered)"
		);

		// inserer les resultats dans la table de cache des resultats
		if (count($points)) {
			$tab_couples = [];
			foreach ($points as $id => $p) {
				$tab_couples[] = [
					'recherche' => $hash,
					'id' => $id,
					'points' => $p['score'],
					'table_objet' => $table,
					'serveur' => $hash_serv,
				];
			}
			sql_insertq_multi('spip_resultats', $tab_couples, []);
		}
	}

	if (!isset($cache[$serveur][$table][$recherche])) {
		if (!$serveur) {
			$cache[$serveur][$table][$recherche] = ['resultats.points AS points', $where];
		} else {
			if (sql_countsel('spip_resultats as resultats', $where)) {
				$rows = sql_allfetsel('resultats.id,resultats.points', 'spip_resultats as resultats', $where);
			}
			$cache[$serveur][$table][$recherche] = generer_select_where_explicites($table, $primary, $rows, $serveur);
		}
	}

	return $cache[$serveur][$table][$recherche];
}


/**
 * Generer le select et where qui contiennent explicitement
 * les id et points (ie comme dans SPIP 1.9.x)
 * quand on fait une recherche sur une table externe
 *
 * @param string $table
 * @param string $primary
 * @param array $rows
 * @param string $serveur
 * @return array
 */
function generer_select_where_explicites($table, $primary, $rows, $serveur) {
	# calculer le {id_article IN()} et le {... as points}
	if (!count($rows)) {
		return ["''", '0=1'];
	} else {
		$listes_ids = [];
		$select = '0';
		foreach ($rows as $r) {
			$listes_ids[$r['points']][] = $r['id'];
		}

		foreach ($listes_ids as $p => $ids) {
			$select .= "+$p*(" .
				sql_in("$table.$primary", $ids, '', $serveur)
				. ') ';
		}

		return ["$select AS points ", sql_in("$table.$primary", array_map('reset', $rows), '', $serveur)];
	}
}
