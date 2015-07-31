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

// Authentifie via LDAP et retourne la ligne SQL decrivant l'utilisateur si ok

// Attributs LDAP correspondants a ceux de SPIP, notamment pour le login
$GLOBALS['ldap_attributes'] = array(
	'login' => array('sAMAccountName', 'uid', 'login', 'userid', 'cn','sn'),
	'nom' => "cn",
	'email' => "mail", 
	'bio' => "description");

// http://doc.spip.org/@inc_auth_ldap_dist
function auth_ldap_dist ($login, $pass, $serveur='') {

	#spip_log("ldap $login " . ($pass ? "mdp fourni" : "mdp absent"));

	// Utilisateur connu ?
	// si http auth, inutile de reauthentifier: cela
 	// ne marchera pas avec auth http autre que basic.
	$checkpass = isset($_SERVER["REMOTE_USER"])?false:true;
	if (!($dn = auth_ldap_search($login, $pass, $checkpass, $serveur))) return array();

	// Si l'utilisateur figure deja dans la base, y recuperer les infos
	$r = sql_fetsel("*", "spip_auteurs", "login=" . sql_quote($login) . " AND source='ldap'",'','','','',$serveur);

	if ($r) return $r;

	// sinon importer les infos depuis LDAP, 

	if ($GLOBALS['meta']["ldap_statut_import"]
	AND $desc = auth_ldap_retrouver($dn, array(), $serveur)) {
	  // rajouter le statut indique  a l'install
		$desc['statut'] = $GLOBALS['meta']["ldap_statut_import"];
		$desc['login'] = $login;
		$desc['source'] = 'ldap';
		$desc['pass'] = '';

		$r = sql_insertq('spip_auteurs', $desc,'',$serveur);
	}				

	if ($r)
		return sql_fetsel("*", "spip_auteurs", "id_auteur=".intval($r),'','','','',$serveur);

	// sinon echec
	spip_log("Creation de l'auteur '$login' impossible");
	return array();
}

/**
 * Connexion a l'annuaire LDAP
 * Il faut passer par spip_connect() pour avoir les info
 * donc potentiellement indiquer un serveur
 * meme si dans les fait cet argument est toujours vide
 *
 * @param string $serveur
 * @return string
 */
function auth_ldap_connect($serveur='') {
	include_spip('base/connect_sql');
	static $connexions_ldap = array();
	if (isset($connexions_ldap[$serveur])) return $connexions_ldap[$serveur]; 
	$connexion = spip_connect($serveur);
	if (!is_array($connexion['ldap'])) {
		if ($connexion['authentification']['ldap']) {
			$f =  _DIR_CONNECT . $connexion['authentification']['ldap'];
			unset($GLOBALS['ldap_link']);
			if (is_readable($f)) include_once($f);
			if (isset($GLOBALS['ldap_link']))
				$connexion['ldap'] = array('link' => $GLOBALS['ldap_link'],
					'base' => $GLOBALS['ldap_base']);
			else spip_log("connection LDAP $serveur mal definie dans $f");
			if (isset($GLOBALS['ldap_champs']))
				$connexion['ldap']['attributes'] = $GLOBALS['ldap_champs'];
		} else spip_log("connection LDAP $serveur inconnue");
	}
	return $connexions_ldap[$serveur]=$connexion['ldap'];
}

/**
 * Retrouver un login, et verifier son pass si demande par $checkpass
 *
 * @param string $login
 * @param sring $pass
 * @param bool $checkpass
 * @return string
 *	le login trouve ou chaine vide si non trouve
 */
function auth_ldap_search($login, $pass, $checkpass=true, $serveur=''){
	// Securite anti-injection et contre un serveur LDAP laxiste
	$login_search = preg_replace("/[^-@._\s\d\w]/", "", $login); 
	if (!strlen($login_search) OR ($checkpass AND !strlen($pass)) )
		return '';

	// verifier la connexion
	if (!$ldap = auth_ldap_connect($serveur))
		return '';

	$ldap_link = $ldap['link'];
	$ldap_base = $ldap['base'];
	$desc = $ldap['attributes'] ? $ldap['attributes'] : $GLOBALS['ldap_attributes'] ;

	$logins = is_array($desc['login']) ? $desc['login'] : array($desc['login']);

	// Tenter une recherche pour essayer de retrouver le DN
	foreach($logins as $att) {
		$result = @ldap_search($ldap_link, $ldap_base, "$att=$login_search", array("dn"));
		$info = @ldap_get_entries($ldap_link, $result);
			// Ne pas accepter les resultats si plus d'une entree
			// (on veut un attribut unique)

		if (is_array($info) AND $info['count'] == 1) {
			$dn = $info[0]['dn'];
			if (!$checkpass) return $dn;
			if (@ldap_bind($ldap_link, $dn, $pass)) return $dn;
		}
	}

	if ($checkpass AND !isset($dn)) {
		// Si echec, essayer de deviner le DN
		foreach($logins as $att) {
			$dn = "$att=$login_search, $ldap_base";
			if (@ldap_bind($ldap_link, $dn, $pass))
				return "$att=$login_search, $ldap_base";
		}
	}
	return '';
}

function auth_ldap_retrouver($dn, $desc=array(), $serveur='')
{
	// Lire les infos sur l'utilisateur a partir de son DN depuis LDAP

	if (!$ldap = spip_connect_ldap($serveur)) {
		spip_log("ldap $serveur injoignable");
		return array();
	}

	$ldap_link = $ldap['link'];
	if (!$desc) {
		$desc = $ldap['attributes'] ? $ldap['attributes'] : $GLOBALS['ldap_attributes'] ;
		unset($desc['login']);
	}
	$result = @ldap_read($ldap_link, $dn, "objectClass=*", array_values($desc));

	if (!$result) return array();

	// Recuperer les donnees du premier (unique?) compte de l'auteur
	$val = @ldap_get_entries($ldap_link, $result);
	if (!is_array($val) OR !is_array($val[0])) return array();
	$val = $val[0];

	// Convertir depuis UTF-8 (jeu de caracteres par defaut)
	include_spip('inc/charsets');

	foreach ($desc as $k => $v)
		$desc[$k] = importer_charset($val[strtolower($v)][0], 'utf-8');
	return $desc;
}


/**
 * Retrouver le login de quelqu'un qui cherche a se loger
 *
 * @param string $login
 * @return string
 */
function auth_ldap_retrouver_login($login, $serveur='')
{
	return auth_ldap_search($login, '', false, $serveur) ? $login : '';
}

?>
