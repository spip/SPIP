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


global $array_server;

// fonction pour la premiere connexion a un serveur array
// http://doc.spip.org/@req_array_dist
function req_array_dist($host, $port, $login, $pass, $db='', $prefixe='', $ldap='') {
	$GLOBALS['array_rappel_nom_base'] = false;
#	spip_log("Connexion vers $host, base $db, prefixe $prefixe "
#		 . ($ok ? "operationnelle sur $link" : 'impossible'));

	return array(
		'db' => $db,
		'prefixe' => 'spip',
		'link' => false,
		'ldap' => '',
		);
}

// http://doc.spip.org/@array_get_var
function array_get_var($table){
	// $table doit toujours etre un array
	if (!is_array($table)) return null;

	// faisons une copie, sans le sous tableau recursif GLOBALS eventuel
	$var = array();
	foreach($table as $k=>$v)
		if ($k != 'GLOBALS')
			$var[$k] = $v;
	return $var;
}

// http://doc.spip.org/@array_where_sql2php
function array_where_sql2php($where){
	$where = preg_replace(",(^|\()([\w.]+)\s*REGEXP\s*(.+)($|\)),Uims","\\1preg_match('/'.preg_quote(\\3).'/Uims',\\2)\\4",$where); // == -> preg_match
	$where = preg_replace(",([\w.]+)\s*=,Uims","\\1==",$where); // = -> ==
	$where = preg_replace(";^FIELD\(([^,]+),(.*)$;Uims","in_array(\\1,array(\\2)",$where); // IN -> FIELD -> in_array()
	return $where;
}

// http://doc.spip.org/@array_where_teste
function array_where_teste($cle,$valeur,$table,$where){
	if (is_array($valeur))
		$valeur = serialize($valeur);
	$where = str_replace(array(
	"$table.cle",
	'cle',
	"$table.valeur",
	'valeur',
	),
	array(
	"'".addslashes($cle)."'",
	"'".addslashes($cle)."'",
	"'".addslashes($valeur)."'",
	"'".addslashes($valeur)."'"
	),$where);
	return eval("if ($where) return true; else return false;");
}


// http://doc.spip.org/@calculer_array_where
function calculer_array_where($v)
{
	if (!is_array($v))
	  return array_where_sql2php($v) ;

	$op = array_shift($v);
	if (!($n=count($v)))
		return $op;
	else {
		$arg = calculer_array_where(array_shift($v));
		if ($n==1) {
			  return "$op($arg)";
		} else {
			$arg2 = calculer_array_where(array_shift($v));
			if ($n==2) {
				return array_where_sql2php("($arg $op $arg2)");
			} else return "($arg $op ($arg2) : $v[0])";
		}
	}
}


// http://doc.spip.org/@array_query_filter
function array_query_filter($cle,$valeur,$table,$where){
	static $wherec = array();
	$hash = md5(serialize($where));
	if (!isset($wherec[$hash])){
		if (is_array($where))
			$wherec[$hash] = implode("AND ",array_map('calculer_array_where',$where));
		else 
			$wherec[$hash] = calculer_array_where($where);
	}
	return array_where_teste($cle,$valeur,$table,$wherec[$hash]);
}

