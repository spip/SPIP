<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;
include_spip('base/objets');

//
// Utilitaires indispensables autour des serveurs SQL
//

// API d'appel aux bases de donnees:
// on charge le fichier config/$serveur ($serveur='connect' pour le principal)
// qui est cense initaliser la connexion en appelant spip_connect_db
// laquelle met dans la globale db_ok la description de la connexion
// On la memorise dans un tableau pour permettre plusieurs serveurs.
// A l'installation, il faut simuler l'existence de ce fichier

// http://doc.spip.org/@spip_connect
function spip_connect($serveur='', $version='') {
	global $connexions, $spip_sql_version;

	$serveur = !is_string($serveur) ? '' : strtolower($serveur);
	$index = $serveur ? $serveur : 0;
	if (!$version) $version = $spip_sql_version;
	if (isset($connexions[$index][$version])) return $connexions[$index];

	include_spip('base/abstract_sql');
	$install = (_request('exec') == 'install');

	// Premiere connexion ?
	if (!($old = isset($connexions[$index]))) {
		$f = (!preg_match('/^[\w\.]*$/', $serveur))
		? '' // nom de serveur mal ecrit
		: ($serveur ?
		   ( _DIR_CONNECT. $serveur . '.php') // serveur externe
		    : (_FILE_CONNECT ? _FILE_CONNECT // serveur principal ok
		       : ($install ? _FILE_CONNECT_TMP // init du serveur principal
			 : ''))); // installation pas faite

		unset($GLOBALS['db_ok']);
		unset($GLOBALS['spip_connect_version']);
		if ($f) { 
			if (is_readable($f)) { 
				include($f);
			} elseif ($serveur AND !$install) {
				// chercher une declaration de serveur dans le path
				// qui pourra un jour servir a declarer des bases sqlite
				// par des plugins. Et sert aussi aux boucles POUR.
				find_in_path("$serveur.php",'connect/',true);
			}
		}
		if (!isset($GLOBALS['db_ok'])) {
		  // fera mieux la prochaine fois
			if ($install) return false;
			if ($f AND is_readable($f))
				spip_log("spip_connect: fichier de connexion '$f' OK.", _LOG_INFO_IMPORTANTE);
			else
				spip_log("spip_connect: fichier de connexion '$f' non trouve", _LOG_INFO_IMPORTANTE);
			spip_log("spip_connect: echec connexion ou serveur $index mal defini dans '$f'.", _LOG_HS);
			// ne plus reessayer si ce n'est pas l'install
			return $connexions[$index]=false;
		}
		$connexions[$index] = $GLOBALS['db_ok'];
	}
	// si la connexion a deja ete tentee mais a echoue, le dire!
	if (!$connexions[$index]) return false;

	// la connexion a reussi ou etait deja faite.
	// chargement de la version du jeu de fonctions
	// si pas dans le fichier par defaut
	$type = $GLOBALS['db_ok']['type'];
	$jeu = 'spip_' . $type .'_functions_' . $version;
	if (!isset($GLOBALS[$jeu])) {
		if (!find_in_path($type . '_' . $version . '.php', 'req/', true)){
		  spip_log("spip_connect: serveur $index version '$version' non defini pour '$type'", _LOG_HS);
			// ne plus reessayer
			return $connexions[$index][$version] = array();
		}
	}
	$connexions[$index][$version] = $GLOBALS[$jeu];
	if ($old) return $connexions[$index];

	$connexions[$index]['spip_connect_version'] = isset($GLOBALS['spip_connect_version']) ? $GLOBALS['spip_connect_version'] : 0;

	// initialisation de l'alphabet utilise dans les connexions SQL
	// si l'installation l'a determine.
	// Celui du serveur principal l'impose aux serveurs secondaires
	// s'ils le connaissent

	if (!$serveur) {
		$charset = spip_connect_main($GLOBALS[$jeu]);
		if (!$charset) {
			unset($connexions[$index]);
			spip_log("spip_connect: absence de charset", _LOG_AVERTISSEMENT);
			return false;
		}
	} else	{
		// spip_meta n'existe pas toujours dans la base
		// C'est le cas d'un dump sqlite par exemple 
		if ($connexions[$index]['spip_connect_version']
		AND sql_showtable('spip_meta', true, $serveur)
		AND $r = sql_getfetsel('valeur', 'spip_meta', "nom='charset_sql_connexion'",'','','','',$serveur))
			$charset = $r;
		else $charset = -1;
	}
	if ($charset != -1) {
		$f = $GLOBALS[$jeu]['set_charset'];
		if (function_exists($f))
			$f($charset, $serveur);
	}
	return $connexions[$index];
}

