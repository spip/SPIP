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

// envoyer le navigateur sur une nouvelle adresse
// en evitant les attaques par la redirection (souvent indique par 1 $_GET)

// http://doc.spip.org/@redirige_par_entete
function redirige_par_entete($url, $equiv='', $status = 302) {
	if (!in_array($status,array(301,302)))
		$status = 302;
	
	$url = trim(strtr($url, "\n\r", "  "));
	# en theorie on devrait faire ca tout le temps, mais quand la chaine
	# commence par ? c'est imperatif, sinon l'url finale n'est pas la bonne
	if ($url[0]=='?')
		$url = url_de_base().(_DIR_RESTREINT?'':_DIR_RESTREINT_ABS).$url;
	if ($url[0]=='#')
		$url = self('&').$url;

	if ($x = _request('transformer_xml'))
		$url = parametre_url($url, 'transformer_xml', $x, '&');

	if (defined('_AJAX') AND _AJAX)
		$url = parametre_url($url, 'var_ajax_redir', 1, '&');
		
	// ne pas laisser passer n'importe quoi dans l'url
	$url = str_replace(array('<','"'),array('&lt;','&quot;'),$url);
	// interdire les url inline avec des pseudo-protocoles :
	if (
		(preg_match(",data:,i",$url) AND preg_match("/base64\s*,/i",$url))
		OR preg_match(",(javascript|mailto):,i",$url)
		)
		$url ="./";

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
function redirige_formulaire($url, $equiv = '', $format='message') {
	if (!_AJAX
	AND !headers_sent()
	AND !_request('var_ajax')) {
		redirige_par_entete(str_replace('&amp;','&',$url), $equiv);
	}
	// si c'est une ancre, fixer simplement le window.location.hash
	elseif($format=='ajaxform' AND preg_match(',^#[0-9a-z\-_]+$,i',$url)) {
		return array(
		// on renvoie un lien masque qui sera traite par ajaxCallback.js
		"<a href='$url' name='ajax_ancre' style='display:none;'>anchor</a>",
		// et rien dans le message ok
		'');
	}
	else {
		// ne pas laisser passer n'importe quoi dans l'url
		$url = str_replace(array('<','"'),array('&lt;','&quot;'),$url);

		$url = strtr($url, "\n\r", "  ");
		# en theorie on devrait faire ca tout le temps, mais quand la chaine
		# commence par ? c'est imperatif, sinon l'url finale n'est pas la bonne
		if ($url[0]=='?')
			$url = url_de_base().(_DIR_RESTREINT?'':_DIR_RESTREINT_ABS).$url;
		$url = str_replace('&amp;','&',$url);
		spip_log("redirige formulaire ajax: $url");
		include_spip('inc/filtres');
		if ($format=='ajaxform')
			return array(
			// on renvoie un lien masque qui sera traite par ajaxCallback.js
			'<a href="'.quote_amp($url).'" name="ajax_redirect"  style="display:none;">'._T('navigateur_pas_redirige').'</a>',
			// et un message au cas ou
			'<br /><a href="'.quote_amp($url).'">'._T('navigateur_pas_redirige').'</a>'
			);
		else // format message texte, tout en js inline
			return
		// ie poste les formulaires dans une iframe, il faut donc rediriger son parent
		"<script type='text/javascript'>if (parent.window){parent.window.document.location.replace(\"$url\");} else {document.location.replace(\"$url\");}</script>"
		. http_img_pack('searching.gif','')
		. '<br />'
		. '<a href="'.quote_amp($url).'">'._T('navigateur_pas_redirige').'</a>';
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
	if (headers_sent())
		{ spip_log("http_no_cache arrive trop tard"); return;}
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

?>
