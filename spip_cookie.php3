<?php

	include ("ecrire/inc_version.php3");
	include_ecrire ("inc_connect.php3");
	include_ecrire ("inc_session.php3");


	// tentative de login
	if ($session_login !='' AND $session_password != '')
	{
		// verifie l'auteur
		$md5pass = md5($session_password);
		$query = "SELECT * FROM spip_auteurs WHERE login='$session_login' AND pass='$md5pass'";
		$result = spip_query($query);

		if ($auteur = mysql_fetch_object($result)) {
			$cookie_session = cree_cookie_session ($auteur);
			$cookie_admin = cree_cookie_admin ($auteur);
		}
	}


	// un cookie de session a poser
	if ($cookie_session == -1)
		supprime_cookie_session();
	else if ($cookie_session)
		pose_cookie_session($cookie_session, $cookie_admin);


	// cookie d'admin ?
	if ($cookie_admin == "oui") {
		setcookie('spip_admin', 'admin', time() + 3600 * 24 * 7);
	} else if ($cookie_admin == -1) {
		setcookie('spip_admin', '', time() - 3600 * 24 * 7);
	}


	// redirection
	if (!$redirect)
		$redirect = './index.php3';

	@header("Location: $redirect");

?>