function spip_sql_erreur($serveur='')
{
	$connexion = spip_connect($serveur);
	$e = sql_errno($serveur);
	$t = (isset($connexion['type']) ? $connexion['type'] : 'sql');
	$m = "Erreur $e de $t: " . sql_error($serveur) . "\n" . $connexion['last'];
	$f = $t . $serveur;
	spip_log($m, $f.'.'._LOG_ERREUR);
}

// Cette fonction ne doit etre appelee qu'a travers la fonction sql_serveur
// definie dans base/abstract_sql
// Elle existe en tant que gestionnaire de versions,
// connue seulement des convertisseurs automatiques

// http://doc.spip.org/@spip_connect_sql
function spip_connect_sql($version, $ins='', $serveur='', $cont=false) {
	$desc = spip_connect($serveur, $version);
	if (function_exists($f = @$desc[$version][$ins])) return $f;
	if ($cont) return $desc;
	if ($ins)
		spip_log("Le serveur '$serveur' version $version n'a pas '$ins'", _LOG_ERREUR);
	include_spip('inc/minipres');
	echo minipres(_T('info_travaux_titre'), _T('titre_probleme_technique'));
	exit;
}

/**
 * Fonction appelee par le fichier cree dans config/ a l'instal'.
 * Il contient un appel direct a cette fonction avec comme arguments
 * les identifants de connexion.
 * Si la connexion reussit, la globale db_ok memorise sa description.
 * C'est un tableau egalement retourne en valeur, pour les appels a l'install'
 *
 * http://doc.spip.org/@spip_connect_db
 *
 * @param string $host
 * @param string $port
 * @param string $login
 * @param string $pass
 * @param string $db
 * @param string $type
 * @param string $prefixe
 * @param string $auth
 * @return array
 */
function spip_connect_db($host, $port, $login, $pass, $db='', $type='mysql', $prefixe='', $auth='') {
	global $db_ok;

	// temps avant nouvelle tentative de connexion
	// suite a une connection echouee
	if (!defined('_CONNECT_RETRY_DELAY'))
		define('_CONNECT_RETRY_DELAY',30);

	$f = "";
	// un fichier de identifiant par combinaison (type,host,port,db)
	// pour ne pas declarer tout indisponible d'un coup
	// si en cours d'installation ou si db=@test@ on ne pose rien
	// car c'est un test de connexion
	if (!defined('_ECRIRE_INSTALL') AND !$db=="@test@")
		$f = _DIR_TMP . $type . '.' . substr(md5($host.$port.$db),0,8) . '.out';
	elseif ($db=='@test@')
		$db = '';

	if ($f
		AND @file_exists($f)
	  AND (time() - @filemtime($f) < _CONNECT_RETRY_DELAY)) {
		spip_log( "Echec : $f recent. Pas de tentative de connexion", _LOG_HS);
		return;
	}

	if (!$prefixe)
		$prefixe = isset($GLOBALS['table_prefix'])
		? $GLOBALS['table_prefix'] : $db;
	$h = charger_fonction($type, 'req', true);
	if (!$h) {
		spip_log( "les requetes $type ne sont pas fournies", _LOG_HS);
		return;
	}
	if ($g = $h($host, $port, $login, $pass, $db, $prefixe)) {

		if (!is_array($auth)) {
			// compatibilite version 0.7 initiale
			$g['ldap'] = $auth;
			$auth = array('ldap' => $auth);
		}
		$g['authentification'] = $auth;
		$g['type'] = $type;
		return $db_ok = $g;
	}
	// En cas d'indisponibilite du serveur, eviter de le bombarder
	if ($f) {
		@touch($f);
		spip_log( "Echec connexion serveur $type : host[$host] port[$port] login[$login] base[$db]", $type.'.'._LOG_HS);
	}
}

// Premiere connexion au serveur principal:
// retourner le charset donnee par la table principale
// mais verifier que le fichier de connexion n'est pas trop vieux
// Version courante = 0.7 
// La version 0.7 indique un serveur d'authentification comme 8e arg
// La version 0.6 indique le prefixe comme 7e arg
// La version 0.5 indique le serveur comme 6e arg
//
// La version 0.0 (non numerotee) doit etre refaite par un admin
// les autres fonctionnent toujours, meme si :
// - la version 0.1 est moins performante que la 0.2
// - la 0.2 fait un include_ecrire('inc_db_mysql.php3').

// http://doc.spip.org/@spip_connect_main
function spip_connect_main($connexion)
{
	if ($GLOBALS['spip_connect_version']< 0.1 AND _DIR_RESTREINT){
		include_spip('inc/headers');
		redirige_url_ecrire('upgrade', 'reinstall=oui');
	}

	if (!($f = $connexion['select'])) return false;
	// en cas d'erreur select retourne la requette (is_string=true donc)
	if (!$r = $f('valeur','spip_meta', "nom='charset_sql_connexion'")
	  OR is_string($r))
		return false;
	if (!($f = $connexion['fetch'])) return false;
	$r = $f($r);
	return ($r['valeur'] ? $r['valeur'] : -1);
}

