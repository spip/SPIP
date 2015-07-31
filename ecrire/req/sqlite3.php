<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;



include_spip('req/sqlite_generique');

$GLOBALS['spip_sqlite3_functions_1'] = _sqlite_ref_fonctions();


// http://doc.spip.org/@req_sqlite3_dist
function req_sqlite3_dist($addr, $port, $login, $pass, $db='', $prefixe='') {
	return req_sqlite_dist($addr, $port, $login, $pass, $db, $prefixe, $sqlite_version=3);
}


// http://doc.spip.org/@spip_sqlite3_constantes
function spip_sqlite3_constantes(){
	if (!defined('SPIP_SQLITE3_ASSOC')) {
		define('SPIP_SQLITE3_ASSOC', PDO::FETCH_ASSOC);
		define('SPIP_SQLITE3_NUM',   PDO::FETCH_NUM);
		define('SPIP_SQLITE3_BOTH',  PDO::FETCH_BOTH);
	}
}

function spip_versions_sqlite3()
{
	return _sqlite_charger_version(3) ? 3 : false;
}

?>
