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

// Se connecte et retourne le nom de la fonction a connexion persistante

// http://doc.spip.org/@base_db_pg_dist
function base_db_pg_dist($addr, $port, $login, $pass, $db='', $prefixe='') {

	@list($host, $p) = split(';', $addr);
	if ($p >0) $port = " port=$p" ; else $port = '';
	if (!$db) {$db = 'spip'; $dbn='';} else $dbn =  " dbname=$db";
	$link = pg_connect("host=$host$port$dbn user=$login password=$pass");
#	spip_log("Connexion vers $host, base $db, prefixe $prefixe "
#		 . ($link ? 'operationnelle' : 'impossible'));

	return !$link ? false : array(
		'link' => $link,
		'db' => $db,
		'prefixe' => $prefixe ? $prefixe : $db,
		'count' => 'spip_pg_count',
		'countsel' => 'spip_pg_countsel',
		'create' => 'spip_pg_create',
		'delete' => 'spip_pg_delete',
		'errno' => 'spip_pg_errno',
		'error' => 'spip_pg_error',
		'fetch' => 'spip_pg_fetch',
		'fetsel' => 'spip_pg_fetsel',
		'free' => 'spip_pg_free',
		'insert' => 'spip_pg_insert',
		'listdbs' => 'spip_pg_listdbs',
		'multi' => 'spip_pg_multi',
		'query' => 'spip_pg_query',
		'replace' => 'spip_pg_replace',
		'select' => 'spip_pg_select',
		'selectdb' => 'spip_pg_selectdb',
		'set_connect_charset' => 'spip_pg_set_connect_charset',
		'showtable' => 'spip_pg_showtable',
		'update' => 'spip_pg_update',
		'updateq' => 'spip_pg_updateq',
		);
}

// Par ou ca passe une fois les traductions faites
// http://doc.spip.org/@spip_pg_trace_query
function spip_pg_trace_query($query, $serveur='')
{
	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	$t = !isset($_GET['var_profile']) ? 0 : trace_query_start();
	$r = pg_query($link, $query);

	if ($e = spip_pg_errno())	// Log de l'erreur eventuelle
		$e .= spip_pg_error($query); // et du fautif
	return $t ? trace_query_end($query, $t, $r, $e) : $r;
}

// Fonction de requete generale quand on est sur que c'est SQL standard.
// Elle change juste le noms des tables ($table_prefix) dans le FROM etc

// http://doc.spip.org/@spip_pg_query
function spip_pg_query($query, $serveur='')
{
	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	if (preg_match('/\s(SET|VALUES|WHERE)\s/i', $query, $regs)) {
		$suite = strstr($query, $regs[0]);
		$query = substr($query, 0, -strlen($suite));
	} else $suite ='';
	$query = preg_replace('/([,\s])spip_/', '\1'.$prefixe.'_', $query) . $suite;
	return spip_pg_trace_query($query, $serveur);
}

//  Qu'une seule base pour le moment

// http://doc.spip.org/@spip_pg_selectdb
function spip_pg_selectdb($db) {
	return pg_dbname();
}

// Qu'une seule base pour le moment

// http://doc.spip.org/@spip_pg_listdbs
function spip_pg_listdbs() {
	return array();
}

// http://doc.spip.org/@spip_pg_select
function spip_pg_select($select, $from, $where,
                           $groupby, $orderby, $limit,
                           $sousrequete, $having,
                           $table='', $id='', $serveur=''){

	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	$limit = preg_match("/^\s*(([0-9]+),)?\s*([0-9]+)\s*$/", $limit,$limatch);
	if ($limit) {
		$offset = $limatch[2];
		$count = $limatch[3];
	}

	$select = spip_pg_frommysql($select);

	$orderby = spip_pg_orderby($orderby, $select);

	if ($having) {
	  if (is_array($having))
	    $having = join("\n\tAND ", array_map('calculer_pg_where', $having));
	}
	$q =  spip_pg_frommysql($select)
	  . (!$from ? '' : ("\nFROM " . spip_pg_from($from, $prefixe)))
	  . (!$where ? '' : ("\nWHERE " . (!is_array($where) ? calculer_pg_where($where) : (join("\n\tAND ", array_map('calculer_pg_where', $where))))))
	  . spip_pg_groupby($groupby, $from, $select)
	  . (!$having ? '' : "\nHAVING $having")
	  . ($orderby ? ("\nORDER BY $orderby") :'')
	  . (!$limit ? '' : (" LIMIT $count" . (!$offset ? '' : " OFFSET $offset")));
		$q = " SELECT ". $q;

	// Erreur ? C'est du debug, ou une erreur du serveur
	// il faudrait mettre ici le déclenchement du message SQL
	// actuellement dans erreur_requete_boucle

	if ($GLOBALS['var_mode'] == 'debug') {
		include_spip('public/debug');
		boucle_debug_resultat($id, '', $q);
	}

	if (!($res = spip_pg_trace_query($q, $serveur))) {
	  include_spip('public/debug');
	  erreur_requete_boucle($q, $id, $table, $n, $m);
	}

	return $res;
}

