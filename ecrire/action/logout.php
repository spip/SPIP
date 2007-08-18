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

include_spip('inc/cookie');

// http://doc.spip.org/@action_logout_dist
function action_logout_dist()
{
	global $auteur_session, $ignore_auth_http;
	$logout =_request('logout');
	$url = _request('url');
	spip_log("logout $logout $url" . $auteur_session['id_auteur']);
	// cas particulier, logout dans l'espace public
	if ($logout == 'public' AND !$url)
		$url = url_de_base();

	// seul le loge peut se deloger (mais id_auteur peut valoir 0 apres une restauration avortee)
	if (is_numeric($auteur_session['id_auteur'])) {
		include_spip('base/abstract_sql');
		sql_updateq('spip_auteurs', 
			   array('en_ligne' => 'DATE_SUB(NOW(),INTERVAL 15 MINUTE)'),
			"id_auteur=" . $auteur_session['id_auteur']);
	// le logout explicite vaut destruction de toutes les sessions
		if (isset($_COOKIE['spip_session'])) {
			$session = charger_fonction('session', 'inc');
			$session($auteur_session['id_auteur']);
			spip_setcookie('spip_session', $_COOKIE['spip_session'], time()-3600);
		}
		if (isset($_SERVER['PHP_AUTH_USER']) AND !$ignore_auth_http) {
			include_spip('inc/actions');
			if (verifier_php_auth()) {
			  ask_php_auth(_T('login_deconnexion_ok'),
				       _T('login_verifiez_navigateur'),
				       _T('login_retour_public'),
				       	"redirect=". _DIR_RESTREINT_ABS, 
				       _T('login_test_navigateur'),
				       true);
			  exit;
			}
		}
	}
	redirige_par_entete($url ? $url : generer_url_public('login'));
}
?>
