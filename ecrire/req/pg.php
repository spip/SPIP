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

define('_DEFAULT_DB', 'spip');

// Se connecte et retourne le nom de la fonction a connexion persistante
// A la premiere connexion de l'installation (BD pas precisee)
// si on ne peut se connecter sans la preciser
// on reessaye avec le login comme nom de BD
// et si ca marche toujours pas, avec "spip" (constante ci-dessus)
// si ca ne marche toujours pas, echec.

// http://doc.spip.org/@req_pg_dist
function req_pg_dist($addr, $port, $login, $pass, $db='', $prefixe='') {
	static $last_connect = array();
	if (!charger_php_extension('pgsql')) return false;
	
	// si provient de selectdb
	if (empty($addr) && empty($port) && empty($login) && empty($pass)){
		foreach (array('addr','port','login','pass','prefixe') as $a){
			$$a = $last_connect[$a];
		}
	}
	@list($host, $p) = explode(';', $addr);
	if ($p >0) $port = " port=$p" ; else $port = '';
	if ($db) {
		@$link = pg_connect("host=$host$port dbname=$db user=$login password=$pass", PGSQL_CONNECT_FORCE_NEW);
	} elseif (!@$link = pg_connect("host=$host$port user=$login password=$pass", PGSQL_CONNECT_FORCE_NEW)) {
	    if (@$link = pg_connect("host=$host$port dbname=$login user=$login password=$pass", PGSQL_CONNECT_FORCE_NEW)) {
	      $db = $login;
	    } else {
	      $db = _DEFAULT_DB;
	      $link = pg_connect("host=$host$port dbname=$db user=$login password=$pass", PGSQL_CONNECT_FORCE_NEW);
	    }
	}

	if ($link)
		$last_connect = array (
			'addr' => $addr,
			'port' => $port,
			'login' => $login,
			'pass' => $pass,
			'db' => $db,
			'prefixe' => $prefixe,
		);
		
#	spip_log("Connexion vers $host, base $db, prefixe $prefixe "
#		 . ($link ? 'operationnelle' : 'impossible'));

	return !$link ? false : array(
		'db' => $db,
		'prefixe' => $prefixe ? $prefixe : $db,
		'link' => $link,
		);
}

$GLOBALS['spip_pg_functions_1'] = array(
		'alter' => 'spip_pg_alter',
		'count' => 'spip_pg_count',
		'countsel' => 'spip_pg_countsel',
		'create' => 'spip_pg_create',
		'create_base' => 'spip_pg_create_base',
		'create_view' => 'spip_pg_create_view',
		'date_proche' => 'spip_pg_date_proche',
		'delete' => 'spip_pg_delete',
		'drop_table' => 'spip_pg_drop_table',
		'drop_view' => 'spip_pg_drop_view',
		'errno' => 'spip_pg_errno',
		'error' => 'spip_pg_error',
		'explain' => 'spip_pg_explain',
		'fetch' => 'spip_pg_fetch',
		'seek' => 'spip_pg_seek',
		'free' => 'spip_pg_free',
		'hex' => 'spip_pg_hex',
		'in' => 'spip_pg_in',
		'insert' => 'spip_pg_insert',
		'insertq' => 'spip_pg_insertq',
		'insertq_multi' => 'spip_pg_insertq_multi',
		'listdbs' => 'spip_pg_listdbs',
		'multi' => 'spip_pg_multi',
		'optimize' => 'spip_pg_optimize',
		'query' => 'spip_pg_query',
		'quote' => 'spip_pg_quote',
		'replace' => 'spip_pg_replace',
		'replace_multi' => 'spip_pg_replace_multi',
		'select' => 'spip_pg_select',
		'selectdb' => 'spip_pg_selectdb',
		'set_connect_charset' => 'spip_pg_set_connect_charset',
		'showbase' => 'spip_pg_showbase',
		'showtable' => 'spip_pg_showtable',
		'update' => 'spip_pg_update',
		'updateq' => 'spip_pg_updateq',
		);

// Par ou ca passe une fois les traductions faites
// http://doc.spip.org/@spip_pg_trace_query
function spip_pg_trace_query($query, $serveur='')
{
	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	if (isset($_GET['var_profile'])) {
		include_spip('public/tracer');
		$t = trace_query_start();
	} else $t = 0 ;
 
	$connexion['last'] = $query;
	$r = spip_pg_query_simple($link, $query);

	if ($e = spip_pg_errno($serveur))	// Log de l'erreur eventuelle
		$e .= spip_pg_error($query, $serveur); // et du fautif
	return $t ? trace_query_end($query, $t, $r, $e, $serveur) : $r;
}

// Fonction de requete generale quand on est sur que c'est SQL standard.
// Elle change juste le noms des tables ($table_prefix) dans le FROM etc

// http://doc.spip.org/@spip_pg_query
function spip_pg_query($query, $serveur='',$requeter=true)
{
	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	if (preg_match('/\s(SET|VALUES|WHERE|DATABASE)\s/i', $query, $regs)) {
		$suite = strstr($query, $regs[0]);
		$query = substr($query, 0, -strlen($suite));
	} else $suite ='';
	$query = preg_replace('/([,\s])spip_/', '\1'.$prefixe.'_', $query) . $suite;

	// renvoyer la requete inerte si demandee
	if (!$requeter) return $query;

	return spip_pg_trace_query($query, $serveur);
}

function spip_pg_query_simple($link, $query){
	#spip_log(var_export($query,true), 'pg_queries');
	return pg_query($link, $query);
}

/*
 * Retrouver les champs 'timestamp'
 * pour les ajouter aux 'insert' ou 'replace'
 * afin de simuler le fonctionnement de mysql 
 * 
 * stocke le resultat pour ne pas faire 
 * de requetes showtable intempestives
 */
