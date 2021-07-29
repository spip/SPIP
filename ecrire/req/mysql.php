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

/**
 * Ce fichier contient les fonctions gérant
 * les instructions SQL pour MySQL
 *
 * Ces instructions utilisent la librairie PHP Mysqli
 *
 * @package SPIP\Core\SQL\MySQL
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

if (!defined('_MYSQL_NOPLANES')) {
	define('_MYSQL_NOPLANES', true);
}

/**
 * Crée la première connexion à un serveur MySQL via MySQLi
 *
 * @param string $host Chemin du serveur
 * @param int $port Port de connexion
 * @param string $login Nom d'utilisateur
 * @param string $pass Mot de passe
 * @param string $db Nom de la base
 * @param string $prefixe Préfixe des tables SPIP
 * @return array|bool
 *     - false si la connexion a échoué
 *     - tableau décrivant la connexion sinon
 */
function req_mysql_dist($host, $port, $login, $pass, $db = '', $prefixe = '') {
	if (!extension_loaded('mysqli')) {
		return false;
	}

	// si port est fourni mais pas host, c'est un socket -> compat avec vieille syntaxe de mysql_connect() et anciens fichiers connect.php
	if (
		$port and !is_numeric($socket = $port)
		and (!$host or $host=='localhost')) {
		$link = @mysqli_connect($host, $login, $pass, '', null, $socket);
	}
	elseif ($port) {
		$link = @mysqli_connect($host, $login, $pass, '', $port);
	}
	else {
		$link = @mysqli_connect($host, $login, $pass);
	}

	if (!$link) {
		spip_log('Echec mysqli_connect. Erreur : ' . mysqli_connect_error(), 'mysql.' . _LOG_HS);

		return false;
	}
	$last = '';
	if (!$db) {
		$ok = $link;
		$db = 'spip';
	} else {
		$ok = mysqli_select_db($link, $db);
		if (defined('_MYSQL_SET_SQL_MODE')
			or defined('_MYSQL_SQL_MODE_TEXT_NOT_NULL') // compatibilite
		) {
			mysqli_query($link, $last = "set sql_mode=''");
		}
	}

	spip_log("Connexion MySQLi vers $host, base $db, prefixe $prefixe " . ($ok ? "operationnelle" : 'impossible'),
		_LOG_DEBUG);

	return !$ok ? false : array(
		'db' => $db,
		'last' => $last,
		'prefixe' => $prefixe ? $prefixe : $db,
		'link' => $link,
		'total_requetes' => 0,
	);
}


$GLOBALS['spip_mysql_functions_1'] = array(
	'alter' => 'spip_mysql_alter',
	'count' => 'spip_mysql_count',
	'countsel' => 'spip_mysql_countsel',
	'create' => 'spip_mysql_create',
	'create_base' => 'spip_mysql_create_base',
	'create_view' => 'spip_mysql_create_view',
	'date_proche' => 'spip_mysql_date_proche',
	'delete' => 'spip_mysql_delete',
	'drop_table' => 'spip_mysql_drop_table',
	'drop_view' => 'spip_mysql_drop_view',
	'errno' => 'spip_mysql_errno',
	'error' => 'spip_mysql_error',
	'explain' => 'spip_mysql_explain',
	'fetch' => 'spip_mysql_fetch',
	'seek' => 'spip_mysql_seek',
	'free' => 'spip_mysql_free',
	'hex' => 'spip_mysql_hex',
	'in' => 'spip_mysql_in',
	'insert' => 'spip_mysql_insert',
	'insertq' => 'spip_mysql_insertq',
	'insertq_multi' => 'spip_mysql_insertq_multi',
	'listdbs' => 'spip_mysql_listdbs',
	'multi' => 'spip_mysql_multi',
	'optimize' => 'spip_mysql_optimize',
	'query' => 'spip_mysql_query',
	'quote' => 'spip_mysql_quote',
	'replace' => 'spip_mysql_replace',
	'replace_multi' => 'spip_mysql_replace_multi',
	'repair' => 'spip_mysql_repair',
	'select' => 'spip_mysql_select',
	'selectdb' => 'spip_mysql_selectdb',
	'set_charset' => 'spip_mysql_set_charset',
	'get_charset' => 'spip_mysql_get_charset',
	'showbase' => 'spip_mysql_showbase',
	'showtable' => 'spip_mysql_showtable',
	'update' => 'spip_mysql_update',
	'updateq' => 'spip_mysql_updateq',

	// association de chaque nom http d'un charset aux couples MySQL
	'charsets' => array(
		'cp1250' => array('charset' => 'cp1250', 'collation' => 'cp1250_general_ci'),
		'cp1251' => array('charset' => 'cp1251', 'collation' => 'cp1251_general_ci'),
		'cp1256' => array('charset' => 'cp1256', 'collation' => 'cp1256_general_ci'),
		'iso-8859-1' => array('charset' => 'latin1', 'collation' => 'latin1_swedish_ci'),
//'iso-8859-6'=>array('charset'=>'latin1','collation'=>'latin1_swedish_ci'),
		'iso-8859-9' => array('charset' => 'latin5', 'collation' => 'latin5_turkish_ci'),
//'iso-8859-15'=>array('charset'=>'latin1','collation'=>'latin1_swedish_ci'),
		'utf-8' => array('charset' => 'utf8', 'collation' => 'utf8_general_ci')
	)
);


/**
 * Retrouver un link d'une connexion MySQL via MySQLi
 *
 * @param string $serveur Nom du serveur
 * @return Object Information de connexion pour mysqli
 */
function _mysql_link($serveur = '') {
	$link = &$GLOBALS['connexions'][$serveur ? $serveur : 0]['link'];

	return $link;
}


/**
 * Définit un charset pour la connexion avec Mysql
 *
 * @param string $charset Charset à appliquer
 * @param string $serveur Nom de la connexion
 * @param bool $requeter inutilisé
 * @return resource       Ressource de résultats pour fetch()
 */
function spip_mysql_set_charset($charset, $serveur = '', $requeter = true) {
	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
	spip_log("changement de charset sql : " . "SET NAMES " . _q($charset), _LOG_DEBUG);

	return mysqli_query($connexion['link'], $connexion['last'] = "SET NAMES " . _q($charset));
}


