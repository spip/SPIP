<?php

/* *************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Ce fichier contient les fonctions gerant
 * les instructions SQL pour PostgreSQL
 *
 * @package SPIP\Core\SQL\PostgreSQL
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

define('_DEFAULT_DB', 'spip');

// Se connecte et retourne le nom de la fonction a connexion persistante
// A la premiere connexion de l'installation (BD pas precisee)
// si on ne peut se connecter sans la preciser
// on reessaye avec le login comme nom de BD
// et si ca marche toujours pas, avec "spip" (constante ci-dessus)
// si ca ne marche toujours pas, echec.

function req_pg_dist($addr, $port, $login, #[\SensitiveParameter] $pass, $db = '', $prefixe = '') {
	static $last_connect = [];
	if (!extension_loaded('pgsql')) {
		return false;
	}

	// si provient de selectdb
	if (empty($addr) && empty($port) && empty($login) && empty($pass)) {
		foreach (['addr', 'port', 'login', 'pass', 'prefixe'] as $a) {
			${$a} = $last_connect[$a];
		}
	}
	[$host, $p] = array_pad(explode(';', (string) $addr), 2, null);
	$port = $p > 0 ? " port=$p" : '';
	$erreurs = [];
	if ($db) {
		@$link = pg_connect("host=$host$port dbname=$db user=$login password='$pass'", PGSQL_CONNECT_FORCE_NEW);
	} elseif (!@$link = pg_connect("host=$host$port user=$login password='$pass'", PGSQL_CONNECT_FORCE_NEW)) {
		$erreurs[] = pg_last_error();
		if (@$link = pg_connect("host=$host$port dbname=$login user=$login password='$pass'", PGSQL_CONNECT_FORCE_NEW)) {
			$db = $login;
		} else {
			$erreurs[] = pg_last_error();
			$db = _DEFAULT_DB;
			$link = pg_connect("host=$host$port dbname=$db user=$login password='$pass'", PGSQL_CONNECT_FORCE_NEW);
		}
	}
	if (!$link) {
		$erreurs[] = pg_last_error();
		foreach ($erreurs as $e) {
			spip_log('Echec pg_connect. Erreur : ' . $e, 'pg.' . _LOG_HS);
		}

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
	}

	spip_log(
		"Connexion vers $host, base $db, prefixe $prefixe " . ($link ? 'operationnelle' : 'impossible'),
		'pg.' . _LOG_DEBUG
	);

	return $link ? [
		'db' => $db,
		'prefixe' => $prefixe ?: $db,
		'link' => $link,
	] : false;
}

$GLOBALS['spip_pg_functions_1'] = [
	'alter' => 'spip_pg_alter',
	'count' => 'spip_pg_count',
	'countsel' => 'spip_pg_countsel',
	'create' => 'spip_pg_create',
	'create_base' => 'spip_pg_create_base',
	'create_view' => 'spip_pg_create_view',
	'date_proche' => 'spip_pg_date_proche',
	'delete' => 'spip_pg_delete',
	'drop_table' => 'spip_pg_drop_table',
	'drop_view' => 'spip_pg_drop_view',
	'errno' => 'spip_pg_errno',
	'error' => 'spip_pg_error',
	'explain' => 'spip_pg_explain',
	'fetch' => 'spip_pg_fetch',
	'seek' => 'spip_pg_seek',
	'free' => 'spip_pg_free',
	'hex' => 'spip_pg_hex',
	'in' => 'spip_pg_in',
	'insert' => 'spip_pg_insert',
	'insertq' => 'spip_pg_insertq',
	'insertq_multi' => 'spip_pg_insertq_multi',
	'listdbs' => 'spip_pg_listdbs',
	'multi' => 'spip_pg_multi',
	'optimize' => 'spip_pg_optimize',
	'query' => 'spip_pg_query',
	'quote' => 'spip_pg_quote',
	'replace' => 'spip_pg_replace',
	'replace_multi' => 'spip_pg_replace_multi',
	'select' => 'spip_pg_select',
	'selectdb' => 'spip_pg_selectdb',
	'set_connect_charset' => 'spip_pg_set_connect_charset',
	'showbase' => 'spip_pg_showbase',
	'showtable' => 'spip_pg_showtable',
	'update' => 'spip_pg_update',
	'updateq' => 'spip_pg_updateq',
];

// Par ou ca passe une fois les traductions faites
function spip_pg_trace_query($query, $serveur = '') {
	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower((string) $serveur) : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	if (isset($_GET['var_profile'])) {
		include_spip('public/tracer');
		$t = trace_query_start();
		$e = '';
	} else {
		$t = 0;
	}

	$connexion['last'] = $query;
	$r = spip_pg_query_simple($link, $query);

	// Log de l'erreur eventuelle
	if ($e = spip_pg_errno($serveur)) {
		$e .= spip_pg_error($query, $serveur);
	} // et du fautif
	return $t ? trace_query_end($query, $t, $r, $e, $serveur) : $r;
}

// Fonction de requete generale quand on est sur que c'est SQL standard.
// Elle change juste le noms des tables ($table_prefix) dans le FROM etc

function spip_pg_query($query, $serveur = '', $requeter = true) {
	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower((string) $serveur) : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	if (preg_match('/\s(SET|VALUES|WHERE|DATABASE)\s/i', (string) $query, $regs)) {
		$suite = strstr((string) $query, (string) $regs[0]);
		$query = substr((string) $query, 0, -strlen($suite));
	} else {
		$suite = '';
	}
	$query = preg_replace('/([,\s])spip_/', '\1' . $prefixe . '_', (string) $query) . $suite;

	// renvoyer la requete inerte si demandee
	if (!$requeter) {
		return $query;
	}

	return spip_pg_trace_query($query, $serveur);
}

function spip_pg_query_simple($link, $query) {
	#spip_log(var_export($query,true), 'pg.'._LOG_DEBUG);
	return pg_query($link, $query);
}

/*
 * Retrouver les champs 'timestamp'
 * pour les ajouter aux 'insert' ou 'replace'
 * afin de simuler le fonctionnement de mysql
 *
 * stocke le resultat pour ne pas faire
 * de requetes showtable intempestives
 */
function spip_pg_ajouter_champs_timestamp($table, $couples, $desc = '', $serveur = '') {
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
		$tables[$table] = [];
		foreach ($desc['field'] as $k => $v) {
			$v = strtolower(ltrim((string) $v));
			// ne pas ajouter de timestamp now() si un default est specifie
			if (str_starts_with($v, 'timestamp') && !str_contains($v, 'default')) {
				$tables[$table][] = $k;
			}
		}
	}

	// ajout des champs type 'timestamp' absents
	foreach ($tables[$table] as $maj) {
		if (!array_key_exists($maj, $couples)) {
			$couples[$maj] = 'NOW()';
		}
	}

	return $couples;
}


