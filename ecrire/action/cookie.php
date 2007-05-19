<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/actions');
include_spip('inc/cookie');

// http://doc.spip.org/@action_cookie_dist
function action_cookie_dist() {

	// La cible de notre operation de connexion
	$url = _request('url');
	$redirect = isset($url) ? $url : _DIR_RESTREINT_ABS;

	// rejoue le cookie pour renouveler spip_session
	if (_request('change_session') == 'oui') {
		$session = charger_fonction('session', 'inc');
		$session(true);
		spip_log("statut 204 pour " . $_SERVER['REQUEST_URI']);
		http_status(204); // No Content
		return;
	}

	// tentative de connexion en auth_http
	if (_request('essai_auth_http') AND !$GLOBALS['ignore_auth_http']) {
		auth_http($redirect);
		return;
	}

	// en cas de login sur bonjour=oui, on tente de poser un cookie
	// puis de passer au login qui diagnostiquera l'echec de cookie
	// le cas echeant.
	if (_request('test_echec_cookie') == 'oui') {
		spip_setcookie('spip_session', 'test_echec_cookie');
		redirige_par_entete(generer_url_public('login',
			"var_echec_cookie=oui&url="
			. rawurlencode($redirect), '&'));
	}


	if (_request('essai_login') == "oui") {
		// Recuperer le login en champ hidden
		if (!$session_login = _request('session_login'))
			$session_login = _request('session_login_hidden');
	
		$row_auteur = array();
		spip_connect();
	
		// Essayer l'authentification par MySQL
		$auth_spip = charger_fonction('auth_spip', 'inc', true);
		if ($auth_spip) $row_auteur = $auth_spip($session_login, _request('session_password'));
	
		// Marche pas: essayer l'authentification par LDAP si present
		if (!$row_auteur AND $GLOBALS['ldap_present']) {
			$auth_ldap = charger_fonction('auth_ldap', 'inc', true);
			if ($auth_ldap) $row_auteur = $auth_ldap($session_login, _request('session_password'));
		}
	
		// Marche pas, renvoyer le formulaire avec message d'erreur si 2e fois
		if (!$row_auteur) {
			if (strpos($redirect,_DIR_RESTREINT_ABS)!==false)
				$redirect = generer_url_public('login',
					"var_login=$session_login", true);
			if (_request('session_password')
			OR _request('session_password_md5'))
				$redirect = parametre_url($redirect, 'var_erreur', 'pass', '&');
			$redirect .= '&url=' . rawurlencode($url);
			spip_log("echec login: $session_login");
		} else {
			spip_log("login de $session_login vers $redirect");
			// Si on se connecte dans l'espace prive, 
			// ajouter "bonjour" (repere a peu pres les cookies desactives)
			if (strpos($redirect,_DIR_RESTREINT_ABS)!==false) {
				$redirect .= ((false !== strpos($redirect, "?")) ? "&" : "?")
					. 'bonjour=oui';
			}
			if ($row_auteur['statut'] == '0minirezo')
				$set_cookie_admin = "@".$session_login;
				
			$session = charger_fonction('session', 'inc');
			$cookie_session = $session($row_auteur);
	
			// La case "rester connecte quelques jours"
			$session_remember = (_request('session_remember') == 'oui') ? 'perma' : '';
			if ($session_remember)
				spip_setcookie('spip_session', $cookie_session, time() + 2 * _RENOUVELLE_ALEA);
			else
				spip_setcookie('spip_session', $cookie_session);
	
			$prefs = ($row_auteur['prefs']) ? unserialize($row_auteur['prefs']) : array();
			$prefs['cnx'] = $session_remember;
	
			spip_query("UPDATE spip_auteurs SET prefs = " . _q(serialize($prefs)) . " WHERE id_auteur = " . $row_auteur['id_auteur']);
		}
	}

	$cook = isset($_COOKIE['spip_admin']) ? $_COOKIE['spip_admin'] : '';
	// Suppression cookie d'admin ?
	if (_request('cookie_admin') == "non") {
		if (!$retour = _request('retour'))
			$retour = generer_url_public('login',
				'url='.rawurlencode($url), true);
	
		if ($cook)
			spip_setcookie('spip_admin', $cook, time() - 3600 * 24);
		$redirect = parametre_url($retour,'var_login','','&');
		$redirect = parametre_url($redirect,'var_erreur','','&');
		$redirect .= ((false !== strpos($redirect, "?")) ? "&" : "?")
			. "var_login=-1";
	}

	// Ajout de cookie d'admin
	else if (
	isset($set_cookie_admin)
	OR $set_cookie_admin = _request('cookie_admin')
	) {
		spip_setcookie('spip_admin', $set_cookie_admin,
			time() + 14 * 24 * 3600);
	}

	// Redirection finale
	redirige_par_entete($redirect, true);
}

?>