function spip_pg_ajouter_champs_timestamp($table, $couples, $desc='', $serveur=''){
	static $tables = array();
	
	if (!isset($tables[$table])){
		
		if (!$desc){
			$f = charger_fonction('trouver_table', 'base');
			$desc = $f($table, $serveur);
			// si pas de description, on ne fait rien, ou on die() ?
			if (!$desc) return $couples;
		}
		
		// recherche des champs avec simplement 'TIMESTAMP'
		// cependant, il faudra peut etre etendre
		// avec la gestion de DEFAULT et ON UPDATE
		// mais ceux-ci ne sont pas utilises dans le core
		$tables[$table] = array();
		foreach ($desc['field'] as $k=>$v){
			if (strpos(strtolower(ltrim($v)), 'timestamp')===0)
			$tables[$table][] = $k;
		}
	}
	
	// ajout des champs type 'timestamp' absents
	foreach ($tables[$table] as $maj){
		if (!array_key_exists($maj, $couples))
			$couples[$maj] = "NOW()";	
	}
	return $couples;
}
 	
	
// Alter en PG ne traite pas les index
// http://doc.spip.org/@spip_pg_alter
function spip_pg_alter($query, $serveur='',$requeter=true) {
	// il faudrait une regexp pour eviter de spliter ADD PRIMARY KEY (colA, colB)
	// tout en cassant en deux alter distincts "ADD PRIMARY KEY (colA, colB), ADD INDEX (chose)"... 
	// ou revoir l'api de sql_alter en creant un 
	// sql_alter_table($table,array($actions));
	if (!preg_match("/\s*((\s*IGNORE)?\s*TABLE\s*([^\s]*))\s*(.*)?/is", $query, $regs)){
		spip_log("$query mal comprise", 'pg');	
		return false;
	}
	$debut = $regs[1];
	$table = $regs[3];
	$suite = $regs[4];
	$todo = explode(',', $suite);
	// on remet les morceaux dechires ensembles... que c'est laid !
	$todo2 = array(); $i=0;
	$ouverte = false;
	while ($do = array_shift($todo)) {
		$todo2[$i] = isset($todo2[$i]) ? $todo2[$i] . "," . $do : $do;
		$o=(false!==strpos($do,"("));
		$f=(false!==strpos($do,")"));
		if ($o AND !$f) $ouverte=true;
		elseif ($f) $ouverte=false;
		if (!$ouverte) $i++;
	}
	$todo=$todo2;
	$query = $debut.' '.array_shift($todo);

	if (!preg_match('/^\s*(IGNORE\s*)?TABLE\s+(\w+)\s+(ADD|DROP|CHANGE|MODIFY|RENAME)\s*(.*)$/is', $query, $r)) {
	  spip_log("$query incompris", 'pg');
	} else {
	  if ($r[1]) spip_log("j'ignore IGNORE dans $query", 'pg');
	  $f = 'spip_pg_alter_' . strtolower($r[3]);
	  if (function_exists($f))
	    $f($r[2], $r[4], $serveur, $requeter);
	  else spip_log("$query non prevu", 'pg');
	}
	// Alter a plusieurs args. Faudrait optimiser.
	if ($todo)
	  spip_pg_alter("TABLE $table " . join(',',$todo));

}
	      
// http://doc.spip.org/@spip_pg_alter_change
function spip_pg_alter_change($table, $arg, $serveur='',$requeter=true)
{
	if (!preg_match('/^`?(\w+)`?\s+`?(\w+)`?\s+(.*?)\s*(DEFAULT .*?)?(NOT\s+NULL)?\s*(DEFAULT .*?)?$/i',$arg, $r)) {
	  spip_log("alter change: $arg  incompris", 'pg');
	} else {
	  list(,$old, $new, $type, $default, $null, $def2) = $r;
	  $actions = array("ALTER $old TYPE " . mysql2pg_type($type));
	  if ($null)
	    $actions[]= "ALTER $old SET NOT NULL";
	  else
	    $actions[]= "ALTER $old DROP NOT NULL";

	  if ($d = ($default ? $default : $def2))
	    $actions[]= "ALTER $old SET $d";
	  else
	    $actions[]= "ALTER $old DROP DEFAULT";

	  spip_pg_query("ALTER TABLE $table " . join(', ', $actions));

	  if ($old != $new)
	    spip_pg_query("ALTER TABLE $table RENAME $old TO $new", $serveur);
	}
}

// http://doc.spip.org/@spip_pg_alter_add
function spip_pg_alter_add($table, $arg, $serveur='',$requeter=true) {
	if (!preg_match('/^(COLUMN|INDEX|KEY|PRIMARY\s+KEY|)\s*(.*)$/', $arg, $r)) {
		spip_log("alter add $arg  incompris", 'pg');
		return NULL;
	}
	if (!$r[1] OR $r[1]=='COLUMN') {
		preg_match('/`?(\w+)`?(.*)/',$r[2], $m);
		if (preg_match('/^(.*)(BEFORE|AFTER|FIRST)(.*)$/is', $m[2], $n)) {
			$m[2]=$n[1];
		}
		return spip_pg_query("ALTER TABLE $table ADD " . $m[1] . ' ' . mysql2pg_type($m[2]),  $serveur, $requeter);
	} elseif ($r[1][0] == 'P') {
		// la primary peut etre sur plusieurs champs
		$r[2] = trim(str_replace('`','',$r[2]));
		$m = ($r[2][0]=='(') ? substr($r[2],1,-1) : $r[2];
		return spip_pg_query("ALTER TABLE $table ADD CONSTRAINT $table" .'_pkey PRIMARY KEY (' . $m . ')', $serveur, $requeter);
	} else {
		preg_match('/([^\s,]*)\s*(.*)?/',$r[2], $m);
		// peut etre "(colonne)" ou "nom_index (colonnes)"
		// bug potentiel si qqn met "(colonne, colonne)"
		//
		// nom_index (colonnes)
		if ($m[2]) {
			$colonnes = substr($m[2],1,-1);
			$nom_index = $m[1];
		}
		else {
			// (colonne)
			if ($m[1][0] == "(") {
				$colonnes = substr($m[1],1,-1);
				if (false!==strpos(",",$colonnes)) {
					spip_log("PG : Erreur, impossible de creer un index sur plusieurs colonnes"
						." sans qu'il ait de nom ($table, ($colonnes))", 'pg');	
					break;
				} else {
					$nom_index = $colonnes;
				}
			}
			// nom_index
			else {
				$nom_index = $colonnes = $m[1];
			}
		}
		return spip_pg_create_index($nom_index, $table, $colonnes, $serveur, $requeter);
	}
}