/**
 * Teste si le charset indiqué est disponible sur le serveur SQL
 *
 * @param array|string $charset Nom du charset à tester.
 * @param string $serveur Nom de la connexion
 * @param bool $requeter inutilisé
 * @return array                Description du charset (son nom est dans 'charset')
 */
function spip_mysql_get_charset($charset = array(), $serveur = '', $requeter = true) {
	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
	$connexion['last'] = $c = "SHOW CHARACTER SET"
		. (!$charset ? '' : (" LIKE " . _q($charset['charset'])));

	return spip_mysql_fetch(mysqli_query($connexion['link'], $c), null, $serveur);
}

/**
 * Exécute une requête MySQL, munie d'une trace à la demande
 *
 * @param string $query Requête
 * @param string $serveur Nom de la connexion
 * @param bool $requeter Exécuter la requête, sinon la retourner
 * @return array|resource|string|bool
 *     - string : Texte de la requête si on ne l'exécute pas
 *     - ressource|bool : Si requête exécutée
 *     - array : Tableau décrivant requête et temps d'exécution si var_profile actif pour tracer.
 */
function spip_mysql_query($query, $serveur = '', $requeter = true) {

	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	$query = _mysql_traite_query($query, $db, $prefixe);

	// renvoyer la requete inerte si demandee
	if (!$requeter) {
		return $query;
	}

	if (isset($_GET['var_profile'])) {
		include_spip('public/tracer');
		$t = trace_query_start();
	} else {
		$t = 0;
	}

	$connexion['last'] = $query;
	$connexion['total_requetes']++;

	// ajouter un debug utile dans log/mysql-slow.log ?
	$debug = '';
	if (defined('_DEBUG_SLOW_QUERIES') and _DEBUG_SLOW_QUERIES) {
		if (isset($GLOBALS['debug']['aucasou'])) {
			list(, $id, , $infos) = $GLOBALS['debug']['aucasou'];
			$debug .= "BOUCLE$id @ " . (isset($infos[0]) ? $infos[0] : '') . " | ";
		}
		if (isset($_SERVER['REQUEST_URI'])) {
			$debug .= $_SERVER['REQUEST_URI'];
		}
		if (!empty($GLOBALS['ip'])) {
			$debug .= ' + ' . $GLOBALS['ip'];
		}
		$debug = ' /* ' . mysqli_real_escape_string($link, str_replace('*/', '@/', $debug)) . ' */';
	}

	$r = mysqli_query($link, $query . $debug);

	//Eviter de propager le GoneAway sur les autres requetes d'un même processus PHP
	if ($e = spip_mysql_errno($serveur)) {  // Log d'un Gone Away
		if ($e == 2006) { //Si Gone Away on relance une connexion vierge
			//Fermer la connexion defaillante
			mysqli_close($connexion['link']);
			unset($GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0]);
			//Relancer une connexion vierge
			spip_connect($serveur);
			$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
			$link = $connexion['link'];
			//On retente au cas où
			$r = mysqli_query($link, $query . $debug);
		}
	}

	// Log de l'erreur eventuelle
	if ($e = spip_mysql_errno($serveur)) {
		$e .= spip_mysql_error($query, $serveur);
	} // et du fautif
	return $t ? trace_query_end($query, $t, $r, $e, $serveur) : $r;
}

/**
 * Modifie une structure de table MySQL
 *
 * @param string $query Requête SQL (sans 'ALTER ')
 * @param string $serveur Nom de la connexion
 * @param bool $requeter Exécuter la requête, sinon la retourner
 * @return array|bool|string
 *     - string : Texte de la requête si on ne l'exécute pas
 *     - bool   : Si requête exécutée
 *     - array  : Tableau décrivant requête et temps d'exécution si var_profile actif pour tracer.
 */
function spip_mysql_alter($query, $serveur = '', $requeter = true) {
	// ici on supprime les ` entourant le nom de table pour permettre
	// la transposition du prefixe, compte tenu que les plugins ont la mauvaise habitude
	// d'utiliser ceux-ci, copie-colle de phpmyadmin
	$query = preg_replace(",^TABLE\s*`([^`]*)`,i", "TABLE \\1", $query);

	return spip_mysql_query("ALTER " . $query, $serveur, $requeter); # i.e. que PG se debrouille
}


/**
 * Optimise une table MySQL
 *
 * @param string $table Nom de la table
 * @param string $serveur Nom de la connexion
 * @param bool $requeter inutilisé
 * @return bool            Toujours true
 */
function spip_mysql_optimize($table, $serveur = '', $requeter = true) {
	spip_mysql_query("OPTIMIZE TABLE " . $table);

	return true;
}


/**
 * Retourne une explication de requête (Explain) MySQL
 *
 * @param string $query Texte de la requête
 * @param string $serveur Nom de la connexion
 * @param bool $requeter inutilisé
 * @return array           Tableau de l'explication
 */
function spip_mysql_explain($query, $serveur = '', $requeter = true) {
	if (strpos(ltrim($query), 'SELECT') !== 0) {
		return array();
	}
	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	$query = 'EXPLAIN ' . _mysql_traite_query($query, $db, $prefixe);
	$r = mysqli_query($link, $query);

	return spip_mysql_fetch($r, null, $serveur);
}


/**
 * Exécute une requête de sélection avec MySQL
 *
 * Instance de sql_select (voir ses specs).
 *
 * @see sql_select()
 * @note
 *     Les `\n` et `\t` sont utiles au debusqueur.
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
 *     - string : Texte de la requête si on ne l'exécute pas
 *     - ressource si requête exécutée, ressource pour fetch()
 *     - false si la requête exécutée a ratée
 *     - array  : Tableau décrivant requête et temps d'exécution si var_profile actif pour tracer.
 */
function spip_mysql_select(
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


	$from = (!is_array($from) ? $from : spip_mysql_select_as($from));
	$query =
		calculer_mysql_expression('SELECT', $select, ', ')
		. calculer_mysql_expression('FROM', $from, ', ')
		. calculer_mysql_expression('WHERE', $where)
		. calculer_mysql_expression('GROUP BY', $groupby, ',')
		. calculer_mysql_expression('HAVING', $having)
		. ($orderby ? ("\nORDER BY " . spip_mysql_order($orderby)) : '')
		. ($limit ? "\nLIMIT $limit" : '');

	// renvoyer la requete inerte si demandee
	if ($requeter === false) {
		return $query;
	}
	$r = spip_mysql_query($query, $serveur, $requeter);

	return $r ? $r : $query;
}


