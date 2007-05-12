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

if ($auth) {
	if ($auth===-1) exit();
	include_spip('inc/headers');
	if ($auth == '6forum') {
		$auth = '../?' . $_SERVER['QUERY_STRING'];
		spip_setcookie('spip_session', $_COOKIE['spip_session'], time() + 3600 * 24 * 14);
	} else
	  $auth = generer_url_public('login',
			"url=" . 
			rawurlencode(str_replace('/./', '/',
				(_DIR_RESTREINT ? "" : _DIR_RESTREINT_ABS)
						 . str_replace('&amp;', '&', self()))), true);
	redirige_par_entete($auth);
 }

// En somme, est prive' ce qui est publiquement nomme'...

include_once 'public.php';
?>
