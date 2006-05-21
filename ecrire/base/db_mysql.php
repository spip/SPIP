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

//constantes spip pour spip_fetch_array()
define('SPIP_BOTH', MYSQL_BOTH);
define('SPIP_ASSOC', MYSQL_ASSOC);
define('SPIP_NUM', MYSQL_NUM);

//
// Appel de requetes SQL
//

function spip_query_db($query) {

	$query = traite_query($query);

	$start = ($GLOBALS['mysql_profile'] AND (($GLOBALS['connect_statut'] == '0minirezo') OR ($GLOBALS['auteur_session']['statut'] == '0minirezo'))) ? microtime() : 0;

	return spip_mysql_trace($query, 
				$start,
		 (($GLOBALS['mysql_rappel_connexion'] AND $GLOBALS['spip_mysql_link']) ?
			mysql_query($query, $GLOBALS['spip_mysql_link']) :
			mysql_query($query)));
}

function spip_mysql_trace($query, $start, $result)
{
	if ($start) spip_mysql_timing($start, microtime(), $query, $result);

	if ($s = trim(mysql_errno().' '.mysql_error())) {
		if ($GLOBALS['mysql_debug']
		AND (($GLOBALS['connect_statut'] == '0minirezo')
		  OR ($GLOBALS['auteur_session']['statut'] == '0minirezo'))) {
			include_spip('public/debug');
			echo _T('info_erreur_requete'),
			  " ",
			  htmlentities($query),
			  "<br>&laquo; ",
			  htmlentities($result = $s),
			  " &raquo;<p>";
		}
		spip_log($GLOBALS['REQUEST_METHOD'].' '.$GLOBALS['REQUEST_URI'], 'mysql');
		spip_log("$result - $query", 'mysql');
		spip_log($s, 'mysql');
	}
	return $result;
}

function spip_mysql_timing($m1, $m2, $query, $result)
{
	static $tt = 0;
	list($usec, $sec) = explode(" ", $m1);
	list($usec2, $sec2) = explode(" ", $m2);
 	$dt = $sec2 + $usec2 - $sec - $usec;
	$tt += $dt;
	echo "<small>", htmlentities($query), " -> <font color='blue'>", sprintf("%3f", $dt),"</font> (", $tt, ")</small> $result<p>\n";
}

// fonction appelant la precedente  specifiquement pour l'espace public
// c'est une instance de spip_abstract_select, voir ses specs dans abstract.php
// traite_query pourrait y est fait d'avance, à moindre cout.
// Les \n et \t sont utiles au debusqueur.

// La parametre sous_requete n'est plus utilise

function spip_mysql_select($select, $from, $where,
			   $groupby, $orderby, $limit,
			   $sousrequete, $having,
			   $table, $id, $serveur) {

	$query = (!is_array($select) ? $select : join(", ", $select)) .
		(!$from ? '' :
			("\nFROM " .
			(!is_array($from) ? $from : spip_select_as($from))))
		. (!$where ? '' : ("\nWHERE " . (!is_array($where) ? $where : (join("\n\tAND ", array_map('calculer_where', $where))))))
		. ($groupby ? "\nGROUP BY $groupby" : '')
		. ($having ? "\nHAVING $having" : '')
		. ($orderby ? ("\nORDER BY " . join(", ", $orderby)) : '')
		. ($limit ? "\nLIMIT $limit" : '');

	// Erreur ? C'est du debug de squelette, ou une erreur du serveur

	if ($GLOBALS['var_mode'] == 'debug') {
		include_spip('public/debug');
		boucle_debug_resultat($id, 'requete', "SELECT " . $query);
	}

	if (!($res = @spip_query("SELECT ". $query))) {
		include_spip('public/debug');
		erreur_requete_boucle($query, $id, $table,
				      spip_sql_errno(),
				      spip_sql_error());
	}
	return $res;
}

function calculer_where($v)
{
	if (!is_array($v))
	  return $v ;

	$op = array_shift($v);
	if (!($n=count($v)))
		return $op;
	else {
		$arg = calculer_where(array_shift($v));
		if ($n==1) {
			  return "$op($arg)";
		} else {
			$arg2 = calculer_where(array_shift($v));
			if ($n==2) {
				return "($arg $op $arg2)";
			} else return "($arg $op ($arg2) : $v[0])";
		}
	}
}

