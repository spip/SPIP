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

// Authentifie et retourne la ligne SQL decrivant l'utilisateur si ok

// http://doc.spip.org/@inc_auth_spip_dist
function inc_auth_spip_dist ($login, $pass, $md5pass="", $md5next="") {

  // si envoi non crypte, crypter maintenant
	if (!$md5pass AND $pass) {
			$result = sql_select("alea_actuel, alea_futur", "spip_auteurs", "login=" . sql_quote($login));

			if ($row = sql_fetch($result)) {
				$md5pass = md5($row['alea_actuel'] . $pass);
				$md5next = md5($row['alea_futur'] . $pass);
			}
		}
	// login inexistant ou mot de passe vide
	if (!$md5pass) return array();

	$result = sql_select("*", "spip_auteurs", "login=" . sql_quote($login) . " AND pass=" . sql_quote($md5pass) . " AND statut<>'5poubelle'");
	$row = sql_fetch($result);

	// login/mot de passe incorrect
	if (!$row) return array(); 

	if ($row['statut'] == 'nouveau') {
		include_spip('inc/auth');
		$row['statut'] = acces_statut($row['id_auteur'], $row['statut'], $row['bio']);
	}

	// fait tourner le codage du pass dans la base
	if ($md5next) {
		include_spip('inc/acces'); // pour creer_uniqid
		@sql_update('spip_auteurs', array('alea_actuel' => 'alea_futur', 'pass' => sql_quote($md5next), 'alea_futur' => sql_quote(creer_uniqid())), "id_auteur=" . $row['id_auteur']);
		// En profiter pour verifier la securite de tmp/
		verifier_htaccess(_DIR_TMP);
	}
	return $row;
}

?>
