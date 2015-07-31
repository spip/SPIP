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

//constantes spip pour spip_fetch_array()
define('SPIP_BOTH', PGSQL_BOTH);
define('SPIP_ASSOC', PGSQL_ASSOC);
define('SPIP_NUM', PGSQL_NUM);

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

// Fonction de requete generale. 
// Passe la main pour les idiosyncrasies MySQL a traduire

// http://doc.spip.org/@spip_pg_query
function spip_pg_query($query)
{
	global $spip_pg_link, $table_prefix;

	if (strpos($query, 'REPLACE') ===0)
		return spip_pg_replace($query);
	// changer les noms des tables ($table_prefix)
	if (preg_match('/\s(SET|VALUES|WHERE)\s/i', $query, $regs)) {
		$suite = strstr($query, $regs[0]);
		$query = substr($query, 0, -strlen($suite));
	} else $suite ='';
	$query = preg_replace('/([,\s])spip_/', '\1'.$table_prefix.'_', $query) . $suite;

	$r = pg_query($spip_pg_link, $query);

	return $r;
}


// http://doc.spip.org/@spip_pg_replace
function spip_pg_replace($query) {
  spip_log("REPLACE a implementer en postgres");
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
                           $table, $id, $serveur){
	global $spip_pg_link;
	if (eregi("^([0-9]+), *([0-9]+)$", $limit,$match))
	      {
		$offset = $match[1];
		$count = $match[2];
	      }

	$q =  (!is_array($select) ? $select : join(", ", $select)) .
	  (!$from ? '' :
			("\nFROM " .
			(!is_array($from) ? $from : spip_pg_select_as($from))))
	  . (!$where ? '' : ("\nWHERE " . (!is_array($where) ? $where : (join("\n\tAND ", array_map('calculer_pg_where', $where))))))
	  . ($groupby ? "\nGROUP BY $groupby" : '')
	  . (!$having ? '' : "\nHAVING " . (!is_array($having) ? $having : (join("\n\tAND ", array_map('calculer_where', $having)))))
	  . ($orderby ? ("\nORDER BY " . spip_pg_order($orderby)) :'')
	  . (!$limit ? '' : (" LIMIT $count" . (!$offset ? '' : " OFFSET $offset")));
		$q = " SELECT ". $q;

	// Erreur ? C'est du debug, ou une erreur du serveur
	// il faudrait mettre ici le déclenchement du message SQL
	// actuellement dans erreur_requete_boucle

	if ($GLOBALS['var_mode'] == 'debug') {
		boucle_debug_resultat($id, '', $q);
	}

	if (!($res = spip_pg_query($q))) {
		include_spip('inc/debug_sql.php');
		erreur_requete_boucle($q, $id, $table,
				      spip_pg_errno(),
				      spip_pg_error());
	}
#	spip_log("selectabs $q" . pg_numrows($res));
	return $res;
}

// 0+x avec un champ x commencant par des chiffres est converti par MySQL
// en le nombre qui commence x. PG ne sait pas faire, on elimine.
// Comme SPIP utilise systematiquement 0+t,t on ne garde que le 2e.

function spip_pg_order($orderby)
{
	if (is_array($orderby)) $orderby = join(", ", $orderby);
	return preg_replace('/0[+]([^, ]+)\s*,\s*\1\b/', '\1', $orderby);
}


function calculer_pg_where($v)
{
	if (!is_array($v))
	  return $v ;

	$op = array_shift($v);
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
function spip_pg_fetch($res, $extra='') {
	  static $n = array();
	  if ($extra) spip_log("fetch argument 2: $extra a revoir");
	  if ($res) $res = pg_fetch_array($res, $n[$res]++, PGSQL_ASSOC);
	  return $res;
}
 
// http://doc.spip.org/@spip_fetch_array
function spip_fetch_array($r, $extra='') {
	  if ($extra) spip_log("fetch argument 2: $extra a revoir");
	  if ($r) return pg_fetch_array($r);
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
	$r = pg_query($spip_pg_link, "INSERT INTO $table $champs VALUES $valeurs $ret");
	if (!$r) return 0;
	if (!$ret) return -1;
	$r = pg_fetch_array($r, 0, PGSQL_NUM);

	return $r[0];
}

// http://doc.spip.org/@spip_pg_update
function spip_pg_update($table, $exp, $where='') {
	global $spip_pg_link, $table_prefix;
	if ($GLOBALS['table_prefix'])
		$table = preg_replace('/^spip/',
				    $GLOBALS['table_prefix'],
				    $table);
	pg_query($spip_pg_link, "UPDATE $table SET $exp" . ($where ? " WHERE $where" : ''));
}


// http://doc.spip.org/@spip_pg_error
function spip_pg_error() {
	return pg_last_error();
}

// http://doc.spip.org/@spip_pg_errno
function spip_pg_errno() {
	return pg_last_error() ? 1 : 0;
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

	// controler si la table existe deja serait pas mal

	$q = "CREATE $temporary TABLE $nom ($query" . ($prim ? ",$prim" : '') . ")".
	($character_set?" DEFAULT $character_set":"")
	."\n";

	pg_query($spip_pg_link, $q);
	foreach($keys as $index)  {pg_query($spip_pg_link, $index);}
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
		str_replace("0000-00-00",'1970-01-01',
		   preg_replace("/unsigned/i", '', 	
		   preg_replace("/double/i", 'double precision', 	
		   preg_replace("/tinyint/i", 'int', 	
		     str_replace("VARCHAR(255) BINARY", 'bytea', 
				 preg_replace("/ENUM *[(][^)]*[)]/", "varchar(255)",
					      $v 
				 ))))))))))));
}

?>
