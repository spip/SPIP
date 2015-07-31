<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

define('sql_ABSTRACT_VERSION', 1);
include_spip('base/connect_sql');

// Ce fichier definit la couche d'abstraction entre SPIP et ses serveurs SQL.
// Cette version 1 est un ensemble de fonctions ecrites rapidement
// pour generaliser le code strictement MySQL de SPIP <= 1.9.2
// Des retouches sont a prevoir apres l'experience des premiers portages.
// Les symboles sql_* (constantes et nom de fonctions) sont reserves
// a cette interface, sans quoi le gestionnaire de version dysfonctionnera.

// Fonction principale. Elle charge l'interface au serveur de base de donnees
// via la fonction spip_connect_version qui etablira la connexion au besoin.
// Elle retourne la fonction produisant la requete SQL demandee
// Erreur fatale si la fonctionnalite est absente sauf si le 3e arg <> false

// http://doc.spip.org/@sql_serveur
function sql_serveur($ins_sql='', $serveur='', $continue=false) {
	return spip_connect_sql(sql_ABSTRACT_VERSION, $ins_sql, $serveur, $continue);
}

// Demande si un charset est disponible. 
// http://doc.spip.org/@sql_get_charset
function sql_get_charset($charset, $serveur='', $option=true){
  // le nom http du charset differe parfois du nom SQL utf-8 ==> utf8 etc.
	$desc = sql_serveur('', $serveur, true,true);
	$desc = $desc[sql_ABSTRACT_VERSION];
	$c = $desc['charsets'][$charset];
	if ($c) {
		if (function_exists($f=@$desc['get_charset'])) 
			if ($f($c, $serveur, $option!==false)) return $c;
	}
	spip_log("SPIP ne connait pas les Charsets disponibles sur le serveur $serveur. Le serveur choisira seul.");
	return false;
}

// Regler le codage de connexion

