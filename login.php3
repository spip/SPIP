<?php

include ("ecrire/inc_version.php3");
include_local ("inc-login.php3");

if (!$url)
	$cible = new Link('ecrire/');
else
	$cible = new Link(urldecode($url));

// tentative de connexion en auth_http
if ($essai_auth_http) {
	auth_http($cible, 'login.php3', $essai_auth_http);
	exit;
}

// tentative de logout
if ($logout) {
	include_ecrire("inc_session.php3");
	verifier_visiteur();
	if ($auteur_session['login'] == $logout) {
		include_ecrire('inc_connect.php3');
		spip_query("UPDATE spip_auteurs SET en_ligne = DATE_SUB(NOW(),INTERVAL 6 MINUTE) WHERE id_auteur = ".$auteur_session['id_auteur']);
		if ($spip_session) {
			supprimer_session($spip_session);
		    setcookie('spip_session', $spip_session, time() - 3600 * 24);
		} else if ($PHP_AUTH_USER)
			auth_http($cible, 'login.php3', 'logout');
	}
}

// login standard vers l'espace prive
login($cible, "login.php3");

?>