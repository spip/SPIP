<?php

/* *************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

use Spip\Sql\Sqlite;

/**
 * Ce fichier contient les fonctions gérant
 * les instructions SQL pour Sqlite
 *
 * @package SPIP\Core\SQL\SQLite
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// TODO: get/set_caracteres ?

/*
 * regroupe le maximum de fonctions qui peuvent cohabiter
 * D'abord les fonctions d'abstractions de SPIP
 */

/**
 * Connecteur à une base SQLite
 *
 * @param string $addr
 * @param int $port
 * @param string $login
 * @param string $pass
 * @param string $db
 * @param string $prefixe
 * @param string $sqlite_version
 * @return array|bool
 */
function req_sqlite_dist($addr, $port, $login, $pass, $db = '', $prefixe = '', $sqlite_version = '')
{
	static $last_connect = [];

	// si provient de selectdb
	// un code pour etre sur que l'on vient de select_db()
	if (strpos($db, $code = '@selectdb@') !== false) {
		foreach (['addr', 'port', 'login', 'pass', 'prefixe'] as $a) {
			$$a = $last_connect[$a];
		}
		$db = str_replace($code, '', $db);
	}

	/*
	 * En sqlite, seule l'adresse du fichier est importante.
	 * Ce sera $db le nom,
	 * le path est $addr
	 * (_DIR_DB si $addr est vide)
	 */
	_sqlite_init();

	// determiner le dossier de la base : $addr ou _DIR_DB
	$f = _DIR_DB;
	if ($addr and str_contains($addr, '/')) {
		$f = rtrim($addr, '/') . '/';
	}

	// un nom de base demande et impossible d'obtenir la base, on s'en va :
	// il faut que la base existe ou que le repertoire parent soit writable
	if ($db and !is_file($f .= $db . '.sqlite') and !is_writable(dirname($f))) {
		spip_log("base $f non trouvee ou droits en ecriture manquants", 'sqlite.' . _LOG_HS);

		return false;
	}

	// charger les modules sqlite au besoin
	if (!_sqlite_charger_version($sqlite_version)) {
		spip_log("Impossible de trouver/charger le module SQLite ($sqlite_version)!", 'sqlite.' . _LOG_HS);

		return false;
	}

	// chargement des constantes
	// il ne faut pas definir les constantes avant d'avoir charge les modules sqlite
	$define = 'spip_sqlite' . $sqlite_version . '_constantes';
	$define();

	$ok = false;
	if (!$db) {
		// si pas de db ->
		// base temporaire tant qu'on ne connait pas son vrai nom
		// pour tester la connexion
		$db = '_sqlite' . $sqlite_version . '_install';
		$tmp = _DIR_DB . $db . '.sqlite';
		$ok = $link = new \PDO("sqlite:$tmp");
	} else {
		// Ouvrir (eventuellement creer la base)
		$ok = $link = new \PDO("sqlite:$f");
	}

	if (!$ok) {
		$e = _sqlite_last_error_from_link($link);
		spip_log("Impossible d'ouvrir la base SQLite($sqlite_version) $f : $e", 'sqlite.' . _LOG_HS);

		return false;
	}

	if ($link) {
		$last_connect = [
			'addr' => $addr,
			'port' => $port,
			'login' => $login,
			'pass' => $pass,
			'db' => $db,
			'prefixe' => $prefixe,
		];
		// etre sur qu'on definit bien les fonctions a chaque nouvelle connexion
		include_spip('req/sqlite_fonctions');
		_sqlite_init_functions($link);
	}

	return [
		'db' => $db,
		'prefixe' => $prefixe ? $prefixe : $db,
		'link' => $link,
		'total_requetes' => 0,
	];
}


/**
 * Fonction de requete generale, munie d'une trace a la demande
 *
 * @param string $query
 *    Requete a executer
 * @param string $serveur
 *    Nom du connecteur
 * @param bool $requeter
 *    Effectuer la requete ?
 *    - true pour executer
 *    - false pour retourner le texte de la requete
 * @return PDOStatement|bool|string|array
 *    Resultat de la requete
 */
function spip_sqlite_query($query, $serveur = '', $requeter = true)
{
	#spip_log("spip_sqlite_query() > $query",'sqlite.'._LOG_DEBUG);
	#_sqlite_init(); // fait la premiere fois dans spip_sqlite
	$query = Sqlite::traduire_requete($query, $serveur);
	if (!$requeter) {
		return $query;
	}

	return Sqlite::executer_requete($query, $serveur);
}


/* ordre alphabetique pour les autres */

/**
 * Modifie une structure de table SQLite
 *
 * @param string $query Requête SQL (sans 'ALTER ')
 * @param string $serveur Nom de la connexion
 * @param bool $requeter inutilisé
 * @return bool
 *     False si erreur dans l'exécution, true sinon
 */
function spip_sqlite_alter($query, $serveur = '', $requeter = true)
{

	$query = spip_sqlite_query("ALTER $query", $serveur, false);
	// traduire la requete pour recuperer les bons noms de table
	$query = Sqlite::traduire_requete($query, $serveur);

	/*
		 * la il faut faire les transformations
		 * si ALTER TABLE x (DROP|CHANGE) y
		 *
		 * 1) recuperer "ALTER TABLE table "
		 * 2) spliter les sous requetes (,)
		 * 3) faire chaque requete independemment
		 */

	// 1
	if (preg_match('/\s*(ALTER(\s*IGNORE)?\s*TABLE\s*([^\s]*))\s*(.*)?/is', $query, $regs)) {
		$debut = $regs[1];
		$table = $regs[3];
		$suite = $regs[4];
	} else {
		spip_log("SQLite : Probleme de ALTER TABLE mal forme dans $query", 'sqlite.' . _LOG_ERREUR);

		return false;
	}

	// 2
	// il faudrait une regexp pour eviter de spliter ADD PRIMARY KEY (colA, colB)
	// tout en cassant "ADD PRIMARY KEY (colA, colB), ADD INDEX (chose)"... en deux
	// ou revoir l'api de sql_alter en creant un
	// sql_alter_table($table,array($actions));
	$todo = explode(',', $suite);

	// on remet les morceaux dechires ensembles... que c'est laid !
	$todo2 = [];
	$i = 0;
	$ouverte = false;
	while ($do = array_shift($todo)) {
		$todo2[$i] = isset($todo2[$i]) ? $todo2[$i] . ',' . $do : $do;
		$o = (str_contains($do, '('));
		$f = (str_contains($do, ')'));
		if ($o and !$f) {
			$ouverte = true;
		} elseif ($f) {
			$ouverte = false;
		}
		if (!$ouverte) {
			$i++;
		}
	}

	// 3
	$resultats = [];
	foreach ($todo2 as $do) {
		$do = trim($do);
		if (
			!preg_match('/(DROP PRIMARY KEY|DROP KEY|DROP INDEX|DROP COLUMN|DROP'
				. '|CHANGE COLUMN|CHANGE|MODIFY|RENAME TO|RENAME'
				. '|ADD PRIMARY KEY|ADD KEY|ADD INDEX|ADD UNIQUE KEY|ADD UNIQUE'
				. '|ADD COLUMN|ADD'
				. ')\s*([^\s]*)\s*(.*)?/i', $do, $matches)
		) {
			spip_log(
				"SQLite : Probleme de ALTER TABLE, utilisation non reconnue dans : $do \n(requete d'origine : $query)",
				'sqlite.' . _LOG_ERREUR
			);

			return false;
		}

		$cle = strtoupper($matches[1]);
		$colonne_origine = $matches[2];
		$colonne_destination = '';

		$def = $matches[3];

		// eluder une eventuelle clause before|after|first inutilisable
		$defr = rtrim(preg_replace('/(BEFORE|AFTER|FIRST)(.*)$/is', '', $def));
		$defo = $defr; // garder la def d'origine pour certains cas
		// remplacer les definitions venant de mysql
		$defr = _sqlite_remplacements_definitions_table($defr);

		// reinjecter dans le do
		$do = str_replace($def, $defr, $do);
		$def = $defr;

		switch ($cle) {
				// suppression d'un index
			case 'DROP KEY':
			case 'DROP INDEX':
				$nom_index = $colonne_origine;
				spip_sqlite_drop_index($nom_index, $table, $serveur);
				break;

				// suppression d'une pk
			case 'DROP PRIMARY KEY':
				if (
					!_sqlite_modifier_table(
						$table,
						$colonne_origine,
						['key' => ['PRIMARY KEY' => '']],
						$serveur
					)
				) {
					return false;
				}
				break;
				// suppression d'une colonne
			case 'DROP COLUMN':
			case 'DROP':
				if (
					!_sqlite_modifier_table(
						$table,
						[$colonne_origine => ''],
						[],
						$serveur
					)
				) {
					return false;
				}
				break;

			case 'CHANGE COLUMN':
			case 'CHANGE':
				// recuperer le nom de la future colonne
				// on reprend la def d'origine car _sqlite_modifier_table va refaire la translation
				// en tenant compte de la cle primaire (ce qui est mieux)
				$def = trim($defo);
				$colonne_destination = substr($def, 0, strpos($def, ' '));
				$def = substr($def, strlen($colonne_destination) + 1);

				if (
					!_sqlite_modifier_table(
						$table,
						[$colonne_origine => $colonne_destination],
						['field' => [$colonne_destination => $def]],
						$serveur
					)
				) {
					return false;
				}
				break;

			case 'MODIFY':
				// on reprend la def d'origine car _sqlite_modifier_table va refaire la translation
				// en tenant compte de la cle primaire (ce qui est mieux)
				if (
					!_sqlite_modifier_table(
						$table,
						$colonne_origine,
						['field' => [$colonne_origine => $defo]],
						$serveur
					)
				) {
					return false;
				}
				break;

				// pas geres en sqlite2
			case 'RENAME':
				$do = 'RENAME TO' . substr($do, 6);
			case 'RENAME TO':
				if (!Sqlite::executer_requete("$debut $do", $serveur)) {
					spip_log("SQLite : Erreur ALTER TABLE / RENAME : $query", 'sqlite.' . _LOG_ERREUR);

					return false;
				}
				break;

				// ajout d'une pk
			case 'ADD PRIMARY KEY':
				$pk = trim(substr($do, 16));
				$pk = ($pk[0] == '(') ? substr($pk, 1, -1) : $pk;
				if (
					!_sqlite_modifier_table(
						$table,
						$colonne_origine,
						['key' => ['PRIMARY KEY' => $pk]],
						$serveur
					)
				) {
					return false;
				}
				break;
				// ajout d'un index
			case 'ADD UNIQUE KEY':
			case 'ADD UNIQUE':
				$unique = true;
			case 'ADD INDEX':
			case 'ADD KEY':
				if (!isset($unique)) {
					$unique = false;
				}
				// peut etre "(colonne)" ou "nom_index (colonnes)"
				// bug potentiel si qqn met "(colonne, colonne)"
				//
				// nom_index (colonnes)
				if ($def) {
					$colonnes = substr($def, 1, -1);
					$nom_index = $colonne_origine;
				} else {
					// (colonne)
					if ($colonne_origine[0] == '(') {
						$colonnes = substr($colonne_origine, 1, -1);
						if (str_contains(',', $colonnes)) {
							spip_log('SQLite : Erreur, impossible de creer un index sur plusieurs colonnes'
								. " sans qu'il ait de nom ($table, ($colonnes))", 'sqlite.' . _LOG_ERREUR);
							break;
						} else {
							$nom_index = $colonnes;
						}
					} // nom_index
					else {
						$nom_index = $colonnes = $colonne_origine;
					}
				}
				spip_sqlite_create_index($nom_index, $table, $colonnes, $unique, $serveur);
				break;

				// pas geres en sqlite2
			case 'ADD COLUMN':
				$do = 'ADD' . substr($do, 10);
			case 'ADD':
			default:
				if (!preg_match(',primary\s+key,i', $do)) {
					if (!Sqlite::executer_requete("$debut $do", $serveur)) {
						spip_log("SQLite : Erreur ALTER TABLE / ADD : $query", 'sqlite.' . _LOG_ERREUR);

						return false;
					}
					break;
				}
				// ou si la colonne est aussi primary key
				// cas du add id_truc int primary key
				// ajout d'une colonne qui passe en primary key directe
				else {
					$def = trim(substr($do, 3));
					$colonne_ajoutee = substr($def, 0, strpos($def, ' '));
					$def = substr($def, strlen($colonne_ajoutee) + 1);
					$opts = [];
					if (preg_match(',primary\s+key,i', $def)) {
						$opts['key'] = ['PRIMARY KEY' => $colonne_ajoutee];
						$def = preg_replace(',primary\s+key,i', '', $def);
					}
					$opts['field'] = [$colonne_ajoutee => $def];
					if (!_sqlite_modifier_table($table, [$colonne_ajoutee], $opts, $serveur)) {
						spip_log("SQLite : Erreur ALTER TABLE / ADD : $query", 'sqlite.' . _LOG_ERREUR);

						return false;
					}
				}
				break;
		}
		// tout est bon, ouf !
		spip_log("SQLite ($serveur) : Changements OK : $debut $do", 'sqlite.' . _LOG_INFO);
	}

	spip_log("SQLite ($serveur) : fin ALTER TABLE OK !", 'sqlite.' . _LOG_INFO);

	return true;
}

