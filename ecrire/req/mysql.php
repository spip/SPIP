<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// fonction pour la premiere connexion a un serveur MySQL

// http://doc.spip.org/@base_db_mysql_dist
function req_mysql_dist($host, $port, $login, $pass, $db='', $prefixe='', $ldap='') {
	if ($port > 0) $host = "$host:$port";
	$link = mysql_connect($host, $login, $pass, true);

	if (!$db) {
		$ok = $link;
		$db = 'spip';
	} else {
		$ok = spip_mysql_selectdb($db);
		if (defined('_MYSQL_SET_SQL_MODE') 
		  OR defined('_MYSQL_SQL_MODE_TEXT_NOT_NULL') // compatibilite
		  )
			mysql_query("set sql_mode=''");
	}
#	spip_log("Connexion vers $host, base $db, prefixe $prefixe "
#		 . ($ok ? "operationnelle sur $link" : 'impossible'));

	return !$ok ? false : array(
		'db' => $db,
		'prefixe' => $prefixe ? $prefixe : $db,
		'link' => $GLOBALS['mysql_rappel_connexion'] ? $link : false,
		'ldap' => $ldap,
		);
}

$GLOBALS['spip_mysql_functions_1'] = array(
		'alter' => 'spip_mysql_alter',
		'count' => 'spip_mysql_count',
		'countsel' => 'spip_mysql_countsel',
		'create' => 'spip_mysql_create',
		'delete' => 'spip_mysql_delete',
		'drop_table' => 'spip_mysql_drop_table',
		'errno' => 'spip_mysql_errno',
		'error' => 'spip_mysql_error',
		'explain' => 'spip_mysql_explain',
		'fetch' => 'spip_mysql_fetch',
		'free' => 'spip_mysql_free',
		'hex' => 'spip_mysql_hex',
		'in' => 'spip_mysql_in', 
		'insert' => 'spip_mysql_insert',
		'insertq' => 'spip_mysql_insertq',
		'listdbs' => 'spip_mysql_listdbs',
		'multi' => 'spip_mysql_multi',
		'optimize' => 'spip_mysql_optimize',
		'query' => 'spip_mysql_query',
		'quote' => 'spip_mysql_quote',
		'replace' => 'spip_mysql_replace',
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
'cp1250'=>array('charset'=>'cp1250','collation'=>'cp1250_general_ci'),
'cp1251'=>array('charset'=>'cp1251','collation'=>'cp1251_general_ci'),
'cp1256'=>array('charset'=>'cp1256','collation'=>'cp1256_general_ci'),
'iso-8859-1'=>array('charset'=>'latin1','collation'=>'latin1_swedish_ci'),
//'iso-8859-6'=>array('charset'=>'latin1','collation'=>'latin1_swedish_ci'),
'iso-8859-9'=>array('charset'=>'latin5','collation'=>'latin5_turkish_ci'),
//'iso-8859-15'=>array('charset'=>'latin1','collation'=>'latin1_swedish_ci'),
'utf-8'=>array('charset'=>'utf8','collation'=>'utf8_general_ci'))
		);

// http://doc.spip.org/@spip_mysql_set_charset
function spip_mysql_set_charset($charset, $serveur=''){
	#spip_log("changement de charset sql : "."SET NAMES "._q($charset));
	return mysql_query("SET NAMES "._q($charset));
}

// http://doc.spip.org/@spip_mysql_get_charset
function spip_mysql_get_charset($charset=array(), $serveur=''){
	$c = !$charset ? '' : (" LIKE "._q($charset['charset']));
	return spip_mysql_fetch(mysql_query("SHOW CHARACTER SET$c"), NULL, $serveur);
}

// obsolete, ne plus utiliser
// http://doc.spip.org/@spip_query_db
function spip_query_db($query, $serveur='') {
	return spip_mysql_query($query, $serveur);
}

// Fonction de requete generale, munie d'une trace a la demande

// http://doc.spip.org/@spip_mysql_query
function spip_mysql_query($query, $serveur='') {

	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	$query = traite_query($query, $db, $prefixe);

	$t = !isset($_GET['var_profile']) ? 0 : trace_query_start();
	$r = $link ? mysql_query($query, $link) : mysql_query($query);

	if ($e = spip_mysql_errno())	// Log de l'erreur eventuelle
		$e .= spip_mysql_error($query); // et du fautif
	return $t ? trace_query_end($query, $t, $r, $e, $serveur) : $r;
}

