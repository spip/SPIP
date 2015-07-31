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

//constantes spip pour mysql_fetch_array()
define('SPIP_BOTH', MYSQL_BOTH);
define('SPIP_ASSOC', MYSQL_ASSOC);
define('SPIP_NUM', MYSQL_NUM);

//
// Appel de requetes SQL
//

// fonction pour la premiere connexion

// http://doc.spip.org/@base_db_mysql_dist
function base_db_mysql_dist($host, $port, $login, $pass, $db='') {
	global $spip_mysql_link, $spip_mysql_db;	// pour connexions multiples
	// gerer le fichier tmp/mysql_out
	## TODO : ajouter md5(parametres de connexion)
	if (@file_exists(_DIR_TMP.'mysql_out')
	AND (time() - @filemtime(_DIR_TMP.'mysql_out') < 30)
	    AND !defined('_ECRIRE_INSTALL')) {
		return false;
	}
	if ($port > 0) $host = "$host:$port";
	$spip_mysql_link = mysql_connect($host, $login, $pass);

	if (!$db)
		$ok = $spip_mysql_link;
	else  {
	  $spip_mysql_db = $db;
	  $ok = spip_mysql_selectdb($db);
	  if (defined('_MYSQL_SQL_MODE_TEXT_NOT_NULL'))
		mysql_query("set sql_mode=''");
	  if (isset($GLOBALS['meta']['charset_sql_connexion']))
		mysql_query("SET NAMES "._q($GLOBALS['meta']['charset_sql_connexion']));
	
	  if ($ok)
		  $ok = spip_mysql_count(spip_mysql_query('SELECT COUNT(*) FROM spip_meta'));
	}
	// En cas d'erreur marquer le fichier mysql_out
	if (!$ok
	AND !defined('_ECRIRE_INSTALL')) {
		@touch(_DIR_TMP.'mysql_out');
		$err = 'Echec connexion MySQL '.spip_mysql_errno().' '.spip_mysql_error('connexion');
		spip_log($err);
		spip_log($err, 'mysql');
	} 

	return $ok ? 'spip_mysql_query' : false;
}

// http://doc.spip.org/@spip_mysql_set_connect_charset
function spip_mysql_set_connect_charset($charset){
	#spip_log("changement de charset sql : "."SET NAMES "._q($charset));
	return mysql_query("SET NAMES "._q($charset));
}

// obsolete, ne plus utiliser
// http://doc.spip.org/@spip_query_db
function spip_query_db($query) {
	return spip_mysql_query($query);
}

// http://doc.spip.org/@spip_mysql_query
function spip_mysql_query($query) {

	$query = traite_query($query); // traitement du prefixe de table

	$r = $GLOBALS['mysql_rappel_connexion'] ?  $GLOBALS['spip_mysql_link'] : false;
	$t = !isset($_GET['var_profile']) ? 0 : trace_query_start();
	$r = $r ? mysql_query($query, $r) : mysql_query($query);

	if ($e = spip_mysql_errno())	// Log de l'erreur eventuelle
		$e .= spip_mysql_error($query); // et du fautif
	return $t ? trace_query_end($query, $t, $r, $e) : $r;
}


// fonction appelant la precedente  specifiquement pour l'espace public
// c'est une instance de sql_select, voir ses specs dans abstract.php
// traite_query pourrait y est fait d'avance, à moindre cout.
// Les \n et \t sont utiles au debusqueur.

// La parametre sous_requete n'est plus utilise