/**
 * Crée une table SQL
 *
 * Crée une table SQL nommee `$nom` à partir des 2 tableaux `$champs` et `$cles`
 *
 * @note Le nom des caches doit être inferieur à 64 caractères
 *
 * @param string $nom Nom de la table SQL
 * @param array $champs Couples (champ => description SQL)
 * @param array $cles Couples (type de clé => champ(s) de la clé)
 * @param bool $autoinc True pour ajouter un auto-incrément sur la Primary Key
 * @param bool $temporary True pour créer une table temporaire
 * @param string $serveur Nom de la connexion
 * @param bool $requeter Exécuter la requête, sinon la retourner
 * @return array|null|resource|string
 *     - string texte de la requête si demandée
 *     - true si la requête réussie, false sinon.
 */
function spip_sqlite_create(
	$nom,
	$champs,
	$cles,
	$autoinc = false,
	$temporary = false,
	$serveur = '',
	$requeter = true
) {
	$query = _sqlite_requete_create($nom, $champs, $cles, $autoinc, $temporary, $ifnotexists = true, $serveur, $requeter);
	if (!$query) {
		return false;
	}
	$res = spip_sqlite_query($query, $serveur, $requeter);

	// SQLite ne cree pas les KEY sur les requetes CREATE TABLE
	// il faut donc les faire creer ensuite
	if (!$requeter) {
		return $res;
	}

	$ok = $res ? true : false;
	if ($ok) {
		foreach ($cles as $k => $v) {
			if (preg_match(',^(UNIQUE KEY|KEY|UNIQUE)\s,i', $k, $m)) {
				$index = trim(substr($k, strlen($m[1])));
				$unique = (strlen($m[1]) > 3);
				$ok &= spip_sqlite_create_index($index, $nom, $v, $unique, $serveur);
			}
		}
	}

	return $ok ? true : false;
}

/**
 * Crée une base de données SQLite
 *
 * @param string $nom Nom de la base (sans l'extension de fichier)
 * @param string $serveur Nom de la connexion
 * @param string $option Options
 *
 * @return bool true si la base est créee.
 **/
function spip_sqlite_create_base($nom, $serveur = '', $option = true)
{
	$f = $nom . '.sqlite';
	if (strpos($nom, '/') === false) {
		$f = _DIR_DB . $f;
	}

	$ok = new \PDO("sqlite:$f");

	if ($ok) {
		unset($ok);

		return true;
	}
	unset($ok);

	return false;
}


/**
 * Crée une vue SQL nommée `$nom`
 *
 * @param string $nom
 *    Nom de la vue a creer
 * @param string $query_select
 *     texte de la requête de sélection servant de base à la vue
 * @param string $serveur
 *     Nom du connecteur
 * @param bool $requeter
 *     Effectuer la requete, sinon la retourner
 * @return bool|string
 *     - true si la vue est créée
 *     - false si erreur ou si la vue existe déja
 *     - string texte de la requête si $requeter vaut false
 */
function spip_sqlite_create_view($nom, $query_select, $serveur = '', $requeter = true)
{
	if (!$query_select) {
		return false;
	}
	// vue deja presente
	if (sql_showtable($nom, false, $serveur)) {
		spip_log(
			"Echec creation d'une vue sql ($nom) car celle-ci existe deja (serveur:$serveur)",
			'sqlite.' . _LOG_ERREUR
		);

		return false;
	}

	$query = "CREATE VIEW $nom AS " . $query_select;

	return spip_sqlite_query($query, $serveur, $requeter);
}

/**
 * Fonction de création d'un INDEX
 *
 * @param string $nom
 *     Nom de l'index
 * @param string $table
 *     Table SQL de l'index
 * @param string|array $champs
 *     Liste de champs sur lesquels s'applique l'index
 * @param string|bool $unique
 *     Créer un index UNIQUE ?
 * @param string $serveur
 *     Nom de la connexion sql utilisee
 * @param bool $requeter
 *     true pour executer la requête ou false pour retourner le texte de la requête
 * @return bool|string
 *    string : requête, false si erreur, true sinon.
 */
function spip_sqlite_create_index($nom, $table, $champs, $unique = '', $serveur = '', $requeter = true)
{
	if (!($nom or $table or $champs)) {
		spip_log(
			"Champ manquant pour creer un index sqlite ($nom, $table, (" . join(',', $champs) . '))',
			'sqlite.' . _LOG_ERREUR
		);

		return false;
	}

	// SQLite ne differentie pas noms des index en fonction des tables
	// il faut donc creer des noms uniques d'index pour une base sqlite
	$nom = $table . '_' . $nom;
	// enlever d'eventuelles parentheses deja presentes sur champs
	if (!is_array($champs)) {
		if ($champs[0] == '(') {
			$champs = substr($champs, 1, -1);
		}
		$champs = [$champs];
		// supprimer l'info de longueur d'index mysql en fin de champ
		$champs = preg_replace(',\(\d+\)$,', '', $champs);
	}

	$ifnotexists = '';
	$version = spip_sqlite_fetch(spip_sqlite_query('select sqlite_version() AS sqlite_version', $serveur), '', $serveur);
	if (!function_exists('spip_version_compare')) {
		include_spip('plugins/installer');
	}

	if ($version and spip_version_compare($version['sqlite_version'], '3.3.0', '>=')) {
		$ifnotexists = ' IF NOT EXISTS';
	} else {
		/* simuler le IF EXISTS - version 2 et sqlite < 3.3a */
		$a = spip_sqlite_showtable($table, $serveur);
		if (isset($a['key']['KEY ' . $nom])) {
			return true;
		}
	}

	$query = 'CREATE ' . ($unique ? 'UNIQUE ' : '') . "INDEX$ifnotexists $nom ON $table (" . join(',', $champs) . ')';
	$res = spip_sqlite_query($query, $serveur, $requeter);
	if (!$requeter) {
		return $res;
	}
	if ($res) {
		return true;
	} else {
		return false;
	}
}

/**
 * Retourne le nombre de lignes d’une ressource de sélection obtenue
 * avec `sql_select()`
 *
 * En PDO/sqlite3, il faut calculer le count par une requete count(*)
 * pour les resultats de SELECT
 * cela est fait sans spip_sqlite_query()
 *
 * @param PDOStatement $r Jeu de résultats
 * @param string $serveur Nom de la connexion
 * @param bool $requeter Inutilisé
 * @return int                 Nombre de lignes
 */
function spip_sqlite_count($r, $serveur = '', $requeter = true)
{
	if (!$r) {
		return 0;
	}

	// select ou autre (insert, update,...) ?
	// (link,requete) a compter
	if (is_array($r->spipSqliteRowCount)) {
		list($link, $query) = $r->spipSqliteRowCount;
		// amelioration possible a tester intensivement : pas de order by pour compter !
		// $query = preg_replace(",ORDER BY .+(LIMIT\s|HAVING\s|GROUP BY\s|$),Uims","\\1",$query);
		$query = "SELECT count(*) as zzzzsqlitecount FROM ($query)";
		$l = $link->query($query);
		$i = 0;
		if ($l and $z = $l->fetch()) {
			$i = (int) $z['zzzzsqlitecount'];
		}
		$r->spipSqliteRowCount = $i;
	}
	if (isset($r->spipSqliteRowCount)) {
		// Ce compte est faux s'il y a des limit dans la requete :(
		// il retourne le nombre d'enregistrements sans le limit
		return $r->spipSqliteRowCount;
	} else {
		return $r->rowCount();
	}
}


/**
 * Retourne le nombre de lignes d'une sélection
 *
 * @param array|string $from Tables à consulter (From)
 * @param array|string $where Conditions a remplir (Where)
 * @param array|string $groupby Critère de regroupement (Group by)
 * @param array|string $having Tableau des des post-conditions à remplir (Having)
 * @param string $serveur Nom de la connexion
 * @param bool $requeter Exécuter la requête, sinon la retourner
 * @return int|bool|string
 *     - string texte de la requête si demandé
 *     - int Nombre de lignes
 *     - false si la requête a échouée
 **/
function spip_sqlite_countsel(
	$from = [],
	$where = [],
	$groupby = '',
	$having = [],
	$serveur = '',
	$requeter = true
) {
	$c = !$groupby ? '*' : ('DISTINCT ' . (is_string($groupby) ? $groupby : join(',', $groupby)));
	$r = spip_sqlite_select(
		"COUNT($c)",
		$from,
		$where,
		'',
		'',
		'',
		$having,
		$serveur,
		$requeter
	);
	if ((is_resource($r) or is_object($r)) && $requeter) { // ressource : sqlite2, object : sqlite3
		[$r] = spip_sqlite_fetch($r, SPIP_SQLITE3_NUM, $serveur);
		$r = (int) $r;
	}

	return $r;
}


/**
 * Supprime des enregistrements d'une table
 *
 * @param string $table Nom de la table SQL
 * @param string|array $where Conditions à vérifier
 * @param string $serveur Nom du connecteur
 * @param bool $requeter Exécuter la requête, sinon la retourner
 * @return bool|string
 *     - int : nombre de suppressions réalisées,
 *     - texte de la requête si demandé,
 *     - false en cas d'erreur.
 **/
