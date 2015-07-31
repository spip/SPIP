<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/minipres');
include_spip('inc/install');
include_spip('inc/autoriser');

define('_ECRIRE_INSTALL', "1");
define('_FILE_TMP', '_install');

// http://doc.spip.org/@exec_install_dist
function exec_install_dist()
{
	$etape = _request('etape');
	$deja = (_FILE_CONNECT AND analyse_fichier_connection(_FILE_CONNECT));

	// Si deja installe, on n'a plus le droit qu'a l'etape chmod
	// pour chgt post-install ou aux etapes supplementaires
	// de declaration de base externes.
	// Mais alors il faut authentifier car ecrire/index.php l'a omis

	if ($deja AND in_array($etape, array('chmod', 'sup1', 'sup2'))) {

		$auth = charger_fonction('auth', 'inc');
		if (!$auth()) {
			verifier_visiteur();
			$deja = (!autoriser('configurer'));
		}
	}
	if ($deja) {
		// Rien a faire ici
	  	echo minipres();
	} else {
		include_spip('base/create');
		$fonc = charger_fonction("etape_$etape", 'install');
		$fonc();
	}
}

?>
