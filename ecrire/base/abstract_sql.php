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

define('sql_ABSTRACT_VERSION', 1);

// Ce fichier definit la couche d'abstraction entre SPIP et ses serveurs SQL.
// Cette version 1 est un ensemble de fonctions ecrites rapidement
// pour generaliser le code strictement MySQL de SPIP < 1.9.3.
// Des retouches sont a prevoir apres l'experience des premiers portages.
// Les symboles sql_* (constantes et nom de fonctions) sont reserves
// a cette interface, sans quoi le gestionnaire de version dysfonctionnera.

// Fonction principale. Elle charge l'interface au serveur de base de donnees
// via la fonction spip_connect_version qui etablira la connexion au besoin.
// Elle retourne la fonction produisant la requête SQL demandee
// Erreur fatale si la fonctionnalite est absente sauf si le 3e arg <> false

// http://doc.spip.org/@sql_serveur
function sql_serveur($ins_sql='', $serveur='', $continue=false) {
	return spip_connect_sql(sql_ABSTRACT_VERSION, $ins_sql, $serveur, $continue);
}

// Demande si un charset est disponible. 
// http://doc.spip.org/@sql_get_charset
function sql_get_charset($charset, $serveur=''){
  // le nom http du charset differe parfois du nom SQL utf-8 ==> utf8 etc.
	$desc = sql_serveur('', $serveur, true);
	$desc = $desc[sql_ABSTRACT_VERSION];
	$c = $desc['charsets'][$charset];
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

// Fonction pour SELECT, retournant la ressource interrogeable par sql_fetch.
// Recoit en argument:
// - le tableau (ou chaîne) des champs a` ramener (Select)
// - le tableau (ou chaîne) des tables a` consulter (From)
// - le tableau (ou chaîne) des conditions a` remplir (Where)
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
	$groupby = '', $orderby = array(), $limit = '', $having = array(),
	$serveur='') {

	$f = sql_serveur('select', $serveur);

	return $f($select, $from, $where, $groupby, $orderby, $limit, $having, $serveur);
}

// http://doc.spip.org/@sql_countsel
function sql_countsel($from = array(), $where = array(),
	$groupby = '', $limit = '', $having = array(),
	$serveur='') {
  	$f = sql_serveur('countsel', $serveur);
	return $f($from, $where, $groupby, $limit, $having, $serveur);
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

// http://doc.spip.org/@sql_listdbs
function sql_listdbs($serveur='') {
  	$f = sql_serveur('listdbs', $serveur);
	return $f($serveur);
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

// Cette fonction ne garantit pas une portabilite totale
//  ===> lui preferer la suivante.
// Elle est fournie pour permettre l'actualisation de vieux codes 
// par un Sed brutal qui peut donner des resultats provisoirement acceptables
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

// http://doc.spip.org/@sql_drop_table
function sql_drop_table($table, $exist='', $serveur='')
{
	$f = sql_serveur('drop_table', $serveur);
	return $f($table, $exist, $serveur);
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
function sql_showtable($table, $table_spip = false, $serveur='')
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
function sql_create($nom, $champs, $cles=array(), $autoinc=false, $temporary=false, $serveur='') {
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

// http://doc.spip.org/@sql_explain
function sql_explain($q, $serveur='') {
	$f = sql_serveur('explain', $serveur, true);
	return @function_exists($f) ? $f($q, $serveur) : false;
}

// http://doc.spip.org/@sql_optimize
function sql_optimize($q, $serveur='') {
	$f = sql_serveur('optimize', $serveur, true);
	return @function_exists($f) ? $f($q, $serveur) : false;
}

// http://doc.spip.org/@sql_repair
function sql_repair($q, $serveur='') {
	$f = sql_serveur('repair', $serveur, true);
	return @function_exists($f) ? $f($q, $serveur) : false;
}

// Fonction la plus generale ... et la moins portable
// A n'utiliser qu'en derniere extremite

// http://doc.spip.org/@sql_query
function sql_query($ins, $serveur='') {
  	$f = sql_serveur('query', $serveur);
	return $f($ins);
}

# une composition tellement frequente...
// http://doc.spip.org/@sql_fetsel
function sql_fetsel(
	$select = array(), $from = array(), $where = array(),
	$groupby = '', $orderby = array(), $limit = '',
	$having = array(), $serveur='') {
	return sql_fetch(sql_select($select, $from, $where,	$groupby, $orderby, $limit, $having, $serveur), $serveur);
}

# Retourne l'unique champ demande dans une requete Select a resultat unique
// http://doc.spip.org/@sql_getfetsel
function sql_getfetsel(
	$select, $from = array(), $where = array(), $groupby = '', 
	$orderby = array(), $limit = '', $having = array(), $serveur='') {
	$r = sql_fetch(sql_select($select, $from, $where,	$groupby, $orderby, $limit, $having, $serveur), $serveur);
	return $r ? $r[$select] : NULL;
}

// http://doc.spip.org/@sql_version
function sql_version($serveur='') {
	$row = sql_fetsel("version() AS n", '','','','','','',$serveur);
	return ($row['n']);
}

// prend une chaine sur l'aphabet hexa
// et retourne sa representation numerique:
// FF ==> 0xFF en MySQL mais x'FF' en PG
// http://doc.spip.org/@sql_hex
function sql_hex($val, $serveur='')
{
	$f = sql_serveur('hex', $serveur);
	return $f($val);
}

function sql_quote($val, $serveur='')
{
	$f = sql_serveur('quote', $serveur);
	return $f($val);
}

// http://doc.spip.org/@sql_in
function sql_in($val, $valeurs, $not='', $serveur='') {
	$f = sql_serveur('in', $serveur);
	return $f($val, $valeurs, $not, $serveur);
}


// http://doc.spip.org/@sql_test_int
function sql_test_int($type, $serveur='')
{
  return (preg_match('/^bigint/i',$type)
	  OR preg_match('/^int/i',$type)
	  OR preg_match('/^tinyint/i',$type));
}

// http://doc.spip.org/@sql_test_date
function sql_test_date($type, $serveur='')
{
  return (preg_match('/^datetime/i',$type)
	  OR preg_match('/^timestamp/i',$type));
}

// Cette fonction devrait disparaitre

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