// http://doc.spip.org/@spip_pg_alter_drop
function spip_pg_alter_drop($table, $arg, $serveur='',$requeter=true) {
	if (!preg_match('/^(COLUMN|INDEX|KEY|PRIMARY\s+KEY|)\s*`?(\w*)`?/', $arg, $r))
	  spip_log("alter drop: $arg  incompris", 'pg');
	else {
	    if (!$r[1] OR $r[1]=='COLUMN')
	      return spip_pg_query("ALTER TABLE $table DROP " . $r[2],  $serveur);
	    elseif ($r[1][0] == 'P')
	      return spip_pg_query("ALTER TABLE $table DROP CONSTRAINT $table" . '_pkey', $serveur);
	    else {
		return spip_pg_query("DROP INDEX " . $table . '_' . $r[2],  $serveur);
	    }
	}
}

function spip_pg_alter_modify($table, $arg, $serveur='',$requeter=true) {
	if (!preg_match('/^`?(\w+)`?\s+(.*)$/',$arg, $r)) {
		spip_log("alter modify: $arg  incompris", 'pg');
	} else {
		return spip_pg_alter_change($table, $r[1].' '.$arg, $serveur='',$requeter=true);
	}
}

// attention (en pg) : 
// - alter table A rename to X = changer le nom de la table
// - alter table A rename X to Y = changer le nom de la colonne X en Y 
// pour l'instant, traiter simplement RENAME TO X
function spip_pg_alter_rename($table, $arg, $serveur='',$requeter=true) {
	$rename="";
	// si TO, mais pas au debut
	if (!stripos($arg,'TO ')){
		$rename=$arg;
	}
	elseif (preg_match('/^(TO)\s*`?(\w*)`?/', $arg, $r)) {
		$rename=$r[2];
	} else {
		spip_log("alter rename: $arg  incompris", 'pg');
	}
	return $rename?spip_pg_query("ALTER TABLE $table RENAME TO $rename"):false;
}


/**
 * Fonction de creation d'un INDEX
 * 
 * @param string $nom : nom de l'index
 * @param string $table : table sql de l'index
 * @param string/array $champs : liste de champs sur lesquels s'applique l'index
 * @param string $serveur : nom de la connexion sql utilisee
 * @param bool $requeter : true pour executer la requete ou false pour retourner le texte de la requete
 * 
 * @return bool ou requete
 */
function spip_pg_create_index($nom, $table, $champs, $serveur='', $requeter=true) {
	if (!($nom OR $table OR $champs)) {
		spip_log("Champ manquant pour creer un index pg ($nom, $table, (".@join(',',$champs)."))","pg");
		return false;
	}
	
	$nom = str_replace("`","",$nom);
	$champs = str_replace("`","",$champs);
	
	// PG ne differentie pas noms des index en fonction des tables
	// il faut donc creer des noms uniques d'index pour une base pg
	$nom = $table.'_'.$nom;
	// enlever d'eventuelles parentheses deja presentes sur champs
	if (!is_array($champs)){
		 if ($champs[0]=="(") $champs = substr($champs,1,-1);
		 $champs = array($champs);
	}
	$query = "CREATE INDEX $nom ON $table (" . join(',',$champs) . ")";
	if (!$requeter) return $query;
	$res = spip_pg_query($query, $serveur, $requeter);
	return $res;
}


// http://doc.spip.org/@spip_pg_explain
function spip_pg_explain($query, $serveur='',$requeter=true){
	if (strpos(ltrim($query), 'SELECT') !== 0) return array();
	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	if (preg_match('/\s(SET|VALUES|WHERE)\s/i', $query, $regs)) {
		$suite = strstr($query, $regs[0]);
		$query = substr($query, 0, -strlen($suite));
	} else $suite ='';
	$query = 'EXPLAIN ' . preg_replace('/([,\s])spip_/', '\1'.$prefixe.'_', $query) . $suite;

	if (!$requeter) return $query;
	$r = spip_pg_query_simple($link,$query);
	return spip_pg_fetch($r, NULL, $serveur);
}

// http://doc.spip.org/@spip_pg_selectdb
function spip_pg_selectdb($db, $serveur='',$requeter=true) {
	// se connecter a la base indiquee
	// avec les identifiants connus
	$index = $serveur ? strtolower($serveur) : 0;

	if ($link = spip_connect_db('', '', '', '', $db, 'pg', '', '')){
		if (($db==$link['db']) && $GLOBALS['connexions'][$index] = $link)
			return $db;					
	} else
		return false;
}

// Qu'une seule base pour le moment

// http://doc.spip.org/@spip_pg_listdbs
function spip_pg_listdbs($serveur) {
	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
	$link = $connexion['link'];
	return spip_pg_query_simple($link, "select * From pg_database");
}

// http://doc.spip.org/@spip_pg_select
function spip_pg_select($select, $from, $where='',
			$groupby=array(), $orderby='', $limit='',
                           $having='', $serveur='',$requeter=true){

	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	$limit = preg_match("/^\s*(([0-9]+),)?\s*([0-9]+)\s*$/", $limit,$limatch);
	if ($limit) {
		$offset = $limatch[2];
		$count = $limatch[3];
	}

	$select = spip_pg_frommysql($select);

	// si pas de tri explicitement demande, le GROUP BY ne
	// contient que la clef primaire.
	// lui ajouter alors le champ de tri par defaut
	if (preg_match("/FIELD\(([a-z]+\.[a-z]+),/i", $orderby[0], $groupbyplus)) {
		$groupby[] = $groupbyplus[1];
	}

	$orderby = spip_pg_orderby($orderby, $select);

	if ($having) {
	  if (is_array($having))
	    $having = join("\n\tAND ", array_map('calculer_pg_where', $having));
	}
	$from =  spip_pg_from($from, $prefixe);
	$query =  "SELECT ". $select
	  . (!$from ? '' : "\nFROM $from")
	  . (!$where ? '' : ("\nWHERE " . (!is_array($where) ? calculer_pg_where($where) : (join("\n\tAND ", array_map('calculer_pg_where', $where))))))
	  . spip_pg_groupby($groupby, $from, $select)
	  . (!$having ? '' : "\nHAVING $having")
	  . ($orderby ? ("\nORDER BY $orderby") :'')
	  . (!$limit ? '' : (" LIMIT $count" . (!$offset ? '' : " OFFSET $offset")));

	// renvoyer la requete inerte si demandee
	if ($requeter === false) return $query;
	
	$r = spip_pg_trace_query($query, $serveur);
	return $r ? $r : $query;;
}

// Le traitement des prefixes de table dans un Select se limite au FROM
// car le reste de la requete utilise les alias (AS) systematiquement

