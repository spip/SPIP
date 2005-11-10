<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// Magic quotes : on n'en veut pas sur la base
// et on nettoie les GET/POST/COOKIE le cas echeant
function magic_unquote($_table) {
	// Certains hebergeurs n'activent pas $GLOBALS['GLOBALS']
	if ($_table == 'GLOBALS'
	AND !isset($GLOBALS['GLOBALS']))
		$GLOBALS['GLOBALS'] = &$GLOBALS;

	if (is_array($GLOBALS[$_table])) {
		foreach ($GLOBALS[$_table] as $key => $val) {
			if (is_string($val))
				$GLOBALS[$_table][$key] = stripslashes($val);
		}
	}
}

magic_unquote('_GET');
magic_unquote('_POST');
magic_unquote('_COOKIE');
#	if (@ini_get('register_globals')) // pas fiable
	magic_unquote('GLOBALS');


?>
