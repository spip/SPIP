<?php

/**
 * Gestion du formulaire de d'édition de rubrique
 *
 * @package SPIP\Core\Auteurs\Formulaires
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/actions');
include_spip('inc/editer');
include_spip('inc/filtres_ecrire'); // si on utilise le formulaire dans le public
include_spip('inc/autoriser');

/**
 * Chargement du formulaire d'édition d'un auteur
 *
 * @see formulaires_editer_objet_charger()
 *
 * @param int|string $id_auteur
 *     Identifiant de l'auteur. 'new' pour une nouvel auteur.
 * @param string $retour
 *     URL de redirection après le traitement
 * @param string $associer_objet
 *     Éventuel 'objet|x' indiquant de lier le mot créé à cet objet,
 *     tel que 'article|3'
 * @param string $config_fonc
 *     Nom de la fonction ajoutant des configurations particulières au formulaire
 * @param array $row
 *     Valeurs de la ligne SQL de l'auteur, si connu
 * @param string $hidden
 *     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
 * @return array
 *     Environnement du formulaire
 **/
function formulaires_editer_auteur_charger_dist(
	$id_auteur = 'new',
	$retour = '',
	$associer_objet = '',
	$config_fonc = 'auteurs_edit_config',
	$row = [],
	$hidden = ''
) {
	$valeurs = formulaires_editer_objet_charger('auteur', $id_auteur, 0, 0, $retour, $config_fonc, $row, $hidden);
	$valeurs['new_login'] = $valeurs['login'];

	if (!autoriser('modifier', 'auteur', intval($id_auteur))) {
		$valeurs['editable'] = '';
	}

	return $valeurs;
}

/**
 * Identifier le formulaire en faisant abstraction des paramètres qui
 * ne représentent pas l'objet édité
 *
 * @param int|string $id_auteur
 *     Identifiant de l'auteur. 'new' pour une nouvel auteur.
 * @param string $retour
 *     URL de redirection après le traitement
 * @param string $associer_objet
 *     Éventuel 'objet|x' indiquant de lier le mot créé à cet objet,
 *     tel que 'article|3'
 * @param string $config_fonc
 *     Nom de la fonction ajoutant des configurations particulières au formulaire
 * @param array $row
 *     Valeurs de la ligne SQL de l'auteur, si connu
 * @param string $hidden
 *     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
 * @return string
 *     Hash du formulaire
 */
function formulaires_editer_auteur_identifier_dist(
	$id_auteur = 'new',
	$retour = '',
	$associer_objet = '',
	$config_fonc = 'auteurs_edit_config',
	$row = [],
	$hidden = ''
) {
	return serialize([intval($id_auteur), $associer_objet]);
}


/**
 * Choix par défaut des options de présentation
 *
 * @param array $row
 *     Valeurs de la ligne SQL d'un auteur, si connu
 * return array
 *     Configuration pour le formulaire
 */
function auteurs_edit_config(array $row): array {

	$config = [];
	$config['lignes'] = 8;
	$config['langue'] = $GLOBALS['spip_lang'];

	// pour instituer_auteur
	$config['auteur'] = $row;

	//$config['restreint'] = ($row['statut'] == 'publie');
	$auth_methode = $row['source'];
	include_spip('inc/auth');
	$config['edit_login'] =
		(auth_autoriser_modifier_login($auth_methode)
			and autoriser('modifier', 'auteur', $row['id_auteur'], null, ['login' => true])
			// legacy : ne pas risquer d'autoriser la modif login si fonction d'autorisation pas mise a jour et ne teste que l'option email
			and autoriser('modifier', 'auteur', $row['id_auteur'], null, ['email' => true])
		);
	$config['edit_pass'] =
		(auth_autoriser_modifier_pass($auth_methode)
			and autoriser('modifier', 'auteur', $row['id_auteur']));

	return $config;
}

/**
 * Vérifications du formulaire d'édition d'un auteur
 *
 * Vérifie en plus des vérifications prévues :
 * - qu'un rédacteur ne peut pas supprimer son adresse mail,
 * - que le mot de passe choisi n'est pas trop court et identique à sa
 *   deuxième saisie
 *
 * @see formulaires_editer_objet_verifier()
 *
 * @param int|string $id_auteur
 *     Identifiant de l'auteur. 'new' pour une nouvel auteur.
 * @param string $retour
 *     URL de redirection après le traitement
 * @param string $associer_objet
 *     Éventuel 'objet|x' indiquant de lier le mot créé à cet objet,
 *     tel que 'article|3'
 * @param string $config_fonc
 *     Nom de la fonction ajoutant des configurations particulières au formulaire
 * @param array $row
 *     Valeurs de la ligne SQL de l'auteur, si connu
 * @param string $hidden
 *     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
 * @return array
 *     Erreurs des saisies
 **/