// http://doc.spip.org/@spip_pg_from
function spip_pg_from($from, $prefixe)
{
	if (is_array($from)) $from = spip_pg_select_as($from);
	return !$prefixe ? $from : preg_replace('/(\b)spip_/','\1'.$prefixe.'_', $from);
}

// http://doc.spip.org/@spip_pg_orderby
function spip_pg_orderby($order, $select)
{
	$res = array();
	$arg = (is_array($order) ?  $order : preg_split('/\s*,\s*/',$order));

	foreach($arg as $v) {
		if (preg_match('/(case\s+.*?else\s+0\s+end)\s*AS\s+' . $v .'/', $select, $m)) {

		  $res[] = $m[1];
		} else $res[]=$v;
	}
	return spip_pg_frommysql(join(',',$res));
}

// Conversion a l'arrach' des jointures MySQL en jointures PG
// A refaire pour tirer parti des possibilites de PG et de MySQL5
// et pour enlever les repetitions (sans incidence de perf, mais ca fait sale)

// http://doc.spip.org/@spip_pg_groupby
function spip_pg_groupby($groupby, $from, $select)
{
	$join = strpos($from, ",");
	if ($join OR $groupby) $join = !is_array($select) ? $select : join(", ", $select);
	if ($join) {
	  $join = str_replace('DISTINCT ','',$join);
	  // fct SQL sur colonne et constante apostrophee ==> la colonne
	  $join = preg_replace('/\w+\(\s*([^(),\']*),\s*\'[^\']*\'[^)]*\)/','\\1', $join);
	  $join = preg_replace('/CAST\(\s*([^(),\' ]*\s+)as\s*\w+\)/','\\1', $join);
	  // resultat d'agregat ne sont pas a mettre dans le groupby
	  $join = preg_replace('/(SUM|COUNT|MAX|MIN|UPPER)\([^)]+\)(\s*AS\s+\w+)\s*,?/i','', $join);
	  // idem sans AS (fetch numerique)
	  $join = preg_replace('/(SUM|COUNT|MAX|MIN|UPPER)\([^)]+\)\s*,?/i','', $join);
	  // ne reste plus que les vrais colonnes, et parfois 1 virgule

	  if (preg_match('/^(.*),\s*$/',$join,$m)) $join=$m[1];
	}
	if (is_array($groupby)) $groupby = join(',',$groupby);
	if ($join) $groupby = $groupby ? "$groupby, $join" : $join;
	if (!$groupby) return '';

	$groupby = spip_pg_frommysql($groupby);
	$groupby = preg_replace('/\s+AS\s+\w+\s*/i','', $groupby);

	return "\nGROUP BY $groupby"; 
}

// Conversion des operateurs MySQL en PG
// IMPORTANT: "0+X" est vu comme conversion numerique du debut de X 
// Les expressions de date ne sont pas gerees au-dela de 3 ()
// Le 'as' du 'CAST' est en minuscule pour echapper au dernier preg_replace
// de spip_pg_groupby.
// A ameliorer.

// http://doc.spip.org/@spip_pg_frommysql
function spip_pg_frommysql($arg)
{
	if (is_array($arg)) $arg = join(", ", $arg);

	$res = spip_pg_fromfield($arg);

	$res = preg_replace('/\brand[(][)]/i','random()', $res);

	$res = preg_replace('/\b0\.0[+]([a-zA-Z0-9_.]+)\s*/',
			    'CAST(substring(\1, \'^ *[0-9.]+\') as float)',
			    $res);
	$res = preg_replace('/\b0[+]([a-zA-Z0-9_.]+)\s*/',
			    'CAST(substring(\1, \'^ *[0-9]+\') as int)',
			    $res);
	$res = preg_replace('/\bconv[(]([^,]*)[^)]*[)]/i',
			    'CAST(substring(\1, \'^ *[0-9]+\') as int)',
			    $res);

	$res = preg_replace('/UNIX_TIMESTAMP\s*[(]\s*[)]/',
			    ' EXTRACT(epoch FROM NOW())', $res);
	
	// la fonction md5(integer) n'est pas connu en pg
	// il faut donc forcer les types en text (cas de md5(id_article))
	$res = preg_replace('/md5\s*[(]([^)]*)[)]/i',
			    'MD5(CAST(\1 AS text))', $res);

	$res = preg_replace('/UNIX_TIMESTAMP\s*[(]([^)]*)[)]/',
			    ' EXTRACT(epoch FROM \1)', $res);

	$res = preg_replace('/\bDAYOFMONTH\s*[(]([^()]*([(][^()]*[)][^()]*)*[^)]*)[)]/',
			    ' EXTRACT(day FROM \1)',
			    $res);

	$res = preg_replace('/\bMONTH\s*[(]([^()]*([(][^)]*[)][^()]*)*[^)]*)[)]/',
			    ' EXTRACT(month FROM \1)',
			    $res);

	$res = preg_replace('/\bYEAR\s*[(]([^()]*([(][^)]*[)][^()]*)*[^)]*)[)]/',
			    ' EXTRACT(year FROM \1)',
			    $res);

	$res = preg_replace('/TO_DAYS\s*[(]([^()]*([(][^)]*[)][()]*)*)[)]/',
			    ' EXTRACT(day FROM \1 - \'0001-01-01\')',
			    $res);

	$res = preg_replace("/(EXTRACT[(][^ ]* FROM *)\"([^\"]*)\"/", '\1\'\2\'', $res);

	$res = preg_replace('/DATE_FORMAT\s*[(]([^,]*),\s*\'%Y%m%d\'[)]/', 'to_char(\1, \'YYYYMMDD\')', $res);

	$res = preg_replace('/DATE_FORMAT\s*[(]([^,]*),\s*\'%Y%m\'[)]/', 'to_char(\1, \'YYYYMM\')', $res);

	$res = preg_replace('/DATE_SUB\s*[(]([^,]*),/', '(\1 -', $res);
	$res = preg_replace('/DATE_ADD\s*[(]([^,]*),/', '(\1 +', $res);
	$res = preg_replace('/INTERVAL\s+(\d+\s+\w+)/', 'INTERVAL \'\1\'', $res);
	$res = preg_replace('/([+<>-]=?)\s*(\'\d+-\d+-\d+\s+\d+:\d+(:\d+)\')/', '\1 timestamp \2', $res);
	$res = preg_replace('/(\'\d+-\d+-\d+\s+\d+:\d+:\d+\')\s*([+<>-]=?)/', 'timestamp \1 \2', $res);

	$res = preg_replace('/([+<>-]=?)\s*(\'\d+-\d+-\d+\')/', '\1 timestamp \2', $res);
	$res = preg_replace('/(\'\d+-\d+-\d+\')\s*([+<>-]=?)/', 'timestamp \1 \2', $res);

	$res = preg_replace('/(timestamp .\d+)-00-/','\1-01-', $res);
	$res = preg_replace('/(timestamp .\d+-\d+)-00/','\1-01',$res);
# correct en theorie mais produit des debordements arithmetiques
#	$res = preg_replace("/(EXTRACT[(][^ ]* FROM *)(timestamp *'[^']*' *[+-] *timestamp *'[^']*') *[)]/", '\2', $res);
	$res = preg_replace("/(EXTRACT[(][^ ]* FROM *)('[^']*')/", '\1 timestamp \2', $res);
	$res = preg_replace("/\sLIKE\s+/", ' ILIKE ', $res);
	return str_replace('REGEXP', '~', $res);
}