function spip_select_as($args)
{
	$argsas = "";
	foreach($args as $k => $v) {
		$argsas .= ', ' . $v . (is_numeric($k) ? '' : " AS `$k`");
	}
	return substr($argsas,2);
}

//
// Passage d'une requete standardisee
// Quand tous les appels SQL seront abstraits on pourra l'ameliorer

function traite_query($query) {
	if ($GLOBALS['table_prefix']) $table_pref = $GLOBALS['table_prefix']."_";
	else $table_pref = "";

	if ($GLOBALS['mysql_rappel_nom_base'] AND $db = $GLOBALS['spip_mysql_db'])
		$db = '`'.$db.'`.';

	// changer les noms des tables ($table_prefix)
	if (preg_match('/\s(SET|VALUES|WHERE)\s/i', $query, $regs)) {
		$suite = strstr($query, $regs[0]);
		$query = substr($query, 0, -strlen($suite));
	} else $suite ='';
	return preg_replace('/([,\s])spip_/', '\1'.$db.$table_pref, $query) . $suite;
}

//
// Connexion a la base
//

function spip_connect_db($host, $port, $login, $pass, $db) {
	global $spip_mysql_link, $spip_mysql_db;	// pour connexions multiples

	// gerer le fichier ecrire/data/mysql_out
	## TODO : ajouter md5(parametres de connexion)
	if (@file_exists(_FILE_MYSQL_OUT)
	AND (time() - @filemtime(_FILE_MYSQL_OUT) < 120)
	AND !defined('_ECRIRE_INSTALL'))
		return $GLOBALS['db_ok'] = false;

	if ($port > 0) $host = "$host:$port";
	$spip_mysql_link = @mysql_connect($host, $login, $pass);
	$spip_mysql_db = $db;
	$ok = @mysql_select_db($db);

	$GLOBALS['db_ok'] = $ok
	AND !!@spip_num_rows(@spip_query_db('SELECT COUNT(*) FROM spip_meta'));

	// En cas d'erreur marquer le fichier mysql_out
	if (!$GLOBALS['db_ok']
	AND !defined('_ECRIRE_INSTALL')) {
		@touch(_FILE_MYSQL_OUT);
		$err = 'Echec connexion MySQL '.spip_sql_errno().' '.spip_sql_error();
		spip_log($err);
		spip_log($err, 'mysql');
	}
	return $GLOBALS['db_ok'];
}

function spip_mysql_showtable($nom_table)
{
	$a = spip_query("SHOW TABLES LIKE '$nom_table'");
	if (!a) return "";
	if (!spip_fetch_array($a)) return "";
	list(,$a) = spip_fetch_array(spip_query("SHOW CREATE TABLE $nom_table"));
	if (!preg_match("/^[^(),]*\((([^()]*\([^()]*\)[^()]*)*)\)[^()]*$/", $a, $r))
		return "";
	else {
		$dec = $r[1];
		if (preg_match("/^(.*?),([^,]*KEY.*)$/s", $dec, $r)) {
			$namedkeys = $r[2];
			$dec = $r[1];
		}
		else 
			$namedkeys = "";

		$fields = array();
		foreach(preg_split("/,\s*`/",$dec) as $v) {
			preg_match("/^\s*`?([^`]*)`\s*(.*)/",$v,$r);
			$fields[strtolower($r[1])] = $r[2];
		}
		$keys = array();

		foreach(preg_split('/\)\s*,?/',$namedkeys) as $v) {
			if (preg_match("/^\s*([^(]*)\((.*)$/",$v,$r)) {
				$k = str_replace("`", '', trim($r[1]));
				$t = strtolower(str_replace("`", '', $r[2]));
				if ($k && !isset($keys[$k])) $keys[$k] = $t; else $keys[] = $t;
			}
		}
		return array('field' => $fields,	'key' => $keys);
	}
} 

//
// Recuperation des resultats
//

function spip_fetch_array($r, $t=SPIP_BOTH) {
	if ($r) return mysql_fetch_array($r, $t);
}

