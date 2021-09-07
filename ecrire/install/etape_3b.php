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

include_spip('inc/headers');

function install_etape_3b_dist() {
	$login = _request('login');
	$email = _request('email');
	$nom = _request('nom');
	$pass = _request('pass');
	$pass_verif = _request('pass_verif');

	$server_db = defined('_INSTALL_SERVER_DB')
		? _INSTALL_SERVER_DB
		: _request('server_db');

	if (!defined('_PASS_LONGUEUR_MINI')) {
		define('_PASS_LONGUEUR_MINI', 6);
	}
	if (!defined('_LOGIN_TROP_COURT')) {
		define('_LOGIN_TROP_COURT', 4);
	}
	if ($login) {
		$echec = ($pass != $pass_verif) ?
			_T('info_passes_identiques')
			: ((strlen($pass) < _PASS_LONGUEUR_MINI) ?
				_T('info_passe_trop_court_car_pluriel', ['nb' => _PASS_LONGUEUR_MINI])
				: ((strlen($login) < _LOGIN_TROP_COURT) ?
					_T('info_login_trop_court')
					: ''));
		include_spip('inc/filtres');
		if (!$echec and $email and !email_valide($email)) {
			$echec = _T('form_email_non_valide');
		}
		if ($echec) {
			echo minipres(
				'AUTO',
				info_progression_etape(3, 'etape_', 'install/', true) .
				"<div class='error'><h3>$echec</h3>\n" .
				'<p>' . _T('avis_connexion_echec_2') . '</p>' .
				'</div>'
			);
			exit;
		}
	}

	if (@file_exists(_FILE_CHMOD_TMP)) {
		include(_FILE_CHMOD_TMP);
	} else {
		redirige_url_ecrire('install');
	}

	if (!@file_exists(_FILE_CONNECT_TMP)) {
		redirige_url_ecrire('install');
	}

	# maintenant on connait le vrai charset du site s'il est deja configure
	# sinon par defaut lire_meta reglera _DEFAULT_CHARSET
	# (les donnees arrivent de toute facon postees en _DEFAULT_CHARSET)

	lire_metas();
	if ($login) {
		include_spip('inc/charsets');

		$nom = (importer_charset($nom, _DEFAULT_CHARSET));
		$login = (importer_charset($login, _DEFAULT_CHARSET));
		$email = (importer_charset($email, _DEFAULT_CHARSET));
		# pour le passwd, bizarrement il faut le convertir comme s'il avait
		# ete tape en iso-8859-1 ; car c'est en fait ce que voit md5.js
		$pass = unicode2charset(utf_8_to_unicode($pass), 'iso-8859-1');
		include_spip('auth/sha256.inc');
		include_spip('inc/acces');
		if ($generer_htpass = charger_fonction('generer_htpass', 'inc', true)) {
			$htpass = $generer_htpass($new_pass);
		}
		$alea_actuel = creer_uniqid();
		$alea_futur = creer_uniqid();
		$shapass = spip_sha256($alea_actuel . $pass);
		// prelablement, creer le champ webmestre si il n'existe pas (install neuve
		// sur une vieille base
		$t = sql_showtable('spip_auteurs', true);
		if (!isset($t['field']['webmestre'])) {
			@sql_alter("TABLE spip_auteurs ADD webmestre varchar(3)  DEFAULT 'non' NOT NULL");
		}

		$id_auteur = sql_getfetsel('id_auteur', 'spip_auteurs', 'login=' . sql_quote($login));
		if ($id_auteur !== null) {
			sql_updateq('spip_auteurs', [
				'nom' => $nom,
				'email' => $email,
				'login' => $login,
				'pass' => $shapass,
				'alea_actuel' => $alea_actuel,
				'alea_futur' => $alea_futur,
				'htpass' => $htpass,
				'statut' => '0minirezo'
			], "id_auteur=$id_auteur");
		} else {
			$id_auteur = sql_insertq('spip_auteurs', [
				'nom' => $nom,
				'email' => $email,
				'login' => $login,
				'pass' => $shapass,
				'htpass' => $htpass,
				'alea_actuel' => $alea_actuel,
				'alea_futur' => $alea_futur,
				'statut' => '0minirezo'
			]);
		}
		// le passer webmestre separrement du reste, au cas ou l'alter n'aurait pas fonctionne
		@sql_updateq('spip_auteurs', ['webmestre' => 'oui'], "id_auteur=$id_auteur");

		// inserer email comme email webmaster principal
		// (sauf s'il est vide: cas de la re-installation)
		if ($email) {
			ecrire_meta('email_webmaster', $email);
		}

		// Connecter directement celui qui vient de (re)donner son login
		// mais sans cookie d'admin ni connexion longue
		include_spip('inc/auth');
		if (
			!$auteur = auth_identifier_login($login, $pass)
			or !auth_loger($auteur, true)
		) {
			spip_log("login automatique impossible $auth_spip $session" . count($row));
		}
	}

	// installer les metas
	$config = charger_fonction('config', 'inc');
	$config();

	// activer les plugins
	// leur installation ne peut pas se faire sur le meme hit, il faudra donc
	// poursuivre au hit suivant
	include_spip('inc/plugin');
	actualise_plugins_actifs();


	include_spip('inc/distant');
	redirige_par_entete(parametre_url(self(), 'etape', '4', '&'));
}