function formulaires_editer_auteur_verifier_dist(
	$id_auteur = 'new',
	$retour = '',
	$associer_objet = '',
	$config_fonc = 'auteurs_edit_config',
	$row = [],
	$hidden = ''
) {
	// auto-renseigner le nom si il n'existe pas, sans couper
	titre_automatique('nom', ['email', 'login'], 255);

	$oblis = ['nom'];
	// si on veut renvoyer des identifiants il faut un email et un login
	if (_request('reset_password')) {
		$oblis[] = 'email';
		$oblis[] = 'new_login';
	}
	// mais il reste obligatoire si on a rien trouve
	$erreurs = formulaires_editer_objet_verifier('auteur', $id_auteur, $oblis);
	if (isset($erreurs['new_login'])) {
		$erreurs['login'] = $erreurs['new_login'];
		unset($erreurs['new_login']);
	}

	$auth_methode = sql_getfetsel('source', 'spip_auteurs', 'id_auteur=' . intval($id_auteur));
	$auth_methode = ($auth_methode ?: 'spip');
	include_spip('inc/auth');

	if (!nom_acceptable(_request('nom'))) {
		$erreurs['nom'] = _T('info_nom_pas_conforme');
	}

	if ($email = _request('email')) {
		include_spip('inc/filtres');
		include_spip('inc/autoriser');
		// un redacteur qui modifie son email n'a pas le droit de le vider si il y en avait un
		if (
			!autoriser('modifier', 'auteur', $id_auteur, null, ['email' => '?'])
			and $GLOBALS['visiteur_session']['id_auteur'] == $id_auteur
			and !strlen(trim($email))
			and $email != ($email_ancien = sql_getfetsel('email', 'spip_auteurs', 'id_auteur=' . intval($id_auteur)))
		) {
			$erreurs['email'] = (($id_auteur == $GLOBALS['visiteur_session']['id_auteur']) ? _T('form_email_non_valide') : _T('form_prop_indiquer_email'));
		} else {
			if (!email_valide($email)) {
				$erreurs['email'] = (($id_auteur == $GLOBALS['visiteur_session']['id_auteur']) ? _T('form_email_non_valide') : _T('form_prop_indiquer_email'));
			}
		}
		# Ne pas autoriser d'avoir deux auteurs avec le même email
		# cette fonctionalité nécessite que la base soit clean à l'activation : pas de
		# doublon sur la requête select email,count(*) from spip_auteurs group by email ;
		if (defined('_INTERDIRE_AUTEUR_MEME_EMAIL')) {
			#Nouvel auteur
			if (intval($id_auteur) == 0) {
				#Un auteur existe deja avec cette adresse ?
				if (sql_countsel('spip_auteurs', 'email=' . sql_quote($email)) > 0) {
					$erreurs['email'] = _T('erreur_email_deja_existant');
				}
			} else {
				#Un auteur existe deja avec cette adresse ? et n'est pas le user courant.
				if (
					(sql_countsel(
						'spip_auteurs',
						'email=' . sql_quote($email)
					) > 0) and ($id_auteur != ($id_auteur_ancien = sql_getfetsel(
						'id_auteur',
						'spip_auteurs',
						'email=' . sql_quote($email)
					)))
				) {
					$erreurs['email'] = _T('erreur_email_deja_existant');
				}
			}
		}
	}

	// quand c'est un auteur existant on fait le reset password ici
	if (!(is_countable($erreurs) ? count($erreurs) : 0) and _request('reset_password') and intval($id_auteur)) {
		$erreurs = auteur_reset_password($id_auteur, $erreurs);
		return $erreurs;
	}

	// corriger un cas si frequent : www.example.org sans le http:// qui precede
	if ($url = _request('url_site') and !tester_url_absolue($url)) {
		if (strpos($url, ':') === false and strncasecmp($url, 'www.', 4) === 0) {
			$url = 'http://' . $url;
			set_request('url_site', $url);
		}
	}
	// traiter les liens implicites avant de tester l'url
	include_spip('inc/lien');
	if ($url = calculer_url(_request('url_site')) and !tester_url_absolue($url)) {
		$erreurs['url_site'] = _T('info_url_site_pas_conforme');
	}

	$erreurs['message_erreur'] = '';
	if (_request('login')) {
		// on n'est jamais cense poster le name 'login'
		$erreurs['login'] = _T('info_non_modifiable');
	}
	elseif (
		($login = _request('new_login')) and
		$login !== sql_getfetsel('login', 'spip_auteurs', 'id_auteur=' . intval($id_auteur))
	) {
		// on verifie la meme chose que dans auteurs_edit_config()
		if (
			! auth_autoriser_modifier_login($auth_methode)
			or !autoriser('modifier', 'auteur', intval($id_auteur), null, ['login' => true])
			// legacy : ne pas risquer d'autoriser la modif login si fonction d'autorisation pas mise a jour et ne teste que l'option email
			or !autoriser('modifier', 'auteur', intval($id_auteur), null, ['email' => true])
		) {
			$erreurs['login'] = _T('info_non_modifiable');
		}
	}

	if (empty($erreurs['login'])) {
		if ($err = auth_verifier_login($auth_methode, _request('new_login'), $id_auteur)) {
			$erreurs['login'] = $err;
			$erreurs['message_erreur'] .= $err;
		} else {
			// pass trop court ou confirmation non identique
			if ($p = _request('new_pass')) {
				if ($p != _request('new_pass2')) {
					$erreurs['new_pass'] = _T('info_passes_identiques');
					$erreurs['message_erreur'] .= _T('info_passes_identiques');
				} elseif ($err = auth_verifier_pass($auth_methode, _request('new_login'), $p, $id_auteur)) {
					$erreurs['new_pass'] = $err;
					$erreurs['message_erreur'] .= $err;
				}
			}
		}
	}

	if (!$erreurs['message_erreur']) {
		unset($erreurs['message_erreur']);
	}

	return $erreurs;
}


