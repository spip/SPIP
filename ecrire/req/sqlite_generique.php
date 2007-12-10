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

// infos :
// il ne faut pas avoir de PDO::CONSTANTE dans ce fichier sinon php4 se tue !
// idem, il ne faut pas de $obj->toto()->toto sinon php4 se tue !
	
# todo : get/set_caracteres ?
# todo : REPAIR TABLE ?


/*
 * 
 * regroupe le maximum de fonctions qui peuvent cohabiter
 * D'abord les fonctions d'abstractions de SPIP
 * 
 */
function req_sqlite_dist($addr, $port, $login, $pass, $db='', $prefixe='', $ldap='', $sqlite_version=''){
	static $last_connect = array();

	// si provient de selectdb
	// un code pour etre sur que l'on vient de select_db()
	if (strpos($db, $code = '@selectdb@')!==false) {
		foreach (array('addr','port','login','pass','prefixe','ldap') as $a){
			$$a = $last_connect[$a];
		}
		$db = str_replace($code, '', $db);
	}

	/*
	 * En sqlite, seule l'adresse du fichier est importante.
	 * Ce sera $db le nom, et le path _DIR_DB
	 */
	_sqlite_init();

	// un nom de base demande et impossible d'obtenir la base, on s'en va
	if ($db && !is_file($f = _DIR_DB . $db . '.sqlite') && !is_writable(_DIR_DB))
			return false;

	
	// charger les modules sqlite au besoin
	if (!_sqlite_charger_version($sqlite_version)) {
		spip_log("Impossible de trouver/charger le module SQLite ($sqlite_version)!");
		return false;	
	} 
	
	// chargement des constantes
	// il ne faut pas definir les constantes avant d'avoir charge les modules sqlite
	$define = "spip_sqlite".$sqlite_version."_constantes";
	$define();
		
	$ok = false;
	if (!$db){
		// si installation -> base temporaire tant qu'on ne connait pas son vrai nom
		if (_request('exec') == 'install'){
			// creation d'une base temporaire pour le debut d'install
			$tmp = _DIR_DB . "_sqlite".$sqlite_version."_install.sqlite";
			if ($sqlite_version == 3)
				$ok = $link = new PDO("sqlite:$tmp");
			else
				$ok = $link = sqlite_open($tmp, _SQLITE_CHMOD, $err);
			$db = "_sqlite".$sqlite_version."_install";	
		// sinon, on arrete finalement
		} else {
			return false;
		}
	} else {
		// Ouvrir (eventuellement creer la base)
		// si pas de version fourni, on essaie la 3, sinon la 2
		if ($sqlite_version == 3) {
			$ok = $link = new PDO("sqlite:$f");
		} else {
			$ok = $link = sqlite_open($f, _SQLITE_CHMOD, $err);
		}
	}

	if (!$ok){
		spip_log("Impossible d'ouvrir la base de donnee SQLite ($sqlite_version) : $f ");
		return false;
	}
	
	if ($link) {
		$last_connect = array (
			'addr' => $addr,
			'port' => $port,
			'login' => $login,
			'pass' => $pass,
			'db' => $db,
			'prefixe' => $prefixe,
			'ldap' => $ldap
		);
	}

	return array(
		'db' => $db,
		'prefixe' => $prefixe ? $prefixe : $db,
		'link' => $link,
		'ldap' => $ldap,
		);	
}


// obsolete, ne plus utiliser
/*
function spip_query_db($query, $serveur='') {
	return spip_sqlite_query($query, $serveur);
}
*/

// Fonction de requete generale, munie d'une trace a la demande
function spip_sqlite_query($query, $serveur='') {
#spip_log("spip_sqlite_query() > $query");
	_sqlite_init();
	
	if (!($sqlite = _sqlite_link($serveur)) && (_request('exec')!='install')){
		spip_log("Aucune connexion sqlite (link)");
		return false;	
	}

	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];
	$db = $connexion['db'];
	
	// corriger la requete au format mysql->sqlite
	// creer la requete de comptage (sqlite3)
	$analyse = new sqlite_analyse_query($sqlite, $query, $db, $prefixe);
	$analyse->creerLesRequetes();
	$query = $analyse->query; // pas indispensable car $query par &
	$queryCount = $analyse->queryCount;
	unset($analyse);
	
	$t = !isset($_GET['var_profile']) ? 0 : trace_query_start();
#echo("<br /><b>spip_sqlite_query() $serveur >></b> $query"); // boum ? pourquoi ?
	if ($sqlite){
		if (_sqlite_is_version(3, $sqlite)) {
			$r = $sqlite->query($query);
			
			// comptage : oblige de compter le nombre d'entrees retournees par la requete
			// aucune autre solution ne donne le nombre attendu :( !
			// particulierement s'il y a des LIMIT dans la requete.
			if ($queryCount){
				if ($r) {
					$l = $sqlite->query($queryCount);
					$r->spipSqliteRowCount =  count($l->fetchAll());
				} else {
					$r->spipSqliteRowCount = 0;
				}
			}
		} else {
			$r = sqlite_query($sqlite, $query);
		}
	} else {
		$r = false;	
	}

