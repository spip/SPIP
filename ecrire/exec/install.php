<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Affichage des étapes d'installation de SPIP
 *
 * @package SPIP\Core\Exec
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/minipres');
include_spip('inc/install');
include_spip('inc/autoriser');

define('_ECRIRE_INSTALL', '1');
define('_FILE_TMP', '_install');

/**
 * Affiche un des écrans d'installation de SPIP
 *
 * Affiche l'étape d'installation en cours, en fonction du paramètre
 * d'url `etape`
 *
 * @uses inc_auth_dist()
 * @uses verifier_visiteur()
 *
 * @uses install_etape__dist()
 *   Affiche l'écran d'accueil de l'installation,
 *   si aucune étape n'est encore définie.
 *
 **/
function exec_install_dist() {
	$etape = _request('etape');
	$deja = (_FILE_CONNECT and analyse_fichier_connection(_FILE_CONNECT));

	// Si deja installe, on n'a plus le droit qu'a l'etape chmod
	// pour chgt post-install ou aux etapes supplementaires
	// de declaration de base externes.
	// Mais alors il faut authentifier car ecrire/index.php l'a omis

	if ($deja and in_array($etape, array('chmod', 'sup1', 'sup2'))) {
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
