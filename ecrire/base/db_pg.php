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
function base_db_pg_dist($addr, $port, $login, $pass, $db='') {
	global $spip_pg_link;

	@list($host, $p) = split(';', $addr);
	if ($p >0) $port = " port=$p" ; else $port = '';
	if ($db) $db= " dbname=$db";
	$spip_pg_link = pg_connect("host=$host$port$db  user=$login password=$pass");
	if ($spip_pg_link) return 'spip_pg_query';

	spip_log("pas d'acces vers $host:$port sur $db pour $login");
	return false;
}

// Par ou ca passe une fois les traductions faites
// http://doc.spip.org/@spip_pg_trace_query
function spip_pg_trace_query($query)
{
	global $spip_pg_link;

	$t = !isset($_GET['var_profile']) ? 0 : trace_query_start();
	$r = pg_query($spip_pg_link, $query);
	if ($e = spip_pg_errno())	// Log de l'erreur eventuelle
		$e .= spip_pg_error($query); // et du fautif
	return $t ? trace_query_end($query, $t, $r, $e) : $r;
}

// Fonction de requete generale quand on est sur que c'est SQL standard.
// Elle change juste le noms des tables ($table_prefix) dans le FROM etc

// http://doc.spip.org/@spip_pg_query
function spip_pg_query($query)
{
	global $table_prefix;

	if (strpos($query, 'REPLACE') ===0) // a evacuer 
		return spip_pg_replace($query);
	if (preg_match('/\s(SET|VALUES|WHERE)\s/i', $query, $regs)) {
		$suite = strstr($query, $regs[0]);
		$query = substr($query, 0, -strlen($suite));
	} else $suite ='';
	$query = preg_replace('/([,\s])spip_/', '\1'.$table_prefix.'_', $query) . $suite;
	return spip_pg_trace_query($query);

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
	  . (!$from ? '' : ("\nFROM " . spip_pg_from($from)))
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

	if (!($res = spip_pg_trace_query($q))) {
	  include_spip('public/debug');
	  erreur_requete_boucle($q, $id, $table, $n, $m);
	}
	return $res;
}

// Le traitement des prefixes de table dans un Select se limite au FROM
// car le reste de la requete utilise les alias (AS) systematiquement

// http://doc.spip.org/@spip_pg_from
function spip_pg_from($from)
{
	global $table_prefix;
	return  !$table_prefix ? $from :
		preg_replace('/(\b)spip_/',
			     '\1'.$table_prefix.'_', 
			     (!is_array($from) ? $from : spip_pg_select_as($from)));

}

// http://doc.spip.org/@spip_pg_orderby
function spip_pg_orderby($order, $select)
{
	$res = array();
	$arg = (is_array($order) ?  $order : preg_split('\s*,\s*',$order));

	foreach($arg as $v) {
		if (preg_match('/(case\s+.*?else\s+0\s+end)\s*AS\s+' . $v .'/', $select, $m)) {

		  $res[] = $m[1];
		} else $res[]=$v;
	}
	spip_log("orde $res");
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
// Manque la traduction de Field
// Les expressions de date ne sont pas gerees au-dela de 3 ()
// Le 'as' du 'CAST' est en minuscule pour echapper au dernier preg_replace
// de spip_pg_groupby
// Bref, a revoir.

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
			spip_log($v);
			$res .= "\nwhen $index=$v then $n";
		}
		spip_log("---- " . substr($arg,strlen($m[0])+1));
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
function spip_pg_fetch($res, $t=PGSQL_ASSOC) {

	if ($res) $res = pg_fetch_array($res, NULL, $t);
	return $res;
}
 
