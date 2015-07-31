<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_MYSQL")) return;
define("_ECRIRE_INC_MYSQL", "1");

function query($query) {
	// return spip_query_profile($query);	// a decommenter pour chronometrer les requetes
	// return spip_query_debug($query);		// a decommenter pour afficher toutes les erreurs
	$suite = "";
	if (eregi('[[:space:]](VALUES|WHERE)[[:space:]].*$', $query, $regs)) {
		$suite = $regs[0];
		$query = substr($query, 0, -strlen($suite));
	}
	$query = ereg_replace('([[:space:],])spip_', '\1'.$GLOBALS['table_prefix'].'_', $query) . $suite;
	return mysql_query($query);
}

function spip_query_profile($query) {
	static $tt = 0;
	$suite = "";
	if (eregi('[[:space:]](VALUES|WHERE)[[:space:]].*$', $query, $regs)) {
		$suite = $regs[0];
		$query = substr($query, 0, -strlen($suite));
	}
	$query = ereg_replace('([[:space:],])spip_', '\1'.$GLOBALS['table_prefix'].'_', $query) . $suite;
	$m1 = microtime();
	$result = mysql_query($query);
	$m2 = microtime();
	list($usec, $sec) = explode(" ", $m1);
	list($usec2, $sec2) = explode(" ", $m2);
	$dt = $sec2 + $usec2 - $sec - $usec;
	$tt += $dt;
	echo "<small>".htmlentities($query);
	echo " -> <font color='blue'>".sprintf("%3f", $dt)."</font> ($tt)</small><p>\n";
	return $result;
}

function spip_query_debug($query) {
	$suite = "";
	if (eregi('[[:space:]](VALUES|WHERE)[[:space:]].*$', $query, $regs)) {
		$suite = $regs[0];
		$query = substr($query, 0, -strlen($suite));
	}
	$query = ereg_replace('([[:space:],])spip_', '\1'.$GLOBALS['table_prefix'].'_', $query) . $suite;
	$r = mysql_query($query);
	if ($GLOBALS['connect_statut'] == '0minirezo' AND $s = mysql_error()) {
		echo "Erreur dans la requ&ecirc;te : ".htmlentities($query)."<br>";
		echo "&laquo; ".htmlentities($s)." &raquo;<p>";
	}
	return $r;
}


?>