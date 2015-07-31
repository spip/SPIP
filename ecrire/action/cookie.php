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

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/actions');
include_spip('inc/cookie');

// http://doc.spip.org/@auth_http
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


// http://doc.spip.org/@action_cookie_dist
function action_cookie_dist()
{
  global
    $auteur_session,
    $change_session,
    $cookie_admin,
    $cookie_session,
    $essai_auth_http,
    $essai_login,
    $ignore_auth_http,
    $retour,
    $session_login,
    $session_login_hidden,
    $session_password,
    $session_password_md5,
    $session_remember,
    $spip_admin,
    $test_echec_cookie,
    $url,
    $hash,
    $var_lang,
    $var_lang_ecrire;

// rejoue le cookie pour renouveler spip_session
  if ($change_session == 'oui') {
	$session = charger_fonction('session', 'inc');
	$session(true);
	envoie_image_vide();
	return;
  }

// tentative de connexion en auth_http
if ($essai_auth_http AND !$ignore_auth_http) {
	auth_http(($url ? $url : _DIR_RESTREINT_ABS), $essai_auth_http);
	return;
}

// en cas de login sur bonjour=oui, on tente de poser un cookie
// puis de passer au login qui diagnostiquera l'echec de cookie
// le cas echeant.
if ($test_echec_cookie == 'oui') {
	spip_setcookie('spip_session', 'test_echec_cookie');
	redirige_par_entete(generer_url_public('login',
			    "var_echec_cookie=oui&url="
			    . ($url ? rawurlencode($url) : _DIR_RESTREINT_ABS), true));
}

unset ($cookie_session);
$redirect = ($url ? $url : _DIR_RESTREINT_ABS);
if ($essai_login == "oui") {
	// Recuperer le login en champ hidden
	if ($session_login_hidden AND !$session_login)
		$session_login = $session_login_hidden;

	$row_auteur = array();
	spip_connect();

	// Essayer l'authentification par MySQL
	$auth_spip = charger_fonction('auth_spip', 'inc', true);
	if ($auth_spip) $row_auteur = $auth_spip($session_login, $session_password);		

	// Marche pas: essayer l'authentification par LDAP si present
	if (!$row_auteur AND $GLOBALS['ldap_present']) {
		$auth_ldap = charger_fonction('auth_ldap', 'inc', true);
		if ($auth_ldap) $row_auteur = $auth_ldap($session_login, $session_password);
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
	        
		$session = charger_fonction('session', 'inc');
		$cookie_session = $session($row_auteur);

		if ($session_remember == 'oui')
			spip_setcookie('spip_session', $cookie_session, time() + 3600 * 24 * 14);
		else
			spip_setcookie('spip_session', $cookie_session);

		$prefs = ($row_auteur['prefs']) ? unserialize($row_auteur['prefs']) : array();
		$prefs['cnx'] = ($session_remember == 'oui') ? 'perma' : '';

		spip_query("UPDATE spip_auteurs SET prefs = " . _q(serialize($prefs)) . " WHERE id_auteur = " . $row_auteur['id_auteur']);
	}
 }

// cookie d'admin ?
if ($cookie_admin == "non") {
	if (!$retour)
		$retour = generer_url_public('login',
			'url='.rawurlencode($url), true);

	spip_setcookie('spip_admin', $spip_admin, time() - 3600 * 24);
	$redirect = parametre_url($retour,'var_login','','&');
	$redirect = parametre_url($redirect,'var_erreur','','&');
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
		$redirect = parametre_url($redirect,'lang',$var_lang,'&');
	}
 }

// changer de langue espace prive avant le login (i.e. pas authentfie)
elseif ($var_lang_ecrire) {
	include_spip('action/converser');
	action_converser_post();
 }
  redirige_par_entete($redirect, true);
}
?>
