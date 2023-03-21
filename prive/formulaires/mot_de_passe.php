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

include_spip('base/abstract_sql');

function retrouve_auteur($id_auteur, $jeton = '') {
	if ($id_auteur = (int) $id_auteur) {
		return sql_fetsel(
			'*',
			'spip_auteurs',
			['id_auteur=' . (int) $id_auteur, "statut<>'5poubelle'", "pass<>''", "login<>''"]
		);
	} elseif ($jeton) {
		include_spip('action/inscrire_auteur');
		if (
			($auteur = auteur_verifier_jeton($jeton))
			&& $auteur['statut'] != '5poubelle'
			&& $auteur['pass'] != ''
			&& $auteur['login'] != ''
		) {
			return $auteur;
		}
	}

	return false;
}

// chargement des valeurs par defaut des champs du formulaire
/**
 * Chargement de l'auteur qui peut changer son mot de passe.
 * Soit un cookie d'oubli fourni par #FORMULAIRE_OUBLI est passe dans l'url par &p=
 * Soit un id_auteur est passe en parametre #FORMULAIRE_MOT_DE_PASSE{#ID_AUTEUR}
 * Dans les deux cas on verifie que l'auteur est autorise
 *
 * @param int $id_auteur
 * @return array
 */
function formulaires_mot_de_passe_charger_dist($id_auteur = null, $jeton = null) {

	$valeurs = [];
	// compatibilite anciens appels du formulaire
	if (is_null($jeton)) {
		$jeton = _request('p');
	}
	$auteur = retrouve_auteur($id_auteur, $jeton);

	if ($auteur) {
		$valeurs['id_auteur'] = $id_auteur; // a toutes fins utiles pour le formulaire
		if ($jeton) {
			$valeurs['_hidden'] = '<input type="hidden" name="p" value="' . $jeton . '" />';
		}
	} else {
		$valeurs['message_erreur'] = _T('pass_erreur_code_inconnu');
		$valeurs['editable'] = false; // pas de saisie
	}
	$valeurs['oubli'] = '';
	// le champ login n'est pas utilise, mais il est destine aux navigateurs smarts
	// qui veulent remplir le formulaire avec login/mot de passe
	// et qui sinon remplissent le champ nobot (autocomplete=off n'est pas une option, certains navigateurs l'ignorant)
	$valeurs['login'] = '';
	$valeurs['nobot'] = '';

	return $valeurs;
}

/**
 * Verification de la saisie du mot de passe.
 * On verifie qu'un mot de passe est saisi, et que sa longuer est suffisante
 * Ce serait le lieu pour verifier sa qualite (caracteres speciaux ...)
 *
 * @param int $id_auteur
 */
function formulaires_mot_de_passe_verifier_dist($id_auteur = null, $jeton = null) {
	$erreurs = [];
	if (!_request('oubli')) {
		$erreurs['oubli'] = _T('info_obligatoire');
	} else {
		if (strlen((string) ($p = _request('oubli'))) < _PASS_LONGUEUR_MINI) {
			$erreurs['oubli'] = _T('info_passe_trop_court_car_pluriel', ['nb' => _PASS_LONGUEUR_MINI]);
		} else {
			if (!is_null($c = _request('oubli_confirm'))) {
				if (!$c) {
					$erreurs['oubli_confirm'] = _T('info_obligatoire');
				} elseif ($c !== $p) {
					$erreurs['oubli'] = _T('info_passes_identiques');
				}
			}
		}
	}
	if (isset($erreurs['oubli'])) {
		set_request('oubli');
		set_request('oubli_confirm');
	}

	if (_request('nobot')) {
		$erreurs['message_erreur'] = _T('pass_rien_a_faire_ici');
	}
	// precaution
	if (_request('login')) {
		set_request('login');
	}

	return $erreurs;
}

/**
 * Modification du mot de passe d'un auteur.
 * Utilise le cookie d'oubli fourni en url ou l'argument du formulaire pour identifier l'auteur
 *
 * @param int $id_auteur
 */
function formulaires_mot_de_passe_traiter_dist($id_auteur = null, $jeton = null) {
	$res = ['message_ok' => ''];
	refuser_traiter_formulaire_ajax(); // puisqu'on va loger l'auteur a la volee (c'est bonus)

	// compatibilite anciens appels du formulaire
	if (is_null($jeton)) {
		$jeton = _request('p');
	}
	$row = retrouve_auteur($id_auteur, $jeton);

	if (
		$row
		&& ($id_auteur = $row['id_auteur'])
		&& ($oubli = _request('oubli'))
	) {
		include_spip('action/editer_auteur');
		include_spip('action/inscrire_auteur');
		if ($err = auteur_modifier($id_auteur, ['pass' => $oubli])) {
			$res = ['message_erreur' => $err];
		} else {
			auteur_effacer_jeton($id_auteur);

			// Par défaut, on rappelle de s'identifier avec son email s'il existe
			// et qu'il n'est PAS utilisé par quelqu'un d'autre
			if (
				$row['email'] && !sql_fetsel(
					'id_auteur',
					'spip_auteurs',
					[
						'(email=' . sql_quote($row['email']) . ' or login=' . sql_quote($row['email']) . ')',
						'id_auteur != ' . $id_auteur
					],
					'',
					'',
					'0,1'
				)
			) {
				$identifiant = $row['email'];
			}
			// Sinon on dit d'utiliser le login
			else {
				$identifiant = $row['login'];
			}
			$res['message_ok'] = '<b>' . _T('pass_nouveau_enregistre') . '</b>' .
				'<br />' . _T('pass_rappel_login', ['login' => $identifiant]);

			include_spip('inc/auth');
			$auth = auth_identifier_login($row['login'], $oubli);
			if (!is_array($auth)) {
				spip_log('Erreur identification ' . $row['login'] . " après changement de mot de passe: $auth", _LOG_ERREUR);
			}
			elseif ($auth['id_auteur'] == $id_auteur) {
				auth_loger($auth);
			}
		}
	}

	return $res;
}
