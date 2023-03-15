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

// chargement des valeurs par defaut des champs du formulaire
function formulaires_oubli_charger_dist() {
	return ['oubli' => '', 'nobot' => ''];
}

// https://code.spip.net/@message_oubli
function message_oubli($email, $param) {
	$r = formulaires_oubli_mail($email);

	if (is_array($r) && $r[1] && $r[1]['statut'] !== '5poubelle' && $r[1]['pass'] !== '') {
		include_spip('inc/texte'); # pour corriger_typo

		include_spip('action/inscrire_auteur');
		$cookie = auteur_attribuer_jeton($r[1]['id_auteur']);

		// l'url_reset doit etre une URL de confiance, on force donc un url_absolue sur adresse_site
		include_spip('inc/filtres');
		$msg = recuperer_fond(
			'modeles/mail_oubli',
			[
				'url_reset' => url_absolue(
					generer_url_public('spip_pass', "$param=$cookie"),
					$GLOBALS['meta']['adresse_site'] . '/'
				)
			]
		);
		include_spip('inc/notifications');
		notifications_envoyer_mails($email, $msg);
	}

	return _T('pass_recevoir_mail');
}

// la saisie a ete validee, on peut agir
function formulaires_oubli_traiter_dist() {

	$message = message_oubli(_request('oubli'), 'p');

	return ['message_ok' => $message];
}


// fonction qu'on peut redefinir pour filtrer les adresses mail
// https://code.spip.net/@test_oubli
function test_oubli_dist($email) {
	include_spip('inc/filtres'); # pour email_valide()
	if (!email_valide($email)) {
		return _T('pass_erreur_non_valide', ['email_oubli' => spip_htmlspecialchars($email)]);
	}

	return ['mail' => $email];
}

function formulaires_oubli_verifier_dist() {
	$erreurs = [];

	$email = (string) _request('oubli');

	$r = formulaires_oubli_mail($email);

	if (!is_array($r)) {
		$erreurs['oubli'] = $r;
	} else {
		if (!$r[1]) {
			spip_log("demande de reinitialisation de mot de passe pour $email non enregistre sur le site", 'oubli');
		} elseif ($r[1]['statut'] == '5poubelle' || $r[1]['pass'] == '') {
			spip_log("demande de reinitialisation de mot de passe pour $email sans acces (poubelle ou pass vide)", 'oubli');
		}
	}

	if (_request('nobot')) {
		$erreurs['message_erreur'] = _T('pass_rien_a_faire_ici');
	}

	return $erreurs;
}

function formulaires_oubli_mail($email) {
	$f = function_exists('test_oubli') ? 'test_oubli' : 'test_oubli_dist';
	$declaration = $f($email);

	if (!is_array($declaration)) {
		return $declaration;
	} else {
		include_spip('base/abstract_sql');

		return [
			$declaration,
			sql_fetsel('id_auteur,statut,pass', 'spip_auteurs', "login<>'' AND email =" . sql_quote($declaration['mail']))
		];
	}
}
