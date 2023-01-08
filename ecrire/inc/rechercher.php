<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Gestion des recherches
 *
 * @package SPIP\Core\Recherche
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

defined('_RECHERCHE_LOCK_KEY') || define('_RECHERCHE_LOCK_KEY', 'fulltext');

/**
 * Donne la liste des champs/tables où l'on sait chercher / remplacer
 * avec un poids pour le score
 *
 * Utilise l'information `rechercher_champs` sur la déclaration
 * des objets éditoriaux.
 *
 * @pipeline_appel rechercher_liste_des_champs
 * @uses lister_tables_objets_sql()
 *
 * @return array Couples (type d'objet => Couples (champ => score))
 */
function liste_des_champs() {
	static $liste = null;
	if (is_null($liste)) {
		$liste = [];
		// recuperer les tables_objets_sql declarees
		include_spip('base/objets');
		$tables_objets = lister_tables_objets_sql();
		foreach ($tables_objets as $t => $infos) {
			if ($infos['rechercher_champs']) {
				$liste[$infos['type']] = $infos['rechercher_champs'];
			}
		}
		// puis passer dans le pipeline
		$liste = pipeline('rechercher_liste_des_champs', $liste);
	}

	return $liste;
}


// Recherche des auteurs et mots-cles associes
// en ne regardant que le titre ou le nom
function liste_des_jointures() {
	static $liste = null;
	if (is_null($liste)) {
		$liste = [];
		// recuperer les tables_objets_sql declarees
		include_spip('base/objets');
		$tables_objets = lister_tables_objets_sql();
		foreach ($tables_objets as $t => $infos) {
			if ($infos['rechercher_jointures']) {
				$liste[$infos['type']] = $infos['rechercher_jointures'];
			}
		}
		// puis passer dans le pipeline
		$liste = pipeline('rechercher_liste_des_jointures', $liste);
	}

	return $liste;
}