/**
 * Prépare une clause order by
 *
 * Regroupe en texte les éléments si un tableau est donné
 *
 * @note
 *   0+x avec un champ x commencant par des chiffres est converti par MySQL
 *   en le nombre qui commence x. Pas portable malheureusement, on laisse pour le moment.
 *
 * @param string|array $orderby Texte du orderby à préparer
 * @return string Texte du orderby préparé
 */
function spip_mysql_order($orderby) {
	return (is_array($orderby)) ? join(", ", $orderby) : $orderby;
}


/**
 * Prépare une clause WHERE pour MySQL
 *
 * Retourne une chaîne avec les bonnes parenthèses pour la
 * contrainte indiquée, au format donnée par le compilateur
 *
 * @param array|string $v
 *     Description des contraintes
 *     - string : Texte du where
 *     - sinon tableau : A et B peuvent être de type string ou array,
 *       OP et C sont de type string :
 *       - array(A) : A est le texte du where
 *       - array(OP, A) : contrainte OP( A )
 *       - array(OP, A, B) : contrainte (A OP B)
 *       - array(OP, A, B, C) : contrainte (A OP (B) : C)
 * @return string
 *     Contrainte pour clause WHERE
 */
function calculer_mysql_where($v) {
	if (!is_array($v)) {
		return $v;
	}

	$op = array_shift($v);
	if (!($n = count($v))) {
		return $op;
	} else {
		$arg = calculer_mysql_where(array_shift($v));
		if ($n == 1) {
			return "$op($arg)";
		} else {
			$arg2 = calculer_mysql_where(array_shift($v));
			if ($n == 2) {
				return "($arg $op $arg2)";
			} else {
				return "($arg $op ($arg2) : $v[0])";
			}
		}
	}
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
 * @return string            Texte de l'expression, une partie donc, du texte la requête.
 */
function calculer_mysql_expression($expression, $v, $join = 'AND') {
	if (empty($v)) {
		return '';
	}

	$exp = "\n$expression ";

	if (!is_array($v)) {
		return $exp . $v;
	} else {
		if (strtoupper($join) === 'AND') {
			return $exp . join("\n\t$join ", array_map('calculer_mysql_where', $v));
		} else {
			return $exp . join($join, $v);
		}
	}
}


/**
 * Renvoie des `nom AS alias`
 *
 * @param array $args
 * @return string Sélection de colonnes pour une clause SELECT
 */
function spip_mysql_select_as($args) {
	$res = '';
	foreach ($args as $k => $v) {
		if (substr($k, -1) == '@') {
			// c'est une jointure qui se refere au from precedent
			// pas de virgule
			$res .= '  ' . $v;
		} else {
			if (!is_numeric($k)) {
				$p = strpos($v, " ");
				if ($p) {
					$v = substr($v, 0, $p) . " AS `$k`" . substr($v, $p);
				} else {
					$v .= " AS `$k`";
				}
			}
			$res .= ', ' . $v;
		}
	}

	return substr($res, 2);
}


/**
 * Changer les noms des tables ($table_prefix)
 *
 * TODO: Quand tous les appels SQL seront abstraits on pourra l'améliorer
 */
define('_SQL_PREFIXE_TABLE_MYSQL', '/([,\s])spip_/S');


/**
 * Prépare le texte d'une requête avant son exécution
 *
 * Change les préfixes de tables SPIP par ceux véritables
 *
 * @param string $query Requête à préparer
 * @param string $db Nom de la base de donnée
 * @param string $prefixe Préfixe de tables à appliquer
 * @return string           Requête préparée
 */
function _mysql_traite_query($query, $db = '', $prefixe = '') {

	if ($GLOBALS['mysql_rappel_nom_base'] and $db) {
		$pref = '`' . $db . '`.';
	} else {
		$pref = '';
	}

	if ($prefixe) {
		$pref .= $prefixe . "_";
	}

	if (!preg_match('/\s(SET|VALUES|WHERE|DATABASE)\s/i', $query, $regs)) {
		$suite = '';
	} else {
		$suite = strstr($query, $regs[0]);
		$query = substr($query, 0, -strlen($suite));
		// propager le prefixe en cas de requete imbriquee
		// il faut alors echapper les chaine avant de le faire, pour ne pas risquer de
		// modifier une requete qui est en fait juste du texte dans un champ
		if (stripos($suite, "SELECT") !== false) {
			list($suite, $textes) = query_echappe_textes($suite);
			if (preg_match('/^(.*?)([(]\s*SELECT\b.*)$/si', $suite, $r)) {
				$suite = $r[1] . _mysql_traite_query($r[2], $db, $prefixe);
			}
			$suite = query_reinjecte_textes($suite, $textes);
		}
	}
	$r = preg_replace(_SQL_PREFIXE_TABLE_MYSQL, '\1' . $pref, $query) . $suite;

	// en option, remplacer les emoji (que mysql ne sait pas gérer) en &#128169;
	// remplacer les emoji (que mysql ne sait pas gérer) en &#128169;
	if (defined('_MYSQL_NOPLANES')
		and _MYSQL_NOPLANES
		and !empty($GLOBALS['meta']['charset_sql_connexion'])
		and $GLOBALS['meta']['charset_sql_connexion'] == 'utf8') {
		include_spip('inc/charsets');
		$r = utf8_noplanes($r);
	}

	#spip_log("_mysql_traite_query: " . substr($r,0, 50) . ".... $db, $prefixe", _LOG_DEBUG);
	return $r;
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
 * @return bool
 *     - True cas de succès.
 *     - False en cas d'erreur.
 **/
function spip_mysql_selectdb($db, $serveur = '', $requeter = true) {
	$link = _mysql_link($serveur);
	$ok = mysqli_select_db($link, $db);
	if (!$ok) {
		spip_log('Echec mysqli_selectdb. Erreur : ' . mysqli_error($link), 'mysql.' . _LOG_CRITIQUE);
	}

	return $ok;
}


/**
 * Retourne les bases de données accessibles
 *
 * Retourne un tableau du nom de toutes les bases de données
 * accessibles avec les permissions de l'utilisateur SQL
 * de cette connexion.
 *
 * Attention on n'a pas toujours les droits !
 *
 * @param string $serveur
 *     Nom du connecteur
 * @param bool $requeter
 *     Inutilisé
 * @return array
 *     Liste de noms de bases de données
 **/
function spip_mysql_listdbs($serveur = '', $requeter = true) {
	$dbs = array();
	if ($res = spip_mysql_query("SHOW DATABASES", $serveur)) {
		while ($row = mysqli_fetch_assoc($res)) {
			$dbs[] = $row['Database'];
		}
	}

	return $dbs;
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
 * @param bool $requeter inutilisé
 * @return array|null|resource|string
 *     - null si champs ou cles n'est pas un tableau
 *     - true si la requête réussie, false sinon.
 */
function spip_mysql_create(
	$nom,
	$champs,
	$cles,
	$autoinc = false,
	$temporary = false,
	$serveur = '',
	$requeter = true
) {

	$query = '';
	$keys = '';
	$s = '';
	$p = '';

	// certains plugins declarent les tables  (permet leur inclusion dans le dump)
	// sans les renseigner (laisse le compilo recuperer la description)
	if (!is_array($champs) || !is_array($cles)) {
		return;
	}

	$res = spip_mysql_query("SELECT version() as v", $serveur);
	if (($row = mysqli_fetch_array($res)) && (version_compare($row['v'], '5.0', '>='))) {
		spip_mysql_query("SET sql_mode=''", $serveur);
	}

	foreach ($cles as $k => $v) {
		$keys .= "$s\n\t\t$k ($v)";
		if ($k == "PRIMARY KEY") {
			$p = $v;
		}
		$s = ",";
	}
	$s = '';

	$character_set = "";
	if (@$GLOBALS['meta']['charset_sql_base']) {
		$character_set .= " CHARACTER SET " . $GLOBALS['meta']['charset_sql_base'];
	}
	if (@$GLOBALS['meta']['charset_collation_sql_base']) {
		$character_set .= " COLLATE " . $GLOBALS['meta']['charset_collation_sql_base'];
	}

	foreach ($champs as $k => $v) {
		$v = _mysql_remplacements_definitions_table($v);
		if (preg_match(',([a-z]*\s*(\(\s*[0-9]*\s*\))?(\s*binary)?),i', $v, $defs)) {
			if (preg_match(',(char|text),i', $defs[1])
				and !preg_match(',(binary|CHARACTER|COLLATE),i', $v)
			) {
				$v = $defs[1] . $character_set . ' ' . substr($v, strlen($defs[1]));
			}
		}

		$query .= "$s\n\t\t$k $v"
			. (($autoinc && ($p == $k) && preg_match(',\b(big|small|medium)?int\b,i', $v))
				? " auto_increment"
				: ''
			);
		$s = ",";
	}
	$temporary = $temporary ? 'TEMPORARY' : '';
	$q = "CREATE $temporary TABLE IF NOT EXISTS $nom ($query" . ($keys ? ",$keys" : '') . ")"
		. " ENGINE=MyISAM"
		. ($character_set ? " DEFAULT $character_set" : "")
		. "\n";

	return spip_mysql_query($q, $serveur);
}


/**
 * Adapte pour Mysql la déclaration SQL d'une colonne d'une table
 *
 * @param string|array $query
 *     Définition SQL d'un champ de table ou liste de déclarations
 * @return string|array
 *     Définition SQL adaptée pour MySQL d'un champ de table
 */
function _mysql_remplacements_definitions_table($query) {
	// quelques remplacements
	$num = "(\s*\([0-9]*\))?";
	$enum = "(\s*\([^\)]*\))?";

	$remplace = array(
		'/VARCHAR(\s*[^\s\(])/is' => 'VARCHAR(255)\\1',
		'/^TIMESTAMP($| NULL DEFAULT NULL)/is' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
	);

	if (is_string($query)) {
		$query = preg_replace(array_keys($remplace), $remplace, $query);
	} elseif (is_array($query)) {
		$keys = array_keys($remplace);
		foreach ($query as $k => $q) {
			$query[$k] = preg_replace($keys, $remplace, $q);
		}
	}

	return $query;
}


/**
 * Crée une base de données MySQL
 *
 * @param string $nom Nom de la base
 * @param string $serveur Nom de la connexion
 * @param bool $requeter Exécuter la requête, sinon la retourner
 * @return bool true si la base est créee.
 **/
function spip_mysql_create_base($nom, $serveur = '', $requeter = true) {
	return spip_mysql_query("CREATE DATABASE `$nom`", $serveur, $requeter);
}


/**
 * Crée une vue SQL nommée `$nom`
 *
 * @param string $nom
 *    Nom de la vue à creer
 * @param string $query_select
 *     Texte de la requête de sélection servant de base à la vue
 * @param string $serveur
 *     Nom du connecteur
 * @param bool $requeter
 *     Effectuer la requete, sinon la retourner
 * @return bool|string
 *     - true si la vue est créée
 *     - false si erreur ou si la vue existe déja
 *     - string texte de la requête si $requeter vaut false
 */
function spip_mysql_create_view($nom, $query_select, $serveur = '', $requeter = true) {
	if (!$query_select) {
		return false;
	}
	// vue deja presente
	if (sql_showtable($nom, false, $serveur)) {
		spip_log("Echec creation d'une vue sql ($nom) car celle-ci existe deja (serveur:$serveur)", _LOG_ERREUR);

		return false;
	}

	$query = "CREATE VIEW $nom AS " . $query_select;

	return spip_mysql_query($query, $serveur, $requeter);
}


/**
 * Supprime une table SQL
 *
 * @param string $table Nom de la table SQL
 * @param string $exist True pour ajouter un test d'existence avant de supprimer
 * @param string $serveur Nom de la connexion
 * @param bool $requeter Exécuter la requête, sinon la retourner
 * @return bool|string
 *     - string Texte de la requête si demandé
 *     - true si la requête a réussie, false sinon
 */
function spip_mysql_drop_table($table, $exist = '', $serveur = '', $requeter = true) {
	if ($exist) {
		$exist = " IF EXISTS";
	}

	return spip_mysql_query("DROP TABLE$exist $table", $serveur, $requeter);
}

/**
 * Supprime une vue SQL
 *
 * @param string $view Nom de la vue SQL
 * @param string $exist True pour ajouter un test d'existence avant de supprimer
 * @param string $serveur Nom de la connexion
 * @param bool $requeter Exécuter la requête, sinon la retourner
 * @return bool|string
 *     - string Texte de la requête si demandé
 *     - true si la requête a réussie, false sinon
 */
function spip_mysql_drop_view($view, $exist = '', $serveur = '', $requeter = true) {
	if ($exist) {
		$exist = " IF EXISTS";
	}

	return spip_mysql_query("DROP VIEW$exist $view", $serveur, $requeter);
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
function spip_mysql_showbase($match, $serveur = '', $requeter = true) {
	return spip_mysql_query("SHOW TABLES LIKE " . _q($match), $serveur, $requeter);
}

/**
 * Répare une table SQL
 *
 * Utilise `REPAIR TABLE ...` de MySQL
 *
 * @param string $table Nom de la table SQL
 * @param string $serveur Nom de la connexion
 * @param bool $requeter Exécuter la requête, sinon la retourner
 * @return bool|string
 *     - string Texte de la requête si demandée,
 *     - true si la requête a réussie, false sinon
 */
function spip_mysql_repair($table, $serveur = '', $requeter = true) {
	return spip_mysql_query("REPAIR TABLE `$table`", $serveur, $requeter);
}


define('_MYSQL_RE_SHOW_TABLE', '/^[^(),]*\(((?:[^()]*\((?:[^()]*\([^()]*\))?[^()]*\)[^()]*)*[^()]*)\)[^()]*$/');
/**
 * Obtient la description d'une table ou vue MySQL
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
 *     - string Texte de la requête si demandé
 *     - array description de la table sinon
 */
function spip_mysql_showtable($nom_table, $serveur = '', $requeter = true) {
	$s = spip_mysql_query("SHOW CREATE TABLE `$nom_table`", $serveur, $requeter);
	if (!$s) {
		return '';
	}
	if (!$requeter) {
		return $s;
	}

	list(, $a) = mysqli_fetch_array($s, MYSQLI_NUM);
	if (preg_match(_MYSQL_RE_SHOW_TABLE, $a, $r)) {
		$desc = $r[1];
		// extraction d'une KEY éventuelle en prenant garde de ne pas
		// relever un champ dont le nom contient KEY (ex. ID_WHISKEY)
		if (preg_match("/^(.*?),([^,]*\sKEY[ (].*)$/s", $desc, $r)) {
			$namedkeys = $r[2];
			$desc = $r[1];
		} else {
			$namedkeys = "";
		}

		$fields = array();
		foreach (preg_split("/,\s*`/", $desc) as $v) {
			preg_match("/^\s*`?([^`]*)`\s*(.*)/", $v, $r);
			$fields[strtolower($r[1])] = $r[2];
		}
		$keys = array();

		foreach (preg_split('/\)\s*(,|$)/', $namedkeys) as $v) {
			if (preg_match("/^\s*([^(]*)\(([^(]*(\(\d+\))?)$/", $v, $r)) {
				$k = str_replace("`", '', trim($r[1]));
				$t = strtolower(str_replace("`", '', $r[2]));
				if ($k && !isset($keys[$k])) {
					$keys[$k] = $t;
				} else {
					$keys[] = $t;
				}
			}
		}
		spip_mysql_free($s);

		return array('field' => $fields, 'key' => $keys);
	}

	$res = spip_mysql_query("SHOW COLUMNS FROM `$nom_table`", $serveur);
	if ($res) {
		$nfields = array();
		$nkeys = array();
		while ($val = spip_mysql_fetch($res)) {
			$nfields[$val["Field"]] = $val['Type'];
			if ($val['Null'] == 'NO') {
				$nfields[$val["Field"]] .= ' NOT NULL';
			}
			if ($val['Default'] === '0' || $val['Default']) {
				if (preg_match('/[A-Z_]/', $val['Default'])) {
					$nfields[$val["Field"]] .= ' DEFAULT ' . $val['Default'];
				} else {
					$nfields[$val["Field"]] .= " DEFAULT '" . $val['Default'] . "'";
				}
			}
			if ($val['Extra']) {
				$nfields[$val["Field"]] .= ' ' . $val['Extra'];
			}
			if ($val['Key'] == 'PRI') {
				$nkeys['PRIMARY KEY'] = $val["Field"];
			} else {
				if ($val['Key'] == 'MUL') {
					$nkeys['KEY ' . $val["Field"]] = $val["Field"];
				} else {
					if ($val['Key'] == 'UNI') {
						$nkeys['UNIQUE KEY ' . $val["Field"]] = $val["Field"];
					}
				}
			}
		}
		spip_mysql_free($res);

		return array('field' => $nfields, 'key' => $nkeys);
	}

	return "";
}


/**
 * Rècupère une ligne de résultat
 *
 * Récupère la ligne suivante d'une ressource de résultat
 *
 * @param Ressource $r Ressource de résultat (issu de sql_select)
 * @param string $t Structure de résultat attendu (défaut MYSQLI_ASSOC)
 * @param string $serveur Nom de la connexion
 * @param bool $requeter Inutilisé
 * @return array           Ligne de résultat
 */
function spip_mysql_fetch($r, $t = '', $serveur = '', $requeter = true) {
	if (!$t) {
		$t = MYSQLI_ASSOC;
	}
	if ($r) {
		return mysqli_fetch_array($r, $t);
	}
}

/**
 * Place le pointeur de résultat sur la position indiquée
 *
 * @param Ressource $r Ressource de résultat
 * @param int $row_number Position. Déplacer le pointeur à cette ligne
 * @param string $serveur Nom de la connexion
 * @param bool $requeter Inutilisé
 * @return bool True si déplacement réussi, false sinon.
 **/
function spip_mysql_seek($r, $row_number, $serveur = '', $requeter = true) {
	if ($r and mysqli_num_rows($r)) {
		return mysqli_data_seek($r, $row_number);
	}
}


/**
 * Retourne le nombre de lignes d'une sélection
 *
 * @param array|string $from Tables à consulter (From)
 * @param array|string $where Conditions a remplir (Where)
 * @param array|string $groupby Critère de regroupement (Group by)
 * @param array $having Tableau des des post-conditions à remplir (Having)
 * @param string $serveur Nom de la connexion
 * @param bool $requeter Exécuter la requête, sinon la retourner
 * @return int|string
 *     - String Texte de la requête si demandé
 *     - int Nombre de lignes (0 si la requête n'a pas réussie)
 **/
function spip_mysql_countsel(
	$from = array(),
	$where = array(),
	$groupby = '',
	$having = array(),
	$serveur = '',
	$requeter = true
) {
	$c = !$groupby ? '*' : ('DISTINCT ' . (is_string($groupby) ? $groupby : join(',', $groupby)));

	$r = spip_mysql_select("COUNT($c)", $from, $where, '', '', '', $having, $serveur, $requeter);
	if (!$requeter) {
		return $r;
	}
	if (!$r instanceof mysqli_result) {
		return 0;
	}
	list($c) = mysqli_fetch_array($r, MYSQLI_NUM);
	mysqli_free_result($r);

	return $c;
}


/**
 * Retourne la dernière erreur generée
 *
 * @note
 *   Bien spécifier le serveur auquel on s'adresse,
 *   mais à l'install la globale n'est pas encore complètement définie.
 *
 * @uses sql_error_backtrace()
 *
 * @param string $query
 *     Requête qui était exécutée
 * @param string $serveur
 *     Nom de la connexion
 * @param bool $requeter
 *     Inutilisé
 * @return string
 *     Erreur eventuelle
 **/
function spip_mysql_error($query = '', $serveur = '', $requeter = true) {
	$link = $GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0]['link'];
	$s = mysqli_error($link);
	if ($s) {
		$trace = debug_backtrace();
		if ($trace[0]['function'] != "spip_mysql_error") {
			spip_log("$s - $query - " . sql_error_backtrace(), 'mysql.' . _LOG_ERREUR);
		}
	}

	return $s;
}


/**
 * Retourne le numero de la dernière erreur SQL
 *
 * @param string $serveur
 *     Nom de la connexion
 * @param bool $requeter
 *     Inutilisé
 * @return int
 *     0, pas d'erreur. Autre, numéro de l'erreur.
 **/
function spip_mysql_errno($serveur = '', $requeter = true) {
	$link = $GLOBALS['connexions'][$serveur ? $serveur : 0]['link'];
	$s = mysqli_errno($link);
	// 2006 MySQL server has gone away
	// 2013 Lost connection to MySQL server during query
	if (in_array($s, array(2006, 2013))) {
		define('spip_interdire_cache', true);
	}
	if ($s) {
		spip_log("Erreur mysql $s", _LOG_ERREUR);
	}

	return $s;
}


/**
 * Retourne le nombre de lignes d’une ressource de sélection obtenue
 * avec `sql_select()`
 *
 * @param Ressource $r Ressource de résultat
 * @param string $serveur Nom de la connexion
 * @param bool $requeter Inutilisé
 * @return int               Nombre de lignes
 */
function spip_mysql_count($r, $serveur = '', $requeter = true) {
	if ($r) {
		return mysqli_num_rows($r);
	}
}


/**
 * Libère une ressource de résultat
 *
 * Indique à MySQL de libérer de sa mémoire la ressoucre de résultat indiquée
 * car on n'a plus besoin de l'utiliser.
 *
 * @param Ressource $r Ressource de résultat
 * @param string $serveur Nom de la connexion
 * @param bool $requeter Inutilisé
 * @return bool              True si réussi
 */
function spip_mysql_free($r, $serveur = '', $requeter = true) {
	return (($r instanceof mysqli_result) ? mysqli_free_result($r) : false);
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
 *     - Texte de la requête si demandé,
 *     - False en cas d'erreur,
 *     - Tableau de description de la requête et du temps d'exécution, si var_profile activé
 **/
function spip_mysql_insert($table, $champs, $valeurs, $desc = array(), $serveur = '', $requeter = true) {

	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
	$link = $connexion['link'];
	$table = prefixer_table_spip($table, $connexion['prefixe']);

	// remplacer les emoji (que mysql ne sait pas gérer) en &#128169;
	if (defined('_MYSQL_NOPLANES')
		and _MYSQL_NOPLANES
		and !empty($GLOBALS['meta']['charset_sql_connexion'])
		and $GLOBALS['meta']['charset_sql_connexion'] == 'utf8') {
		include_spip('inc/charsets');
		$valeurs = utf8_noplanes($valeurs);
	}

	$query = "INSERT INTO $table $champs VALUES $valeurs";
	if (!$requeter) {
		return $query;
	}

	if (isset($_GET['var_profile'])) {
		include_spip('public/tracer');
		$t = trace_query_start();
		$e = '';
	} else {
		$t = 0;
	}

	$connexion['last'] = $query;
	#spip_log($query, 'mysql.'._LOG_DEBUG);
	$r = false;
	if (mysqli_query($link, $query)) {
		$r = mysqli_insert_id($link);
	} else {
		// Log de l'erreur eventuelle
		if ($e = spip_mysql_errno($serveur)) {
			$e .= spip_mysql_error($query, $serveur);
		} // et du fautif
	}

	return $t ? trace_query_end($query, $t, $r, $e, $serveur) : $r;

	// return $r ? $r : (($r===0) ? -1 : 0); pb avec le multi-base.
}

/**
 * Insère une ligne dans une table, en protégeant chaque valeur
 *
 * @param string $table
 *     Nom de la table SQL
 * @param string $couples
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
 *     - Texte de la requête si demandé,
 *     - False en cas d'erreur,
 *     - Tableau de description de la requête et du temps d'exécution, si var_profile activé
 **/
function spip_mysql_insertq($table, $couples = array(), $desc = array(), $serveur = '', $requeter = true) {

	if (!$desc) {
		$desc = description_table($table, $serveur);
	}
	if (!$desc) {
		$couples = array();
	}
	$fields = isset($desc['field']) ? $desc['field'] : array();

	foreach ($couples as $champ => $val) {
		$couples[$champ] = spip_mysql_cite($val, $fields[$champ]);
	}

	return spip_mysql_insert($table, "(" . join(',', array_keys($couples)) . ")", "(" . join(',', $couples) . ")", $desc,
		$serveur, $requeter);
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
 * @return int|bool|string
 *     - int|true identifiant du dernier élément inséré (si possible), ou true, si réussite
 *     - Texte de la requête si demandé,
 *     - False en cas d'erreur.
 **/
function spip_mysql_insertq_multi($table, $tab_couples = array(), $desc = array(), $serveur = '', $requeter = true) {

	if (!$desc) {
		$desc = description_table($table, $serveur);
	}
	if (!$desc) {
		$tab_couples = array();
	}
	$fields = isset($desc['field']) ? $desc['field'] : array();

	$cles = "(" . join(',', array_keys(reset($tab_couples))) . ')';
	$valeurs = array();
	$r = false;

	// Quoter et Inserer par groupes de 100 max pour eviter un debordement de pile
	foreach ($tab_couples as $couples) {
		foreach ($couples as $champ => $val) {
			$couples[$champ] = spip_mysql_cite($val, $fields[$champ]);
		}
		$valeurs[] = '(' . join(',', $couples) . ')';
		if (count($valeurs) >= 100) {
			$r = spip_mysql_insert($table, $cles, join(', ', $valeurs), $desc, $serveur, $requeter);
			$valeurs = array();
		}
	}
	if (count($valeurs)) {
		$r = spip_mysql_insert($table, $cles, join(', ', $valeurs), $desc, $serveur, $requeter);
	}

	return $r; // dans le cas d'une table auto_increment, le dernier insert_id
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
function spip_mysql_update($table, $champs, $where = '', $desc = array(), $serveur = '', $requeter = true) {
	$set = array();
	foreach ($champs as $champ => $val) {
		$set[] = $champ . "=$val";
	}
	if (!empty($set)) {
		return spip_mysql_query(
			calculer_mysql_expression('UPDATE', $table, ',')
			. calculer_mysql_expression('SET', $set, ',')
			. calculer_mysql_expression('WHERE', $where),
			$serveur, $requeter);
	}
}

/**
 * Met à jour des enregistrements d'une table SQL et protège chaque valeur
 *
 * Protège chaque valeur transmise avec sql_quote(), adapté au type
 * de champ attendu par la table SQL
 *
 * @note
 *   Les valeurs sont des constantes à mettre entre apostrophes
 *   sauf les expressions de date lorsqu'il s'agit de fonctions SQL (NOW etc)
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
function spip_mysql_updateq($table, $champs, $where = '', $desc = array(), $serveur = '', $requeter = true) {

	if (!$champs) {
		return;
	}
	if (!$desc) {
		$desc = description_table($table, $serveur);
	}
	if (!$desc) {
		$champs = array();
	} else {
		$fields = $desc['field'];
	}
	$set = array();
	foreach ($champs as $champ => $val) {
		$set[] = $champ . '=' . spip_mysql_cite($val, @$fields[$champ]);
	}

	return spip_mysql_query(
		calculer_mysql_expression('UPDATE', $table, ',')
		. calculer_mysql_expression('SET', $set, ',')
		. calculer_mysql_expression('WHERE', $where),
		$serveur, $requeter);
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
 *     - Texte de la requête si demandé,
 *     - False en cas d'erreur.
 **/
function spip_mysql_delete($table, $where = '', $serveur = '', $requeter = true) {
	$res = spip_mysql_query(
		calculer_mysql_expression('DELETE FROM', $table, ',')
		. calculer_mysql_expression('WHERE', $where),
		$serveur, $requeter);
	if (!$requeter) {
		return $res;
	}
	if ($res) {
		$link = _mysql_link($serveur);

		return mysqli_affected_rows($link);
	} else {
		return false;
	}
}


/**
 * Insère où met à jour une entrée d’une table SQL
 *
 * La clé ou les cles primaires doivent être présentes dans les données insérés.
 * La fonction effectue une protection automatique des données.
 *
 * Préférez updateq ou insertq.
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
 *     - Texte de la requête si demandé,
 *     - False en cas d'erreur.
 **/
function spip_mysql_replace($table, $couples, $desc = array(), $serveur = '', $requeter = true) {
	return spip_mysql_query("REPLACE $table (" . join(',', array_keys($couples)) . ') VALUES (' . join(',',
			array_map('_q', $couples)) . ')', $serveur, $requeter);
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
 *     - Texte de la requête si demandé,
 *     - False en cas d'erreur.
 **/
function spip_mysql_replace_multi($table, $tab_couples, $desc = array(), $serveur = '', $requeter = true) {
	$cles = "(" . join(',', array_keys($tab_couples[0])) . ')';
	$valeurs = array();
	foreach ($tab_couples as $couples) {
		$valeurs[] = '(' . join(',', array_map('_q', $couples)) . ')';
	}
	$valeurs = implode(', ', $valeurs);

	return spip_mysql_query("REPLACE $table $cles VALUES $valeurs", $serveur, $requeter);
}


/**
 * Retourne l'instruction SQL pour obtenir le texte d'un champ contenant
 * une balise `<multi>` dans la langue indiquée
 *
 * Cette sélection est mise dans l'alias `multi` (instruction AS multi).
 *
 * @param string $objet Colonne ayant le texte
 * @param string $lang Langue à extraire
 * @return string       Texte de sélection pour la requête
 */
function spip_mysql_multi($objet, $lang) {
	$lengthlang = strlen("[$lang]");
	$posmulti = "INSTR(" . $objet . ", '<multi>')";
	$posfinmulti = "INSTR(" . $objet . ", '</multi>')";
	$debutchaine = "LEFT(" . $objet . ", $posmulti-1)";
	$finchaine = "RIGHT(" . $objet . ", CHAR_LENGTH(" . $objet . ") -(7+$posfinmulti))";
	$chainemulti = "TRIM(SUBSTRING(" . $objet . ", $posmulti+7, $posfinmulti -(7+$posmulti)))";
	$poslang = "INSTR($chainemulti,'[" . $lang . "]')";
	$poslang = "IF($poslang=0,INSTR($chainemulti,']')+1,$poslang+$lengthlang)";
	$chainelang = "TRIM(SUBSTRING(" . $objet . ", $posmulti+7+$poslang-1,$posfinmulti -($posmulti+7+$poslang-1) ))";
	$posfinlang = "INSTR(" . $chainelang . ", '[')";
	$chainelang = "IF($posfinlang>0,LEFT($chainelang,$posfinlang-1),$chainelang)";
	//$chainelang = "LEFT($chainelang,$posfinlang-1)";
	$retour = "(TRIM(IF($posmulti = 0 , " .
		"     TRIM(" . $objet . "), " .
		"     CONCAT( " .
		"          $debutchaine, " .
		"          IF( " .
		"               $poslang = 0, " .
		"                     $chainemulti, " .
		"               $chainelang" .
		"          ), " .
		"          $finchaine" .
		"     ) " .
		"))) AS multi";

	return $retour;
}

/**
 * Prépare une chaîne hexadécimale
 *
 * Par exemple : FF ==> 0xFF en MySQL
 *
 * @param string $v
 *     Chaine hexadecimale
 * @return string
 *     Valeur hexadécimale pour MySQL
 **/
function spip_mysql_hex($v) {
	return "0x" . $v;
}

/**
 * Échapper une valeur selon son type ou au mieux
 * comme le fait `_q()` mais pour MySQL avec ses spécificités
 *
 * @param string|array|number $v
 *     Texte, nombre ou tableau à échapper
 * @param string $type
 *     Description du type attendu
 *    (par exemple description SQL de la colonne recevant la donnée)
 * @return string|number
 *    Donnée prête à être utilisée par le gestionnaire SQL
 */
function spip_mysql_quote($v, $type = '') {
	if (!is_array($v)) {
		return spip_mysql_cite($v, $type);
	}

	// si c'est un tableau, le parcourir en propageant le type
	foreach ($v as $k => $r) {
		$v[$k] = spip_mysql_quote($r, $type);
	}
	return implode(',', $v);
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
function spip_mysql_date_proche($champ, $interval, $unite) {
	$use_now = ( ($champ === 'maj' or strpos($champ, '.maj')) ? true : false );
	return '('
	. $champ
	. (($interval <= 0) ? '>' : '<')
	. (($interval <= 0) ? 'DATE_SUB' : 'DATE_ADD')
	. '('
	. ($use_now ? 'NOW()' : sql_quote(date('Y-m-d H:i:s')))
	. ', INTERVAL '
	. (($interval > 0) ? $interval : (0 - $interval))
	. ' '
	. $unite
	. '))';
}


/**
 * Retourne une expression IN pour le gestionnaire de base de données
 *
 * IN (...) est limité à 255 éléments, d'où cette fonction assistante
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
function spip_mysql_in($val, $valeurs, $not = '', $serveur = '', $requeter = true) {
	return "($val $not IN ($valeurs))";
}


/**
 * Renvoie les bons echappements (mais pas sur les fonctions comme NOW())
 *
 * @param string|number $v Texte ou nombre à échapper
 * @param string $type Type de donnée attendue, description SQL de la colonne de destination
 * @return string|number     Texte ou nombre échappé
 */
function spip_mysql_cite($v, $type) {
	if (!$type) {
		if (is_bool($v)) {
			return strval(intval($v));
		}
		elseif (is_numeric($v)) {
			return strval($v);
		}
		return "'" . addslashes($v) . "'";
	}

	if (is_null($v)
		and stripos($type, "NOT NULL") === false
	) {
		return 'NULL';
	} // null php se traduit en NULL SQL
	if (sql_test_date($type) and preg_match('/^\w+\(/', $v)) {
		return $v;
	}
	if (sql_test_int($type)) {
		if (is_numeric($v) or (ctype_xdigit(substr($v, 2))
				and $v[0] == '0' and $v[1] == 'x')
		) {
			return $v;
		} // si pas numerique, forcer le intval
		else {
			return intval($v);
		}
	}

	return ("'" . addslashes($v) . "'");
}

/**
 * Teste si on a les fonctions MySQLi (pour l'install)
 *
 * @return bool
 *     True si on a les fonctions, false sinon
 */
function spip_versions_mysql() {
	return function_exists('mysqli_query');
}


/**
 * Tester si mysql ne veut pas du nom de la base dans les requêtes
 *
 * @param string $server_db
 * @return string
 *     - chaîne vide si nom de la base utile
 *     - chaîne : code compilé pour le faire désactiver par SPIP sinon
 */
function test_rappel_nom_base_mysql($server_db) {
	$GLOBALS['mysql_rappel_nom_base'] = true;
	sql_delete('spip_meta', "nom='mysql_rappel_nom_base'", $server_db);
	$ok = spip_query("INSERT INTO spip_meta (nom,valeur) VALUES ('mysql_rappel_nom_base', 'test')", $server_db);

	if ($ok) {
		sql_delete('spip_meta', "nom='mysql_rappel_nom_base'", $server_db);

		return '';
	} else {
		$GLOBALS['mysql_rappel_nom_base'] = false;

		return "\$GLOBALS['mysql_rappel_nom_base'] = false; " .
		"/* echec de test_rappel_nom_base_mysql a l'installation. */\n";
	}
}

/**
 * Teste si on peut changer les modes de MySQL
 *
 * @link http://dev.mysql.com/doc/refman/5.0/fr/server-sql-mode.html
 *
 * @param string $server_db Nom de la connexion
 * @return string
 *     - chaîne vide si on ne peut pas appliquer de mode
 *     - chaîne : code compilé pour l'indiquer le résultat du test à SPIP
 */
function test_sql_mode_mysql($server_db) {
	$res = sql_select("version() as v", '', '', '', '', '', '', $server_db);
	$row = sql_fetch($res, $server_db);
	if (version_compare($row['v'], '5.0.0', '>=')) {
		defined('_MYSQL_SET_SQL_MODE') || define('_MYSQL_SET_SQL_MODE', true);

		return "defined('_MYSQL_SET_SQL_MODE') || define('_MYSQL_SET_SQL_MODE',true);\n";
	}

	return '';
}