// Le traitement des prefixes de table dans un Select se limite au FROM
// car le reste de la requete utilise les alias (AS) systematiquement

// http://doc.spip.org/@spip_pg_from
function spip_pg_from($from, $prefixe)
{
	return !$prefixe ? $from :
		preg_replace('/(\b)spip_/',
			'\1'.$prefixe.'_', 
			(!is_array($from) ? $from : spip_pg_select_as($from)));

}

// http://doc.spip.org/@spip_pg_orderby
function spip_pg_orderby($order, $select)
{
	$res = array();
	$arg = (is_array($order) ?  $order : preg_split('/\s*,\s*/',$order));

	foreach($arg as $v) {
		if (preg_match('/(case\s+.*?else\s+0\s+end)\s*AS\s+' . $v .'/', $select, $m)) {

		  $res[] = $m[1];
		} else $res[]=$v;
	}
	return spip_pg_frommysql(join(',',$res));
}

// Conversion a l'arrach' des jointures MySQL en jointures PG
// A refaire pour tirer parti des possibilites de PG et de MySQL5

// http://doc.spip.org/@spip_pg_groupby
function spip_pg_groupby($groupby, $from, $select)
{
	$join = is_array($from) ? (count($from) > 1) : strpos($from, ",");
	if ($join) $join = !is_array($select) ? $select : join(", ", $select);
	if ($join) {
	  $join = preg_replace('/(SUM|COUNT|MAX|MIN)\([^)]+\)\s*,/i','', $join);
	  $join = preg_replace('/,?\s*(SUM|COUNT|MAX|MIN)\([^)]+\)/i','', $join);
	}
	if ($join) $groupby = $groupby ? "$groupby, $join" : $join;
	if (!$groupby) return '';
	$groupby = spip_pg_frommysql($groupby);
	$groupby = preg_replace('/\bAS\s+\w+/','', $groupby);

	return "\nGROUP BY $groupby"; 
}

// Conversion des operateurs MySQL en PG
// IMPORTANT: "0+X" est vu comme conversion numerique du debut de X 
// Les expressions de date ne sont pas gerees au-dela de 3 ()
// Le 'as' du 'CAST' est en minuscule pour echapper au dernier preg_replace
// de spip_pg_groupby.
// A ameliorer.

// http://doc.spip.org/@spip_pg_frommysql
function spip_pg_frommysql($arg)
{
	if (is_array($arg)) $arg = join(", ", $arg);

	$res = spip_pg_fromfield($arg);

	$res = preg_replace('/\b0[+]([^, ]+)\s*/',
			    'CAST(substring(\1, \'^ *[0-9]+\') as int)',
			    $res);
	$res = preg_replace('/UNIX_TIMESTAMP\s*[(]([^)]*)[)]/',
			    'EXTRACT(\'epoch\' FROM \1)', $res);

	$res = preg_replace('/DAYOFMONTH\s*[(]([^(]*([(][^)]*[)][()]*)*)[)]/',
			    'EXTRACT(\'day\' FROM \1)',
			    $res);

	$res = preg_replace('/MONTH\s*[(]([^(]*([(][^)]*[)][()]*)*)[)]/',
			    'EXTRACT(\'month\' FROM \1)',
			    $res);

	$res = preg_replace('/YEAR\s*[(]([^(]*([(][^)]*[)][()]*)*)[)]/',
			    'EXTRACT(\'year\' FROM \1)',
			    $res);

	$res = preg_replace('/DATE_SUB\s*[(]([^,]*),/', '(\1 -', $res);
	$res = preg_replace('/DATE_ADD\s*[(]([^,]*),/', '(\1 +', $res);
	$res = preg_replace('/INTERVAL\s+(\d+\s+\w+)/', 'INTERVAL \'\1\'', $res);
	$res = preg_replace('/([+<>-]=?)\s*(\'\d+-\d+-\d+\s+\d+:\d+(:\d+)\')/', '\1 timestamp \2', $res);
	$res = preg_replace('/(\'\d+-\d+-\d+\s+\d+:\d+(:\d+)\')\s*([+<>-]=?)/', 'date \1 \2', $res);

	$res = preg_replace('/([+<>-]=?)\s*(\'\d+-\d+-\d+\')/', '\1 date \2', $res);
	$res = preg_replace('/(\'\d+-\d+-\d+\')\s*([+<>-]=?)/', 'date \1 \2', $res);

	$res = preg_replace('/TO_DAYS\s*[(]([^(]*([(][^)]*[)][()]*)*)[)]/',
			    'date_part(\'day\', \1 - \'0000-01-01\')',
			    $res);

	return str_replace('REGEXP', '~', $res);
}