// http://doc.spip.org/@array_results
function &array_results($hash,$store='get'){
	static $array_results = array();
	if (is_array($store)){
		$array_results[$hash] = $store;
	}
	elseif($store=='get'){
		return isset($array_results[$hash])?each($array_results[$hash]):false;
	}
	elseif($store=='count'){
		return isset($array_results[$hash])?count($array_results[$hash]):false;
	}
	elseif($store=='free')
		unset($array_results[$hash]);
}
// emulations array
// http://doc.spip.org/@array_query
function array_query($query){
	// pas de jointure, que des requetes simples
	// trouver le tableau de base, fourni en condition having
	// c'est un hack ...
	$table = null;
	if (!is_array($query['having'])) return -1; // on arrive pas ici par une boucle !
	foreach($query['having'] as $k=>$w){
		if (reset($w)=='tableau')
			$table = end($w);
	}
	// recuperer le pseudo nom de la table pour la condition where
	if (is_array($query['from']))
		if (count($query['from'])!==1)
			return false;
		else
			$query['from'] = reset($query['from']);	

	$res = array_get_var($table); // recuperer la table
	if (!$res OR !is_array($res))
		$res = array();
	// filtrons les resultats
	if ($query['where']){
		foreach($res as $k=>$v){
			if (!array_query_filter($k,$v,$query['from'],$query['where']))
				unset($res[$k]);
		}
	}
	if ($query['orderby']){
		// on ne prend que le premier critere
		$sort = is_array($query['orderby'])?reset($query['orderby']):$query['orderby'];
		$sort = str_replace($table.".","",$sort);
		$sort = explode(',',$sort);
		$sort = reset($sort);
		if (preg_match(',^cle,',$sort)){
			if (preg_match(',DESC$,i',$sort))
				krsort($res);
			else
				ksort($res);
		}
		if (preg_match(',^valeur,',$sort)){
			if (preg_match(',DESC$,i',$sort))
				arsort($res);
			else
				asort($res);
		}
	}
	if ($query['limit']){
		$limit = explode(',',$query['limit']);
		$res = array_slice($res,$limit[0],$limit[1],true);
	}
	
	// ici calculer un vrai res si la variable existe
	if (count($res)) {
		$hash = md5(serialize($query));
		array_results($hash,$res);
		return $hash;
	}
	return -1; // pas de resultats mais pas false non plus
}

// -----

$GLOBALS['spip_array_functions_1'] = array(
		'count' => 'spip_array_count',
		'countsel' => 'spip_array_countsel',
		'errno' => 'spip_array_errno',
		'error' => 'spip_array_error',
		'fetch' => 'spip_array_fetch',
		'free' => 'spip_array_free',
		'hex' => 'spip_array_hex',
		'in' => 'spip_array_in', 
		'listdbs' => 'spip_array_listdbs',
		'multi' => 'spip_array_multi',
		'optimize' => 'spip_array_optimize',
		'query' => 'spip_array_query',
		'quote' => 'spip_array_quote',
		'select' => 'spip_array_select',
		'selectdb' => 'spip_array_selectdb',
		'set_charset' => 'spip_array_set_charset',
		'get_charset' => 'spip_array_get_charset',
		'showbase' => 'spip_array_showbase',
		'showtable' => 'spip_array_showtable',

		);


// http://doc.spip.org/@spip_array_set_charset
function spip_array_set_charset($charset, $serveur=''){
	#spip_log("changement de charset sql : "."SET NAMES "._q($charset));
	return true;
}

// http://doc.spip.org/@spip_array_get_charset
function spip_array_get_charset($charset=array(), $serveur=''){
	return false;
}

// Fonction de requete generale, munie d'une trace a la demande
// http://doc.spip.org/@spip_array_query
function spip_array_query($query, $serveur='') {

	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	$t = !isset($_GET['var_profile']) ? 0 : trace_query_start();
	$r = array_query($query,$db);

	if ($e = spip_array_errno())	// Log de l'erreur eventuelle
		$e .= spip_array_error($query); // et du fautif
	return $t ? trace_query_end(var_export($query,true), $t, $r, $e) : $r;
}


// fonction  instance de sql_select, voir ses specs dans abstract.php
// Les \n et \t sont utiles au debusqueur.

// http://doc.spip.org/@spip_array_select
function spip_array_select($select, $from, $where='',
			   $groupby='', $orderby='', $limit='', $having='',
			   $serveur='') {

	$from = (!is_array($from) ? $from : spip_array_select_as($from));

	// pas de prefixage par nom de table dans array, une seule table a la fois !
	$clean_prefix = trim($from).".";

	$query = array(
	'select'=>$select,
	'from'=>$from,
	'where'=>$where,
	'groupby'=>$groupby,
	'orderby'=>$orderby,
	'limit'=>$limit);
	$querydump = var_export($query,1);
	$query['having'] = $having;

	// Erreur ? C'est du debug de squelette, ou une erreur du serveur
	if (isset($GLOBALS['var_mode']) AND $GLOBALS['var_mode'] == 'debug') {
		include_spip('public/debug');
		boucle_debug_requete($querydump);
	}

	if (!($res = spip_array_query($query, $serveur))) {
		include_spip('public/debug');
		erreur_requete_boucle($querydump,
				      spip_array_errno(),
				      spip_array_error($query) );
	}

	return $res;
}