// http://doc.spip.org/@spip_pg_fromfield
function spip_pg_fromfield($arg)
{
	while(preg_match('/^(.*?)FIELD\s*\(([^,]*)((,[^,)]*)*)\)/', $arg, $m)) {

		preg_match_all('/,([^,]*)/', $m[3], $r, PREG_PATTERN_ORDER);
		$res = '';
		$n=0;
		$index = $m[2];
		foreach($r[1] as $v) {
			$n++;
			$res .= "\nwhen $index=$v then $n";
		}
		$arg = $m[1] . "case $res else 0 end "
		  . substr($arg,strlen($m[0]));
	}
	return $arg;
}

// http://doc.spip.org/@calculer_pg_where
function calculer_pg_where($v)
{
	if (!is_array($v))
		return spip_pg_frommysql($v);

	$op = str_replace('REGEXP', '~', array_shift($v));
	if (!($n=count($v)))
		return $op;
	else {
		$arg = calculer_pg_where(array_shift($v));
		if ($n==1) {
			  return "$op($arg)";
		} else {
			$arg2 = calculer_pg_where(array_shift($v));
			if ($n==2) {
				return "($arg $op $arg2)";
			} else return "($arg $op ($arg2) : $v[0])";
		}
	}
}


// http://doc.spip.org/@calculer_pg_expression
function calculer_pg_expression($expression, $v, $join = 'AND'){
	if (empty($v))
		return '';
	
	$exp = "\n$expression ";
	
	if (!is_array($v)) $v = array($v);
	
	if (strtoupper($join) === 'AND')
			return $exp . join("\n\t$join ", array_map('calculer_pg_where', $v));
		else
			return $exp . join($join, $v);
}

// http://doc.spip.org/@spip_pg_select_as
function spip_pg_select_as($args)
{
	$argsas = "";
	foreach($args as $k => $v) {
		if (substr($k,-1)=='@') {
			// c'est une jointure qui se refere au from precedent
			// pas de virgule
		  $argsas .= '  ' . $v ;
		}
		else {
			$as = '';
			//  spip_log("$k : $v");
			if (!is_numeric($k)) {
				if (preg_match('/\.(.*)$/', $k, $r))
					$v = $k;
				elseif ($v != $k) {
					$p = strpos($v, " ");
					if ($p)
					  $v = substr($v,0,$p) . " AS $k" . substr($v,$p);
					else  $as = " AS $k"; 
				}
			}
			// spip_log("subs $k : $v avec $as");
			// if (strpos($v, 'JOIN') === false)  $argsas .= ', ';
			$argsas .= ', '. $v . $as; 
		}
	}
	return substr($argsas,2) . $join;
}

// http://doc.spip.org/@spip_pg_fetch
function spip_pg_fetch($res, $t='', $serveur='',$requeter=true) {

	if ($res) $res = pg_fetch_array($res, NULL, PGSQL_ASSOC);
	return $res;
}

function spip_pg_seek($r, $row_number, $serveur='',$requeter=true) {
	if ($r) return pg_result_seek($r,$row_number);
}


// http://doc.spip.org/@spip_pg_countsel
function spip_pg_countsel($from = array(), $where = array(), $groupby=array(),
			  $having = array(), $serveur='',$requeter=true) 
{
	$c = !$groupby ? '*' : ('DISTINCT ' . (is_string($groupby) ? $groupby : join(',', $groupby)));
	$r = spip_pg_select("COUNT($c)", $from, $where,'', '', '', $having, $serveur, $requeter);
	if (!$requeter) return $r;
	if (!is_resource($r)) return 0;
	list($c) = pg_fetch_array($r, NULL, PGSQL_NUM);
	return $c;
}

// http://doc.spip.org/@spip_pg_count
function spip_pg_count($res, $serveur='',$requeter=true) {
	return !$res ? 0 : pg_numrows($res);
}
  
// http://doc.spip.org/@spip_pg_free
function spip_pg_free($res, $serveur='',$requeter=true) {
  // rien a faire en postgres
}

// http://doc.spip.org/@spip_pg_delete
function spip_pg_delete($table, $where='', $serveur='',$requeter=true) {

	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];
	if ($prefixe) $table = preg_replace('/^spip/', $prefixe, $table);
	
	$query = calculer_pg_expression('DELETE FROM', $table, ',')
			. calculer_pg_expression('WHERE', $where, 'AND');
			
	// renvoyer la requete inerte si demandee
	if (!$requeter) return $query;
	
	$res = spip_pg_trace_query($query, $serveur);
	if ($res)
		return pg_affected_rows($res);
	else
		return false;
}

// http://doc.spip.org/@spip_pg_insert
function spip_pg_insert($table, $champs, $valeurs, $desc=array(), $serveur='',$requeter=true) {
	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	if (!$desc) $desc = description_table($table);
	$seq = spip_pg_sequence($table);

	if ($prefixe) {
		$table = preg_replace('/^spip/', $prefixe, $table);
		$seq = preg_replace('/^spip/', $prefixe, $seq);
	}
	$ret = !$seq ? '' : (" RETURNING currval('$seq')");
	$ins = (strlen($champs)<3)
	  ? " DEFAULT VALUES"
	  : "$champs VALUES $valeurs";
	  
	$q ="INSERT INTO $table $ins $ret";
	if (!$requeter) return $q;
	$connexion['last'] = $q;
	
	
	$r = spip_pg_query_simple($link, $q);
#	spip_log($q);
	if ($r) {
		if (!$ret) return 0;
		if ($r2 = pg_fetch_array($r, NULL, PGSQL_NUM))
			return $r2[0];
	}
	return false;
}

