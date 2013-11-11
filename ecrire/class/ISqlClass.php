<?php

/* *************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Definition de l'API SQL
 * 
 * Ce fichier definit la couche d'abstraction entre SPIP et ses serveurs SQL.
 * Cette version 1 est un ensemble de fonctions ecrites rapidement
 * pour generaliser le code strictement MySQL de SPIP <= 1.9.2
 * Des retouches sont a prevoir apres l'experience des premiers portages.
 * Les symboles sql_* (constantes et nom de fonctions) sont reserves
 * a cette interface, sans quoi le gestionnaire de version dysfonctionnera.
 *
 * @package SPIP\SQL\API
 * @version 1
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

Interface ISql
{
	public function connect($host, $port, $login, $pass, $serveur='');
	
	function get_charset($charset, $serveur='');
	
	function set_charset($charset, $serveur='');
	
	function select($select = array(), $from = array(), $where = array(), $groupby = array(),
		$orderby = array(), $limit = '', $having = array(), $serveur='', $option=true);

	function countsel($from = array(), $where = array(), $groupby = array(),
		$having = array(), $serveur='', $option=true);

	function alter($q, $serveur='', $option=true);

	function fetch($res, $type=MYSQL_ASSOC);

	function seek($res, $row_number);

	function listdbs($serveur='');

	function selectdb($nom, $serveur='');

	function count($res);

	function free($res, $serveur='');

	function insert($table, $noms, $valeurs, $desc=array(), $serveur='', $option=true);

	function update($table, $exp, $where='', $desc=array(), $serveur='', $option=true);

	function delete($table, $where='', $serveur='', $option=true);

	function replace($table, $couples, $desc=array(), $serveur='', $option=true);

	function replace_multi($table, $tab_couples, $desc=array(), $serveur='', $option=true);

	function drop_table($table, $exist='', $serveur='', $option=true);

	function drop_view($table, $exist='', $serveur='', $option=true);

	function showbase($spip=NULL, $serveur='', $option=true);

	function showtable($table, $serveur='', $option=true);

	function create($nom, $champs, $cles=array(), $autoinc=false, $temporary=false, $serveur='', $option=true);

	function create_base($nom, $serveur='', $option=true);

	function create_view($nom, $select_query='', $serveur='', $option=true);

	function multi($sel, $lang, $serveur='', $option=true);

	function error($serveur='');

	function errno($serveur='');
	
	function explain($q, $serveur='', $option=true);

	function optimize($table, $serveur='', $option=true);

	function repair($table, $serveur='', $option=true);

	function query($ins, $serveur='', $option=true);

	function preferer_transaction($serveur='', $option=true);

	function demarrer_transaction($serveur='', $option=true);

	function terminer_transaction($serveur='', $option=true);

	function hex($val);

	function quote($val);

	function date_proche($champ, $interval, $unite);
	
	function cite($val, $type);
	
	function select_as($args);
	
	function in($val, $valeurs, $not='');
	
	public function test_int($type);
	
	public function test_date($type);

}

?>
