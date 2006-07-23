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
	include_spip('inc/actions');
	include_spip('inc/cookie');
	action_spip_cookie_dist();
}

function auth_http($url) {

	if (verifier_php_auth())
		redirige_par_entete($url);
	else {
		ask_php_auth(_T('login_connexion_refusee'),
			     _T('login_login_pass_incorrect'),
			     _T('login_retour_site'),
			     "url=".rawurlencode($url),
			     _T('login_nouvelle_tentative'),
			     (ereg(_DIR_RESTREINT_ABS, $url)));
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
    $hash,
    $var_lang,
    $var_lang_ecrire;

// rejoue le cookie pour renouveler spip_session
  if ($change_session == 'oui') {
	$var_f = charger_fonction('session', 'inc');
	$var_f(true);
	envoie_image_vide();
	exit;
  }

// tentative de connexion en auth_http
if ($essai_auth_http AND !$ignore_auth_http) {
	auth_http(($url ? $url : _DIR_RESTREINT_ABS), $essai_auth_http);
	exit;
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

	$row_auteur = array();
	spip_connect();

	// Essayer l'authentification par MySQL
	$f = charger_fonction('auth_spip', 'inc', true);
	if ($f) $row_auteur = $f($session_login, $session_password);		

	// Marche pas: essayer l'authentification par LDAP si present
	if (!$row_auteur AND $GLOBALS['ldap_present']) {
		$f = charger_fonction('auth_ldap', 'inc', true);
		if ($f) $row_auteur = $f($session_login, $session_password);
	}

	// Marche pas, renvoyer le formulaire avec message d'erreur si 2e fois
	if (!$row_auteur) {
		if (ereg(_DIR_RESTREINT_ABS, $redirect))
			$redirect = generer_url_public('login',
				"var_login=$session_login", true);
		if ($session_password || $session_password_md5)
			$redirect = parametre_url($redirect, 'var_erreur', 'pass', '&');
		$redirect .= '&url=' . rawurlencode($url);
		spip_log("echec login: $session_login");
	} else {
		spip_log("login de $session_login vers $redirect");
		// Si on se connecte dans l'espace prive, 
		// ajouter "bonjour" (repere a peu pres les cookies desactives)
		if (ereg(_DIR_RESTREINT_ABS, $redirect)) {
			$redirect .= ((false !== strpos($redirect, "?")) ? "&" : "?")
				. 'bonjour=oui';
		}
		if ($row_auteur['statut'] == '0minirezo')
			$cookie_admin = "@".$session_login;
	        
		$var_f = charger_fonction('session', 'inc');
		$cookie_session = $var_f($row_auteur);

		if ($session_remember == 'oui')
			spip_setcookie('spip_session', $cookie_session, time() + 3600 * 24 * 14);
		else
			spip_setcookie('spip_session', $cookie_session);

		$prefs = ($row_auteur['prefs']) ? unserialize($row_auteur['prefs']) : array();
		$prefs['cnx'] = ($session_remember == 'oui') ? 'perma' : '';

		spip_query("UPDATE spip_auteurs SET prefs = " . spip_abstract_quote(serialize($prefs)) . " WHERE id_auteur = " . $row_auteur['id_auteur']);
	}
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
		if (verifier_action_auteur("cookie-var_lang_ecrire", $hash, $id_auteur)) {
			spip_query("UPDATE spip_auteurs SET lang = " . spip_abstract_quote($var_lang_ecrire) . " WHERE id_auteur = " . intval($id_auteur));
			$auteur_session['lang'] = $var_lang_ecrire;
			$var_f = charger_fonction('session', 'inc');
			$var_f($auteur_session);
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
