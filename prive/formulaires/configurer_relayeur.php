<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;
include_spip('inc/presentation');
include_spip('inc/config');

function formulaires_configurer_relayeur_charger_dist(){
	$valeurs = array(
			'http_proxy' =>no_password_proxy_url(lire_config('http_proxy', '')),
			'http_noproxy' => lire_config('http_noproxy', ''),
			'test_proxy' => 'http://www.spip.net/',
			);

	return $valeurs;
}

function formulaires_configurer_relayeur_verifier_dist(){
	$erreurs = array();
	$http_proxy = relayeur_saisie_ou_config(_request('http_proxy'), lire_config('http_proxy', ''));
	$http_noproxy = _request('http_noproxy');

	if ($http_proxy AND !preg_match(",https?://,", $http_proxy)){
		$erreurs['http_proxy'] = _L('format_proxy_incorrect');
	}

	if (!isset($erreurs['http_proxy']) AND _request('tester_proxy')) {
		if (!$http_proxy)
			$erreurs['http_proxy'] = _T('info_obligatoire');
		else {
			include_spip('inc/distant');
			$test_proxy = _request('test_proxy');
			$t = parse_url($test_proxy);
			if (!@$t['host']) {
				$erreurs['test_proxy'] = _T('info_adresse_non_indiquee');
			}
			else {
				include_spip('inc/texte'); // pour aide, couper, lang
				$info = "";
				if (!need_proxy($t['host'],$http_proxy,$http_noproxy))
					$info = "<strong>"._T('page_pas_proxy')."</strong><br />";

				// il faut fausser le proxy actuel pour faire le test !
				$cur_http_proxy = $GLOBALS['meta']['http_proxy'];
				$cur_http_noproxy = $GLOBALS['meta']['http_noproxy'];
				$GLOBALS['meta']['http_proxy'] = $http_proxy;
				$GLOBALS['meta']['http_noproxy'] = $http_noproxy;
				$page = recuperer_page($test_proxy, true);
				$GLOBALS['meta']['http_proxy'] = $cur_http_proxy;
				$GLOBALS['meta']['http_noproxy'] = $cur_http_noproxy;
				if ($page) {
					$erreurs['message_ok'] = _T('info_proxy_ok')."<br />$info\n<tt>".couper(entites_html($page),300)."</tt>";
				}
				else {
					$erreurs['message_erreur'] = $info._T('info_impossible_lire_page', array('test_proxy' => "<tt>$test_proxy</tt>"))
					. " <b><tt>".no_password_proxy_url($http_proxy)."</tt></b>."
					. aide('confhttpproxy');
				}
			}

		}
	}
	return $erreurs;
}

function formulaires_configurer_relayeur_traiter_dist(){
	$res = array('editable'=>true);

	$http_proxy = relayeur_saisie_ou_config(_request('http_proxy'), lire_config('http_proxy', ''));
	$http_noproxy = _request('http_noproxy');
	if ($http_proxy !== NULL)
		ecrire_meta('http_proxy', $http_proxy);

	if ($http_noproxy !== NULL)
		ecrire_meta('http_noproxy', $http_noproxy);
	
	$res['message_ok'] = _T('config_info_enregistree');
	return $res;
}

function relayeur_saisie_ou_config($http_proxy, $default){
	// http_proxy : ne pas prendre en compte la modif si le password est '****'
	if (preg_match(',:\*\*\*\*@,', $http_proxy))
		$http_proxy = $default;
	return $http_proxy;
}

// Function glue_url : le pendant de parse_url
// http://doc.spip.org/@glue_url
function glue_url ($url){
	if (!is_array($url)){
		return false;
	}
	// scheme
	$uri = (!empty($url['scheme'])) ? $url['scheme'].'://' : '';
	// user & pass
	if (!empty($url['user'])){
		$uri .= $url['user'].':'.$url['pass'].'@';
	}
	// host
	$uri .= $url['host'];
	// port
	$port = (!empty($url['port'])) ? ':'.$url['port'] : '';
	$uri .= $port;
	// path
	$uri .= $url['path'];
// fragment or query
	if (isset($url['fragment'])){
		$uri .= '#'.$url['fragment'];
	} elseif (isset($url['query'])){
		$uri .= '?'.$url['query'];
	}
	return $uri;
}


// Ne pas afficher la partie 'password' du proxy
// http://doc.spip.org/@no_password_proxy_url
function no_password_proxy_url($http_proxy) {
	if ($http_proxy
	AND $p = @parse_url($http_proxy)
	AND isset($p['pass'])
	AND $p['pass']) {
		$p['pass'] = '****';
		$http_proxy = glue_url($p);
	}
	return $http_proxy;
}
?>