// Alter en PG ne traite pas les index
function spip_pg_alter($query, $serveur = '', $requeter = true) {
	// il faudrait une regexp pour eviter de spliter ADD PRIMARY KEY (colA, colB)
	// tout en cassant en deux alter distincts "ADD PRIMARY KEY (colA, colB), ADD INDEX (chose)"...
	// ou revoir l'api de sql_alter en creant un
	// sql_alter_table($table,array($actions));
	if (!preg_match('/\s*((\s*IGNORE)?\s*TABLE\s*([^\s]*))\s*(.*)?/is', (string) $query, $regs)) {
		spip_log("$query mal comprise", 'pg.' . _LOG_ERREUR);

		return false;
	}
	$debut = $regs[1];
	$table = $regs[3];
	$suite = $regs[4];
	$todo = explode(',', $suite);
	// on remet les morceaux dechires ensembles... que c'est laid !
	$todo2 = [];
	$i = 0;
	$ouverte = false;
	while ($do = array_shift($todo)) {
		$todo2[$i] = isset($todo2[$i]) ? $todo2[$i] . ',' . $do : $do;
		$o = (str_contains($do, '('));
		$f = (str_contains($do, ')'));
		if ($o && !$f) {
			$ouverte = true;
		} elseif ($f) {
			$ouverte = false;
		}
		if (!$ouverte) {
			$i++;
		}
	}
	$todo = $todo2;
	$query = $debut . ' ' . array_shift($todo);

	if (!preg_match('/^\s*(IGNORE\s*)?TABLE\s+(\w+)\s+(ADD|DROP|CHANGE|MODIFY|RENAME)\s*(.*)$/is', $query, $r)) {
		spip_log("$query incompris", 'pg.' . _LOG_ERREUR);
	} else {
		if ($r[1]) {
			spip_log("j'ignore IGNORE dans $query", 'pg.' . _LOG_AVERTISSEMENT);
		}
		$f = 'spip_pg_alter_' . strtolower($r[3]);
		if (function_exists($f)) {
			$f($r[2], $r[4], $serveur, $requeter);
		} else {
			spip_log("$query non prevu", 'pg.' . _LOG_ERREUR);
		}
	}
	// Alter a plusieurs args. Faudrait optimiser.
	if ($todo) {
		spip_pg_alter("TABLE $table " . implode(',', $todo));
	}
}

function spip_pg_alter_change($table, $arg, $serveur = '', $requeter = true) {
	if (!preg_match('/^`?(\w+)`?\s+`?(\w+)`?\s+(.*?)\s*(DEFAULT .*?)?(NOT\s+NULL)?\s*(DEFAULT .*?)?$/i', (string) $arg, $r)) {
		spip_log("alter change: $arg  incompris", 'pg.' . _LOG_ERREUR);
	} else {
		[, $old, $new, $type, $default, $null, $def2] = $r;
		$actions = ["ALTER $old TYPE " . mysql2pg_type($type)];
		$actions[] = $null ? "ALTER $old SET NOT NULL" : "ALTER $old DROP NOT NULL";

		if ($d = ($default ?: $def2)) {
			$actions[] = "ALTER $old SET $d";
		} else {
			$actions[] = "ALTER $old DROP DEFAULT";
		}

		spip_pg_query("ALTER TABLE $table " . implode(', ', $actions));

		if ($old !== $new) {
			spip_pg_query("ALTER TABLE $table RENAME $old TO $new", $serveur);
		}
	}
}

function spip_pg_alter_add($table, $arg, $serveur = '', $requeter = true) {
	$nom_index = null;
	if (!preg_match('/^(COLUMN|INDEX|KEY|PRIMARY\s+KEY|)\s*(.*)$/', (string) $arg, $r)) {
		spip_log("alter add $arg  incompris", 'pg.' . _LOG_ERREUR);

		return null;
	}
	if (!$r[1] || $r[1] == 'COLUMN') {
		preg_match('/`?(\w+)`?(.*)/', $r[2], $m);
		if (preg_match('/^(.*)(BEFORE|AFTER|FIRST)(.*)$/is', $m[2], $n)) {
			$m[2] = $n[1];
		}

		return spip_pg_query("ALTER TABLE $table ADD " . $m[1] . ' ' . mysql2pg_type($m[2]), $serveur, $requeter);
	} elseif ($r[1][0] == 'P') {
		// la primary peut etre sur plusieurs champs
		$r[2] = trim(str_replace('`', '', $r[2]));
		$m = ($r[2][0] == '(') ? substr($r[2], 1, -1) : $r[2];

		return spip_pg_query(
			"ALTER TABLE $table ADD CONSTRAINT $table" . '_pkey PRIMARY KEY (' . $m . ')',
			$serveur,
			$requeter
		);
	} else {
		preg_match('/([^\s,]*)\s*(.*)?/', $r[2], $m);
		// peut etre "(colonne)" ou "nom_index (colonnes)"
		// bug potentiel si qqn met "(colonne, colonne)"
		//
		// nom_index (colonnes)
		if ($m[2]) {
			$colonnes = substr($m[2], 1, -1);
			$nom_index = $m[1];
		} else {
			// (colonne)
			if ($m[1][0] == '(') {
				$colonnes = substr($m[1], 1, -1);
				if (str_contains(',', $colonnes)) {
					spip_log('PG : Erreur, impossible de creer un index sur plusieurs colonnes'
						. " sans qu'il ait de nom ($table, ($colonnes))", 'pg.' . _LOG_ERREUR);
				} else {
					$nom_index = $colonnes;
				}
			} // nom_index
			else {
				$nom_index = $colonnes = $m[1];
			}
		}

		return spip_pg_create_index($nom_index, $table, $colonnes, $serveur, $requeter);
	}
}

function spip_pg_alter_drop($table, $arg, $serveur = '', $requeter = true) {
	if (!preg_match('/^(COLUMN|INDEX|KEY|PRIMARY\s+KEY|)\s*`?(\w*)`?/', (string) $arg, $r)) {
		spip_log("alter drop: $arg  incompris", 'pg.' . _LOG_ERREUR);
	} else {
		if (!$r[1] || $r[1] == 'COLUMN') {
			return spip_pg_query("ALTER TABLE $table DROP " . $r[2], $serveur);
		} elseif ($r[1][0] == 'P') {
			return spip_pg_query("ALTER TABLE $table DROP CONSTRAINT $table" . '_pkey', $serveur);
		} else {
			return spip_pg_query('DROP INDEX ' . $table . '_' . $r[2], $serveur);
		}
	}
}

