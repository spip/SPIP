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


$fond = _request('exec');
$GLOBALS['delais'] = 0;// pas de cache !
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

function shutdown_error(){

	// si on arrive ici avec un tampon non ferme : erreur fatale
/*	if (ob_get_level()){
		// envoyer tous les tampons
		while (ob_get_level())
			ob_end_flush();

		var_dump(error_get_last());
		#echo "<hr />"."Erreur fatale (memoire ?)<hr />";
		@flush();
	}*/
}
register_shutdown_function('shutdown_error');


// on retient l'envoi de html pour pouvoir tout jeter et generer une 403
// si on tombe sur un filtre sinon_interdire_acces
// il faudrait etre capable de flusher cela des que le contenu principal est genere
// car c'est lui qui peut faire des appels a ce filtre
ob_start();
# comme on est dans un exec, l'auth a deja ete testee
# on peut appeler directement public.php
include "public.php";
// flushons si cela ne l'a pas encore ete
ob_end_flush();
/**
 * Un exec generique qui branche sur un squelette Z pour ecrire
 * La fonction ne fait rien, c'est l'inclusion du fichier qui declenche le traitement
 *
 */
function exec_fond_dist(){

}

?>
