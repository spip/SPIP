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
define('SPIP_BOTH', MYSQL_BOTH);
define('SPIP_ASSOC', MYSQL_ASSOC);
define('SPIP_NUM', MYSQL_NUM);

//
// Appel de requetes SQL
//

// fonction pour la premiere connexion

// http://doc.spip.org/@base_db_mysql_dist
function base_db_mysql_dist()
{
	// fichier d'init present ?
	if (!_FILE_CONNECT) {
		if ($GLOBALS['exec'] != 'install') // est-ce l'installation ?
			return false; // non, faut faire sans
		else  return 'spip_query_db'; // oui; valeur d'office
	}

	include_once(_FILE_CONNECT); 
	if (!$GLOBALS['db_ok']) return false;

	// Version courante = 0.3
	//
	// les versions 0.1 et 0.2 fonctionnent toujours, meme si :
	// - la version 0.1 est moins performante que la 0.2
	// - la 0.2 fait un include_ecrire('inc_db_mysql.php3')
	// En tout cas on ne force pas la mise a niveau
	if ($GLOBALS['spip_connect_version'] >= 0.1)
		return 'spip_query_db';

	// La version 0.0 (non numerotee) doit etre refaite par un admin

	if (!_DIR_RESTREINT) return false;

	include_spip('inc/headers');
	redirige_par_entete(generer_url_ecrire('upgrade', 'reinstall=oui', true));
}

// http://doc.spip.org/@spip_query_db
function spip_query_db($query) {

	$query = traite_query($query);

	$start = ($GLOBALS['mysql_profile'] AND (($GLOBALS['connect_statut'] == '0minirezo') OR ($GLOBALS['auteur_session']['statut'] == '0minirezo'))) ? microtime() : 0;

	return spip_mysql_trace($query, 
				$start,
		 (($GLOBALS['mysql_rappel_connexion'] AND $GLOBALS['spip_mysql_link']) ?
			mysql_query($query, $GLOBALS['spip_mysql_link']) :
			mysql_query($query)));
}

// http://doc.spip.org/@spip_mysql_trace
function spip_mysql_trace($query, $start, $result)
{
	$s = mysql_errno();

	if ($start) spip_mysql_timing($start, microtime(), $query, $result);

	if ($s) {
		$s .= ' '.mysql_error();
		if ($GLOBALS['mysql_debug']
		AND (($GLOBALS['connect_statut'] == '0minirezo')
		  OR ($GLOBALS['auteur_session']['statut'] == '0minirezo'))) {
			include_spip('public/debug');
			echo _T('info_erreur_requete'),
			  " ",
			  htmlentities($query),
			  "<br />&laquo; ",
			  htmlentities($result = $s),
			  " &raquo;<p>";
		}
		spip_log($GLOBALS['REQUEST_METHOD'].' '.$GLOBALS['REQUEST_URI'], 'mysql');
		spip_log("$result - $query", 'mysql');
		spip_log($s, 'mysql');
	}
	return $result;
}

// http://doc.spip.org/@spip_mysql_timing
function spip_mysql_timing($m1, $m2, $query, $result)
{
	static $tt = 0;
	list($usec, $sec) = explode(" ", $m1);
	list($usec2, $sec2) = explode(" ", $m2);
 	$dt = $sec2 + $usec2 - $sec - $usec;
	$tt += $dt;
	echo "<small>", htmlentities($query), " -> <span style='color: blue'>", sprintf("%3f", $dt),"</span> (", $tt, ")</small> $result<p>\n";
}

// fonction appelant la precedente  specifiquement pour l'espace public
// c'est une instance de spip_abstract_select, voir ses specs dans abstract.php
// traite_query pourrait y est fait d'avance, à moindre cout.
// Les \n et \t sont utiles au debusqueur.

// La parametre sous_requete n'est plus utilise

