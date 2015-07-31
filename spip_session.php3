<?php

	include ("ecrire/inc_version.php3");
	include_ecrire ("inc_connect.php3");
	include_ecrire ("inc_session.php3");

	if ($session_login !='' AND $session_password != '') // tentative de login
	{
		// verifie l'auteur
		$md5pass = md5($session_password);
		$query = "SELECT * FROM spip_auteurs WHERE login='$session_login' AND pass='$md5pass'";
		$result = spip_query($query);

		if ($auteur = mysql_fetch_object($result)) {
			$cookie = cree_cookie_session ($auteur->id_auteur);
		}
	}

	if ($cookie == -1)
		supprime_cookie_session();
	else if ($cookie)
		pose_cookie_session ($cookie);

	if ($redirect) {
		if (ereg("\?",$redirect))
			$redirect .= '&pose_cookie=fini';
		else
			$redirect .= '?pose_cookie=fini';

		@header("Location: $redirect");

	} else 
		echo "cookie posŽ";

?>