function spip_sqlite_delete($table, $where = '', $serveur = '', $requeter = true)
{
	$res = spip_sqlite_query(
		_sqlite_calculer_expression('DELETE FROM', $table, ',')
			. _sqlite_calculer_expression('WHERE', $where),
		$serveur,
		$requeter
	);

	// renvoyer la requete inerte si demandee
	if (!$requeter) {
		return $res;
	}

	if ($res) {
		$link = _sqlite_link($serveur);
		return $res->rowCount();
	} else {
		return false;
	}
}


/**
 * Supprime une table SQL
 *
 * @param string $table Nom de la table SQL
 * @param string $exist True pour ajouter un test d'existence avant de supprimer
 * @param string $serveur Nom de la connexion
 * @param bool $requeter Exécuter la requête, sinon la retourner
 * @return bool|string
 *     - string texte de la requête si demandé
 *     - true si la requête a réussie, false sinon
 */
function spip_sqlite_drop_table($table, $exist = '', $serveur = '', $requeter = true)
{
	if ($exist) {
		$exist = ' IF EXISTS';
	}

	if (spip_sqlite_query("DROP TABLE$exist $table", $serveur, $requeter)) {
		return true;
	} else {
		return false;
	}
}


/**
 * Supprime une vue SQL
 *
 * @param string $view Nom de la vue SQL
 * @param string $exist True pour ajouter un test d'existence avant de supprimer
 * @param string $serveur Nom de la connexion
 * @param bool $requeter Exécuter la requête, sinon la retourner
 * @return bool|string
 *     - string texte de la requête si demandé
 *     - true si la requête a réussie, false sinon
 */
function spip_sqlite_drop_view($view, $exist = '', $serveur = '', $requeter = true)
{
	if ($exist) {
		$exist = ' IF EXISTS';
	}

	return spip_sqlite_query("DROP VIEW$exist $view", $serveur, $requeter);
}

/**
 * Fonction de suppression d'un INDEX
 *
 * @param string $nom : nom de l'index
 * @param string $table : table sql de l'index
 * @param string $serveur : nom de la connexion sql utilisee
 * @param bool $requeter : true pour executer la requête ou false pour retourner le texte de la requête
 *
 * @return bool ou requete
 */
function spip_sqlite_drop_index($nom, $table, $serveur = '', $requeter = true)
{
	if (!($nom or $table)) {
		spip_log("Champ manquant pour supprimer un index sqlite ($nom, $table)", 'sqlite.' . _LOG_ERREUR);

		return false;
	}

	// SQLite ne differentie pas noms des index en fonction des tables
	// il faut donc creer des noms uniques d'index pour une base sqlite
	$index = $table . '_' . $nom;
	$exist = ' IF EXISTS';

	$query = "DROP INDEX$exist $index";

	return spip_sqlite_query($query, $serveur, $requeter);
}

/**
 * Retourne la dernière erreur generée
 *
 * @uses sql_error_backtrace()
 *
 * @param string $query
 *     Requête qui était exécutée
 * @param string $serveur
 *     Nom de la connexion
 * @return string
 *     Erreur eventuelle
 **/
function spip_sqlite_error($query = '', $serveur = '')
{
	$link = _sqlite_link($serveur);

	if ($link) {
		$errs = $link->errorInfo();
		$s = _sqlite_last_error_from_link($link);
	} else {
		$s = ': aucune ressource sqlite (link)';
	}
	if ($s) {
		$trace = debug_backtrace();
		if ($trace[0]['function'] != 'spip_sqlite_error') {
			spip_log("$s - $query - " . sql_error_backtrace(), 'sqlite.' . _LOG_ERREUR);
		}
	}

	return $s;
}

function _sqlite_last_error_from_link($link)
{
	if ($link) {
		$errs = $link->errorInfo();
		/*
			$errs[0]
				numero SQLState ('HY000' souvent lors d'une erreur)
				http://www.easysoft.com/developer/interfaces/odbc/sqlstate_status_return_codes.html
			$errs[1]
				numéro d'erreur SQLite (souvent 1 lors d'une erreur)
				http://www.sqlite.org/c3ref/c_abort.html
			$errs[2]
				Le texte du message d'erreur
		*/
		if (ltrim($errs[0], '0')) { // 00000 si pas d'erreur
			return "$errs[2]";
		}
	}
	return '';
}

/**
 * Retourne le numero de la dernière erreur SQL
 *
 * Le numéro (en sqlite3/pdo) est un retour ODBC tel que (très souvent) HY000
 * http://www.easysoft.com/developer/interfaces/odbc/sqlstate_status_return_codes.html
 *
 * @param string $serveur
 *    nom de la connexion
 * @return int|string
 *    0 pas d'erreur
 *    1 ou autre erreur (en sqlite 2)
 *    'HY000/1' : numéro de l'erreur SQLState / numéro d'erreur interne SQLite (en sqlite 3)
 **/
function spip_sqlite_errno($serveur = '')
{
	$link = _sqlite_link($serveur);

	if ($link) {
		$t = $link->errorInfo();
		$s = ltrim($t[0], '0'); // 00000 si pas d'erreur
		if ($s) {
			$s .= ' / ' . $t[1];
		} // ajoute l'erreur du moteur SQLite
	} else {
		$s = ': aucune ressource sqlite (link)';
	}

	if ($s) {
		spip_log("Erreur sqlite $s", 'sqlite.' . _LOG_ERREUR);
	}

	return $s ? $s : 0;
}


/**
 * Retourne une explication de requête (Explain) SQLite
 *
 * @param string $query texte de la requête
 * @param string $serveur Nom de la connexion
 * @param bool $requeter Exécuter la requête, sinon la retourner
 * @return array|string|bool
 *     - array : Tableau de l'explication
 *     - string si on retourne le texte de la requête
 *     - false si on a pas pu avoir d'explication
 */
function spip_sqlite_explain($query, $serveur = '', $requeter = true)
{
	if (strpos(ltrim($query), 'SELECT') !== 0) {
		return [];
	}

	$query = Sqlite::traduire_requete($query, $serveur);
	$query = 'EXPLAIN ' . $query;
	if (!$requeter) {
		return $query;
	}
	// on ne trace pas ces requetes, sinon on obtient un tracage sans fin...
	$r = Sqlite::executer_requete($query, $serveur, false);

	return $r ? spip_sqlite_fetch($r, null, $serveur) : false; // hum ? etrange ca... a verifier
}


/**
 * Rècupère une ligne de résultat
 *
 * Récupère la ligne suivante d'une ressource de résultat
 *
 * @param PDOStatement $r Jeu de résultats (issu de sql_select)
 * @param string $t Structure de résultat attendu (défaut ASSOC)
 * @param string $serveur Nom de la connexion
 * @param bool $requeter Inutilisé
 * @return array|null|false
 *     - array Ligne de résultat
 *     - null Pas de résultat
 *     - false Erreur
 */
function spip_sqlite_fetch($r, $t = '', $serveur = '', $requeter = true)
{

	$link = _sqlite_link($serveur);
	$t = $t ? $t : SPIP_SQLITE3_ASSOC;

	if (!$r) {
		return false;
	}

	$retour = $r->fetch($t);

	if (!$retour) {
		if ($r->errorCode() === '00000') {
			return null;
		}
		return false;
	}

	// Renvoie des 'table.titre' au lieu de 'titre' tout court ! pff !
	// suppression de 'table.' pour toutes les cles (c'est un peu violent !)
	// c'est couteux : on ne verifie que la premiere ligne pour voir si on le fait ou non
	if (str_contains(implode('', array_keys($retour)), '.')) {
		foreach ($retour as $cle => $val) {
			if (($pos = strpos($cle, '.')) !== false) {
				$retour[substr($cle, $pos + 1)] = &$retour[$cle];
				unset($retour[$cle]);
			}
		}
	}

	return $retour;
}

/**
 * Place le pointeur de résultat sur la position indiquée
 *
 * @param PDOStatement $r Jeu de résultats
 * @param int $row_number Position. Déplacer le pointeur à cette ligne
 * @param string $serveur Nom de la connexion
 * @param bool $requeter Inutilisé
 * @return bool True si déplacement réussi, false sinon.
 **/
function spip_sqlite_seek($r, $row_number, $serveur = '', $requeter = true)
{
	// encore un truc de bien fichu : PDO ne PEUT PAS faire de seek ou de rewind...
	return false;
}


/**
 * Libère une ressource de résultat
 *
 * Indique à SQLite de libérer de sa mémoire la ressoucre de résultat indiquée
 * car on n'a plus besoin de l'utiliser.
 *
 * @param PDOStatement $r Jeu de résultats
 * @param string $serveur Nom de la connexion
 * @param bool $requeter Inutilisé
 * @return bool                True si réussi
 */
function spip_sqlite_free(&$r, $serveur = '', $requeter = true)
{
	unset($r);

	return true;
	//return sqlite_free_result($r);
}


/**
 * Teste si le charset indiqué est disponible sur le serveur SQL (aucune action ici)
 *
 * Cette fonction n'a aucune action actuellement
 *
 * @param array|string $charset Nom du charset à tester.
 * @param string $serveur Nom de la connexion
 * @param bool $requeter inutilisé
 * @return void
 */
function spip_sqlite_get_charset($charset = [], $serveur = '', $requeter = true)
{
	//$c = !$charset ? '' : (" LIKE "._q($charset['charset']));
	//return spip_sqlite_fetch(sqlite_query(_sqlite_link($serveur), "SHOW CHARACTER SET$c"), NULL, $serveur);
}


/**
 * Prépare une chaîne hexadécimale
 *
 * Par exemple : FF ==> 255 en SQLite
 *
 * @param string $v
 *     Chaine hexadecimale
 * @return string
 *     Valeur hexadécimale pour SQLite
 **/
function spip_sqlite_hex($v)
{
	return hexdec($v);
}


/**
 * Retourne une expression IN pour le gestionnaire de base de données
 *
 * @param string $val
 *     Colonne SQL sur laquelle appliquer le test
 * @param string $valeurs
 *     Liste des valeurs possibles (séparés par des virgules)
 * @param string $not
 *     - '' sélectionne les éléments correspondant aux valeurs
 *     - 'NOT' inverse en sélectionnant les éléments ne correspondant pas aux valeurs
 * @param string $serveur
 *     Nom du connecteur
 * @param bool $requeter
 *     Inutilisé
 * @return string
 *     Expression de requête SQL
 **/
function spip_sqlite_in($val, $valeurs, $not = '', $serveur = '', $requeter = true)
{
	return "($val $not IN ($valeurs))";
}


/**
 * Insère une ligne dans une table
 *
 * @param string $table
 *     Nom de la table SQL
 * @param string $champs
 *     Liste des colonnes impactées,
 * @param string $valeurs
 *     Liste des valeurs,
 * @param array $desc
 *     Tableau de description des colonnes de la table SQL utilisée
 *     (il sera calculé si nécessaire s'il n'est pas transmis).
 * @param string $serveur
 *     Nom du connecteur
 * @param bool $requeter
 *     Exécuter la requête, sinon la retourner
 * @return bool|string|int|array
 *     - int|true identifiant de l'élément inséré (si possible), ou true, si réussite
 *     - texte de la requête si demandé,
 *     - false en cas d'erreur,
 *     - Tableau de description de la requête et du temps d'exécution, si var_profile activé
 **/