function spip_pg_alter_modify($table, $arg, $serveur = '', $requeter = true) {
	if (!preg_match('/^`?(\w+)`?\s+(.*)$/', (string) $arg, $r)) {
		spip_log("alter modify: $arg  incompris", 'pg.' . _LOG_ERREUR);
	} else {
		return spip_pg_alter_change($table, $r[1] . ' ' . $arg, $serveur = '', $requeter = true);
	}
}

// attention (en pg) :
// - alter table A rename to X = changer le nom de la table
// - alter table A rename X to Y = changer le nom de la colonne X en Y
// pour l'instant, traiter simplement RENAME TO X
function spip_pg_alter_rename($table, $arg, $serveur = '', $requeter = true) {
	$rename = '';
	// si TO, mais pas au debut
	if (!stripos((string) $arg, 'TO ')) {
		$rename = $arg;
	} elseif (preg_match('/^(TO)\s*`?(\w*)`?/', (string) $arg, $r)) {
		$rename = $r[2];
	} else {
		spip_log("alter rename: $arg  incompris", 'pg.' . _LOG_ERREUR);
	}

	return $rename ? spip_pg_query("ALTER TABLE $table RENAME TO $rename") : false;
}


/**
 * Fonction de creation d'un INDEX
 *
 * @param string $nom : nom de l'index
 * @param string $table : table sql de l'index
 * @param string /array $champs : liste de champs sur lesquels s'applique l'index
 * @param string $serveur : nom de la connexion sql utilisee
 * @param bool $requeter : true pour executer la requete ou false pour retourner le texte de la requete
 *
 * @return bool ou requete
 */
function spip_pg_create_index($nom, $table, $champs, $serveur = '', $requeter = true) {
	if (!($nom || $table || $champs)) {
		spip_log(
			"Champ manquant pour creer un index pg ($nom, $table, (" . @implode(',', $champs) . '))',
			'pg.' . _LOG_ERREUR
		);

		return false;
	}

	$nom = str_replace('`', '', $nom);
	$champs = str_replace('`', '', (string) $champs);

	// PG ne differentie pas noms des index en fonction des tables
	// il faut donc creer des noms uniques d'index pour une base pg
	$nom = $table . '_' . $nom;
	// enlever d'eventuelles parentheses deja presentes sur champs
	if (!is_array($champs)) {
		if ($champs[0] == '(') {
			$champs = substr($champs, 1, -1);
		}
		$champs = [$champs];
	}
	$query = "CREATE INDEX $nom ON $table (" . implode(',', $champs) . ')';
	if (!$requeter) {
		return $query;
	}

	return spip_pg_query($query, $serveur, $requeter);
}


function spip_pg_explain($query, $serveur = '', $requeter = true) {
	if (!str_starts_with(ltrim((string) $query), 'SELECT')) {
		return [];
	}
	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower((string) $serveur) : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	if (preg_match('/\s(SET|VALUES|WHERE)\s/i', (string) $query, $regs)) {
		$suite = strstr((string) $query, (string) $regs[0]);
		$query = substr((string) $query, 0, -strlen($suite));
	} else {
		$suite = '';
	}
	$query = 'EXPLAIN ' . preg_replace('/([,\s])spip_/', '\1' . $prefixe . '_', (string) $query) . $suite;

	if (!$requeter) {
		return $query;
	}
	$r = spip_pg_query_simple($link, $query);

	return spip_pg_fetch($r, null, $serveur);
}


/**
 * Sélectionne une base de données
 *
 * @param string $db
 *     Nom de la base à utiliser
 * @param string $serveur
 *     Nom du connecteur
 * @param bool $requeter
 *     Inutilisé
 *
 * @return bool|string
 *     - Nom de la base en cas de succès.
 *     - False en cas d'erreur.
 **/
function spip_pg_selectdb($db, $serveur = '', $requeter = true) {
	// se connecter a la base indiquee
	// avec les identifiants connus
	$index = $serveur ? strtolower($serveur) : 0;

	if (($link = spip_connect_db('', '', '', '', $db, 'pg', '', '')) && (($db == $link['db']) && $GLOBALS['connexions'][$index] = $link)) {
		return $db;
	}

	return false;
}

// Qu'une seule base pour le moment

function spip_pg_listdbs($serveur) {
	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower((string) $serveur) : 0];
	$link = $connexion['link'];
	$dbs = [];
	$res = spip_pg_query_simple($link, 'select * From pg_database');
	while ($row = pg_fetch_array($res, null, PGSQL_NUM)) {
		$dbs[] = reset($row);
	}

	return $dbs;
}

function spip_pg_select(
	$select,
	$from,
	$where = '',
	$groupby = [],
	$orderby = '',
	$limit = '',
	$having = '',
	$serveur = '',
	$requeter = true
) {

	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower((string) $serveur) : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	$limit = preg_match('/^\s*((\d+),)?\s*(\d+)\s*$/', (string) $limit, $limatch);
	if ($limit) {
		$offset = $limatch[2];
		$count = $limatch[3];
	}

	$select = spip_pg_frommysql($select);

	// si pas de tri explicitement demande, le GROUP BY ne
	// contient que la clef primaire.
	// lui ajouter alors le champ de tri par defaut
	if (preg_match('/FIELD\(([a-z]+\.[a-z]+),/i', (string) $orderby[0], $groupbyplus)) {
		$groupby[] = $groupbyplus[1];
	}

	$orderby = spip_pg_orderby($orderby, $select);

	if ($having && is_array($having)) {
		$having = implode("\n\tAND ", array_map('calculer_pg_where', $having));
	}
	$from = spip_pg_from($from, $prefixe);
	$query = 'SELECT ' . $select
		. ($from ? "\nFROM $from" : '')
		. ($where ? "\nWHERE " . (is_array($where) ? implode(
			"\n\tAND ",
			array_map('calculer_pg_where', $where)
		) : (calculer_pg_where($where))) : (''))
		. spip_pg_groupby($groupby, $from, $select)
		. ($having ? "\nHAVING $having" : '')
		. ($orderby ? ("\nORDER BY $orderby") : '')
		. ($limit ? " LIMIT $count" . ($offset ? " OFFSET $offset" : '') : (''));

	// renvoyer la requete inerte si demandee
	if ($requeter === false) {
		return $query;
	}

	$r = spip_pg_trace_query($query, $serveur);

	return $r ?: $query;
;
}

// Le traitement des prefixes de table dans un Select se limite au FROM
// car le reste de la requete utilise les alias (AS) systematiquement

function spip_pg_from($from, $prefixe) {
	if (is_array($from)) {
		$from = spip_pg_select_as($from);
	}

	return $prefixe ? preg_replace('/(\b)spip_/', '\1' . $prefixe . '_', (string) $from) : $from;
}

