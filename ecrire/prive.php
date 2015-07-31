<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

  // Script pour appeler un squelette apres s'etre authentifie

include_once 'inc_version.php';

include_spip('inc/cookie');

$auth = charger_fonction('auth', 'inc');
$var_auth = $auth();

if ($var_auth !== '') {
	if (!is_int($var_auth)) {
	// si l'authentifie' n'a pas acces a l'espace de redac
	// c'est qu'on voulait forcer sa reconnaissance en tant que visiteur.
	// On reexecute pour deboucher sur le include public.
	// autrement on insiste
		if (is_array($var_auth)) {
			$var_auth = '../?' . $_SERVER['QUERY_STRING'];
			spip_setcookie('spip_session', $_COOKIE['spip_session'], time() + 3600 * 24 * 14);
		}
		include_spip('inc/headers');
		redirige_formulaire($var_auth);
	}
}

// En somme, est prive' ce qui est publiquement nomme'...
include_once 'public.php';
?>
