<?php

/**
 * Main routine called from an application using this include.
 *
 * General usage:
 *   require_once('sha256.inc.php');
 *   $hashstr = spip_sha256('abc');
 *
 * @param string $str Chaîne dont on veut calculer le SHA
 * @return string Le SHA de la chaîne
 */
function spip_sha256($str) {
	return hash('sha256', $str);
}

/**
 * @param string $str Chaîne dont on veut calculer le SHA
 * @param bool $ig_func
 * @return string Le SHA de la chaîne
 * @deprecated 4.0
 * @see spip_sha256()
 */
function _nano_sha256($str, $ig_func = true) {
	trigger_deprecation('spip', '4.0', 'Using "%s" is deprecated, use "%s" instead', __FUNCTION__, "hash('sha256', ...)");
	return spip_sha256($str);
}
