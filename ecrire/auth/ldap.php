<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// Authentifie via LDAP et retourne la ligne SQL decrivant l'utilisateur si ok

// Attributs LDAP correspondants a ceux de SPIP, notamment pour le login
$GLOBALS['ldap_attributes'] = array(
	'login' => array('sAMAccountName', 'uid', 'login', 'userid', 'cn','sn'),
	'nom' => "cn",
	'email' => "mail", 
	'bio' => "description");

// http://doc.spip.org/@inc_auth_ldap_dist
function auth_ldap_dist ($login, $pass) {

	#spip_log("ldap $login " . ($pass ? "mdp fourni" : "mdp absent"));

	// Utilisateur connu ?
	if (!($dn = auth_ldap_search($login, $pass))) return array();

	// Si l'utilisateur figure deja dans la base, y recuperer les infos
	$r = sql_fetsel("*", "spip_auteurs", "login=" . sql_quote($login) . " AND source='ldap'");

	if ($r) return $r;

	// sinon importer les infos depuis LDAP, 

	if ($GLOBALS['meta']["ldap_statut_import"]
	AND $desc = auth_ldap_retrouver($dn)) {
	  // rajouter le statut indique  a l'install
		$desc['statut'] = $GLOBALS['meta']["ldap_statut_import"];
		$desc['login'] = $login;
		$desc['source'] = 'ldap';
		$desc['pass'] = '';

		$r = sql_insertq('spip_auteurs', $desc);
	}				

	if ($r)	return sql_fetsel("*", "spip_auteurs", "id_auteur=$r");
	spip_log("Creation de l'auteur '$login' impossible");
	return array();
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
function auth_ldap_search($login, $pass, $checkpass=true){
	// Securite anti-injection et contre un serveur LDAP laxiste
	$login_search = preg_replace("/[^-@._\s\d\w]/", "", $login); 
	if (!strlen($login_search) OR ($checkpass AND !strlen($pass)) )
		return '';

	if (!$ldap = spip_connect_ldap()) return '';

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
			if (!$checkpass) return $login;
			$dn = $info[0]['dn'];
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

function auth_ldap_retrouver($dn, $desc=array())
{
	// Lire les infos sur l'utilisateur a partir de son DN depuis LDAP

	$ldap = spip_connect_ldap();
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
function auth_ldap_retrouver_login($login)
{
	return auth_ldap_search($login,'',false) ? $login : '';
}

?>