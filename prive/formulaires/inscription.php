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

/**
 *
 * #FORMULAIRE_INSCRIPTION
 * #FORMULAIRE_INSCRIPTION{6forum}
 * #FORMULAIRE_INSCRIPTION{1comite,#ARRAY{id,#ENV{id_rubrique}}}
 *
 * Pour rediriger l'utilisateur apres soumission du formulaire vers une page qui lui dit de verifier ses mails par exemple :
 * #FORMULAIRE_INSCRIPTION{6forum,'',#URL_PAGE{verifiez-vos-mails}}
 *
 * Pour rediriger l'utilisateur apres Clic dans le lien du mail de confirmation, pour lui confirmer son inscription par exemple
 * #FORMULAIRE_INSCRIPTION{6forum,#ARRAY{redirect,#URL_PAGE{confirmation-inscription}}}
 *
 * Tout ensemble
 * #FORMULAIRE_INSCRIPTION{6forum,#ARRAY{redirect,#URL_PAGE{confirmation-inscription}}, #URL_PAGE{verifiez-vos-mails}}
 *
 * Syntaxe legacy :
 * #FORMULAIRE_INSCRIPTION{1comite,#ENV{id_rubrique}}
 *
 *
 * @param string $mode
 * @param array $options
 * @param string $retour
 * @return array|false
 */
function formulaires_inscription_charger_dist($mode = '', $options = [], $retour = '') {

	$id = ($options['id'] ?? 0);

	// fournir le mode de la config ou tester si l'argument du formulaire est un mode accepte par celle-ci
	// pas de formulaire si le mode est interdit
	include_spip('inc/autoriser');
	if (!autoriser('inscrireauteur', $mode, $id)) {
		return false;
	}

	// pas de formulaire si on a déjà une session avec un statut égal ou meilleur au mode
	if (isset($GLOBALS['visiteur_session']['statut']) and ($GLOBALS['visiteur_session']['statut'] <= $mode)) {
		return false;
	}


	$valeurs = array('nom_inscription' => '', 'mail_inscription' => '', 'id' => $id, '_mode' => $mode);

	return $valeurs;
}


/**
 * Si inscriptions pas autorisees, retourner une chaine d'avertissement
 *
 * @param string $mode
 * @param array $options
 * @param string $retour
 * @return array
 */
function formulaires_inscription_verifier_dist($mode = '', $options = [], $retour = '') {
	set_request('_upgrade_auteur'); // securite
	include_spip('inc/filtres');
	$erreurs = array();

	$id = ($options['id'] ?? 0);

	include_spip('inc/autoriser');
	if (!autoriser('inscrireauteur', $mode, $id)
		or (strlen(_request('nobot')) > 0)
	) {
		$erreurs['message_erreur'] = _T('pass_rien_a_faire_ici');
	}

	if (!$nom = _request('nom_inscription')) {
		$erreurs['nom_inscription'] = _T('info_obligatoire');
	} elseif (!nom_acceptable(_request('nom_inscription'))) {
		$erreurs['nom_inscription'] = _T('ecrire:info_nom_pas_conforme');
	}
	if (!$mail = strval(_request('mail_inscription'))) {
		$erreurs['mail_inscription'] = _T('info_obligatoire');
	}

	// compatibilite avec anciennes fonction surchargeables
	// plus de definition par defaut
	if (!count($erreurs)) {
		include_spip('action/inscrire_auteur');
		if (function_exists('test_inscription')) {
			$f = 'test_inscription';
		} else {
			$f = 'test_inscription_dist';
		}
		$declaration = $f($mode, $mail, $nom, $options);
		if (is_string($declaration)) {
			$k = (strpos($declaration, 'mail') !== false) ?
				'mail_inscription' : 'nom_inscription';
			$erreurs[$k] = _T($declaration);
		} else {
			include_spip('base/abstract_sql');

			if ($row = sql_fetsel(
				'statut, id_auteur, login, email',
				'spip_auteurs',
				'email=' . sql_quote($declaration['email'])
			)) {
				if (($row['statut'] == '5poubelle') and empty($declaration['pass'])) {
					// irrecuperable
					$erreurs['message_erreur'] = _T('form_forum_access_refuse');
				} else {
					if (($row['statut'] != 'nouveau') and empty($declaration['pass'])) {
						if (intval($row['statut']) > intval($mode)) {
							set_request('_upgrade_auteur', $row['id_auteur']);
						} else {
							// deja inscrit
							$erreurs['message_erreur'] = _T('form_forum_email_deja_enregistre');
						}
					}
				}
				spip_log($row['id_auteur'] . ' veut se resinscrire');
			}
		}
	}

	return $erreurs;
}

/**
 * Si inscriptions pas autorisees, retourner une chaine d'avertissement
 *
 * @param string $mode
 * @param array $options
 * @param string $retour
 * @return array
 */
function formulaires_inscription_traiter_dist($mode = '', array $options = [], $retour = '') {
	if ($retour) {
		refuser_traiter_formulaire_ajax();
	}

	include_spip('inc/filtres');
	include_spip('inc/autoriser');

	$id = ($options['id'] ?? 0);

	if (!autoriser('inscrireauteur', $mode, $id)) {
		$desc = 'rien a faire ici';
	} else {
		if ($id_auteur = _request('_upgrade_auteur')) {
			include_spip('action/editer_auteur');
			autoriser_exception('modifier', 'auteur', $id_auteur);
			autoriser_exception('instituer', 'auteur', $id_auteur);
			auteur_modifier($id_auteur, array('statut' => $mode));
			autoriser_exception('modifier', 'auteur', $id_auteur, false);
			autoriser_exception('instituer', 'auteur', $id_auteur, false);

			return array('message_ok' => _T('form_forum_email_deja_enregistre'), 'id_auteur' => $id_auteur);
		}

		$nom = _request('nom_inscription');
		$mail_complet = _request('mail_inscription');

		$inscrire_auteur = charger_fonction('inscrire_auteur', 'action');
		$desc = $inscrire_auteur($mode, $mail_complet, $nom, $options);
	}

	// erreur ?
	if (is_string($desc)) {
		return array('message_erreur' => $desc);
	} // OK
	else {
		$retours = array(
			'message_ok' => _T('form_forum_identifiant_mail'),
			'id_auteur' => $desc['id_auteur'],
		);

		// Si on demande à rediriger juste après validation du formulaire
		if ($retour) {
			$retours['redirect'] = $retour;
		}

		return $retours;
	}
}
