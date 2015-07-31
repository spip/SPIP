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

// Ce fichier definit la couche d'abstraction entre SPIP et ses serveurs SQL.
// Cette couche n'est pour le moment qu'un ensemble de fonctions ecrites
// rapidement pour generaliser le code strictement MySQL de SPIP < 1.9.3.
// Une reconception generale est a prevoir apres l'experience des premiers
// portages.

// Cette fonction charge la description d'un serveur de base de donnees
// (via la fonction spip_connect qui etablira la connexion si ce n'est fait)
// et retourne la fonction produisant la requête SQL demandee
// Erreur fatale si la fonctionnalite est absente

// http://doc.spip.org/@sql_serveur
function sql_serveur($ins_sql, $serveur='') {

	$desc = spip_connect($serveur);
	if (function_exists($f = @$desc[$ins_sql])) return $f;
	include_spip('inc/minipres');
	echo minipres("'$serveur' " ._T('zbug_serveur_indefini') .  " ($ins_sql)");
	exit;
}

// Dans quelques cas, l'absence de fonctionnalite ne doit pas declencher
// d'erreur fatale ==> ne pas utiliser la fonction generale

// http://doc.spip.org/@sql_explain
function sql_explain($q, $serveur='') {
	$desc = spip_connect($serveur);
	if (function_exists($f = @$desc['explain'])) {
		return $f($q, $serveur);
	}
	spip_log("Le serveur '$serveur' ne dispose pas de 'explain'");
	return false;
}

// http://doc.spip.org/@sql_optimize
function sql_optimize($q, $serveur='') {
	$desc = spip_connect($serveur);
	if (function_exists($f = @$desc['optimize'])) {
		return $f($q, $serveur);
	}
	spip_log("Le serveur '$serveur' ne dispose pas de 'optimize'");
}

function sql_repair($table, $serveur='') {
	$desc = spip_connect($serveur);
	if (function_exists($f = @$desc['repair'])) {
		return $f($table, $serveur);
	}
	spip_log("Le serveur '$serveur' ne dispose pas de 'repair'");
}

// Demande si un charset est disponible. 
// http://doc.spip.org/@sql_get_charset
function sql_get_charset($charset, $serveur=''){
  // le nom http du charset differe parfois du nom SQL utf-8 ==> utf8 etc.
	$desc = spip_connect($serveur);
	$c = @$desc['charsets'][$charset];
	if ($c) {
		if (function_exists($f=@$desc['get_charset'])) 
			if ($f($c, $serveur)) return $c;
	}
	spip_log("SPIP ne connait pas les Charsets disponibles sur le serveur $serveur. Le serveur choisira seul.");
	return false;
}

// Regler le codage de connexion

// http://doc.spip.org/@sql_set_charset
function sql_set_charset($charset,$serveur=''){
	$f = sql_serveur('set_charset', $serveur);
	return $f($charset, $serveur);
}

// Cette fonction est systematiquement appelee par les squelettes
// pour constuire une requete SQL de type "lecture" (SELECT) a partir
// de chaque boucle.
// Elle construit et exe'cute une reque^te SQL correspondant a` une balise
// Boucle ; elle notifie une erreur SQL dans le flux de sortie et termine
// le processus.
// Sinon, retourne la ressource interrogeable par sql_fetch.
// Recoit en argument:
// - le tableau des champs a` ramener (Select)
// - le tableau des tables a` consulter (From)
// - le tableau des conditions a` remplir (Where)
// - le crite`re de regroupement (Group by)
// - le tableau de classement (Order By)
// - le crite`re de limite (Limit)
// - une sous-requete e'ventuelle (inutilisee pour le moment. MySQL > 4.1)
// - le tableau des des post-conditions a remplir (Having)
// - le nom de la table (pour le message d'erreur e'ventuel)
// - le nom de la boucle (pour le message d'erreur e'ventuel)
// - le serveur sollicite (pour retrouver la connexion)

// http://doc.spip.org/@sql_select
function sql_select (
	$select = array(), $from = array(), $where = array(),
	$groupby = '', $orderby = array(), $limit = '',
	$sousrequete = '', $having = array(),
	$table = '', $id = '', $serveur='') {

	$f = sql_serveur('select', $serveur);

	return $f($select, $from, $where,
		  $groupby, $orderby, $limit,
		  $sousrequete, $having,
		  $table, $id, $serveur);
}

// http://doc.spip.org/@sql_alter
function sql_alter($q, $serveur='') {
	$f = sql_serveur('alter', $serveur);
	return $f($q, $serveur);
}

// http://doc.spip.org/@sql_fetch
function sql_fetch($res, $serveur='') {
	$f = sql_serveur('fetch', $serveur);
	return $f($res, NULL, $serveur);
}

// http://doc.spip.org/@sql_selectdb
function sql_selectdb($res, $serveur='')
{
	$f = sql_serveur('selectdb', $serveur);
	return $f($res, $serveur);
}

// http://doc.spip.org/@sql_count
function sql_count($res, $serveur='')
{
	$f = sql_serveur('count', $serveur);
	return $f($res, $serveur);
}

// http://doc.spip.org/@sql_free
function sql_free($res, $serveur='')
{
	$f = sql_serveur('free', $serveur);
	return $f($res);
}

// http://doc.spip.org/@sql_insert
function sql_insert($table, $noms, $valeurs, $desc=array(), $serveur='')
{
	$f = sql_serveur('insert', $serveur);
	return $f($table, $noms, $valeurs, $desc, $serveur);
}

// http://doc.spip.org/@sql_insertq
function sql_insertq($table, $couples, $desc=array(), $serveur='')
{
	$f = sql_serveur('insertq', $serveur);
	return $f($table, $couples, $desc, $serveur);
}