function spip_sqlite_insert($table, $champs, $valeurs, $desc = [], $serveur = '', $requeter = true)
{

	$query = "INSERT INTO $table " . ($champs ? "$champs VALUES $valeurs" : 'DEFAULT VALUES');
	if ($r = spip_sqlite_query($query, $serveur, $requeter)) {
		if (!$requeter) {
			return $r;
		}
		$nb = Sqlite::last_insert_id($serveur);
	} else {
		$nb = false;
	}

	$err = spip_sqlite_error($query, $serveur);

	// cas particulier : ne pas substituer la reponse spip_sqlite_query si on est en profilage
	return isset($_GET['var_profile']) ? $r : $nb;
}


/**
 * Insère une ligne dans une table, en protégeant chaque valeur
 *
 * @param string $table
 *     Nom de la table SQL
 * @param array $couples
 *    Couples (colonne => valeur)
 * @param array $desc
 *     Tableau de description des colonnes de la table SQL utilisée
 *     (il sera calculé si nécessaire s'il n'est pas transmis).
 * @param string $serveur
 *     Nom du connecteur
 * @param bool $requeter
 *     Exécuter la requête, sinon la retourner
 * @return bool|string|int|array
 *     - int|true identifiant de l'élément inséré (si possible), ou true, si réussite
 *     - texte de la requête si demandé,
 *     - false en cas d'erreur,
 *     - Tableau de description de la requête et du temps d'exécution, si var_profile activé
 **/
function spip_sqlite_insertq($table, $couples = [], $desc = [], $serveur = '', $requeter = true)
{
	if (!$desc) {
		$desc = description_table($table, $serveur);
	}
	if (!$desc) {
		die("$table insertion sans description");
	}
	$fields = isset($desc['field']) ? $desc['field'] : [];

	foreach ($couples as $champ => $val) {
		$couples[$champ] = _sqlite_calculer_cite($val, $fields[$champ]);
	}

	// recherche de champs 'timestamp' pour mise a jour auto de ceux-ci
	$couples = _sqlite_ajouter_champs_timestamp($table, $couples, $desc, $serveur);

	$cles = $valeurs = '';
	if (count($couples)) {
		$cles = '(' . join(',', array_keys($couples)) . ')';
		$valeurs = '(' . join(',', $couples) . ')';
	}

	return spip_sqlite_insert($table, $cles, $valeurs, $desc, $serveur, $requeter);
}


/**
 * Insère plusieurs lignes d'un coup dans une table
 *
 * @param string $table
 *     Nom de la table SQL
 * @param array $tab_couples
 *     Tableau de tableaux associatifs (colonne => valeur)
 * @param array $desc
 *     Tableau de description des colonnes de la table SQL utilisée
 *     (il sera calculé si nécessaire s'il n'est pas transmis).
 * @param string $serveur
 *     Nom du connecteur
 * @param bool $requeter
 *     Exécuter la requête, sinon la retourner
 * @return bool|string
 *     - true en cas de succès,
 *     - texte de la requête si demandé,
 *     - false en cas d'erreur.
 **/
function spip_sqlite_insertq_multi($table, $tab_couples = [], $desc = [], $serveur = '', $requeter = true)
{
	if (!$desc) {
		$desc = description_table($table, $serveur);
	}
	if (!$desc) {
		die("$table insertion sans description");
	}
	if (!isset($desc['field'])) {
		$desc['field'] = [];
	}

	// recuperer les champs 'timestamp' pour mise a jour auto de ceux-ci
	$maj = _sqlite_ajouter_champs_timestamp($table, [], $desc, $serveur);

	// seul le nom de la table est a traduire ici :
	// le faire une seule fois au debut
	$query_start = "INSERT INTO $table ";
	$query_start = Sqlite::traduire_requete($query_start, $serveur);

	// ouvrir une transaction
	if ($requeter) {
		Sqlite::demarrer_transaction($serveur);
	}

	while ($couples = array_shift($tab_couples)) {
		foreach ($couples as $champ => $val) {
			$couples[$champ] = _sqlite_calculer_cite($val, $desc['field'][$champ]);
		}

		// inserer les champs timestamp par defaut
		$couples = array_merge($maj, $couples);

		$champs = $valeurs = '';
		if (count($couples)) {
			$champs = '(' . join(',', array_keys($couples)) . ')';
			$valeurs = '(' . join(',', $couples) . ')';
			$query = $query_start . "$champs VALUES $valeurs";
		} else {
			$query = $query_start . 'DEFAULT VALUES';
		}

		if ($requeter) {
			$retour = Sqlite::executer_requete($query, $serveur);
		}

		// sur le dernier couple uniquement
		if (!count($tab_couples)) {
			$nb = 0;
			if ($requeter) {
				$nb = Sqlite::last_insert_id($serveur);
			} else {
				return $query;
			}
		}

		$err = spip_sqlite_error($query, $serveur);
	}

	if ($requeter) {
		Sqlite::finir_transaction($serveur);
	}

	// renvoie le dernier id d'autoincrement ajoute
	// cas particulier : ne pas substituer la reponse spip_sqlite_query si on est en profilage
	return isset($_GET['var_profile']) ? $retour : $nb;
}


/**
 * Retourne si le moteur SQL préfère utiliser des transactions.
 *
 * @param string $serveur
 *     Nom du connecteur
 * @param bool $requeter
 *     Inutilisé
 * @return bool
 *     Toujours true.
 **/
function spip_sqlite_preferer_transaction($serveur = '', $requeter = true)
{
	return true;
}

/**
 * Démarre une transaction
 *
 * Pratique pour des sql_updateq() dans un foreach,
 * parfois 100* plus rapide s'ils sont nombreux en sqlite !
 *
 * @param string $serveur
 *     Nom du connecteur
 * @param bool $requeter
 *     true pour exécuter la requête ou false pour retourner le texte de la requête
 * @return bool|string
 *     string si texte de la requête demandé, true sinon
 **/
function spip_sqlite_demarrer_transaction($serveur = '', $requeter = true)
{
	if (!$requeter) {
		return 'BEGIN TRANSACTION';
	}
	Sqlite::demarrer_transaction($serveur);

	return true;
}

/**
 * Clôture une transaction
 *
 * @param string $serveur
 *     Nom du connecteur
 * @param bool $requeter
 *     true pour exécuter la requête ou false pour retourner le texte de la requête
 * @return bool|string
 *     string si texte de la requête demandé, true sinon
 **/
function spip_sqlite_terminer_transaction($serveur = '', $requeter = true)
{
	if (!$requeter) {
		return 'COMMIT';
	}
	Sqlite::finir_transaction($serveur);

	return true;
}


/**
 * Liste les bases de données disponibles
 *
 * @param string $serveur
 *     Nom du connecteur
 * @param bool $requeter
 *     Inutilisé
 * @return array
 *     Liste des noms de bases
 **/
function spip_sqlite_listdbs($serveur = '', $requeter = true)
{
	_sqlite_init();

	if (!is_dir($d = substr(_DIR_DB, 0, -1))) {
		return [];
	}

	include_spip('inc/flock');
	$bases = preg_files($d, $pattern = '(.*)\.sqlite$');
	$bds = [];

	foreach ($bases as $b) {
		// pas de bases commencant pas sqlite
		// (on s'en sert pour l'installation pour simuler la presence d'un serveur)
		// les bases sont de la forme _sqliteX_tmp_spip_install.sqlite
		if (strpos($b, '_sqlite')) {
			continue;
		}
		$bds[] = preg_replace(";.*/$pattern;iS", '$1', $b);
	}

	return $bds;
}


/**
 * Retourne l'instruction SQL pour obtenir le texte d'un champ contenant
 * une balise `<multi>` dans la langue indiquée
 *
 * Cette sélection est mise dans l'alias `multi` (instruction AS multi).
 *
 * @param string $objet Colonne ayant le texte
 * @param string $lang Langue à extraire
 * @return string       texte de sélection pour la requête
 */
function spip_sqlite_multi($objet, $lang)
{
	$r = 'EXTRAIRE_MULTI(' . $objet . ", '" . $lang . "') AS multi";

	return $r;
}


/**
 * Optimise une table SQL
 *
 * @note
 *   Sqlite optimise TOUT un fichier sinon rien.
 *   On évite donc 2 traitements sur la même base dans un hit.
 *
 * @param string $table nom de la table a optimiser
 * @param string $serveur nom de la connexion
 * @param bool $requeter effectuer la requete ? sinon retourner son code
 * @return bool|string true / false / requete
 **/
function spip_sqlite_optimize($table, $serveur = '', $requeter = true)
{
	static $do = false;
	if ($requeter and $do) {
		return true;
	}
	if ($requeter) {
		$do = true;
	}

	return spip_sqlite_query('VACUUM', $serveur, $requeter);
}


/**
 * Échapper une valeur selon son type
 * mais pour SQLite avec ses spécificités
 *
 * @param string|array|number $v
 *     texte, nombre ou tableau à échapper
 * @param string $type
 *     Description du type attendu
 *    (par exemple description SQL de la colonne recevant la donnée)
 * @return string|number
 *    Donnée prête à être utilisée par le gestionnaire SQL
 */
function spip_sqlite_quote($v, $type = '')
{
	if (!is_array($v)) {
		return _sqlite_calculer_cite($v, $type);
	}
	// si c'est un tableau, le parcourir en propageant le type
	foreach ($v as $k => $r) {
		$v[$k] = spip_sqlite_quote($r, $type);
	}

	return join(',', $v);
}


/**
 * Tester si une date est proche de la valeur d'un champ
 *
 * @param string $champ
 *     Nom du champ a tester
 * @param int $interval
 *     Valeur de l'intervalle : -1, 4, ...
 * @param string $unite
 *     Utité utilisée (DAY, MONTH, YEAR, ...)
 * @return string
 *     Expression SQL
 **/
function spip_sqlite_date_proche($champ, $interval, $unite)
{
	$op = (($interval <= 0) ? '>' : '<');

	return "($champ $op datetime('" . date('Y-m-d H:i:s') . "', '$interval $unite'))";
}


/**
 * Répare une table SQL
 *
 * Il n'y a pas de fonction native repair dans sqlite, mais on profite
 * pour vérifier que tous les champs (text|char) ont bien une clause DEFAULT
 *
 * @param string $table Nom de la table SQL
 * @param string $serveur Nom de la connexion
 * @param bool $requeter Exécuter la requête, sinon la retourner
 * @return string[]
 *     Tableau avec clé 0 pouvant avoir " OK " ou " ERROR " indiquant
 *     l'état de la table après la réparation
 */
