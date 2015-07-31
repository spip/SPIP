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

class Auth_spip {
	var $id_auteur, $nom, $login, $email, $md5pass, $md5next, $alea_futur, $statut, $bio;

	function init() {
		return true;
	}

	// Verification du mot passe crypte (javascript)
	function verifier_challenge_md5($login, $mdpass_actuel, $mdpass_futur) {
		// Interdire mot de passe vide
		if ($mdpass_actuel == '') return false;

		$result = spip_query("SELECT * FROM spip_auteurs WHERE login=" . spip_abstract_quote($login) . " AND pass=" . spip_abstract_quote($mdpass_actuel) . " AND statut<>'5poubelle'");

		if ($row = spip_fetch_array($result)) {
			$this->id_auteur = $row['id_auteur'];
			$this->nom = $row['nom'];
			$this->login = $row['login'];
			$this->email = $row['email'];
			$this->statut = $row['statut'];
			$this->bio = $row['bio'];
			$this->md5pass = $mdpass_actuel;
			$this->md5next = $mdpass_futur;
			return true;
		}
		return false;
	}

	// Verification du mot passe en clair (sans javascript)
	function verifier($login, $pass) {
		// Interdire mot de passe vide
		if ($pass == '') return false;

		$result = spip_query("SELECT alea_actuel, alea_futur FROM spip_auteurs WHERE login=" . spip_abstract_quote($login));

		if ($row = spip_fetch_array($result)) {
			$md5pass = md5($row['alea_actuel'] . $pass);
			$md5next = md5($row['alea_futur'] . $pass);
			return $this->verifier_challenge_md5($login, $md5pass, $md5next);
		}
		return false;
	}

	function lire() {
		return true;
	}

	function activer() {
		include_spip('inc/auth');
		$this->statut = acces_statut($this->id_auteur, $this->statut, $this->bio);
		if ($this->md5next) {
			include_spip('inc/session');
			// fait tourner le codage du pass dans la base
			$nouvel_alea_futur = creer_uniqid();
			@spip_query("UPDATE spip_auteurs SET alea_actuel = alea_futur, pass = " . spip_abstract_quote($this->md5next) . ", alea_futur = '$nouvel_alea_futur' WHERE id_auteur=" . $this->id_auteur);

		}
	}
}

?>
