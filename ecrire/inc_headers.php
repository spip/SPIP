<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_HEADERS")) return;
define("_ECRIRE_INC_HEADERS", "1");


// Interdire les attaques par manipulation des headers
function spip_header($h) {
	@header(strtr($h, "\n\r", "  "));
}

// cf. liste des sapi_name - http://fr.php.net/php_sapi_name
function php_module() {
	return (
		($flag_sapi_name AND eregi("apache", @php_sapi_name()))
		OR ereg("^Apache.* PHP", $SERVER_SOFTWARE)
		);
}


function http_status($status) {
	global $REDIRECT_STATUS;

	if ($REDIRECT_STATUS && $REDIRECT_STATUS == $status) return;

	$status_string = array(
		200 => '200 OK',
		301 => '301 Moved Permanently',
		302 => '302 Found',
		304 => '304 Not Modified',
		401 => '401 Unauthorized',
		403 => '403 Forbidden',
		404 => '404 Not Found'
	);

	$php_cgi = ($flag_sapi_name AND eregi("cgi", @php_sapi_name()));
	if ($php_cgi)
		header("Status: $status");
	else
		header("HTTP/1.0 ".$status_string[$status]);
}


?>