// http://doc.spip.org/@spip_pg_insertq
function spip_pg_insertq($table, $couples=array(), $desc=array(), $serveur='',$requeter=true) {

	if (!$desc) $desc = description_table($table);
	if (!$desc) die("$table insertion sans description");
	$fields =  $desc['field'];
	
	foreach ($couples as $champ => $val) {
		$couples[$champ]=  spip_pg_cite($val, $fields[$champ]);
	}

	// recherche de champs 'timestamp' pour mise a jour auto de ceux-ci
	$couples = spip_pg_ajouter_champs_timestamp($table, $couples, $desc, $serveur);
	
	return spip_pg_insert($table, "(".join(',',array_keys($couples)).")", "(".join(',', $couples).")", $desc, $serveur, $requeter);
}



// http://doc.spip.org/@spip_pg_insertq_multi
function spip_pg_insertq_multi($table, $tab_couples=array(), $desc=array(), $serveur='',$requeter=true) {

	if (!$desc) $desc = description_table($table);
	if (!$desc) die("$table insertion sans description");
	$fields =  isset($desc['field'])?$desc['field']:array();
	
	// recherche de champs 'timestamp' pour mise a jour auto de ceux-ci
	// une premiere fois pour ajouter maj dans les cles
	$les_cles = spip_pg_ajouter_champs_timestamp($table, $tab_couples[0], $desc, $serveur);
	
	$cles = "(" . join(',',array_keys($les_cles)). ')';
	$valeurs = array();
	foreach ($tab_couples as $couples) {
		foreach ($couples as $champ => $val){
			$couples[$champ]= spip_pg_cite($val, $fields[$champ]);
		}
		// recherche de champs 'timestamp' pour mise a jour auto de ceux-ci
		$couples = spip_pg_ajouter_champs_timestamp($table, $couples, $desc, $serveur);
		
		$valeurs[] = '(' .join(',', $couples) . ')';
	}
	$valeurs = implode(', ',$valeurs);
	
	return	spip_pg_insert($table, $cles, $valeurs, $desc, $serveur, $requeter);
}


// http://doc.spip.org/@spip_pg_update
function spip_pg_update($table, $couples, $where='', $desc='', $serveur='',$requeter=true) {

	if (!$couples) return;
	$connexion = $GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];
	if ($prefixe) $table = preg_replace('/^spip/', $prefixe, $table);

	// recherche de champs 'timestamp' pour mise a jour auto de ceux-ci
	$couples = spip_pg_ajouter_champs_timestamp($table, $couples, $desc, $serveur);

	$set = array();
	foreach ($couples as $champ => $val) {
		$set[] = $champ . '=' . $val; 
	}

	$query = calculer_pg_expression('UPDATE', $table, ',')
		. calculer_pg_expression('SET', $set, ',')
		. calculer_pg_expression('WHERE', $where, 'AND');
		
	// renvoyer la requete inerte si demandee
	if (!$requeter) return $query;
	
	return spip_pg_trace_query($query, $serveur);
}

// idem, mais les valeurs sont des constantes a mettre entre apostrophes
// sauf les expressions de date lorsqu'il s'agit de fonctions SQL (NOW etc)
// http://doc.spip.org/@spip_pg_updateq
function spip_pg_updateq($table, $couples, $where='', $desc=array(), $serveur='',$requeter=true) {
	if (!$couples) return;
	if (!$desc) $desc = description_table($table);
	$fields = $desc['field'];
	foreach ($couples as $k => $val) {
		$couples[$k] = spip_pg_cite($val, $fields[$k]);
	}

	return spip_pg_update($table, $couples, $where, $desc, $serveur, $requeter);
}


// http://doc.spip.org/@spip_pg_replace
function spip_pg_replace($table, $values, $desc, $serveur='',$requeter=true) {
	if (!$values) {spip_log("replace vide $table"); return 0;}
	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	if (!$desc) $desc = description_table($table);
	if (!$desc) die("$table insertion sans description");
	$prim = $desc['key']['PRIMARY KEY'];
	$ids = preg_split('/,\s*/', $prim);
	$noprims = $prims = array();
	foreach($values as $k=>$v) {
		$values[$k] = $v = spip_pg_cite($v, $desc['field'][$k]);

		if (!in_array($k, $ids))
			$noprims[$k]= "$k=$v";
		else $prims[$k]= "$k=$v";
	}

	// recherche de champs 'timestamp' pour mise a jour auto de ceux-ci
	$values = spip_pg_ajouter_champs_timestamp($table, $values, $desc, $serveur);
	
	$where = join(' AND ', $prims);
	if (!$where) {
		return spip_pg_insert($table, "(".join(',',array_keys($values)).")", "(".join(',', $values).")", $desc, $serveur);
	}
	$couples = join(',', $noprims);

	$seq = spip_pg_sequence($table);
	if ($prefixe) {
		$table = preg_replace('/^spip/', $prefixe, $table);
		$seq = preg_replace('/^spip/', $prefixe, $seq);
	}

	$connexion['last'] = $q = "UPDATE $table SET $couples WHERE $where";
	if ($couples) {
	  $couples = spip_pg_query_simple($link, $q);
#	  spip_log($q);
	  if (!$couples) return false;
	  $couples = pg_affected_rows($couples);
	}
	if (!$couples) {
		$ret = !$seq ? '' :
		  (" RETURNING nextval('$seq') < $prim");
		$connexion['last'] = $q = "INSERT INTO $table (" . join(',',array_keys($values)) . ') VALUES (' .join(',', $values) . ")$ret";
		$couples = spip_pg_query_simple($link, $q);
	    if (!$couples) {
		return false;
	    } elseif ($ret) {
	      $r = pg_fetch_array($couples, NULL, PGSQL_NUM);
	      if ($r[0]) {
		$connexion['last'] = $q = "SELECT setval('$seq', $prim) from $table";
		// Le code de SPIP met parfois la sequence a 0 (dans l'import)
		// MySQL n'en dit rien, on fait pareil pour PG
		$r = @pg_query($link, $q);
	      }
	    }
	}

	return $couples;
}