// http://doc.spip.org/@spip_mysql_alter
function spip_mysql_alter($query, $serveur=''){
	return spip_mysql_query("ALTER ".$query); # i.e. que PG se debrouille
}

// http://doc.spip.org/@spip_mysql_optimize
function spip_mysql_optimize($table, $serveur=''){
	spip_mysql_query("OPTIMIZE TABLE ". $table);
	return true;
}

// http://doc.spip.org/@spip_mysql_explain
function spip_mysql_explain($query, $serveur=''){
	if (strpos(ltrim($query), 'SELECT') !== 0) return array();
	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	$query = 'EXPLAIN ' . traite_query($query, $db, $prefixe);
	$r = $link ? mysql_query($query, $link) : mysql_query($query);
	return spip_mysql_fetch($r, NULL, $serveur);
}
// fonction  instance de sql_select, voir ses specs dans abstract.php
// traite_query pourrait y etre fait d'avance ce serait moins cher
// Les \n et \t sont utiles au debusqueur.


// http://doc.spip.org/@spip_mysql_select
function spip_mysql_select($select, $from, $where='',
			   $groupby='', $orderby='', $limit='', $having='',
			   $serveur='') {


	$from = (!is_array($from) ? $from : spip_mysql_select_as($from));
	$query = 
		  calculer_mysql_expression('SELECT', $select, ', ')
		. calculer_mysql_expression('FROM', $from, ', ')
		. calculer_mysql_expression('WHERE', $where)
		. calculer_mysql_expression('GROUP BY', $groupby, ',')
		. calculer_mysql_expression('HAVING', $having)
		. ($orderby ? ("\nORDER BY " . spip_mysql_order($orderby)) :'')
		. ($limit ? "\nLIMIT $limit" : '');

	// Erreur ? C'est du debug de squelette, ou une erreur du serveur

	if (isset($GLOBALS['var_mode']) AND $GLOBALS['var_mode'] == 'debug') {
		include_spip('public/debug');
		boucle_debug_requete($query);
	}

	if (!($res = spip_mysql_query($query, $serveur))) {
		include_spip('public/debug');
		erreur_requete_boucle(substr($query, 7),
				      spip_mysql_errno(),
				      spip_mysql_error($query) );
	}

	return $res;
}

// 0+x avec un champ x commencant par des chiffres est converti par MySQL
// en le nombre qui commence x.
// Pas portable malheureusement, on laisse pour le moment.

// http://doc.spip.org/@spip_mysql_order
function spip_mysql_order($orderby)
{
	return (is_array($orderby)) ? join(", ", $orderby) :  $orderby;
}


// http://doc.spip.org/@calculer_mysql_where
function calculer_mysql_where($v)
{
	if (!is_array($v))
	  return $v ;

	$op = array_shift($v);
	if (!($n=count($v)))
		return $op;
	else {
		$arg = calculer_mysql_where(array_shift($v));
		if ($n==1) {
			  return "$op($arg)";
		} else {
			$arg2 = calculer_mysql_where(array_shift($v));
			if ($n==2) {
				return "($arg $op $arg2)";
			} else return "($arg $op ($arg2) : $v[0])";
		}
	}
}

function calculer_mysql_expression($expression, $v, $join = 'AND'){
	if (empty($v))
		return '';
	
	$exp = "\n$expression ";
	
	if (!is_array($v)) {
		return $exp . $v;
	} else {
		if (strtoupper($join) === 'AND')
			return $exp . join("\n\t$join ", array_map('calculer_mysql_where', $v));
		else
			return $exp . join($join, $v);
	}
}

// http://doc.spip.org/@spip_mysql_select_as
function spip_mysql_select_as($args)
{
	if (isset($args[-1])) {
		$join = ' ' . $args[-1];
		unset($args[-1]);
	} else $join ='';
	$res = '';
	foreach($args as $k => $v) {
	  if (!is_numeric($k)) {
	  	$p = strpos($v, " ");
		if ($p)
		  $v = substr($v,0,$p) . " AS `$k`" . substr($v,$p);
		else $v .= " AS `$k`";
	  }
	      
	  $res .= ', ' . $v ;
	}
	return substr($res,2) . $join;
}

