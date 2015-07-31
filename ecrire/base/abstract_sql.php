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

// Chargement a la volee de la description d'un serveur de base de donnees

// http://doc.spip.org/@sql_serveur
function sql_serveur($ins_sql, $serveur) {

  // le serveur par defaut est indique par spip_connect
  // qui etablira la premiere connexion si ce n'est fait.
	if (!$serveur) {
		$g = spip_connect();
		$f = $g ? str_replace('query', $ins_sql, $g) : '';
	} else {
	  // c'est un autre; s'y connecter si ce n'est fait
		$f = 'spip_' . $serveur . '_' . $ins_sql;
		if (function_exists($f)) return $f;

		$d = find_in_path('inc_connect-' . $serveur . '.php');
		if (@file_exists($d))
			include($d);
		else spip_log("pas de fichier $d pour decrire le serveur '$serveur'");
	}
	if (function_exists($f)) return $f;

	include_spip('public/debug');
	erreur_squelette(" $f " ._T('zbug_serveur_indefini'), $serveur);

	// hack pour continuer la chasse aux erreurs
	return 'spip_log';
}

// http://doc.spip.org/@spip_sql_set_connect_charset
function spip_sql_set_connect_charset($charset,$serveur=''){
	$f = sql_serveur('set_connect_charset', $serveur);
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

// http://doc.spip.org/@sql_fetch
function sql_fetch($res, $serveur='') {
	$f = sql_serveur('fetch', $serveur);
	return $f($res);
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
function sql_delete($table, $where, $serveur='')
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

// http://doc.spip.org/@sql_showtable
function sql_showtable($table, $serveur='', $table_spip = false)
{
	if ($table_spip){
		if ($GLOBALS['table_prefix']) $table_pref = $GLOBALS['table_prefix']."_";
		else $table_pref = "";
		$table = preg_replace('/^spip_/', $table_pref, $table);
	}
	
	$f = sql_serveur('showtable', $serveur);
	return $f($table, $serveur);
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


// Une version d'abstract_showtable prenant en compte les tables predefinies
// Faudrait tester un jour si ca accelere vraiment.

// http://doc.spip.org/@description_table
function description_table($nom){
	global $tables_principales, $tables_auxiliaires, $table_des_tables, $tables_des_serveurs_sql;
	static $tables_externes = array();

	if (isset($tables_externes[$nom]))
		return array($nom, $tables_externes[$nom]);

	$nom_table = $nom;
	include_spip('base/serial');
	if (isset($tables_principales[$nom_table]))
		return array($nom_table, $tables_principales[$nom_table]);
	if (isset($tables_auxiliaires[$nom_table]))
		return array($nom_table, $tables_auxiliaires[$nom_table]);

	$nom_table = 'spip_' . $nom; // discutable
	if ($desc = sql_showtable($nom, '', true))
		if (isset($desc['field'])) {
			$tables_externes[$nom] = $desc;
			return array($nom, $desc);
		}

	return array($nom,array());
}

// http://doc.spip.org/@spip_num_rows
function spip_num_rows($r) {
	return sql_count($r);
}


//
// Poser un verrou local a un SPIP donne
//
// http://doc.spip.org/@spip_get_lock
function spip_get_lock($nom, $timeout = 0) {
	global $spip_mysql_db, $table_prefix;
	if ($table_prefix) $nom = "$table_prefix:$nom";
	if ($spip_mysql_db) $nom = "$spip_mysql_db:$nom";

	// Changer de nom toutes les heures en cas de blocage MySQL (ca arrive)
	define('_LOCK_TIME', intval(time()/3600-316982));
	$nom .= _LOCK_TIME;

	$q = spip_query("SELECT GET_LOCK(" . _q($nom) . ", $timeout) AS n");
	$q = @sql_fetch($q);
	if (!$q) spip_log("pas de lock sql pour $nom");
	return $q['n'];
}

// http://doc.spip.org/@spip_release_lock
function spip_release_lock($nom) {
	global $spip_mysql_db, $table_prefix;
	if ($table_prefix) $nom = "$table_prefix:$nom";
	if ($spip_mysql_db) $nom = "$spip_mysql_db:$nom";

	@spip_query("SELECT RELEASE_LOCK(" . _q($nom . _LOCK_TIME) . ")");
}

// http://doc.spip.org/@spip_sql_version
function spip_sql_version() {
	$row = sql_fetch(spip_query("SELECT version() AS n"));
	return ($row['n']);
}

// http://doc.spip.org/@test_sql_int
function test_sql_int($type)
{
	return (strpos($type, 'bigint') === 0
	OR strpos($type, 'int') === 0
	OR strpos($type, 'tinyint') === 0);
}

// donner le character set sql fonction de celui utilise par spip
// $skip_verif permet de ne pas faire de verif a l'install car la bd n'est pas configuree
// le script d'install se charge des verif lui meme
// http://doc.spip.org/@spip_sql_character_set
function spip_sql_character_set($charset, $skip_verif=false){
	$sql_charset_coll = array(
	'cp1250'=>array('charset'=>'cp1250','collation'=>'cp1250_general_ci'),
	'cp1251'=>array('charset'=>'cp1251','collation'=>'cp1251_general_ci'),
	'cp1256'=>array('charset'=>'cp1256','collation'=>'cp1256_general_ci'),
	
	'iso-8859-1'=>array('charset'=>'latin1','collation'=>'latin1_swedish_ci'),
	//'iso-8859-6'=>array('charset'=>'latin1','collation'=>'latin1_swedish_ci'),
	'iso-8859-9'=>array('charset'=>'latin5','collation'=>'latin5_turkish_ci'),
	//'iso-8859-15'=>array('charset'=>'latin1','collation'=>'latin1_swedish_ci'),
	
	'utf-8'=>array('charset'=>'utf8','collation'=>'utf8_general_ci')
	);
	if (isset($sql_charset_coll[$charset])){
		if ($skip_verif)
			return $sql_charset_coll[$charset];
		// verifier que le character set vise est bien supporte par mysql
		$res = spip_query($q="SHOW CHARACTER SET LIKE "._q($sql_charset_coll[$charset]['charset']));
		if ($res AND ($row = sql_fetch($res)))
			return $sql_charset_coll[$charset];
	}

	return false;
}

?>
