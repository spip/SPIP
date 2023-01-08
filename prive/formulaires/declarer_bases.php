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

include_spip('inc/install');

function formulaires_declarer_bases_charger_dist() {
	[$adresse_db, $login_db, $pass_db, $sel, $server_db] = analyse_fichier_connection(_FILE_CONNECT);

	$deja = bases_referencees(_FILE_CONNECT);
	// proposer un nom de connect si pas encore saisi
	$nom_connect = '';
	if (defined('_DECLARER_CHOIX_DB')) {
		$nom_connect = _DECLARER_CHOIX_DB;
		$n = '';
		while (in_array($nom_connect . $n, $deja)) {
			$n = ($n ? $n + 1 : 1);
		}
		$nom_connect = $nom_connect . $n;
	}

	$valeurs = [
		'_etapes' => 3,
		'_bases_deja' => $deja,
		'_bases_prop' => defined('_DECLARER_SERVEUR_DB') ? liste_bases(_DECLARER_SERVEUR_DB) : '',
		'_tables' => (defined('_DECLARER_SERVEUR_DB') and defined('_DECLARER_CHOIX_DB')) ?
			$tables = sql_alltable('%', _DECLARER_SERVEUR_DB)
			:
			[],
		'main_db' => '',
		'_serveurs' => liste_serveurs(),
		'sql_serveur_db' => 'sqlite3', // valeur par defaut
		'adresse_db' => $adresse_db,
		'login_db' => '',
		'pass_db' => '',
		'choix_db' => '',
		'table_new' => '',
		'nom_connect' => $nom_connect,
	];

	return $valeurs;
}

function liste_serveurs() {
	$options = [];
	$dir = _DIR_RESTREINT . 'req/';
	$d = opendir($dir);
	if (!$d) {
		return [];
	}
	while ($f = readdir($d)) {
		if (
			(preg_match('/^(.*)[.]php$/', $f, $s))
			and is_readable($f = $dir . $f)
		) {
			require_once($f);
			$s = $s[1];
			$v = 'spip_versions_' . $s;
			if (function_exists($v) and $v()) {
				$options[$s] = "install_select_type_$s";
			} else {
				spip_log("$s: portage indisponible");
			}
		}
	}
	ksort($options);

	return $options;
}

function liste_bases($server_db) {
	if (
		is_null($server_db)
		or !$result = sql_listdbs($server_db)
	) {
		return '';
	}

	$noms = [];

	// si sqlite : result est deja un tableau
	if (is_array($result)) {
		$noms = $result;
	} else {
		while ($row = sql_fetch($result, $server_db)) {
			$noms[] = reset($row);
		}
	}

	return $noms;
}

function formulaires_declarer_bases_verifier_1_dist() {
	$erreurs = [];
	[$def_adresse_db, $def_login_db, $def_pass_db, $sel_db, $def_serveur_db] = analyse_fichier_connection(_FILE_CONNECT);

	if (!$adresse_db = _request('adresse_db')) {
		if (defined('_INSTALL_HOST_DB')) {
			$adresse_db = _INSTALL_HOST_DB;
		} else {
			$adresse_db = $def_adresse_db;
		}
	}
	if (!$serveur_db = _request('sql_serveur_db')) {
		if (defined('_INSTALL_SERVER_DB')) {
			$serveur_db = _INSTALL_SERVER_DB;
		} else {
			$serveur_db = $def_serveur_db;
		}
	}

	$login_db = $pass_db = '';
	if (!preg_match(',^sqlite,i', $serveur_db)) {
		if (!$login_db = _request('login_db')) {
			if (defined('_INSTALL_USER_DB')) {
				$login_db = _INSTALL_USER_DB;
			} else {
				$login_db = $def_login_db;
			}
		}
		if (!$pass_db = _request('pass_db')) {
			if (defined('_INSTALL_PASS_DB')) {
				$pass_db = _INSTALL_PASS_DB;
			} else {
				$pass_db = $def_pass_db;
			}
		}
	}

	$link = spip_connect_db($adresse_db, '', $login_db, $pass_db, '@test@', $serveur_db);
	if ($link) {
		$GLOBALS['connexions'][$serveur_db][$GLOBALS['spip_sql_version']] = $GLOBALS['spip_' . $serveur_db . '_functions_' . $GLOBALS['spip_sql_version']];
		$GLOBALS['connexions'][$serveur_db] = $link;
		define('_DECLARER_SERVEUR_DB', $serveur_db);
		define('_DECLARER_ADRESSE_DB', $adresse_db);
		define('_DECLARER_LOGIN_DB', $login_db);
		define('_DECLARER_PASS_DB', $pass_db);
		// si on est sur le meme serveur que connect.php
		// indiquer quelle est la db utilisee pour l'exclure des choix possibles
		if ($serveur_db == $def_serveur_db and $adresse_db == $def_adresse_db) {
			set_request('main_db', $sel_db);
		} else {
			set_request('main_db', '');
		}
	} else {
		$erreurs['message_erreur'] = _T('avis_connexion_echec_1');
	}

	return $erreurs;
}

function formulaires_declarer_bases_verifier_2_dist() {
	$erreurs = [];
	$choix_db = _request('choix_db');
	if ($choix_db == '-1') {
		$choix_db = _request('table_new');
	}
	if (!$choix_db) {
		$erreurs['choix_db'] = _T('info_obligatoire');
	} else {
		define('_ECRIRE_INSTALL', 1); // hackons sqlite
		if (!sql_selectdb($choix_db, _DECLARER_SERVEUR_DB)) {
			$erreurs['choix_db'] = _T('avis_base_inaccessible', ['base' => $choix_db]);
		} else {
			define('_DECLARER_CHOIX_DB', $choix_db);
		}
	}

	return $erreurs;
}

function formulaires_declarer_bases_verifier_3_dist() {
	$erreurs = [];
	$nom_connect = _request('nom_connect');
	if (!$nom_connect) {
		$erreurs['nom_connect'] = _T('info_obligatoire');
	} else {
		// securite : le nom doit etre un mot sans caracteres speciaux
		$f = preg_replace(',[^\w],', '', $nom_connect);
		if ($f !== $nom_connect) {
			$erreurs['nom_connect'] = _T('erreur_nom_connect_incorrect');
		} elseif (file_exists(_DIR_CONNECT . $nom_connect . '.php')) {
			$erreurs['nom_connect'] = _T('erreur_connect_deja_existant');
		} else {
			define('_DECLARER_NOM_CONNECT', $nom_connect);
		}
	}

	return $erreurs;
}

function formulaires_declarer_bases_traiter_dist() {

	$adresse_db = _DECLARER_ADRESSE_DB;
	if (preg_match(',(.*):(.*),', $adresse_db, $r)) {
		[, $adresse_db, $port] = $r;
	} else {
		$port = '';
	}

	$server_db = addcslashes(_DECLARER_SERVEUR_DB, "'\\");

	$conn = install_mode_appel($server_db)
		. install_connexion(
			$adresse_db,
			$port,
			_DECLARER_LOGIN_DB,
			_DECLARER_PASS_DB,
			_DECLARER_CHOIX_DB,
			_DECLARER_SERVEUR_DB,
			'',
			'',
			''
		);

	install_fichier_connexion(_DIR_CONNECT . _DECLARER_NOM_CONNECT . '.php', $conn);

	return [
		'message_ok' => _T('install_connect_ok', ['connect' => '<strong>' . _DECLARER_NOM_CONNECT . '</strong>'])
	];
}
