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

include_spip('inc/cookie');

function action_logout_dist()
{
	global $auteur_session, $ignore_auth_http;
	$logout =_request('logout');
	$url = _request('url');
	spip_log("logout $logout $url" . $auteur_session['id_auteur']);
// cas particulier, logout dans l'espace public
	if ($logout='public' AND !$url)  $url = $GLOBALS['meta']['adresse_site'];

// seul le loge peut se deloger
	if ($auteur_session['id_auteur']) {
		spip_query("UPDATE spip_auteurs SET en_ligne = DATE_SUB(NOW(),INTERVAL 15 MINUTE) WHERE id_auteur = ".$auteur_session['id_auteur']);
	// le logout explicite vaut destruction de toutes les sessions
		if ($_COOKIE['spip_session']) {
			$var_f = charger_fonction('session', 'inc');
			$var_f($auteur_session['id_auteur']);
			spip_setcookie('spip_session', '', 0);
		}
		if ($_SERVER['PHP_AUTH_USER'] AND !$ignore_auth_http) {
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