/**
 * Traitements du formulaire d'édition d'un auteur
 *
 * En plus de l'enregistrement normal des infos de l'auteur, la fonction
 * traite ces cas spécifiques :
 *
 * - Envoie lorsqu'un rédacteur n'a pas forcément l'autorisation changer
 *   seul son adresse email, un email à la nouvelle adresse indiquée
 *   pour vérifier l'email saisi, avec un lien dans le mai sur l'action
 *   'confirmer_email' qui acceptera alors le nouvel email.
 *
 * - Crée aussi une éventuelle laision indiquée dans $associer_objet avec
 *   cet auteur.
 *
 * @see formulaires_editer_objet_traiter()
 *
 * @param int|string $id_auteur
 *     Identifiant de l'auteur. 'new' pour une nouvel auteur.
 * @param string $retour
 *     URL de redirection après le traitement
 * @param string $associer_objet
 *     Éventuel 'objet|x' indiquant de lier le mot créé à cet objet,
 *     tel que 'article|3'
 * @param string $config_fonc
 *     Nom de la fonction ajoutant des configurations particulières au formulaire
 * @param array $row
 *     Valeurs de la ligne SQL de l'auteur, si connu
 * @param string $hidden
 *     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
 * @return array
 *     Retour des traitements
 **/
function formulaires_editer_auteur_traiter_dist(
	$id_auteur = 'new',
	$retour = '',
	$associer_objet = '',
	$config_fonc = 'auteurs_edit_config',
	$row = [],
	$hidden = ''
) {
	$id_objet = null;
	if (_request('saisie_webmestre') or _request('webmestre')) {
		set_request('webmestre', _request('webmestre') ?: 'non');
	}
	$retour = parametre_url($retour, 'email_confirm', '');

	if ($restreintes = _request('restreintes')) {
		foreach ($restreintes as $k => $v) {
			if (strpos($v, 'rubrique|') === 0) {
				$restreintes[$k] = substr($v, 9);
			}
		}
		set_request('restreintes', $restreintes);
	}

	set_request(
		'email',
		email_valide(_request('email'))
	); // eviter d'enregistrer les cas qui sont acceptés par email_valide dans le verifier :
	// "Marie@toto.com  " ou encore "Marie Toto <Marie@toto.com>"

	include_spip('inc/autoriser');
	if (!autoriser('modifier', 'auteur', $id_auteur, null, ['email' => '?'])) {
		$email_nouveau = _request('email');
		set_request('email'); // vider la saisie car l'auteur n'a pas le droit de modifier cet email
		// mais si c'est son propre profil on lui envoie un email à l'adresse qu'il a indique
		// pour qu'il confirme qu'il possede bien cette adresse
		// son clic sur l'url du message permettre de confirmer le changement
		// et de revenir sur son profil
		if (
			$GLOBALS['visiteur_session']['id_auteur'] == $id_auteur
			and $email_nouveau !=
				($email_ancien = sql_getfetsel('email', 'spip_auteurs', 'id_auteur=' . intval($id_auteur)))
		) {
			$envoyer_mail = charger_fonction('envoyer_mail', 'inc');
			$texte = _T(
				'form_auteur_mail_confirmation',
				[
					'url' => generer_action_auteur(
						'confirmer_email',
						$email_nouveau,
						parametre_url($retour, 'email_modif', 'ok')
					)
				]
			);
			$envoyer_mail($email_nouveau, _T('form_auteur_confirmation'), $texte);
			set_request('email_confirm', $email_nouveau);
			if ($email_ancien) {
				$envoyer_mail(
					$email_ancien,
					_T('form_auteur_confirmation'),
					_T('form_auteur_envoi_mail_confirmation', ['email' => $email_nouveau])
				);
			}
			$retour = parametre_url($retour, 'email_confirm', $email_nouveau);
		}
	}

	$res = formulaires_editer_objet_traiter('auteur', $id_auteur, 0, 0, $retour, $config_fonc, $row, $hidden);

	if (_request('reset_password') and !intval($id_auteur) and intval($res['id_auteur'])) {
		$erreurs = [];
		$erreurs = auteur_reset_password($res['id_auteur'], $erreurs);
		if (isset($erreurs['message_ok'])) {
			if (!isset($res['message_ok'])) { $res['message_ok'] = '';
			}
			$res['message_ok'] = trim($res['message_ok'] . ' ' . $erreurs['message_ok']);
		}
		if (isset($erreurs['message_erreur']) and $erreurs['message_erreur']) {
			if (!isset($res['message_erreur'])) { $res['message_erreur'] = '';
			}
			$res['message_erreur'] = trim($res['message_erreur'] . ' ' . $erreurs['message_erreur']);
		}
	}

	// Un lien auteur a prendre en compte ?
	if ($associer_objet and $id_auteur = $res['id_auteur']) {
		$objet = '';
		if (intval($associer_objet)) {
			$objet = 'article';
			$id_objet = intval($associer_objet);
		} elseif (preg_match(',^\w+\|[0-9]+$,', $associer_objet)) {
			[$objet, $id_objet] = explode('|', $associer_objet);
		}
		if ($objet and $id_objet and autoriser('modifier', $objet, $id_objet)) {
			include_spip('action/editer_auteur');
			auteur_associer($id_auteur, [$objet => $id_objet]);
			if (isset($res['redirect'])) {
				$res['redirect'] = parametre_url($res['redirect'], 'id_lien_ajoute', $id_auteur, '&');
			}
		}
	}

	return $res;
}


