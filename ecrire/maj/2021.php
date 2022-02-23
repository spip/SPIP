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
 * Gestion des mises à jour de SPIP, version >= 2021000000
 *
 * Gestion des mises à jour du cœur de SPIP par un tableau global `maj`
 * indexé par la date du changement YYYYMMDDXX
 *
 * @package SPIP\Core\SQL\Upgrade
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS['maj'][2021_02_18_00] = [
	['sql_alter', "TABLE spip_auteurs CHANGE imessage imessage VARCHAR(3) DEFAULT '' NOT NULL" ],
	['sql_updateq', 'spip_auteurs', ['imessage' => 'oui'], "imessage != 'non' OR imessage IS NULL" ],
];

$GLOBALS['maj'][2022_02_23_02] = [
	['sql_alter', "TABLE spip_auteurs ADD backup_cles mediumtext DEFAULT '' NOT NULL" ],
	['sql_delete', "spip_meta","nom='secret_du_site'" ],
];