function spip_pg_orderby($order, $select) {
	$res = [];
	$arg = (is_array($order) ? $order : preg_split('/\s*,\s*/', (string) $order));

	foreach ($arg as $v) {
		$res[] = preg_match('/(case\s+.*?else\s+0\s+end)\s*AS\s+' . $v . '/', (string) $select, $m) ? $m[1] : $v;
	}

	return spip_pg_frommysql(implode(',', $res));
}

// Conversion a l'arrach' des jointures MySQL en jointures PG
// A refaire pour tirer parti des possibilites de PG et de MySQL5
// et pour enlever les repetitions (sans incidence de perf, mais ca fait sale)

function spip_pg_groupby($groupby, $from, $select) {
	$join = strpos((string) $from, ',');
	// ismplifier avant de decouper
	if (is_string($select)) { // fct SQL sur colonne et constante apostrophee ==> la colonne
	$select = preg_replace('/\w+\(\s*([^(),\']*),\s*\'[^\']*\'[^)]*\)/', '\\1', $select);
	}

	if ($join || $groupby) {
		$join = is_array($select) ? $select : explode(', ', (string) $select);
	}
	if ($join) {
		// enlever les 0 as points, '', ...
		foreach ($join as $k => $v) {
			$v = str_replace('DISTINCT ', '', (string) $v);
			// fct SQL sur colonne et constante apostrophee ==> la colonne
			$v = preg_replace('/\w+\(\s*([^(),\']*),\s*\'[^\']*\'[^)]*\)/', '\\1', $v);
			$v = preg_replace('/CAST\(\s*([^(),\' ]*\s+)as\s*\w+\)/', '\\1', $v);
			// resultat d'agregat ne sont pas a mettre dans le groupby
			$v = preg_replace('/(SUM|COUNT|MAX|MIN|UPPER)\([^)]+\)(\s*AS\s+\w+)\s*,?/i', '', $v);
			// idem sans AS (fetch numerique)
			$v = preg_replace('/(SUM|COUNT|MAX|MIN|UPPER)\([^)]+\)\s*,?/i', '', $v);
			// des AS simples : on garde le cote droit du AS
			$v = preg_replace('/^.*\sAS\s+(\w+)\s*$/i', '\\1', $v);
			// ne reste plus que les vrais colonnes, ou des constantes a virer
			if (preg_match(',^[\'"],', $v) || is_numeric($v)) {
				unset($join[$k]);
			} else {
				$join[$k] = trim($v);
			}
		}
		$join = array_diff($join, ['']);
		$join = implode(',', $join);
	}
	if (is_array($groupby)) {
		$groupby = implode(',', $groupby);
	}
	if ($join) {
		$groupby = $groupby ? "$groupby, $join" : $join;
	}
	if (!$groupby) {
		return '';
	}

	$groupby = spip_pg_frommysql($groupby);
	// Ne pas mettre dans le Group-By des valeurs numeriques
	// issue de prepare_recherche
	$groupby = preg_replace('/^\s*\d+\s+AS\s+\w+\s*,?\s*/i', '', (string) $groupby);
	$groupby = preg_replace('/,\s*\d+\s+AS\s+\w+\s*/i', '', $groupby);
	$groupby = preg_replace('/\s+AS\s+\w+\s*/i', '', $groupby);

	return "\nGROUP BY $groupby";
}

// Conversion des operateurs MySQL en PG
// IMPORTANT: "0+X" est vu comme conversion numerique du debut de X
// Les expressions de date ne sont pas gerees au-dela de 3 ()
// Le 'as' du 'CAST' est en minuscule pour echapper au dernier preg_replace
// de spip_pg_groupby.
// A ameliorer.

function spip_pg_frommysql($arg) {
	if (is_array($arg)) {
		$arg = implode(', ', $arg);
	}

	$res = spip_pg_fromfield($arg);

	$res = preg_replace('/\brand[(][)]/i', 'random()', (string) $res);

	$res = preg_replace(
		'/\b0\.0[+]([a-zA-Z0-9_.]+)\s*/',
		'CAST(substring(\1, \'^ *[0-9.]+\') as float)',
		$res
	);
	$res = preg_replace(
		'/\b0[+]([a-zA-Z0-9_.]+)\s*/',
		'CAST(substring(\1, \'^ *[0-9]+\') as int)',
		$res
	);
	$res = preg_replace(
		'/\bconv[(]([^,]*)[^)]*[)]/i',
		'CAST(substring(\1, \'^ *[0-9]+\') as int)',
		$res
	);

	$res = preg_replace(
		'/UNIX_TIMESTAMP\s*[(]\s*[)]/',
		' EXTRACT(epoch FROM NOW())',
		$res
	);

	// la fonction md5(integer) n'est pas connu en pg
	// il faut donc forcer les types en text (cas de md5(id_article))
	$res = preg_replace(
		'/md5\s*[(]([^)]*)[)]/i',
		'MD5(CAST(\1 AS text))',
		$res
	);

	$res = preg_replace(
		'/UNIX_TIMESTAMP\s*[(]([^)]*)[)]/',
		' EXTRACT(epoch FROM \1)',
		$res
	);

	$res = preg_replace(
		'/\bDAYOFMONTH\s*[(]([^()]*([(][^()]*[)][^()]*)*[^)]*)[)]/',
		' EXTRACT(day FROM \1)',
		$res
	);

	$res = preg_replace(
		'/\bMONTH\s*[(]([^()]*([(][^)]*[)][^()]*)*[^)]*)[)]/',
		' EXTRACT(month FROM \1)',
		$res
	);

	$res = preg_replace(
		'/\bYEAR\s*[(]([^()]*([(][^)]*[)][^()]*)*[^)]*)[)]/',
		' EXTRACT(year FROM \1)',
		$res
	);

	$res = preg_replace(
		'/TO_DAYS\s*[(]([^()]*([(][^)]*[)][()]*)*)[)]/',
		' EXTRACT(day FROM \1 - \'0001-01-01\')',
		$res
	);

	$res = preg_replace('/(EXTRACT[(][^ ]* FROM *)"([^"]*)"/', '\1\'\2\'', $res);

	$res = preg_replace('/DATE_FORMAT\s*[(]([^,]*),\s*\'%Y%m%d\'[)]/', 'to_char(\1, \'YYYYMMDD\')', $res);

	$res = preg_replace('/DATE_FORMAT\s*[(]([^,]*),\s*\'%Y%m\'[)]/', 'to_char(\1, \'YYYYMM\')', $res);

	$res = preg_replace('/DATE_SUB\s*[(]([^,]*),/', '(\1 -', $res);
	$res = preg_replace('/DATE_ADD\s*[(]([^,]*),/', '(\1 +', $res);
	$res = preg_replace('/INTERVAL\s+(\d+\s+\w+)/', 'INTERVAL \'\1\'', $res);
	$res = preg_replace('/([+<>-]=?)\s*(\'\d+-\d+-\d+\s+\d+:\d+(:\d+)\')/', '\1 timestamp \2', $res);
	$res = preg_replace('/(\'\d+-\d+-\d+\s+\d+:\d+:\d+\')\s*([+<>-]=?)/', 'timestamp \1 \2', $res);

	$res = preg_replace('/([+<>-]=?)\s*(\'\d+-\d+-\d+\')/', '\1 timestamp \2', $res);
	$res = preg_replace('/(\'\d+-\d+-\d+\')\s*([+<>-]=?)/', 'timestamp \1 \2', $res);

	$res = preg_replace('/(timestamp .\d+)-00-/', '\1-01-', $res);
	$res = preg_replace('/(timestamp .\d+-\d+)-00/', '\1-01', $res);
# correct en theorie mais produit des debordements arithmetiques
#	$res = preg_replace("/(EXTRACT[(][^ ]* FROM *)(timestamp *'[^']*' *[+-] *timestamp *'[^']*') *[)]/", '\2', $res);
	$res = preg_replace("/(EXTRACT[(][^ ]* FROM *)('[^']*')/", '\1 timestamp \2', $res);
	$res = preg_replace('/\sLIKE\s+/', ' ILIKE ', $res);

	return str_replace('REGEXP', '~', $res);
}