// http://doc.spip.org/@spip_pg_replace_multi
function spip_pg_replace_multi($table, $tab_couples, $desc=array(), $serveur='',$requeter=true) {
	// boucler pour traiter chaque requete independemment
	foreach ($tab_couples as $couples){
		$retour = spip_pg_replace($table, $couples, $desc, $serveur,$requeter);
	}
	// renvoie le dernier id 
	return $retour; 
}



// Donne la sequence eventuelle associee a une table 
// Pas extensible pour le moment,

// http://doc.spip.org/@spip_pg_sequence
function spip_pg_sequence($table)
{
	global $tables_principales;
	include_spip('base/serial');
	if (!isset($tables_principales[$table])) return false;
	$desc = $tables_principales[$table];
	$prim = @$desc['key']['PRIMARY KEY'];
	if (!preg_match('/^\w+$/', $prim)
	OR strpos($desc['field'][$prim], 'int') === false)
		return '';
	else  {	return $table . '_' . $prim . "_seq";}
}

// Explicite les conversions de Mysql d'une valeur $v de type $t
// Dans le cas d'un champ date, pas d'apostrophe, c'est une syntaxe ad hoc

// http://doc.spip.org/@spip_pg_cite
function spip_pg_cite($v, $t)
{
	if (sql_test_date($t)) {
		if (strpos("0123456789", $v[0]) === false)
			return spip_pg_frommysql($v);
		else {
			if (strpos($v, "-00-00") <= 4) {
				$annee = substr($v,0,4);
				if (!intval($annee)) $annee = '0001';
				$v = $annee ."-01-01".substr($v,10);
			}
			return "timestamp '$v'";
		}
	}
	elseif (!sql_test_int($t))
		return   ("'" . addslashes($v) . "'");
	elseif (is_numeric($v) OR (strpos($v, 'CAST(') === 0))
		return $v;
	elseif ($v[0]== '0' AND $v[1]!=='x' AND  ctype_xdigit(substr($v,1)))
		return  substr($v,1);
	else {
		spip_log("Warning: '$v'  n'est pas de type $t", 'pg');
		return intval($v);
	}
}

// http://doc.spip.org/@spip_pg_hex
function spip_pg_hex($v)
{
	return "CAST(x'" . $v . "' as bigint)";
}

function spip_pg_quote($v, $type='')
{
	return ($type === 'int' AND !$v) ? '0' :  _q($v);
}

function spip_pg_date_proche($champ, $interval, $unite)
{
	return '('
	. $champ
        . (($interval <= 0) ? '>' : '<')
        . (($interval <= 0) ? 'DATE_SUB' : 'DATE_ADD')
	. '('
	. sql_quote(date('Y-m-d H:i:s'))
	. ', INTERVAL '
	. (($interval > 0) ? $interval : (0-$interval))
	. ' '
	. $unite
	. '))';
}

// http://doc.spip.org/@spip_pg_in
function spip_pg_in($val, $valeurs, $not='', $serveur) {
//
// IN (...) souvent limite a 255  elements, d'ou cette fonction assistante
//
	if (strpos($valeurs, "CAST(x'") !== false)
		return "($val=" . join("OR $val=", explode(',',$valeurs)).')';
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

// http://doc.spip.org/@spip_pg_error
function spip_pg_error($query='', $serveur, $requeter=true) {
	$link = $GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0]['link'];
	$s = $link ? pg_last_error($link) : pg_last_error();
	if ($s) {
		$s = str_replace('ERROR', 'errcode: 1000 ', $s);
		spip_log("$s - $query", 'pg.'._LOG_ERREUR);
	}
	return $s;
}

// http://doc.spip.org/@spip_pg_errno
function spip_pg_errno($serveur='') {
  // il faudrait avoir la derniere ressource retournee et utiliser
  // http://fr2.php.net/manual/fr/function.pg-result-error.php
	return 0;
}

// http://doc.spip.org/@spip_pg_drop_table
function spip_pg_drop_table($table, $exist='', $serveur='',$requeter=true)
{
	if ($exist) $exist =" IF EXISTS";
	if (spip_pg_query("DROP TABLE$exist $table", $serveur, $requeter))
		return true;
	else return false;
}

// supprime une vue 
// http://doc.spip.org/@spip_pg_drop_view
function spip_pg_drop_view($view, $exist='', $serveur='',$requeter=true) {
	if ($exist) $exist =" IF EXISTS";
	return spip_pg_query("DROP VIEW$exist $view", $serveur, $requeter);
}

// http://doc.spip.org/@spip_pg_showbase
function spip_pg_showbase($match, $serveur='',$requeter=true)
{
	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
	$link = $connexion['link'];
	$connexion['last'] = $q = "SELECT tablename FROM pg_tables WHERE tablename ILIKE "._q($match);
	return spip_pg_query_simple($link, $q);
}

// http://doc.spip.org/@spip_pg_showtable
function spip_pg_showtable($nom_table, $serveur='',$requeter=true)
{
	$connexion = &$GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
	$link = $connexion['link'];
	$connexion['last'] = $q = "SELECT column_name, column_default, data_type FROM information_schema.columns WHERE table_name ILIKE " . _q($nom_table);

	$res = spip_pg_query_simple($link, $q);
	if (!$res) return false;
	
	// etrangement, $res peut ne rien contenir, mais arriver ici...
	// il faut en tenir compte dans le return
	$fields = array();
	while($field = pg_fetch_array($res, NULL, PGSQL_NUM)) {
		$fields[$field[0]] = $field[2] . (!$field[1] ? '' : (" DEFAULT " . $field[1]));
	}
	$connexion['last'] = $q = "SELECT indexdef FROM pg_indexes WHERE tablename ILIKE " . _q($nom_table);
	$res = spip_pg_query_simple($link, $q);
	$keys = array();
	while($index = pg_fetch_array($res, NULL, PGSQL_NUM)) {
		if (preg_match('/CREATE\s+(UNIQUE\s+)?INDEX\s([^\s]+).*\((.*)\)$/', $index[0],$r)) {
			$nom = str_replace($nom_table.'_','',$r[2]);
			$keys[($r[1] ? "PRIMARY KEY" : ("KEY " . $nom))] = $r[3];
		}
	}

	return count($fields) ? array('field' => $fields, 'key' => $keys) : false;
}