#spip_log("spip_sqlite_query() >> $query"); // boum ? pourquoi ?
	if (!$r){
		echo "<br /><small>#erreur serveur '$serveur' dans &gt; $query</small><br />";
		echo "<br />- ".spip_sqlite_error($query, $serveur);
	}
	if (!$r && $e = spip_sqlite_errno($serveur))	// Log de l'erreur eventuelle
		$e .= spip_sqlite_error($query, $serveur); // et du fautif

	return $t ? trace_query_end($query, $t, $r, $e) : $r;
}


/* ordre alphabetique pour les autres */

function spip_sqlite_alter($query, $serveur=''){
	return spip_sqlite_query("ALTER ".$query, $serveur);
}


// Fonction de creation d'une table SQL nommee $nom
function spip_sqlite_create($nom, $champs, $cles, $autoinc=false, $temporary=false, $serveur='') {

	$query = $keys = $s = $p = '';

	// certains plugins declarent les tables  (permet leur inclusion dans le dump)
	// sans les renseigner (laisse le compilo recuperer la description)
	if (!is_array($champs) || !is_array($cles)) 
		return;

	// sqlite ne gere pas KEY tout court
	foreach($cles as $k => $v) {
		if ($k == "PRIMARY KEY"){
			$keys .= "$s\n\t\t$k ($v)";
			$p = $v;
		}
		$s = ",";
	}
	$s = '';
	
	/* a tester ulterieurement
	 * je ne sais pas a quoi ca sert
	 *
	$character_set = "";
	if (@$GLOBALS['meta']['charset_sql_base'])
		$character_set .= " CHARACTER SET ".$GLOBALS['meta']['charset_sql_base'];
	if (@$GLOBALS['meta']['charset_collation_sql_base'])
		$character_set .= " COLLATE ".$GLOBALS['meta']['charset_collation_sql_base'];
	*/

	// quelques remplacements
	$num = "\s?(\([0-9]*)\)?";
	$enum = "\s?(\(.*)\)?";
	
	$remplace = array(
		// pour l'autoincrement, il faut des INTEGER NOT NULL PRIMARY KEY
		'(big)?int(eger)?'.$num => 'INTEGER',		
		'enum'.$enum => 'VARCHAR',
		'binary' => ''
	);

	$_replace = array();
	foreach ($remplace as $cle=>$val)
		$_replace["/$cle/is"] = $val;
		
	$champs = preg_replace(array_keys($_replace), $_replace, $champs);
		
	foreach($champs as $k => $v) {
		// je sais pas ce que c'est ca...
		// puis personne rentre ici vue qe binary->''
		/*
		if (preg_match(',([a-z]*\s*(\(\s*[0-9]*\s*\))?(\s*binary)?),i',$v,$defs)){
			if (preg_match(',(char|text),i',$defs[1]) AND !preg_match(',binary,i',$defs[1]) ){
				$v = $defs[1] . $character_set . ' ' . substr($v,strlen($defs[1]));
			}
		}
		*/
		
		// autoincrement v3.1.3 ?
		$query .= "$s\n\t\t$k $v";
			//. (($autoinc && ($p == $k) && preg_match(',\binteger\b,i', $v))? " AUTOINCREMENT": '');
		$s = ",";
	}

	/* simuler le IF NOT EXISTS - version 2 */
	if (_sqlite_is_version(2, '', $serveur)){
		$a = spip_sqlite_showtable($nom, $serveur); 
		if ($a) return false;
	}
	
	$temporary = $temporary ? ' TEMPORARY':'';
	$ifnotexists = _sqlite_is_version(3, '', $serveur) ? ' IF NOT EXISTS':'';// IF NOT EXISTS 
	$q = "CREATE$temporary TABLE$ifnotexists $nom ($query" . ($keys ? ",$keys" : '') . ")"
	//. ($character_set?" DEFAULT $character_set":"")
	."\n";

	return spip_sqlite_query($q, $serveur);
}


// en PDO/sqlite3, il faut calculer le count par une requete count(*)
// pour les resultats de SELECT
// cela est fait sans spip_sqlite_query()
function spip_sqlite_count($r, $serveur='') {
	if (!$r) return 0;
		
	if (_sqlite_is_version(3, '', $serveur)){
		// select ou autre (insert, update,...) ?
		if (isset($r->spipSqliteRowCount)) {
			// Ce compte est faux s'il y a des limit dans la requete :(
			// il retourne le nombre d'enregistrements sans le limit
			return $r->spipSqliteRowCount;
		} else {
			return $r->rowCount();
		}
	} else {
		return sqlite_num_rows($r);
	}
}