// http://doc.spip.org/@spip_pg_fromfield
function spip_pg_fromfield($arg)
{
	while(preg_match('/^(.*?)FIELD\s*\(([^,]*)((,[^,)]*)*)\)/', $arg, $m)) {

		preg_match_all('/,([^,]*)/', $m[3], $r, PREG_PATTERN_ORDER);
		$res = '';
		$n=0;
		$index = $m[2];
		foreach($r[1] as $v) {
			$n++;
			$res .= "\nwhen $index=$v then $n";
		}
		$arg = $m[1] . "case $res else 0 end "
		  . substr($arg,strlen($m[0]));
	}
	return $arg;
}

// http://doc.spip.org/@calculer_pg_where
function calculer_pg_where($v)
{
	if (!is_array($v))
	  return spip_pg_frommysql($v);

	$op = str_replace('REGEXP', '~', array_shift($v));
	if (!($n=count($v)))
		return $op;
	else {
		$arg = calculer_pg_where(array_shift($v));
		if ($n==1) {
			  return "$op($arg)";
		} else {
			$arg2 = calculer_pg_where(array_shift($v));
			if ($n==2) {
				return "($arg $op $arg2)";
			} else return "($arg $op ($arg2) : $v[0])";
		}
	}
}

// http://doc.spip.org/@spip_pg_select_as
function spip_pg_select_as($args)
{
	$argsas = "";
	foreach($args as $k => $v) {
	  $argsas .= ', ' . $v . ((is_numeric($k) OR $v==$k) ? '' : " AS $k");
	}
	return substr($argsas,2);
}

// http://doc.spip.org/@spip_pg_fetch
function spip_pg_fetch($res, $t='', $serveur='') {

	if ($res) $res = pg_fetch_array($res, NULL, PGSQL_ASSOC);
	return $res;
}
 
// http://doc.spip.org/@spip_pg_countsel
function spip_pg_countsel($from = array(), $where = array(),
			  $groupby='', $limit='', $sousrequete = '', $having = array(), $serveur='')
{
	$r = spip_pg_select('COUNT(*)', $from, $where,
			    $groupby, '', $limit, $sousrequete, $having, '','', $serveur);
	if ($r) list($r) = pg_fetch_array($r, NULL, PGSQL_NUM);
	return $r;
}

// http://doc.spip.org/@spip_pg_count
function spip_pg_count($res, $serveur='') {
	return !$res ? 0 : pg_numrows($res);
}
  
// http://doc.spip.org/@spip_pg_free
function spip_pg_free($res, $serveur='') {
  // rien à faire en postgres
}

// http://doc.spip.org/@spip_pg_delete
function spip_pg_delete($table, $where='', $serveur='') {

	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];
	if ($prefixe) $table = preg_replace('/^spip/', $prefixe, $table);
	spip_pg_trace_query("DELETE FROM $table " . ($where ? (" WHERE " . spip_pg_frommysql($where)) : ''), $serveur);
}

// http://doc.spip.org/@spip_pg_insert
function spip_pg_insert($table, $champs, $valeurs, $desc=array(), $serveur='') {
	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	if (!$desc) {
		global $tables_principales;
		include_spip('base/serial');
		$desc = @$tables_principales[$table];
	}

	// Dans les tables principales de SPIP, le numero de l'insertion
	// est la valeur de l'unique et atomique cle primaire ===> RETURNING
	// Le code actuel n'a pas besoin de ce numero dans les autres cas
	// mais il faudra surement amelioer ca un jour.
	$seq = @$desc['key']["PRIMARY KEY"];
	$ret = preg_match('/\w+/', $seq) ? " RETURNING $seq" : '';

	if ($prefixe) $table = preg_replace('/^spip/', $prefixe, $table);
	$r = pg_query($link, $q="INSERT INTO $table $champs VALUES $valeurs $ret");
	if ($r) {
		if (!$ret) return 0;
		if ($r = pg_fetch_array($r, NULL, PGSQL_NUM))
			return $r[0];
	}
	spip_log("Erreur $q", 'pg'); // trace a minima
	return -1;

}

