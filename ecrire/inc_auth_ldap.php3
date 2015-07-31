<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_AUTH_LDAP")) return;
define("_ECRIRE_INC_AUTH_LDAP", "1");

class Auth_ldap {
	var $user_dn;
	var $nom, $login, $email, $pass, $statut, $bio;

	function init() {
		if (!$GLOBALS['ldap_present']) return false;
		return spip_connect_ldap();
	}

	function verifier_challenge_md5($login, $mdpass_actuel, $mdpass_futur) {
		return false;
	}

	function verifier($login, $pass) {
		global $ldap_link, $ldap_base;
		$dn = "uid=$login, $ldap_base";
		if (@ldap_bind($ldap_link, $dn, $pass)) {
			$this->user_dn = $dn;
			$this->login = $login;
			return true;
		}
		$dn = "cn=$login, $ldap_base";
		if (@ldap_bind($ldap_link, $dn, $pass)) {
			$this->user_dn = $dn;
			$this->login = $login;
			return true;
		}
		return false;
	}

	function lire() {
		global $ldap_link, $ldap_base;
		$this->nom = $this->email = $this->pass = $this->statut = '';

		if (!$this->login) return false;

		$query = "SELECT * FROM spip_auteurs WHERE login='".addslashes($this->login)."' AND source='ldap'";
		$result = spip_query($query);

		if ($row = mysql_fetch_array($result)) {
			$this->nom = $row['nom'];
			$this->email = $row['email'];
			$this->statut = $row['statut'];
			$this->bio = $row['bio'];
			return true;
		}

		$result = @ldap_read($ldap_link, $this->user_dn, "objectClass=*", array("uid", "cn", "mail", "description"));
		if (!$result) return false;
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
/*		$result = @ldap_read($ldap_link, $this->user_dn, "objectClass=*", array("userPassword"));
		if ($result) {
			$info = @ldap_get_entries($ldap_link, $result);
			if (is_array($info)) {
				$this->pass = $val[0]['userPassword'][0];
			}
		}*/
		return true;
	}

	function activer() {
		$nom = addslashes($this->nom);
		$login = addslashes($this->login);
		$email = addslashes($this->email);
		$bio = addslashes($this->bio);
		$statut = lire_meta("ldap_statut_import");

		if (!$statut) return false;

		$query = "SELECT id_auteur FROM spip_auteurs WHERE login='$login'";
		$result = spip_query($query);
		if (mysql_num_rows($result)) return false;

		$query = "INSERT IGNORE INTO spip_auteurs (source, nom, login, email, bio, statut, pass) ".
			"VALUES ('ldap', '$nom', '$login', '$email', '$bio', '$statut', '')";
		return spip_query($query);
	}
}


?>