function auteur_reset_password($id_auteur, $erreurs = []) {
	$auteur = sql_fetsel('*', 'spip_auteurs', 'id_auteur=' . intval($id_auteur));
	$config = auteurs_edit_config($auteur);

	if ($config['edit_pass']) {
		if ($email = auteur_regenerer_identifiants($id_auteur)) {
			$erreurs['message_ok'] = _T('message_nouveaux_identifiants_ok', ['email' => $email]);
			$erreurs['message_erreur'] = '';
		} elseif ($email === false) {
			$erreurs['message_erreur'] = _T('message_nouveaux_identifiants_echec_envoi');
		} else {
			$erreurs['message_erreur'] = _T('message_nouveaux_identifiants_echec');
		}
	} else {
		$erreurs['message_erreur'] = _T('message_nouveaux_identifiants_echec');
	}

	return $erreurs;
}

/**
 * Renvoyer des identifiants
 * @param int $id_auteur
 * @param bool $notifier
 * @param array $contexte
 * @return string
 */
function auteur_regenerer_identifiants($id_auteur, $notifier = true, $contexte = []) {
	if ($id_auteur) {
		$set = [];
		include_spip('inc/access');
		$set['pass'] = creer_pass_aleatoire(max(_PASS_LONGUEUR_MINI, 16));

		include_spip('action/editer_auteur');
		auteur_modifier($id_auteur, $set);

		$row = sql_fetsel('*', 'spip_auteurs', 'id_auteur=' . intval($id_auteur));
		include_spip('inc/filtres');
		if (
			$notifier
			and $row['email']
			and email_valide($row['email'])
			and trouver_fond($fond = 'modeles/mail_nouveaux_identifiants')
		) {
			// envoyer l'email avec login/pass
			$c = [
				'id_auteur' => $id_auteur,
				'nom' => $row['nom'],
				'mode' => $row['statut'],
				'email' => $row['email'],
				'pass' => $set['pass'],
			];
			// on merge avec les champs fournit en appel, qui sont passes au modele de notification donc
			$contexte = array_merge($contexte, $c);
			// si pas de langue explicitement demandee, prendre celle de l'auteur si on la connait, ou a defaut celle du site
			// plutot que celle de l'admin qui vient de cliquer sur le bouton
			if (!isset($contexte['lang']) or !$contexte['lang']) {
				if (isset($row['lang']) and $row['lang']) {
					$contexte['lang'] = $row['lang'];
				}
				else {
					$contexte['lang'] = $GLOBALS['meta']['langue_site'];
				}
			}
			lang_select($contexte['lang']);
			$message = recuperer_fond($fond, $contexte);
			include_spip('inc/notifications');
			notifications_envoyer_mails($row['email'], $message);
			lang_select();

			return $row['email'];
		}

		return false;
	}

	return '';
}
