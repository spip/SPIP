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

# ou est l'espace prive ?
@define('_DIR_RESTREINT_ABS', 'ecrire/');

# rediriger les anciens URLs de la forme page.php3fond=xxx
if (isset($_GET['fond'])) {
	include_once _DIR_RESTREINT_ABS.'inc_version.php';
	redirige_par_entete(generer_url_public($_GET['fond']));
}

# au travail...
include _DIR_RESTREINT_ABS.'public.php';

?>
