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

include_spip('auth/ldap');

function install_etape_ldap4_dist() {
	$adresse_ldap = _request('adresse_ldap');
	$login_ldap = _request('login_ldap');
	$pass_ldap = _request('pass_ldap');
	$port_ldap = _request('port_ldap');
	$base_ldap = _request('base_ldap');
	$base_ldap_text = _request('base_ldap_text');
	if (!$base_ldap) {
		$base_ldap = $base_ldap_text;
	}

	$minipage = new Installation();
	echo $minipage->installDebutPage(['onload' => 'document.getElementById(\'suivant\').focus();return false;']);

	$ldap_link = ldap_connect($adresse_ldap, $port_ldap);
	@ldap_bind($ldap_link, $login_ldap, $pass_ldap);

	// Essayer de verifier le chemin fourni
	$r = @ldap_compare($ldap_link, $base_ldap, 'objectClass', '');
	$fail = (ldap_errno($ldap_link) == 32);

	if ($fail) {
		echo info_etape(_T('info_chemin_acces_annuaire')),
		info_progression_etape(3, 'etape_ldap', 'install/', true),
			"<div class='error'><p><b>" . _T('avis_operation_echec') . '</b></p><p>' . _T('avis_chemin_invalide_1'),
			' (<tt>' . spip_htmlspecialchars($base_ldap) . '</tt>) ' . _T('avis_chemin_invalide_2') . '</p></div>';
	} else {
		info_etape(_T('info_reglage_ldap'));
		echo info_progression_etape(4, 'etape_ldap', 'install/');

		$statuts = liste_statuts_ldap();
		$statut_ldap = defined('_INSTALL_STATUT_LDAP')
			? _INSTALL_STATUT_LDAP
			: $GLOBALS['liste_des_statuts']['info_redacteurs'];


		$res = install_propager(['adresse_ldap', 'port_ldap', 'login_ldap', 'pass_ldap', 'protocole_ldap', 'tls_ldap'])
			. "<input type='hidden' name='etape' value='ldap5'>"
			. "<input type='hidden' name='base_ldap' value='" . spip_htmlentities($base_ldap) . "'>"
			. fieldset(
				_T('info_statut_utilisateurs_1'),
				[
					'statut_ldap' => [
						'label' => _T('info_statut_utilisateurs_2') . '<br />',
						'valeur' => $statut_ldap,
						'alternatives' => $statuts
					]
				]
			)
			. install_ldap_correspondances()
			. bouton_suivant();

		echo generer_form_ecrire('install', $res);
	}

	echo $minipage->installFinPage();
}

function liste_statuts_ldap() {
	$recom = [
		'info_administrateurs' => ('<b>' . _T('info_administrateur_1') . '</b> ' . _T('info_administrateur_2') . '<br />'),
		'info_redacteurs' => ('<b>' . _T('info_redacteur_1') . '</b> ' . _T('info_redacteur_2') . '<br />'),
		'info_visiteurs' => ('<b>' . _T('info_visiteur_1') . '</b> ' . _T('info_visiteur_2') . '<br />')
	];

	$res = [];
	foreach ($GLOBALS['liste_des_statuts'] as $k => $v) {
		if (isset($recom[$k])) {
			$res[$v] = $recom[$k];
		}
	}

	return $res;
}

function install_ldap_correspondances() {
	$champs = [];
	foreach (is_array($GLOBALS['ldap_attributes']) ? $GLOBALS['ldap_attributes'] : [] as $champ => $v) {
		$nom = 'ldap_' . $champ;
		$val = is_array($v) ? implode(',', $v) : (string) $v;
		$champs[$nom] = [
			'label' => _T('ldap_correspondance', ['champ' => "<tt>$champ</tt>"]) . '<br />',
			'valeur' => $val
		];
	}

	return $champs ? fieldset(_T('ldap_correspondance_1'), $champs, '', _T('ldap_correspondance_2') . '<br /><br />') : '';
}