function spip_sql_error() {
	return mysql_error();
}

function spip_sql_errno() {
	return mysql_errno();
}

function spip_num_rows($r) {
	if ($r)
		return mysql_num_rows($r);
}

function spip_free_result($r) {
	if ($r)
		return mysql_free_result($r);
}

function spip_mysql_insert($table, $champs, $valeurs) {
	spip_query("INSERT INTO $table $champs VALUES $valeurs");
	return  mysql_insert_id();
}

function spip_insert_id() {
	return mysql_insert_id();
}

//
// Poser un verrou local a un SPIP donne
//
function spip_get_lock($nom, $timeout = 0) {
	global $spip_mysql_db, $table_prefix;
	if ($table_prefix) $nom = "$table_prefix:$nom";
	if ($spip_mysql_db) $nom = "$spip_mysql_db:$nom";

	// Changer de nom toutes les heures en cas de blocage MySQL (ca arrive)
	define('_LOCK_TIME', intval(time()/3600-316982));
	$nom .= _LOCK_TIME;

	$q = spip_query("SELECT GET_LOCK('" . addslashes($nom) . "', $timeout)");
	list($lock_ok) = spip_fetch_array($q);

	if (!$lock_ok) spip_log("pas de lock sql pour $nom");
	return $lock_ok;
}

function spip_release_lock($nom) {
	global $spip_mysql_db, $table_prefix;
	if ($table_prefix) $nom = "$table_prefix:$nom";
	if ($spip_mysql_db) $nom = "$spip_mysql_db:$nom";

	$nom .= _LOCK_TIME;

	spip_query("SELECT RELEASE_LOCK('" . addslashes($nom) . "')");
}

function spip_mysql_version() {
	$row = spip_fetch_array(spip_query("SELECT version() AS n"));
	return ($row['n']);
}

//
// IN (...) est limite a 255 elements, d'ou cette fonction assistante
//
function calcul_mysql_in($val, $valeurs, $not='') {
	if (!$valeurs) return ($not ? "0=0" : '0=1');

	$n = $i = 0;
	$in_sql ="";
	while ($n = strpos($valeurs, ',', $n+1)) {
	  if ((++$i) >= 255) {
			$in_sql .= "($val $not IN (" .
			  substr($valeurs, 0, $n) .
			  "))\n" .
			  ($not ? "AND\t" : "OR\t");
			$valeurs = substr($valeurs, $n+1);
			$i = $n = 0;
		}
	}
	$in_sql .= "($val $not IN ($valeurs))";

	return "($in_sql)";
}


function creer_objet_multi ($objet, $lang) {
	$retour = "(TRIM(IF(INSTR(".$objet.", '<multi>') = 0 , ".
		"     TRIM(".$objet."), ".
		"     CONCAT( ".
		"          LEFT(".$objet.", INSTR(".$objet.", '<multi>')-1), ".
		"          IF( ".
		"               INSTR(TRIM(RIGHT(".$objet.", LENGTH(".$objet.") -(6+INSTR(".$objet.", '<multi>')))),'[".$lang."]') = 0, ".
		"               IF( ".
		"                     TRIM(RIGHT(".$objet.", LENGTH(".$objet.") -(6+INSTR(".$objet.", '<multi>')))) REGEXP '^\\[[a-z\_]{2,}\\]', ".
		"                     INSERT( ".
		"                          TRIM(RIGHT(".$objet.", LENGTH(".$objet.") -(6+INSTR(".$objet.", '<multi>')))), ".
		"                          1, ".
		"                          INSTR(TRIM(RIGHT(".$objet.", LENGTH(".$objet.") -(6+INSTR(".$objet.", '<multi>')))), ']'), ".
		"                          '' ".
		"                     ), ".
		"                     TRIM(RIGHT(".$objet.", LENGTH(".$objet.") -(6+INSTR(".$objet.", '<multi>')))) ".
		"                ), ".
		"               TRIM(RIGHT(".$objet.", ( LENGTH(".$objet.") - (INSTR(".$objet.", '[".$lang."]')+ LENGTH('[".$lang."]')-1) ) )) ".
		"          ) ".
		"     ) ".
		"))) AS multi ";

	return $retour;
}


?>
