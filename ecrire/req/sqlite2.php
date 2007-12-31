<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('req/sqlite_generique');

$GLOBALS['spip_sqlite2_functions_1'] = _sqlite_ref_fonctions();


function req_sqlite2_dist($addr, $port, $login, $pass, $db='', $prefixe='', $ldap='') {	
	return req_sqlite_dist($addr, $port, $login, $pass, $db, $prefixe, $ldap, $sqlite_version=2);
}

function spip_sqlite2_constantes(){
	define('SPIP_SQLITE2_ASSOC', SQLITE_ASSOC);
	define('SPIP_SQLITE2_NUM', SQLITE_NUM);
	define('SPIP_SQLITE2_BOTH', SQLITE_BOTH);
}

?>
