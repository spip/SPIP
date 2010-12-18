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

// Mise a jour de l'option de configuration du proxy

include_spip('configuration/relayeur');

// http://doc.spip.org/@action_configurer_relayeur_dist
function action_configurer_relayeur_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	$http_proxy = _request('http_proxy');
	$http_noproxy = _request('http_noproxy');
	$test_proxy = _request('test_proxy');
	$tester_proxy = _request('tester_proxy');

	$test = configuration_relayeur_post($http_proxy, $http_noproxy, $test_proxy, $tester_proxy); 

	// message a afficher dans l'exec de retour

	$r = rawurldecode(_request('redirect'));
	$r = parametre_url($r, 'retour_proxy', $test, "&");
	redirige_par_entete($r);
}
?>
