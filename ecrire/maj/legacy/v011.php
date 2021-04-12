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
 * Gestion des mises à jour de SPIP, versions 1.1*
 *
 * @package SPIP\Core\SQL\Upgrade
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Mises à jour de SPIP n°011
 *
 * @param float $version_installee Version actuelle
 * @param float $version_cible Version de destination
 **/
function maj_legacy_v011_dist($version_installee, $version_cible) {

	if (upgrade_vers(1.1, $version_installee, $version_cible)) {
		sql_query("DROP TABLE spip_petition");
		sql_query("DROP TABLE spip_signatures_petition");
		maj_version(1.1);
	}
}