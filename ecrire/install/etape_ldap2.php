<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function install_etape_ldap2_dist() {
	$minipage = new Spip\Afficher\Minipage\Installation();
	echo $minipage->installDebutPage(['onload' => 'document.getElementById(\'suivant\').focus();return false;']);

	$adresse_ldap = _request('adresse_ldap');

	$port_ldap = _request('port_ldap');

	$tls_ldap = _request('tls_ldap');

	$protocole_ldap = _request('protocole_ldap');

	$login_ldap = _request('login_ldap');

	$pass_ldap = _request('pass_ldap');

	$port_ldap = (int) $port_ldap;

	$tls = false;

	if ($tls_ldap == 'oui') {
		if ($port_ldap == 636) {
			$adresse_ldap = "ldaps://$adresse_ldap";
		} else {
			$tls = true;
		}
	}
	else {
		$tls_ldap == 'non';
	}

	// Verifions que l'adresse demandee est valide
	$adresse_ldap = filter_var($adresse_ldap, FILTER_SANITIZE_URL) ?: '';

	$ldap_link = ldap_connect($adresse_ldap, $port_ldap);
	$erreur = 'ldap_connect(' . spip_htmlspecialchars($adresse_ldap) . ', ' . spip_htmlspecialchars($port_ldap) . ')';

	if ($ldap_link) {
		if (!ldap_set_option($ldap_link, LDAP_OPT_PROTOCOL_VERSION, $protocole_ldap)) {
			$protocole_ldap = 2;
			ldap_set_option($ldap_link, LDAP_OPT_PROTOCOL_VERSION, $protocole_ldap);
		}
		if ($tls && !ldap_start_tls($ldap_link)) {
			$erreur = 'ldap_start_tls(' . spip_htmlspecialchars($ldap_link)
				. ' ' . spip_htmlspecialchars($adresse_ldap)
				. ', ' . spip_htmlspecialchars($port_ldap) . ')';
			$ldap_link = false;
		}
		if ($ldap_link) {
			$ldap_link = ldap_bind($ldap_link, $login_ldap, $pass_ldap);
			$erreur = "ldap_bind('" . spip_htmlspecialchars($ldap_link)
				. "', '" . spip_htmlspecialchars($login_ldap)
				. "', '" . spip_htmlspecialchars($pass_ldap)
				. "'): " . spip_htmlspecialchars($adresse_ldap)
				. ', ' . spip_htmlspecialchars($port_ldap);
		}
	}

	if ($ldap_link) {
		echo info_etape(
			_T('titre_connexion_ldap'),
			info_progression_etape(2, 'etape_ldap', 'install/')
		),  _T('info_connexion_ldap_ok');
		echo generer_form_ecrire('install', (
			"\n<input type='hidden' name='etape' value='ldap3' />"
			. "\n<input type='hidden' name='adresse_ldap' value=\"" . spip_htmlspecialchars($adresse_ldap) . '" />'
			. "\n<input type='hidden' name='port_ldap' value=\"" . spip_htmlspecialchars($port_ldap) . '" />'
			. "\n<input type='hidden' name='login_ldap' value=\"" . spip_htmlspecialchars($login_ldap) . '" />'
			. "\n<input type='hidden' name='pass_ldap' value=\"" . spip_htmlspecialchars($pass_ldap) . '" />'
			. "\n<input type='hidden' name='protocole_ldap' value=\"" . spip_htmlspecialchars($protocole_ldap) . '" />'
			. "\n<input type='hidden' name='tls_ldap' value=\"" . spip_htmlspecialchars($tls_ldap) . '" />'
			. bouton_suivant()));
	} else {
		echo info_etape(_T('titre_connexion_ldap')), info_progression_etape(1, 'etape_ldap', 'install/', true),
			"<div class='error'><p>" . _T('avis_connexion_ldap_echec_1') . '</p>',
			'<p>' . _T('avis_connexion_ldap_echec_2') .
			"<br />\n" . _T('avis_connexion_ldap_echec_3') .
			'<br /><br />' . $erreur . '<b> ?</b></p></div>';
	}

	echo $minipage->installFinPage();
}