function spip_sqlite_repair($table, $serveur = '', $requeter = true)
{
	if (
		$desc = spip_sqlite_showtable($table, $serveur)
		and isset($desc['field'])
		and is_array($desc['field'])
	) {
		foreach ($desc['field'] as $c => $d) {
			if (
				preg_match(',^(tinytext|mediumtext|text|longtext|varchar|char),i', $d)
				and stripos($d, 'NOT NULL') !== false
				and stripos($d, 'DEFAULT') === false
				/* pas touche aux cles primaires */
				and (!isset($desc['key']['PRIMARY KEY']) or $desc['key']['PRIMARY KEY'] !== $c)
			) {
				spip_sqlite_alter($q = "TABLE $table CHANGE $c $c $d DEFAULT ''", $serveur);
				spip_log("ALTER $q", 'repair' . _LOG_INFO_IMPORTANTE);
			}
			if (
				preg_match(',^(INTEGER),i', $d)
				and stripos($d, 'NOT NULL') !== false
				and stripos($d, 'DEFAULT') === false
				/* pas touche aux cles primaires */
				and (!isset($desc['key']['PRIMARY KEY']) or $desc['key']['PRIMARY KEY'] !== $c)
			) {
				spip_sqlite_alter($q = "TABLE $table CHANGE $c $c $d DEFAULT '0'", $serveur);
				spip_log("ALTER $q", 'repair' . _LOG_INFO_IMPORTANTE);
			}
			if (
				preg_match(',^(datetime),i', $d)
				and stripos($d, 'NOT NULL') !== false
				and stripos($d, 'DEFAULT') === false
				/* pas touche aux cles primaires */
				and (!isset($desc['key']['PRIMARY KEY']) or $desc['key']['PRIMARY KEY'] !== $c)
			) {
				spip_sqlite_alter($q = "TABLE $table CHANGE $c $c $d DEFAULT '0000-00-00 00:00:00'", $serveur);
				spip_log("ALTER $q", 'repair' . _LOG_INFO_IMPORTANTE);
			}
		}

		return [' OK '];
	}

	return [' ERROR '];
}


/**
 * Insère où met à jour une entrée d’une table SQL
 *
 * La clé ou les cles primaires doivent être présentes dans les données insérés.
 * La fonction effectue une protection automatique des données.
 *
 * Préférer à cette fonction updateq ou insertq.
 *
 * @param string $table
 *     Nom de la table SQL
 * @param array $couples
 *     Couples colonne / valeur à modifier,
 * @param array $desc
 *     Tableau de description des colonnes de la table SQL utilisée
 *     (il sera calculé si nécessaire s'il n'est pas transmis).
 * @param string $serveur
 *     Nom du connecteur
 * @param bool $requeter
 *     Exécuter la requête, sinon la retourner
 * @return bool|string
 *     - true si réussite
 *     - texte de la requête si demandé,
 *     - false en cas d'erreur.
 **/
function spip_sqlite_replace($table, $couples, $desc = [], $serveur = '', $requeter = true)
{
	if (!$desc) {
		$desc = description_table($table, $serveur);
	}
	if (!$desc) {
		die("$table insertion sans description");
	}
	$fields = isset($desc['field']) ? $desc['field'] : [];

	foreach ($couples as $champ => $val) {
		$couples[$champ] = _sqlite_calculer_cite($val, $fields[$champ]);
	}

	// recherche de champs 'timestamp' pour mise a jour auto de ceux-ci
	$couples = _sqlite_ajouter_champs_timestamp($table, $couples, $desc, $serveur);

	return spip_sqlite_query("REPLACE INTO $table (" . join(',', array_keys($couples)) . ') VALUES (' . join(
		',',
		$couples
	) . ')', $serveur);
}


/**
 * Insère où met à jour des entrées d’une table SQL
 *
 * La clé ou les cles primaires doivent être présentes dans les données insérés.
 * La fonction effectue une protection automatique des données.
 *
 * Préférez insertq_multi et sql_updateq
 *
 * @param string $table
 *     Nom de la table SQL
 * @param array $tab_couples
 *     Tableau de tableau (colonne / valeur à modifier),
 * @param array $desc
 *     Tableau de description des colonnes de la table SQL utilisée
 *     (il sera calculé si nécessaire s'il n'est pas transmis).
 * @param string $serveur
 *     Nom du connecteur
 * @param bool $requeter
 *     Exécuter la requête, sinon la retourner
 * @return bool|string
 *     - true si réussite
 *     - texte de la requête si demandé,
 *     - false en cas d'erreur.
 **/
function spip_sqlite_replace_multi($table, $tab_couples, $desc = [], $serveur = '', $requeter = true)
{

	// boucler pour trainter chaque requete independemment
	foreach ($tab_couples as $couples) {
		$retour = spip_sqlite_replace($table, $couples, $desc, $serveur, $requeter);
	}

	// renvoie le dernier id
	return $retour;
}


/**
 * Exécute une requête de sélection avec SQLite
 *
 * Instance de sql_select (voir ses specs).
 *
 * @see sql_select()
 *
 * @param string|array $select Champs sélectionnés
 * @param string|array $from Tables sélectionnées
 * @param string|array $where Contraintes
 * @param string|array $groupby Regroupements
 * @param string|array $orderby Tris
 * @param string $limit Limites de résultats
 * @param string|array $having Contraintes posts sélections
 * @param string $serveur Nom de la connexion
 * @param bool $requeter Exécuter la requête, sinon la retourner
 * @return array|bool|resource|string
 *     - string : texte de la requête si on ne l'exécute pas
 *     - ressource si requête exécutée, ressource pour fetch()
 *     - false si la requête exécutée a ratée
 *     - array  : Tableau décrivant requête et temps d'exécution si var_profile actif pour tracer.
 */
function spip_sqlite_select(
	$select,
	$from,
	$where = '',
	$groupby = '',
	$orderby = '',
	$limit = '',
	$having = '',
	$serveur = '',
	$requeter = true
) {

	// version() n'est pas connu de sqlite
	$select = str_replace('version()', 'sqlite_version()', $select);

	// recomposer from
	$from = (!is_array($from) ? $from : _sqlite_calculer_select_as($from));

	$query =
		_sqlite_calculer_expression('SELECT', $select, ', ')
		. _sqlite_calculer_expression('FROM', $from, ', ')
		. _sqlite_calculer_expression('WHERE', $where)
		. _sqlite_calculer_expression('GROUP BY', $groupby, ',')
		. _sqlite_calculer_expression('HAVING', $having)
		. ($orderby ? ("\nORDER BY " . _sqlite_calculer_order($orderby)) : '')
		. ($limit ? "\nLIMIT $limit" : '');

	// dans un select, on doit renvoyer la requête en cas d'erreur
	$res = spip_sqlite_query($query, $serveur, $requeter);
	// texte de la requete demande ?
	if (!$requeter) {
		return $res;
	}
	// erreur survenue ?
	if ($res === false) {
		return Sqlite::traduire_requete($query, $serveur);
	}

	return $res;
}


/**
 * Sélectionne un fichier de base de données
 *
 * @param string $db
 *     Nom de la base à utiliser
 * @param string $serveur
 *     Nom du connecteur
 * @param bool $requeter
 *     Inutilisé
 *
 * @return bool|string
 *     - Nom de la base en cas de success.
 *     - False en cas d'erreur.
 **/
function spip_sqlite_selectdb($db, $serveur = '', $requeter = true)
{
	_sqlite_init();

	// interdire la creation d'une nouvelle base,
	// sauf si on est dans l'installation
	if (
		!is_file($f = _DIR_DB . $db . '.sqlite')
		&& (!defined('_ECRIRE_INSTALL') || !_ECRIRE_INSTALL)
	) {
		spip_log("Il est interdit de creer la base $db", 'sqlite.' . _LOG_HS);

		return false;
	}

	// se connecter a la base indiquee
	// avec les identifiants connus
	$index = $serveur ? $serveur : 0;

	if ($link = spip_connect_db('', '', '', '', '@selectdb@' . $db, $serveur, '', '')) {
		if (($db == $link['db']) && $GLOBALS['connexions'][$index] = $link) {
			return $db;
		}
	} else {
		spip_log("Impossible de selectionner la base $db", 'sqlite.' . _LOG_HS);
	}

	return false;
}


/**
 * Définit un charset pour la connexion avec SQLite (aucune action ici)
 *
 * Cette fonction n'a aucune action actuellement.
 *
 * @param string $charset Charset à appliquer
 * @param string $serveur Nom de la connexion
 * @param bool $requeter inutilisé
 * @return void
 */
function spip_sqlite_set_charset($charset, $serveur = '', $requeter = true)
{
	# spip_log("Gestion charset sql a ecrire : "."SET NAMES "._q($charset), 'sqlite.'._LOG_ERREUR);
	# return spip_sqlite_query("SET NAMES ". spip_sqlite_quote($charset), $serveur); //<-- Passe pas !
}


/**
 * Retourne une ressource de la liste des tables de la base de données
 *
 * @param string $match
 *     Filtre sur tables à récupérer
 * @param string $serveur
 *     Connecteur de la base
 * @param bool $requeter
 *     true pour éxecuter la requête
 *     false pour retourner le texte de la requête.
 * @return PDOStatement|bool|string|array
 *     Ressource à utiliser avec sql_fetch()
 **/
function spip_sqlite_showbase($match, $serveur = '', $requeter = true)
{
	// type est le type d'entrée : table / index / view
	// on ne retourne que les tables (?) et non les vues...
	# ESCAPE non supporte par les versions sqlite <3
	#	return spip_sqlite_query("SELECT name FROM sqlite_master WHERE type='table' AND tbl_name LIKE "._q($match)." ESCAPE '\'", $serveur, $requeter);
	$match = preg_quote($match);
	$match = str_replace('\\\_', '[[TIRETBAS]]', $match);
	$match = str_replace('\\\%', '[[POURCENT]]', $match);
	$match = str_replace('_', '.', $match);
	$match = str_replace('%', '.*', $match);
	$match = str_replace('[[TIRETBAS]]', '_', $match);
	$match = str_replace('[[POURCENT]]', '%', $match);
	$match = "^$match$";

	return spip_sqlite_query(
		"SELECT name FROM sqlite_master WHERE type='table' AND tbl_name REGEXP " . _q($match),
		$serveur,
		$requeter
	);
}

/**
 * Indique si une table existe dans la base de données
 *
 * @param string $table
 *     Table dont on cherche l’existence
 * @param string $serveur
 *     Connecteur de la base
 * @param bool $requeter
 *     true pour éxecuter la requête
 *     false pour retourner le texte de la requête.
 * @return bool|string
 *     - true si la table existe, false sinon
 *     - string : requete sql, si $requeter = true
 **/
function spip_sqlite_table_exists(string $table, $serveur = '', $requeter = true)
{
	$r = spip_sqlite_query(
		'SELECT name FROM sqlite_master WHERE'
			. ' type=\'table\''
			. ' AND name=' . spip_sqlite_quote($table, 'string')
			. ' AND name NOT LIKE \'sqlite_%\'',
		$serveur,
		$requeter
	);
	if (!$requeter) {
		return $r;
	}
	$res = spip_sqlite_fetch($r);
	return (bool) $res;
}

define('_SQLITE_RE_SHOW_TABLE', '/^[^(),]*\(((?:[^()]*\((?:[^()]*\([^()]*\))?[^()]*\)[^()]*)*[^()]*)\)[^()]*$/');
/**
 * Obtient la description d'une table ou vue SQLite
 *
 * Récupère la définition d'une table ou d'une vue avec colonnes, indexes, etc.
 * au même format que la définition des tables SPIP, c'est à dire
 * un tableau avec les clés
 *
 * - `field` (tableau colonne => description SQL) et
 * - `key` (tableau type de clé => colonnes)
 *
 * @param string $nom_table Nom de la table SQL
 * @param string $serveur Nom de la connexion
 * @param bool $requeter Exécuter la requête, sinon la retourner
 * @return array|string
 *     - chaîne vide si pas de description obtenue
 *     - string texte de la requête si demandé
 *     - array description de la table sinon
 */
