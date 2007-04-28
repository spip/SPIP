<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;

// envoyer le navigateur sur une nouvelle adresse
// en evitant les attaques par la redirection (souvent indique par 1 $_GET)

// http://doc.spip.org/@redirige_par_entete
function redirige_par_entete($url, $equiv='') {

	$url = strtr($url, "\n\r", "  ");
	# en theorie on devrait faire ca tout le temps, mais quand la chaine
	# commence par ? c'est imperatif, sinon l'url finale n'est pas la bonne
	if ($url[0]=='?')
		$url = url_de_base().$url;

	if ($x = _request('transformer_xml'))
		$url = parametre_url($url, 'transformer_xml', $x, '&');
	// Il n'y a que sous Apache que setcookie puis redirection fonctionne

	if (!$equiv OR (strncmp("Apache", $_SERVER['SERVER_SOFTWARE'],6)==0)) {
		@header("Location: " . $url);
	} else {
		@header("Refresh: 0; url=" . $url);
		$equiv = "<meta http-equiv='Refresh' content='0; url=$url'>";
	}
	include_spip('inc/lang');
	echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">',"\n",
	  html_lang_attributes(),'
<head>',
	  $equiv,'
<title>HTTP 302</title>
</head>
<body>
<h1>HTTP 302</h1>
<a href="',
	  quote_amp($url),
	  '">',
	  _T('navigateur_pas_redirige'),
	  '</a></body></html>';

	spip_log("redirige: $url");

	exit;
}

// http://doc.spip.org/@http_status
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

	$php_cgi = ($flag_sapi_name AND preg_match(",cgi,i", @php_sapi_name()));
	if ($php_cgi)
		header("Status: ".$status_string[$status]);
	else
		header("HTTP/1.0 ".$status_string[$status]);
}

// Retourne ce qui va bien pour que le navigateur ne mette pas la page en cache
// http://doc.spip.org/@http_no_cache
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


// envoi de l'image demandee dans le code ci-dessus
// http://doc.spip.org/@envoie_image_vide
function envoie_image_vide() {
	$image = pack("H*", "47494638396118001800800000ffffff00000021f90401000000002c0000000018001800000216848fa9cbed0fa39cb4da8bb3debcfb0f86e248965301003b");
	header("Content-Type: image/gif");
	header("Content-Length: ".strlen($image));
	header("Cache-Control: no-cache,no-store");
	header("Pragma: no-cache");
	header("Connection: close");
	echo $image;
	flush();
}

// http://doc.spip.org/@generer_test_dirs
function generer_test_dirs($arg='', $redirect=false)
{
	if (!is_string($redirect))
		return  generer_url_public('', "action=test_dirs" . ($arg ? "&test_dir=$arg" : ''),  $redirect);
	else return generer_form_public('test_dirs', $redirect);
}
?>
