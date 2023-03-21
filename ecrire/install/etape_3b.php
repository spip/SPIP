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

include_spip('inc/headers');

function install_etape_3b_dist() {
	$auth_spip = null;
	$session = null;
	$row = null;
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
			: ((strlen((string) $pass) < _PASS_LONGUEUR_MINI) ?
				_T('info_passe_trop_court_car_pluriel', ['nb' => _PASS_LONGUEUR_MINI])
				: ((strlen((string) $login) < _LOGIN_TROP_COURT) ?
					_T('info_login_trop_court')
					: ''));
		include_spip('inc/filtres');
		if (!$echec && $email && !email_valide($email)) {
			$echec = _T('form_email_non_valide');
		}
		if ($echec) {
			echouer_etape_3b($echec);
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

		include_spip('auth/spip');
		// prelablement, creer le champ webmestre si il n'existe pas (install neuve
		// sur une vieille base
		$t = sql_showtable('spip_auteurs', true);
		if (!isset($t['field']['webmestre'])) {
			@sql_alter("TABLE spip_auteurs ADD webmestre varchar(3)  DEFAULT 'non' NOT NULL");
		}

		// il faut avoir une cle des auth valide pour creer un nouvel auteur webmestre
		$cles = \Spip\Chiffrer\SpipCles::instance();
		$secret = $cles->getSecretAuth();

		$id_auteur = sql_getfetsel('id_auteur', 'spip_auteurs', 'login=' . sql_quote($login));
		if ($id_auteur !== null) {
			// c'est un auteur connu : si on a pas de secret il faut absolument qu'il se reconnecte avec le meme mot de passe
			// pour restaurer la copie des cles
			if (!$secret && !auth_spip_initialiser_secret()) {
				$row = sql_fetsel('backup_cles, pass', 'spip_auteurs', 'id_auteur=' . (int) $id_auteur);
				if (empty($row['backup_cles']) || !$cles->restore($row['backup_cles'], $pass, $row['pass'], $id_auteur)) {
					$echec = _T('avis_connexion_erreur_fichier_cle_manquant_1');
					echouer_etape_3b($echec);
				}
				spip_log("Les cles secretes ont ete restaurées avec le backup du webmestre #$id_auteur", 'auth' . _LOG_INFO_IMPORTANTE);
				$cles->save();
			}

			sql_updateq('spip_auteurs', [
				'nom' => $nom,
				'email' => $email,
				'login' => $login,
				'statut' => '0minirezo'
			], 'id_auteur=' . (int) $id_auteur);
			// le passer webmestre separement du reste, au cas ou l'alter n'aurait pas fonctionne
			@sql_updateq('spip_auteurs', ['webmestre' => 'oui'], "id_auteur=$id_auteur");
			if (!auth_spip_modifier_pass($login, $pass, $id_auteur)) {
				$echec = _T('avis_erreur_creation_compte');
				echouer_etape_3b($echec);
			}
		} else {
			// Si on a pas de cle et qu'on ne sait pas la creer, on ne peut pas creer de nouveau compte :
			// il faut qu'un webmestre avec un backup fasse l'install
			if (!$secret && !auth_spip_initialiser_secret()) {
				$echec = _T('avis_connexion_erreur_fichier_cle_manquant_2');
				echouer_etape_3b($echec);
			}

			$id_auteur = sql_insertq('spip_auteurs', [
				'nom' => $nom,
				'email' => $email,
				'login' => $login,
				'statut' => '0minirezo'
			]);
			// le passer webmestre separrement du reste, au cas ou l'alter n'aurait pas fonctionne
			@sql_updateq('spip_auteurs', ['webmestre' => 'oui'], "id_auteur=$id_auteur");
			if (!auth_spip_modifier_pass($login, $pass, $id_auteur)) {
				$echec = _T('avis_erreur_creation_compte');
				echouer_etape_3b($echec);
			}
		}

		// inserer email comme email webmaster principal
		// (sauf s'il est vide: cas de la re-installation)
		if ($email) {
			ecrire_meta('email_webmaster', $email);
		}

		// Connecter directement celui qui vient de (re)donner son login
		// mais sans cookie d'admin ni connexion longue
		include_spip('inc/auth');
		$auteur = auth_identifier_login($login, $pass);
		if (!$auteur || !auth_loger($auteur)) {
			spip_log("login automatique impossible $auth_spip $session" . (is_countable($row) ? count($row) : 0));
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

function echouer_etape_3b($echec): never {
	echo minipres(
		'AUTO',
		info_progression_etape(3, 'etape_', 'install/', true) .
		"<div class='error'><h3>$echec</h3>\n" .
		'<p>' . _T('avis_connexion_echec_2') . '</p>' .
		'</div>'
	);
	exit;
}
