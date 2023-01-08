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
include_spip('base/abstract_sql');

function install_bases($adresse_db, $login_db, $pass_db, $server_db, $choix_db, $sel_db, $chmod_db) {

	// Prefix des tables :
	// S'il n'est pas defini par mes_options/inc/mutualiser, on va le creer
	// a partir de ce qui est envoye a l'installation
	if (!defined('_INSTALL_TABLE_PREFIX')) {
		$table_prefix = ($GLOBALS['table_prefix'] != 'spip')
			? $GLOBALS['table_prefix']
			: preparer_prefixe_tables(_request('tprefix'));
		// S'il est vide on remet spip
		if (!$table_prefix) {
			$table_prefix = 'spip';
		}
	} else {
		$table_prefix = _INSTALL_TABLE_PREFIX;
	}

	if (preg_match(',(.*):(.*),', $adresse_db, $r)) {
		[, $adresse_db, $port] = $r;
	} else {
		$port = '';
	}

	$GLOBALS['connexions'][$server_db]
		= spip_connect_db($adresse_db, $port, $login_db, $pass_db, '', $server_db);

	$GLOBALS['connexions'][$server_db][$GLOBALS['spip_sql_version']]
		= $GLOBALS['spip_' . $server_db . '_functions_' . $GLOBALS['spip_sql_version']];

	$fquery = sql_serveur('query', $server_db);
	if ($choix_db == 'new_spip') {
		$re = ',^[a-z_][a-z_0-9-]*$,i';
		if (preg_match($re, $sel_db)) {
			$ok = sql_create_base($sel_db, $server_db);
			if (!$ok) {
				$re = "Impossible de creer la base $re";
				spip_log($re);
				return '<p>' . _T('avis_connexion_erreur_creer_base') . "</p><!--\n$re\n-->";
			}
		} else {
			$re = "Le nom de la base doit correspondre a $re";
			spip_log($re);

			return '<p>' . _T('avis_connexion_erreur_nom_base') . "</p><!--\n$re\n-->";
		}
	}

	// on rejoue la connexion apres avoir teste si il faut lui indiquer
	// un sql_mode
	install_mode_appel($server_db, false);
	$GLOBALS['connexions'][$server_db]
		= spip_connect_db($adresse_db, $port, $login_db, $pass_db, $sel_db, $server_db);

	$GLOBALS['connexions'][$server_db][$GLOBALS['spip_sql_version']]
		= $GLOBALS['spip_' . $server_db . '_functions_' . $GLOBALS['spip_sql_version']];

	// Completer le tableau decrivant la connexion

	$GLOBALS['connexions'][$server_db]['prefixe'] = $table_prefix;
	$GLOBALS['connexions'][$server_db]['db'] = $sel_db;

	$old = sql_showbase($table_prefix . '_meta', $server_db);
	if ($old) {
		$old = sql_fetch($old, $server_db);
	}
	if (!$old) {
		// Si possible, demander au serveur d'envoyer les textes
		// dans le codage std de SPIP,
		$charset = sql_get_charset(_DEFAULT_CHARSET, $server_db);

		if ($charset) {
			sql_set_charset($charset['charset'], $server_db);
			$GLOBALS['meta']['charset_sql_base'] =
				$charset['charset'];
			$GLOBALS['meta']['charset_collation_sql_base'] =
				$charset['collation'];
			$GLOBALS['meta']['charset_sql_connexion'] =
				$charset['charset'];
			$charsetbase = $charset['charset'];
		} else {
			spip_log(_DEFAULT_CHARSET . ' inconnu du serveur SQL');
			$charsetbase = 'standard';
		}
		spip_log("Creation des tables. Codage $charsetbase");
		creer_base($server_db); // AT LAST
		// memoriser avec quel charset on l'a creee

		if ($charset) {
			$t = [
				'nom' => 'charset_sql_base',
				'valeur' => $charset['charset'],
				'impt' => 'non'
			];
			@sql_insertq('spip_meta', $t, [], $server_db);
			$t['nom'] = 'charset_collation_sql_base';
			$t['valeur'] = $charset['collation'];
			@sql_insertq('spip_meta', $t, [], $server_db);
			$t['nom'] = 'charset_sql_connexion';
			$t['valeur'] = $charset['charset'];
			@sql_insertq('spip_meta', $t, [], $server_db);
		}
		$t = [
			'nom' => 'version_installee',
			'valeur' => $GLOBALS['spip_version_base'],
			'impt' => 'non'
		];
		@sql_insertq('spip_meta', $t, [], $server_db);
		$t['nom'] = 'nouvelle_install';
		$t['valeur'] = 1;
		@sql_insertq('spip_meta', $t, [], $server_db);
		// positionner la langue par defaut du site si un cookie de lang a ete mis
		if (isset($_COOKIE['spip_lang_ecrire'])) {
			@sql_insertq(
				'spip_meta',
				['nom' => 'langue_site', 'valeur' => $_COOKIE['spip_lang_ecrire']],
				[],
				$server_db
			);
		}
	} else {
		// pour recreer les tables disparues au besoin
		spip_log('Table des Meta deja la. Verification des autres.');
		creer_base($server_db);
		$fupdateq = sql_serveur('updateq', $server_db);

		$r = $fquery("SELECT valeur FROM spip_meta WHERE nom='version_installee'", $server_db);

		if ($r) {
			$r = sql_fetch($r, $server_db);
		}
		$version_installee = !$r ? 0 : (double)$r['valeur'];
		if (!$version_installee or ($GLOBALS['spip_version_base'] < $version_installee)) {
			$fupdateq(
				'spip_meta',
				['valeur' => $GLOBALS['spip_version_base'], 'impt' => 'non'],
				"nom='version_installee'",
				'',
				$server_db
			);
			spip_log('nouvelle version installee: ' . $GLOBALS['spip_version_base']);
		}
		// eliminer la derniere operation d'admin mal terminee
		// notamment la mise a jour
		@$fquery("DELETE FROM spip_meta WHERE nom='import_all' OR  nom='admin'", $server_db);
	}

	// recuperer le charset de la connexion dans les meta
	$charset = '';
	$r = $fquery("SELECT valeur FROM spip_meta WHERE nom='charset_sql_connexion'", $server_db);
	if ($r) {
		$r = sql_fetch($r, $server_db);
	}
	if ($r) {
		$charset = $r['valeur'];
	}

	$ligne_rappel = install_mode_appel($server_db);

	$result_ok = @$fquery('SELECT COUNT(*) FROM spip_meta', $server_db);
	if (!$result_ok) {
		return "<!--\nvielle = $old rappel= $ligne_rappel\n-->";
	}

	if ($chmod_db) {
		install_fichier_connexion(
			_FILE_CHMOD_TMP,
			"if (!defined('_SPIP_CHMOD')) define('_SPIP_CHMOD', " . sprintf('0%3o', $chmod_db) . ");\n"
		);
	}

	// si ce fichier existe a cette etape c'est qu'il provient
	// d'une installation qui ne l'a pas cree correctement.
	// Le supprimer pour que _FILE_CONNECT_TMP prime.

	if (_FILE_CONNECT and file_exists(_FILE_CONNECT)) {
		spip_unlink(_FILE_CONNECT);
	}

	install_fichier_connexion(
		_FILE_CONNECT_TMP,
		$ligne_rappel
		. install_connexion(
			$adresse_db,
			$port,
			$login_db,
			$pass_db,
			$sel_db,
			$server_db,
			$table_prefix,
			'',
			$charset
		)
	);

	return '';
}