function spip_sqlite_showtable($nom_table, $serveur = '', $requeter = true)
{
	$query =
		'SELECT sql, type FROM'
		. ' (SELECT * FROM sqlite_master UNION ALL'
		. ' SELECT * FROM sqlite_temp_master)'
		. " WHERE tbl_name LIKE '$nom_table'"
		. " AND type!='meta' AND sql NOT NULL AND name NOT LIKE 'sqlite_%'"
		. ' ORDER BY substr(type,2,1), name';

	$a = spip_sqlite_query($query, $serveur, $requeter);
	if (!$a) {
		return '';
	}
	if (!$requeter) {
		return $a;
	}
	if (!($a = spip_sqlite_fetch($a, null, $serveur))) {
		return '';
	}
	$vue = ($a['type'] == 'view'); // table | vue

	// c'est une table
	// il faut parser le create
	if (!$vue) {
		if (!preg_match(_SQLITE_RE_SHOW_TABLE, array_shift($a), $r)) {
			return '';
		} else {
			$desc = $r[1];
			// extraction d'une KEY éventuelle en prenant garde de ne pas
			// relever un champ dont le nom contient KEY (ex. ID_WHISKEY)
			if (preg_match('/^(.*?),([^,]*\sKEY[ (].*)$/s', $desc, $r)) {
				$namedkeys = $r[2];
				$desc = $r[1];
			} else {
				$namedkeys = '';
			}

			$fields = [];
			$keys = [];

			// enlever les contenus des valeurs DEFAULT 'xxx' qui pourraient perturber
			// par exemple s'il contiennent une virgule.
			// /!\ cela peut aussi echapper le nom des champs si la table a eu des operations avec SQLite Manager !
			list($desc, $echaps) = query_echappe_textes($desc);

			// separer toutes les descriptions de champs, separes par des virgules
			# /!\ explode peut exploser aussi DECIMAL(10,2) !
			$k_precedent = null;
			foreach (explode(',', $desc) as $v) {
				preg_match('/^\s*([^\s]+)\s+(.*)/', $v, $r);
				// Les cles de champs peuvent etre entourees
				// de guillements doubles " , simples ', graves ` ou de crochets [ ],  ou rien.
				// http://www.sqlite.org/lang_keywords.html
				$k = strtolower(query_reinjecte_textes($r[1], $echaps)); // champ, "champ", [champ]...
				if ($char = strpbrk($k[0], '\'"[`')) {
					$k = trim($k, $char);
					if ($char == '[') {
						$k = rtrim($k, ']');
					}
				}
				$def = query_reinjecte_textes($r[2], $echaps); // valeur du champ

				// rustine pour DECIMAL(10,2)
				// s'il y a une parenthèse fermante dans la clé
				// ou dans la définition sans qu'il n'y ait une ouverture avant
				if (str_contains($k, ')') or preg_match('/^[^\(]*\)/', $def)) {
					$fields[$k_precedent] .= ',' . $k . ' ' . $def;
					continue;
				}

				// la primary key peut etre dans une des descriptions de champs
				// et non en fin de table, cas encore decouvert avec Sqlite Manager
				if (stripos($r[2], 'PRIMARY KEY') !== false) {
					$keys['PRIMARY KEY'] = $k;
				}

				$fields[$k] = $def;
				$k_precedent = $k;
			}
			// key inclues dans la requete
			foreach (preg_split('/\)\s*(,|$)/', $namedkeys) as $v) {
				if (preg_match('/^\s*([^(]*)\(([^(]*(\(\d+\))?)$/', $v, $r)) {
					$k = str_replace('`', '', trim($r[1]));
					$t = trim(strtolower(str_replace('`', '', $r[2])), '"');
					if ($k && !isset($keys[$k])) {
						$keys[$k] = $t;
					} else {
						$keys[] = $t;
					}
				}
			}
			// sinon ajouter les key index
			$query =
				'SELECT name,sql FROM'
				. ' (SELECT * FROM sqlite_master UNION ALL'
				. ' SELECT * FROM sqlite_temp_master)'
				. " WHERE tbl_name LIKE '$nom_table'"
				. " AND type='index' AND name NOT LIKE 'sqlite_%'"
				. 'ORDER BY substr(type,2,1), name';
			$a = spip_sqlite_query($query, $serveur, $requeter);
			while ($r = spip_sqlite_fetch($a, null, $serveur)) {
				$key = str_replace($nom_table . '_', '', $r['name']); // enlever le nom de la table ajoute a l'index
				$keytype = 'KEY';
				if (strpos($r['sql'], 'UNIQUE INDEX') !== false) {
					$keytype = 'UNIQUE KEY';
				}
				$colonnes = preg_replace(',.*\((.*)\).*,', '$1', $r['sql']);
				$keys[$keytype . ' ' . $key] = $colonnes;
			}
		}
	} // c'est une vue, on liste les champs disponibles simplement
	else {
		if ($res = sql_fetsel('*', $nom_table, '', '', '', '1', '', $serveur)) { // limit 1
			$fields = [];
			foreach ($res as $c => $v) {
				$fields[$c] = '';
			}
			$keys = [];
		} else {
			return '';
		}
	}

	return ['field' => $fields, 'key' => $keys];
}


/**
 * Met à jour des enregistrements d'une table SQL
 *
 * @param string $table
 *     Nom de la table
 * @param array $champs
 *     Couples (colonne => valeur)
 * @param string|array $where
 *     Conditions a remplir (Where)
 * @param array $desc
 *     Tableau de description des colonnes de la table SQL utilisée
 *     (il sera calculé si nécessaire s'il n'est pas transmis).
 * @param string $serveur
 *     Nom de la connexion
 * @param bool $requeter
 *     Exécuter la requête, sinon la retourner
 * @return array|bool|string
 *     - string : texte de la requête si demandé
 *     - true si la requête a réussie, false sinon
 *     - array Tableau décrivant la requête et son temps d'exécution si var_profile est actif
 */
function spip_sqlite_update($table, $champs, $where = '', $desc = '', $serveur = '', $requeter = true)
{
	// recherche de champs 'timestamp' pour mise a jour auto de ceux-ci
	$champs = _sqlite_ajouter_champs_timestamp($table, $champs, $desc, $serveur);

	$set = [];
	foreach ($champs as $champ => $val) {
		$set[] = $champ . "=$val";
	}
	if (!empty($set)) {
		return spip_sqlite_query(
			_sqlite_calculer_expression('UPDATE', $table, ',')
				. _sqlite_calculer_expression('SET', $set, ',')
				. _sqlite_calculer_expression('WHERE', $where),
			$serveur,
			$requeter
		);
	}

	return false;
}


/**
 * Met à jour des enregistrements d'une table SQL et protège chaque valeur
 *
 * Protège chaque valeur transmise avec sql_quote(), adapté au type
 * de champ attendu par la table SQL
 *
 * @param string $table
 *     Nom de la table
 * @param array $champs
 *     Couples (colonne => valeur)
 * @param string|array $where
 *     Conditions a remplir (Where)
 * @param array $desc
 *     Tableau de description des colonnes de la table SQL utilisée
 *     (il sera calculé si nécessaire s'il n'est pas transmis).
 * @param string $serveur
 *     Nom de la connexion
 * @param bool $requeter
 *     Exécuter la requête, sinon la retourner
 * @return array|bool|string
 *     - string : texte de la requête si demandé
 *     - true si la requête a réussie, false sinon
 *     - array Tableau décrivant la requête et son temps d'exécution si var_profile est actif
 */
function spip_sqlite_updateq($table, $champs, $where = '', $desc = [], $serveur = '', $requeter = true)
{

	if (!$champs) {
		return;
	}
	if (!$desc) {
		$desc = description_table($table, $serveur);
	}
	if (!$desc) {
		die("$table insertion sans description");
	}
	$fields = $desc['field'];

	$set = [];
	foreach ($champs as $champ => $val) {
		$set[$champ] = $champ . '=' . _sqlite_calculer_cite($val, isset($fields[$champ]) ? $fields[$champ] : '');
	}

	// recherche de champs 'timestamp' pour mise a jour auto de ceux-ci
	// attention ils sont deja quotes
	$maj = _sqlite_ajouter_champs_timestamp($table, [], $desc, $serveur);
	foreach ($maj as $champ => $val) {
		if (!isset($set[$champ])) {
			$set[$champ] = $champ . '=' . $val;
		}
	}

	return spip_sqlite_query(
		_sqlite_calculer_expression('UPDATE', $table, ',')
			. _sqlite_calculer_expression('SET', $set, ',')
			. _sqlite_calculer_expression('WHERE', $where),
		$serveur,
		$requeter
	);
}


/*
 *
 * Ensuite les fonctions non abstraites
 * crees pour l'occasion de sqlite
 *
 */


/**
 * Initialise la première connexion à un serveur SQLite
 *
 * @return void
 */
function _sqlite_init()
{
	if (!defined('_DIR_DB')) {
		define('_DIR_DB', _DIR_ETC . 'bases/');
	}
	if (!defined('_SQLITE_CHMOD')) {
		define('_SQLITE_CHMOD', _SPIP_CHMOD);
	}

	if (!is_dir($d = _DIR_DB)) {
		include_spip('inc/flock');
		sous_repertoire($d);
	}
}


/**
 * Teste la version sqlite du link en cours
 *
 * @param string $version
 * @param string $link
 * @param string $serveur
 * @param bool $requeter
 * @return bool|int
 */
function _sqlite_is_version($version = '', $link = '', $serveur = '', $requeter = true)
{
	if ($link === '') {
		$link = _sqlite_link($serveur);
	}
	if (!$link) {
		return false;
	}

	$v = 3;

	if (!$version) {
		return $v;
	}

	return ($version == $v);
}


/**
 * Retrouver un link d'une connexion SQLite
 *
 * @param string $serveur Nom du serveur
 * @return PDO Information de connexion pour SQLite
 */
function _sqlite_link($serveur = '')
{
	$link = &$GLOBALS['connexions'][$serveur ? $serveur : 0]['link'];

	return $link;
}


/* ordre alphabetique pour les autres */


/**
 * Renvoie les bons echappements (mais pas sur les fonctions comme NOW())
 *
 * @param string|number $v texte ou nombre à échapper
 * @param string $type Type de donnée attendue, description SQL de la colonne de destination
 * @return string|number     texte ou nombre échappé
 */
