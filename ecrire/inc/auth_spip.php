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

// Authentifie et retourne la ligne SQL decrivant l'utilisateur si ok

// http://doc.spip.org/@inc_auth_spip_dist
function inc_auth_spip_dist ($login, $pass) {

  // recuperer le cryptage par JavaScript
	$md5pass = $_POST['session_password_md5']; 
	$md5next = $_POST['next_session_password_md5'];

	  // si envoi non crypte, crypter maintenant
	if (!$md5pass AND $pass) {
			$result = spip_query("SELECT alea_actuel, alea_futur FROM spip_auteurs WHERE login=" . _q($login));

			if ($row = spip_fetch_array($result)) {
				$md5pass = md5($row['alea_actuel'] . $pass);
				$md5next = md5($row['alea_futur'] . $pass);
			}
		}
	// login inexistant ou mot de passe vide
	if (!$md5pass) return array();

	$result = spip_query("SELECT * FROM spip_auteurs WHERE login=" . _q($login) . " AND pass=" . _q($md5pass) . " AND statut<>'5poubelle'");
	$row = spip_fetch_array($result);

	// login/mot de passe incorrect
	if (!$row) return array(); 

	if ($row['statut'] == 'nouveau') {
		include_spip('inc/auth');
		$row['statut'] = acces_statut($row['id_auteur'], $row['statut'], $row['bio']);
	}

	// fait tourner le codage du pass dans la base
	if ($md5next) {
		include_spip('inc/acces'); // pour creer_uniqid
		@spip_query("UPDATE spip_auteurs SET alea_actuel = alea_futur, pass = " . _q($md5next) . ", alea_futur = '" . creer_uniqid() ."' WHERE id_auteur=" . $row['id_auteur']);
		// En profiter pour verifier la securite de ecrire/data/
		verifier_htaccess(_DIR_TMP);
	}
	return $row;
}

?>
