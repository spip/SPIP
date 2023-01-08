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


include_spip('req/sqlite_generique');

$GLOBALS['spip_sqlite3_functions_1'] = _sqlite_ref_fonctions();


function req_sqlite3_dist($addr, $port, $login, $pass, $db = '', $prefixe = '') {
	return req_sqlite_dist($addr, $port, $login, $pass, $db, $prefixe, $sqlite_version = 3);
}


function spip_sqlite3_constantes() {
	if (!defined('SPIP_SQLITE3_ASSOC')) {
		define('SPIP_SQLITE3_ASSOC', PDO::FETCH_ASSOC);
		define('SPIP_SQLITE3_NUM', PDO::FETCH_NUM);
		define('SPIP_SQLITE3_BOTH', PDO::FETCH_BOTH);
	}
}

function spip_versions_sqlite3() {
	return _sqlite_charger_version(3) ? 3 : false;
}
