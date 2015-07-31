<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined('_ECRIRE_INC_VERSION')) return;

/*
 * Des fonctions pour les requetes SQL
 * 
 * Voir la liste des fonctions natives : http://www.sqlite.org/lang_corefunc.html
 * Et la liste des evolutions pour : http://sqlite.org/changes.html
 * 
 */
// http://doc.spip.org/@_sqlite_init_functions
function _sqlite_init_functions(&$sqlite){
	
	if (!$sqlite) return false;

	
	$fonctions = array(
		'CONCAT'		=> array( '_sqlite_func_concat'			,-1),
		'CEIL'      => array( '_sqlite_func_ceil', 1), // absent de sqlite2
		
		'DATE_FORMAT'	=> array( '_sqlite_func_strftime'		,2),
		'DAYOFMONTH'	=> array( '_sqlite_func_dayofmonth'		,1),

		'EXTRAIRE_MULTI' => array( '_sqlite_func_extraire_multi', 2), // specifique a SPIP/sql_multi()
		'EXP'			=> array( 'exp'							,1),//exponentielle
		'FIND_IN_SET'	=> array( '_sqlite_func_find_in_set'	,2),
		'FLOOR'      => array( '_sqlite_func_floor', 1), // absent de sqlite2

		'IF'			=> array( '_sqlite_func_if' 			,3),
		'INSERT'		=> array( '_sqlite_func_insert'			,4),		
		'INSTR'			=> array( '_sqlite_func_instr'			,2),

		'LEAST'			=> array( '_sqlite_func_least'			,3),
		'_LEFT'			=> array( '_sqlite_func_left'			,2),
#		'LENGTH'		=> array( 'strlen'						,1), // present v1.0.4
#		'LOWER'			=> array( 'strtolower'					,1), // present v2.4
#		'LTRIM'			=> array( 'ltrim'						,1), // present en theorie

		'NOW'			=> array( '_sqlite_func_now'			,0),
		
		'MD5'			=> array( 'md5'							,1),
		'MONTH'			=> array( '_sqlite_func_month'			,1),
		
		'PREG_REPLACE'	=> array( '_sqlite_func_preg_replace'	,3),	
		
		'RAND'			=> array( '_sqlite_func_rand'			,0), // sinon random() v2.4
		'REGEXP'		=> array( '_sqlite_func_regexp_match'	,2), // critere REGEXP supporte a partir de v3.3.2
		//'REGEXP_MATCH'	=> array( '_sqlite_func_regexp_match'	,2), // critere REGEXP supporte a partir de v3.3.2

		'RIGHT'			=> array( '_sqlite_func_right'			,2),
#		'RTRIM'			=> array( 'rtrim'						,1), // present en theorie

		'SETTYPE'		=> array( 'settype'						,2), // CAST present en v3.2.3
		'SQRT'			=> array( 'sqrt'						,1), 
		'SUBSTRING'		=> array( '_sqlite_func_substring'						/*,3*/), // peut etre appelee avec 2 ou 3 arguments, index base 1 et non 0
		
		'TO_DAYS'		=> array( '_sqlite_func_to_days'		,1),
#		'TRIM'			=> array( 'trim'						,1), // present en theorie

		'TIMESTAMPDIFF' => array('_sqlite_timestampdiff'		/*,3*/),

		'UNIX_TIMESTAMP'=> array( '_sqlite_func_unix_timestamp'	,1),
#		'UPPER'			=> array( 'strtoupper'					,1), // present v2.4		

		'VIDE'			=> array( '_sqlite_func_vide'			,0), // du vide pour SELECT 0 as x ... ORDER BY x -> ORDER BY vide()
			
		'YEAR'			=> array( '_sqlite_func_year'			,1)
	);
	

	foreach ($fonctions as $f=>$r){
		_sqlite_add_function($sqlite, $f, $r);
	}

	#spip_log('functions sqlite chargees ','sqlite.'._LOG_DEBUG);
}

// permet au besoin de charger des fonctions ailleurs par _sqlite_init_functions();
// http://doc.spip.org/@_sqlite_add_function
function _sqlite_add_function(&$sqlite, &$f, &$r){
	if (_sqlite_is_version(3, $sqlite)){
		isset($r[1])
			?$sqlite->sqliteCreateFunction($f, $r[0], $r[1])
			:$sqlite->sqliteCreateFunction($f, $r[0]);
	} else {
		isset($r[1])
			?sqlite_create_function($sqlite, $f, $r[0], $r[1])	
			:sqlite_create_function($sqlite, $f, $r[0]);
	}
}

//
// SQLite : fonctions sqlite -> php
// entre autre auteurs : mlebas
//

function _sqlite_func_ceil($a) {
	return ceil($a);
}

// http://doc.spip.org/@_sqlite_func_concat
function _sqlite_func_concat () {
	$args = func_get_args();
	return join('',$args);
}