function spip_pg_fromfield($arg) {
	while (preg_match('/^(.*?)FIELD\s*\(([^,]*)((,[^,)]*)*)\)/', (string) $arg, $m)) {
		preg_match_all('/,([^,]*)/', $m[3], $r, PREG_PATTERN_ORDER);
		$res = '';
		$n = 0;
		$index = $m[2];
		foreach ($r[1] as $v) {
			$n++;
			$res .= "\nwhen $index=$v then $n";
		}
		$arg = $m[1] . "case $res else 0 end "
			. substr((string) $arg, strlen($m[0]));
	}

	return $arg;
}

function calculer_pg_where($v) {
	if (!is_array($v)) {
		return spip_pg_frommysql($v);
	}

	$op = str_replace('REGEXP', '~', (string) array_shift($v));
	if (!($n = count($v))) {
		return $op;
	} else {
		$arg = calculer_pg_where(array_shift($v));
		if ($n == 1) {
			return "$op($arg)";
		} else {
			$arg2 = calculer_pg_where(array_shift($v));
			if ($n == 2) {
				return "($arg $op $arg2)";
			} else {
				return "($arg $op ($arg2) : $v[0])";
			}
		}
	}
}


function calculer_pg_expression($expression, $v, $join = 'AND') {
	if (empty($v)) {
		return '';
	}

	$exp = "\n$expression ";

	if (!is_array($v)) {
		$v = [$v];
	}

	if (strtoupper((string) $join) === 'AND') {
		return $exp . implode("\n\t$join ", array_map('calculer_pg_where', $v));
	} else {
		return $exp . implode($join, $v);
	}
}

function spip_pg_select_as($args) {
	$argsas = '';
	foreach ($args as $k => $v) {
		if (str_ends_with((string) $k, '@')) {
			// c'est une jointure qui se refere au from precedent
			// pas de virgule
			$argsas .= '  ' . $v;
		} else {
			$as = '';
			//  spip_log("$k : $v", _LOG_DEBUG);
			if (!is_numeric($k)) {
				if (preg_match('/\.(.*)$/', (string) $k, $r)) {
					$v = $k;
				} elseif ($v != $k) {
					$p = strpos((string) $v, ' ');
					if ($p) {
						$v = substr((string) $v, 0, $p) . " AS $k" . substr((string) $v, $p);
					} else {
						$as = " AS $k";
					}
				}
			}
			// spip_log("subs $k : $v avec $as", _LOG_DEBUG);
			// if (strpos($v, 'JOIN') === false)  $argsas .= ', ';
			$argsas .= ', ' . $v . $as;
		}
	}

	return substr($argsas, 2);
}

function spip_pg_fetch($res, $t = '', $serveur = '', $requeter = true) {

	if ($res) {
		$res = pg_fetch_array($res, null, PGSQL_ASSOC);
	}

	return $res;
}

function spip_pg_seek($r, $row_number, $serveur = '', $requeter = true) {
	if ($r) {
		return pg_result_seek($r, $row_number);
	}
}


function spip_pg_countsel(
	$from = [],
	$where = [],
	$groupby = [],
	$having = [],
	$serveur = '',
	$requeter = true
) {
	$c = $groupby ? 'DISTINCT ' . (is_string($groupby) ? $groupby : implode(',', $groupby)) : ('*');
	$r = spip_pg_select("COUNT($c)", $from, $where, '', '', '', $having, $serveur, $requeter);
	if (!$requeter) {
		return $r;
	}
	if (!is_resource($r)) {
		return 0;
	}
	[$c] = pg_fetch_array($r, null, PGSQL_NUM);

	return $c;
}

function spip_pg_count($res, $serveur = '', $requeter = true) {
	return $res ? pg_num_rows($res) : 0;
}

function spip_pg_free($res, $serveur = '', $requeter = true) {
	// rien a faire en postgres
}

function spip_pg_delete($table, $where = '', $serveur = '', $requeter = true) {

	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower((string) $serveur) : 0];
	$table = prefixer_table_spip($table, $connexion['prefixe']);

	$query = calculer_pg_expression('DELETE FROM', $table, ',')
		. calculer_pg_expression('WHERE', $where, 'AND');

	// renvoyer la requete inerte si demandee
	if (!$requeter) {
		return $query;
	}

	$res = spip_pg_trace_query($query, $serveur);
	if ($res) {
		return pg_affected_rows($res);
	} else {
		return false;
	}
}

function spip_pg_insert($table, $champs, $valeurs, $desc = [], $serveur = '', $requeter = true) {
	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower((string) $serveur) : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];

	if (!$desc) {
		$desc = description_table($table, $serveur);
	}
	$seq = spip_pg_sequence($table, true);
	// si pas de cle primaire dans l'insertion, renvoyer curval
	if (!preg_match(",\b$seq\b,", (string) $champs)) {
		$seq = spip_pg_sequence($table);
		$seq = prefixer_table_spip($seq, $prefixe);
		$seq = "currval('$seq')";
	}

	$table = prefixer_table_spip($table, $prefixe);
	$ret = $seq ? " RETURNING $seq" : ('');
	$ins = (strlen((string) $champs) < 3)
		? ' DEFAULT VALUES'
		: "$champs VALUES $valeurs";
	$q = "INSERT INTO $table $ins $ret";
	if (!$requeter) {
		return $q;
	}
	$connexion['last'] = $q;
	$r = spip_pg_query_simple($link, $q);