function _sqlite_calculer_cite($v, $type)
{
	if ($type) {
		if (
			is_null($v)
			and stripos($type, 'NOT NULL') === false
		) {
			// null php se traduit en NULL SQL
			return 'NULL';
		}

		if (sql_test_date($type) and preg_match('/^\w+\(/', $v)) {
			return $v;
		}
		if (sql_test_int($type)) {
			if (is_numeric($v)) {
				return $v;
			} elseif ($v === null) {
				return 0;
			} elseif (ctype_xdigit(substr($v, 2)) and strncmp($v, '0x', 2) === 0) {
				return hexdec(substr($v, 2));
			} else {
				return intval($v);
			}
		}
	} else {
		// si on ne connait pas le type on le deduit de $v autant que possible
		if (is_bool($v)) {
			return strval(intval($v));
		} elseif (is_numeric($v)) {
			return strval($v);
		}
	}

	// trouver un link sqlite pour faire l'echappement
	foreach ($GLOBALS['connexions'] as $s) {
		if (
			$l = $s['link']
			and is_object($l)
			and $l instanceof \PDO
			and $l->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite'
		) {
			return $l->quote($v ?? '');
		}
	}

	// echapper les ' en ''
	spip_log('Pas de methode ->quote pour echapper', 'sqlite.' . _LOG_INFO_IMPORTANTE);

	return ("'" . str_replace("'", "''", $v) . "'");
}


/**
 * Calcule un expression pour une requête, en cumulant chaque élément
 * avec l'opérateur de liaison ($join) indiqué
 *
 * Renvoie grosso modo "$expression join($join, $v)"
 *
 * @param string $expression Mot clé de l'expression, tel que "WHERE" ou "ORDER BY"
 * @param array|string $v Données de l'expression
 * @param string $join Si les données sont un tableau, elles seront groupées par cette jointure
 * @return string            texte de l'expression, une partie donc, du texte la requête.
 */
function _sqlite_calculer_expression($expression, $v, $join = 'AND')
{
	if (empty($v)) {
		return '';
	}

	$exp = "\n$expression ";

	if (!is_array($v)) {
		return $exp . $v;
	} else {
		if (strtoupper($join) === 'AND') {
			return $exp . join("\n\t$join ", array_map('_sqlite_calculer_where', $v));
		} else {
			return $exp . join($join, $v);
		}
	}
}


/**
 * Prépare une clause order by
 *
 * Regroupe en texte les éléments si un tableau est donné
 *
 * @note
 *   Pas besoin de conversion pour 0+x comme il faudrait pour mysql.
 *
 * @param string|array $orderby texte du orderby à préparer
 * @return string texte du orderby préparé
 */
function _sqlite_calculer_order($orderby)
{
	return (is_array($orderby)) ? join(', ', $orderby) : $orderby;
}


/**
 * Renvoie des `nom AS alias`
 *
 * @param array $args
 * @return string Sélection de colonnes pour une clause SELECT
 */
function _sqlite_calculer_select_as($args)
{
	$res = '';
	foreach ($args as $k => $v) {
		if (substr($k, -1) == '@') {
			// c'est une jointure qui se refere au from precedent
			// pas de virgule
			$res .= '  ' . $v;
		} else {
			if (!is_numeric($k)) {
				$p = strpos($v, ' ');
				if ($p) {
					$v = substr($v, 0, $p) . " AS '$k'" . substr($v, $p);
				} else {
					$v .= " AS '$k'";
				}
			}
			$res .= ', ' . $v;
		}
	}

	return substr($res, 2);
}


/**
 * Prépare une clause WHERE pour SQLite
 *
 * Retourne une chaîne avec les bonnes parenthèses pour la
 * contrainte indiquée, au format donnée par le compilateur
 *
 * @param array|string $v
 *     Description des contraintes
 *     - string : texte du where
 *     - sinon tableau : A et B peuvent être de type string ou array,
 *       OP et C sont de type string :
 *       - array(A) : A est le texte du where
 *       - array(OP, A) : contrainte OP( A )
 *       - array(OP, A, B) : contrainte (A OP B)
 *       - array(OP, A, B, C) : contrainte (A OP (B) : C)
 * @return string
 *     Contrainte pour clause WHERE
 */
function _sqlite_calculer_where($v)
{
	if (!is_array($v)) {
		return $v;
	}

	$op = array_shift($v);
	if (!($n = count($v))) {
		return $op;
	} else {
		$arg = _sqlite_calculer_where(array_shift($v));
		if ($n == 1) {
			return "$op($arg)";
		} else {
			$arg2 = _sqlite_calculer_where(array_shift($v));
			if ($n == 2) {
				return "($arg $op $arg2)";
			} else {
				return "($arg $op ($arg2) : $v[0])";
			}
		}
	}
}


/**
 * Charger les modules SQLite
 *
 * Si possible et juste la version demandée,
 * ou, si aucune version, on renvoie les versions sqlite disponibles
 * sur ce serveur dans un tableau
 *
 * @param string $version
 * @return array|bool
 */
function _sqlite_charger_version($version = '')
{
	$versions = [];

	// version 3
	if (!$version || $version == 3) {
		if (extension_loaded('pdo') && extension_loaded('pdo_sqlite')) {
			$versions[] = 3;
		}
	}
	if ($version) {
		return in_array($version, $versions);
	}

	return $versions;
}


/**
 * Gestion des requêtes ALTER non reconnues de SQLite
 *
 * Requêtes non reconnues :
 *
 *     ALTER TABLE table DROP column
 *     ALTER TABLE table CHANGE [COLUMN] columnA columnB definition
 *     ALTER TABLE table MODIFY column definition
 *     ALTER TABLE table ADD|DROP PRIMARY KEY
 *
 * `MODIFY` est transformé en `CHANGE columnA columnA` par spip_sqlite_alter()
 *
 * 1) Créer une table B avec le nouveau format souhaité
 * 2) Copier la table d'origine A vers B
 * 3) Supprimer la table A
 * 4) Renommer la table B en A
 * 5) Remettre les index (qui sont supprimés avec la table A)
 *
 * @param string|array $table
 *     - string : Nom de la table table,
 *     - array : couple (nom de la table => nom futur)
 * @param string|array $colonne
 *     - string : nom de la colonne,
 *     - array : couple (nom de la colonne => nom futur)
 * @param array $opt
 *     options comme les tables SPIP, qui sera mergé à la table créee :
 *     `array('field'=>array('nom'=>'syntaxe', ...), 'key'=>array('KEY nom'=>'colonne', ...))`
 * @param string $serveur
 *     Nom de la connexion SQL en cours
 * @return bool
 *     true si OK, false sinon.
 */
function _sqlite_modifier_table($table, $colonne, $opt = [], $serveur = '')
{

	if (is_array($table)) {
		$table_destination = reset($table);
		$table_origine = key($table);
	} else {
		$table_origine = $table_destination = $table;
	}
	// ne prend actuellement qu'un changement
	// mais pourra etre adapte pour changer plus qu'une colonne a la fois
	if (is_array($colonne)) {
		$colonne_destination = reset($colonne);
		$colonne_origine = key($colonne);
	} else {
		$colonne_origine = $colonne_destination = $colonne;
	}
	if (!isset($opt['field'])) {
		$opt['field'] = [];
	}
	if (!isset($opt['key'])) {
		$opt['key'] = [];
	}

	// si les noms de tables sont differents, pas besoin de table temporaire
	// on prendra directement le nom de la future table
	$meme_table = ($table_origine == $table_destination);

	$def_origine = sql_showtable($table_origine, false, $serveur);
	if (!$def_origine or !isset($def_origine['field'])) {
		spip_log("Alter table impossible sur $table_origine : table non trouvee", 'sqlite' . _LOG_ERREUR);

		return false;
	}


	$table_tmp = $table_origine . '_tmp';

	// 1) creer une table temporaire avec les modifications
	// - DROP : suppression de la colonne
	// - CHANGE : modification de la colonne
	// (foreach pour conserver l'ordre des champs)

	// field
	$fields = [];
	// pour le INSERT INTO plus loin
	// stocker la correspondance nouvelles->anciennes colonnes
	$fields_correspondances = [];
	foreach ($def_origine['field'] as $c => $d) {
		if ($colonne_origine && ($c == $colonne_origine)) {
			// si pas DROP
			if ($colonne_destination) {
				$fields[$colonne_destination] = $opt['field'][$colonne_destination];
				$fields_correspondances[$colonne_destination] = $c;
			}
		} else {
			$fields[$c] = $d;
			$fields_correspondances[$c] = $c;
		}
	}
	// cas de ADD sqlite2 (ajout du champ en fin de table):
	if (!$colonne_origine && $colonne_destination) {
		$fields[$colonne_destination] = $opt['field'][$colonne_destination];
	}

	// key...
	$keys = [];
	foreach ($def_origine['key'] as $c => $d) {
		$c = str_replace($colonne_origine, $colonne_destination, $c);
		$d = str_replace($colonne_origine, $colonne_destination, $d);
		// seulement si on ne supprime pas la colonne !
		if ($d) {
			$keys[$c] = $d;
		}
	}

	// autres keys, on merge
	$keys = array_merge($keys, $opt['key']);
	$queries = [];

	// copier dans destination (si differente de origine), sinon tmp
	$table_copie = ($meme_table) ? $table_tmp : $table_destination;
	$autoinc = (isset($keys['PRIMARY KEY'])
		and $keys['PRIMARY KEY']
		and stripos($keys['PRIMARY KEY'], ',') === false
		and stripos($fields[$keys['PRIMARY KEY']], 'default') === false);

	if (
		$q = _sqlite_requete_create(
			$table_copie,
			$fields,
			$keys,
			$autoinc,
			$temporary = false,
			$ifnotexists = true,
			$serveur
		)
	) {
		$queries[] = $q;
	}


	// 2) y copier les champs qui vont bien
	$champs_dest = join(', ', array_keys($fields_correspondances));
	$champs_ori = join(', ', $fields_correspondances);
	$queries[] = "INSERT INTO $table_copie ($champs_dest) SELECT $champs_ori FROM $table_origine";

	// 3) supprimer la table d'origine
	$queries[] = "DROP TABLE $table_origine";

	// 4) renommer la table temporaire
	// avec le nom de la table destination
	// si necessaire
	if ($meme_table) {
		$queries[] = "ALTER TABLE $table_copie RENAME TO $table_destination";
	}

	// 5) remettre les index !
	foreach ($keys as $k => $v) {
		if ($k == 'PRIMARY KEY') {
		} else {
			// enlever KEY
			$k = substr($k, 4);
			$queries[] = "CREATE INDEX $table_destination" . "_$k ON $table_destination ($v)";
		}
	}


	if (count($queries)) {
		Sqlite::demarrer_transaction($serveur);
		// il faut les faire une par une car $query = join('; ', $queries).";"; ne fonctionne pas
		foreach ($queries as $q) {
			if (!Sqlite::executer_requete($q, $serveur)) {
				spip_log('SQLite : ALTER TABLE table :'
					. " Erreur a l'execution de la requete : $q", 'sqlite.' . _LOG_ERREUR);
				Sqlite::annuler_transaction($serveur);

				return false;
			}
		}
		Sqlite::finir_transaction($serveur);
	}

	return true;
}


/**
 * Nom des fonctions
 *
 * @return array
 */
