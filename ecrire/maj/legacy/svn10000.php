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
 * Certains plugins appelaient ce fichier pour acceder a maj_lien(),
 * qui sert à la migration des tables vers SPIP 3.0
 *
 * @package SPIP\Core\SQL\Upgrade
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('maj/legacy/v30');
