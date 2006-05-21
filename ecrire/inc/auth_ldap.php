<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

class Auth_ldap {
	var $user_dn;
	var $nom, $login, $email, $pass, $statut, $bio;

	function init() {
		// Verifier la presence de LDAP
		if (!$GLOBALS['ldap_present']) return false;
		return spip_connect_ldap();
	}

	function verifier_challenge_md5($login, $mdpass_actuel, $mdpass_futur) {
		return false;
	}

	function verifier($login, $pass) {
		global $ldap_link, $ldap_base;

		// Securite, au cas ou le serveur LDAP est tres laxiste
		if (!$login || !$pass) return false;

		// Attributs testes pour egalite avec le login
		$atts = array('sAMAccountName', 'uid', 'login', 'userid', 'cn', 'sn');
		$login_search = ereg_replace("[^-@._[:space:][:alnum:]]", "", $login); // securite

		// Tenter une recherche pour essayer de retrouver le DN
		reset($atts);
		while (list(, $att) = each($atts)) {
			$filter = "$att=$login_search";
			$result = @ldap_search($ldap_link, $ldap_base, $filter, array("dn"));
			$info = @ldap_get_entries($ldap_link, $result);
			// Ne pas accepter les resultats si plus d'une entree
			// (on veut un attribut unique)
			if (is_array($info) AND $info['count'] == 1) {
				$dn = $info[0]['dn'];
				if (@ldap_bind($ldap_link, $dn, $pass)) {
					$this->user_dn = $dn;
					$this->login = $login;
					return true;
				}
			}
		}

		// Si echec, essayer de deviner le DN
		reset($atts);
		while (list(, $att) = each($atts)) {
			$dn = "$att=$login_search, $ldap_base";
			if (@ldap_bind($ldap_link, $dn, $pass)) {
				$this->user_dn = $dn;
				$this->login = $login;
				return true;
			}
		}
		return false;
	}

	function lire() {
		global $ldap_link, $ldap_base;
		$this->nom = $this->email = $this->pass = $this->statut = '';

		if (!$this->login) return false;

		// Si l'auteur existe dans la base, y recuperer les infos
		$row = spip_fetch_array(spip_query("SELECT * FROM spip_auteurs WHERE login='".addslashes($this->login)."' AND source='ldap'"));


		if ($row) {
			$this->nom = $row['nom'];
			$this->email = $row['email'];
			$this->statut = $row['statut'];
			$this->bio = $row['bio'];
			return true;
		}

		// Lire les infos sur l'auteur depuis LDAP
		$result = @ldap_read($ldap_link, $this->user_dn, "objectClass=*", array("uid", "cn", "mail", "description"));
		
		// Si l'utilisateur ne peut lire ses infos, se reconnecter avec le compte principal
		if (!$result) {
			if (spip_connect_ldap())
				$result = @ldap_read($ldap_link, $this->user_dn, "objectClass=*", array("uid", "cn", "mail", "description"));
			else
				return false;
		}
		if (!$result) return false;

		// Recuperer les donnees de l'auteur
		$info = @ldap_get_entries($ldap_link, $result);
		if (!is_array($info)) return false;
		for ($i = 0; $i < $info["count"]; $i++) {
			$val = $info[$i];
			if (is_array($val)) {
				if (!$this->nom) $this->nom = $val['cn'][0];
				if (!$this->email) $this->email = $val['mail'][0];
				if (!$this->login) $this->login = $val['uid'][0];
				if (!$this->bio) $this->bio = $val['description'][0];
			}
		}

		// Convertir depuis UTF-8 (jeu de caracteres par defaut)
		include_spip('inc/charsets');
		$this->nom = importer_charset($this->nom, 'utf-8');
		$this->email = importer_charset($this->email, 'utf-8');
		$this->login = importer_charset($this->login, 'utf-8');
		$this->bio = importer_charset($this->bio, 'utf-8');

		return true;
	}

	function activer() {
		$login = strtolower(($this->login));
		$statut = $GLOBALS['meta']["ldap_statut_import"];

		if (!$statut) return false;

		// Si l'auteur n'existe pas, l'inserer avec le statut par defaut (defini a l'install)

		$n = spip_num_rows(spip_query("SELECT id_auteur FROM spip_auteurs WHERE login='" . addslashes($login) . "'"));
		if ($n) return false;

		$n = spip_query("INSERT IGNORE INTO spip_auteurs (source, nom, login, email, bio, statut, pass) VALUES ('ldap', '" . addslashes($this->nom) . "', '" . addslashes($login) . "', '" . addslashes($this->email) . "', '" . addslashes($this->bio) . "', '$statut', '')");
		return $n;

	}
}
?>