function spip_sqlite_countsel($from = array(), $where = array(), $groupby = '', $limit = '', $sousrequete = '', $having = array(), $serveur='') {
	$r = spip_sqlite_select('COUNT(*)', $from, $where,$groupby, '', $limit,
			$having, $serveur);
	
	if ($r) {
		if (_sqlite_is_version(3,'',$serveur)){
			list($r) = spip_sqlite_fetch($r, SPIP_SQLITE3_NUM, $serveur);
		} else {
			list($r) = spip_sqlite_fetch($r, SPIP_SQLITE2_NUM, $serveur);
		}
		
	}
	return $r;
}



function spip_sqlite_delete($table, $where='', $serveur='') {
	return spip_sqlite_query(
			  _sqlite_calculer_expression('DELETE FROM', $table, ',')
			. _sqlite_calculer_expression('WHERE', $where),
			$serveur);
}


function spip_sqlite_drop_table($table, $exist='', $serveur='') {
	if ($exist) $exist =" IF EXISTS";
	return spip_sqlite_query("DROP TABLE$exist $table", $serveur);
}


function spip_sqlite_error($query='', $serveur='') {
	$link  = _sqlite_link($serveur);
	
	if (_sqlite_is_version(3, $link)){
		$errs = $link->errorInfo();
		$s = '';
		foreach($errs as $n=>$e){
			$s .= "\n$n : $e";
		}
		
	} elseif ($link) {
		$s = sqlite_error_string(sqlite_last_error($link));
	} else {
		$s = ": aucune ressource sqlite (link)";
	}
	if ($s) spip_log("$s - $query", 'sqlite');
	return $s;
}


function spip_sqlite_errno($serveur='') {
	$link  = _sqlite_link($serveur);
	
	if (_sqlite_is_version(3, $link)){
		$s = $link->errorCode();
	} elseif ($link) {
		$s = sqlite_last_error($link);
	} else {
		$s = ": aucune ressource sqlite (link)";	
	}
	if ($s) spip_log("Erreur sqlite $s");

	return $s;
}


function spip_sqlite_explain($query, $serveur=''){
	if (strpos($query, 'SELECT') !== 0) return array();

	$query = 'EXPLAIN ' . _sqlite_traite_query($query, $db, $prefixe);
	$r = spip_sqlite_query($query, $serveur);
	return $r ? spip_sqlite_fetch($r, null, $serveur) : false; // hum ? etrange ca... a verifier
}


function spip_sqlite_fetch($r, $t='', $serveur='') {
	$link = _sqlite_link($serveur);
	if (!$t) {
		if (_sqlite_is_version(3, $link)) {
			$t = SPIP_SQLITE3_ASSOC;
		} else {
			$t = SPIP_SQLITE2_ASSOC;
		}
	}
		

	if (_sqlite_is_version(3, $link)){
		if ($r) $retour = $r->fetch($t);
	} elseif ($r) {
		$retour = sqlite_fetch_array($r, $t);
	}	
	
	// la version 2  parfois renvoie des 'table.titre' au lieu de 'titre' tout court ! pff !
	// suppression de 'table.' pour toutes les cles (c'est un peu violent !)
	if ($retour && _sqlite_is_version(2, $link)){
		$new = array();
		foreach ($retour as $cle=>$val){
			if (($pos = strpos($cle, '.'))!==false){
				$cle = substr($cle,++$pos);
			}
			$new[$cle] = $val;
		}
		$retour = &$new;
	}

	//print_r($retour);
	return $retour;
}


function spip_sqlite_free($r, $serveur='') {
	//return sqlite_free_result($r);
}


function spip_sqlite_get_charset($charset=array(), $serveur=''){
	//$c = !$charset ? '' : (" LIKE "._q($charset['charset']));
	//return spip_sqlite_fetch(sqlite_query(_sqlite_link($serveur), "SHOW CHARACTER SET$c"), NULL, $serveur);
}


function spip_sqlite_hex($v){
	return "0x" . $v;
}


function spip_sqlite_in($val, $valeurs, $not='', $serveur='') {
	// limite a 255 elements aussi en sqlite ou non ?
	if (is_array($valeurs))
		$valeurs = join(',', array_map('spip_sqlite_quote', $valeurs));
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


function spip_sqlite_insert($table, $champs, $valeurs, $desc='', $serveur='') {

	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];
	$sqlite = $connexion['link'];
	$db = $connexion['db'];

	if ($prefixe) $table = preg_replace('/^spip/', $prefixe, $table);

	$t = !isset($_GET['var_profile']) ? 0 : trace_query_start();

	$query="INSERT OR REPLACE INTO $table $champs VALUES $valeurs";

	if ($r = spip_sqlite_query($query, $serveur)) {
		if (_sqlite_is_version(3, $sqlite)) $nb = $sqlite->lastInsertId();
		else $nb = sqlite_last_insert_rowid($sqlite);
	} else {
	  if ($e = spip_sqlite_errno($serveur))	// Log de l'erreur eventuelle
		$e .= spip_sqlite_error($query, $serveur); // et du fautif
	}
	return $t ? trace_query_end($query, $t, $nb, $e) : $nb;

}


