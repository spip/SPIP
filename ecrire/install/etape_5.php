<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

// http://doc.spip.org/@inc_install_5
function install_etape_5_dist()
{
	global $email, $ldap_present, $login, $nom, $pass, $spip_lang_right;

	install_debut_html();

	if (@file_exists(_FILE_CONNECT_INS . _FILE_TMP . '.php'))
		include(_FILE_CONNECT_INS . _FILE_TMP . '.php');
	else
		redirige_par_entete(generer_url_ecrire('install'));

	if (@file_exists(_FILE_CHMOD_INS . _FILE_TMP . '.php'))
		include(_FILE_CHMOD_INS . _FILE_TMP . '.php');
	else
		redirige_par_entete(generer_url_ecrire('install'));

	echo info_etape(_T('info_informations_personnelles'),
		"<b>"._T('texte_informations_personnelles_1')."</b>" .
		aide ("install5") .
		"</p><p>" .
		_T('texte_informations_personnelles_2') . " " .
		_T('info_laisser_champs_vides')
	);

	echo generer_url_post_ecrire('install');

	echo "<INPUT TYPE='hidden' NAME='etape' VALUE='6' />";

	echo fieldset(_T('info_identification_publique'),
		array(
			'nom' => array(
				'label' => "<b>"._T('entree_signature')."</b><br />\n"._T('entree_nom_pseudo_1')."\n",
				'value' => $nom
			),
			'email' => array(
				'label' => "<b>"._T('entree_adresse_email')."</b>\n",
				'value' => $email
			)
		)
	);

	echo fieldset(_T('entree_identifiants_connexion'),
		array(
			'login' => array(
				'label' => "<b>"._T('entree_login')."</b><br />\n"._T('info_plus_trois_car')."\n",
				'value' => $login
			),
			'pass' => array(
				'label' => "<b>"._T('entree_mot_passe')."</b><br />\n"._T('info_plus_cinq_car_2')."\n",
				'value' => $pass
			)
		)
	);

	echo bouton_suivant();
	echo "</form>\n";

	if (function_exists('ldap_connect') AND !$ldap_present) {
		echo generer_url_post_ecrire('install');
		echo fieldset(_T('info_authentification_externe'),
			array(
				'etape' => array(
					'label' => _T('texte_annuaire_ldap_1').bouton_suivant('bouton_acces_ldap'),
					'value' => 'ldap1',
					'hidden' => true
				)
			)
		);
		echo "</form>\n";
	}

	install_fin_html();
}

?>