function expression_recherche($recherche, $options) {
	// ne calculer qu'une seule fois l'expression par hit
	// (meme si utilisee dans plusieurs boucles)
	static $expression = [];
	$key = serialize([$recherche, $options['preg_flags']]);
	if (isset($expression[$key])) {
		return $expression[$key];
	}

	$u = $GLOBALS['meta']['pcre_u'];
	if ($u and !str_contains($options['preg_flags'], (string) $u)) {
		$options['preg_flags'] .= $u;
	}
	include_spip('inc/charsets');
	$recherche = trim($recherche);

	// retirer les + de +truc et les * de truc*
	$recherche = preg_replace(',(^|\s)\+(\w),Uims', '$1$2', $recherche);
	$recherche = preg_replace(',(\w)\*($|\s),Uims', '$1$2', $recherche);

	$is_preg = false;
	if (substr($recherche, 0, 1) == '/' and substr($recherche, -1, 1) == '/' and strlen($recherche) > 2) {
		// c'est une preg
		$recherche_trans = translitteration($recherche);
		$preg = $recherche_trans . $options['preg_flags'];
		$is_preg = true;
	} else {
		// s'il y a plusieurs mots il faut les chercher tous : oblige REGEXP,
		// sauf ceux de moins de 4 lettres (on supprime ainsi 'le', 'les', 'un',
		// 'une', 'des' ...)

		// attention : plusieurs mots entre guillemets sont a rechercher tels quels
		$recherche_trans = $recherche_mod = $recherche_org = $recherche;

		// les expressions entre " " sont un mot a chercher tel quel
		// -> on remplace les espaces par un \x1 et on enleve les guillemets
		if (preg_match(',["][^"]+["],Uims', $recherche_mod, $matches)) {
			foreach ($matches as $match) {
				$word = preg_replace(',\s+,Uims', "\x1", $match);
				$word = trim($word, '"');
				$recherche_mod = str_replace($match, $word, $recherche_mod);
			}
		}

		if (preg_match(',\s+,' . $u, $recherche_mod)) {
			$is_preg = true;

			$recherche_inter = '|';
			$recherche_mots = explode(' ', $recherche_mod);
			$min_long = defined('_RECHERCHE_MIN_CAR') ? _RECHERCHE_MIN_CAR : 4;
			$petits_mots = true;
			foreach ($recherche_mots as $mot) {
				if (strlen($mot) >= $min_long) {
					// echapper les caracteres de regexp qui sont eventuellement dans la recherche
					$recherche_inter .= preg_quote($mot) . ' ';
					$petits_mots = false;
				}
			}
			$recherche_inter = str_replace("\x1", '\s', $recherche_inter);

			// mais on cherche quand même l'expression complète, même si elle
			// comporte des mots de moins de quatre lettres
			$recherche = trim(preg_replace(',\s+,' . $u, '|', $recherche_inter), '|');
			if (!$recherche or $petits_mots) {
				$recherche = preg_quote($recherche_org);
			}
			$recherche_trans = translitteration($recherche);
		}

		$preg = '/' . str_replace('/', '\\/', $recherche_trans) . '/' . $options['preg_flags'];
	}

	// Si la chaine est inactive, on va utiliser LIKE pour aller plus vite
	// ou si l'expression reguliere est invalide
	if (
		!$is_preg
		or (@preg_match($preg, '') === false)
	) {
		$methode = 'LIKE';
		$u = $GLOBALS['meta']['pcre_u'];

		// echapper les % et _
		$q = str_replace(['%', '_'], ['\%', '\_'], trim($recherche));

		// eviter les parentheses et autres caractères qui interferent avec pcre par la suite (dans le preg_match_all) s'il y a des reponses
		$recherche = preg_quote($recherche, '/');
		$recherche_trans = translitteration($recherche);
		$recherche_mod = $recherche_trans;

		// les expressions entre " " sont un mot a chercher tel quel
		// -> on remplace les espaces par un _ et on enleve les guillemets
		// corriger le like dans le $q
		if (preg_match(',["][^"]+["],Uims', $q, $matches)) {
			foreach ($matches as $match) {
				$word = preg_replace(',\s+,Uims', '_', $match);
				$word = trim($word, '"');
				$q = str_replace($match, $word, $q);
			}
		}
		// corriger la regexp
		if (preg_match(',["][^"]+["],Uims', $recherche_mod, $matches)) {
			foreach ($matches as $match) {
				$word = preg_replace(',\s+,Uims', '[\s]', $match);
				$word = trim($word, '"');
				$recherche_mod = str_replace($match, $word, $recherche_mod);
			}
		}
		$q = sql_quote(
			'%'
			. preg_replace(',\s+,' . $u, '%', $q)
			. '%'
		);

		$preg = '/' . preg_replace(',\s+,' . $u, '.+', trim($recherche_mod)) . '/' . $options['preg_flags'];
	} else {
		$methode = 'REGEXP';
		$q = sql_quote(trim($recherche, '/'));
	}

	// tous les caracteres transliterables de $q sont remplaces par un joker
	// permet de matcher en SQL meme si on est sensible aux accents (SQLite)
	$q_t = $q;
	for ($i = 0; $i < spip_strlen($q); $i++) {
		$char = spip_substr($q, $i, 1);
		if (
			!is_ascii($char)
			and $char_t = translitteration($char)
			and $char_t !== $char
		) {
			// on utilise ..?.? car le char utf peut etre encode sur 1, 2 ou 3 bytes
			// mais c'est un pis aller cf #4354
			$q_t = str_replace($char, $is_preg ? '..?.?' : '_', $q_t);
		}
	}

	$q = $q_t;

	// fix : SQLite 3 est sensible aux accents, on jokerise les caracteres
	// les plus frequents qui peuvent etre accentues
	// (oui c'est tres dicustable...)
	if (
		isset($GLOBALS['connexions'][$options['serveur'] ?: 0]['type'])
		and strncmp($GLOBALS['connexions'][$options['serveur'] ?: 0]['type'], 'sqlite', 6) == 0
	) {
		$q_t = strtr($q, 'aeuioc', $is_preg ? '......' : '______');
		// si il reste au moins un char significatif...
		if (preg_match(",[^'%_.],", $q_t)) {
			$q = $q_t;
		}
	}

	return $expression[$key] = [$methode, $q, $preg];
}



