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
 * Action pour exécuter le cron de manière asynchrone si le serveur le permet
 *
 * @package SPIP\Core\Genie
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Url pour lancer le cron de manière asynchrone si le serveur le permet
 *
 * Cette fonction est utile pour être appelée depuis un cron UNIX par exemple
 * car elle se termine tout de suite
 *
 * Exemple de tache cron Unix pour un appel toutes les minutes :
 * `* * * * * curl  http://www.mondomaine.tld/spip.php?action=super_cron`
 *
 * @deprecated 4.0
 * utiliser directement curl  http://www.mondomaine.tld/spip.php?action=cron
 * qui ferme la connection immediatement et est plus robuste
 * (ici le curl peut ne pas marcher si la configuration reseau du serveur le bloque)
 *
 * @see queue_affichage_cron() Dont une partie du code est repris ici.
 * @see action_cron() URL appelée en asynchrone pour excécuter le cron
 * @uses queue_lancer_url_http_async()
 */
function action_super_cron_dist() {
	$url_cron = generer_url_action('cron');
	include_spip('inc/queue');
	queue_lancer_url_http_async($url_cron);
}