function spip_sqlite_insertq($table, $couples=array(), $desc=array(), $serveur='') {
	if (!$desc) $desc = description_table($table);
	if (!$desc) die("$table insertion sans description");
	$fields =  isset($desc['field'])?$desc['field']:array();

	foreach ($couples as $champ => $val) {
		$couples[$champ]= _sqlite_calculer_cite($val, $fields[$champ]);
	}
	
	return spip_sqlite_insert($table, "(".join(',',array_keys($couples)).")", "(".join(',', $couples).")", $desc, $serveur);
}


function spip_sqlite_listdbs($serveur='') {
	_sqlite_init();
	
	if (!is_dir($d = substr(_DIR_DB,0,-1))){
		return array();
	}
	
	include_spip('inc/flock');
	$bases = preg_files($d, $pattern = '(.*)\.sqlite$');
	$bds = array();

	foreach($bases as $b){
		// pas de bases commencant pas sqlite 
		// (on s'en sert pour l'installation pour simuler la presence d'un serveur)
		// les bases sont de la forme _sqliteX_tmp_spip_install.sqlite
		if (strpos($b, '_sqlite')) continue;
		$bds[] = preg_replace(";.*/$pattern;iS",'$1', $b);
	}

	return $bds;
}


function spip_sqlite_multi ($objet, $lang) {
	$r = "REGEXP_REPLACE("
	  . $objet
	  . ",'<multi>.*[\[]"
	  . $lang
	  . "[\]]([^\[]*).*</multi>', '$1') AS multi";
	return $r;
}


function spip_sqlite_optimize($table, $serveur=''){
	spip_sqlite_query("OPTIMIZE TABLE ". $table, $serveur); // <- a verifier mais ca doit pas etre ca !
	return true;
}


// avoir le meme comportement que _q()
function spip_sqlite_quote($v){
	if (is_int($v)) return strval($v);
	if (is_array($v)) return join(",", array_map('spip_sqlite_quote', $v));

	if (function_exists('sqlite_escape_string')) {
		return "'" . sqlite_escape_string($v) . "'";
	}
	
	// trouver un link sqlite3 pour faire l'echappement
	foreach ($GLOBALS['connexions'] as $s) {
		if (_sqlite_is_version(3, $l = $s['link'])){
			return	$l->quote($v);
		}	
	}
}


function spip_sqlite_repair($table, $serveur=''){
	return spip_sqlite_query("REPAIR TABLE $table", $serveur); // <- ca m'ettonerait aussi ca !
}


function spip_sqlite_replace($table, $values, $keys=array(), $serveur='') {
	return spip_sqlite_query("REPLACE INTO $table (" . join(',',array_keys($values)) . ') VALUES (' .join(',',array_map('spip_sqlite_quote', $values)) . ')', $serveur);
}


function spip_sqlite_select($select, $from, $where='', $groupby='', $orderby='', $limit='', $having='', $serveur='') {	
	// version() n'est pas connu de sqlite
	$select = str_replace('version()', 'sqlite_version()',$select);
	
	// recomposer from
	$from = (!is_array($from) ? $from : _sqlite_calculer_select_as($from));
	
	$query = 
		  _sqlite_calculer_expression('SELECT', $select, ', ')
		. _sqlite_calculer_expression('FROM', $from, ', ')
		. _sqlite_calculer_expression('WHERE', $where)
		. _sqlite_calculer_expression('GROUP BY', $groupby, ',')
		. _sqlite_calculer_expression('HAVING', $having)
		. ($orderby ? ("\nORDER BY " . _sqlite_calculer_order($orderby)) :'')
		. ($limit ? "\nLIMIT $limit" : '');

	// Erreur ? C'est du debug de squelette, ou une erreur du serveur

	if (isset($GLOBALS['var_mode']) AND $GLOBALS['var_mode'] == 'debug') {
		include_spip('public/debug');
		boucle_debug_requete($query);
	}

	if (!($res = spip_sqlite_query($query, $serveur))) {
		include_spip('public/debug');
		
		erreur_requete_boucle(substr($query, 7),
				      spip_sqlite_errno($serveur),
				      spip_sqlite_error($query, $serveur) );
	}

	return $res;
}


function spip_sqlite_selectdb($db, $serveur='') {
	_sqlite_init();

	// interdire la creation d'une nouvelle base, 
	// sauf si on est dans l'installation
	if (!is_file($f = _DIR_DB . $db . '.sqlite')
		&& _request('exec')!='install')
		return false;

	// se connecter a la base indiquee
	// avec les identifiants connus
	$index = $serveur ? $serveur : 0;

	if ($link = spip_connect_db('', '', '', '', '@selectdb@' . $db , $serveur, '', '')){
		if (($db==$link['db']) && $GLOBALS['connexions'][$index] = $link)
			return $db;					
	} else {
		spip_log("Impossible de selectionner la base $db", 'sqlite');
		return false;
	}

}