// http://doc.spip.org/@spip_mysql_select
function spip_mysql_select($select, $from, $where,
			   $groupby, $orderby, $limit,
			   $sousrequete, $having,
			   $table, $id, $server) {

	$query = (!is_array($select) ? $select : join(", ", $select)) .
		(!$from ? '' :
			("\nFROM " .
			(!is_array($from) ? $from : spip_select_as($from))))
		. (!$where ? '' : ("\nWHERE " . (!is_array($where) ? $where : (join("\n\tAND ", array_map('calculer_where', $where))))))
		. ($groupby ? "\nGROUP BY $groupby" : '')
		. (!$having ? '' : "\nHAVING " . (!is_array($having) ? $having : (join("\n\tAND ", array_map('calculer_where', $having)))))
		. ($orderby ? ("\nORDER BY " . join(", ", $orderby)) : '')
		. ($limit ? "\nLIMIT $limit" : '');

	// Erreur ? C'est du debug de squelette, ou une erreur du serveur

	if ($GLOBALS['var_mode'] == 'debug') {
		include_spip('public/debug');
		boucle_debug_resultat($id, 'requete', "SELECT " . $query);
	}

	if (!($res = @spip_query("SELECT ". $query, $server))) {
		include_spip('public/debug');
		erreur_requete_boucle($query, $id, $table,
				      spip_sql_errno(),
				      spip_sql_error());
	}
	return $res;
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
// Passage d'une requete standardisee
// Quand tous les appels SQL seront abstraits on pourra l'ameliorer

// http://doc.spip.org/@traite_query
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
// Fonction appelee uniquement par le fichier FILE_CONNECT cree a l'installation, 
// et comportant les identifants de connexion SQL
// db_ok est globale, pour test par base_db_mysql_dist ci-dessus
// Ce serait plus propre de reduire FILE_CONNECT a un fichier de donnees
// et d'appeler cette fonction a partir de base_db_mysql_dist.

// http://doc.spip.org/@spip_connect_db
function spip_connect_db($host, $port, $login, $pass, $db) {
	global $spip_mysql_link, $spip_mysql_db;	// pour connexions multiples

	// gerer le fichier tmp/mysql_out
	## TODO : ajouter md5(parametres de connexion)
	if (@file_exists(_DIR_TMP.'mysql_out')
	AND (time() - @filemtime(_DIR_TMP.'mysql_out') < 30)
	AND !defined('_ECRIRE_INSTALL'))
		return $GLOBALS['db_ok'] = false;

	if ($port > 0) $host = "$host:$port";
	$spip_mysql_link = @mysql_connect($host, $login, $pass);
	$spip_mysql_db = $db;
	$ok = @mysql_select_db($db);

	if (defined('_MYSQL_SQL_MODE_TEXT_NOT_NULL'))
		mysql_query("set sql_mode=''");

	$GLOBALS['db_ok'] = $ok
	AND !!@spip_num_rows(@spip_query_db('SELECT COUNT(*) FROM spip_meta'));

	// En cas d'erreur marquer le fichier mysql_out
	if (!$GLOBALS['db_ok']
	AND !defined('_ECRIRE_INSTALL')) {
		@touch(_DIR_TMP.'mysql_out');
		$err = 'Echec connexion MySQL '.spip_sql_errno().' '.spip_sql_error();
		spip_log($err);
		spip_log($err, 'mysql');
	} else $GLOBALS['db_ok'] = 'spip_query_db';
	return $GLOBALS['db_ok'];
}

// http://doc.spip.org/@spip_mysql_showtable
function spip_mysql_showtable($nom_table)
{
	$a = spip_query("SHOW TABLES LIKE '$nom_table'");
	if (!$a) return "";
	if (!spip_fetch_array($a)) return "";
	list(,$a) = spip_fetch_array(spip_query("SHOW CREATE TABLE $nom_table"),SPIP_NUM);
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

// http://doc.spip.org/@spip_fetch_array
function spip_fetch_array($r, $t=SPIP_ASSOC) {
	if ($r) return mysql_fetch_array($r, $t);
}

// http://doc.spip.org/@spip_sql_error
function spip_sql_error() {
	return mysql_error();
}

// http://doc.spip.org/@spip_sql_errno
function spip_sql_errno() {
	return mysql_errno();
}

// http://doc.spip.org/@spip_num_rows
function spip_num_rows($r) {
	if ($r)
		return mysql_num_rows($r);
}

// http://doc.spip.org/@spip_free_result
function spip_free_result($r) {
	if ($r)
		return mysql_free_result($r);
}

// http://doc.spip.org/@spip_mysql_insert
function spip_mysql_insert($table, $champs, $valeurs) {
	spip_query("INSERT INTO $table $champs VALUES $valeurs");
	return  mysql_insert_id();
}

// http://doc.spip.org/@spip_insert_id
function spip_insert_id() {
	return mysql_insert_id();
}

//
// Poser un verrou local a un SPIP donne
//
// http://doc.spip.org/@spip_get_lock
function spip_get_lock($nom, $timeout = 0) {
	global $spip_mysql_db, $table_prefix;
	if ($table_prefix) $nom = "$table_prefix:$nom";
	if ($spip_mysql_db) $nom = "$spip_mysql_db:$nom";

	// Changer de nom toutes les heures en cas de blocage MySQL (ca arrive)
	define('_LOCK_TIME', intval(time()/3600-316982));
	$nom .= _LOCK_TIME;

	$q = spip_query("SELECT GET_LOCK(" . _q($nom) . ", $timeout)");
	list($lock_ok) = spip_fetch_array($q,SPIP_NUM);

	if (!$lock_ok) spip_log("pas de lock sql pour $nom");
	return $lock_ok;
}

// http://doc.spip.org/@spip_release_lock
function spip_release_lock($nom) {
	global $spip_mysql_db, $table_prefix;
	if ($table_prefix) $nom = "$table_prefix:$nom";
	if ($spip_mysql_db) $nom = "$spip_mysql_db:$nom";

	$nom .= _LOCK_TIME;

	spip_query("SELECT RELEASE_LOCK(" . _q($nom) . ")");
}

// http://doc.spip.org/@spip_mysql_version
function spip_mysql_version() {
	$row = spip_fetch_array(spip_query("SELECT version() AS n"));
	return ($row['n']);
}

// http://doc.spip.org/@creer_objet_multi
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