//
// Changer les noms des tables ($table_prefix)
// Quand tous les appels SQL seront abstraits on pourra l'ameliorer

// http://doc.spip.org/@traite_query
function traite_query($query, $db='', $prefixe='') {

	if ($GLOBALS['mysql_rappel_nom_base'] AND $db)
		$pref = '`'. $db.'`.';
	else $pref = '';

	if ($prefixe)
		$pref .= $prefixe . "_";

	if (preg_match('/\s(SET|VALUES|WHERE)\s/i', $query, $regs)) {
		$suite = strstr($query, $regs[0]);
		$query = substr($query, 0, -strlen($suite));
	} else $suite ='';

	$r = preg_replace('/([,\s])spip_/', '\1'.$pref, $query) . $suite;
#	spip_log("traite_query: " . substr($r,0, 50) . ".... $db, $prefixe");
	return $r;
}

// http://doc.spip.org/@spip_mysql_selectdb
function spip_mysql_selectdb($db) {
	return mysql_select_db($db);
}


// Retourne les bases accessibles
// Attention on n'a pas toujours les droits

// http://doc.spip.org/@spip_mysql_listdbs
function spip_mysql_listdbs($serveur='') {
	return @mysql_list_dbs();
}

// Fonction de creation d'une table SQL nommee $nom
// a partir de 2 tableaux PHP :
// champs: champ => type
// cles: type-de-cle => champ(s)
// si $autoinc, c'est une auto-increment (i.e. serial) sur la Primary Key
// Le nom des caches doit etre inferieur a 64 caracteres

// http://doc.spip.org/@spip_mysql_create
function spip_mysql_create($nom, $champs, $cles, $autoinc=false, $temporary=false, $serveur='') {

	$query = ''; $keys = ''; $s = ''; $p='';

	// certains plugins declarent les tables  (permet leur inclusion dans le dump)
	// sans les renseigner (laisse le compilo recuperer la description)
	if (!is_array($champs) || !is_array($cles)) 
		return;

	$res = spip_mysql_query("SELECT @@session.sql_mode");
	if ($row = mysql_fetch_array($res))
		spip_mysql_query("SET sql_mode=''");

	foreach($cles as $k => $v) {
		$keys .= "$s\n\t\t$k ($v)";
		if ($k == "PRIMARY KEY")
			$p = $v;
		$s = ",";
	}
	$s = '';
	
	$character_set = "";
	if (@$GLOBALS['meta']['charset_sql_base'])
		$character_set .= " CHARACTER SET ".$GLOBALS['meta']['charset_sql_base'];
	if (@$GLOBALS['meta']['charset_collation_sql_base'])
		$character_set .= " COLLATE ".$GLOBALS['meta']['charset_collation_sql_base'];

	foreach($champs as $k => $v) {
		if (preg_match(',([a-z]*\s*(\(\s*[0-9]*\s*\))?(\s*binary)?),i',$v,$defs)){
			if (preg_match(',(char|text),i',$defs[1]) AND !preg_match(',binary,i',$defs[1]) ){
				$v = $defs[1] . $character_set . ' ' . substr($v,strlen($defs[1]));
			}
		}

		$query .= "$s\n\t\t$k $v"
			. (($autoinc && ($p == $k) && preg_match(',\b(big)?int\b,i', $v))
				? " auto_increment"
				: ''
			);
		$s = ",";
	}
	$temporary = $temporary ? 'TEMPORARY':'';
	$q = "CREATE $temporary TABLE IF NOT EXISTS $nom ($query" . ($keys ? ",$keys" : '') . ")".
	($character_set?" DEFAULT $character_set":"")
	."\n";
	return spip_mysql_query($q, $serveur);
}

// http://doc.spip.org/@spip_mysql_drop_table
function spip_mysql_drop_table($table, $exist='', $serveur='')
{
	if ($exist) $exist =" IF EXISTS";
	return spip_mysql_query("DROP TABLE$exist $table", $serveur);
}

// http://doc.spip.org/@spip_mysql_showbase
function spip_mysql_showbase($match, $serveur='')
{
	return spip_mysql_query("SHOW TABLES LIKE '$match'", $serveur);
}

