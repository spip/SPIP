<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion d'affichage des pages privées en squelette
 *
 * Chargé depuis ecrire/index.php lorsqu'une page demandée est présente
 * en tant que squelettes dans `prive/squelettes/contenu` ou que le
 * squelette peut être échaffaudé
 *
 * @package SPIP\Core\Exec
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


$fond = _request('exec');
$GLOBALS['delais'] = 0;// pas de cache !
// Securite
if (strstr($fond, '/')) {
	if (!include_spip('inc/autoriser')
		or !autoriser('webmestre')
	) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}
} else {
	$fond = "prive/squelettes/$fond";
}

// quelques inclusions et ini prealables
include_spip('inc/commencer_page');

/**
 * Fonction appelée en cas d'arrêt de php sur une erreur
 *
 * @todo supprimer cette fonction vide ?
 **/
function shutdown_error() {
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
include 'public.php';
// flushons si cela ne l'a pas encore ete
ob_end_flush();

/**
 * Un exec générique qui branche sur un squelette Z pour écrire
 *
 * La fonction ne fait rien, c'est l'inclusion du fichier qui déclenche le traitement
 */
function exec_fond_dist() {
}