#	spip_log($q,'pg.'._LOG_DEBUG);
	if ($r) {
		if (!$ret) {
			return 0;
		}
		if ($r2 = pg_fetch_array($r, null, PGSQL_NUM)) {
			return $r2[0];
		}
	}

	return false;
}

function spip_pg_insertq($table, $couples = [], $desc = [], $serveur = '', $requeter = true) {

	if (!$desc) {
		$desc = description_table($table, $serveur);
	}
	if (!$desc) {
		die("$table insertion sans description");
	}
	$fields = $desc['field'];

	foreach ($couples as $champ => $val) {
		$couples[$champ] = spip_pg_cite($val, $fields[$champ]);
	}

	// recherche de champs 'timestamp' pour mise a jour auto de ceux-ci
	$couples = spip_pg_ajouter_champs_timestamp($table, $couples, $desc, $serveur);

	return spip_pg_insert(
		$table,
		'(' . implode(',', array_keys($couples)) . ')',
		'(' . implode(',', $couples) . ')',
		$desc,
		$serveur,
		$requeter
	);
}


function spip_pg_insertq_multi($table, $tab_couples = [], $desc = [], $serveur = '', $requeter = true) {

	if (!$desc) {
		$desc = description_table($table, $serveur);
	}
	if (!$desc) {
		die("$table insertion sans description");
	}
	$fields = $desc['field'] ?? [];

	// recherche de champs 'timestamp' pour mise a jour auto de ceux-ci
	// une premiere fois pour ajouter maj dans les cles
	$c = $tab_couples[0] ?? [];
	$les_cles = spip_pg_ajouter_champs_timestamp($table, $c, $desc, $serveur);

	$cles = '(' . implode(',', array_keys($les_cles)) . ')';
	$valeurs = [];
	foreach ($tab_couples as $couples) {
		foreach ($couples as $champ => $val) {
			$couples[$champ] = spip_pg_cite($val, $fields[$champ]);
		}
		// recherche de champs 'timestamp' pour mise a jour auto de ceux-ci
		$couples = spip_pg_ajouter_champs_timestamp($table, $couples, $desc, $serveur);

		$valeurs[] = '(' . implode(',', $couples) . ')';
	}
	$valeurs = implode(', ', $valeurs);

	return spip_pg_insert($table, $cles, $valeurs, $desc, $serveur, $requeter);
}


function spip_pg_update($table, $couples, $where = '', $desc = '', $serveur = '', $requeter = true) {

	if (!$couples) {
		return;
	}
	$connexion = $GLOBALS['connexions'][$serveur ? strtolower((string) $serveur) : 0];
	$table = prefixer_table_spip($table, $connexion['prefixe']);

	// recherche de champs 'timestamp' pour mise a jour auto de ceux-ci
	$couples = spip_pg_ajouter_champs_timestamp($table, $couples, $desc, $serveur);

	$set = [];
	foreach ($couples as $champ => $val) {
		$set[] = $champ . '=' . $val;
	}

	$query = calculer_pg_expression('UPDATE', $table, ',')
		. calculer_pg_expression('SET', $set, ',')
		. calculer_pg_expression('WHERE', $where, 'AND');

	// renvoyer la requete inerte si demandee
	if (!$requeter) {
		return $query;
	}

	return spip_pg_trace_query($query, $serveur);
}

// idem, mais les valeurs sont des constantes a mettre entre apostrophes
// sauf les expressions de date lorsqu'il s'agit de fonctions SQL (NOW etc)
function spip_pg_updateq($table, $couples, $where = '', $desc = [], $serveur = '', $requeter = true) {
	if (!$couples) {
		return;
	}
	if (!$desc) {
		$desc = description_table($table, $serveur);
	}
	$fields = $desc['field'];
	foreach ($couples as $k => $val) {
		$couples[$k] = spip_pg_cite($val, $fields[$k]);
	}

	return spip_pg_update($table, $couples, $where, $desc, $serveur, $requeter);
}


function spip_pg_replace($table, $values, $desc, $serveur = '', $requeter = true) {
	if (!$values) {
		spip_log("replace vide $table", 'pg.' . _LOG_AVERTISSEMENT);

		return 0;
	}
	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower((string) $serveur) : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];

	if (!$desc) {
		$desc = description_table($table, $serveur);
	}
	if (!$desc) {
		die("$table insertion sans description");
	}
	$prim = $desc['key']['PRIMARY KEY'];
	$ids = preg_split('/,\s*/', (string) $prim);
	$noprims = $prims = [];
	foreach ($values as $k => $v) {
		$values[$k] = $v = spip_pg_cite($v, $desc['field'][$k]);

		if (!in_array($k, $ids)) {
			$noprims[$k] = "$k=$v";
		} else {
			$prims[$k] = "$k=$v";
		}
	}

	// recherche de champs 'timestamp' pour mise a jour auto de ceux-ci
	$values = spip_pg_ajouter_champs_timestamp($table, $values, $desc, $serveur);

	$where = implode(' AND ', $prims);
	if (!$where) {
		return spip_pg_insert(
			$table,
			'(' . implode(',', array_keys($values)) . ')',
			'(' . implode(',', $values) . ')',
			$desc,
			$serveur
		);
	}
	$couples = implode(',', $noprims);

	$seq = spip_pg_sequence($table);
	$table = prefixer_table_spip($table, $prefixe);
	$seq = prefixer_table_spip($seq, $prefixe);

	$connexion['last'] = $q = "UPDATE $table SET $couples WHERE $where";
	if ($couples) {
		$couples = spip_pg_query_simple($link, $q);
#	  spip_log($q,'pg.'._LOG_DEBUG);
		if (!$couples) {
			return false;
		}
		$couples = pg_affected_rows($couples);
	}
	if (!$couples) {
		$ret = $seq ? " RETURNING nextval('$seq') < $prim" :
			('');
		$connexion['last'] = $q = "INSERT INTO $table (" . implode(',', array_keys($values)) . ') VALUES (' . implode(
			',',
			$values
		) . ")$ret";
		$couples = spip_pg_query_simple($link, $q);
		if (!$couples) {
			return false;
		} elseif ($ret) {
			$r = pg_fetch_array($couples, null, PGSQL_NUM);
			if ($r[0]) {
				$connexion['last'] = $q = "SELECT setval('$seq', $prim) from $table";
				// Le code de SPIP met parfois la sequence a 0 (dans l'import)
				// MySQL n'en dit rien, on fait pareil pour PG
				$r = @pg_query($link, $q);
			}
		}
	}

	return $couples;
}


function spip_pg_replace_multi($table, $tab_couples, $desc = [], $serveur = '', $requeter = true) {
	$retour = null;
	// boucler pour traiter chaque requete independemment
	foreach ($tab_couples as $couples) {
		$retour = spip_pg_replace($table, $couples, $desc, $serveur, $requeter);
	}

	// renvoie le dernier id
	return $retour;
}