// http://doc.spip.org/@sql_update
function sql_update($table, $exp, $where='', $desc=array(), $serveur='')
{
	$f = sql_serveur('update', $serveur);
	return $f($table, $exp, $where, $desc, $serveur);
}

// Update est presque toujours appelee sur des constantes ou des dates
// Cette fonction est donc plus utile que la precedente,d'autant qu'elle
// permet de gerer les differences de representation des constantes.
// http://doc.spip.org/@sql_updateq
function sql_updateq($table, $exp, $where='', $desc=array(), $serveur='')
{
	$f = sql_serveur('updateq', $serveur);
	return $f($table, $exp, $where, $desc, $serveur);
}

// http://doc.spip.org/@sql_delete
function sql_delete($table, $where='', $serveur='')
{
	$f = sql_serveur('delete', $serveur);
	return $f($table, $where, $serveur);
}

// http://doc.spip.org/@sql_replace
function sql_replace($table, $values, $desc=array(), $serveur='')
{
	$f = sql_serveur('replace', $serveur);
	return $f($table, $values, $desc, $serveur);
}

// http://doc.spip.org/@sql_showbase
function sql_showbase($spip=NULL, $serveur='')
{
	if ($spip == NULL){
		$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
		$spip = $connexion['prefixe'] . '%';
	}
	
	$f = sql_serveur('showbase', $serveur);
	return $f($spip, $serveur);
}

// http://doc.spip.org/@sql_showtable
function sql_showtable($table, $serveur='', $table_spip = false)
{
	if ($table_spip){
		$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
		$prefixe = $connexion['prefixe'];
		$table = preg_replace('/^spip/', $prefixe, $table);
	}
	
	$f = sql_serveur('showtable', $serveur);
	$f = $f($table, $serveur);
	if (!$f) return array();
	if (isset($GLOBALS['tables_principales'][$table]['join']))
		$f['join'] = $GLOBALS['tables_principales'][$table]['join'];
	elseif (isset($GLOBALS['tables_auxiliaires'][$table]['join']))
		$f['join'] = $GLOBALS['tables_auxiliaires'][$table]['join'];
	return $f;
}

// http://doc.spip.org/@sql_create
function sql_create($nom, $champs, $cles, $autoinc=false, $temporary=false, $serveur='') {
	$f = sql_serveur('create', $serveur);
	return $f($nom, $champs, $cles, $autoinc, $temporary, $serveur);
}

// http://doc.spip.org/@sql_multi
function sql_multi($sel, $lang, $serveur='')
{
  	$f = sql_serveur('multi', $serveur);
	return $f($sel, $lang);
}

// http://doc.spip.org/@sql_error
function sql_error($query='requete inconnue', $serveur='') {
  	$f = sql_serveur('error', $serveur);
	return $f($query);
}

// http://doc.spip.org/@sql_errno
function sql_errno($serveur='') {
  	$f = sql_serveur('errno', $serveur);
	return $f();
}

# une composition tellement frequente...
// http://doc.spip.org/@sql_fetsel
function sql_fetsel(
	$select = array(), $from = array(), $where = array(),
	$groupby = '', $orderby = array(), $limit = '',
	$sousrequete = '', $having = array(),
	$table = '', $id = '', $serveur='') {
	return sql_fetch(sql_select(
$select, $from, $where,	$groupby, $orderby, $limit,
$sousrequete, $having, $table, $id, $serveur),
				   $serveur);
}

# Retourne l'unique champ demande dans une requete Select a resultat unique
// http://doc.spip.org/@sql_getfetsel
function sql_getfetsel(
	$select, $from = array(), $where = array(),
	$groupby = '', $orderby = array(), $limit = '',
	$sousrequete = '', $having = array(),
	$table = '', $id = '', $serveur='') {
	$r = sql_fetch(sql_select(
$select, $from, $where,	$groupby, $orderby, $limit,
$sousrequete, $having, $table, $id, $serveur),
				   $serveur);
	return $r ? $r[$select] : NULL;
}

# une composition tellement frequente...
// http://doc.spip.org/@sql_countsel
function sql_countsel($from = array(), $where = array(),
	$groupby = '', $limit = '', $sousrequete = '', $having = array(),
	$serveur='') {
  	$f = sql_serveur('countsel', $serveur);
	return $f($from, $where, $groupby, $limit, $sousrequete, $having, $serveur);
}

//
// IN (...) est limite a 255 elements, d'ou cette fonction assistante
//
// http://doc.spip.org/@calcul_mysql_in
function calcul_mysql_in($val, $valeurs, $not='') {
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

// http://doc.spip.org/@sql_listdbs
function sql_listdbs($serveur='') {
  	$f = sql_serveur('listdbs', $serveur);
	return $f($serveur);
}

// http://doc.spip.org/@sql_version
function sql_version($serveur='') {
	$row = sql_fetsel("version() AS n", $serveur);
	return ($row['n']);
}

// http://doc.spip.org/@test_sql_int
function test_sql_int($type)
{
	return (strpos($type, 'bigint') === 0
	OR strpos($type, 'int') === 0
	OR strpos($type, 'tinyint') === 0);
}


// Cette fonction est vouee a disparaitre

// http://doc.spip.org/@description_table
function description_table($nom){

        global $tables_principales, $tables_auxiliaires;
	static $f;

        include_spip('base/serial');
        if (isset($tables_principales[$nom]))
                return $tables_principales[$nom];

        include_spip('base/auxiliaires');
        if (isset($tables_auxiliaires[$nom]))
                return $tables_auxiliaires[$nom];

	if (!$f) $f = charger_fonction('trouver_table', 'base');
	return $f($nom);
}

?>
