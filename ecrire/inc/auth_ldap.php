<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// Authentifie via LDAP et retourne la ligne SQL decrivant l'utilisateur si ok

// http://doc.spip.org/@inc_auth_ldap_dist
function inc_auth_ldap_dist ($login, $pass) {

	if (!spip_connect_ldap())
		return false;

	#spip_log("ldap $login " . ($pass ? "mdp fourni" : "mdp absent"));
	// Securite contre un serveur LDAP laxiste
	if (!$login || !$pass) return array();

	// Utilisateur connu ?
	if (!($dn = auth_ldap_search($login, $pass))) return array();

	// Si l'utilisateur figure deja dans la base, y recuperer les infos
	$result = sql_fetsel("*", "spip_auteurs", "login=" . sql_quote($login) . " AND source='ldap'");

	// sinon importer les infos depuis LDAP, 
	// avec le statut par defaut a l'install
	return $result ? $result : auth_ldap_inserer($dn, $GLOBALS['meta']["ldap_statut_import"]);
}

// http://doc.spip.org/@auth_ldap_search
function auth_ldap_search($login, $pass)
{
	$ldap = spip_connect_ldap();
	$ldap_link = $ldap['link'];
	$ldap_base = $ldap['base'];

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
			$dn = "$att=$login_search, $ldap_base";
			if (@ldap_bind($ldap_link, $dn, $pass))
				return "$att=$login_search, $ldap_base";
		}
	}
	return '';
}

// http://doc.spip.org/@auth_ldap_inserer
function auth_ldap_inserer($dn, $statut)
{
	$ldap_link = spip_connect_ldap();
	$ldap_link = $ldap_link['link'];

	// refuser d'importer n'importe qui 
	if (!$statut) return array();

	// Lire les infos sur l'uid de l'utilisateur depuis LDAP 
	$result = @ldap_read($ldap_link, $dn, "objectClass=*", array("uid", "cn", "mail", "description"));
		
	// Si ça ne marche pas, essayer avec le samaccountname
	if (!$result)
		$result = @ldap_read($ldap_link, $dn, "objectClass=*", array("samaccountname", "cn", "mail", "description"));

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

	$n = sql_insertq('spip_auteurs', array(
			'source' => 'ldap',
			'nom' => $nom,
			'login' => $login,
			'email' => $email,
			'bio' => $bio,
			'statut' => $statut,
			'pass' => ''));

	if ($n)	return sql_fetsel("*", "spip_auteurs", "id_auteur=$n");
	spip_log("Creation de l'auteur '$nom' impossible");
	return array();
}
?>
