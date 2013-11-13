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
 * Ce fichier contient les fonctions gerant
 * les instructions SQL pour MySQL
 *
 * @package SPIP\SQL\MySQL
 */
 
if (!defined('_ECRIRE_INC_VERSION')) return;

// fonction pour la premiere connexion a un serveur MySQL

// http://doc.spip.org/@req_mysql_dist
/**
 * @param $host
 * @param $port
 * @param $login
 * @param $pass
 * @param string $db
 * @param string $prefixe
 * @return array|bool
 */

function req_mysql_dist($host, $port, $login, $pass, $db='', $prefixe='')
{
	if (!charger_php_extension('mysql')) return false;
	
	$link = Sql::connect($host, $port, $login, $pass, true);
	if (!$link) {
		spip_log('Echec mysql_connect. Erreur : ' . mysql_error(),'mysql.'._LOG_HS);
		return false;
	}
	
	$last = '';
	if (!$db) {
		$ok = $link;
		$db = 'spip';
	} else {
		$ok = Sql::selectdb($db);
		if (defined('_MYSQL_SET_SQL_MODE') 
		  OR defined('_MYSQL_SQL_MODE_TEXT_NOT_NULL') // compatibilite
		  )
			Sql::query($last = "set sql_mode=''");
	}
	spip_log("Connexion vers $host, base $db, prefixe $prefixe " . ($ok ? "operationnelle sur $link" : 'impossible'), _LOG_DEBUG);

	return !$ok ? false : array(
		'db' => $db,
		'last' => $last,
		'prefixe' => $prefixe ? $prefixe : $db,
		'link' => $GLOBALS['mysql_rappel_connexion'] ? $link : false,
		);
}

$GLOBALS['spip_mysql_functions_1'] = array(
		// association de chaque nom http d'un charset aux couples MySQL 
		'charsets' => array(
			'cp1250'=>array('charset'=>'cp1250','collation'=>'cp1250_general_ci'),
			'cp1251'=>array('charset'=>'cp1251','collation'=>'cp1251_general_ci'),
			'cp1256'=>array('charset'=>'cp1256','collation'=>'cp1256_general_ci'),
			'iso-8859-1'=>array('charset'=>'latin1','collation'=>'latin1_swedish_ci'),
			//'iso-8859-6'=>array('charset'=>'latin1','collation'=>'latin1_swedish_ci'),
			'iso-8859-9'=>array('charset'=>'latin5','collation'=>'latin5_turkish_ci'),
			//'iso-8859-15'=>array('charset'=>'latin1','collation'=>'latin1_swedish_ci'),
			'utf-8'=>array('charset'=>'utf8','collation'=>'utf8_general_ci'))
		);
		
		
// pour compatibilite. Ne plus utiliser.
// http://doc.spip.org/@calcul_mysql_in
/**
 * @param $val
 * @param $valeurs
 * @param string $not
 * @return string
 */
function calcul_mysql_in($val, $valeurs, $not='')
{
	if (is_array($valeurs))
		$valeurs = join(',', array_map('_q', $valeurs));
	elseif ($valeurs[0]===',') $valeurs = substr($valeurs,1);

	if (!strlen(trim($valeurs))) return ($not ? "0=0" : '0=1');
	return Sql::in($val, $valeurs, $not);
}

// Ces deux fonctions n'ont pas d'equivalent exact PostGres
// et ne sont la que pour compatibilite avec les extensions de SPIP < 1.9.3

//
// Poser un verrou local a un SPIP donne
// Changer de nom toutes les heures en cas de blocage MySQL (ca arrive)
//
// http://doc.spip.org/@spip_get_lock
/**
 * @param $nom
 * @param int $timeout
 * @return mixed
 */
function spip_get_lock($nom, $timeout = 0)
{
	define('_LOCK_TIME', intval(time()/3600-316982));

	$connexion = &$GLOBALS['connexions'][0];
	$bd = $connexion['db'];
	$prefixe = $connexion['prefixe'];
	$nom = "$bd:$prefixe:$nom" .  _LOCK_TIME;

	$connexion['last'] = $q = "SELECT GET_LOCK(" . _q($nom) . ", $timeout) AS n";
	$q = Sql::fetch(Sql::query($q));
	if (!$q) spip_log("pas de lock sql pour $nom", _LOG_ERREUR);
	return $q['n'];
}

// http://doc.spip.org/@spip_release_lock
/**
 * @param $nom
 */
function spip_release_lock($nom)
{
	$connexion = &$GLOBALS['connexions'][0];
	$bd = $connexion['db'];
	$prefixe = $connexion['prefixe'];
	$nom = "$bd:$prefixe:$nom" . _LOCK_TIME;

	$connexion['last'] = $q = "SELECT RELEASE_LOCK(" . _q($nom) . ")";
	@Sql::query($q);
}

// Renvoie false si on n'a pas les fonctions mysql (pour l'install)
// http://doc.spip.org/@spip_versions_mysql
/**
 * @return bool
 */
function spip_versions_mysql()
{
	charger_php_extension('mysql');
	return class_exists('mysql');
}

// Tester si mysql ne veut pas du nom de la base dans les requetes

// http://doc.spip.org/@test_rappel_nom_base_mysql
/**
 * @param $server_db
 * @return string
 */
function test_rappel_nom_base_mysql($server_db)
{
	$GLOBALS['mysql_rappel_nom_base'] = true;
	Sql::delete('spip_meta', "nom='mysql_rappel_nom_base'", $server_db);
	$ok = Sql::query("INSERT INTO spip_meta (nom,valeur) VALUES ('mysql_rappel_nom_base', 'test')", $server_db);

	if ($ok) {
		Sql::delete('spip_meta', "nom='mysql_rappel_nom_base'", $server_db);
		return '';
	} else {
		$GLOBALS['mysql_rappel_nom_base'] = false;
		return "\$GLOBALS['mysql_rappel_nom_base'] = false; ".
		"/* echec de test_rappel_nom_base_mysql a l'installation. */\n";
	}
}

// http://doc.spip.org/@test_sql_mode_mysql
/**
 * @param $server_db
 * @return string
 */
function test_sql_mode_mysql($server_db)
{
	$res = Sql::select("version() as v",'','','','','','',$server_db);
	$row = Sql::fetch($res,$server_db);
	if (version_compare($row['v'],'5.0.0','>=')) {
		define('_MYSQL_SET_SQL_MODE',true);
		return "define('_MYSQL_SET_SQL_MODE',true);\n";
	}
	return '';
}

?>