// http://doc.spip.org/@_sqlite_func_dayofmonth
function _sqlite_func_dayofmonth ($d) {
	return _sqlite_func_date("d",$d);
}


// http://doc.spip.org/@_sqlite_func_find_in_set
function _sqlite_func_find_in_set($num, $set) {
  $rank=0;
  foreach (explode(",",$set) as $v) {
   if ($v == $num) return (++$rank);
   $rank++;
  }
  return 0;
}

function _sqlite_func_floor($a) {
	return floor($a);
}

// http://doc.spip.org/@_sqlite_func_if
function _sqlite_func_if ($bool, $oui, $non) {
    return ($bool)?$oui:$non;
}


/*
 * INSERT(chaine, index, longueur, chaine) 	MySQL
 * Retourne une chaine de caracteres a partir d'une chaine dans laquelle "sschaine"
 *  a ete inseree a la position "index" en remplacant "longueur" caracteres.
 */ 
// http://doc.spip.org/@_sqlite_func_insert
function _sqlite_func_insert ($s, $index, $longueur, $chaine) {
	return
			substr($s,0, $index)
		. $chaine
		. substr(substr($s, $index), $longueur);
}


// http://doc.spip.org/@_sqlite_func_instr
function _sqlite_func_instr ($s, $search) {
    return strpos($s,$search);
}


// http://doc.spip.org/@_sqlite_func_least
function _sqlite_func_least () {
	$arg_list = func_get_args();
    $least = min($arg_list);
	#spip_log("Passage avec LEAST : $least",'sqlite.'._LOG_DEBUG);
	return $least;
}


// http://doc.spip.org/@_sqlite_func_left
function _sqlite_func_left ($s, $lenght) {
    return substr($s,0,$lenght);
}


// http://doc.spip.org/@_sqlite_func_now
function _sqlite_func_now(){
	static $now = null;
	if (is_null($now))
		$now = date("Y-m-d H:i:s");
	#spip_log("Passage avec NOW : $now",'sqlite.'._LOG_DEBUG);
	return $now;
}


// http://doc.spip.org/@_sqlite_func_month
function _sqlite_func_month ($d) {
	return _sqlite_func_date("m",$d);
}



// http://doc.spip.org/@_sqlite_func_preg_replace
function _sqlite_func_preg_replace($quoi, $cherche, $remplace) {
	$return = preg_replace('%'.$cherche.'%', $remplace, $quoi);
	#spip_log("preg_replace : $quoi, $cherche, $remplace, $return",'sqlite.'._LOG_DEBUG);
	return $return;
}

/**
 * Extrait une langue d'un texte <multi>[fr] xxx [en] yyy</multi>
 * 
 * @param string $quoi le texte contenant ou non un multi
 * @param string $lang la langue a extraire
 * @return string, l'extrait trouve.
**/
function _sqlite_func_extraire_multi($quoi, $lang) {
	if (!defined('_EXTRAIRE_MULTI'))
		include_spip('inc/filtres');
	if (!function_exists('approcher_langue'))
		include_spip('inc/lang');
	if (preg_match_all(_EXTRAIRE_MULTI, $quoi, $regs, PREG_SET_ORDER)) {
		foreach ($regs as $reg) {
			// chercher la version de la langue courante
			$trads = extraire_trads($reg[1]);
			if ($l = approcher_langue($trads, $lang)) {
				$trad = $trads[$l];
			} else {
				$trad = reset($trads);
			}
			$quoi = str_replace($reg[0], $trad, $quoi);
		}
	}
	return $quoi;
}


// http://doc.spip.org/@_sqlite_func_rand
function _sqlite_func_rand() {
	return rand();
}


// http://doc.spip.org/@_sqlite_func_right
function _sqlite_func_right ($s, $length) {
	return substr($s,0 - $length);
}


// http://doc.spip.org/@_sqlite_func_regexp_match
function _sqlite_func_regexp_match($cherche, $quoi) {
	// optimiser un cas tres courant avec les requetes en base
	if (!$quoi AND !strlen($quoi)) return false;
	$u = $GLOBALS['meta']['pcre_u'];
	$return = preg_match('%'.$cherche.'%imsS'.$u, $quoi);
	#spip_log("regexp_replace : $quoi, $cherche, $remplace, $return",'sqlite.'._LOG_DEBUG);
	return $return;
}

// http://doc.spip.org/@_sqlite_func_strftime
function _sqlite_func_strftime($date, $conv){
	return strftime($conv, is_int($date)?$date:strtotime($date));
}

/**
 * Nombre de jour entre 0000-00-00 et $d
 * http://doc.spip.org/@_sqlite_func_to_days
 * cf http://dev.mysql.com/doc/refman/5.5/en/date-and-time-functions.html#function_to-days
 * @param string $d
 * @return int
 */
