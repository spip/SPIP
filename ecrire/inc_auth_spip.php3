<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_AUTH_SPIP")) return;
define("_ECRIRE_INC_AUTH_SPIP", "1");

class Auth_spip {
	var $nom, $login, $email, $md5pass, $md5next, $alea_futur, $statut;

	function init() {
		return true;
	}

	function verifier_challenge_md5($login, $mdpass_actuel, $mdpass_futur) {
		$query = "SELECT * FROM spip_auteurs WHERE login='$login' AND pass='$mdpass_actuel' AND statut<>'5poubelle'";
		$result = spip_query($query);

		if ($row = spip_fetch_array($result)) {
			$this->nom = $row['nom'];
			$this->login = $row['login'];
			$this->email = $row['email'];
			$this->statut = $row['statut'];
			$this->md5pass = $mdpass_actuel;
			$this->md5next = $mdpass_futur;
			return true;
		}
		return false;
	}

	function verifier($login, $pass) {
		return false;
	}

	function lire() {
		return true;
	}

	function activer() {
		if ($this->statut == 'nouveau') { // nouvel inscrit
			spip_query("UPDATE spip_auteurs SET statut='1comite' WHERE login='".$this->login."'");
		}
		if ($this->md5next) {
			include_ecrire("inc_session.php3");
			// fait tourner le codage du pass dans la base
			$nouvel_alea_futur = creer_uniqid();
			$query = "UPDATE spip_auteurs SET alea_actuel = alea_futur, ".
				"pass = '".$this->md5next."', alea_futur = '$nouvel_alea_futur' ".
				"WHERE login='".$this->login."'";
			@spip_query($query);
		}
	}
}


?>