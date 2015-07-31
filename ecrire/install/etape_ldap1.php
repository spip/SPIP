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

// http://doc.spip.org/@inc_install_ldap1
function install_etape_ldap1_dist()
{
	global $spip_lang_right;

	$protocole_ldap = 3; // on essaie 2 en cas d'echec
	$adresse_ldap = 'localhost';
	$port_ldap = 389;

	// Recuperer les anciennes donnees (si presentes)
	if (@file_exists(_FILE_CONNECT_INS . _FILE_TMP . '.php')) {
		$s = @join('', @file(_FILE_CONNECT_INS . _FILE_TMP . '.php'));
		if (ereg('ldap_connect\("(.*)","(.*)"\)', $s, $regs)) {
			$adresse_ldap = $regs[1];
			$port_ldap = $regs[2];
		}
	}
	echo install_debut_html();

	echo info_etape(_T('titre_connexion_ldap'), _T('entree_informations_connexion_ldap'));

	echo generer_url_post_ecrire('install');
	echo "<input type='hidden' name='etape' value='ldap2' />";

	echo fieldset(_T('entree_adresse_annuaire'),
		array(
			'adresse_ldap' => array(
				'label' => _T('texte_adresse_annuaire_1'),
				'valeur' => $adresse_ldap
			),
			'port_ldap' => array(
				'label' => _T('entree_port_annuaire').'<br />'._T('texte_port_annuaire'),
				'valeur' => $port_ldap
			),
			'tls_ldap' => array(
				'label' => '<b>'._L('Transport Layer Security :').'</b>',
				'valeur' => 'non',
				'alternatives' => array(
					'non' => _T('item_non'),
					'oui' => _T('item_oui')
				)
			),
			'protocole_ldap' => array(
				'label' => _L('Version du protocole :'),
				'valeur' => $protocole_ldap,
				'alternatives' => array(
					'3' => '3',
					'2' => '2'
				)
			)
		)
	);

	echo '<p>'._T('texte_acces_ldap_anonyme_1').'</p>';
	echo fieldset(_L('Connexion:'),
		array(
			'login_ldap' => array(
				'label' => _T('texte_login_ldap_1'),
				'valeur' => ''
			),
			'pass_ldap' => array(
				'label' => _T('entree_passe_ldap'),
				'vaelur' => ''
			)
		)
	);

	echo bouton_suivant();
	echo "</form>";

	echo install_fin_html();
}

?>