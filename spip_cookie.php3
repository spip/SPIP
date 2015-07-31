<?php

include ("ecrire/inc_version.php3");

include_ecrire ("inc_meta.php3");
include_ecrire ("inc_session.php3");

// determiner ou l'on veut retomber
if ($url)
	$cible = new Link($url);
else
	$cible = new Link('ecrire/');

// cas particulier, logout dans l'espace public
if ($logout_public) {
    $logout = $logout_public;
	if (!$url)
		$url = 'index.php3';
}

// rejoue le cookie pour renouveler spip_session
if ($change_session == 'oui') {
	if (verifier_session($spip_session)) {
		// Attention : seul celui qui a le bon IP a le droit de rejouer,
		// ainsi un eventuel voleur de cookie ne pourrait pas deconnecter
		// sa victime, mais se ferait deconnecter par elle.
		if ($auteur_session['hash_env'] == hash_env()) {
			$auteur_session['ip_change'] = false;
			$cookie = creer_cookie_session($auteur_session);
			supprimer_session($spip_session);
			spip_setcookie('spip_session', $cookie);
		}
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
		spip_query("UPDATE spip_auteurs SET en_ligne = DATE_SUB(NOW(),INTERVAL 6 MINUTE) WHERE id_auteur = ".$auteur_session['id_auteur']);
		if ($spip_session) {
			zap_sessions($auteur_session['id_auteur'], true);
			spip_setcookie('spip_session', $spip_session, time() - 3600 * 24);
		}
		if ($PHP_AUTH_USER) {
			include_local ("inc-login.php3");
			auth_http($cible, 'logout');
		}
		unset ($auteur_session);
	}

	if (!$url)	// ecrire
		@Header("Location: ./spip_login.php3");
	else
		@Header("Location: $url");
	exit;
}


// en cas de login sur bonjour=oui, on tente de poser un cookie
// puis de passer a spip_login qui diagnostiquera l'echec de cookie
// le cas echeant.
if ($test_echec_cookie == 'oui') {
	spip_setcookie('spip_session', 'test_echec_cookie');
	$link = new Link("spip_login.php3?var_echec_cookie=oui");
	$link->addVar("var_url", $cible->getUrl());
	@header("Location: ".$link->getUrl());
	exit;
}

// Tentative de login
unset ($cookie_session);
if ($essai_login == "oui") {
	// Recuperer le login en champ hidden
	if ($session_login_hidden AND !$session_login)
		$session_login = $session_login_hidden;

	$login = $session_login;
	$pass = $session_password;

	// Recuperer le mot de passe en champ hidden
	if ($session_password_md5) { // mot passe en md5
		$md5pass = $session_password_md5;
		$md5next = $next_session_password_md5;
	}
	else if ($session_password) { // mot passe en clair
		$query = "SELECT alea_actuel, alea_futur FROM spip_auteurs WHERE login='".addslashes($login)."' AND statut!='5poubelle'";
		$result = spip_query($query);
		if ($row = spip_fetch_array($result)) {
			$md5pass = md5($row['alea_actuel'] . $session_password);
			$md5next = md5($row['alea_futur'] . $session_password);
		}
	}

	// Essayer differentes methodes d'authentification
	$auths = array('spip');
	if ($ldap_present) $auths[] = 'ldap';

	$ok = false;
	reset($auths);
	while (list(, $nom_auth) = each($auths)) {
		include_ecrire("inc_auth_".$nom_auth.".php3");
		$classe_auth = "Auth_".$nom_auth;
		$auth = new $classe_auth;
		if ($auth->init()) {
			$ok = $auth->verifier_challenge_md5($login, $md5pass, $md5next);
			if (!$ok && $session_password) $ok = $auth->verifier($login, $session_password);
		}
		if ($ok) break;
	}

	if ($ok) $ok = $auth->lire();

	if ($ok) {
		$auth->activer();

		if ($auth->login AND $auth->statut == '0minirezo') // force le cookie pour les admins
			$cookie_admin = "@".$auth->login;

		$query = "SELECT * FROM spip_auteurs WHERE login='".addslashes($login)."'";
		$result = spip_query($query);
		if ($row_auteur = spip_fetch_array($result))
			$cookie_session = creer_cookie_session($row_auteur);

		if (ereg("ecrire/", $cible->getUrl())) {
			$cible->addVar('bonjour','oui');
		}
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
	spip_setcookie('spip_admin', $spip_admin, time() - 3600 * 24);
	$cible->delVar('var_login');
	$cible->addVar('var_login', '-1');
}
else if ($cookie_admin AND $spip_admin != $cookie_admin) {
	spip_setcookie('spip_admin', $cookie_admin, time() + 3600 * 24 * 14);
}

// cookie de session ?
if ($cookie_session) {
	if ($session_remember == 'oui')
		spip_setcookie('spip_session', $cookie_session, time() + 3600 * 24 * 14);
	else
		spip_setcookie('spip_session', $cookie_session);
}

// Redirection
// Sous Apache 1.x, les cookies avec une redirection fonctionnent
// Sinon, on fait un refresh HTTP
if (ereg("^Apache", $SERVER_SOFTWARE)) {
	@header("Location: " . $cible->getUrl());
}
else {
	@header("Refresh: 0; url=" . $cible->getUrl());
	echo "<html><head>";
	echo "<meta http-equiv='Refresh' content='0; url=".$cible->getUrl()."'>";
	echo "</head>\n";
	echo "<body>Si votre navigateur n'est pas redirig&eacute;, <a href='".$cible->getUrl()."'>continuer</a>.</body></html>";
}

?>