function _sqlite_ref_fonctions()
{
	$fonctions = [
		'alter' => 'spip_sqlite_alter',
		'count' => 'spip_sqlite_count',
		'countsel' => 'spip_sqlite_countsel',
		'create' => 'spip_sqlite_create',
		'create_base' => 'spip_sqlite_create_base',
		'create_view' => 'spip_sqlite_create_view',
		'date_proche' => 'spip_sqlite_date_proche',
		'delete' => 'spip_sqlite_delete',
		'drop_table' => 'spip_sqlite_drop_table',
		'drop_view' => 'spip_sqlite_drop_view',
		'errno' => 'spip_sqlite_errno',
		'error' => 'spip_sqlite_error',
		'explain' => 'spip_sqlite_explain',
		'fetch' => 'spip_sqlite_fetch',
		'seek' => 'spip_sqlite_seek',
		'free' => 'spip_sqlite_free',
		'hex' => 'spip_sqlite_hex',
		'in' => 'spip_sqlite_in',
		'insert' => 'spip_sqlite_insert',
		'insertq' => 'spip_sqlite_insertq',
		'insertq_multi' => 'spip_sqlite_insertq_multi',
		'listdbs' => 'spip_sqlite_listdbs',
		'multi' => 'spip_sqlite_multi',
		'optimize' => 'spip_sqlite_optimize',
		'query' => 'spip_sqlite_query',
		'quote' => 'spip_sqlite_quote',
		'repair' => 'spip_sqlite_repair',
		'replace' => 'spip_sqlite_replace',
		'replace_multi' => 'spip_sqlite_replace_multi',
		'select' => 'spip_sqlite_select',
		'selectdb' => 'spip_sqlite_selectdb',
		'set_charset' => 'spip_sqlite_set_charset',
		'get_charset' => 'spip_sqlite_get_charset',
		'showbase' => 'spip_sqlite_showbase',
		'showtable' => 'spip_sqlite_showtable',
		'table_exists' => 'spip_sqlite_table_exists',
		'update' => 'spip_sqlite_update',
		'updateq' => 'spip_sqlite_updateq',
		'preferer_transaction' => 'spip_sqlite_preferer_transaction',
		'demarrer_transaction' => 'spip_sqlite_demarrer_transaction',
		'terminer_transaction' => 'spip_sqlite_terminer_transaction',
	];

	// association de chaque nom http d'un charset aux couples sqlite
	// SQLite supporte utf-8 et utf-16 uniquement.
	$charsets = [
		'utf-8' => ['charset' => 'utf8', 'collation' => 'utf8_general_ci'],
		//'utf-16be'=>array('charset'=>'utf16be','collation'=>'UTF-16BE'),// aucune idee de quoi il faut remplir dans es champs la
		//'utf-16le'=>array('charset'=>'utf16le','collation'=>'UTF-16LE')
	];

	$fonctions['charsets'] = $charsets;

	return $fonctions;
}


/**
 * Adapte les déclarations des champs pour SQLite
 *
 * @param string|array $query Déclaration d’un champ ou  liste de déclarations de champs
 * @param bool $autoinc
 * @return mixed
 */
function _sqlite_remplacements_definitions_table($query, $autoinc = false)
{
	// quelques remplacements
	$num = '(\s*\([0-9]*\))?';
	$enum = '(\s*\([^\)]*\))?';

	$remplace = [
		'/enum' . $enum . '/is' => 'VARCHAR(255)',
		'/COLLATE \w+_bin/is' => 'COLLATE BINARY',
		'/COLLATE \w+_ci/is' => 'COLLATE NOCASE',
		'/auto_increment/is' => '',
		'/current_timestamp\(\)/is' => 'CURRENT_TIMESTAMP', // Fix export depuis mariaDB #4374
		'/(timestamp .* )ON .*$/is' => '\\1',
		'/character set \w+/is' => '',
		'/((big|small|medium|tiny)?int(eger)?)' . $num . '\s*unsigned/is' => '\\1 UNSIGNED',
		'/(text\s+not\s+null(\s+collate\s+\w+)?)\s*$/is' => "\\1 DEFAULT ''",
		'/((char|varchar)' . $num . '\s+not\s+null(\s+collate\s+\w+)?)\s*$/is' => "\\1 DEFAULT ''",
		'/(datetime\s+not\s+null)\s*$/is' => "\\1 DEFAULT '0000-00-00 00:00:00'",
		'/(date\s+not\s+null)\s*$/is' => "\\1 DEFAULT '0000-00-00'",
	];

	// pour l'autoincrement, il faut des INTEGER NOT NULL PRIMARY KEY
	$remplace_autocinc = [
		'/(big|small|medium|tiny)?int(eger)?' . $num . '/is' => 'INTEGER'
	];
	// pour les int non autoincrement, il faut un DEFAULT
	$remplace_nonautocinc = [
		'/((big|small|medium|tiny)?int(eger)?' . $num . '\s+not\s+null)\s*$/is' => "\\1 DEFAULT 0",
	];

	if (is_string($query)) {
		$query = preg_replace(array_keys($remplace), $remplace, $query);
		if ($autoinc or preg_match(',AUTO_INCREMENT,is', $query)) {
			$query = preg_replace(array_keys($remplace_autocinc), $remplace_autocinc, $query);
		} else {
			$query = preg_replace(array_keys($remplace_nonautocinc), $remplace_nonautocinc, $query);
			$query = _sqlite_collate_ci($query);
		}
	} elseif (is_array($query)) {
		foreach ($query as $k => $q) {
			$ai = ($autoinc ? $k == $autoinc : preg_match(',AUTO_INCREMENT,is', $q));
			$query[$k] = preg_replace(array_keys($remplace), $remplace, $query[$k]);
			if ($ai) {
				$query[$k] = preg_replace(array_keys($remplace_autocinc), $remplace_autocinc, $query[$k]);
			} else {
				$query[$k] = preg_replace(array_keys($remplace_nonautocinc), $remplace_nonautocinc, $query[$k]);
				$query[$k] = _sqlite_collate_ci($query[$k]);
			}
		}
	}

	return $query;
}

/**
 * Definir la collation d'un champ en fonction de si une collation est deja explicite
 * et du par defaut que l'on veut NOCASE
 *
 * @param string $champ
 * @return string
 */
function _sqlite_collate_ci($champ)
{
	if (stripos($champ, 'COLLATE') !== false) {
		return $champ;
	}
	if (stripos($champ, 'BINARY') !== false) {
		return str_ireplace('BINARY', 'COLLATE BINARY', $champ);
	}
	if (preg_match(',^(char|varchar|(long|small|medium|tiny)?text),i', $champ)) {
		return $champ . ' COLLATE NOCASE';
	}

	return $champ;
}


/**
 * Creer la requete pour la creation d'une table
 * retourne la requete pour utilisation par sql_create() et sql_alter()
 *
 * @param  $nom
 * @param  $champs
 * @param  $cles
 * @param bool $autoinc
 * @param bool $temporary
 * @param bool $_ifnotexists
 * @param string $serveur
 * @param bool $requeter
 * @return bool|string
 */
function _sqlite_requete_create(
	$nom,
	$champs,
	$cles,
	$autoinc = false,
	$temporary = false,
	$_ifnotexists = true,
	$serveur = '',
	$requeter = true
) {
	$query = $keys = $s = $p = '';

	// certains plugins declarent les tables  (permet leur inclusion dans le dump)
	// sans les renseigner (laisse le compilo recuperer la description)
	if (!is_array($champs) || !is_array($cles)) {
		return;
	}

	// sqlite ne gere pas KEY tout court dans une requete CREATE TABLE
	// il faut passer par des create index
	// Il gere par contre primary key !
	// Soit la PK est definie dans les cles, soit dans un champs
	// soit faussement dans les 2 (et dans ce cas, il faut l’enlever à un des 2 endroits !)
	$pk = 'PRIMARY KEY';
	// le champ de cle primaire
	$champ_pk = !empty($cles[$pk]) ? $cles[$pk] : '';

	foreach ($champs as $k => $v) {
		if (false !== stripos($v, $pk)) {
			$champ_pk = $k;
			// on n'en a plus besoin dans field, vu que defini dans key
			$champs[$k] = preg_replace("/$pk/is", '', $champs[$k]);
			break;
		}
	}

	if ($champ_pk) {
		$keys = "\n\t\t$pk ($champ_pk)";
	}
	// Pas de DEFAULT 0 sur les cles primaires en auto-increment
	if (
		isset($champs[$champ_pk])
		and stripos($champs[$champ_pk], 'default 0') !== false
	) {
		$champs[$champ_pk] = trim(str_ireplace('default 0', '', $champs[$champ_pk]));
	}

	$champs = _sqlite_remplacements_definitions_table($champs, $autoinc ? $champ_pk : false);
	foreach ($champs as $k => $v) {
		$query .= "$s\n\t\t$k $v";
		$s = ',';
	}

	$ifnotexists = '';
	if ($_ifnotexists) {
		$version = spip_sqlite_fetch(
			spip_sqlite_query('select sqlite_version() AS sqlite_version', $serveur),
			'',
			$serveur
		);
		if (!function_exists('spip_version_compare')) {
			include_spip('plugins/installer');
		}

		if ($version and spip_version_compare($version['sqlite_version'], '3.3.0', '>=')) {
			$ifnotexists = ' IF NOT EXISTS';
		} else {
			/* simuler le IF EXISTS - version 2 et sqlite < 3.3a */
			$a = spip_sqlite_showtable($nom, $serveur);
			if (isset($a['key']['KEY ' . $nom])) {
				return true;
			}
		}
	}

	$temporary = $temporary ? ' TEMPORARY' : '';
	$q = "CREATE$temporary TABLE$ifnotexists $nom ($query" . ($keys ? ",$keys" : '') . ")\n";

	return $q;
}


/**
 * Retrouver les champs 'timestamp'
 * pour les ajouter aux 'insert' ou 'replace'
 * afin de simuler le fonctionnement de mysql
 *
 * stocke le resultat pour ne pas faire
 * de requetes showtable intempestives
 *
 * @param  $table
 * @param  $couples
 * @param string $desc
 * @param string $serveur
 * @return
 */
function _sqlite_ajouter_champs_timestamp($table, $couples, $desc = '', $serveur = '')
{
	static $tables = [];

	if (!isset($tables[$table])) {
		if (!$desc) {
			$trouver_table = charger_fonction('trouver_table', 'base');
			$desc = $trouver_table($table, $serveur);
			// si pas de description, on ne fait rien, ou on die() ?
			if (!$desc) {
				return $couples;
			}
		}

		// recherche des champs avec simplement 'TIMESTAMP'
		// cependant, il faudra peut etre etendre
		// avec la gestion de DEFAULT et ON UPDATE
		// mais ceux-ci ne sont pas utilises dans le core
		$tables[$table] = ['valeur' => [], 'cite' => [], 'desc' => []];

		$now = _sqlite_func_now(true);
		foreach ($desc['field'] as $k => $v) {
			if (strpos(strtolower(ltrim($v)), 'timestamp') === 0) {
				$tables[$table]['desc'][$k] = $v;
				$tables[$table]['valeur'][$k] = _sqlite_calculer_cite($now, $tables[$table]['desc'][$k]);
			}
		}
	} else {
		$now = _sqlite_func_now(true);
		foreach (array_keys($tables[$table]['desc']) as $k) {
			$tables[$table]['valeur'][$k] = _sqlite_calculer_cite($now, $tables[$table]['desc'][$k]);
		}
	}

	// ajout des champs type 'timestamp' absents
	return array_merge($tables[$table]['valeur'], $couples);
}


/**
 * Renvoyer la liste des versions sqlite disponibles
 * sur le serveur
 *
 * @return array|bool
 */
function spip_versions_sqlite()
{
	return _sqlite_charger_version();
}
