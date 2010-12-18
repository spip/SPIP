<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

//
// Appliquer le prefixe cookie
//
// http://doc.spip.org/@spip_setcookie
function spip_setcookie ($name='', $value='', $expire=0, $path='AUTO', $domain='', $secure='') {
	$name = preg_replace ('/^spip_/', $GLOBALS['cookie_prefix'].'_', $name);
	if ($path == 'AUTO')
		$path = defined('_COOKIE_PATH')?_COOKIE_PATH:preg_replace(',^\w+://[^/]*,', '', url_de_base());
	if (!$domain AND defined('_COOKIE_DOMAIN'))
		$domain = _COOKIE_DOMAIN;

	#spip_log("cookie('$name', '$value', '$expire', '$path', '$domain', '$secure'");

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

// http://doc.spip.org/@recuperer_cookies_spip
function recuperer_cookies_spip($cookie_prefix) {
	$prefix_long = strlen($cookie_prefix);

	foreach ($_COOKIE as $name => $value) {
		if (substr($name,0,5)=='spip_' && substr($name,0,$prefix_long)!=$cookie_prefix) {
			unset($_COOKIE[$name]);
			unset($GLOBALS[$name]);
		}
	}
	foreach ($_COOKIE as $name => $value) {
		if (substr($name,0,$prefix_long)==$cookie_prefix) {
			$spipname = preg_replace ('/^'.$cookie_prefix.'_/', 'spip_', $name);
			$_COOKIE[$spipname] = $value;
			$GLOBALS[$spipname] = $value;
		}
	}

}

// Idem faudrait creer exec/test_ajax, mais c'est si court.
// Tester si Ajax fonctionne pour ce brouteur
// (si on arrive la c'est que c'est bon, donc poser le cookie)

// http://doc.spip.org/@exec_test_ajax_dist
function exec_test_ajax_dist() {
	switch (_request('js')) {
		// on est appele par <noscript>
		case -1:
			spip_setcookie('spip_accepte_ajax', -1);
			include_spip('inc/headers');
			redirige_par_entete(chemin_image('puce-orange-anim.gif'));
			break;

		// ou par ajax
		case 1:
		default:
			spip_setcookie('spip_accepte_ajax', 1);
			break;
	}
}
?>
