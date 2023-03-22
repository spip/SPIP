<?php

/**
 * Main routine called from an application using this include.
 *
 * General usage:
 *   require_once('sha256.inc.php');
 *   $hashstr = spip_sha256('abc');
 *
 * @deprecated 5.0 Use `hash('sha256', $str)`
 * @param string $str Chaîne dont on veut calculer le SHA
 * @return string Le SHA de la chaîne
 */
function spip_sha256($str) {
	trigger_deprecation('spip', '5.0', 'Using "%s" is deprecated, use "%s" instead.', 'spip_sha256($str)', 'hash(\'sha256\', $str)');
	return hash('sha256', $str);
}
