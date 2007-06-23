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

include_spip('inc/presentation');

/// A PASSER DANS LES FICHIERS DE LANGUE

_T('texte_test_proxy'); // force le chargement du fichier de langue
// pour ecraser la valeur ci-dessous ensuite 
$GLOBALS[$GLOBALS['idx_lang']]['texte_proxy'] = 'Dans certains cas
		(intranet, r&eacute;seaux prot&eacute;g&eacute;s...),
		les sites distants
		(documentation de SPIP, sites syndiqu&eacute;s etc.)
		ne sont accessibles qu\'&agrave; travers un
		<i>proxy HTTP</i>.
		Le cas &eacute;ch&eacute;ant,
		indiquez ci-dessous son adresse, sous la forme
		@proxy_en_cours@.
		En g&eacute;n&eacute;ral, vous laisserez cette case vide.';
// et un nouveau, gaffe au _L implicite
$GLOBALS[$GLOBALS['idx_lang']]['pas_de_proxy_pour'] = 'Au besoin, indiquez les machines ou domaines pour lesquels ce proxy ne doit pas s\'appliquer (par exemple: @exemple@)&nbsp;:';

function configuration_relayeur_dist($retour_proxy)
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
			. _T('texte_test_proxy')
			. "</p>"
			. "\n<p>"
			. "<input type='text' name='test_proxy' value='http://www.spip.net/' size='40' class='forml' />"
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

	$encours = $http_proxy ? $http_proxy : "http://proxy:8080";
	$exemple = $http_noproxy ? $http_noproxy : "127.0.0.1 .mondomaine.net";
	$res = "\n<div class='verdana2'>"
	  . propre(_T('texte_proxy', array('proxy_en_cours' => "<b><tt><html>$encours</html></tt></b>")))
	. "</div>"
	. "\n<div class='verdana2'>"
	. "<input type='text' name='http_proxy' size='40' class='forml' value='$http_proxy' />"
	. "<br />"
	  . propre(_T('pas_de_proxy_pour', array('exemple' => "<b><tt><html>$exemple</html></tt></b>")))
	. "<input type='text' name='http_noproxy' size='40' class='forml' value='$http_noproxy' />"
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

	$retour_proxy = '';
	if ($tester_proxy AND $http_proxy) {
		if (!$test_proxy) {
			$retour_proxy = _T('info_adresse_non_indiquee');
		} else {
			include_spip('inc/texte'); // pour aide, couper, lang
			if (strncmp("http://", $http_proxy,7)!=0)
			  $page = '';
			else {
			  include_spip('inc/distant');
			  $page = recuperer_page($test_proxy, true);
			}
			if ($page)
				$retour_proxy = "<p>"._T('info_proxy_ok')."</p>\n<tt>".couper(entites_html($page),300)."</tt>";
			else
				$retour_proxy = _T('info_impossible_lire_page', array('test_proxy' => $test_proxy))
				. " <tt>".no_password_proxy_url($http_proxy)."</tt>."
				. aide('confhttpproxy');
		}
	}
	if ($t = ($http_proxy !== NULL)) {
		ecrire_meta('http_proxy', $http_proxy);
	}
	if ($http_noproxy !== NULL) {
		ecrire_meta('http_noproxy', $http_noproxy);
		$t = true;
	}
	if ($t) ecrire_metas();
	return $retour_proxy;
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
