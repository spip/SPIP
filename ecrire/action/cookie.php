<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
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
	$redirect_echec = _request('url_echec');
	if (!isset($redirect_echec)) {
		if (strpos($redirect,_DIR_RESTREINT_ABS)!==false)
			$redirect_echec = generer_url_public('login','',true);
		else
			$redirect_echec = $redirect;
	}

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
		redirige_par_entete(parametre_url(parametre_url($redirect_echec,'var_echec_cookie','oui','&'),'url',rawurlencode($redirect),'&'));
	}


	if (_request('essai_login') == "oui") {
		// Recuperer le login en champ hidden
		if (!$session_login = _request('session_login'))
			$session_login = _request('session_login_hidden');

		if (!spip_connect()) {
			include_spip('inc/minipres');
			echo minipres(_T('info_travaux_titre'),  _T('titre_probleme_technique'));
			exit;
		}

		$auteur = array();

		// Essayer tour a tour les differentes sources d'authenfication
		// on s'en souviendra dans visiteur_session['auth']
		$sources_auth = array('spip', 'ldap');
		while (!$auteur
		AND list(,$methode) = each($sources_auth)) {
			if ($auth = charger_fonction('auth_'.$methode, 'inc', true)
			AND $auteur = $auth(
				$session_login, _request('session_password')
			)) {
				$auteur['auth'] = $methode;
			} else {
				spip_log("pas de connexion avec $methode");
			}
		}

		// Sinon, renvoyer le formulaire avec message d'erreur si 2e fois
		if (!$auteur) {
			$redirect = parametre_url($redirect_echec,'var_login',$session_login,'&');
			if (_request('session_password')
			OR _request('session_password_md5'))
				$redirect = parametre_url($redirect, 'var_erreur', 'pass', '&');
			$redirect = parametre_url($redirect,'url',$url,'&');
			spip_log("echec login: $session_login");
		}

		// OK on a ete authentifie, on se connecte
		if ($auteur) {
			spip_log("login de $session_login vers $redirect (".$auteur['auth']);
			// Si on se connecte dans l'espace prive, 
			// ajouter "bonjour" (repere a peu pres les cookies desactives)
			if (strpos($redirect,_DIR_RESTREINT_ABS)!==false)
				$redirect = parametre_url($redirect, 'bonjour', 'oui', '&');

			// Prevoir de demander un cookie de correspondance
			if ($auteur['statut'] == '0minirezo')
				$set_cookie_admin = "@".$session_login;

			$session = charger_fonction('session', 'inc');
			$cookie_session = $session($auteur);
	
			// La case "rester connecte quelques jours"
			$session_remember = (_request('session_remember') == 'oui') ? 'perma' : '';
			if ($session_remember)
				spip_setcookie('spip_session', $cookie_session, time() + 2 * _RENOUVELLE_ALEA);
			else
				spip_setcookie('spip_session', $cookie_session);
	
			$prefs = ($auteur['prefs']) ? unserialize($auteur['prefs']) : array();
			$prefs['cnx'] = $session_remember;
	
			sql_updateq('spip_auteurs', array('prefs' => serialize($prefs)), "id_auteur = " . $auteur['id_auteur']);
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