function _sqlite_func_to_days ($d) {
	static $offset = 719528; // nb de jour entre 0000-00-00 et timestamp 0=1970-01-01
	$result = $offset+(int)ceil(_sqlite_func_unix_timestamp($d)/(24*3600));
	#spip_log("Passage avec TO_DAYS : $d, $result",'sqlite.'._LOG_DEBUG);
	return $result;
}

function _sqlite_func_substring($string,$start,$len=null){
	// SQL compte a partir de 1, php a partir de 0
	$start = ($start>0)?$start-1:$start;
	if (is_null($len))
		return substr($string,$start);
	else
		return substr($string,$start,$len);
}

/**
 * Calcul de la difference entre 2 timestamp, exprimes dans l'unite fournie en premier argument
 * emule https://dev.mysql.com/doc/refman/5.5/en/date-and-time-functions.html#function_timestampdiff
 * @param string $unit
 * @param string $date1
 * @param string $date2
 * @return int
 */
function _sqlite_timestampdiff($unit,$date1,$date2){
	// PHP >= 5.3
	if (function_exists("date_diff")){
		$d1 = date_create($date1);
		$d2 = date_create($date2);
		$diff = date_diff($d1,$d2);
		$inv = $diff->invert?-1:1;
		switch($unit){
			case "YEAR":
				return $inv*$diff->y;
			case "QUARTER":
				return $inv*(4*$diff->y+intval(floor($diff->m/3)));
			case "MONTH":
				return $inv*(12*$diff->y+$diff->m);
			case "WEEK":
				return $inv*intval(floor($diff->days/7));
			case "DAY":
				#var_dump($inv*$diff->days);
				return $inv*$diff->days;
			case "HOUR":
				return $inv*(24*$diff->days+$diff->h);
			case "MINUTE":
				return $inv*((24*$diff->days+$diff->h)*60+$diff->i);
			case "SECOND":
				return $inv*(((24*$diff->days+$diff->h)*60+$diff->i)*60+$diff->s);
			case "MICROSECOND":
				return $inv*(((24*$diff->days+$diff->h)*60+$diff->i)*60+$diff->s)*1000000;
		}
		return 0;
	}
	// PHP < 5.3
	else {
		$d1 = strtotime($date1);
		$d2 = strtotime($date2);
		$diff = $d2 - $d1;
		$sign = ($diff<0?-1:1);
		$diff = $sign * $diff;
		switch($unit){
			case "YEAR":
				$diff = $d2-$d1;
				return $sign*(date('Y',abs($diff))-date('Y',0));
			case "QUARTER":
				return $sign*(4*(date('Y',abs($diff))-date('Y',0))+intval(floor((date('m',$diff)-1)/3)));
			case "MONTH":
				return $sign*((date('Y',$diff)-date('Y',0))*12+date('m',$diff)-1);
			case "WEEK":
				return intval(floor(($d2-$d1)/3600/7));
			case "DAY":
				return intval(floor(($d2-$d1)/3600/24));
			case "HOUR":
				return intval(floor(($d2-$d1)/3600));
			case "MINUTE":
				return intval(floor(($d2-$d1)/60));
			case "SECOND":
				return $d2-$d1;
			case "MICROSECOND":
				return $d2-$d1*1000000;
		}
	}
}

// http://doc.spip.org/@_sqlite_func_unix_timestamp
function _sqlite_func_unix_timestamp($d) {
	static $mem = array();
	static $n = 0;
	if (isset($mem[$d])) return $mem[$d];
	if ($n++>100) {$mem = array();$n=0;}

	//2005-12-02 20:53:53
	#spip_log("Passage avec UNIX_TIMESTAMP : $d",'sqlite.'._LOG_DEBUG);
	if (!$d) return $mem[$d] = mktime();
	// une pile plus grosse n'accelere pas le calcul
	return $mem[$d] = strtotime($d);
}


// http://doc.spip.org/@_sqlite_func_year
function _sqlite_func_year ($d) {
	return _sqlite_func_date("Y",$d);
}

/**
 * version optimisee et memoizee de date() utilisee par
 * _sqlite_func_year, _sqlite_func_month, _sqlite_func_dayofmonth
 * @param string $quoi
 *   format : Y, m, ou d
 * @param int $d
 *   timestamp
 * @return int
 */
function _sqlite_func_date($quoi,$d) {
	static $mem = array();
	static $n = 0;
	if (isset($mem[$d])) return $mem[$d][$quoi];
	if ($n++>100) {$mem = array();$n=0;}

	$dec = date("Y-m-d",_sqlite_func_unix_timestamp($d));
	$mem[$d] = array("Y"=>substr($dec,0,4),"m"=>substr($dec,5,2),"d"=>substr($dec,8,2));
	return $mem[$d][$quoi];
}

// http://doc.spip.org/@_sqlite_func_vide
function _sqlite_func_vide(){
	return;
}



?>