// http://doc.spip.org/@sql_set_charset
function sql_set_charset($charset,$serveur='', $option=true){
	$f = sql_serveur('set_charset', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	return $f($charset, $serveur, $option!==false);
}

// Fonction pour SELECT, retournant la ressource interrogeable par sql_fetch.
// Recoit en argument:
// - le tableau (ou cha�ne) des champs a` ramener (Select)
// - le tableau (ou cha�ne) des tables a` consulter (From)
// - le tableau (ou cha�ne) des conditions a` remplir (Where)
// - le crite`re de regroupement (Group by)
// - le tableau de classement (Order By)
// - le crite`re de limite (Limit)
// - le tableau des des post-conditions a remplir (Having)
// - le serveur sollicite (pour retrouver la connexion)
// - option peut avoir 3 valeurs : 
//	false -> ne pas l'executer mais la retourner, 
//	continue -> ne pas echouer en cas de serveur sql indisponible,
//	autre -> executer la requete.
// Le cas "autre" est, pour une requete produite par le compilateur,
// un tableau donnnant le contexte afin d'indiquer le lieu de l'erreur au besoin
// Retourne false en cas d'erreur, apres l'avoir denoncee.
// Les portages doivent retourner la requete elle-meme en cas d'erreur,
// afin de disposer du texte brut.

// http://doc.spip.org/@sql_select
function sql_select ($select = array(), $from = array(), $where = array(),
	$groupby = array(), $orderby = array(), $limit = '', $having = array(),
	$serveur='', $option=true) {
	$f = sql_serveur('select', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;

	$debug = (isset($GLOBALS['var_mode']) AND $GLOBALS['var_mode'] == 'debug' );
	if (($option !== false) AND !$debug) {
		$res = $f($select, $from, $where, $groupby, $orderby, $limit, $having, $serveur, is_array($option) ? true : $option);
	} else {
		$query = $f($select, $from, $where, $groupby, $orderby, $limit, $having, $serveur, false);
		if (!$option) return $query;
		// le debug, c'est pour ce qui a ete produit par le compilateur
		if (isset($GLOBALS['debug']['aucasou'])) {
			list($table, $id,) = $GLOBALS['debug']['aucasou'];
			$nom = $GLOBALS['debug_objets']['courant'] . $id;
			$GLOBALS['debug_objets']['requete'][$nom] = $query;
		}
		$res = $f($select, $from, $where, $groupby, $orderby, $limit, $having, $serveur, true);
	}

	if (!is_string($res)) return $res;
	// denoncer l'erreur SQL dans sa version brute
	spip_sql_erreur($serveur);
	// idem dans sa version squelette (prefixe des tables non substitue)
	erreur_squelette(array(sql_errno($serveur), sql_error($serveur), $res), $option);
	return false;
}

// Recupere la syntaxe de la requete select sans l'executer
// simplement $option = false au lieu de true
// http://doc.spip.org/@sql_get_select
function sql_get_select($select = array(), $from = array(), $where = array(),
	$groupby = array(), $orderby = array(), $limit = '', $having = array(),
	$serveur='') {
	return sql_select ($select, $from, $where, $groupby, $orderby, $limit, $having, $serveur, false);
}

// Comme ci-dessus, mais ramene seulement et tout de suite le nombre de lignes
// Pas de colonne ni de tri a donner donc, et l'argument LIMIT est trompeur
// http://doc.spip.org/@sql_countsel
function sql_countsel($from = array(), $where = array(),
		      $groupby = array(), $having = array(),
	$serveur='', $option=true) {
	$f = sql_serveur('countsel', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($from, $where, $groupby, $having, $serveur, $option!==false);
	if ($r===false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_alter
function sql_alter($q, $serveur='', $option=true) {
	$f = sql_serveur('alter', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($q, $serveur, $option!==false);
	if ($r===false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_fetch
function sql_fetch($res, $serveur='', $option=true) {
	$f = sql_serveur('fetch', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	return $f($res, NULL, $serveur, $option!==false);
}

function sql_fetch_all($res, $serveur='', $option=true){
	$rows = array();
	if (!$res) return $rows;
	$f = sql_serveur('fetch', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return array();
	while ($r = $f($res, NULL, $serveur, $option!==false))
		$rows[] = $r;
	sql_free($res, $serveur);
	return $rows;
}

function sql_seek($res, $row_number, $serveur='', $option=true) {
	$f = sql_serveur('seek', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($res, $row_number, $serveur, $option!==false);
	if ($r===false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_listdbs
function sql_listdbs($serveur='', $option=true) {
	$f = sql_serveur('listdbs', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($serveur);
	if ($r===false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_selectdb
function sql_selectdb($nom, $serveur='', $option=true)
{
	$f = sql_serveur('selectdb', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($nom, $serveur, $option!==false);
	if ($r===false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_count
function sql_count($res, $serveur='', $option=true)
{
	$f = sql_serveur('count', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($res, $serveur, $option!==false);
	if ($r===false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_free
function sql_free($res, $serveur='', $option=true)
{
	$f = sql_serveur('free', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	return $f($res);
}

// Cette fonction ne garantit pas une portabilite totale
//  ===> lui preferer la suivante.
// Elle est fournie pour permettre l'actualisation de vieux codes 
// par un Sed brutal qui peut donner des resultats provisoirement acceptables
// http://doc.spip.org/@sql_insert
function sql_insert($table, $noms, $valeurs, $desc=array(), $serveur='', $option=true)
{
	$f = sql_serveur('insert', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($table, $noms, $valeurs, $desc, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_insertq
function sql_insertq($table, $couples=array(), $desc=array(), $serveur='', $option=true)
{
	$f = sql_serveur('insertq', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($table, $couples, $desc, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_insertq_multi
function sql_insertq_multi($table, $couples=array(), $desc=array(), $serveur='', $option=true)
{
	$f = sql_serveur('insertq_multi', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($table, $couples, $desc, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_update
function sql_update($table, $exp, $where='', $desc=array(), $serveur='', $option=true)
{
	$f = sql_serveur('update', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($table, $exp, $where, $desc, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// Update est presque toujours appelee sur des constantes ou des dates
// Cette fonction est donc plus utile que la precedente,d'autant qu'elle
// permet de gerer les differences de representation des constantes.
// http://doc.spip.org/@sql_updateq
function sql_updateq($table, $exp, $where='', $desc=array(), $serveur='', $option=true)
{
	$f = sql_serveur('updateq', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($table, $exp, $where, $desc, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_delete
function sql_delete($table, $where='', $serveur='', $option=true)
{
	$f = sql_serveur('delete', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($table, $where, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_replace
function sql_replace($table, $couples, $desc=array(), $serveur='', $option=true)
{
	$f = sql_serveur('replace', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($table, $couples, $desc, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}


// http://doc.spip.org/@sql_replace_multi
function sql_replace_multi($table, $tab_couples, $desc=array(), $serveur='', $option=true)
{
	$f = sql_serveur('replace_multi', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($table, $tab_couples, $desc, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_drop_table
function sql_drop_table($table, $exist='', $serveur='', $option=true)
{
	$f = sql_serveur('drop_table', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($table, $exist, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// supprimer une vue sql
// http://doc.spip.org/@sql_drop_view
function sql_drop_view($table, $exist='', $serveur='', $option=true)
{
	$f = sql_serveur('drop_view', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($table, $exist, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_showbase
function sql_showbase($spip=NULL, $serveur='', $option=true)
{
	if ($spip == NULL){
		$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
		$spip = $connexion['prefixe'] . '\_%';
	}
	
	$f = sql_serveur('showbase', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	return $f($spip, $serveur, $option!==false);
}

function sql_alltable($spip=NULL, $serveur='', $option=true)
{
	$q = sql_showbase($spip, $serveur, $option);
	$r = array();
	if ($q) while ($t = sql_fetch($q, $serveur)) { $r[] = array_shift($t);}
	return $r;
}

// http://doc.spip.org/@sql_showtable
function sql_showtable($table, $table_spip = false, $serveur='', $option=true)
{
	if ($table_spip){
		$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
		$prefixe = $connexion['prefixe'];
		$vraie_table = preg_replace('/^spip/', $prefixe, $table);
	} else $vraie_table = $table;
	
	$f = sql_serveur('showtable', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$f = $f($vraie_table, $serveur, $option!==false);
	if (!$f) return array();
	if (isset($GLOBALS['tables_principales'][$table]['join']))
		$f['join'] = $GLOBALS['tables_principales'][$table]['join'];
	elseif (isset($GLOBALS['tables_auxiliaires'][$table]['join']))
		$f['join'] = $GLOBALS['tables_auxiliaires'][$table]['join'];
	return $f;
}

// http://doc.spip.org/@sql_create
function sql_create($nom, $champs, $cles=array(), $autoinc=false, $temporary=false, $serveur='', $option=true) {
	$f = sql_serveur('create', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($nom, $champs, $cles, $autoinc, $temporary, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

function sql_create_base($nom, $serveur='', $option=true)
{
	$f = sql_serveur('create_base', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($nom, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// Fonction pour creer une vue 
// nom : nom de la vue,
// select_query : une requete select, idealement cree avec $req = sql_select()
// (en mettant $option du sql_select a false pour recuperer la requete)
// http://doc.spip.org/@sql_create_view
function sql_create_view($nom, $select_query, $serveur='', $option=true) {
	$f = sql_serveur('create_view', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($nom, $select_query, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_multi
function sql_multi($sel, $lang, $serveur='', $option=true)
{
	$f = sql_serveur('multi', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	return $f($sel, $lang);
}

// http://doc.spip.org/@sql_error
function sql_error($serveur='') {
	$f = sql_serveur('error', $serveur, 'continue');
	if (!is_string($f) OR !$f) return false;
	return $f('query inconnue', $serveur, $option!==false);
}

// http://doc.spip.org/@sql_errno
function sql_errno($serveur='') {
	$f = sql_serveur('errno', $serveur, 'continue');
	if (!is_string($f) OR !$f) return false;
	return $f($serveur);
}

// http://doc.spip.org/@sql_explain
function sql_explain($q, $serveur='', $option=true) {
	$f = sql_serveur('explain', $serveur,  'continue');
	if (!is_string($f) OR !$f) return false;
	$r = $f($q, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_optimize
function sql_optimize($table, $serveur='', $option=true) {
	$f = sql_serveur('optimize', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($table, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// http://doc.spip.org/@sql_repair
function sql_repair($table, $serveur='', $option=true) {
	$f = sql_serveur('repair', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($table, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

// Fonction la plus generale ... et la moins portable
// A n'utiliser qu'en derniere extremite

// http://doc.spip.org/@sql_query
function sql_query($ins, $serveur='', $option=true) {
	$f = sql_serveur('query', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	$r = $f($ins, $serveur, $option!==false);
	if ($r === false) spip_sql_erreur($serveur);
	return $r;
}

# une composition tellement frequente...
// http://doc.spip.org/@sql_fetsel
function sql_fetsel($select = array(), $from = array(), $where = array(),
	$groupby = array(), $orderby = array(), $limit = '',
	$having = array(), $serveur='', $option=true) {
	$q = sql_select($select, $from, $where,	$groupby, $orderby, $limit, $having, $serveur, $option);
	if ($option===false) return $q;
	if (!$q) return array();
	$r = sql_fetch($q, $serveur, $option);
	sql_free($q, $serveur, $option);
	return $r;
}

// Retourne le tableau de toutes les lignes d'une requete Select
// http://doc.spip.org/@sql_allfetsel
function sql_allfetsel($select = array(), $from = array(), $where = array(),
	$groupby = array(), $orderby = array(), $limit = '',
	$having = array(), $serveur='', $option=true) {
	$q = sql_select($select, $from, $where,	$groupby, $orderby, $limit, $having, $serveur, $option);
	if ($option===false) return $q;
	return sql_fetch_all($q, $serveur, $option);
}

# Retourne l'unique champ demande dans une requete Select a resultat unique
// http://doc.spip.org/@sql_getfetsel
function sql_getfetsel($select, $from = array(), $where = array(), $groupby = array(), 
	$orderby = array(), $limit = '', $having = array(), $serveur='', $option=true) {
	if (preg_match('/\s+as\s+(\w+)$/i', $select, $c)) $id = $c[1];
	elseif (!preg_match('/\W/', $select)) $id = $select;
	else {$id = 'n'; $select .= ' AS n';}
	$r = sql_fetsel($select, $from, $where,	$groupby, $orderby, $limit, $having, $serveur, $option);
	if ($option===false) return $r;
	if (!$r) return NULL;
	return $r[$id]; 
}

// http://doc.spip.org/@sql_version
function sql_version($serveur='', $option=true) {
	$row = sql_fetsel("version() AS n", '','','','','','',$serveur);
	return ($row['n']);
}

// prend une chaine sur l'aphabet hexa
// et retourne sa representation numerique:
// FF ==> 0xFF en MySQL mais x'FF' en PG
// http://doc.spip.org/@sql_hex
function sql_hex($val, $serveur='', $option=true)
{
	$f = sql_serveur('hex', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	return $f($val);
}

function sql_quote($val, $serveur='', $type='')
{
	$f = sql_serveur('quote', $serveur, true);
	if (!is_string($f) OR !$f) $f = '_q';
	return $f($val, $type);
}

function sql_date_proche($champ, $interval, $unite, $serveur='', $option=true)
{
	$f = sql_serveur('date_proche', $serveur, true);
	if (!is_string($f) OR !$f) return false;
	return $f($champ, $interval, $unite);
}

function sql_in($val, $valeurs, $not='', $serveur='', $option=true) {
	if (is_array($valeurs)) {
		$f = sql_serveur('quote', $serveur, true);
		if (!is_string($f) OR !$f) return false;
		$valeurs = join(',', array_map($f, array_unique($valeurs)));
	} elseif ($valeurs[0]===',') $valeurs = substr($valeurs,1);
	if (!strlen(trim($valeurs))) return ($not ? "0=0" : '0=1');

	$f = sql_serveur('in', $serveur,  $option==='continue' OR $option===false);
	if (!is_string($f) OR !$f) return false;
	return $f($val, $valeurs, $not, $serveur, $option!==false);
}

// Penser a dire dans la description du serveur 
// s'il accepte les requetes imbriquees afin d'optimiser ca

// http://doc.spip.org/@sql_in_select
function sql_in_select($in, $select, $from = array(), $where = array(),
		    $groupby = array(), $orderby = array(), $limit = '', $having = array(), $serveur='')
{
	$liste = array(); 
	$res = sql_select($select, $from, $where, $groupby, $orderby, $limit, $having, $serveur); 
	while ($r = sql_fetch($res)) {$liste[] = array_shift($r);}
	sql_free($res);
	return sql_in($in, $liste);
}


// http://doc.spip.org/@sql_test_int
function sql_test_int($type, $serveur='', $option=true)
{
  return (preg_match('/^bigint/i',$type)
	  OR preg_match('/^int/i',$type)
	  OR preg_match('/^tinyint/i',$type));
}

// http://doc.spip.org/@sql_test_date
function sql_test_date($type, $serveur='', $option=true)
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


if(!function_exists("ctype_xdigit")){
    function ctype_xdigit($string = ""){
        return !strlen(  trim( $string,  "1234567890abcdefABCDEF" )  );
    }/* endfunction ctype_xdigit */
}/* endif not function_exists ctype_xdigit */

?>