function spip_sqlite_set_charset($charset, $serveur=''){
	#spip_log("changement de charset sql : "."SET NAMES "._q($charset));
	# return spip_sqlite_query("SET NAMES ". spip_sqlite_quote($charset), $serveur); //<-- Passe pas !
}


function spip_sqlite_showbase($match, $serveur=''){
	return spip_sqlite_query('SELECT name FROM sqlite_master WHERE type LIKE "'.$match.'"', $serveur);
}


function spip_sqlite_showtable($nom_table, $serveur=''){

	$query = 
			'SELECT sql FROM'
   			. '(SELECT * FROM sqlite_master UNION ALL'
   			. ' SELECT * FROM sqlite_temp_master)'
			. " WHERE tbl_name LIKE '$nom_table'"
			. " AND type!='meta' AND sql NOT NULL AND name NOT LIKE 'sqlite_%'"
			. 'ORDER BY substr(type,2,1), name';
	
	$a = spip_sqlite_query($query, $serveur);
	if (!$a) return "";
	if (!($a = spip_sqlite_fetch($a, null, $serveur))) return "";
	$a = array_shift($a); 
	if (!preg_match("/^[^(),]*\((([^()]*\([^()]*\)[^()]*)*)\)[^()]*$/", $a, $r))
		return "";
	else {
		$dec = $r[1];
		if (preg_match("/^(.*?),([^,]*KEY.*)$/s", $dec, $r)) {
			$namedkeys = $r[2];
			$dec = $r[1];
		}
		else 
			$namedkeys = "";

		$fields = array();
		foreach (explode(",",$dec) as $v) {
			preg_match("/^\s*([^\s]+)\s+(.*)/",$v,$r);
			$fields[strtolower($r[1])] = $r[2];
		}
		$keys = array();

		foreach(preg_split('/\)\s*,?/',$namedkeys) as $v) {
			if (preg_match("/^\s*([^(]*)\((.*)$/",$v,$r)) {
				$k = str_replace("`", '', trim($r[1]));
				$t = strtolower(str_replace("`", '', $r[2]));
				if ($k && !isset($keys[$k])) $keys[$k] = $t; else $keys[] = $t;
			}
		}
		/*
		 * me demande si les cles servent au compilateur de spip
		 * car vu que sqlite le gere pas, je sais pas ce que ca donne ...
		 */
		return array('field' => $fields, 'key' => $keys);
	}
}


function spip_sqlite_update($table, $champs, $where='', $desc='', $serveur='') {
	$set = array();
	foreach ($champs as $champ => $val)
		$set[] = $champ . "=$val";
	if (!empty($set))
		return spip_sqlite_query(
			  _sqlite_calculer_expression('UPDATE', $table, ',')
			. _sqlite_calculer_expression('SET', $set, ',')
			. _sqlite_calculer_expression('WHERE', $where), 
			$serveur);
}


function spip_sqlite_updateq($table, $champs, $where='', $desc=array(), $serveur='') {

	if (!$champs) return;
	if (!$desc) $desc = description_table($table);
	if (!$desc) die("$table insertion sans description");
	$fields =  $desc['field'];
	$set = array();
	foreach ($champs as $champ => $val) {
		$set[] = $champ . '=' . _sqlite_calculer_cite($val, $fields[$champ]);
	}
	return spip_sqlite_query(
			  _sqlite_calculer_expression('UPDATE', $table, ',')
			. _sqlite_calculer_expression('SET', $set, ',')
			. _sqlite_calculer_expression('WHERE', $where),
			$serveur);
}



/*
 * 
 * Ensuite les fonctions non abstraites
 * crees pour l'occasion de sqlite
 * 
 */


// fonction pour la premiere connexion a un serveur SqLite
function _sqlite_init(){
	if (!defined('_DIR_DB')) define('_DIR_DB', _DIR_ETC . 'bases/');
	if (!defined('_SQLITE_CHMOD')) define('_SQLITE_CHMOD', _SPIP_CHMOD);
	
	if (!is_dir($d = _DIR_DB)){
		include_spip('inc/flock');
		sous_repertoire($d);
	}
}


// teste la version sqlite du link en cours
function _sqlite_is_version($version='', $link='', $serveur=''){
	if ($link==='') $link = _sqlite_link($serveur);
	if (!$link) return false;
	if (is_a($link, 'PDO')){
		$v = 3;	
	} else {
		$v = 2;	
	}
	
	if (!$version) return $v;
	return ($version == $v);
}


// retrouver un link (et definir les fonctions externes sqlite->php)
// $recharger devient inutile (a supprimer ?)
function _sqlite_link($serveur = '', $recharger = false){
	static $charge = array();
	if ($recharger) $charge[$serveur] = false;
	
	$link = &$GLOBALS['connexions'][$serveur ? $serveur : 0]['link'];

	if ($link && !$charge[$serveur]){
		include_spip('req/sqlite_fonctions');
		_sqlite_init_functions($link);
		$charge[$serveur] = true;
	}
	return $link;
}


/* ordre alphabetique pour les autres */


