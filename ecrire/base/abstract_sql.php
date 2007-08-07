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

// http://doc.spip.org/@spip_abstract_serveur
function spip_abstract_serveur($ins_sql, $serveur) {

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

// Cette fonction est systematiquement appelee par les squelettes
// pour constuire une requete SQL de type "lecture" (SELECT) a partir
// de chaque boucle.
// Elle construit et exe'cute une reque^te SQL correspondant a` une balise
// Boucle ; elle notifie une erreur SQL dans le flux de sortie et termine
// le processus.
// Sinon, retourne la ressource interrogeable par spip_abstract_fetch.
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

// http://doc.spip.org/@spip_abstract_select
function spip_abstract_select (
	$select = array(), $from = array(), $where = array(),
	$groupby = '', $orderby = array(), $limit = '',
	$sousrequete = '', $having = array(),
	$table = '', $id = '', $serveur='') {

	$f = spip_abstract_serveur('select', $serveur);

	return $f($select, $from, $where,
		  $groupby, $orderby, $limit,
		  $sousrequete, $having,
		  $table, $id, $serveur);
}

// http://doc.spip.org/@spip_abstract_fetch
function spip_abstract_fetch($res, $serveur='') {
	$f = spip_abstract_serveur('fetch', $serveur);
	return $f($res);
}

// http://doc.spip.org/@spip_abstract_count
function spip_abstract_count($res, $serveur='')
{
	$f = spip_abstract_serveur('count', $serveur);
	return $f($res);
}

// http://doc.spip.org/@spip_abstract_free
function spip_abstract_free($res, $serveur='')
{
	$f = spip_abstract_serveur('free', $serveur);
	return $f($res);
}

// http://doc.spip.org/@spip_abstract_insert
function spip_abstract_insert($table, $noms, $valeurs, $serveur='')
{
	$f = spip_abstract_serveur('insert', $serveur);
	return $f($table, $noms, $valeurs);
}

// http://doc.spip.org/@spip_abstract_update
function spip_abstract_update($table, $exp, $where, $serveur='')
{
	$f = spip_abstract_serveur('update', $serveur);
	return $f($table, $exp, $where);
}

// http://doc.spip.org/@spip_abstract_delete
function spip_abstract_delete($table, $where, $serveur='')
{
	$f = spip_abstract_serveur('delete', $serveur);
	return $f($table, $where);
}

// http://doc.spip.org/@spip_abstract_showtable
function spip_abstract_showtable($table, $serveur='', $table_spip = false)
{
	if ($table_spip){
		if ($GLOBALS['table_prefix']) $table_pref = $GLOBALS['table_prefix']."_";
		else $table_pref = "";
		$table = preg_replace('/^spip_/', $table_pref, $table);
	}
	
	$f = spip_abstract_serveur('showtable', $serveur);
	return $f($table);
}

// http://doc.spip.org/@spip_abstract_create
function spip_abstract_create($nom, $champs, $cles, $autoinc=false, $temporary=false, $serveur='') {
	$f = spip_abstract_serveur('create', $serveur);
	return $f($nom, $champs, $cles, $autoinc, $temporary);
}

// http://doc.spip.org/@spip_abstract_multi
function spip_abstract_multi($sel, $lang, $serveur='')
{
  	$f = spip_abstract_serveur('multi', $serveur);
	return $f($sel, $lang);
}

// http://doc.spip.org/@spip_sql_error
function spip_sql_error($serveur='') {
  	$f = spip_abstract_serveur('error', $serveur);
	return $f();
}

# une composition tellement frequente...
// http://doc.spip.org/@spip_abstract_fetsel
function spip_abstract_fetsel(
	$select = array(), $from = array(), $where = array(),
	$groupby = '', $orderby = array(), $limit = '',
	$sousrequete = '', $having = array(),
	$table = '', $id = '', $serveur='') {
	return spip_abstract_fetch(spip_abstract_select(
$select, $from, $where,	$groupby, $orderby, $limit,
$sousrequete, $having, $table, $id, $serveur),
				   $serveur);
}

# une composition tellement frequente...
// http://doc.spip.org/@spip_abstract_countsel
function spip_abstract_countsel($from = array(), $where = array(),
	$groupby = '', $limit = '', $sousrequete = '', $having = array(),
	$serveur='') {
  	$f = spip_abstract_serveur('countsel', $serveur);
	return $f($from, $where, $groupby, $limit, $sousrequete, $having);
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
	if (in_array($nom, $table_des_tables))
	   $nom_table = 'spip_' . $nom;

	include_spip('base/serial');
	if (isset($tables_principales[$nom_table]))
		return array($nom_table, $tables_principales[$nom_table]);

	include_spip('base/auxiliaires');
	$nom_table = 'spip_' . $nom;
	if (isset($tables_auxiliaires[$nom_table]))
		return array($nom_table, $tables_auxiliaires[$nom_table]);

	if ($desc = spip_abstract_showtable($nom, '', true))
		if (isset($desc['field'])) {
			$tables_externes[$nom] = $desc;
			return array($nom, $desc);
		}

	return array($nom,array());
}

// http://doc.spip.org/@spip_num_rows
function spip_num_rows($r) {
	return spip_abstract_count($r);
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
	$q = spip_abstract_fetch($q);
	if (!$q) spip_log("pas de lock sql pour $nom");
	return $q['n'];
}

// http://doc.spip.org/@spip_release_lock
function spip_release_lock($nom) {
	global $spip_mysql_db, $table_prefix;
	if ($table_prefix) $nom = "$table_prefix:$nom";
	if ($spip_mysql_db) $nom = "$spip_mysql_db:$nom";

	spip_query("SELECT RELEASE_LOCK(" . _q($nom . _LOCK_TIME) . ")");
}

// http://doc.spip.org/@spip_sql_version
function spip_sql_version() {
	$row = spip_abstract_fetch(spip_query("SELECT version() AS n"));
	return ($row['n']);
}
?>