// http://doc.spip.org/@spip_mysql_repair
function spip_mysql_repair($table, $serveur='')
{
	return spip_mysql_query("REPAIR TABLE $table", $serveur);
}

// http://doc.spip.org/@spip_mysql_showtable
function spip_mysql_showtable($nom_table, $serveur='')
{
	$a = spip_mysql_query("SHOW TABLES LIKE '$nom_table'", $serveur);
	if (!$a) return "";
	if (!mysql_fetch_array($a)) return "";
	list(,$a) = mysql_fetch_array(spip_mysql_query("SHOW CREATE TABLE $nom_table", $serveur),MYSQL_NUM);
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
		return array('field' => $fields, 'key' => $keys);
	}
}

//
// Recuperation des resultats
//

// http://doc.spip.org/@spip_mysql_fetch
function spip_mysql_fetch($r, $t='', $serveur='') {
	if (!$t) $t = MYSQL_ASSOC;
	if ($r) return mysql_fetch_array($r, $t);
}


// http://doc.spip.org/@spip_mysql_countsel
function spip_mysql_countsel($from = array(), $where = array(),
			     $groupby = '', $limit = '', $sousrequete = '', $having = array(), $serveur='')
{
	$r = spip_mysql_select('COUNT(*)', $from, $where,$groupby, '', $limit,
			$having, $serveur);
	if ($r) list($r) = mysql_fetch_array($r, MYSQL_NUM);
	return $r;
}

// http://doc.spip.org/@spip_mysql_error
function spip_mysql_error($query='', $serveur='') {
	$s = mysql_error();
	if ($s) spip_log("$s - $query", 'mysql');
	return $s;
}

// A transposer dans les portages
// http://doc.spip.org/@spip_mysql_errno
function spip_mysql_errno($serveur='') {
	$s = mysql_errno();
	// 2006 MySQL server has gone away
	// 2013 Lost connection to MySQL server during query
	if (in_array($s, array(2006,2013)))
		define('spip_interdire_cache', true);
	if ($s) spip_log("Erreur mysql $s");
	return $s;
}

// Interface de abstract_sql
// http://doc.spip.org/@spip_mysql_count
function spip_mysql_count($r, $serveur='') {
	if ($r)	return mysql_num_rows($r);
}


// http://doc.spip.org/@spip_mysql_free
function spip_mysql_free($r, $serveur='') {
	return mysql_free_result($r);
}

// http://doc.spip.org/@spip_mysql_insert
function spip_mysql_insert($table, $champs, $valeurs, $desc='', $serveur='') {

	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	if ($prefixe) $table = preg_replace('/^spip/', $prefixe, $table);

	$t = !isset($_GET['var_profile']) ? 0 : trace_query_start();
	$query="INSERT INTO $table $champs VALUES $valeurs";
#	spip_log($query);
	if (mysql_query($query, $link))
		$r = mysql_insert_id($link);
	else {
	  if ($e = spip_mysql_errno())	// Log de l'erreur eventuelle
		$e .= spip_mysql_error($query); // et du fautif
	}
	return $t ? trace_query_end($query, $t, $r, $e, $serveur) : $r;

	// return $r ? $r : (($r===0) ? -1 : 0); pb avec le multi-base.
}

// http://doc.spip.org/@spip_mysql_insertq
function spip_mysql_insertq($table, $couples=array(), $desc=array(), $serveur='') {

	if (!$desc) $desc = description_table($table);
	if (!$desc) die("$table insertion sans description");
	$fields =  isset($desc['field'])?$desc['field']:array();

	foreach ($couples as $champ => $val) {
		$couples[$champ]= spip_mysql_cite($val, $fields[$champ]);
	}

	return spip_mysql_insert($table, "(".join(',',array_keys($couples)).")", "(".join(',', $couples).")", $desc, $serveur);
}

// http://doc.spip.org/@spip_mysql_update
function spip_mysql_update($table, $champs, $where='', $desc='', $serveur='') {
	$set = array();
	foreach ($champs as $champ => $val)
		$set[] = $champ . "=$val";
	if (!empty($set))
		return spip_mysql_query(
			  calculer_mysql_expression('UPDATE', $table, ',')
			. calculer_mysql_expression('SET', $set, ',')
			. calculer_mysql_expression('WHERE', $where), 
			$serveur);
}