// renvoie les bons echappements (pas sur les fonctions now())
function _sqlite_calculer_cite($v, $type) {
	if (sql_test_date($type) AND preg_match('/^\w+\(/', $v)
	OR (sql_test_int($type)
		 AND (is_numeric($v)
		      OR (ctype_xdigit(substr($v,2))
			  AND $v[0]=='0' AND $v[1]=='x'))))
		return $v;
	//else return  ("'" . spip_sqlite_quote($v) . "'");
	else return  (spip_sqlite_quote($v));
}


// renvoie grosso modo "$expression join($join, $v)"
function _sqlite_calculer_expression($expression, $v, $join = 'AND'){
	if (empty($v))
		return '';
	
	$exp = "\n$expression ";
	
	if (!is_array($v)) {
		return $exp . $v;
	} else {
		if (strtoupper($join) === 'AND')
			return $exp . join("\n\t$join ", array_map('_sqlite_calculer_where', $v));
		else
			return $exp . join($join, $v);
	}
}




// pour conversion 0x ?
function _sqlite_calculer_order($orderby) {
	//if (!is_array($orderby)) $orderby = explode(',', $orderby);
	//array_walk($orderby, '_sqlite_mettre_quote');
	//return join(", ", $orderby);
	return (is_array($orderby)) ? join(", ", $orderby) :  $orderby;
}


// renvoie des 'nom AS alias' 
function _sqlite_calculer_select_as($args){
	if (isset($args[-1])) {
		$join = ' ' . $args[-1];
		unset($args[-1]);
	} else $join ='';
	$res = '';
	foreach($args as $k => $v) {
		$res .= ', ' . $v . (is_numeric($k) ? '' : " AS '$k'") . $join;
		$join = '';
	}
	return substr($res,2);
}


// renvoie les bonnes parentheses pour des where imbriquees
function _sqlite_calculer_where($v){
	if (!is_array($v))
	  return $v ;

	$op = array_shift($v);
	if (!($n=count($v)))
		return $op;
	else {
		$arg = _sqlite_calculer_where(array_shift($v));
		if ($n==1) {
			  return "$op($arg)";
		} else {
			$arg2 = _sqlite_calculer_where(array_shift($v));
			if ($n==2) {
				return "($arg $op $arg2)";
			} else return "($arg $op ($arg2) : $v[0])";
		}
	}
}



/*
 * Charger les modules sqlite (si possible) (juste la version demandee),
 * ou, si aucune version, renvoie les versions sqlite dispo 
 * sur ce serveur dans un array
 */
function _sqlite_charger_version($version=''){
	$versions = array();
	
	// version 2
	if (!$version || $version == 2){
		$ok = false;
		if (extension_loaded('sqlite')){
			$ok = true;	
		} else {
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
				$ok = dl('php_sqlite.dll');
			} else {
				$ok = dl('sqlite.so');
			}
		}
		if ($ok) $versions[]=2;
	}
	
	// version 3
	if (!$version || $version == 3){
		$ok = false;
		if (extension_loaded('pdo') && extension_loaded('pdo_sqlite')){
			$ok = true;	
		} else {	
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
				$ok = dl('php_pdo.dll') && dl('php_pdo_sqlite.dll');
			} else {
				$ok = dl('pdo.so') && dl('pdo_sqlite.so');
			}	
		}
		if ($ok) $versions[]=3;
	}
	if ($version) return in_array($version, $versions);
	return $versions;
}

/*
 * renvoyer la liste des versions sqlite disponibles
 * sur le serveur 
 */
function spip_versions_sqlite(){
	return 	_sqlite_charger_version();
}




/*
 * Nom des fonctions
 */
function _sqlite_ref_fonctions(){
	$fonctions = array(
	// tests
		'begin' => 'spip_sqlite_begin',
		'commit' => 'spip_sqlite_commit',
		
		'alter' => 'spip_sqlite_alter',
		'count' => 'spip_sqlite_count',
		'countsel' => 'spip_sqlite_countsel',
		'create' => 'spip_sqlite_create',
		'delete' => 'spip_sqlite_delete',
		'drop_table' => 'spip_sqlite_drop_table',
		'errno' => 'spip_sqlite_errno',
		'error' => 'spip_sqlite_error',
		'explain' => 'spip_sqlite_explain',
		'fetch' => 'spip_sqlite_fetch',
		'free' => 'spip_sqlite_free',
		'hex' => 'spip_sqlite_hex',
		'in' => 'spip_sqlite_in', 
		'insert' => 'spip_sqlite_insert',
		'insertq' => 'spip_sqlite_insertq',
		'listdbs' => 'spip_sqlite_listdbs',
		'multi' => 'spip_sqlite_multi',
		'optimize' => 'spip_sqlite_optimize',
		'query' => 'spip_sqlite_query',
		'quote' => 'spip_sqlite_quote',
		'replace' => 'spip_sqlite_replace',
		'repair' => 'spip_sqlite_repair',
		'select' => 'spip_sqlite_select',
		'selectdb' => 'spip_sqlite_selectdb',
		'set_charset' => 'spip_sqlite_set_charset',
		'get_charset' => 'spip_sqlite_get_charset',
		'showbase' => 'spip_sqlite_showbase',
		'showtable' => 'spip_sqlite_showtable',
		'update' => 'spip_sqlite_update',
		'updateq' => 'spip_sqlite_updateq',
	);
	
	// association de chaque nom http d'un charset aux couples sqlite 
	
	$charsets = array(
		'iso-8859-1'=>array('charset'=>'latin1','collation'=>'latin1_swedish_ci'),// non supporte ?
		'utf-8'=>array('charset'=>'utf8','collation'=>'utf8_general_ci'), 
		//'utf-16be'=>array('charset'=>'utf16be','collation'=>'UTF-16BE'),// aucune idee de quoi il faut remplir dans es champs la
		//'utf-16le'=>array('charset'=>'utf16le','collation'=>'UTF-16LE')
	);
	
	$fonctions['charsets'] = $charsets;
	
	return $fonctions;
}






