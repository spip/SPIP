<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Gestion de l'action cookie
 *
 * @package SPIP\Core\Inscription
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/actions');
include_spip('inc/cookie');

/**
 * Cette fonction traite les cookies posés au moment de l'authentification standard
 * ou vérifie que l'authentification HTTP est correcte
 *
 * @global bool ignore_auth_http
 * @param string|null $set_cookie_admin
 * @param string|null $change_session
 * @return void
 */
function action_cookie_dist($set_cookie_admin = null, $change_session = null) {
	$redirect_echec = $redirect = null;
	$test_echec_cookie = null;
	$url = '';
	if (is_null($set_cookie_admin)) {
		$set_cookie_admin = _request('cookie_admin');
		$change_session = _request('change_session');
		$test_echec_cookie = _request('test_echec_cookie');

		// La cible de notre operation de connexion
		$url = securiser_redirect_action(_request('url'));
		$redirect = $url ?: generer_url_ecrire('accueil');
		$redirect_echec = _request('url_echec');
		if (!isset($redirect_echec)) {
			if (str_contains((string) $redirect, (string) _DIR_RESTREINT_ABS)) {
				$redirect_echec = generer_url_public('login', '', true);
			} else {
				$redirect_echec = $redirect;
			}
		}
	}


	// rejoue le cookie pour renouveler spip_session
	if ($change_session == 'oui') {
		$session = charger_fonction('session', 'inc');
		$session(true);
		spip_log('statut 204 pour ' . $_SERVER['REQUEST_URI']);
		http_response_code(204); // No Content
		return;
	}

	// tentative de connexion en auth_http
	if (_request('essai_auth_http') && !$GLOBALS['ignore_auth_http']) {
		include_spip('inc/auth');
		if (
			@$_SERVER['PHP_AUTH_USER']
			&& @$_SERVER['PHP_AUTH_PW']
			&& ($auteur = lire_php_auth($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']))
		) {
			auth_loger($auteur);
			redirige_par_entete(parametre_url($redirect, 't', time(), '&'));
		} else {
			ask_php_auth(
				_T('info_connexion_refusee'),
				_T('login_login_pass_incorrect'),
				_T('login_retour_site'),
				'url=' . rawurlencode((string) $redirect),
				_T('login_nouvelle_tentative'),
				(str_contains((string) $url, (string) _DIR_RESTREINT_ABS))
			);
		}
	} else {
		// en cas de login sur bonjour=oui, on tente de poser un cookie
		// puis de passer au login qui diagnostiquera l'echec de cookie
		// le cas echeant.
		if ($test_echec_cookie == 'oui') {
			spip_setcookie('spip_session', 'test_echec_cookie');
			if ($redirect) {
				$redirect = parametre_url(
					parametre_url($redirect_echec, 'var_echec_cookie', 'oui', '&'),
					'url',
					rawurlencode((string) $redirect),
					'&'
				);
			}
		} else {
			$cook = $_COOKIE['spip_admin'] ?? '';
			// Suppression cookie d'admin ?
			if ($set_cookie_admin == 'non') {
				if ($cook) {
					spip_setcookie('spip_admin', $cook, [
						'expires' => time() - 3600 * 24
					]);
				}
			} // Ajout de cookie d'admin
			else {
				if ($set_cookie_admin && _DUREE_COOKIE_ADMIN) {
					spip_setcookie('spip_admin', $set_cookie_admin, [
						'expires' => time() + max(_DUREE_COOKIE_ADMIN, 2 * _RENOUVELLE_ALEA)
					]);
				}
			}
		}
	}

	// Redirection finale
	if ($redirect) {
		redirige_par_entete($redirect, true);
	}
}