// Fonction de creation d'une table SQL nommee $nom
// a partir de 2 tableaux PHP :
// champs: champ => type
// cles: type-de-cle => champ(s)
// si $autoinc, c'est une auto-increment (i.e. serial) sur la Primary Key
// Le nom des index est prefixe par celui de la table pour eviter les conflits
// http://doc.spip.org/@spip_pg_create
function spip_pg_create($nom, $champs, $cles, $autoinc=false, $temporary=false, $serveur='',$requeter=true) {

	$connexion = $GLOBALS['connexions'][$serveur ? strtolower($serveur) : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];
	if ($prefixe) $nom = preg_replace('/^spip/', $prefixe, $nom);
	$query = $prim = $prim_name = $v = $s = $p='';
	$keys = array();

	// certains plugins declarent les tables  (permet leur inclusion dans le dump)
	// sans les renseigner (laisse le compilo recuperer la description)
	if (!is_array($champs) || !is_array($cles)) 
		return;

	foreach($cles as $k => $v) {
		if (strpos($k, "KEY ") === 0) {
		  $n = str_replace('`','',$k);	
		  $v = str_replace('`','"',$v);	
		  $i = $nom . preg_replace("/KEY +/", '_',$n);
		  if ($k != $n) $i = "\"$i\"";
		  $keys[] = "CREATE INDEX $i ON $nom ($v);";
		} else $prim .= "$s\n\t\t" . str_replace('`','"',$k) ." ($v)";
		if ($k == "PRIMARY KEY")
			$prim_name = $v;
		$s = ",";
	}
	$s = '';
	
	$character_set = "";
	if (@$GLOBALS['meta']['charset_sql_base'])
		$character_set .= " CHARACTER SET ".$GLOBALS['meta']['charset_sql_base'];
	if (@$GLOBALS['meta']['charset_collation_sql_base'])
		$character_set .= " COLLATE ".$GLOBALS['meta']['charset_collation_sql_base'];

	foreach($champs as $k => $v) {
		$k = str_replace('`','"',$k);
		if (preg_match(',([a-z]*\s*(\(\s*[0-9]*\s*\))?(\s*binary)?),i',$v,$defs)){
			if (preg_match(',(char|text),i',$defs[1]) AND !preg_match(',binary,i',$defs[1]) ){
				$v = $defs[1] . $character_set . ' ' . substr($v,strlen($defs[1]));
			}
		}

		$query .= "$s\n\t\t$k "
			. (($autoinc && ($prim_name == $k) && preg_match(',\b(big|small|medium|tiny)?int\b,i', $v))
				? " bigserial"
			   : mysql2pg_type($v)
			);
		$s = ",";
	}
	$temporary = $temporary ? 'TEMPORARY':'';

	// En l'absence de "if not exists" en PG, on neutralise les erreurs

	$q = "CREATE $temporary TABLE $nom ($query" . ($prim ? ",$prim" : '') . ")".
	($character_set?" DEFAULT $character_set":"")
	."\n";

	if (!$requeter) return $q;
	$connexion['last'] = $q;
	$r = @pg_query($link, $q);

	if (!$r)
		spip_log("Impossible de creer cette table: $q");
	else {
		foreach($keys as $index) {pg_query($link, $index);}
	} 
	return $r;
}


function spip_pg_create_base($nom, $serveur='',$requeter=true) {
  return spip_pg_query("CREATE DATABASE $nom", $serveur, $requeter);
}

// Fonction de creation d'une vue SQL nommee $nom
// http://doc.spip.org/@spip_pg_create_view
function spip_pg_create_view($nom, $query_select, $serveur='',$requeter=true) {
	if (!$query_select) return false;
	// vue deja presente
	if (sql_showtable($nom, false, $serveur)) {
		if ($requeter) spip_log("Echec creation d'une vue sql ($nom) car celle-ci existe deja (serveur:$serveur)");
		return false;
	}
	
	$query = "CREATE VIEW $nom AS ". $query_select;
	return spip_pg_query($query, $serveur, $requeter);
}


// http://doc.spip.org/@spip_pg_set_connect_charset
function spip_pg_set_connect_charset($charset, $serveur='',$requeter=true){
	spip_log("changement de charset sql a ecrire en PG");
}


/**
 * Optimise une table SQL
 *
 * @param $table nom de la table a optimiser
 * @param $serveur nom de la connexion
 * @param $requeter effectuer la requete ? sinon retourner son code
 * @return bool|string true / false / requete
**/
// http://doc.spip.org/@spip_sqlite_optimize
function spip_pg_optimize($table, $serveur='',$requeter=true){
	return spip_pg_query("VACUUM ". $table, $serveur, $requeter);
}

// Selectionner la sous-chaine dans $objet
// correspondant a $lang. Cf balise Multi de Spip

// http://doc.spip.org/@spip_pg_multi
function spip_pg_multi ($objet, $lang) {
	$r = "regexp_replace("
	  . $objet
	  . ",'<multi>.*[[]"
	  . $lang
	  . "[]]([^[]*).*</multi>', E'\\\\1') AS multi";
	return $r;
}

// Palanquee d'idiosyncrasies MySQL dans les creations de table
// A completer par les autres, mais essayer de reduire en amont.

// http://doc.spip.org/@mysql2pg_type
function mysql2pg_type($v)
{
  return     
  		preg_replace('/auto_increment/i', '', // non reconnu
  		preg_replace('/bigint/i', 'bigint', 
  		preg_replace('/mediumint/i', 'mediumint', 
  		preg_replace('/smallint/i', 'smallint', 
		preg_replace("/tinyint/i", 'int',
		preg_replace('/int\s*[(]\s*\d+\s*[)]/i', 'int', 
		preg_replace("/longtext/i", 'text',
		str_replace("mediumtext", 'text',
		preg_replace("/tinytext/i", 'text',
	  	str_replace("longblob", 'text',
		str_replace("0000-00-00",'0001-01-01',
		preg_replace("/datetime/i", 'timestamp',
		preg_replace("/unsigned/i", '', 	
		preg_replace("/double/i", 'double precision', 	 	
		preg_replace('/VARCHAR\((\d+)\)\s+BINARY/i', 'varchar(\1)', 
		preg_replace("/ENUM *[(][^)]*[)]/i", "varchar(255)",
					      $v 
			     ))))))))))))))));
}

// Renvoie false si on n'a pas les fonctions pg (pour l'install)
// http://doc.spip.org/@spip_versions_pg
function spip_versions_pg(){
	charger_php_extension('pgsql');
	return function_exists('pg_connect');	
}

?>
