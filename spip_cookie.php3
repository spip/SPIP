<?php

include ("ecrire/inc_version.php3");
include_ecrire ("inc_connect.php3");
include_ecrire ("inc_meta.php3");
include_ecrire ("inc_session.php3");


// rejoue le cookie pour renouveler spip_session
if ($rejoue==oui) {
	if (verifier_session($spip_session)) {
		$cookie = creer_cookie_session($auteur_session);
		supprimer_session($spip_session);
		setcookie ('spip_session', $spip_session, time() - 24 * 7 * 3600);
		setcookie ('spip_session', $cookie, time() + 24 * 7 * 3600);
		@header('Content-Type: text/javascript');
		echo " \n";	// ne pas renvoyer un fichier vide, ca fait ramer.
		exit;
	}
}

// tentative de login
if ($cookie_session == "non") {
	supprimer_session($spip_session);
	setcookie('spip_session', $spip_session, time() - 3600 * 24);
}
else if ($essai_login == "oui") {
	// verifie l'auteur
	if ($session_password_md5) $md5pass = $session_password_md5;
	else $md5pass = md5($session_password);
/*	echo "session_password = ".$session_password."<p>";
	echo "session_password_md5 = ".$session_password_md5."<p>";
	exit;*/

	$login = addslashes($session_login);
	$query = "SELECT * FROM spip_auteurs WHERE login='$login' AND pass='$md5pass'";
	$result = spip_query($query);

	if ($row_auteur = mysql_fetch_array($result)) {
		if ($row_auteur['statut'] == '0minirezo') { // force le cookie pour les admins
			$cookie_admin = "@".$row_auteur['login'];
		}
		$cookie_session = creer_cookie_session($row_auteur);
		setcookie('spip_session', $cookie_session, time() + 3600 * 24 * 7);
	}
	else if ($redirect_echec) {
		@header("Location: $redirect_echec");
		exit;
	}
}

// cookie d'admin ?
if ($cookie_admin == "non") {
	setcookie('spip_admin', $spip_admin, time() - 3600 * 24);
}
else if ($cookie_admin) {
	setcookie('spip_admin', $cookie_admin, time() + 3600 * 24 * 14);
}

// redirection
if (!$redirect) $redirect = './index.php3';

@header("Location: $redirect");

?>