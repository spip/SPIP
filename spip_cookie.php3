<?php

include ("ecrire/inc_version.php3");
include_ecrire ("inc_connect.php3");
include_ecrire ("inc_meta.php3");
include_ecrire ("inc_session.php3");

if ($url = $HTTP_POST_VARS['url'])
	$cible = new Link($url);
else
	$cible = new Link('ecrire/');

// rejoue le cookie pour renouveler spip_session
if ($change_session == 'oui') {
	if (verifier_session($spip_session)) {
		$cookie = creer_cookie_session($auteur_session);
		supprimer_session($spip_session);
		setcookie('spip_session', $cookie);
		@header('Content-Type: image/gif');
		@header('Expires: 0');
		@header("Cache-Control: no-store, no-cache, must-revalidate");
		@header('Pragma: no-cache');
		@header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		@readfile('ecrire/img_pack/rien.gif');
		exit;
	}
}


// tentative de connexion en auth_http
if ($essai_auth_http) {
	include_local ("inc-login.php3");
	auth_http($cible, $essai_auth_http);
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
		}
		if ($PHP_AUTH_USER) {
			include_local ("inc-login.php3");
			auth_http($cible, 'logout');
		}
		unset ($auteur_session);
	}
	if ($url)
		@Header("Location: ".urldecode($url));
	else
		@Header("Location: ./");
	exit;
}


// tentative de login
unset ($cookie_session);
if ($essai_login == "oui") {
	// recuperer le login passe en champ hidden
	if ($session_login_hidden AND !$session_login)
		$session_login = $session_login_hidden;

	// verifier l'auteur
	$login = addslashes($session_login);
	if ($session_password_md5) { // mot passe en md5
		$md5pass = $session_password_md5;
		$md5next = $next_session_password_md5;
	}
	else { // mot passe en clair
		$query = "SELECT * FROM spip_auteurs WHERE login='$login' AND statut!='5poubelle'";
		$result = spip_query($query);
		if ($row = mysql_fetch_array($result)) {
			$md5pass = md5($row['alea_actuel'] . $session_password);
			$md5next = md5($row['alea_futur'] . $session_password);
		}
	}

	$query = "SELECT * FROM spip_auteurs WHERE login='$login' AND pass='$md5pass' AND statut<>'5poubelle'";
	echo "$query<p>";

	$result = spip_query($query);

	if ($row_auteur = mysql_fetch_array($result)) { // login reussi
		if ($row_auteur['statut'] == 'nouveau') { // nouvel inscrit
			spip_query ("UPDATE spip_auteurs SET statut='1comite' WHERE login='$login'");
			$row_auteur['statut'] = '1comite';
		}

		if ($row_auteur['statut'] == '0minirezo') // force le cookie pour les admins
			$cookie_admin = "@".$row_auteur['login'];

		$cookie_session = creer_cookie_session($row_auteur);
	
		// fait tourner le codage du pass dans la base
		$nouvel_alea_futur = creer_uniqid();
		$query = "UPDATE spip_auteurs
			SET alea_actuel = alea_futur,
				pass = '$md5next',
				alea_futur = '$nouvel_alea_futur'
			WHERE login='$login'";
		@spip_query($query);
		if (ereg("ecrire/", $cible->getUrl()))
			$cible->addVar('bonjour','oui');
	}
	else {
		if (ereg("ecrire/", $cible->getUrl())) {
			$cible = new Link("./spip_login.php3");
		}
		$cible->addVar('var_login', $login);
		if ($session_password || $session_password_md5)
			$cible->addVar('var_erreur', 'pass');
		$cible->addVar('var_url', urldecode($url));
	}
}


// cookie d'admin ?
if ($cookie_admin == "non") {
	setcookie('spip_admin', $spip_admin, time() - 3600 * 24);
	$cible->delVar('var_login');
}
else if ($cookie_admin AND $spip_admin != $cookie_admin) {
	setcookie('spip_admin', $cookie_admin, time() + 3600 * 24 * 14);
}

// cookie de session ?
if ($cookie_session)
	setcookie('spip_session', $cookie_session);

// redirection
@header("Location: " . $cible->getUrl());

?>
