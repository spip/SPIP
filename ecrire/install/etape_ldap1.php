<?php

use Spip\Afficher\Minipage\Installation;

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

function install_etape_ldap1_dist() {
	$adresse_ldap = defined('_INSTALL_HOST_LDAP')
		? _INSTALL_HOST_LDAP
		: 'localhost';

	$port_ldap = defined('_INSTALL_PORT_LDAP')
		? _INSTALL_PORT_LDAP
		: 389;

	$tls_ldap = defined('_INSTALL_TLS_LDAP')
		? _INSTALL_TLS_LDAP
		: 'non';

	$protocole_ldap = defined('_INSTALL_PROTOCOLE_LDAP')
		? _INSTALL_PROTOCOLE_LDAP
		: 3; // on essaie 2 en cas d'echec

	$login_ldap = defined('_INSTALL_USER_LDAP')
		? _INSTALL_USER_LDAP
		: '';

	$pass_ldap = defined('_INSTALL_PASS_LDAP')
		? _INSTALL_PASS_LDAP
		: '';

	$minipage = new Installation();
	echo $minipage->installDebutPage(['onload' => 'document.getElementById(\'suivant\').focus();return false;']);

	echo info_etape(
		_T('titre_connexion_ldap'),
		info_progression_etape(1, 'etape_ldap', 'install/')
	);

	echo generer_form_ecrire('install', (
		"\n<input type='hidden' name='etape' value='ldap2'>"
		. fieldset(
			_T('entree_adresse_annuaire'),
			[
				'adresse_ldap' => [
					'label' => _T('texte_adresse_annuaire_1'),
					'valeur' => $adresse_ldap
				],
				'port_ldap' => [
					'label' => _T('entree_port_annuaire') . '<br />' . _T('texte_port_annuaire'),
					'valeur' => $port_ldap
				],
				'tls_ldap' => [
					'label' => '<b>' . _T('tls_ldap') . '</b>',
					'valeur' => $tls_ldap,
					'alternatives' => [
						'non' => _T('item_non'),
						'oui' => _T('item_oui')
					]
				],
				'protocole_ldap' => [
					'label' => _T('protocole_ldap'),
					'valeur' => $protocole_ldap,
					'alternatives' => [
						'3' => '3',
						'2' => '2'
					]
				]
			]
		)

		. "\n<p>" . _T('texte_acces_ldap_anonyme_1') . '</p>'
		. fieldset(
			_T('connexion_ldap'),
			[
				'login_ldap' => [
					'label' => _T('texte_login_ldap_1'),
					'valeur' => $login_ldap
				],
				'pass_ldap' => [
					'label' => _T('entree_passe_ldap'),
					'valeur' => $pass_ldap
				]
			]
		)
		. bouton_suivant()));

	echo $minipage->installFinPage();
}
