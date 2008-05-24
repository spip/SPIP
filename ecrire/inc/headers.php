<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;

// envoyer le navigateur sur une nouvelle adresse
// en evitant les attaques par la redirection (souvent indique par 1 $_GET)

// http://doc.spip.org/@redirige_par_entete
function redirige_par_entete($url, $equiv='', $status = 302) {
	if (!in_array($status,array(301,302)))
		$status = 302;
	
	$url = strtr($url, "\n\r", "  ");
	# en theorie on devrait faire ca tout le temps, mais quand la chaine
	# commence par ? c'est imperatif, sinon l'url finale n'est pas la bonne
	if ($url[0]=='?')
		$url = url_de_base().$url;

	if ($x = _request('transformer_xml'))
		$url = parametre_url($url, 'transformer_xml', $x, '&');

	if (_AJAX)
		$url = parametre_url($url, 'var_ajax_redir', 1, '&');

	// Il n'y a que sous Apache que setcookie puis redirection fonctionne

	if (!$equiv OR (strncmp("Apache", $_SERVER['SERVER_SOFTWARE'],6)==0) OR defined('_SERVER_APACHE')) {
		@header("Location: " . $url);
		$equiv="";
	} else {
		@header("Refresh: 0; url=" . $url);
		$equiv = "<meta http-equiv='Refresh' content='0; url=$url'>";
	}
	include_spip('inc/lang');
	if ($status!=302)
		http_status($status);
	echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">',"\n",
	  html_lang_attributes(),'
<head>',
	  $equiv,'
<title>HTTP '.$status.'</title>
</head>
<body>
<h1>HTTP '.$status.'</h1>
<a href="',
	  quote_amp($url),
	  '">',
	  _T('navigateur_pas_redirige'),
	  '</a></body></html>';

	spip_log("redirige $status: $url");

	exit;
}

// http://doc.spip.org/@redirige_formulaire
function redirige_formulaire($url, $equiv = '') {
	if (!_AJAX
	&& !headers_sent() 
	&& !$_GET['var_mode']) {
		redirige_par_entete(str_replace('&amp;','&',$url), $equiv);
	} else {
		$url = strtr($url, "\n\r", "  ");
		# en theorie on devrait faire ca tout le temps, mais quand la chaine
		# commence par ? c'est imperatif, sinon l'url finale n'est pas la bonne
		if ($url[0]=='?')
			$url = url_de_base().$url;
		$url = str_replace('&amp;','&',$url);
		spip_log("redirige formulaire ajax: $url");
		include_spip('inc/filtres');	
		return 
		"<script type='javascript'>window.location='$url';</script>"
		. http_img_pack('searching.gif','');
	}
}

// http://doc.spip.org/@redirige_url_ecrire
function redirige_url_ecrire($script='', $args='', $equiv='') {
	return redirige_par_entete(generer_url_ecrire($script, $args, true), $equiv);
}

// http://doc.spip.org/@http_status
function http_status($status) {
	global $REDIRECT_STATUS, $flag_sapi_name;
	static $status_string = array(
		200 => '200 OK',
		204 => '204 No Content',
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
	$charset = empty($GLOBALS['meta']['charset']) ? 'utf-8' : $GLOBALS['meta']['charset'];

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

/* Code mort: le statut 204 c'est fait pour.

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
*/

?>