// http://doc.spip.org/@spip_pg_update
function spip_pg_update($table, $champs, $where='', $desc=array()) {

	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];
	$r = '';
	foreach ($champs as $champ => $val) {
		$r .= ',' . $champ . '=' . 
		  spip_pg_cite($val,  $desc['field'][$champ]);
	}

	if ($r = substr($r, 1)) {
		if ($prefixe) $table = preg_replace('/^spip/', $prefixe, $table);
		spip_pg_trace_query("UPDATE $table SET $r" .($where ? (" WHERE " . spip_pg_frommysql($where)) : ''));
	}
}

// idem, mais les valeurs sont des constantes a mettre entre apostrophes
// sauf les expressions de date lorsqu'il s'agit de fonctions SQL (NOW etc)
// http://doc.spip.org/@spip_pg_updateq
function spip_pg_updateq($table, $champs, $where='', $desc=array(), $serveur='') {

	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	if (!$champs) return;
	if (!$desc) {
		global $tables_principales;
		include_spip('base/serial');
		$desc = $tables_principales[$table];
	}
	if ($prefixe) $table = preg_replace('/^spip/', $prefixe, $table);
	$fields = $desc['field'];
	$r = '';
	foreach ($champs as $champ => $val) {
		$r .= ',' . $champ . '=' . spip_pg_cite($val, $fields[$champ]);
	}
	$r = "UPDATE $table SET " . substr($r, 1) . ($where ? " WHERE $where" : '');
	return pg_query($link, $r);
}


// http://doc.spip.org/@spip_pg_replace
function spip_pg_replace($table, $values, $desc, $serveur='') {

	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];
	if ($prefixe) $table = preg_replace('/^spip/', $prefixe, $table);

	$prim = $desc['key']['PRIMARY KEY'];
	$ids = preg_split('/,\s*/', $prim);
	$noprims = $prims = array();
	foreach($values as $k=>$v) {
		$values[$k] = $v = spip_pg_cite($v, $desc['field'][$k]);

		if (!in_array($k, $ids))
			$noprims[$k]= "$k=$v";
		else $prims[$k]= "$k=$v";
	}

	$where = join(' AND ', $prims);
	if (!$where) {
		spip_log("REPLACE en PG exige de connaitre la cle primaire de $table: $prim");
		return 0;
	}
	$set = join(',', $noprims);
	if ($set) {
	  $set = pg_query($link, $q = "UPDATE $table SET $set WHERE $where");
	  if (!$set) {
	    $n = spip_pg_errno();
	    $m = spip_pg_error($q);
	  } else {
	    $set = pg_affected_rows($set);
	  }
	}
	if (!$set) {
	    $set = pg_query($link, $q = "INSERT INTO $table (" . join(',',array_keys($values)) . ') VALUES (' .join(',', $values) . ')');
	    if (!$set) {
	      $n = spip_pg_errno();
	      $m = spip_pg_error($q);
	    }
	}
	return $set;
}

// Explicite les conversions de Mysql d'une valeur $v de type $t
// Dans le cas d'un champ date, pas d'apostrophe, c'est une syntaxe ad hoc

// http://doc.spip.org/@spip_pg_cite
function spip_pg_cite($v, $t)
{
	if ((strpos($t, 'datetime')===0) OR (strpos($t, 'TIMESTAMP')===0)) {
		if (strpos("0123456789", $v[0]) === false)
			return spip_pg_frommysql($v);
		else {
			if (strpos($v, "-00-00") === 4)
				$v = substr($v,0,4)."-01-01".substr($v,10);
			return "date '$v'";
		}
	}
	elseif  (test_sql_int($t))
		  return intval($v);
	else return   ("'" . addslashes($v) . "'");
}

// http://doc.spip.org/@spip_pg_error
function spip_pg_error($query) {
	$s = str_replace('ERROR', 'errcode: 1000 ', pg_last_error());
	if ($s) spip_log("$s - $query", 'pg');
	return $s;
}

// http://doc.spip.org/@spip_pg_errno
function spip_pg_errno() {
	$s = pg_last_error(); 
	if ($s) spip_log("Erreur PG $s");
	return $s ? 1 : 0;
}

// http://doc.spip.org/@spip_pg_showtable
function spip_pg_showtable($nom_table)
{
	spip_log("spip_pg_showtable('$nom_table') a definir");
}

