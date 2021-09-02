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

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// https://code.spip.net/@install_etape_ldap2_dist
function install_etape_ldap2_dist() {
	echo install_debut_html('AUTO', ' onload="document.getElementById(\'suivant\').focus();return false;"');

	$adresse_ldap = _request('adresse_ldap');

	$port_ldap = _request('port_ldap');

	$tls_ldap = _request('tls_ldap');

	$protocole_ldap = _request('protocole_ldap');

	$login_ldap = _request('login_ldap');

	$pass_ldap = _request('pass_ldap');

	$port_ldap = intval($port_ldap);

	$tls = false;

	if ($tls_ldap == 'oui') {
		if ($port_ldap == 636) {
			$adresse_ldap = "ldaps://$adresse_ldap";
		} else {
			$tls = true;
		}
	}
	$ldap_link = ldap_connect($adresse_ldap, $port_ldap);
	$erreur = "ldap_connect($adresse_ldap, $port_ldap)";

	if ($ldap_link) {
		if (!ldap_set_option($ldap_link, LDAP_OPT_PROTOCOL_VERSION, $protocole_ldap)) {
			$protocole_ldap = 2;
			ldap_set_option($ldap_link, LDAP_OPT_PROTOCOL_VERSION, $protocole_ldap);
		}
		if ($tls === true) {
			if (!ldap_start_tls($ldap_link)) {
				$erreur = "ldap_start_tls($ldap_link) $adresse_ldap, $port_ldap";
				$ldap_link = false;
			}
		}
		if ($ldap_link) {
			$ldap_link = ldap_bind($ldap_link, $login_ldap, $pass_ldap);
			$erreur = "ldap_bind('$ldap_link', '$login_ldap', '$pass_ldap'): $adresse_ldap, $port_ldap";
		}
	}

	if ($ldap_link) {
		echo info_etape(
			_T('titre_connexion_ldap'),
			info_progression_etape(2, 'etape_ldap', 'install/')
		),  _T('info_connexion_ldap_ok');
		echo generer_form_ecrire('install', (
			"\n<input type='hidden' name='etape' value='ldap3' />"
			. "\n<input type='hidden' name='adresse_ldap' value=\"$adresse_ldap\" />"
			. "\n<input type='hidden' name='port_ldap' value=\"$port_ldap\" />"
			. "\n<input type='hidden' name='login_ldap' value=\"$login_ldap\" />"
			. "\n<input type='hidden' name='pass_ldap' value=\"$pass_ldap\" />"
			. "\n<input type='hidden' name='protocole_ldap' value=\"$protocole_ldap\" />"
			. "\n<input type='hidden' name='tls_ldap' value=\"$tls_ldap\" />"
			. bouton_suivant()));
	} else {
		echo info_etape(_T('titre_connexion_ldap')), info_progression_etape(1, 'etape_ldap', 'install/', true),
			"<div class='error'><p>" . _T('avis_connexion_ldap_echec_1') . '</p>',
			'<p>' . _T('avis_connexion_ldap_echec_2') .
			"<br />\n" . _T('avis_connexion_ldap_echec_3') .
			'<br /><br />' . $erreur . '<b> ?</b></p></div>';
	}

	echo install_fin_html();
}