// http://doc.spip.org/@spip_mysql_select
function spip_mysql_select($select, $from, $where,
			   $groupby, $orderby, $limit,
			   $sousrequete, $having,
			   $table='', $id='', $server='') {

	$query = 'SELECT ' .
		(!is_array($select) ? $select : join(", ", $select)) .
		(!$from ? '' :
			("\nFROM " .
			(!is_array($from) ? $from : spip_select_as($from))))
		. (!$where ? '' : ("\nWHERE " . (!is_array($where) ? $where : (join("\n\tAND ", array_map('calculer_where', $where))))))
		. ($groupby ? "\nGROUP BY $groupby" : '')
		. (!$having ? '' : "\nHAVING " . (!is_array($having) ? $having : (join("\n\tAND ", array_map('calculer_where', $having)))))
		. ($orderby ? ("\nORDER BY " . spip_mysql_order($orderby)) :'')
		. ($limit ? "\nLIMIT $limit" : '');

	// Erreur ? C'est du debug de squelette, ou une erreur du serveur

	if (isset($GLOBALS['var_mode']) AND $GLOBALS['var_mode'] == 'debug') {
		include_spip('public/debug');
		boucle_debug_resultat($id, 'requete', $query);
	}

	if (!($res = spip_mysql_query($query, $server))) {
		include_spip('public/debug');
		erreur_requete_boucle(substr($query, 7), $id, $table,
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


// http://doc.spip.org/@calculer_where
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

// http://doc.spip.org/@spip_select_as
function spip_select_as($args)
{
	$argsas = "";
	foreach($args as $k => $v) {
		$argsas .= ', ' . $v . (is_numeric($k) ? '' : " AS `$k`");
	}
	return substr($argsas,2);
}

//
// Changer les noms des tables ($table_prefix)
// Quand tous les appels SQL seront abstraits on pourra l'ameliorer

// http://doc.spip.org/@traite_query
function traite_query($query) {

	if ($GLOBALS['mysql_rappel_nom_base'] AND $GLOBALS['spip_mysql_db'])
	  $pref = '`'. $GLOBALS['spip_mysql_db'].'`.';
	else $pref = '';

	if ($GLOBALS['table_prefix']) $pref .= $GLOBALS['table_prefix']."_";

	if (preg_match('/\s(SET|VALUES|WHERE)\s/i', $query, $regs)) {
		$suite = strstr($query, $regs[0]);
		$query = substr($query, 0, -strlen($suite));
	} else $suite ='';

	return preg_replace('/([,\s])spip_/', '\1'.$pref, $query) . $suite;
}

// Fonction de creation d'une table SQL nommee $nom
// a partir de 2 tableaux PHP :
// champs: champ => type
// cles: type-de-cle => champ(s)
// si $autoinc, c'est une auto-increment (i.e. serial) sur la Primary Key
// Le nom des caches doit etre inferieur a 64 caracteres


// http://doc.spip.org/@spip_mysql_selectdb
function spip_mysql_selectdb($db) {
	return mysql_select_db($db);
}


// Retourne les bases accessibles

// http://doc.spip.org/@spip_mysql_listdbs
function spip_mysql_listdbs() {
	return mysql_list_dbs();
}


// http://doc.spip.org/@spip_mysql_create
function spip_mysql_create($nom, $champs, $cles, $autoinc=false, $temporary=false) {
	$query = ''; $keys = ''; $s = ''; $p='';

	// certains plugins declarent les tables  (permet leur inclusion dans le dump)
	// sans les renseigner (laisse le compilo recuperer la description)
	if (!is_array($champs) || !is_array($cles)) 
		return;

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
	spip_mysql_query($q);
}

// http://doc.spip.org/@spip_mysql_showtable
function spip_mysql_showtable($nom_table)
{
	$a = spip_mysql_query("SHOW TABLES LIKE '$nom_table'");
	if (!$a) return "";
	if (!mysql_fetch_array($a)) return "";
	list(,$a) = mysql_fetch_array(spip_mysql_query("SHOW CREATE TABLE $nom_table"),MYSQL_NUM);
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
		if (isset($GLOBALS['tables_principales'][$nom_table]['join']))
			return array('field' => $fields,	'key' => $keys, 'join'=>$GLOBALS['tables_principales'][$nom_table]['join']);
		elseif (isset($GLOBALS['tables_auxiliaires'][$nom_table]['join']))
			return array('field' => $fields,	'key' => $keys, 'join'=>$GLOBALS['tables_auxiliaires'][$nom_table]['join']);
		else return array('field' => $fields,	'key' => $keys);
	}
} 

//
// Recuperation des resultats
//

// http://doc.spip.org/@spip_mysql_fetch
function spip_mysql_fetch($r, $t=MYSQL_ASSOC) {
	if ($r) return mysql_fetch_array($r, $t);
}


// http://doc.spip.org/@spip_mysql_countsel
function spip_mysql_countsel($from = array(), $where = array(),
	$groupby = '', $limit = '', $sousrequete = '', $having = array())
{
	$r = spip_mysql_select('COUNT(*)', $from, $where,
			   $groupby, '', $limit,
			   $sousrequete, $having);
	if ($r) list($r) = mysql_fetch_array($r, MYSQL_NUM);
	return $r;
}

// http://doc.spip.org/@spip_mysql_error
function spip_mysql_error($query='') {
	$s = mysql_error();
	if ($s) spip_log("$s - $query", 'mysql');
	return $s;
}

// A transposer dans les portages
// http://doc.spip.org/@spip_mysql_errno
function spip_mysql_errno() {
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
function spip_mysql_count($r) {
	if ($r)	return mysql_num_rows($r);
}


// http://doc.spip.org/@spip_mysql_free
function spip_mysql_free($r) {
	return mysql_free_result($r);
}

// http://doc.spip.org/@spip_mysql_insert
function spip_mysql_insert($table, $champs, $valeurs, $desc='') {
	if (!spip_mysql_query("INSERT INTO $table $champs VALUES $valeurs"))
		return 0;
	$r = mysql_insert_id();
	return $r ? $r : (($r===0) ? -1 : 0);
}

// http://doc.spip.org/@spip_mysql_update
function spip_mysql_update($table, $champs, $where='', $desc='') {
	$r = '';
	foreach ($champs as $champ => $val)
		$r .= ',' . $champ . "=$val";
	if ($r = substr($r, 1))
		spip_mysql_query("UPDATE $table SET $r" . ($where ? " WHERE $where" : ''));
}

// idem, mais les valeurs sont des constantes a mettre entre apostrophes
// sauf les expressions de date lorsqu'il s'agit de fonctions SQL (NOW etc)
// http://doc.spip.org/@spip_mysql_update
function spip_mysql_updateq($table, $champs, $where='', $desc=array()) {

	if (!$champs) return;
	if (!$desc) {
		global $tables_principales;
		include_spip('base/serial');
		$desc = $tables_principales[$table];
	}
	$fields = $desc['field'];
	$r = '';
	foreach ($champs as $champ => $val) {
		$t = $fields[$champ];
		if (((strpos($t, 'datetime')!==0)
		     AND (strpos($t, 'TIMESTAMP')!==0))
		OR strpos("012345678", $val[0]) !==false)
			$val = _q($val);
		$r .= ',' . $champ . '=' . $val;
	}
	$r = "UPDATE $table SET " . substr($r, 1) . ($where ? " WHERE $where" : '');
	spip_mysql_query($r);
}

// http://doc.spip.org/@spip_mysql_delete
function spip_mysql_delete($table, $where='') {
	spip_mysql_query("DELETE FROM $table" . ($where ? " WHERE $where" : ''));
}

// http://doc.spip.org/@spip_mysql_replace
function spip_mysql_replace($table, $values, $keys=array()) {
	return spip_mysql_query("REPLACE $table (" . join(',',array_keys($values)) . ') VALUES (' .join(',',array_map('_q', $values)) . ')');
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
?>