// Donne la sequence eventuelle associee a une table
// Pas extensible pour le moment,

function spip_pg_sequence($table, $raw = false) {

	include_spip('base/serial');
	if (!isset($GLOBALS['tables_principales'][$table])) {
		return false;
	}
	$desc = $GLOBALS['tables_principales'][$table];
	$prim = @$desc['key']['PRIMARY KEY'];
	if (
		!preg_match('/^\w+$/', (string) $prim) || !str_contains((string) $desc['field'][$prim], 'int')
	) {
		return '';
	} else {
		return $raw ? $prim : $table . '_' . $prim . '_seq';
	}
}

// Explicite les conversions de Mysql d'une valeur $v de type $t
// Dans le cas d'un champ date, pas d'apostrophe, c'est une syntaxe ad hoc

function spip_pg_cite($v, $t) {
	if (is_null($v)) {
		return 'NULL';
	} // null php se traduit en NULL SQL

	if (sql_test_date($t)) {
		if ($v && !str_contains('0123456789', (string) $v[0])) {
			return spip_pg_frommysql($v);
		} else {
			if (str_starts_with((string) $v, '0000')) {
				$v = '0001' . substr((string) $v, 4);
			}
			if (strpos((string) $v, '-00-00') === 4) {
				$v = substr((string) $v, 0, 4) . '-01-01' . substr((string) $v, 10);
			}

			return "timestamp '$v'";
		}
	} elseif (!sql_test_int($t)) {
		return ("'" . pg_escape_string($v) . "'");
	} elseif (is_numeric($v) || str_starts_with((string) $v, 'CAST(')) {
		return $v;
	} elseif ($v[0] == '0' && $v[1] !== 'x' && ctype_xdigit(substr((string) $v, 1))) {
		return substr((string) $v, 1);
	} else {
		spip_log("Warning: '$v'  n'est pas de type $t", 'pg.' . _LOG_AVERTISSEMENT);

		return (int) $v;
	}
}

function spip_pg_hex($v) {
	return "CAST(x'" . $v . "' as bigint)";
}

function spip_pg_quote($v, $type = '') {
	if (!is_array($v)) {
		return spip_pg_cite($v, $type);
	}
	// si c'est un tableau, le parcourir en propageant le type
	foreach ($v as $k => $r) {
		$v[$k] = spip_pg_quote($r, $type);
	}

	return implode(',', $v);
}

function spip_pg_date_proche($champ, $interval, $unite) {
	return '('
	. $champ
	. (($interval <= 0) ? '>' : '<')
	. (($interval <= 0) ? 'DATE_SUB' : 'DATE_ADD')
	. '('
	. sql_quote(date('Y-m-d H:i:s'))
	. ', INTERVAL '
	. (($interval > 0) ? $interval : (0 - $interval))
	. ' '
	. $unite
	. '))';
}

function spip_pg_in($val, $valeurs, $not = '', $serveur = '') {
//
// IN (...) souvent limite a 255  elements, d'ou cette fonction assistante
//
	// s'il n'y a pas de valeur, eviter de produire un IN vide: PG rale.
	if (!$valeurs) {
		return $not ? '0=0' : '0=1';
	}
	if (str_contains((string) $valeurs, "CAST(x'")) {
		return "($val=" . implode("OR $val=", explode(',', (string) $valeurs)) . ')';
	}
	$n = $i = 0;
	$in_sql = '';
	while ($n = strpos((string) $valeurs, ',', $n + 1)) {
		if ((++$i) >= 255) {
			$in_sql .= "($val $not IN (" .
				substr((string) $valeurs, 0, $n) .
				"))\n" .
				($not ? "AND\t" : "OR\t");
			$valeurs = substr((string) $valeurs, $n + 1);
			$i = $n = 0;
		}
	}
	$in_sql .= "($val $not IN ($valeurs))";

	return "($in_sql)";
}

function spip_pg_error($query = '', $serveur = '', $requeter = true) {
	$link = $GLOBALS['connexions'][$serveur ? strtolower((string) $serveur) : 0]['link'];
	$s = $link ? pg_last_error($link) : pg_last_error();
	if ($s) {
		$s = str_replace('ERROR', 'errcode: 1000 ', $s);
		spip_log("$s - $query", 'pg.' . _LOG_ERREUR);
	}

	return $s;
}

function spip_pg_errno($serveur = '') {
	// il faudrait avoir la derniere ressource retournee et utiliser
	// http://fr2.php.net/manual/fr/function.pg-result-error.php
	return 0;
}

function spip_pg_drop_table($table, $exist = '', $serveur = '', $requeter = true) {
	if ($exist) {
		$exist = ' IF EXISTS';
	}
	if (spip_pg_query("DROP TABLE$exist $table", $serveur, $requeter)) {
		return true;
	} else {
		return false;
	}
}

// supprime une vue
function spip_pg_drop_view($view, $exist = '', $serveur = '', $requeter = true) {
	if ($exist) {
		$exist = ' IF EXISTS';
	}

	return spip_pg_query("DROP VIEW$exist $view", $serveur, $requeter);
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
 * @return ressource
 *     Ressource à utiliser avec sql_fetch()
 **/
function spip_pg_showbase($match, $serveur = '', $requeter = true) {
	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
	$link = $connexion['link'];
	$connexion['last'] = $q = 'SELECT tablename FROM pg_tables WHERE tablename ILIKE ' . _q($match);

	return spip_pg_query_simple($link, $q);
}

function spip_pg_showtable($nom_table, $serveur = '', $requeter = true) {
	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower((string) $serveur) : 0];
	$link = $connexion['link'];
	$connexion['last'] = $q = 'SELECT column_name, column_default, data_type FROM information_schema.columns WHERE table_name ILIKE ' . _q($nom_table);

	$res = spip_pg_query_simple($link, $q);
	if (!$res) {
		return false;
	}

	// etrangement, $res peut ne rien contenir, mais arriver ici...
	// il faut en tenir compte dans le return
	$fields = [];
	while ($field = pg_fetch_array($res, null, PGSQL_NUM)) {
		$fields[$field[0]] = $field[2] . ($field[1] ? ' DEFAULT ' . $field[1] : (''));
	}
	$connexion['last'] = $q = 'SELECT indexdef FROM pg_indexes WHERE tablename ILIKE ' . _q($nom_table);
	$res = spip_pg_query_simple($link, $q);
	$keys = [];
	while ($index = pg_fetch_array($res, null, PGSQL_NUM)) {
		if (preg_match('/CREATE\s+(UNIQUE\s+)?INDEX\s([^\s]+).*\((.*)\)$/', $index[0], $r)) {
			$nom = str_replace($nom_table . '_', '', $r[2]);
			$keys[($r[1] ? 'PRIMARY KEY' : ('KEY ' . $nom))] = $r[3];
		}
	}

	return count($fields) ? ['field' => $fields, 'key' => $keys] : false;
}

