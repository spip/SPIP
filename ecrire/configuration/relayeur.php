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

include_spip('inc/presentation');

/// A PASSER DANS LES FICHIERS DE LANGUE

function configuration_relayeur_dist($retour_proxy='')
{
	global $spip_lang_left;

	$res = $submit = '';

	$http_proxy = $GLOBALS['meta']["http_proxy"];
	$http_noproxy = $GLOBALS['meta']["http_noproxy"];

	if ($http_proxy) {
		include_spip('inc/distant');
		// Masquer un eventuel password authentifiant
		$http_proxy=entites_html(no_password_proxy_url($http_proxy));
		if ($http_proxy) {
			$res = "\n<p style='text-align: $spip_lang_left;' class='verdana1 spip_small'>"
			. "<label for='test_proxy'>" 
			. _T('texte_test_proxy')
			. "</label>"
			. "</p>"
			. "\n<p>"
			. "<input type='text' name='test_proxy' id='test_proxy' value='http://www.spip.net/' size='40' class='forml' />"
			. "</p>";

			if($retour_proxy) {
				$res .= debut_boite_info(true)
				. $retour_proxy
				. fin_boite_info(true);
			}
			$submit = array('valider_proxy' => _T('bouton_valider'),
					'tester_proxy' => _T('bouton_test_proxy'));
		}
	}

	$encours = "<label for='http_proxy'>" . ($http_proxy ? $http_proxy : "http://proxy:8080") . '</label>' ;
	$exemple = "<label for='http_noproxy'>" . ($http_noproxy ? $http_noproxy : "127.0.0.1 .mondomaine.net") . "</label>";
	$res = "\n<div class='verdana2'>"
	  . propre(_T('texte_proxy', array('proxy_en_cours' => "<b><tt><html>$encours</html></tt></b>")))
	  . "</div>"
	  . "\n<div class='verdana2'>"
	  . "<input type='text' name='http_proxy' id='http_proxy' size='40' class='forml' value='$http_proxy' />"
	  . "<br />"
	  . propre(_T('pas_de_proxy_pour', array('exemple' => "<b><tt><html>$exemple</html></tt></b>")))
	  . "<input type='text' name='http_noproxy' id='http_noproxy' size='40' class='forml' value='$http_noproxy' />"
	  . $res
	  . "</div>";

	$res = debut_cadre_trait_couleur("base-24.gif", true, "", _T('info_sites_proxy').aide ("confhttpproxy"))
	.  ajax_action_post('configurer_relayeur', 0, 'config_fonctions', '', $res, $submit)
	.  fin_cadre_trait_couleur(true);

	return ajax_action_greffe("configurer_relayeur", 0, $res);
}

function configuration_relayeur_post ($http_proxy, $http_noproxy, $test_proxy, $tester_proxy) 
{
	// http_proxy : ne pas prendre en compte la modif si le password est '****'
	if (preg_match(',:\*\*\*\*@,', $http_proxy))
		$http_proxy = $GLOBALS['meta']['http_proxy'];

	$retour = $page = '';

	if ($tester_proxy AND preg_match(",https?://,", $http_proxy)) {
		include_spip('inc/distant');
		$t = parse_url($test_proxy);

		if (!@$t['host']) {
			$retour = _T('info_adresse_non_indiquee');
		} elseif (!need_proxy($t['host'])) {

			$retour = "<p>"._T('page_pas_proxy')."</p>\n";
		} elseif ($page = recuperer_page($test_proxy, true)) {
			include_spip('inc/texte'); // pour aide, couper, lang
			$retour = "<p>"._T('info_proxy_ok')."</p>\n<tt>"
			. couper(entites_html($page),300)."</tt>";
		} else	$retour = _T('info_impossible_lire_page',
					array('test_proxy' => $test_proxy))
			. " <tt>".no_password_proxy_url($http_proxy)."</tt>."
			. aide('confhttpproxy');
	}

	if ($http_proxy !== NULL) {
		ecrire_meta('http_proxy', $http_proxy);
	}
	if ($http_noproxy !== NULL) {
		ecrire_meta('http_noproxy', $http_noproxy);
	}
	if ($page) {
		include_spip('install/etape_fin');
		$retour .= install_verifier_htaccess();
	}
	return $retour;
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
	if ($p = @parse_url($http_proxy)
	AND $p['pass']) {
		$p['pass'] = '****';
		$http_proxy = glue_url($p);
	}
	return $http_proxy;
}
?>
