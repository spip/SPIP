<?php

include ("ecrire/inc_version.php3");
include_ecrire ("inc_connect.php3");
include_ecrire ("inc_meta.php3");
include_ecrire ("inc_session.php3");


if (! strpos($redirect,'?')) $redirect .='?';

// si demande auth_http
if ($essai_auth_http == 'oui') {
	include_ecrire('inc_session.php3');
	if (! verifier_php_auth()) {
		ask_php_auth("<b>Connexion refus&eacute;e.</b><p>(Login ou mot de passe incorrect.)<p>[<a href='./'>Retour au site public</a>] [<a href='./spip_cookie.php3?essai_auth_http=oui&redirect=./ecrire/'>Nouvelle tentative</a>] [<a href='./ecrire/'>espace priv&eacute</a>]");
	} else {
		@header("Location: $redirect&bonjour=oui");
	}
	exit;
}
// si demande logout auth_http
else if ($essai_auth_http == 'logout') {
	include_ecrire('inc_session.php3');
	ask_php_auth("<b>D&eacute;connexion effectu&eacute;e.</b><p>(V&eacute;rifiez toutefois que votre navigateur n'a pas m&eacute;moris&eacute; votre mot de passe...)<p>[<a href='./'>Retour au site public</a>] [<a href='./spip_cookie.php3?essai_auth_http=oui&redirect=./ecrire/'>test navigateur/reconnexion</a>] [<a href='./ecrire/'>espace priv&eacute</a>]");
	exit;
}

// rejoue le cookie pour renouveler spip_session
if ($change_session == "oui") {
	if (verifier_session($spip_session)) {
		$cookie = creer_cookie_session($auteur_session);
		supprimer_session($spip_session);
		if ($zap_session)
			zap_sessions($auteur_session['login'], true);
//		setcookie ('spip_session', $spip_session, time() - 24 * 7 * 3600);
		setcookie('spip_session', $cookie);
		@header('Content-Type: image/gif');
		@header('Expires: 0');
		@header('Cache-Control: no-cache');
		@header('Pragma: no-cache');
		@readfile('ecrire/img_pack/rien.gif');
		exit;
	}
}

// zapper les mauvaises sessions
if ($zap_session && verifier_session($spip_session)){
	zap_sessions($auteur_session['login'], true);
}

// tentative de login
if ($cookie_session == "non") {
	supprimer_session($spip_session);
	setcookie('spip_session', $spip_session, time() - 3600 * 24);
}
else if ($essai_login == "oui") {
	// recuperer le login passe en champ hidden
	if ($session_login_hidden AND ! $session_login)	$session_login=$session_login_hidden;	

	// verifier l'auteur
	$login = addslashes($session_login);
	if ($session_password_md5) {
		$md5pass = $session_password_md5;
		$md5next = $next_session_password_md5;
	} else {
		$query = "SELECT * FROM spip_auteurs WHERE login='$login'";
		$result = spip_query($query);
		if ($row = mysql_fetch_array($result)) {
			$md5pass = md5($row['alea_actuel'] . $session_password);
			$md5next = md5($row['alea_futur'] . $session_password);
		}
	}

	$query = "SELECT * FROM spip_auteurs WHERE login='$login' AND pass='$md5pass' AND statut<>'5poubelle'";
	$result = spip_query($query);

	if ($row_auteur = mysql_fetch_array($result)) {
		if ($row_auteur['statut'] == 'nouveau') { // nouvel inscrit
			spip_query ("UPDATE spip_auteurs SET statut='1comite' WHERE login='$login'");
			$row_auteur['statut'] = '1comite';
		}

		if ($row_auteur['statut'] == '0minirezo') { // force le cookie pour les admins
			$cookie_admin = "@".$row_auteur['login'];
		}
		$cookie_session = creer_cookie_session($row_auteur);
		setcookie('spip_session', $cookie_session, time() + 3600 * 24 * 7);
	
		// ici on fait tourner le codage du pass dans la base
		// retournera une erreur si la base n'est pas mise a jour...
		$nouvel_alea_futur = creer_uniqid();
		$query = "UPDATE spip_auteurs
			SET alea_actuel = alea_futur,
				pass = '$md5next',
				alea_futur = '$nouvel_alea_futur'
			WHERE login='$login'";
		@spip_query($query);
		$redirect .= '&bonjour=oui';
	}
	else if ($redirect) {
		@header("Location: $redirect&login=$login&erreur=pass");
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
if (!$redirect) $redirect = './';

@header("Location: $redirect");

?>