/*
 * Un classe simplement pour un preg_replace_callback avec des parametres 
 * dans la fonction appelee que l'on souhaite incrementer (fonction pour proteger les textes)
 * 
 * Du coup, je mets aussi les traitements a faire dedans
 * 
 */
class sqlite_analyse_query {
	var $sqlite = ''; 		// la ressource link (ou objet pdo)
	var $query = ''; 		// la requete
	var $queryCount = ''; 	// la requete pour comptage des lignes select (sqlite3/PDO)
	var $db = ''; 			// le nom de la bdd
	var $prefixe = ''; 		// le prefixe des tables
	var $debug = false; 	// spip_logguer les actions
	var $crier = false; 	// echo des actions
	var $textes = array(); 	// array(code=>'texte') trouvé
	
	var $codeEchappements = "%@##@%";


	function sqlite_analyse_query(&$link, &$query, $db, $prefixe){
		$this->sqlite 		= $link;
		$this->query 		= $query;
		$this->db 			= $db;
		$this->prefixe 		= $prefixe;
		$this->queryCount 	= "";

		$this->sqlite_version = _sqlite_is_version('', $this->sqlite);
	}


	function debug($texte='', $afficherQuery = true){
		if ($afficherQuery){
			if ($this->debug) spip_log("sqlite_analyse_query > $texte >" . $this->query);
			if ($this->crier) echo "<b>sqlite_analyse_query > $texte ></b> " . $this->query . "<br/>\n";
		} else {
			if ($this->debug) spip_log("sqlite_analyse_query > $texte");
			if ($this->crier) echo "<b>sqlite_analyse_query > $texte </b><br/>\n";			
		}
	}


	function creerLesRequetes(){
		#$analyse->debug = $analyse->crier = true;
		$this->cacherLesTextes();
		// traitements
		$this->corrigerTout();
		// hop, on remet les 'textes'
		$this->afficherLesTextes();
		// requete pour comptage
		if ($this->sqlite_version == 3){
			$this->creerRequeteCount();
		}
	}
	
	
	// enlever le contenu 'texte' des requetes
	function cacherLesTextes(){
		$this->debug("protegerLesTextes 1");	
		
		// enlever les echappements ''
		$this->query = str_replace("''", $this->codeEchappements, $this->query);
		$this->debug("protegerLesTextes 2");
		
		// enlever les 'textes'
		$this->textes = array(); // vider 
		$this->query = preg_replace_callback("/('[^']*')/", array(&$this, '_remplacerTexteParCode'), $this->query);
		$this->debug("protegerLesTextes 3");
	}


	// remettre le contenu 'texte' des requetes
	function afficherLesTextes(){
		$this->debug("afficherLesTextes 1");
		
		// remettre les 'textes'
		foreach ($this->textes as $cle=>$val){
			$this->query = str_replace($cle, $val, $this->query);
		}
		$this->debug("afficherLesTextes 2");
		
		// remettre les echappements ''
		$this->query = str_replace($this->codeEchappements,"''",$this->query);
		$this->debug("afficherLesTextes 3");
	}
		
	
	// les corrections
	
	function corrigerTout(){
		$this->corrigerCreateDatabase();
		$this->corrigerInsertIgnore();
		$this->corrigerDate();
		$this->corrigerUsing();
		$this->corrigerField();
		$this->corrigerTablesFrom();
		$this->corrigerZeroAsX();
		$this->corrigerAntiquotes();
	}
	
	
	// ` => rien
	function corrigerAntiquotes(){
		$this->query = str_replace('`','',$this->query);	
	}
	
	
	// Create Database -> ignore
	function corrigerCreateDatabase(){
		if (strpos($this->query, 'CREATE DATABASE')===0){
			spip_log("Sqlite : requete non executee -> $this->query","sqlite");
			$this->query = "SELECT 1";	
		}			
	}


