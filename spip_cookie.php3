<?php

include ("ecrire/inc_version.php3");
include_ecrire ("inc_connect.php3");
include_ecrire ("inc_meta.php3");
include_ecrire ("inc_session.php3");


// tentative de login
if ($cookie_session == "non") {
	supprimer_session($spip_session);
	setcookie('spip_session', '', time() - 3600 * 24);
}
else if ($session_login != '') {
	// verifie l'auteur
	$md5pass = md5($session_password);
	$query = "SELECT * FROM spip_auteurs WHERE login='$session_login' AND pass='$md5pass'";
	$result = spip_query($query);

	if ($row_auteur = mysql_fetch_array($result)) {
		if ($row_auteur['statut'] == '0minirezo') { // force le cookie pour les admins
			$cookie_admin = "@".$row_auteur['login'];
		}
		$cookie_session = creer_cookie_session($row_auteur);
		setcookie('spip_session', $cookie_session, time() + 3600 * 24 * 7);
	} else { // mauvais mot de passe, tue la session
		setcookie('spip_session', '', time() - 3600 * 24);
	}
}

// cookie d'admin ?
if ($cookie_admin == "non") {
	setcookie('spip_admin', '', time() - 3600 * 24);
}
else if ($cookie_admin) {
	setcookie('spip_admin', $cookie_admin, time() + 3600 * 24 * 14);
}

// redirection
if (!$redirect) $redirect = './index.php3';

@header("Location: $redirect");

?>