<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_DB_MYSQL")) return;
define("_ECRIRE_INC_DB_MYSQL", "1");

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
// a noter qu'on pourrait y réaliser traite_query à moindre cout
// les \n et \t sont utiles au debusqueur

function spip_mysql_select($select, $from, $where,
			   $groupby, $orderby, $limit,
			   $sousrequete, $cpt,
			   $table, $id, $serveur) {

	$DB = 'spip_';
	$q = "\nFROM $DB" . join(",\n\t$DB", $from)
	. ($where ? "\nWHERE " . join("\n\tAND ", $where) : '')
	. ($groupby ? "\nGROUP BY $groupby" : '')
	. ($orderby ? "\nORDER BY $orderby" : '')
	. ($limit ? "\nLIMIT $limit" : '');

	if (!$sousrequete)
		$q = " SELECT ". join(", ", $select) . $q;
	else
		$q = " SELECT S_" . join(", S_", $select)
		. " FROM (" . join(", ", $select)
		. ", COUNT(".$sousrequete.") AS compteur " . $q
		.") AS S_$table WHERE compteur=" . $cpt;

	// Erreur ? C'est du debug de squelette, ou une erreur du serveur

	if ($GLOBALS['var_mode'] == 'debug') {
	  boucle_debug_resultat($id, '', $q);
	}

	if (!($res = @spip_query($q))) {
		include_ecrire('inc_debug_sql.php3');
		echo erreur_requete_boucle($q, $id, $table);
	}

#	 spip_log($serveur . spip_num_rows($res) . $q);
	return $res;
}

//
// Passage d'une requete standardisee
//
function traite_query($query) {
	if ($GLOBALS['table_prefix']) $table_pref = $GLOBALS['table_prefix']."_";
	else $table_pref = "";

	if ($GLOBALS['mysql_rappel_connexion'] AND $db = $GLOBALS['spip_mysql_db'])
		$db = '`'.$db.'`.';

	// changer les noms des tables ($table_prefix)
	if ($GLOBALS['flag_pcre']) {
		if (preg_match('/\s(SET|VALUES|WHERE)\s/i', $query, $regs)) {
			$suite = strstr($query, $regs[0]);
			$query = substr($query, 0, -strlen($suite));
		}
		$query = preg_replace('/([,\s])spip_/', '\1'.$db.$table_pref, $query) . $suite;
	}
	else {
		if (eregi('[[:space:]](SET|VALUES|WHERE)[[:space:]]', $query, $regs)) {
			$suite = strstr($query, $regs[0]);
			$query = substr($query, 0, -strlen($suite));
		}
		$query = ereg_replace('([[:space:],])spip_', '\1'.$db.$table_pref, $query) . $suite;
	}

	return $query;
}


//
// Connexion a la base
//

function spip_connect_db($host, $port, $login, $pass, $db) {
	global $spip_mysql_link, $spip_mysql_db;	// pour connexions multiples

	if ($port > 0) $host = "$host:$port";
	$spip_mysql_link = @mysql_connect($host, $login, $pass);
	$spip_mysql_db = $db;
	return @mysql_select_db($db);
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

function spip_insert($table, $champs, $valeurs) {
	spip_query("INSERT INTO spip_$table $champs VALUES $valeurs");
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
	if (!$valeurs) return '0=0';
	$s = split(',', $valeurs, 255);
	if (count($s) < 255) {
		return ("($val $not IN ($valeurs))");
	} else {
		$valeurs = array_pop($s);
		return ("($val $not IN (" . join(',',$s) . "))\n" .
			($not ? "AND\t" : "OR\t") .
			calcul_mysql_in($val, $valeurs, $not));
    }
}

?>
