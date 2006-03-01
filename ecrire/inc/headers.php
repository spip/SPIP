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


// Interdire les attaques par manipulation des headers
function spip_header($h) {
	@header(strtr($h, "\n\r", "  "));
}

// cf. liste des sapi_name - http://fr.php.net/php_sapi_name
function php_module() {
	global $SERVER_SOFTWARE, $flag_sapi_name;
	return (
		($flag_sapi_name AND eregi("apache", @php_sapi_name()))
		OR ereg("^Apache.* PHP", $SERVER_SOFTWARE)
		);
}


function http_status($status) {
	global $REDIRECT_STATUS, $flag_sapi_name;
	static $status_string = array(
		200 => '200 OK',
		301 => '301 Moved Permanently',
		302 => '302 Found',
		304 => '304 Not Modified',
		401 => '401 Unauthorized',
		403 => '403 Forbidden',
		404 => '404 Not Found'
	);

	if ($REDIRECT_STATUS && $REDIRECT_STATUS == $status) return;

	$php_cgi = ($flag_sapi_name AND eregi("cgi", @php_sapi_name()));
	if ($php_cgi)
		header("Status: $status");
	else
		header("HTTP/1.0 ".$status_string[$status]);
}

// Retourne ce qui va bien pour que le navigateur ne mette pas la page en cache
function http_no_cache() {
	if (headers_sent()) return;
	if (!$charset = $GLOBALS['meta']['charset']) $charset = 'utf-8';

	// selon http://developer.apple.com/internet/safari/faq.html#anchor5
	// il faudrait aussi pour Safari
	// header("Cache-Control: post-check=0, pre-check=0", false)
	// mais ca ne respecte pas
	// http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9

	header("Content-Type: text/html; charset=$charset");
	header("Expires: 0");
	header("Last-Modified: " .gmdate("D, d M Y H:i:s"). " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Pragma: no-cache");
}


?>