/**
 * Préparer le préfixe des tables
 *
 * Contrairement a ce qui est dit dans le message (trop strict mais c'est
 * pour notre bien), on tolère les chiffres en plus des minuscules.
 * On corrige aussi le préfixe afin qu'il ne commence pas par un chiffre
 * cf https://core.spip.net/issues/3626
 *
 * @param string $prefixe Le préfixe demandé
 * @return string Le préfixe corrigé
 */
function preparer_prefixe_tables($prefixe) {
	return trim(preg_replace(',^[0-9]+,', '', preg_replace(',[^a-z0-9],', '', strtolower($prefixe))));
}

function install_propose_ldap() {
	return generer_form_ecrire('install', (
	fieldset(
		_T('info_authentification_externe'),
		[
			'etape' => [
				'label' => _T('texte_annuaire_ldap_1'),
				'valeur' => 'ldap1',
				'hidden' => true
			]
		],
		bouton_suivant(_T('bouton_acces_ldap'))
	)));
}


function install_premier_auteur($email, $login, $nom, $pass, $hidden, $auteur_obligatoire) {
	return info_progression_etape(3, 'etape_', 'install/') .
	info_etape(
		_T('info_informations_personnelles'),
		'<b>' . _T('texte_informations_personnelles_1') . '</b>' .
		aider('install5', true) .
		'<p>' .
		($auteur_obligatoire ?
			''
			:
			_T('texte_informations_personnelles_2') . ' ' . _T('info_laisser_champs_vides')
		)
	)
	. generer_form_ecrire('install', (
		"\n<input type='hidden' name='etape' value='3b' />"
		. $hidden
		. fieldset(
			_T('info_identification_publique'),
			[
				'nom' => [
					'label' => '<b>' . _T('entree_signature') . "</b><br />\n" . _T('entree_nom_pseudo_1') . "\n",
					'valeur' => $nom,
					'required' => $auteur_obligatoire,
				],
				'email' => [
					'label' => '<b>' . _T('entree_adresse_email') . "</b>\n",
					'valeur' => $email,
				]
			]
		)

		. fieldset(
			_T('entree_identifiants_connexion'),
			[
				'login' => [
					'label' => '<b>' . _T('entree_login') . "</b><br />\n" . _T(
						'info_login_trop_court_car_pluriel',
						['nb' => _LOGIN_TROP_COURT]
					) . "\n",
					'valeur' => $login,
					'required' => $auteur_obligatoire,
				],
				'pass' => [
					'label' => '<b>' . _T('entree_mot_passe') . "</b><br />\n" . _T(
						'info_passe_trop_court_car_pluriel',
						['nb' => _PASS_LONGUEUR_MINI]
					) . "\n",
					'valeur' => $pass,
					'required' => $auteur_obligatoire,
				],
				'pass_verif' => [
					'label' => '<b>' . _T('info_confirmer_passe') . "</b><br />\n",
					'valeur' => $pass,
					'required' => $auteur_obligatoire,
				]
			]
		)
		. bouton_suivant()));
}