	// corriger les dates avec INTERVAL
	function corrigerDate() { 
		if (strpos($this->query, 'INTERVAL')!==false){
			$this->debug("corrigerDate 1");
			$this->query = preg_replace_callback("/DATE_(ADD|SUB).*INTERVAL\s+(\d+)\s+([a-zA-Z]+)\)/U", 
							array(&$this, '_remplacerDateParTime'), 
							$this->query);
			$this->debug("corrigerDate 2");
		}
	}
	
	
	// FIELD (issu de pg) a tester !
	function corrigerField(){
		if (strpos($this->query, 'FIELD')!==false){
			$this->debug("corrigerField 1");
			$this->query = preg_replace_callback('/FIELD\s*\(([^\)]*)\)/', 
							array(&$this, '_remplacerFieldParCase'), 
							$this->query); 
			$this->debug("corrigerField 2");
		}	
	}

	
	// INSERT IGNORE -> insert (tout court et pas 'insert or replace')
	function corrigerInsertIgnore(){
		if (strpos($this->query, 'INSERT IGNORE')===0){
			#spip_log("Sqlite : requete transformee -> $this->query","sqlite");
			$this->query = 'INSERT ' . substr($this->query,'13');	
		}				
	}	
		
	
	// mettre les bons noms de table dans from, update, insert, replace...
	function corrigerTablesFrom(){	
		if (preg_match('/\s(SET|VALUES|WHERE)\s/i', $this->query, $regs)) {
			$suite = strstr($this->query, $regs[0]);
			$this->query = substr($this->query, 0, -strlen($suite));
		} else $suite ='';

		$pref = ($this->prefixe) ? $this->prefixe . "_": "";
		$this->query = preg_replace('/([,\s])spip_/', '\1'.$pref, $this->query) . $suite;
		#spip_log("_sqlite_traite_query: " . substr($this->query,0, 50) . ".... $this->db, $this->prefixe");	
	}
		
		
	// USING (inutile et non reconnu en sqlite2)
	function corrigerUsing(){
		if (($this->sqlite_version == 2) && (strpos($this->query, "USING")!==false)) {
			$this->query = preg_replace('/USING\s*\([^\)]*\)/', '', $this->query);
		}
	}


	// pg n'aime pas 0+x AS alias, sqlite, dans le meme style, 
	// sqlite n'apprecie pas du tout SELECT 0 as x ... ORDER BY x
	// il dit que x ne doit pas être un integer dans le orger by !
	// on remplace du coup x par vide() dans ce cas uniquement
	//
	// rien que pour public/vertebrer.php ?
	function corrigerZeroAsX(){
		if ((strpos($this->query, "0 AS")!==false)){
			// on ne remplace que dans ORDER BY ou GROUP BY 		
			if (preg_match('/\s(ORDER|GROUP) BY\s/i', $this->query, $regs)) {
				$suite = strstr($this->query, $regs[0]);
				$this->query = substr($this->query, 0, -strlen($suite));
			
				// on cherche les noms des x dans 0 AS x
				// on remplace dans $suite le nom par vide()
				preg_match_all('/\b0 AS\s*([^\s,]+)/', $this->query, $matches, PREG_PATTERN_ORDER);
				foreach ($matches[1] as $m){
					$suite = str_replace($m, 'VIDE()', $suite);
				}
				$this->query .= $suite;
			}
		}
		#$this->debug("Analyse > corrigerZeroAsX() > ");			
	}
	
	
	// les creations !
	
	function creerRequeteCount(){
		if (strpos($this->query,'SELECT')!==false){
			$this->queryCount = $this->query;
				//preg_replace('/SELECT(\s*)(DISTINCT)?(\s*)/s','SELECT$1$2$3COUNT(*) AS sqlite_count, ', $this->query, 1); // 1 seul !	
		}
	}		
			
	// les callbacks
	
	// remplacer DATE_ / INTERVAL par DATE...strtotime
	function _remplacerDateParTime($matches){
		$op = strtoupper($matches[1] == 'ADD')?'+':'-';	
		return "'".date("Y-m-d H:i:s", strtotime(" $op$matches[2] ".strtolower($matches[3])))."'";
	}	

	
	// callback ou l'on remplace FIELD(table,i,j,k...) par CASE WHEN table=i THEN n ... ELSE 0 END
	function _remplacerFieldParCase($matches){
		$fields = substr($matches[0],6,-1); // ne recuperer que l'interieur X de field(X)
		$t = explode(',', $fields);
		$index = array_shift($t);

		$res = '';
		$n=0;
		foreach($t as $v) {
			$n++;
			$res .= "\nWHEN $index=$v THEN $n";
		}
		return "CASE $res ELSE 0 END ";			
	}


	// callback ou l'on sauve le texte qui est cache dans un tableau $this->textes
	function _remplacerTexteParCode($matches){
		#$this->debug("Matches ". $matches[1], false);
		$this->textes[$code = "%@##".count($this->textes)."##@%"] = $matches[1];
		return $code;	
	}
		
}
?>