// http://doc.spip.org/@calcul_pg_in
function calcul_pg_in($val, $valeurs, $not='') {
//
// IN (...) souvent limite a 255  elements, d'ou cette fonction assistante
//
	if (!$valeurs) return '0=0';
	$s = split(',', $valeurs, 255);
	if (count($s) < 255) {
		return ("($val $not IN ($valeurs))");
	} else {
		$valeurs = array_pop($s);
		return ("($val $not IN (" . join(',',$s) . "))\n" .
			($not ? "AND\t" : "OR\t") .
			calcul_pgsql_in($val, $valeurs, $not));
    }
}

// Fonction de creation d'une table SQL nommee $nom
// a partir de 2 tableaux PHP :
// champs: champ => type
// cles: type-de-cle => champ(s)
// si $autoinc, c'est une auto-increment (i.e. serial) sur la Primary Key
// Le nom des caches doit etre inferieur a 64 caracteres
// http://doc.spip.org/@spip_pg_create
function spip_pg_create($nom, $champs, $cles, $autoinc=false, $temporary=false, $serveur='') {

	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];
	if ($prefixe) $nom = preg_replace('/^spip/', $prefixe, $nom);
	$query = $prim = $s = $p='';
	$keys = array();

	// certains plugins declarent les tables  (permet leur inclusion dans le dump)
	// sans les renseigner (laisse le compilo recuperer la description)
	if (!is_array($champs) || !is_array($cles)) 
		return;

	foreach($cles as $k => $v) {
		if (strpos($k, "KEY ") === 0) {
		  $n = str_replace('`','',$k);	
		  $v = str_replace('`','"',$v);	
		  $i = $nom . preg_replace("/KEY +/", '_',$n);
		  if ($k != $n) $i = "\"$i\"";
		  $keys[] = "CREATE INDEX $i ON $nom ($v);";
		} else $prim .= "$s\n\t\t" . str_replace('`','"',$k) ." ($v)";
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
		$k = str_replace('`','"',$k);
		if (preg_match(',([a-z]*\s*(\(\s*[0-9]*\s*\))?(\s*binary)?),i',$v,$defs)){
			if (preg_match(',(char|text),i',$defs[1]) AND !preg_match(',binary,i',$defs[1]) ){
				$v = $defs[1] . $character_set . ' ' . substr($v,strlen($defs[1]));
			}
		}

		$query .= "$s\n\t\t$k "
			. (($autoinc && ($p == $k) && preg_match(',\b(big)?int\b,i', $v))
				? " bigserial"
			   : mysql2pg_type($v)
			);
		$s = ",";
	}
	$temporary = $temporary ? 'TEMPORARY':'';

	// En l'absence de "if not exists" en PG, on neutralise les erreurs

	$q = "CREATE $temporary TABLE $nom ($query" . ($prim ? ",$prim" : '') . ")".
	($character_set?" DEFAULT $character_set":"")
	."\n";

	$r = @pg_query($link, $q);

	foreach($keys as $index)  {@pg_query($link, $index);}
	return $r;
}

function spip_pg_set_connect_charset($charset, $serveur=''){
	spip_log("changement de charset sql a ecrire en PG");
}

// Selectionner la sous-chaine dans $objet
// correspondant a $lang. Cf balise Multi de Spip

// http://doc.spip.org/@spip_pg_multi
function spip_pg_multi ($objet, $lang) {
	$r = "regexp_replace("
	  . $objet
	  . ",'<multi>.*[[]"
	  . $lang
	  . "[]]([^[]*).*</multi>', E'\\\\1') AS multi";
	return $r;
}

// Palanquee d'idiosyncrasies MySQL dans les creations de table
// A completer par les autres, mais essayer de reduire en amont.

// http://doc.spip.org/@mysql2pg_type
function mysql2pg_type($v)
{
  return     preg_replace('/bigint\s*[(]\d+[)]/i', 'bigint', 
	preg_replace("/longtext/i", 'text',
		str_replace("mediumtext", 'text',
		preg_replace("/tinytext/i", 'text',
	  	str_replace("longblob", 'text',
	  	str_replace("datetime", 'timestamp',
		str_replace("0000-00-00",'0000-01-01',
		   preg_replace("/unsigned/i", '', 	
		   preg_replace("/double/i", 'double precision', 	
		   preg_replace("/tinyint/i", 'int', 	
		     str_replace("VARCHAR(255) BINARY", 'bytea', 
				 preg_replace("/ENUM *[(][^)]*[)]/", "varchar(255)",
					      $v 
				 ))))))))))));
}

?>
