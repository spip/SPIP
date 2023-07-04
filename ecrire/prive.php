<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

use Spip\Auth\SessionCookie;

// Script pour appeler un squelette apres s'etre authentifie

include_once __DIR__ . '/inc_version.php';

include_spip('inc/cookie');

$auth = charger_fonction('auth', 'inc');
$var_auth = $auth();

if ($var_auth !== '' && !is_int($var_auth)) {
	// si l'authentifie' n'a pas acces a l'espace de redac
	// c'est qu'on voulait forcer sa reconnaissance en tant que visiteur.
	// On reexecute pour deboucher sur le include public.
	// autrement on insiste
	if (is_array($var_auth)) {
		$var_auth = '../?' . $_SERVER['QUERY_STRING'];
		// on prolonge le cookie
		(new SessionCookie())->expires(time() + 3600 * 24 * 14);
	}
	include_spip('inc/headers');
	redirige_formulaire($var_auth);
}

// En somme, est prive' ce qui est publiquement nomme'...
include __DIR__ . '/public.php';
