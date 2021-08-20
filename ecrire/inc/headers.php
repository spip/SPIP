<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion des headers et redirections
 *
 * @package SPIP\Core\Headers
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Envoyer le navigateur sur une nouvelle adresse
 *
 * Le tout en évitant les attaques par la redirection (souvent indique par un `$_GET`)
 *
 * @example
 *     ```
 *     $redirect = parametre_url(urldecode(_request('redirect')),'id_article=' . $id_article);
 *     include_spip('inc/headers');
 *     redirige_par_entete($redirect);
 *     ```
 *
 * @param string $url URL de redirection
 * @param string $equiv ?
 * @param int $status Code de redirection (301 ou 302)
 **/
function redirige_par_entete($url, $equiv = '', $status = 302) {
	if (!in_array($status, [301, 302])) {
		$status = 302;
	}

	$url = trim(strtr($url, "\n\r", '  '));
	# si l'url de redirection est relative, on la passe en absolue
	if (!preg_match(',^(\w+:)?//,', $url)) {
		include_spip('inc/filtres_mini');
		$url = url_absolue($url);
	}

	if (defined('_AJAX') and _AJAX) {
		$url = parametre_url($url, 'var_ajax_redir', 1, '&');
	}

	// ne pas laisser passer n'importe quoi dans l'url
	$url = str_replace(['<', '"'], ['&lt;', '&quot;'], $url);
	$url = str_replace(["\r", "\n", ' '], ['%0D', '%0A', '%20'], $url);
	while (strpos($url, '%0A') !== false) {
		$url = str_replace('%0A', '', $url);
	}
	// interdire les url inline avec des pseudo-protocoles :
	if (
		(preg_match(',data:,i', $url) and preg_match('/base64\s*,/i', $url))
		or preg_match(',(javascript|mailto):,i', $url)
	) {
		$url = './';
	}

	// Il n'y a que sous Apache que setcookie puis redirection fonctionne
	include_spip('inc/cookie');
	if (!defined('_SERVEUR_SOFTWARE_ACCEPTE_LOCATION_APRES_COOKIE')) {
		define('_SERVEUR_SOFTWARE_ACCEPTE_LOCATION_APRES_COOKIE', '^(Apache|Cherokee|nginx)');
	}
	if (!defined('_SERVEUR_SIGNATURE_ACCEPTE_LOCATION_APRES_COOKIE')) {
		define('_SERVEUR_SIGNATURE_ACCEPTE_LOCATION_APRES_COOKIE', 'Apache|Cherokee|nginx');
	}
	if (
		(!$equiv and !spip_cookie_envoye()) or (
			   (!empty($_SERVER['SERVER_SOFTWARE'])
				   and _SERVEUR_SOFTWARE_ACCEPTE_LOCATION_APRES_COOKIE
				   and preg_match('/' . _SERVEUR_SOFTWARE_ACCEPTE_LOCATION_APRES_COOKIE . '/i', $_SERVER['SERVER_SOFTWARE']))
			or (!empty($_SERVER['SERVER_SIGNATURE'])
				   and _SERVEUR_SIGNATURE_ACCEPTE_LOCATION_APRES_COOKIE
				   and preg_match('/' . _SERVEUR_SIGNATURE_ACCEPTE_LOCATION_APRES_COOKIE . '/i', $_SERVER['SERVER_SIGNATURE']))
			or function_exists('apache_getenv')
			or defined('_SERVER_APACHE')
		)
	) {
		@header('Location: ' . $url);
		$equiv = '';
	} else {
		@header('Refresh: 0; url=' . $url);
		if (isset($GLOBALS['meta']['charset'])) {
			@header('Content-Type: text/html; charset=' . $GLOBALS['meta']['charset']);
		}
		$equiv = "<meta http-equiv='Refresh' content='0; url=$url'>";
	}
	include_spip('inc/lang');
	if ($status != 302) {
		http_status($status);
	}
	echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">', "\n",
	html_lang_attributes(), '
<head>',
	$equiv, '
<title>HTTP ' . $status . '</title>
' . ((isset($GLOBALS['meta']['charset'])) ? '<meta http-equiv="Content-Type" content="text/html;charset=' . $GLOBALS['meta']['charset'] . '">' : '') . '
</head>
<body>
<h1>HTTP ' . $status . '</h1>
<a href="',
	quote_amp($url),
	'">',
	_T('navigateur_pas_redirige'),
	'</a></body></html>';

	spip_log("redirige $status: $url");

	exit;
}