function install_etape_3_dist() {
	$ldap_present = _request('ldap_present');

	if (!$ldap_present) {
		$adresse_db = defined('_INSTALL_HOST_DB')
			? _INSTALL_HOST_DB
			: _request('adresse_db');

		$login_db = defined('_INSTALL_USER_DB')
			? _INSTALL_USER_DB
			: _request('login_db');

		$pass_db = defined('_INSTALL_PASS_DB')
			? _INSTALL_PASS_DB
			: _request('pass_db');

		$server_db = defined('_INSTALL_SERVER_DB')
			? _INSTALL_SERVER_DB
			: _request('server_db');

		$chmod_db = defined('_SPIP_CHMOD')
			? _SPIP_CHMOD
			: _request('chmod');

		$choix_db = defined('_INSTALL_NAME_DB')
			? _INSTALL_NAME_DB
			: _request('choix_db');

		$sel_db = ($choix_db == 'new_spip')
			? _request('table_new') : $choix_db;

		$res = install_bases($adresse_db, $login_db, $pass_db, $server_db, $choix_db, $sel_db, $chmod_db);

		if ($res) {
			$res = info_progression_etape(2, 'etape_', 'install/', true)
				. "<div class='error'><h3>" . _T('avis_operation_echec') . '</h3>'
				. $res
				. '<p>' . _T('texte_operation_echec') . '</p>'
				. '</div>';
		}
	} else {
		$res = '';
		[$adresse_db, $login_db, $pass_db, $sel_db, $server_db] = analyse_fichier_connection(_FILE_CONNECT_TMP);
		$GLOBALS['connexions'][$server_db] = spip_connect_db($adresse_db, $sel_db, $login_db, $pass_db, $sel_db, $server_db);
	}

	if (!$res) {
		if (file_exists(_FILE_CONNECT_TMP)) {
			include(_FILE_CONNECT_TMP);
		} else {
			redirige_url_ecrire('install');
		}

		if (file_exists(_FILE_CHMOD_TMP)) {
			include(_FILE_CHMOD_TMP);
		} else {
			redirige_url_ecrire('install');
		}

		$hidden = predef_ou_cache($adresse_db, $login_db, $pass_db, $server_db)
			. (defined('_INSTALL_NAME_DB') ? ''
				: "\n<input type='hidden' name='sel_db' value=\"" . spip_htmlspecialchars($sel_db) . '" />');

		$auteur_obligatoire = ($ldap_present ? 0 : !sql_countsel('spip_auteurs', '', '', '', $server_db));

		$res = "<div class='success'><b>"
			. _T('info_base_installee')
			. '</b></div>'
			. install_premier_auteur(
				_request('email'),
				_request('login'),
				_request('nom'),
				_request('pass'),
				$hidden,
				$auteur_obligatoire
			)
			. (($ldap_present or !function_exists('ldap_connect'))
				? '' : install_propose_ldap());
	}

	echo install_debut_html();
	echo $res;
	echo install_fin_html();
}