// idem, mais les valeurs sont des constantes a mettre entre apostrophes
// sauf les expressions de date lorsqu'il s'agit de fonctions SQL (NOW etc)
// http://doc.spip.org/@spip_mysql_updateq
function spip_mysql_updateq($table, $champs, $where='', $desc=array(), $serveur='') {

	if (!$champs) return;
	if (!$desc) $desc = description_table($table);
	if (!$desc) die("$table insertion sans description");
	$fields =  $desc['field'];
	$set = array();
	foreach ($champs as $champ => $val) {
		$set[] = $champ . '=' . spip_mysql_cite($val, $fields[$champ]);
	}
	return spip_mysql_query(
			  calculer_mysql_expression('UPDATE', $table, ',')
			. calculer_mysql_expression('SET', $set, ',')
			. calculer_mysql_expression('WHERE', $where),
			$serveur);
}

// http://doc.spip.org/@spip_mysql_delete
function spip_mysql_delete($table, $where='', $serveur='') {
	return spip_mysql_query(
			  calculer_mysql_expression('DELETE FROM', $table, ',')
			. calculer_mysql_expression('WHERE', $where),
			$serveur);
}

// http://doc.spip.org/@spip_mysql_replace
function spip_mysql_replace($table, $values, $keys=array(), $serveur='') {
	return spip_mysql_query("REPLACE $table (" . join(',',array_keys($values)) . ') VALUES (' .join(',',array_map('_q', $values)) . ')', $serveur);
}

// http://doc.spip.org/@spip_mysql_multi
function spip_mysql_multi ($objet, $lang) {
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

function spip_mysql_hex($v)
{
	return "0x" . $v;
}

function spip_mysql_quote($v)
{
	return _q($v);
}

//
// IN (...) est limite a 255 elements, d'ou cette fonction assistante
//
function spip_mysql_in($val, $valeurs, $not='', $serveur='') {
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

// pour compatibilite. Ne plus utiliser.
// http://doc.spip.org/@calcul_mysql_in
function calcul_mysql_in($val, $valeurs, $not='') {
	if (is_array($valeurs))
		$valeurs = join(',', array_map('_q', $valeurs));
	elseif ($valeurs[0]===',') $valeurs = substr($valeurs,1);
	if (!strlen(trim($valeurs))) return ($not ? "0=0" : '0=1');
	return spip_mysql_in($val, $valeurs, $not);
}

// http://doc.spip.org/@spip_mysql_cite
function spip_mysql_cite($v, $type) {
	if (sql_test_date($type) AND preg_match('/^\w+\(/', $v)
	OR (sql_test_int($type)
		 AND (is_numeric($v)
		      OR (ctype_xdigit(substr($v,2))
			  AND $v[0]=='0' AND $v[1]=='x'))))
		return $v;
	else return  ("'" . addslashes($v) . "'");
}

// Ces deux fonctions n'ont pas d'equivalent exact PostGres
// et ne sont la que pour compatibilite avec les extensions de SPIP < 1.9.3

//
// Poser un verrou local a un SPIP donne
// Changer de nom toutes les heures en cas de blocage MySQL (ca arrive)
//
// http://doc.spip.org/@spip_get_lock
function spip_get_lock($nom, $timeout = 0) {

	define('_LOCK_TIME', intval(time()/3600-316982));

	$connexion = $GLOBALS['connexions'][0];
	$prefixe = $connexion['prefixe'];
	$db = $connexion['db'];
	$nom = "$bd:$prefix:$nom" .  _LOCK_TIME;

	$q = mysql_query("SELECT GET_LOCK(" . _q($nom) . ", $timeout) AS n");
	$q = @sql_fetch($q);
	if (!$q) spip_log("pas de lock sql pour $nom");
	return $q['n'];
}

// http://doc.spip.org/@spip_release_lock
function spip_release_lock($nom) {

	$connexion = $GLOBALS['connexions'][0];
	$prefixe = $connexion['prefixe'];
	$db = $connexion['db'];
	$nom = "$bd:$prefix:$nom" . _LOCK_TIME;

	@mysql_query("SELECT RELEASE_LOCK(" . _q($nom) . ")");
}

?>
