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
