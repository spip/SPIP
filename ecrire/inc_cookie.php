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


//
if (!defined("_ECRIRE_INC_VERSION")) return;

//
// Appliquer le prefixe cookie
//
function spip_setcookie ($name='', $value='', $expire=0, $path='AUTO', $domain='', $secure='') {
	$name = ereg_replace ('^spip_', $GLOBALS['cookie_prefix'].'_', $name);
	if ($path == 'AUTO') $path=$GLOBALS['cookie_path'];

	if ($secure)
		@setcookie ($name, $value, $expire, $path, $domain, $secure);
	else if ($domain)
		@setcookie ($name, $value, $expire, $path, $domain);
	else if ($path)
		@setcookie ($name, $value, $expire, $path);
	else if ($expire)
		@setcookie ($name, $value, $expire);
	else
		@setcookie ($name, $value);
}

function recuperer_cookies_spip($cookie_prefix) {
	global $_COOKIE;

	foreach ($_COOKIE as $name => $value) {
		if (ereg('^spip_', $name)) {
			unset($_COOKIE[$name]);
			unset($GLOBALS[$name]);
		}
	}
	foreach ($_COOKIE as $name => $value) {
		if (ereg('^'.$cookie_prefix.'_', $name)) {
			$spipname = ereg_replace ('^'.$cookie_prefix.'_', 'spip_', $name);
			$_COOKIE[$spipname] = $value;
			$GLOBALS[$spipname] = $value;
		}
	}

}


?>