/**
 * Effectue une recherche sur toutes les tables de la base de données
 *
 * @uses liste_des_champs()
 * @uses inc_recherche_to_array_dist()
 *
 * @param string $recherche
 *     Le terme de recherche
 * @param null|array|string $tables
 *     - null : toutes les tables acceptant des recherches
 *     - array : liste des tables souhaitées
 *     - string : une chaîne listant les tables souhaitées, séparées par des virgules (préférer array cependant)
 * @param array $options {
 *     @var $toutvoir pour éviter autoriser(voir)
 *     @var $flags pour éviter les flags regexp par défaut (UimsS)
 *     @var $champs pour retourner les champs concernés
 *     @var $score pour retourner un score
 * }
 * @param string $serveur
 * @return array
 */
function recherche_en_base($recherche = '', $tables = null, $options = [], $serveur = '') {
	include_spip('base/abstract_sql');

	if (!is_array($tables)) {
		$liste = liste_des_champs();

		if (
			is_string($tables)
			and $tables != ''
		) {
			$toutes = [];
			foreach (explode(',', $tables) as $t) {
				$t = trim($t);
				if (isset($liste[$t])) {
					$toutes[$t] = $liste[$t];
				}
			}
			$tables = $toutes;
			unset($toutes);
		} else {
			$tables = $liste;
		}
	}

	if (!strlen($recherche) or !count($tables)) {
		return [];
	}

	include_spip('inc/autoriser');

	// options par defaut
	$options = array_merge(
		[
		'preg_flags' => 'UimsS',
		'toutvoir' => false,
		'champs' => false,
		'score' => false,
		'matches' => false,
		'jointures' => false,
		'serveur' => $serveur
		],
		$options
	);

	$results = [];

	// Utiliser l'iterateur (DATA:recherche)
	// pour recuperer les couples (id_objet, score)
	// Le resultat est au format {
	//      id1 = { 'score' => x, attrs => { } },
	//      id2 = { 'score' => x, attrs => { } },
	// }

	include_spip('inc/recherche_to_array');

	foreach ($tables as $table => $champs) {
		# lock via memoization, si dispo
		if (function_exists('cache_lock')) {
			cache_lock($lock = _RECHERCHE_LOCK_KEY . ' ' . $table . ' ' . $recherche);
		}

		spip_timer('rech');

		# TODO : ici plutot charger un iterateur via l'API iterateurs
		$to_array = charger_fonction('recherche_to_array', 'inc');
		$results[$table] = $to_array(
			$recherche,
			array_merge($options, ['table' => $table, 'champs' => $champs])
		);
		##var_dump($results[$table]);


		spip_log(
			"recherche $table ($recherche) : " . (is_countable($results[$table]) ? count($results[$table]) : 0) . ' resultats ' . spip_timer('rech'),
			'recherche'
		);

		if (isset($lock)) {
			cache_unlock($lock);
		}
	}

	return $results;
}


// Effectue une recherche sur toutes les tables de la base de donnees
function remplace_en_base($recherche = '', $remplace = null, $tables = null, $options = []) {
	include_spip('inc/modifier');

	// options par defaut
	$options = array_merge(
		[
		'preg_flags' => 'UimsS',
		'toutmodifier' => false
		],
		$options
	);
	$options['champs'] = true;


	if (!is_array($tables)) {
		$tables = liste_des_champs();
	}

	$results = recherche_en_base($recherche, $tables, $options);

	$preg = '/' . str_replace('/', '\\/', $recherche) . '/' . $options['preg_flags'];

	foreach ($results as $table => $r) {
		$_id_table = id_table_objet($table);
		foreach ($r as $id => $x) {
			if (
				$options['toutmodifier']
				or autoriser('modifier', $table, $id)
			) {
				$modifs = [];
				foreach ($x['champs'] as $key => $val) {
					if ($key == $_id_table) {
						continue;
					}
					$repl = preg_replace($preg, $remplace, $val);
					if ($repl <> $val) {
						$modifs[$key] = $repl;
					}
				}
				if ($modifs) {
					objet_modifier_champs(
						$table,
						$id,
						[
							'champs' => array_keys($modifs),
						],
						$modifs
					);
				}
			}
		}
	}
}