// http://doc.spip.org/@spip_pg_countsel
function spip_pg_countsel($from = array(), $where = array(),
	$groupby='', $limit='', $sousrequete = '', $having = array())
{
	$r = spip_pg_select('COUNT(*)', $from, $where,
			    $groupby, '', $limit, $sousrequete, $having);
	if ($r) list($r) = pg_fetch_array($r, NULL, PGSQL_NUM);
#	spip_log("$r pg_mysql_countsel($from $where $limit");
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

// http://doc.spip.org/@spip_pg_insert
function spip_pg_insert($table, $champs, $valeurs, $ignore='') {
	global $tables_principales;
	global $spip_pg_link, $table_prefix;
	include_spip('base/serial');
	if (isset($tables_principales[$table]['key']["PRIMARY KEY"]))
		$ret = " RETURNING "
		. $tables_principales[$table]['key']["PRIMARY KEY"];
	else $ret = '';

	if ($GLOBALS['table_prefix'])
		$table = preg_replace('/^spip/',
				    $GLOBALS['table_prefix'],
				    $table);
	$r = spip_pg_trace_query("INSERT INTO $table $champs VALUES $valeurs $ret");
	if (!$r) return 0;
	if (!$ret) return -1;
	$r = pg_fetch_array($r, NULL, PGSQL_NUM);

	return $r[0];
}

// http://doc.spip.org/@spip_pg_update
function spip_pg_update($table, $exp, $where='') {
	global $spip_pg_link, $table_prefix;
	if ($GLOBALS['table_prefix'])
		$table = preg_replace('/^spip/',
				    $GLOBALS['table_prefix'],
				    $table);
	spip_pg_trace_query("UPDATE $table SET " . spip_pg_frommysql($exp) .($where ? (" WHERE " . spip_pg_frommysql($where)) : ''));
}

// http://doc.spip.org/@spip_pg_delete
function spip_pg_delete($table, $where='') {
	global $spip_pg_link, $table_prefix;
	if ($GLOBALS['table_prefix'])
		$table = preg_replace('/^spip/',
				    $GLOBALS['table_prefix'],
				    $table);
	spip_pg_trace_query("DELETE FROM $table " . ($where ? (" WHERE " . spip_pg_frommysql($where)) : ''));
}


// http://doc.spip.org/@spip_pg_replace
function spip_pg_replace($table, $values, $desc) {
	global $spip_pg_link, $table_prefix;
	if ($GLOBALS['table_prefix'])
		$table = preg_replace('/^spip/',
				    $GLOBALS['table_prefix'],
				    $table);

	$prim = $desc['key']['PRIMARY KEY'];
	$ids = preg_split('/,\s*/', $prim);
	$noprims = $prims = array();

	foreach($values as $k=>$v) {
		$t = $desc['field'][$k];
		if ((strpos($t, 'datetime') === 0)
		AND strpos($v, "-00-00") === 4)
			  { $values[$k] = $v = _q(substr($v,0,4)."-01-01".substr($v,10));}
		elseif  (test_sql_int($t))
		  $values[$k] = $v = intval($v);
		else $values[$k] = $v = _q($v);

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
	  $r = pg_query($spip_pg_link, $q = "UPDATE $table SET $set WHERE $where");
	  if (!$r) {
	    $n = spip_pg_errno();
	    $m = spip_pg_error($q);
	  } else {
	    $r = pg_affected_rows($r);
	  }
	}
	if (!$r) {
	    $r = pg_query($spip_pg_link, $q = "INSERT INTO $table (" . join(',',array_keys($values)) . ') VALUES (' .join(',', $values) . ')');
	    if (!$r) {
	      $n = spip_pg_errno();
	      $m = spip_pg_error($q);
	    }
	}
	return $r;
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
function spip_pg_create($nom, $champs, $cles, $autoinc=false, $temporary=false) {
	global $spip_pg_link;
	if ($GLOBALS['table_prefix'])
		$nom = preg_replace('/^spip/',
				    $GLOBALS['table_prefix'],
				    $nom);
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

	@pg_query($spip_pg_link, $q);

	foreach($keys as $index)  {@pg_query($spip_pg_link, $index);}
}

// Fonction PG a ecrire: selectionner la sous-chaine dans $objet
// correspondant a $lang. Cf balise Multi de Spip

// http://doc.spip.org/@spip_pg_multi
function spip_pg_multi ($objet, $lang) {
	spip_log("SPIP-PG ne sait pas traduire multi $objet"); # a revoir
	return "$objet AS multi";
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
