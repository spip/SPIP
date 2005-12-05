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
function magic_unquote(&$t) {
	if (is_array($t)) {
		foreach ($t as $key => $val) {
			if (!is_array($val)
			OR !($t['spip_recursions']++)) # interdire les recursions
				magic_unquote($t[$key], $key);
		}
	} else
		$t = stripslashes($t);
}

magic_unquote($_GET);
magic_unquote($_POST);
magic_unquote($_COOKIE);
#	if (@ini_get('register_globals')) // pas fiable
	magic_unquote($GLOBALS);

# et a la fin supprimer la variable anti-recursion devenue inutile (et meme nuisible, notamment si on teste $_POST)
unset($_GET['spip_recursions']);
unset($_POST['spip_recursions']);
unset($_COOKIE['spip_recursions']);
unset($_GLOBALS['spip_recursions']);

?>
