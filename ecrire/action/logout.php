<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Action pour déconnecter une personne authentifiée
 *
 * @package SPIP\Core\Authentification
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/cookie');

/**
 * Se déloger
 *
 * Pour éviter les CSRF on passe par une étape de confirmation si pas de jeton fourni
 * avec un autosubmit js pour ne pas compliquer l'expérience utilisateur
 *
 * Déconnecte l'utilisateur en cours et le redirige sur l'URL indiquée par
 * l'argument de l'action sécurisée, et sinon sur la page d'accueil
 * de l'espace public.
 *
 */
function action_logout_dist() {
	$logout = _request('logout');
	$url = securiser_redirect_action(_request('url'));
	// cas particulier, logout dans l'espace public
	if ($logout == 'public' and !$url) {
		$url = url_de_base();
	}

	// seul le loge peut se deloger (mais id_auteur peut valoir 0 apres une restauration avortee)
	if (
		isset($GLOBALS['visiteur_session']['id_auteur'])
		and is_numeric($GLOBALS['visiteur_session']['id_auteur'])
		// des sessions anonymes avec id_auteur=0 existent, mais elle n'ont pas de statut : double check
		and isset($GLOBALS['visiteur_session']['statut'])
	) {
		// il faut un jeton pour fermer la session (eviter les CSRF)
		if (
			!$jeton = _request('jeton')
			or !verifier_jeton_logout($jeton, $GLOBALS['visiteur_session'])
		) {
			$jeton = generer_jeton_logout($GLOBALS['visiteur_session']);
			$action = generer_url_action('logout', "jeton=$jeton");
			$action = parametre_url($action, 'logout', _request('logout'));
			$action = parametre_url($action, 'url', _request('url'));
			include_spip('inc/minipres');
			include_spip('inc/filtres');
			$texte = bouton_action(_T('spip:icone_deconnecter'), $action);
			$texte = "<div class='boutons'>$texte</div>";
			$texte .= '<script type="text/javascript">document.write("<style>body{visibility:hidden;}</style>");window.document.forms[0].submit();</script>';
			$res = minipres(_T('spip:icone_deconnecter'), $texte, '', true);
			echo $res;

			return;
		}

		include_spip('inc/auth');
		auth_trace($GLOBALS['visiteur_session'], '0000-00-00 00:00:00');
		// le logout explicite vaut destruction de toutes les sessions
		if (isset($_COOKIE['spip_session'])) {
			$session = charger_fonction('session', 'inc');
			$session($GLOBALS['visiteur_session']['id_auteur']);
			spip_setcookie('spip_session', $_COOKIE['spip_session'], [
				'expires' => time() - 3600
			]);
		}
		// si authentification http, et que la personne est loge,
		// pour se deconnecter, il faut proposer un nouveau formulaire de connexion http
		if (
			isset($_SERVER['PHP_AUTH_USER'])
			and !$GLOBALS['ignore_auth_http']
			and $GLOBALS['auth_can_disconnect']
		) {
			ask_php_auth(
				_T('login_deconnexion_ok'),
				_T('login_verifiez_navigateur'),
				_T('login_retour_public'),
				'redirect=' . _DIR_RESTREINT_ABS,
				_T('login_test_navigateur'),
				true
			);
		}
	}

	// Rediriger en contrant le cache navigateur (Safari3)
	include_spip('inc/headers');
	redirige_par_entete($url
		? parametre_url($url, 'var_hasard', uniqid(rand()), '&')
		: generer_url_public('login'));
}

/**
 * Generer un jeton de logout personnel et ephemere
 *
 * @param array $session
 * @param null|string $alea
 * @return string
 */
function generer_jeton_logout($session, $alea = null) {
	if (is_null($alea)) {
		include_spip('inc/acces');
		$alea = charger_aleas();
	}

	$jeton = md5($session['date_session']
		. $session['id_auteur']
		. $session['statut']
		. $alea);

	return $jeton;
}

/**
 * Verifier que le jeton de logout est bon
 *
 * Il faut verifier avec alea_ephemere_ancien si pas bon avec alea_ephemere
 * pour gerer le cas de la rotation d'alea
 *
 * @param string $jeton
 * @param array $session
 * @return bool
 */
function verifier_jeton_logout($jeton, $session) {
	if (generer_jeton_logout($session) === $jeton) {
		return true;
	}

	if (generer_jeton_logout($session, $GLOBALS['meta']['alea_ephemere_ancien']) === $jeton) {
		return true;
	}

	return false;
}