// https://code.spip.net/@redirige_formulaire
function redirige_formulaire($url, $equiv = '', $format = 'message') {
	if (
		!_AJAX
		and !headers_sent()
		and !_request('var_ajax')
	) {
		redirige_par_entete(str_replace('&amp;', '&', $url), $equiv);
	} // si c'est une ancre, fixer simplement le window.location.hash
	elseif ($format == 'ajaxform' and preg_match(',^#[0-9a-z\-_]+$,i', $url)) {
		return [
			// on renvoie un lien masque qui sera traite par ajaxCallback.js
			"<a href='$url' name='ajax_ancre' style='display:none;'>anchor</a>",
			// et rien dans le message ok
			''
		];
	} else {
		// ne pas laisser passer n'importe quoi dans l'url
		$url = str_replace(['<', '"'], ['&lt;', '&quot;'], $url);

		$url = strtr($url, "\n\r", '  ');
		# en theorie on devrait faire ca tout le temps, mais quand la chaine
		# commence par ? c'est imperatif, sinon l'url finale n'est pas la bonne
		if ($url[0] == '?') {
			$url = url_de_base() . $url;
		}
		$url = str_replace('&amp;', '&', $url);
		spip_log("redirige formulaire ajax: $url");
		include_spip('inc/filtres');
		if ($format == 'ajaxform') {
			return [
				// on renvoie un lien masque qui sera traite par ajaxCallback.js
				'<a href="' . quote_amp($url) . '" name="ajax_redirect"  style="display:none;">' . _T('navigateur_pas_redirige') . '</a>',
				// et un message au cas ou
				'<br /><a href="' . quote_amp($url) . '">' . _T('navigateur_pas_redirige') . '</a>'
			];
		} else // format message texte, tout en js inline
		{
			return
				// ie poste les formulaires dans une iframe, il faut donc rediriger son parent
				"<script type='text/javascript'>if (parent.window){parent.window.document.location.replace(\"$url\");} else {document.location.replace(\"$url\");}</script>"
				. http_img_pack('loader.svg', '', " class='loader'")
				. '<br />'
				. '<a href="' . quote_amp($url) . '">' . _T('navigateur_pas_redirige') . '</a>';
		}
	}
}

/**
 * Effectue une redirection par header PHP vers un script de l’interface privée
 *
 * @uses redirige_par_entete() Qui tue le script PHP.
 * @example
 *     ```
 *     include_spip('inc/headers');
 *     redirige_url_ecrire('rubriques','id_rubrique=' . $id_rubrique);
 *     ```
 *
 * @param string $script
 *     Nom de la page privée (exec)
 * @param string $args
 *     Arguments à transmettre. Exemple `etape=1&autre=oui`
 * @param string $equiv
 * @return void
 **/
function redirige_url_ecrire($script = '', $args = '', $equiv = '') {
	return redirige_par_entete(generer_url_ecrire($script, $args, true), $equiv);
}

/**
 * Renvoie au client le header HTTP avec le message correspondant au code indiqué.
 *
 * Ainsi `http_status(301)` enverra le message `301 Moved Permanently`.
 *
 * @link http://php.net/manual/fr/function.header.php Fonction header() de PHP utilisée ici
 *
 * @param int $status
 *     Code d'erreur
 **/
function http_status($status) {

	static $status_string = [
		200 => '200 OK',
		204 => '204 No Content',
		301 => '301 Moved Permanently',
		302 => '302 Found',
		304 => '304 Not Modified',
		400 => '400 Bad Request',
		401 => '401 Unauthorized',
		403 => '403 Forbidden',
		404 => '404 Not Found',
		503 => '503 Service Unavailable'
	];

	if (!empty($GLOBALS['REDIRECT_STATUS']) && $GLOBALS['REDIRECT_STATUS'] == $status) {
		return;
	}

	$php_cgi = ($GLOBALS['flag_sapi_name'] and preg_match(',cgi,i', @php_sapi_name()));
	if ($php_cgi) {
		header('Status: ' . $status_string[$status]);
	} else {
		header('HTTP/1.0 ' . $status_string[$status]);
	}
}

// Retourne ce qui va bien pour que le navigateur ne mette pas la page en cache
// https://code.spip.net/@http_no_cache
function http_no_cache() {
	if (headers_sent()) {
		spip_log('http_no_cache arrive trop tard');

		return;
	}
	$charset = empty($GLOBALS['meta']['charset']) ? 'utf-8' : $GLOBALS['meta']['charset'];

	// selon http://developer.apple.com/internet/safari/faq.html#anchor5
	// il faudrait aussi pour Safari
	// header("Cache-Control: post-check=0, pre-check=0", false)
	// mais ca ne respecte pas
	// http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9

	header("Content-Type: text/html; charset=$charset");
	header('Expires: 0');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	header('Cache-Control: no-cache, must-revalidate');
	header('Pragma: no-cache');
}
