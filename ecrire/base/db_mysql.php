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

// fonction pour la premiere connexion a un serveur MySQL

// http://doc.spip.org/@base_db_mysql_dist
function base_db_mysql_dist($host, $port, $login, $pass, $db='', $prefixe='') {
	if ($port > 0) $host = "$host:$port";
	$link = mysql_connect($host, $login, $pass);

	if (!$db) {
		$ok = $link;
		$db = 'spip';
	} else {
		$ok = spip_mysql_selectdb($db);
		if (defined('_MYSQL_SQL_MODE_TEXT_NOT_NULL'))
			mysql_query("set sql_mode=''");
		if (isset($GLOBALS['meta']['charset_sql_connexion']))
			mysql_query("SET NAMES "._q($GLOBALS['meta']['charset_sql_connexion']));
	}
#	spip_log("Connexion vers $host, base $db, prefixe $prefixe "
#		 . ($ok ? "operationnelle sur $link" : 'impossible'));

	return !$ok ? false : array(
		'db' => $db,
		'prefixe' => $prefixe ? $prefixe : $db,
		'link' => $GLOBALS['mysql_rappel_connexion'] ? $link : false,
		'alter' => 'spip_mysql_alter',
		'count' => 'spip_mysql_count',
		'countsel' => 'spip_mysql_countsel',
		'create' => 'spip_mysql_create',
		'delete' => 'spip_mysql_delete',
		'errno' => 'spip_mysql_errno',
		'error' => 'spip_mysql_error',
		'fetch' => 'spip_mysql_fetch',
		'fetsel' => 'spip_mysql_fetsel',
		'free' => 'spip_mysql_free',
		'insert' => 'spip_mysql_insert',
		'insertq' => 'spip_mysql_insertq',
		'listdbs' => 'spip_mysql_listdbs',
		'multi' => 'spip_mysql_multi',
		'query' => 'spip_mysql_query',
		'replace' => 'spip_mysql_replace',
		'select' => 'spip_mysql_select',
		'selectdb' => 'spip_mysql_selectdb',
		'set_connect_charset' => 'spip_mysql_set_connect_charset',
		'showtable' => 'spip_mysql_showtable',
		'update' => 'spip_mysql_update',
		'updateq' => 'spip_mysql_updateq',
		);
}

// http://doc.spip.org/@spip_mysql_set_connect_charset
function spip_mysql_set_connect_charset($charset, $serveur=''){
	#spip_log("changement de charset sql : "."SET NAMES "._q($charset));
	return mysql_query("SET NAMES "._q($charset));
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
	return $t ? trace_query_end($query, $t, $r, $e) : $r;
}

// http://doc.spip.org/@spip_mysql_alter
function spip_mysql_alter($query, $serveur=''){
	return spip_mysql_query("ALTER ".$query); # i.e. que PG se debrouille
}

// fonction appelant la precedente
// c'est une instance de sql_select, voir ses specs dans abstract.php
// traite_query pourrait y etre fait d'avance ce serait moins cher
// Les \n et \t sont utiles au debusqueur.

// La parametre sous_requete n'est plus utilise

// http://doc.spip.org/@spip_mysql_select
function spip_mysql_select($select, $from, $where,
			   $groupby, $orderby, $limit,
			   $sousrequete, $having,
			   $table='', $id='', $serveur='') {

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

	if (!($res = spip_mysql_query($query, $serveur))) {
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

// http://doc.spip.org/@spip_mysql_listdbs
function spip_mysql_listdbs($serveur='') {
	return mysql_list_dbs();
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
	$r = spip_mysql_select('COUNT(*)', $from, $where,
			$groupby, '', $limit,
			$sousrequete, $having, $serveur);
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

	if (mysql_query("INSERT INTO $table $champs VALUES $valeurs", $link))
		$r = mysql_insert_id($link);
	return $r ? $r : (($r===0) ? -1 : 0);
}

// http://doc.spip.org/@spip_mysql_insertq
function spip_mysql_insertq($table, $couples, $desc=array(), $serveur='') {

	if (!$desc) $desc = description_table($table);
	if (!$desc) die("$table insertion sans description");
	$fields =  $desc['field'];

	foreach ($couples as $champ => $val) {
		$couples[$champ]= spip_mysql_cite($val, $fields[$champ]);
	}

	return spip_mysql_insert($table, "(".join(',',array_keys($couples)).")", "(".join(',', $couples).")", $desc, $serveur);
}

// http://doc.spip.org/@spip_mysql_update
function spip_mysql_update($table, $champs, $where='', $desc='', $serveur='') {
	$r = '';
	foreach ($champs as $champ => $val)
		$r .= ',' . $champ . "=$val";
	if ($r = substr($r, 1))
		spip_mysql_query("UPDATE $table SET $r" . ($where ? " WHERE $where" : ''), $serveur);
}

// idem, mais les valeurs sont des constantes a mettre entre apostrophes
// sauf les expressions de date lorsqu'il s'agit de fonctions SQL (NOW etc)
// http://doc.spip.org/@spip_mysql_updateq
function spip_mysql_updateq($table, $champs, $where='', $desc=array(), $serveur='') {

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
	spip_mysql_query($r, $serveur);
}

// http://doc.spip.org/@spip_mysql_delete
function spip_mysql_delete($table, $where='', $serveur='') {
	spip_mysql_query("DELETE FROM $table" . ($where ? " WHERE $where" : ''), $serveur);
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

// Ces deux fonctions n'ont pas d'équivalent exact PostGres
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

	$q = spip_query("SELECT GET_LOCK(" . _q($nom) . ", $timeout) AS n");
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

	@spip_query("SELECT RELEASE_LOCK(" . _q($nom) . ")");
}

function spip_mysql_cite($val, $type) {
	if ((strpos($type, 'datetime')===0) OR (strpos($type, 'TIMESTAMP')===0))
	  return $val;
	else return _q($val);
}
?>
