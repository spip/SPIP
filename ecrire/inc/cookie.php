<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2019                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion des cookies
 *
 * @package SPIP\Core\Cookies
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Place un cookie (préfixé) sur le poste client
 *
 * @global cookie_prefix Préfixe de cookie défini
 * @link http://fr.php.net/setcookie
 *
 * @param string $name
 *     Nom du cookie
 * @param string $value
 *     Valeur à stocker
 * @param int $expire
 *     Date d'expiration du cookie (timestamp)
 * @param string $path
 *     Chemin sur lequel le cookie sera disponible
 * @param string $domain
 *     Domaine à partir duquel le cookie est disponible
 * @param bool $secure
 *     cookie sécurisé ou non ?
 * @return bool
 *     true si le cookie a été posé, false sinon.
 **/
function spip_setcookie($name = '', $value = '', $expire = 0, $path = 'AUTO', $domain = '', $secure = '') {
	// liste des cookies en httponly (a passer en define si besoin)
	$httponly = in_array($name, explode(' ', 'spip_session'));

	$name = preg_replace('/^spip_/', $GLOBALS['cookie_prefix'] . '_', $name);
	if ($path == 'AUTO') {
		$path = defined('_COOKIE_PATH') ? _COOKIE_PATH : preg_replace(',^\w+://[^/]*,', '', url_de_base());
	}
	if (!$domain and defined('_COOKIE_DOMAIN')) {
		$domain = _COOKIE_DOMAIN;
	}

	#spip_log("cookie('$name', '$value', '$expire', '$path', '$domain', '$secure', '$httponly'");

	$a =
		$httponly ?
			@setcookie($name, $value, $expire, $path, $domain, $secure, $httponly)
			: ($secure ?
			@setcookie($name, $value, $expire, $path, $domain, $secure)
			: ($domain ?
				@setcookie($name, $value, $expire, $path, $domain)
				: ($path ?
					@setcookie($name, $value, $expire, $path)
					: ($expire ?
						@setcookie($name, $value, $expire)
						:
						@setcookie($name, $value)
					))));

	spip_cookie_envoye(true);

	return $a;
}

/**
 * Teste si un cookie a déjà été envoyé ou pas
 *
 * Permet par exemple à `redirige_par_entete()` de savoir le type de
 * redirection à appliquer (serveur ou navigateur)
 *
 * @see redirige_par_entete()
 *
 * @param bool|string $set
 *     true pour déclarer les cookies comme envoyés
 * @return bool
 **/
function spip_cookie_envoye($set = '') {
	static $envoye = false;
	if ($set) {
		$envoye = true;
	}

	return $envoye;
}

/**
 * Adapte le tableau PHP `$_COOKIE` pour prendre en compte le préfixe
 * des cookies de SPIP
 *
 * Si le préfixe des cookies de SPIP est différent de `spip_` alors
 * la fonction modifie les `$_COOKIE` ayant le préfixe spécifique
 * pour remettre le préfixe `spip_` à la place.
 *
 * Ainsi les appels dans le code n'ont pas besoin de gérer le préfixe,
 * ils appellent simplement `$_COOKIE['spip_xx']` qui sera forcément
 * la bonne donnée.
 *
 * @param string $cookie_prefix
 *     Préfixe des cookies de SPIP
 **/
function recuperer_cookies_spip($cookie_prefix) {
	$prefix_long = strlen($cookie_prefix);

	foreach ($_COOKIE as $name => $value) {
		if (substr($name, 0, 5) == 'spip_' && substr($name, 0, $prefix_long) != $cookie_prefix) {
			unset($_COOKIE[$name]);
			unset($GLOBALS[$name]);
		}
	}
	foreach ($_COOKIE as $name => $value) {
		if (substr($name, 0, $prefix_long) == $cookie_prefix) {
			$spipname = preg_replace('/^' . $cookie_prefix . '_/', 'spip_', $name);
			$_COOKIE[$spipname] = $value;
			$GLOBALS[$spipname] = $value;
		}
	}

}


/**
 * Teste si javascript est supporté par le navigateur et pose un cookie en conséquence
 *
 * Si la valeur d'environnement `js` arrive avec la valeur
 *
 * - `-1` c'est un appel via une balise `<noscript>`.
 * - `1` c'est un appel via javascript
 *
 * Inscrit le résultat dans le cookie `spip_accepte_ajax`
 *
 * @see  html_tests_js()
 * @uses spip_setcookie()
 *
 **/
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
