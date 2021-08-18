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
 * Gestion des mises à jour de bdd de SPIP
 *
 * Mises à jour en 3.1
 *
 * @package SPIP\Core\SQL\Upgrade
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


$GLOBALS['maj'][21676] = array(
	array('ranger_cache_gd2'),
);

/**
 * Ranger les images de local/cache-gd2 dans des sous-rep
 *
 * https://core.spip.net/issues/3277
 */
function ranger_cache_gd2() {
	spip_log('ranger_cache_gd2');
	$base = _DIR_VAR . 'cache-gd2/';
	if (is_dir($base) and is_readable($base)) {
		if ($dir = opendir($base)) {
			while (($f = readdir($dir)) !== false) {
				if (!is_dir($base . $f) and strncmp($f, '.', 1) !== 0
					and preg_match(',[0-9a-f]{32}\.\w+,', $f)
				) {
					$sub = substr($f, 0, 2);
					$sub = sous_repertoire($base, $sub);
					@rename($base . $f, $sub . substr($f, 2));
					@unlink($base . $f); // au cas ou le rename a foire (collision)
				}
				if (time() >= _TIME_OUT) {
					return;
				}
			}
		}
	}
}


$GLOBALS['maj'][21742] = array(
	array('sql_alter', "TABLE spip_articles CHANGE url_site url_site text DEFAULT '' NOT NULL"),
	array('sql_alter', "TABLE spip_articles CHANGE virtuel virtuel text DEFAULT '' NOT NULL"),
);