// Fonction de creation d'une table SQL nommee $nom
// a partir de 2 tableaux PHP :
// champs: champ => type
// cles: type-de-cle => champ(s)
// si $autoinc, c'est une auto-increment (i.e. serial) sur la Primary Key
// Le nom des index est prefixe par celui de la table pour eviter les conflits
function spip_pg_create($nom, $champs, $cles, $autoinc = false, $temporary = false, $serveur = '', $requeter = true) {

	$connexion = $GLOBALS['connexions'][$serveur ? strtolower((string) $serveur) : 0];
	$link = $connexion['link'];
	$nom = prefixer_table_spip($nom, $connexion['prefixe']);

	$query = $prim = $prim_name = $v = $s = $p = '';
	$keys = [];

	// certains plugins declarent les tables  (permet leur inclusion dans le dump)
	// sans les renseigner (laisse le compilo recuperer la description)
	if (!is_array($champs) || !is_array($cles)) {
		return;
	}

	foreach ($cles as $k => $v) {
		if (str_starts_with($k, 'KEY ')) {
			$n = str_replace('`', '', $k);
			$v = str_replace('`', '"', (string) $v);
			$i = $nom . preg_replace('/KEY +/', '_', $n);
			if ($k != $n) {
				$i = "\"$i\"";
			}
			$keys[] = "CREATE INDEX $i ON $nom ($v);";
		} elseif (str_starts_with($k, 'UNIQUE ')) {
			$k = preg_replace('/^UNIQUE +/', '', $k);
			$prim .= "$s\n\t\tCONSTRAINT " . str_replace('`', '"', $k) . " UNIQUE ($v)";
		} else {
			$prim .= "$s\n\t\t" . str_replace('`', '"', $k) . " ($v)";
		}
		if ($k == 'PRIMARY KEY') {
			$prim_name = $v;
		}
		$s = ',';
	}
	$s = '';

	$character_set = '';
	if (@$GLOBALS['meta']['charset_sql_base']) {
		$character_set .= ' CHARACTER SET ' . $GLOBALS['meta']['charset_sql_base'];
	}
	if (@$GLOBALS['meta']['charset_collation_sql_base']) {
		$character_set .= ' COLLATE ' . $GLOBALS['meta']['charset_collation_sql_base'];
	}

	foreach ($champs as $k => $v) {
		$k = str_replace('`', '"', $k);
		if (preg_match(',([a-z]*\s*(\(\s*\d*\s*\))?(\s*binary)?),i', (string) $v, $defs) && (preg_match(',(char|text),i', $defs[1]) && !preg_match(',binary,i', $defs[1]))) {
			$v = $defs[1] . $character_set . ' ' . substr((string) $v, strlen($defs[1]));
		}

		$query .= "$s\n\t\t$k "
			. (($autoinc && ($prim_name == $k) && preg_match(',\b(big|small|medium|tiny)?int\b,i', (string) $v))
				? ' bigserial'
				: mysql2pg_type($v)
			);
		$s = ',';
	}
	$temporary = $temporary ? 'TEMPORARY' : '';

	// En l'absence de "if not exists" en PG, on neutralise les erreurs

	$q = "CREATE $temporary TABLE $nom ($query" . ($prim ? ",$prim" : '') . ')' .
		($character_set ? " DEFAULT $character_set" : '')
		. "\n";

	if (!$requeter) {
		return $q;
	}
	$connexion['last'] = $q;
	$r = @pg_query($link, $q);

	if (!$r) {
		spip_log("Impossible de creer cette table: $q", 'pg.' . _LOG_ERREUR);
	} else {
		foreach ($keys as $index) {
			pg_query($link, $index);
		}
	}

	return $r;
}


function spip_pg_create_base($nom, $serveur = '', $requeter = true) {
	return spip_pg_query("CREATE DATABASE $nom", $serveur, $requeter);
}

// Fonction de creation d'une vue SQL nommee $nom
function spip_pg_create_view($nom, $query_select, $serveur = '', $requeter = true) {
	if (!$query_select) {
		return false;
	}
	// vue deja presente
	if (sql_showtable($nom, false, $serveur)) {
		if ($requeter) {
			spip_log("Echec creation d'une vue sql ($nom) car celle-ci existe deja (serveur:$serveur)", 'pg.' . _LOG_ERREUR);
		}

		return false;
	}

	$query = "CREATE VIEW $nom AS " . $query_select;

	return spip_pg_query($query, $serveur, $requeter);
}


function spip_pg_set_connect_charset($charset, $serveur = '', $requeter = true) {
	spip_log('changement de charset sql a ecrire en PG', 'pg.' . _LOG_ERREUR);
}


/**
 * Optimise une table SQL
 *
 * @param $table nom de la table a optimiser
 * @param $serveur nom de la connexion
 * @param $requeter effectuer la requete ? sinon retourner son code
 * @return bool|string true / false / requete
 **/
function spip_pg_optimize($table, $serveur = '', $requeter = true) {
	return spip_pg_query('VACUUM ' . $table, $serveur, $requeter);
}

// Selectionner la sous-chaine dans $objet
// correspondant a $lang. Cf balise Multi de Spip

function spip_pg_multi($objet, $lang) {
	return 'regexp_replace('
		. $objet
		. ",'<multi>.*[[]"
		. $lang
		. "[]]([^[]*).*</multi>', E'\\\\1') AS multi";
}

// Palanquee d'idiosyncrasies MySQL dans les creations de table
// A completer par les autres, mais essayer de reduire en amont.

function mysql2pg_type($v) {
	$remplace = [
		'/auto_increment/i' => '', // non reconnu
		'/bigint/i' => 'bigint',
		'/mediumint/i' => 'mediumint',
		'/smallint/i' => 'smallint',
		'/tinyint/i' => 'int',
		'/int\s*[(]\s*\d+\s*[)]/i' => 'int',
		'/longtext/i' => 'text',
		'/mediumtext/i' => 'text',
		'/tinytext/i' => 'text',
		'/longblob/i' => 'text',
		'/0000-00-00/' => '0001-01-01',
		'/datetime/i' => 'timestamp',
		'/unsigned/i' => '',
		'/double/i' => 'double precision',
		'/VARCHAR\((\d+)\)\s+BINARY/i' => 'varchar(\1)',
		'/ENUM *[(][^)]*[)]/i' => 'varchar(255)',
		'/(timestamp .* )ON .*$/is' => '\\1',
	];

	return preg_replace(array_keys($remplace), array_values($remplace), (string) $v);
}

// Renvoie false si on n'a pas les fonctions pg (pour l'install)
function spip_versions_pg() {
	return function_exists('pg_connect');
}
