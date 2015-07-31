<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


//
if (!defined("_ECRIRE_INC_VERSION")) return;

//
// Appel de requetes SQL
//

function spip_query_db($query) {
	global $spip_mysql_link;
	static $tt = 0;
	$my_admin = (($GLOBALS['connect_statut'] == '0minirezo') OR ($GLOBALS['auteur_session']['statut'] == '0minirezo'));
	$my_profile = ($GLOBALS['mysql_profile'] AND $my_admin);
	$my_debug = ($GLOBALS['mysql_debug'] AND $my_admin);

	$query = traite_query($query);

	#spip_log($query);
	if ($my_profile)
		$m1 = microtime();

	if ($GLOBALS['mysql_rappel_connexion'] AND $spip_mysql_link)
		$result = mysql_query($query, $spip_mysql_link);
	else
		$result = mysql_query($query);

	if ($my_profile) {
		$m2 = microtime();
		list($usec, $sec) = explode(" ", $m1);
		list($usec2, $sec2) = explode(" ", $m2);
		$dt = $sec2 + $usec2 - $sec - $usec;
		$tt += $dt;
		echo "<small>".htmlentities($query);
		echo " -> <font color='blue'>".sprintf("%3f", $dt)."</font> ($tt)</small><p>\n";

	}

	if ($s = mysql_error()) {
		if ($my_debug) {
			echo _T('info_erreur_requete')." ".htmlentities($query)."<br>";
			echo "&laquo; ".htmlentities($s)." &raquo;<p>";
		}
		spip_log($GLOBALS['REQUEST_METHOD'].' '.$GLOBALS['REQUEST_URI'], 'mysql');
		spip_log("$s - $query", 'mysql');
	}

	# spip_log("$s - $query", 'mysql');

	return $result;
}

// fonction appelant la precedente 
// specifiquement pour les select des squelettes
// c'est une instance de spip_abstract_select, voir ses specs dans inc_calcul
// les \n et \t sont utiles au debusqueur
// traite_query pourrait y est fait d'avance, � moindre cout

function spip_mysql_select($select, $from, $where,
			   $groupby, $orderby, $limit,
			   $sousrequete, $having,
			   $table, $id, $serveur) {

	$q = ($from  ?("\nFROM " . join(",\n\t", $from)) : '')
	  .  ($where ? ("\nWHERE " . join("\n\tAND ", $where)) : '')
	  .  ($groupby ? "\nGROUP BY $groupby" : '')
	  .  ($having  ? "\nHAVING $having" : '')
	  .  ($orderby ? ("\nORDER BY " . join(", ", $orderby)) : '')
	  .  ($limit ? "\nLIMIT $limit" : '');

	if (!$sousrequete)
		$q = " SELECT ". join(", ", $select) . $q;
	else
		$q = " SELECT S_" . join(", S_", $select)
		. " FROM (" . join(", ", $select)
		. ", COUNT(".$sousrequete.") AS compteur " . $q
		.") AS S_$table WHERE compteur=" . $cpt;

	// Erreur ? C'est du debug de squelette, ou une erreur du serveur

	if ($GLOBALS['var_mode'] == 'debug') {
		boucle_debug_resultat($id, 'requete', $q);
	}

	if (!($res = @spip_query($q))) {
		include_ecrire('inc_debug_sql.php3');
		erreur_requete_boucle($q, $id, $table,
				      spip_sql_errno(),
				      spip_sql_error());
	}
#	 spip_log($serveur . spip_num_rows($res) . $q);
	return $res;
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
	}
	$query = preg_replace('/([,\s])spip_/', '\1'.$db.$table_pref, $query) . $suite;

	return $query;
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
		spip_log("La connexion MySQL est out!");
		@touch(_FILE_MYSQL_OUT);
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
    if (preg_match("/^(.*),(.*KEY.*)$/s", $dec, $r)) {
      $namedkeys = $r[2];
      $dec = $r[1];
    }
    else 
      $namedkeys = "";

    $fields = array();
    foreach(preg_split("/,\s*`/",$dec) as $v) {
      preg_match("/^\s*`?([^`]*)`\s*(.*)$/",$v,$r);
      $fields[strtolower($r[1])] = $r[2];
    }
    $keys = array();

    foreach(split(")",$namedkeys) as $v) {
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

function spip_fetch_array($r) {
	if ($r)
		return mysql_fetch_array($r);
}

/* Appels obsoletes
function spip_fetch_object($r) {
	if ($r)
		return mysql_fetch_object($r);
}

function spip_fetch_row($r) {
	if ($r)
		return mysql_fetch_row($r);
}
*/

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

	$nom = addslashes($nom);
	$q = spip_query("SELECT GET_LOCK('$nom', $timeout)");
	list($lock_ok) = spip_fetch_array($q);

	if (!$lock_ok) spip_log("pas de lock sql pour $nom");
	return $lock_ok;
}

function spip_release_lock($nom) {
	global $spip_mysql_db, $table_prefix;
	if ($table_prefix) $nom = "$table_prefix:$nom";
	if ($spip_mysql_db) $nom = "$spip_mysql_db:$nom";

	$nom = addslashes($nom);
	spip_query("SELECT RELEASE_LOCK('$nom')");
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
