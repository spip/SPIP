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

// Authentifie via LDAP et retourne la ligne SQL decrivant l'utilisateur si ok

// http://doc.spip.org/@inc_auth_ldap_dist
function inc_auth_ldap_dist ($login, $pass) {

	// Securite contre un serveur LDAP laxiste
	if (!$login || !$pass) return array();

	// Serveur joignable ?
	if (!@spip_connect_ldap()) return array();

	// Utilisateur connu ?
	if (!($dn = auth_ldap_search($login, $pass))) return array();

	// Si l'utilisateur figure deja dans la base, y recuperer les infos
	$result = spip_query("SELECT * FROM spip_auteurs WHERE login=" . _q($login) . " AND source='ldap'");

	// sinon importer les infos depuis LDAP, 
	// avec le statut par defaut a l'install
	if (!spip_num_rows($result))
		$result = auth_ldap_inserer($dn, $GLOBALS['meta']["ldap_statut_import"]);
	return $result ? spip_fetch_array($result) : array(); 
}

// http://doc.spip.org/@auth_ldap_search
function auth_ldap_search($login, $pass)
{
	global $ldap_link, $ldap_base;

	// Attributs testes pour egalite avec le login
	$atts = array('sAMAccountName', 'uid', 'login', 'userid', 'cn', 'sn');
	$login_search = preg_replace("/[^-@._\s\d\w]/", "", $login); // securite

	// Tenter une recherche pour essayer de retrouver le DN
	reset($atts);
	while (list(, $att) = each($atts)) {
		$result = @ldap_search($ldap_link, $ldap_base, "$att=$login_search", array("dn"));
		$info = @ldap_get_entries($ldap_link, $result);
			// Ne pas accepter les resultats si plus d'une entree
			// (on veut un attribut unique)
		if (is_array($info) AND $info['count'] == 1) {
			$dn = $info[0]['dn'];
			if (@ldap_bind($ldap_link, $dn, $pass)) return $dn;
		}
	}

	if (!isset($dn)) {
		// Si echec, essayer de deviner le DN
		reset($atts);
		while (list(, $att) = each($atts)) {
			if (@ldap_bind($ldap_link, $dn, $pass))
				return "$att=$login_search, $ldap_base";
		}
	}
	return '';
}

// http://doc.spip.org/@auth_ldap_inserer
function auth_ldap_inserer($dn, $statut)
{
	global $ldap_link, $ldap_base;

	// refuser d'importer n'importe qui 
	if (!$statut) return false;

	// Lire les infos sur l'utilisateur depuis LDAP
	$result = @ldap_read($ldap_link, $dn, "objectClass=*", array("uid", "cn", "mail", "description"));
		
	// Si l'utilisateur ne peut lire ses infos, 
	// se reconnecter avec le compte principal
	if (!$result AND spip_connect_ldap())
		$result = @ldap_read($ldap_link, $dn, "objectClass=*", array("uid", "cn", "mail", "description"));

	if (!$result) return array();

	    // Recuperer les donnees de l'auteur
	$info = @ldap_get_entries($ldap_link, $result);
	if (!is_array($info)) return array();
	for ($i = 0; $i < $info["count"]; $i++) {
		$val = $info[$i];
		if (is_array($val)) {
				if (!$nom) $nom = $val['cn'][0];
				if (!$email) $email = $val['mail'][0];
				if (!$login) $login = $val['uid'][0];
				if (!$bio) $bio = $val['description'][0];
		}
	}

	// Convertir depuis UTF-8 (jeu de caracteres par defaut)
	include_spip('inc/charsets');
	$nom = importer_charset($nom, 'utf-8');
	$email = importer_charset($email, 'utf-8');
	$bio = importer_charset($bio, 'utf-8');
	$login = strtolower(importer_charset($login, 'utf-8'));

	include_spip('base/abstract_sql');
	$n = spip_abstract_insert('spip_auteurs', '(source, nom, login, email, bio, statut, pass)', "('ldap', " . _q($nom) . ", " . _q($login) . ", " . _q($email) . ", " . _q($bio) . ", " . _q($statut) . ", '')");

	return spip_query("SELECT * FROM spip_auteurs WHERE id_auteur=$n");
}
?>
