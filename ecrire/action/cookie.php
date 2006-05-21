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

function action_cookie_dist() {
	include_spip('inc/session');
	include_spip('inc/cookie');
	action_spip_cookie_dist();
}

function auth_http($url, $essai_auth_http) {
	global $_SERVER;
	if ($essai_auth_http == 'oui') {
		if (verifier_php_auth())
			redirige_par_entete($url);
		else {
			ask_php_auth(_T('login_connexion_refusee'),
			_T('login_login_pass_incorrect'), _T('login_retour_site'),
			"url=".rawurlencode($url), _T('login_nouvelle_tentative'),
			(ereg(_DIR_RESTREINT_ABS, $url)));
			exit;
		}
	}
	// si demande logout auth_http
	else if ($essai_auth_http == 'logout') {
		ask_php_auth(_T('login_deconnexion_ok'),
		_T('login_verifiez_navigateur'), _T('login_retour_public'),
		"redirect="._DIR_RESTREINT_ABS, _T('login_test_navigateur'), true);
		exit;
	}
}

function action_spip_cookie_dist()
{
  global
    $auteur_session,
    $change_session,
    $cookie_admin,
    $cookie_session,
    $essai_auth_http,
    $essai_login,
    $id_auteur,
    $ignore_auth_http,
    $ldap_present,
    $logout,
    $logout_public,
    $next_session_password_md5,
    $retour,
    $session_login,
    $session_login_hidden,
    $session_password,
    $session_password_md5,
    $session_remember,
    $spip_admin,
    $spip_session,
    $test_echec_cookie,
    $url,
    $valeur,
    $var_lang,
    $var_lang_ecrire;

// rejoue le cookie pour renouveler spip_session
if ($change_session == 'oui') {
	if (verifier_session($spip_session)) {
		// Attention : seul celui qui a le bon IP a le droit de rejouer,
		// ainsi un eventuel voleur de cookie ne pourrait pas deconnecter
		// sa victime, mais se ferait deconnecter par elle.
		if ($auteur_session['hash_env'] == hash_env()) {
			spip_log("rejoue session");
			$auteur_session['ip_change'] = false;
			$cookie = creer_cookie_session($auteur_session);
			supprimer_session($spip_session);
			spip_setcookie('spip_session', $cookie);
		}
		else
			spip_log("session non rejouee, changement d'IP ?");
	}
	envoie_image_vide();
	exit;
}

// tentative de connexion en auth_http
if ($essai_auth_http AND !$ignore_auth_http) {
	auth_http(($url ? $url : _DIR_RESTREINT_ABS), $essai_auth_http);
	exit;
}

// cas particulier, logout dans l'espace public
if ($logout_public) {
	$logout = $logout_public;
	if (!$url)  $url = $GLOBALS['meta']['adresse_site'];
 }
// tentative de logout
if ($logout) {
	if ($auteur_session['login'] == $logout) {
		spip_query("UPDATE spip_auteurs SET en_ligne = DATE_SUB(NOW(),INTERVAL 6 MINUTE) WHERE id_auteur = ".$auteur_session['id_auteur']);
		if ($spip_session) {
			zap_sessions($auteur_session['id_auteur'], true);
			spip_setcookie('spip_session', $spip_session, time() - 3600 * 24);
		}
		
		if ($_SERVER['PHP_AUTH_USER']
		AND !$ignore_auth_http
		AND verifier_php_auth()) {
			auth_http(($url ? $url : _DIR_RESTREINT_ABS), 'logout');
		}
		unset ($auteur_session);
	}
	spip_log("logout: $logout");
	redirige_par_entete($url ? $url : generer_url_public('login'));
}

// en cas de login sur bonjour=oui, on tente de poser un cookie
// puis de passer au login qui diagnostiquera l'echec de cookie
// le cas echeant.
if ($test_echec_cookie == 'oui') {
	spip_setcookie('spip_session', 'test_echec_cookie');
	redirige_par_entete(generer_url_public('login'),
			    "var_echec_cookie=oui&url="
			    . ($url ? rawurlencode($url) : _DIR_RESTREINT_ABS), true);
}

// Tentative de login
unset ($cookie_session);
$redirect = ($url ? $url : _DIR_RESTREINT_ABS);
if ($essai_login == "oui") {
	// Recuperer le login en champ hidden
	if ($session_login_hidden AND !$session_login)
		$session_login = $session_login_hidden;

	$login = $session_login;

	// Essayer differentes methodes d'authentification
	$auths = array('spip');
	spip_connect(); // pour savoir si ldap est present 
	if ($ldap_present) $auths[] = 'ldap';
	$ok = false;
	foreach ($auths as $nom_auth) {
		include_spip('inc/auth_'.$nom_auth);
		$classe_auth = "Auth_".$nom_auth;
		$auth = new $classe_auth;
		if ($auth->init()) {
			// Essayer les mots de passe md5
			$ok = $auth->verifier_challenge_md5($login, $session_password_md5, $next_session_password_md5);
			// Sinon essayer avec le mot de passe en clair
			if (!$ok && $session_password) $ok = $auth->verifier($login, $session_password);
			if ($ok)  { $auth->lire(); break; }
		}
	}

	// Si la connexion a reussi
	if ($ok) {
		// Nouveau redacteur ou visiteur inscrit par mail :
		// 'nouveau' -> '1comite' ou  '6forum'
		// Si LDAP : importer l'utilisateur vers la base SPIP
		$auth->activer();

		if ($auth->login AND $auth->statut == '0minirezo') // force le cookie pour les admins
			$cookie_admin = "@".$auth->login;

		// On est connecte : recuperer les donnees auteurs
		// poser le cookie session, puis le cas echeant
		// verifier que le statut correspond au minimum requis,
		$result = spip_query("SELECT * FROM spip_auteurs WHERE login=" . spip_abstract_quote($auth->login));

		if ($row_auteur = spip_fetch_array($result)) {
			$cookie_session = creer_cookie_session($row_auteur);
		} else
			$ok = false;

		// Si on se connecte dans l'espace prive, ajouter "bonjour" (inutilise)
		if ($ok AND ereg(_DIR_RESTREINT_ABS, $redirect)) {
			$redirect .= ((false !== strpos($redirect, "?")) ? "&" : "?")
			. 'bonjour=oui';
		}
	}

	if (!$ok) {
		if (ereg(_DIR_RESTREINT_ABS, $redirect))
			$redirect = generer_url_public('login',
				"var_login=$login", true);
		if ($session_password || $session_password_md5)
			$redirect = parametre_url($redirect, 'var_erreur', 'pass', '&');
		$redirect .= '&url=' . rawurlencode($url);
		spip_log("echec login: $login");
	}
	else
		spip_log("login: $login");
}

// cookie d'admin ?
if ($cookie_admin == "non") {
	if (!$retour)
		$retour = generer_url_public('login',
			'url='.rawurlencode($url), true);

	spip_setcookie('spip_admin', $spip_admin, time() - 3600 * 24);
	$redirect = ereg_replace("([?&])var_login=[^&]*&?", '\1', $retour);
	$redirect = ereg_replace("([?&])var_erreur=[^&]*&?", '\1', $redirect);
	$redirect .= ((false !== strpos($redirect, "?")) ? "&" : "?")
		. "var_login=-1";
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

	$prefs = ($row_auteur['prefs']) ? unserialize($row_auteur['prefs']) : array();
	$prefs['cnx'] = ($session_remember == 'oui') ? 'perma' : '';
	update_prefs_session($prefs, $row_auteur['id_auteur']);
 }

// changement de langue espace public
if ($var_lang) {
	include_spip('inc/lang');

	if (changer_langue($var_lang)) {
		spip_setcookie('spip_lang', $var_lang, time() + 365 * 24 * 3600);
		$redirect = ereg_replace("[?&]lang=[^&]*", '', $redirect);
		$redirect .= (strpos($redirect, "?")!==false ? "&" : "?") . "lang=$var_lang";
	}
}

// changer de langue espace prive (ou login)
if ($var_lang_ecrire) {
	include_spip('inc/lang');

	spip_setcookie('spip_lang_ecrire', $var_lang_ecrire, time() + 365 * 24 * 3600);
	spip_setcookie('spip_lang', $var_lang_ecrire, time() + 365 * 24 * 3600);

	if (_FILE_CONNECT AND $id_auteur) {
		if (verifier_action_auteur('var_lang_ecrire', $valeur, $id_auteur)) {
			ajouter_session($auteur_session, $spip_session, $var_lang_ecrire);
		}
	}

	$redirect = ereg_replace("[?&]lang=[^&]*", '', $redirect);
	$redirect .= (strpos($redirect, "?")!==false ? "&" : "?") . "lang=$var_lang_ecrire";
}

// Redirection
// Sous Apache, les cookies avec une redirection fonctionnent
// Sinon, on fait un refresh HTTP
if (ereg("^Apache", $GLOBALS['SERVER_SOFTWARE'])) {
	redirige_par_entete($redirect);
}
else {
	include_spip('inc/headers');
	spip_header("Refresh: 0; url=" . $redirect);
	echo "<html><head>";
	echo "<meta http-equiv='Refresh' content='0; url=".$redirect."'>";
	echo "</head>\n";
	echo "<body><a href='".$redirect."'>"._T('navigateur_pas_redirige')."</a></body></html>";
}
}

?>