// compatibilite
function spip_connect_ldap($serveur='') {
	include_spip('auth/ldap');
	return auth_ldap_connect($serveur);
}

// Echappement d'une valeur (num, string, array) sous forme de chaine PHP
// pour un array(1,'a',"a'") renvoie la chaine "'1','a','a\''"
// Usage sql un peu deprecie, a remplacer par sql_quote()
// http://doc.spip.org/@_q
function _q ($a) {
	return (is_numeric($a)) ? strval($a) :
		(!is_array($a) ? ("'" . addslashes($a) . "'")
		 : join(",", array_map('_q', $a)));
}


// Recuperer le nom de la table de jointure xxxx sur l'objet yyyy
// http://doc.spip.org/@table_jointure
function table_jointure($x, $y) {
	$trouver_table = charger_fonction('trouver_table', 'base');
	$xdesc = $trouver_table(table_objet($x));
	$ydesc = $trouver_table(table_objet($y));
	$ix = @$xdesc['key']["PRIMARY KEY"];
	$iy = @$ydesc['key']["PRIMARY KEY"];
	if ($table = $ydesc['tables_jointures'][$ix]) return $table;
	if ($table = $xdesc['tables_jointures'][$iy]) return $table;
	return '';
}

/**
 * Echapper les textes entre ' ' ou " " d'une requete SQL
 * avant son pre-traitement
 * On renvoi la query sans textes et les textes separes, dans
 * leur ordre d'apparition dans la query
 *
 * @param string $query
 * @return array
 */
function query_echappe_textes($query){
	static $codeEchappements = array("''"=>"\x1@##@\x1", "\'"=>"\x2@##@\x2", "\\\""=>"\x3@##@\x3");
	$query = str_replace(array_keys($codeEchappements), array_values($codeEchappements), $query);
	if (preg_match_all("/((['])[^']*(\\2))|(([\"])[^\"]*(\\5))/S",$query,$textes)){
		$textes = reset($textes); // indice 0 du match
		switch(count($textes)){
			case 0:$replace=array();break;
			case 1:$replace=array('%1$s');break;
			case 2:$replace=array('%1$s','%2$s');break;
			case 3:$replace=array('%1$s','%2$s','%3$s');break;
			case 4:$replace=array('%1$s','%2$s','%3$s','%4$s');break;
			case 5:$replace=array('%1$s','%2$s','%3$s','%4$s','%5$s');break;
			default:
				$replace = range(1,count($textes));
				$replace = '%'.implode('$s,%',$replace).'$s';
				$replace = explode(',',$replace);
				break;
		}
		$query = str_replace($textes,$replace,$query);
	}
	else
		$textes = array();

	return array($query, $textes);
}

/**
 * Reinjecter les textes d'une requete SQL a leur place initiale,
 * apres traitement de la requete
 *
 * @param string $query
 * @param array $textes
 * @return string
 */
function query_reinjecte_textes($query, $textes){
	static $codeEchappements = array("''"=>"\x1@##@\x1", "\'"=>"\x2@##@\x2", "\\\""=>"\x3@##@\x3");
	# debug de la substitution
	#if (($c1=substr_count($query,"%"))!=($c2=count($textes))){
	#	spip_log("$c1 ::". $query,"tradquery"._LOG_ERREUR);
	#	spip_log("$c2 ::". var_export($textes,1),"tradquery"._LOG_ERREUR);
	#	spip_log("ini ::". $qi,"tradquery"._LOG_ERREUR);
	#}
	switch (count($textes)){
		case 0:break;
		case 1:$query=sprintf($query,$textes[0]);break;
		case 2:$query=sprintf($query,$textes[0],$textes[1]);break;
		case 3:$query=sprintf($query,$textes[0],$textes[1],$textes[2]);break;
		case 4:$query=sprintf($query,$textes[0],$textes[1],$textes[2],$textes[3]);break;
		case 5:$query=sprintf($query,$textes[0],$textes[1],$textes[2],$textes[3],$textes[4]);break;
		default:
			array_unshift($textes,$query);
			$query = call_user_func_array('sprintf',$textes);
			break;
	}

	$query = str_replace(array_values($codeEchappements), array_keys($codeEchappements), $query);

	return $query;
}

// Pour compatibilite. Ne plus utiliser.
// http://doc.spip.org/@spip_query
function spip_query($query, $serveur='') {
	global $spip_sql_version;
	$f = spip_connect_sql($spip_sql_version, 'query', $serveur, true);
	return function_exists($f) ? $f($query, $serveur) : false;
}

?>
