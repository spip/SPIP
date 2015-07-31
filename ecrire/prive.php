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

# ou est l'espace prive : on y est deja !
include_once 'inc_version.php';

include_spip('inc/cookie');

//
// Determiner l'action demandee
//

$exec = _request('exec');
if (!preg_match(',^[a-z][0-9a-z_]*$,i', $exec)) $exec = "accueil";

//
// Authentification, redefinissable
//

if (autoriser_sans_cookie($exec)) {
	if (!isset($reinstall)) $reinstall = 'non';
	$var_auth = true;
} else {
	$auth = charger_fonction('auth', 'inc');
	$auth = $auth();
	if ($auth) {
	  include_spip('inc/headers');
	  redirige_par_entete($auth);
	}
}


# au travail...
include_once 'public.php';

?>