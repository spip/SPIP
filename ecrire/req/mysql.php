<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// fonction pour la premiere connexion a un serveur MySQL

// http://doc.spip.org/@req_mysql_dist
function req_mysql_dist($host, $port, $login, $pass, $db='', $prefixe='') {
	if (!charger_php_extension('mysql')) return false;
	if ($port > 0) $host = "$host:$port";
	$link = @mysql_connect($host, $login, $pass, true);
	if (!$link) {
		spip_log('Echec mysql_connect. Erreur : ' . mysql_error(),'mysql');
		return false;
	}
	$last = '';
	if (!$db) {
		$ok = $link;
		$db = 'spip';
	} else {
		$ok = spip_mysql_selectdb($db);
		if (defined('_MYSQL_SET_SQL_MODE') 
		  OR defined('_MYSQL_SQL_MODE_TEXT_NOT_NULL') // compatibilite
		  )
			mysql_query($last = "set sql_mode=''");
	}
#	spip_log("Connexion vers $host, base $db, prefixe $prefixe "
#		 . ($ok ? "operationnelle sur $link" : 'impossible'));

	return !$ok ? false : array(
		'db' => $db,
		'last' => $last,
		'prefixe' => $prefixe ? $prefixe : $db,
		'link' => $GLOBALS['mysql_rappel_connexion'] ? $link : false,
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
function spip_mysql_set_charset($charset, $serveur='',$requeter=true,$requeter=true){
	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
	#spip_log("changement de charset sql : "."SET NAMES "._q($charset));
	return mysql_query($connexion['last'] = "SET NAMES "._q($charset));
}

// http://doc.spip.org/@spip_mysql_get_charset
function spip_mysql_get_charset($charset=array(), $serveur='',$requeter=true){
	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
	$connexion['last'] = $c = "SHOW CHARACTER SET"
	. (!$charset ? '' : (" LIKE "._q($charset['charset'])));

	return spip_mysql_fetch(mysql_query($c), NULL, $serveur);
}

// obsolete, ne plus utiliser
// http://doc.spip.org/@spip_query_db
function spip_query_db($query, $serveur='',$requeter=true) {
	return spip_mysql_query($query, $serveur, $requeter);
}

// Fonction de requete generale, munie d'une trace a la demande

// http://doc.spip.org/@spip_mysql_query
function spip_mysql_query($query, $serveur='',$requeter=true) {

	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	$query = traite_query($query, $db, $prefixe);

	// renvoyer la requete inerte si demandee
	if (!$requeter) return $query;

	if (isset($_GET['var_profile'])) {
		include_spip('public/tracer');
		$t = trace_query_start();
	} else $t = 0 ;
 
	$connexion['last'] = $query;
	$r = $link ? mysql_query($query, $link) : mysql_query($query);

	if ($e = spip_mysql_errno($serveur))	// Log de l'erreur eventuelle
		$e .= spip_mysql_error($query, $serveur); // et du fautif
	return $t ? trace_query_end($query, $t, $r, $e, $serveur) : $r;
}

// http://doc.spip.org/@spip_mysql_alter
function spip_mysql_alter($query, $serveur='',$requeter=true){
	return spip_mysql_query("ALTER ".$query, $serveur, $requeter); # i.e. que PG se debrouille
}

// http://doc.spip.org/@spip_mysql_optimize
function spip_mysql_optimize($table, $serveur='',$requeter=true){
	spip_mysql_query("OPTIMIZE TABLE ". $table);
	return true;
}

// http://doc.spip.org/@spip_mysql_explain
function spip_mysql_explain($query, $serveur='',$requeter=true){
	if (strpos(ltrim($query), 'SELECT') !== 0) return array();
	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
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
			   $serveur='',$requeter=true) {


	$from = (!is_array($from) ? $from : spip_mysql_select_as($from));
	$query = 
		  calculer_mysql_expression('SELECT', $select, ', ')
		. calculer_mysql_expression('FROM', $from, ', ')
		. calculer_mysql_expression('WHERE', $where)
		. calculer_mysql_expression('GROUP BY', $groupby, ',')
		. calculer_mysql_expression('HAVING', $having)
		. ($orderby ? ("\nORDER BY " . spip_mysql_order($orderby)) :'')
		. ($limit ? "\nLIMIT $limit" : '');

	// renvoyer la requete inerte si demandee
	if ($requeter === false) return $query;
	$r = spip_mysql_query($query, $serveur, $requeter);
	return $r ? $r : $query;
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

// http://doc.spip.org/@calculer_mysql_expression
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
	$res = '';
	foreach($args as $k => $v) {
		if (substr($k,-1)=='@') {
			// c'est une jointure qui se refere au from precedent
			// pas de virgule
		  $res .= '  ' . $v ;
		}
		else {
		  if (!is_numeric($k)) {
		  	$p = strpos($v, " ");
			if ($p)
			  $v = substr($v,0,$p) . " AS `$k`" . substr($v,$p);
			else $v .= " AS `$k`";
		  }
		      
		  $res .= ', ' . $v ;
		}
	}
	return substr($res,2);
}

//
// Changer les noms des tables ($table_prefix)
// Quand tous les appels SQL seront abstraits on pourra l'ameliorer

define('_SQL_PREFIXE_TABLE', '/([,\s])spip_/S');

// http://doc.spip.org/@traite_query
function traite_query($query, $db='', $prefixe='') {

	if ($GLOBALS['mysql_rappel_nom_base'] AND $db)
		$pref = '`'. $db.'`.';
	else $pref = '';

	if ($prefixe)
		$pref .= $prefixe . "_";

	if (!preg_match('/\s(SET|VALUES|WHERE|DATABASE)\s/i', $query, $regs)) {
		$suite ='';
	} else {
		$suite = strstr($query, $regs[0]);
		$query = substr($query, 0, -strlen($suite));
		if (preg_match('/^(.*?)([(]\s*SELECT\b.*)$/si', $suite, $r)) {
		  $suite = $r[1] . traite_query($r[2], $db, $prefixe);
		}
	}
	$r = preg_replace(_SQL_PREFIXE_TABLE, '\1'.$pref, $query) . $suite;
#	spip_log("traite_query: " . substr($r,0, 50) . ".... $db, $prefixe");
	return $r;
}

// http://doc.spip.org/@spip_mysql_selectdb
function spip_mysql_selectdb($db) {
	$ok = mysql_select_db($db);
	if (!$ok)
		spip_log('Echec mysql_selectdb. Erreur : ' . mysql_error(),'mysql');
	return $ok;
}


// Retourne les bases accessibles
// Attention on n'a pas toujours les droits

// http://doc.spip.org/@spip_mysql_listdbs
function spip_mysql_listdbs($serveur='',$requeter=true) {
	return @mysql_list_dbs();
}

// Fonction de creation d'une table SQL nommee $nom
// a partir de 2 tableaux PHP :
// champs: champ => type
// cles: type-de-cle => champ(s)
// si $autoinc, c'est une auto-increment (i.e. serial) sur la Primary Key
// Le nom des caches doit etre inferieur a 64 caracteres

// http://doc.spip.org/@spip_mysql_create
function spip_mysql_create($nom, $champs, $cles, $autoinc=false, $temporary=false, $serveur='',$requeter=true) {

	$query = ''; $keys = ''; $s = ''; $p='';

	// certains plugins declarent les tables  (permet leur inclusion dans le dump)
	// sans les renseigner (laisse le compilo recuperer la description)
	if (!is_array($champs) || !is_array($cles)) 
		return;

	$res = spip_mysql_query("SELECT version() as v");
	if ($row = mysql_fetch_array($res)
	 && (version_compare($row['v'],'5.0','>=')))
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
			. (($autoinc && ($p == $k) && preg_match(',\b(big|small|medium)?int\b,i', $v))
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

function spip_mysql_create_base($nom, $serveur='',$requeter=true) {
  return spip_mysql_query("CREATE DATABASE `$nom`", $serveur, $requeter);
}

// Fonction de creation d'une vue SQL nommee $nom
// http://doc.spip.org/@spip_mysql_create_view
function spip_mysql_create_view($nom, $query_select, $serveur='',$requeter=true) {
	if (!$query_select) return false;
	// vue deja presente
	if (sql_showtable($nom, false, $serveur)) {
		spip_log("Echec creation d'une vue sql ($nom) car celle-ci existe deja (serveur:$serveur)");
		return false;
	}
	
	$query = "CREATE VIEW $nom AS ". $query_select;
	return spip_mysql_query($query, $serveur, $requeter);
}


// http://doc.spip.org/@spip_mysql_drop_table
function spip_mysql_drop_table($table, $exist='', $serveur='',$requeter=true)
{
	if ($exist) $exist =" IF EXISTS";
	return spip_mysql_query("DROP TABLE$exist `$table`", $serveur, $requeter);
}

// supprime une vue 
// http://doc.spip.org/@spip_mysql_drop_view
function spip_mysql_drop_view($view, $exist='', $serveur='',$requeter=true) {
	if ($exist) $exist =" IF EXISTS";
	return spip_mysql_query("DROP VIEW$exist `$view`", $serveur, $requeter);
}

// http://doc.spip.org/@spip_mysql_showbase
function spip_mysql_showbase($match, $serveur='',$requeter=true)
{
	return spip_mysql_query("SHOW TABLES LIKE " . _q($match), $serveur, $requeter);
}

// http://doc.spip.org/@spip_mysql_repair
function spip_mysql_repair($table, $serveur='',$requeter=true)
{
	return spip_mysql_query("REPAIR TABLE `$table`", $serveur, $requeter);
}

// Recupere la definition d'une table ou d'une vue MySQL
// colonnes, indexes, etc.
// au meme format que la definition des tables de SPIP
// http://doc.spip.org/@spip_mysql_showtable
function spip_mysql_showtable($nom_table, $serveur='',$requeter=true)
{
	$s = spip_mysql_query("SHOW CREATE TABLE `$nom_table`", $serveur, $requeter);
	if (!$s) return '';
	if (!$requeter) return $s;

	list(,$a) = mysql_fetch_array($s ,MYSQL_NUM);
	if (preg_match("/^[^(),]*\((([^()]*\([^()]*\)[^()]*)*)\)[^()]*$/", $a, $r)){
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
		spip_mysql_free($s);
		return array('field' => $fields, 'key' => $keys);
	}

	$res = spip_mysql_query("SHOW COLUMNS FROM `$nom_table`", $serveur);
	if($res) {
	  $nfields = array();
	  $nkeys = array();
	  while($val = spip_mysql_fetch($res)) {
		$nfields[$val["Field"]] = $val['Type'];
		if($val['Null']=='NO') {
		  $nfields[$val["Field"]] .= ' NOT NULL'; 
		}
		if($val['Default'] === '0' || $val['Default']) {
		  if(preg_match('/[A-Z_]/',$val['Default'])) {
			$nfields[$val["Field"]] .= ' DEFAULT '.$val['Default'];		  
		  } else {
			$nfields[$val["Field"]] .= " DEFAULT '".$val['Default']."'";		  
		  }
		}
		if($val['Extra'])
		  $nfields[$val["Field"]] .= ' '.$val['Extra'];
		if($val['Key'] == 'PRI') {
		  $nkeys['PRIMARY KEY'] = $val["Field"];
		} else if($val['Key'] == 'MUL') {
		  $nkeys['KEY '.$val["Field"]] = $val["Field"];
		} else if($val['Key'] == 'UNI') {
		  $nkeys['UNIQUE KEY '.$val["Field"]] = $val["Field"];
		}
	  }
	  spip_mysql_free($res);
	  return array('field' => $nfields, 'key' => $nkeys);
	}
	return "";
}

//
// Recuperation des resultats
//

// http://doc.spip.org/@spip_mysql_fetch
function spip_mysql_fetch($r, $t='', $serveur='',$requeter=true) {
	if (!$t) $t = MYSQL_ASSOC;
	if ($r) return mysql_fetch_array($r, $t);
}

function spip_mysql_seek($r, $row_number, $serveur='',$requeter=true) {
	if ($r) return mysql_data_seek($r,$row_number);
}


// http://doc.spip.org/@spip_mysql_countsel
function spip_mysql_countsel($from = array(), $where = array(),
			     $groupby = '', $having = array(), $serveur='',$requeter=true)
{
	$c = !$groupby ? '*' : ('DISTINCT ' . (is_string($groupby) ? $groupby : join(',', $groupby)));

	$r = spip_mysql_select("COUNT($c)", $from, $where,'', '', '', $having, $serveur, $requeter);

	if (!$requeter) return $r;
	if (!is_resource($r)) return 0;
	list($c) = mysql_fetch_array($r, MYSQL_NUM);
	mysql_free_result($r);
	return $c;
}

// Bien specifier le serveur auquel on s'adresse,
// mais a l'install la globale n'est pas encore completement definie
// http://doc.spip.org/@spip_mysql_error
function spip_mysql_error($query='', $serveur='',$requeter=true) {
	$link = $GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0]['link'];
	$s = $link ? mysql_error($link) : mysql_error();
	if ($s) spip_log("$s - $query", 'mysql');
	return $s;
}

// A transposer dans les portages
// http://doc.spip.org/@spip_mysql_errno
function spip_mysql_errno($serveur='',$requeter=true) {
	$link = $GLOBALS['connexions'][$serveur ? $serveur : 0]['link'];
	$s = $link ? mysql_errno($link) : mysql_errno();
	// 2006 MySQL server has gone away
	// 2013 Lost connection to MySQL server during query
	if (in_array($s, array(2006,2013)))
		define('spip_interdire_cache', true);
	if ($s) spip_log("Erreur mysql $s");
	return $s;
}

// Interface de abstract_sql
// http://doc.spip.org/@spip_mysql_count
function spip_mysql_count($r, $serveur='',$requeter=true) {
	if ($r)	return mysql_num_rows($r);
}


// http://doc.spip.org/@spip_mysql_free
function spip_mysql_free($r, $serveur='',$requeter=true) {
	return mysql_free_result($r);
}

// http://doc.spip.org/@spip_mysql_insert
function spip_mysql_insert($table, $champs, $valeurs, $desc='', $serveur='',$requeter=true) {

	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	if ($prefixe) $table = preg_replace('/^spip/', $prefixe, $table);
	
	$query ="INSERT INTO $table $champs VALUES $valeurs";
	if (!$requeter) return $query;
	
	if (isset($_GET['var_profile'])) {
		include_spip('public/tracer');
		$t = trace_query_start();
	} else $t = 0 ;

	$connexion['last'] = $query;
#	spip_log($query);
	if (mysql_query($query, $link))
		$r = mysql_insert_id($link);
	else {
	  if ($e = spip_mysql_errno($serveur))	// Log de l'erreur eventuelle
		$e .= spip_mysql_error($query, $serveur); // et du fautif
	}
	return $t ? trace_query_end($query, $t, $r, $e, $serveur) : $r;

	// return $r ? $r : (($r===0) ? -1 : 0); pb avec le multi-base.
}

// http://doc.spip.org/@spip_mysql_insertq
function spip_mysql_insertq($table, $couples=array(), $desc=array(), $serveur='',$requeter=true) {

	if (!$desc) $desc = description_table($table);
	if (!$desc) $couples = array();
	$fields =  isset($desc['field'])?$desc['field']:array();

	foreach ($couples as $champ => $val) {
		$couples[$champ]= spip_mysql_cite($val, $fields[$champ]);
	}

	return spip_mysql_insert($table, "(".join(',',array_keys($couples)).")", "(".join(',', $couples).")", $desc, $serveur, $requeter);
}


// http://doc.spip.org/@spip_mysql_insertq_multi
function spip_mysql_insertq_multi($table, $tab_couples=array(), $desc=array(), $serveur='',$requeter=true) {

	if (!$desc) $desc = description_table($table);
	if (!$desc) $tab_couples = array();
	$fields =  isset($desc['field'])?$desc['field']:array();
	
	$cles = "(" . join(',',array_keys($tab_couples[0])) . ')';
	$valeurs = array();
	foreach ($tab_couples as $couples) {
		foreach ($couples as $champ => $val){
			$couples[$champ]= spip_mysql_cite($val, $fields[$champ]);
		}
		$valeurs[] = '(' .join(',', $couples) . ')';
	}

	// Inserer par groupes de 100 max pour eviter un debordement de pile
	$r = false;
	do {
		$ins = array_slice($valeurs,0,100);
		$valeurs = array_slice($valeurs,100);
		$r = spip_mysql_insert($table, $cles, join(', ', $ins), $desc, $serveur, $requeter);
	}  while (count($valeurs));

	return $r; // dans le cas d'une table auto_increment, le dernier insert_id
}

// http://doc.spip.org/@spip_mysql_update
function spip_mysql_update($table, $champs, $where='', $desc='', $serveur='',$requeter=true) {
	$set = array();
	foreach ($champs as $champ => $val)
		$set[] = $champ . "=$val";
	if (!empty($set))
		return spip_mysql_query(
			  calculer_mysql_expression('UPDATE', $table, ',')
			. calculer_mysql_expression('SET', $set, ',')
			. calculer_mysql_expression('WHERE', $where), 
			$serveur, $requeter);
}

// idem, mais les valeurs sont des constantes a mettre entre apostrophes
// sauf les expressions de date lorsqu'il s'agit de fonctions SQL (NOW etc)
// http://doc.spip.org/@spip_mysql_updateq
function spip_mysql_updateq($table, $champs, $where='', $desc=array(), $serveur='',$requeter=true) {

	if (!$champs) return;
	if (!$desc) $desc = description_table($table);
	if (!$desc) $champs = array(); else $fields =  $desc['field'];
	$set = array();
	foreach ($champs as $champ => $val) {
		$set[] = $champ . '=' . spip_mysql_cite($val, $fields[$champ]);
	}
	return spip_mysql_query(
			  calculer_mysql_expression('UPDATE', $table, ',')
			. calculer_mysql_expression('SET', $set, ',')
			. calculer_mysql_expression('WHERE', $where),
			$serveur, $requeter);
}

// http://doc.spip.org/@spip_mysql_delete
function spip_mysql_delete($table, $where='', $serveur='',$requeter=true) {
	$res = spip_mysql_query(
			  calculer_mysql_expression('DELETE FROM', $table, ',')
			. calculer_mysql_expression('WHERE', $where),
			$serveur, $requeter);
	if (!$requeter) return $res;
	if ($res){
		$connexion = &$GLOBALS['connexions'][$serveur ? $serveur : 0];
		$link = $connexion['link'];
		return $link ? mysql_affected_rows($link) : mysql_affected_rows();
	}
	else
		return false;
}

// http://doc.spip.org/@spip_mysql_replace
function spip_mysql_replace($table, $couples, $desc=array(), $serveur='',$requeter=true) {
	return spip_mysql_query("REPLACE $table (" . join(',',array_keys($couples)) . ') VALUES (' .join(',',array_map('_q', $couples)) . ')', $serveur, $requeter);
}


// http://doc.spip.org/@spip_mysql_replace_multi
function spip_mysql_replace_multi($table, $tab_couples, $desc=array(), $serveur='',$requeter=true) {
	$cles = "(" . join(',',array_keys($tab_couples[0])). ')';
	$valeurs = array();
	foreach ($tab_couples as $couples) {
		$valeurs[] = '(' .join(',',array_map('_q', $couples)) . ')';
	}
	$valeurs = implode(', ',$valeurs);
	return spip_mysql_query("REPLACE $table $cles VALUES $valeurs", $serveur, $requeter);
}


// http://doc.spip.org/@spip_mysql_multi
function spip_mysql_multi ($objet, $lang) {
	$lengthlang = strlen("[$lang]");
	$posmulti = "INSTR(".$objet.", '<multi>')";
	$posfinmulti = "INSTR(".$objet.", '</multi>')";
	$debutchaine = "LEFT(".$objet.", $posmulti-1)";
	$finchaine = "RIGHT(".$objet.", CHAR_LENGTH(".$objet.") -(7+$posfinmulti))";
	$chainemulti = "TRIM(SUBSTRING(".$objet.", $posmulti+7, $posfinmulti -(7+$posmulti)))";
	$poslang = "INSTR($chainemulti,'[".$lang."]')";
	$poslang = "IF($poslang=0,INSTR($chainemulti,']')+1,$poslang+$lengthlang)";
	$chainelang = "TRIM(SUBSTRING(".$objet.", $posmulti+7+$poslang-1,$posfinmulti -($posmulti+7+$poslang-1) ))";
	$posfinlang = "INSTR(".$chainelang.", '[')";
	$chainelang = "IF($posfinlang>0,LEFT($chainelang,$posfinlang-1),$chainelang)";
	//$chainelang = "LEFT($chainelang,$posfinlang-1)";
	$retour = "(TRIM(IF($posmulti = 0 , ".
		"     TRIM(".$objet."), ".
		"     CONCAT( ".
		"          $debutchaine, ".
		"          IF( ".
		"               $poslang = 0, ".
		"                     $chainemulti, ".
		"               $chainelang".
		"          ), ". 
		"          $finchaine".
		"     ) ".
		"))) AS multi";

	return $retour;
}

// http://doc.spip.org/@spip_mysql_hex
function spip_mysql_hex($v)
{
	return "0x" . $v;
}

function spip_mysql_quote($v, $type='')
{
	return ($type === 'int' AND !$v) ? '0' :  _q($v);
}

function spip_mysql_date_proche($champ, $interval, $unite)
{
	return '('
	. $champ
        . (($interval <= 0) ? '>' : '<')
        . (($interval <= 0) ? 'DATE_SUB' : 'DATE_ADD')
	. '('
	. sql_quote(date('Y-m-d H:i:s'))
	. ', INTERVAL '
	. (($interval > 0) ? $interval : (0-$interval))
	. ' '
	. $unite
	. '))';
}

//
// IN (...) est limite a 255 elements, d'ou cette fonction assistante
//
// http://doc.spip.org/@spip_mysql_in
function spip_mysql_in($val, $valeurs, $not='', $serveur='',$requeter=true) {
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

	$connexion = &$GLOBALS['connexions'][0];
	$prefixe = $connexion['prefixe'];
	$nom = "$bd:$prefix:$nom" .  _LOCK_TIME;

	$connexion['last'] = $q = "SELECT GET_LOCK(" . _q($nom) . ", $timeout) AS n";
	$q = @sql_fetch(mysql_query($q));
	if (!$q) spip_log("pas de lock sql pour $nom");
	return $q['n'];
}

// http://doc.spip.org/@spip_release_lock
function spip_release_lock($nom) {

	$connexion = &$GLOBALS['connexions'][0];
	$prefixe = $connexion['prefixe'];
	$nom = "$bd:$prefix:$nom" . _LOCK_TIME;

	$connexion['last'] = $q = "SELECT RELEASE_LOCK(" . _q($nom) . ")";
	@mysql_query($q);
}

// Renvoie false si on n'a pas les fonctions mysql (pour l'install)
// http://doc.spip.org/@spip_versions_mysql
function spip_versions_mysql() {
	charger_php_extension('mysql');
	return function_exists('mysql_query');
}

// Tester si mysql ne veut pas du nom de la base dans les requetes

// http://doc.spip.org/@test_rappel_nom_base_mysql
function test_rappel_nom_base_mysql($server_db)
{
	$GLOBALS['mysql_rappel_nom_base'] = true;
	sql_delete('spip_meta', "nom='mysql_rappel_nom_base'", $server_db);
	$ok = spip_query("INSERT INTO spip_meta (nom,valeur) VALUES ('mysql_rappel_nom_base', 'test')", $server_db);

	if ($ok) {
		sql_delete('spip_meta', "nom='mysql_rappel_nom_base'", $server_db);
		return '';
	} else {
		$GLOBALS['mysql_rappel_nom_base'] = false;
		return "\$GLOBALS['mysql_rappel_nom_base'] = false; ".
		"/* echec de test_rappel_nom_base_mysql a l'installation. */\n";
	}
}

// http://doc.spip.org/@test_sql_mode_mysql
function test_sql_mode_mysql($server_db){
	$res = sql_select("version() as v",'','','','','','',$server_db);
	$row = sql_fetch($res,$server_db);
	if (version_compare($row['v'],'5.0.0','>=')){
		define('_MYSQL_SET_SQL_MODE',true);
		return "define('_MYSQL_SET_SQL_MODE',true);\n";
	}
	return '';
}

?>
