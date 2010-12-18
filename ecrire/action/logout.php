<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/cookie');

// http://doc.spip.org/@action_logout_dist
function action_logout_dist()
{
	global $visiteur_session, $ignore_auth_http;
	$logout =_request('logout');
	$url = _request('url');
	// cas particulier, logout dans l'espace public
	if ($logout == 'public' AND !$url)
		$url = url_de_base();

	// seul le loge peut se deloger (mais id_auteur peut valoir 0 apres une restauration avortee)
	if (is_numeric($visiteur_session['id_auteur'])) {
		include_spip('inc/auth');
		auth_trace($visiteur_session, '0000-00-00 00:00:00');
	// le logout explicite vaut destruction de toutes les sessions
		if (isset($_COOKIE['spip_session'])) {
			$session = charger_fonction('session', 'inc');
			$session($visiteur_session['id_auteur']);
			spip_setcookie('spip_session', $_COOKIE['spip_session'], time()-3600);
		}
		// si authentification http, et que la personne est loge,
		// pour se deconnecter, il faut proposer un nouveau formulaire de connexion http
		if (isset($_SERVER['PHP_AUTH_USER']) AND !$ignore_auth_http AND $GLOBALS['auth_can_disconnect']) {
			  ask_php_auth(_T('login_deconnexion_ok'),
				       _T('login_verifiez_navigateur'),
				       _T('login_retour_public'),
				       	"redirect=". _DIR_RESTREINT_ABS, 
				       _T('login_test_navigateur'),
				       true);
			
		}
	}

	// Rediriger en contrant le cache navigateur (Safari3)
	include_spip('inc/headers');
	redirige_par_entete($url
		? parametre_url($url, 'var_hasard', uniqid(rand()), '&')
		: generer_url_public('login'));
}

?>
