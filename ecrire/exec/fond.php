<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;


$fond = _request('exec');

// Securite
if (strstr($fond, '/')) {
	if (!include_spip('inc/autoriser')
		OR !autoriser('webmestre')) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}
}
else
	$fond = "prive/squelettes/$fond";

// quelques inclusions et ini prealables
include_spip('inc/commencer_page');

include "prive.php";

/**
 * Un exec generique qui branche sur un squelette Z pour ecrire
 * La fonction ne fait rien, c'est l'inclusion du fichier qui declenche le traitement
 *
 */
function exec_fond_dist(){

}

?>
