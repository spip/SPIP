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

//
// Changer les noms des tables ($table_prefix)
// Quand tous les appels SQL seront abstraits on pourra l'ameliorer

define('_SQL_PREFIXE_TABLE', '/([,\s])spip_/S');


Class Mysql implements ISql
{
	function connect($host, $port, $login, $pass, $serveur='')
	{	
		if ($port > 0) $host = "$host:$port";
		$link = @mysql_connect($host, $login, $pass, true);
		if (!is_resource($link)) {
			spip_log('Echec Mysql::connect. Erreur : ' . @mysql_error(), 'mysql.'._LOG_HS);
			return false;
		}
		return $link;
	}
	
	function get_charset($charset, $serveur='')
	{
		$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
		$connexion['last'] = $strQuery = "SHOW CHARACTER SET"
		. (!is_array($charset) ? '' : (" LIKE "._q($charset['charset'])));
		
		return $this->fetch($this->query($strQuery), NULL, $serveur);
	}
	
	function set_charset($charset, $serveur='')
	{
		$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
		$connexion['last'] = $strQuery = 'SET NAMES '._q($charset);
		
		spip_log("changement de charset sql : ".$strQuery, _LOG_DEBUG);
		
		return $this->query($strQuery);
	}
	
	function select($select = array(), $from = array(), $where = array(), $groupby = array(),
		$orderby = array(), $limit = '', $having = array(), $serveur='', $option=true)
	{
		$from = (!is_array($from) ? $from : $this->select_as($from));
		$strQuery = 
			  $this->calculer_expression('SELECT', $select, ', ')
			. $this->calculer_expression('FROM', $from, ', ')
			. $this->calculer_expression('WHERE', $where)
			. $this->calculer_expression('GROUP BY', $groupby, ',')
			. $this->calculer_expression('HAVING', $having)
			. $this->calculer_expression('ORDER BY', $orderby, ', ')
			. $this->calculer_expression('LIMIT', $limit, ', ');

		// renvoyer la requete inerte si demandee
		if (!$option) return $strQuery;
		$r = $this->query($strQuery, $serveur, $option);
		return $r;
	}
	
	function countsel($from = array(), $where = array(), $groupby = array(),
		$having = array(), $serveur='', $option=true)
	{
		$c = empty($groupby) ? '*' : ('DISTINCT ' . (!is_array($groupby) ? $groupby : join(',', $groupby)));

		$res = $this->select("COUNT($c)", $from, $where, '', '', '', $having, $serveur, $option);

		if (!$option) return $res;
		if (!is_resource($res)) return 0;
		$c = $this->fetch($res, MYSQL_NUM);
		$this->free($res);
		return array_shift($c);
	}

	function alter($query, $serveur='', $option=true)
	{
		// ici on supprime les ` entourant le nom de table pour permettre
		// la transposition du prefixe, compte tenu que les plugins ont la mauvaise habitude
		// d'utiliser ceux-ci, copie-colle de phpmyadmin
		$strQuery = preg_replace(",^TABLE\s*`([^`]*)`,i","TABLE \\1", $query);
		return $this->query("ALTER ".$strQuery, $serveur, $option); # i.e. que PG se debrouille
	}

	function fetch($res, $type=MYSQL_ASSOC)
	{
		if (empty($type)) $type = MYSQL_ASSOC;
		if ($res) return mysql_fetch_array($res, $type);
	}

	function seek($res, $row_number)
	{
		if ($res) return mysql_data_seek($res, $row_number);
		return false;
	}

	function listdbs($serveur='')
	{
		$dbs = array();
		if ($res = $this->query("SHOW DATABASES")) {
			while ($row = $this->fetch($res))
				$dbs[] = $row['Database'];
		}
		return $dbs;
	}

	function selectdb($nom, $serveur='')
	{
		$ok = mysql_select_db($nom);
		if (!$ok)
			spip_log('Echec Mysql::selectdb. Erreur : ' . $this->error(), 'mysql.'._LOG_CRITIQUE);
		return $ok;
	}

	function count($res)
	{
		if ($res) return mysql_num_rows($res);
		return false;
	}

	function free($res, $serveur='')
	{
		return (is_resource($res) ? @mysql_free_result($res) : false);
	}

	function insert($table, $champs, $valeurs, $desc=array(), $serveur='', $option=true)
	{
		$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
		$prefixe = $connexion['prefixe'];
		$link = $connexion['link'];
		$db = $connexion['db'];

		if ($prefixe) $table = preg_replace('/^spip/', $prefixe, $table);
		
		$query ="INSERT INTO $table $champs VALUES $valeurs";
		if (!$option) return $query;
		
		if (isset($_GET['var_profile'])) {
			include_spip('public/tracer');
			$t = trace_query_start();
		} else
			$t = 0;

		$connexion['last'] = $query;
		#spip_log($query, 'mysql.'._LOG_DEBUG);
		if (mysql_query($query, $link))
			$r = mysql_insert_id($link);
		else {
			if ($e = $this->errno($serveur))	// Log de l'erreur eventuelle
				$e .= $this->error($query, $serveur); // et du fautif
		}
		return $t ? trace_query_end($query, $t, $r, $e, $serveur) : $r;

		// return $r ? $r : (($r===0) ? -1 : 0); pb avec le multi-base.
	}
	
	// http://doc.spip.org/@sql_update
	public function update($table, $exp, $where='', $desc=array(), $serveur='', $option=true)
	{
		$set = array();
		foreach ($champs as $champ => $val)
			$set[] = $champ . "=$val";
		if (!empty($set))
			return $this->query(
				  $this->calculer_expression('UPDATE', $table, ',')
				. $this->calculer_expression('SET', $set, ',')
				. $this->calculer_expression('WHERE', $where), 
				$serveur, $option);
		return false;
	}
	
	// http://doc.spip.org/@sql_delete
	public function delete($table, $where='', $serveur='', $option=true)
	{
		$res = $this->query(
				  $this->calculer_expression('DELETE FROM', $table, ',')
				. $this->calculer_expression('WHERE', $where),
				$serveur, $option);
		if (!$option) return $res;
		if ($res) {
			$connexion = &$GLOBALS['connexions'][$serveur ? $serveur : 0];
			$link = $connexion['link'];
			return $link ? mysql_affected_rows($link) : mysql_affected_rows();
		}
		else
			return false;
	}

	function replace($table, $couples, $desc=array(), $serveur='', $option=true)
	{
		return $this->query("REPLACE $table (" . join(',',	array_keys($couples)) . ')
			VALUES (' . join(',', array_map('_q', $couples)) . ')',
			$serveur, $option);
	}
	
	function replace_multi($table, $tab_couples, $desc=array(), $serveur='', $option=true)
	{
		$cles = '(' . join(',',array_keys($tab_couples[0])). ')';
		$valeurs = array();
		foreach ($tab_couples as $couples) {
			$valeurs[] = '(' .join(',',array_map('_q', $couples)) . ')';
		}
		$valeurs = join(', ',$valeurs);
		return $this->query("REPLACE $table $cles VALUES $valeurs", $serveur, $option);
	}
	
	function drop_table($table, $exist='', $serveur='', $option=true)
	{
		if ($exist) $exist =" IF EXISTS";
		return $this->query("DROP TABLE $exist $table", $serveur, $option);
	}

	function drop_view($view, $exist='', $serveur='', $option=true)
	{
		if ($exist) $exist = 'IF EXISTS';
		return $this->query("DROP VIEW $exist $view", $serveur, $option);
	}

	function showbase($spip=NULL, $serveur='', $option=true)
	{
		return $this->query("SHOW TABLES LIKE " . _q($match), $serveur, $option);
	}

	function showtable($table, $serveur='', $option=true)
	{
		$s = $this->query("SHOW CREATE TABLE `$table`", $serveur, $option);
		if (!$s) return '';
		if (!$option) return $s;

		list(,$a) = mysql_fetch_array($s ,MYSQL_NUM);
		if (preg_match("/^[^(),]*\((([^()]*\([^()]*\)[^()]*)*)\)[^()]*$/", $a, $r)) {
			$desc = $r[1];
			// extraction d'une KEY éventuelle en prenant garde de ne pas
			// relever un champ dont le nom contient KEY (ex. ID_WHISKEY)
			if (preg_match("/^(.*?),([^,]*KEY[ (].*)$/s", $desc, $r)) {
			  $namedkeys = $r[2];
			  $desc = $r[1];
			}
			else 
			  $namedkeys = "";

			$fields = array();
			foreach (preg_split("/,\s*`/",$desc) as $v) {
			  preg_match("/^\s*`?([^`]*)`\s*(.*)/",$v,$r);
			  $fields[strtolower($r[1])] = $r[2];
			}
			$keys = array();

			foreach (preg_split('/\)\s*,?/',$namedkeys) as $v) {
			  if (preg_match("/^\s*([^(]*)\((.*)$/",$v,$r)) {
				$k = str_replace("`", '', trim($r[1]));
				$t = strtolower(str_replace("`", '', $r[2]));
				if ($k && !isset($keys[$k])) $keys[$k] = $t; else $keys[] = $t;
			  }
			}
			$this->free($s);
			return array('field' => $fields, 'key' => $keys);
		}

		$res = $this->query("SHOW COLUMNS FROM `$nom_table`", $serveur);
		if ($res) {
		  $nfields = array();
		  $nkeys = array();
		  while ($val = $this->fetch($res)) {
			$nfields[$val["Field"]] = $val['Type'];
			if ($val['Null']=='NO') {
			  $nfields[$val["Field"]] .= ' NOT NULL'; 
			}
			if ($val['Default'] === '0' || $val['Default']) {
			  if (preg_match('/[A-Z_]/',$val['Default'])) {
				$nfields[$val["Field"]] .= ' DEFAULT '.$val['Default'];		  
			  } else {
				$nfields[$val["Field"]] .= " DEFAULT '".$val['Default']."'";		  
			  }
			}
			if ($val['Extra'])
			  $nfields[$val["Field"]] .= ' '.$val['Extra'];
			if ($val['Key'] == 'PRI') {
			  $nkeys['PRIMARY KEY'] = $val["Field"];
			} else if($val['Key'] == 'MUL') {
			  $nkeys['KEY '.$val["Field"]] = $val["Field"];
			} else if($val['Key'] == 'UNI') {
			  $nkeys['UNIQUE KEY '.$val["Field"]] = $val["Field"];
			}
		  }
		  $this->free($res);
		  return array('field' => $nfields, 'key' => $nkeys);
		}
		return "";
	}

	function create($nom, $champs, $cles=array(), $autoinc=false, $temporary=false, $serveur='', $option=true)
	{
		$query = $keys = $s = $p = '';

		// certains plugins declarent les tables  (permet leur inclusion dans le dump)
		// sans les renseigner (laisse le compilo recuperer la description)
		if (!is_array($champs) || !is_array($cles)) 
			return;

		$res = $this->query("SELECT version() as v");
		if (($row = $this->fetch($res))
		AND (version_compare($row['v'],'5.0','>=')))
			$this->query("SET sql_mode=''");

		foreach ($cles as $k => $v) {
			$keys .= "$s\n\t\t$k ($v)";
			if ($k == "PRIMARY KEY")
				$p = $v;
			$s = ",";
		}
		$s = '';
		
		$character_set = "";
		if (@$GLOBALS['meta']['charset_sql_base'])
			$character_set .= " CHARACTER SET ".$GLOBALS['meta']['charset_sql_base'];
		if (@$GLOBALS['meta']['charset_collation_sql_base'])
			$character_set .= " COLLATE ".$GLOBALS['meta']['charset_collation_sql_base'];

		foreach ($champs as $k => $v) {
			$v = $this->remplacements_definitions_table($v);
			if (preg_match(',([a-z]*\s*(\(\s*[0-9]*\s*\))?(\s*binary)?),i',$v,$defs)) {
				if (preg_match(',(char|text),i',$defs[1])
				AND !preg_match(',(binary|CHARACTER|COLLATE),i',$v) )
				{
					$v = $defs[1] . $character_set . ' ' . substr($v,strlen($defs[1]));
				}
			}

			$query .= "$s\n\t\t$k $v"
				. (($autoinc && ($p == $k) && preg_match(',\b(big|small|medium)?int\b,i', $v))
					? " auto_increment"
					: ''
				);
			$s = ",";
		}
		$temporary = $temporary ? 'TEMPORARY':'';
		$q = "CREATE $temporary TABLE IF NOT EXISTS $nom ($query" . ($keys ? ",$keys" : '') . ")".
		($character_set?" DEFAULT $character_set":"")
		."\n";
		return $this->query($q, $serveur);
	}

	function create_base($nom, $serveur='', $option=true)
	{
		return $this->query("CREATE DATABASE `$nom`", $serveur, $option);
	}

	function create_view($nom, $select_query='', $serveur='', $option=true)
	{
		if (!$select_query) return false;
		// vue deja presente
		if (Sql::showtable($nom, false, $serveur)) {
			spip_log("Echec creation d'une vue sql ($nom) car celle-ci existe deja (serveur:$serveur)", _LOG_ERREUR);
			return false;
		}
		
		$query = "CREATE VIEW $nom AS ". $select_query;
		return $this->query($query, $serveur, $option);
	}

	function multi($sel, $lang, $serveur='', $option=true)
	{
		$lengthlang = strlen("[$lang]");
		$posmulti = 'INSTR('.$objet.', \'<multi>\')';
		$posfinmulti = "INSTR(".$objet.", '</multi>')";
		$debutchaine = "LEFT(".$objet.", $posmulti-1)";
		$finchaine = "RIGHT(".$objet.", CHAR_LENGTH(".$objet.") -(7+$posfinmulti))";
		$chainemulti = "TRIM(SUBSTRING(".$objet.", $posmulti+7, $posfinmulti -(7+$posmulti)))";
		$poslang = "INSTR($chainemulti,'[".$lang."]')";
		$poslang = "IF($poslang=0,INSTR($chainemulti,']')+1,$poslang+$lengthlang)";
		$chainelang = "TRIM(SUBSTRING(".$objet.", $posmulti+7+$poslang-1,$posfinmulti -($posmulti+7+$poslang-1) ))";
		$posfinlang = "INSTR(".$chainelang.", '[')";
		$chainelang = "IF($posfinlang>0,LEFT($chainelang,$posfinlang-1),$chainelang)";
		//$chainelang = "LEFT($chainelang,$posfinlang-1)";
		$retour = "(TRIM(IF($posmulti = 0 , ".
			"     TRIM(".$objet."), ".
			"     CONCAT( ".
			"          $debutchaine, ".
			"          IF( ".
			"               $poslang = 0, ".
			"                     $chainemulti, ".
			"               $chainelang".
			"          ), ". 
			"          $finchaine".
			"     ) ".
			"))) AS multi";

		return $retour;
	}

	function error($serveur='')
	{
		$link = $GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0]['link'];
		$s = $link ? mysql_error($link) : mysql_error();
		if ($s) spip_log("$s - $query", 'mysql.'._LOG_ERREUR);
		return $s;
	}

	function errno($serveur='')
	{
		$link = $GLOBALS['connexions'][$serveur ? $serveur : 0]['link'];
		$s = $link ? mysql_errno($link) : mysql_errno();
		// 2006 MySQL server has gone away
		// 2013 Lost connection to MySQL server during query
		if (in_array($s, array(2006,2013)))
			define('spip_interdire_cache', true);
		if ($s) spip_log("Erreur mysql $s", _LOG_ERREUR);
		return $s;
	}
	
	function explain($query, $serveur='', $option=true)
	{
		if (strpos(ltrim($query), 'SELECT') !== 0) return array();
		$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
		$prefixe = $connexion['prefixe'];
		$link = $connexion['link'];
		$db = $connexion['db'];

		$query = 'EXPLAIN ' . traite_query($query, $db, $prefixe);
		$r = $this->query($query, $serveur, $option);
		return $this->fetch($r, NULL, $serveur);
	}

	function optimize($table, $serveur='', $option=true)
	{
		return $this->query("OPTIMIZE TABLE ". $table, $serveur, $option);
	}

	function repair($table, $serveur='', $option=true)
	{
		return $this->query("REPAIR TABLE `$table`", $serveur, $option);
	}

	function query($ins, $serveur='', $option=true)
	{
		$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
		$prefixe = $connexion['prefixe'];
		$link = $connexion['link'];
		$db = $connexion['db'];

		$query = $this->traite_query($ins, $db, $prefixe);

		// renvoyer la requete inerte si demandee
		if (!$option) return $query;

		if (isset($_GET['var_profile'])) {
			include_spip('public/tracer');
			$t = trace_query_start();
		} else $t = 0 ;
	 
		$connexion['last'] = $query;

		// ajouter un debug utile dans log/mysql-slow.log ?
		$debug = '';
		if (defined('_DEBUG_SLOW_QUERIES') AND _DEBUG_SLOW_QUERIES) {
			if ($GLOBALS['debug']['aucasou']) {
				list(,$id,, $infos) = $GLOBALS['debug']['aucasou'];
				$debug .= " BOUCLE$id @ ".$infos[0] ." | ";
			}
			$debug .= " " . $_SERVER['REQUEST_URI'].' + '.$GLOBALS['ip'];
			$debug = ' /*'.str_replace('*/','@/',$debug).' */';
		}

		$r = $link ? mysql_query($query.$debug, $link) : mysql_query($query.$debug);

		if ($e = $this->errno($serveur))	// Log de l'erreur eventuelle
			$e .= $this->error($query, $serveur); // et du fautif
		return $t ? trace_query_end($query, $t, $r, $e, $serveur) : $r;
	}

	function preferer_transaction($serveur='', $option=true)
	{
	}

	function demarrer_transaction($serveur='', $option=true)
	{
	}

	function terminer_transaction($serveur='', $option=true)
	{
	}

	function hex($val)
	{
		return '0x' . $v;
	}

	function quote($val)
	{
		if ($type) {
			if (!is_array($v))
				return $this->cite($v,$type);
			// si c'est un tableau, le parcourir en propageant le type
			foreach ($v as $k=>$r)
				$v[$k] = $this->quote($r, $type);
			return $v;
		}
		// si on ne connait pas le type, s'en remettre a _q :
		// on ne fera pas mieux
		else
			return _q($v);
	}

	function date_proche($champ, $interval, $unite)
	{
		return '('
		. $champ
			. (($interval <= 0) ? '>' : '<')
			. (($interval <= 0) ? 'DATE_SUB' : 'DATE_ADD')
		. '('
		. $this->quote(date('Y-m-d H:i:s'))
		. ', INTERVAL '
		. (($interval > 0) ? $interval : (0-$interval))
		. ' '
		. $unite
		. '))';
	}

	// http://doc.spip.org/@Mysql::traite_query
	/**
	 * @param $query
	 * @param string $db
	 * @param string $prefixe
	 * @return array|null|string
	 */
	protected function traite_query($query, $db='', $prefixe='')
	{
		if (empty($query)) return '';
		
		if ($GLOBALS['mysql_rappel_nom_base'] AND $db)
			$pref = '`'. $db.'`.';
		else $pref = '';

		if ($prefixe)
			$pref .= $prefixe . "_";

		if (!preg_match('/\s(SET|VALUES|WHERE|DATABASE)\s/i', $query, $regs)) {
			$suite ='';
		} else {
			$suite = strstr($query, $regs[0]);
			$query = substr($query, 0, -strlen($suite));
			// propager le prefixe en cas de requete imbriquee
			// il faut alors echapper les chaine avant de le faire, pour ne pas risquer de
			// modifier une requete qui est en fait juste du texte dans un champ
			if (stripos($suite,"SELECT")!==false) {
				list($suite,$textes) = query_echappe_textes($suite);
				if (preg_match('/^(.*?)([(]\s*SELECT\b.*)$/si', $suite, $r))
				$suite = $r[1] . traite_query($r[2], $db, $prefixe);
				$suite = query_reinjecte_textes($suite, $textes);
			}
		}
		$r = preg_replace(_SQL_PREFIXE_TABLE, '\1'.$pref, $query) . $suite;

		#spip_log("traite_query: " . substr($r,0, 50) . ".... $db, $prefixe", _LOG_DEBUG);
		return $r;
	}
	
	/**
	 * Adapte pour Mysql la declaration SQL d'une colonne d'une table
	 *
	 * @param string $query
	 * 		Definition SQL d'un champ de table
	 * @return string
	 * 		Definition SQL adaptee pour MySQL d'un champ de table
	 */
	protected function remplacements_definitions_table($query)
	{
		// quelques remplacements
		$num = "(\s*\([0-9]*\))?";
		$enum = "(\s*\([^\)]*\))?";

		$remplace = array(
			'/VARCHAR(\s*[^\s\(])/is' => 'VARCHAR(255)\\1',
		);

		$query = preg_replace(array_keys($remplace), $remplace, $query);
		return $query;
	}
	
	// http://doc.spip.org/@Mysql::in
	/**
	 * @param $val
	 * @param $valeurs
	 * @param string $not
	 * @return string
	 */
	public function in($val, $valeurs, $not='')
	{
		$n = $i = 0;
		$in_sql = '';
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

	// http://doc.spip.org/@Mysql::cite
	/**
	 * @param $v
	 * @param $type
	 * @return int|string
	 */
	public function cite($v, $type)
	{
		if(is_null($v)
		AND stripos($type,"NOT NULL")===false)
			return 'NULL'; // null php se traduit en NULL SQL
		if ($this->test_date($type) AND preg_match('/^\w+\(/', $v))
			return $v;
		if ($this->test_int($type)) {
			if (is_numeric($v) OR (ctype_xdigit(substr($v,2))
				  AND $v[0]=='0' AND $v[1]=='x'))
				return $v;
			// si pas numerique, forcer le intval
			else
				return intval($v);
		}
		return  ("'" . addslashes($v) . "'");
	}

	
	// http://doc.spip.org/@calculer_where
	/**
	 * @param $v
	 * @return array|mixed|string
	 */
	protected function calculer_where($v)
	{
		if (!is_array($v))
		  return $v ;

		$op = array_shift($v);
		if (!($n=count($v)))
			return $op;
		else {
			$arg = $this->calculer_where(array_shift($v));
			if ($n==1) {
				  return "$op($arg)";
			} else {
				$arg2 = $this->calculer_where(array_shift($v));
				if ($n==2) {
					return "($arg $op $arg2)";
				} else return "($arg $op ($arg2) : $v[0])";
			}
		}
	}
	
	// http://doc.spip.org/@calculer_expression
	/**
	 * @param $expression
	 * @param $v
	 * @param string $join
	 * @return string
	 */
	protected function calculer_expression($expression, $v, $join = 'AND')
	{
		if (empty($v))
			return '';
		
		$exp = "\n$expression ";
		
		if (!is_array($v)) {
			return $exp . $v;
		} else {
			if (strtoupper($join) === 'AND')
				return $exp . join("\n\t$join ", array_map(array($this,'calculer_where'), $v));
			else
				return $exp . join($join, $v);
		}
	}
	
	// http://doc.spip.org/@select_as
	/**
	 * @param $args
	 * @return string
	 */
	public function select_as($args)
	{
		$res = '';
		foreach ($args as $k => $v) {
			if (substr($k,-1)=='@') {
				// c'est une jointure qui se refere au from precedent
				// pas de virgule
			  $res .= '  ' . $v ;
			}
			else {
			  if (!is_numeric($k)) {
				$p = strpos($v, " ");
				if ($p)
				  $v = substr($v,0,$p) . " AS `$k`" . substr($v,$p);
				else $v .= " AS `$k`";
			  }
				  
			  $res .= ', ' . $v ;
			}
		}
		return substr($res,2);
	}
	
	public function test_int($type)
	{
	  return preg_match('/^(TINYINT|SMALLINT|MEDIUMINT|INT|INTEGER|BIGINT)/i',trim($type));
	}

	public function test_date($type)
	{
	  return preg_match('/^(DATE|DATETIME|TIMESTAMP|TIME)/i',trim($type));
	}
}

?>