// 0+x avec un champ x commencant par des chiffres est converti par array
// en le nombre qui commence x.
// Pas portable malheureusement, on laisse pour le moment.


// http://doc.spip.org/@spip_array_order
function spip_array_order($orderby)
{
	return (is_array($orderby)) ? join(", ", $orderby) :  $orderby;
}


// http://doc.spip.org/@spip_array_select_as
function spip_array_select_as($args)
{
	$argsas = "";
	foreach($args as $k => $v) {
		if (strpos($v, 'JOIN') === false)  $argsas .= ', ';
		$argsas .= $v;// PAS de AS en array : . (is_numeric($k) ? '' : " AS `$k`");
	}
	return substr($argsas,2);
}


// http://doc.spip.org/@spip_array_selectdb
function spip_array_selectdb($db) {
	return true;
}


// Retourne les bases accessibles
// Attention on n'a pas toujours les droits


// http://doc.spip.org/@spip_array_listdbs
function spip_array_listdbs($serveur='') {
	return false;
}



// http://doc.spip.org/@spip_array_showbase
function spip_array_showbase($match, $serveur='')
{
	return false;
}


// pas fe SHOW en array, on renvoie une declaration type si la variable existe
// http://doc.spip.org/@spip_array_showtable
function spip_array_showtable($nom_table, $serveur='')
{
	if (in_array($nom_table,array('tableau')))
		return array('field'=>array('cle'=>'int','valeur'=>'text'),'key'=>array('PRIMARY KEY'=>'cle'));
	return false;
}

//
// Recuperation des resultats
//


// http://doc.spip.org/@spip_array_fetch
function spip_array_fetch($r, $t='', $serveur='') {
	if ($r AND $each = array_results($r)) {
		list($cle,$valeur) = $each;
		return array('cle'=>$cle,'valeur'=>$valeur);
	}
	return false;
}

// http://doc.spip.org/@spip_array_error
function spip_array_error($query='') {
	spip_log("Erreur - $query", 'array');
	return false;
}

// A transposer dans les portages

// http://doc.spip.org/@spip_array_errno
function spip_array_errno() {
	return false;
}

// Interface de abstract_sql

// http://doc.spip.org/@spip_array_count
function spip_array_count($r, $serveur='') {
	return array_results($r,'count');
}



// http://doc.spip.org/@spip_array_free
function spip_array_free($r, $serveur='') {
	array_results($r,'free');
	return true;
}


// http://doc.spip.org/@spip_array_multi
function spip_array_multi ($objet, $lang) {
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

// http://doc.spip.org/@spip_array_hex
function spip_array_hex($v)
{
	return "0x" . $v;
}

// http://doc.spip.org/@spip_array_quote
function spip_array_quote($v)
{
	return _q($v);
}

// pour compatibilite
// http://doc.spip.org/@spip_array_in
function spip_array_in($val, $valeurs, $not='', $serveur='') {
	return calcul_array_in($val, $valeurs, $not);
}

//
// IN (...) est limite a 255 elements, d'ou cette fonction assistante
//

// http://doc.spip.org/@calcul_array_in
function calcul_array_in($val, $valeurs, $not='') {
	if (is_array($valeurs))
		$valeurs = join(',', array_map('_q', $valeurs));
	if (!strlen(trim($valeurs))) return ($not ? "0=0" : '0=1');

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


// http://doc.spip.org/@spip_array_cite
function spip_array_cite($v, $type) {
	if (sql_test_date($type) AND preg_match('/^\w+\(/', $v)
	OR (sql_test_int($type)
		 AND (is_numeric($v)
		      OR (ctype_xdigit(substr($v,2))
			  AND $v[0]=='0' AND $v[1]=='x'))))
		return $v;
	else return  ("'" . addslashes($v) . "'");
}

?>