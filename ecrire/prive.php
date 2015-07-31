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

  // Script pour appeler un squelette apres s'etre authentifie

include_once 'inc_version.php';

include_spip('inc/cookie');

$auth = charger_fonction('auth', 'inc');
$auth = $auth();
spip_log("authen: $auth");
if ($auth) {
	if ($auth===-1) exit();
	include_spip('inc/headers');
	if ($auth == '6forum') {
	  $auth = generer_url_public('', $_SERVER['QUERY_STRING'], true);
	  spip_log("6forum pour $auth");
	}	else
	  $auth = generer_url_public('login',
			"url=" . 
			rawurlencode(str_replace('/./', '/',
				(_DIR_RESTREINT ? "" : _DIR_RESTREINT_ABS)
						 . str_replace('&amp;', '&', self()))), true);
	redirige_par_entete($auth);
 }

# au travail...
include_once 'public.php';

?>