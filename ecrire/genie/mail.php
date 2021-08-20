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
 * Tâche de fond pour l'envoi des mails de nouveautés
 *
 * @package SPIP\Core\Mail
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Envoi du Mail des nouveautés
 *
 * Ce mail est basé sur le squelette nouveautes.html
 *
 * La meta `dernier_envoi_neuf` permet de marquer la date du dernier envoi
 * et de determiner les nouveautes publiees depuis cette date
 *
 * @param int $t
 * @return int
 */
function genie_mail_dist($t) {
	$adresse_neuf = $GLOBALS['meta']['adresse_neuf'];
	$jours_neuf = $GLOBALS['meta']['jours_neuf'];

	$now = time();
	if (!isset($GLOBALS['meta']['dernier_envoi_neuf'])) {
		ecrire_meta('dernier_envoi_neuf', date('Y-m-d H:i:s', $now - (3600 * 24 * $jours_neuf)));
	}

	$page = recuperer_fond(
		'nouveautes',
		['date' => $GLOBALS['meta']['dernier_envoi_neuf'], 'jours_neuf' => $jours_neuf],
		['raw' => true]
	);

	if (strlen(trim($page['texte']))) {
		// recuperer les entetes envoyes par #HTTP_HEADER
		$headers = '';
		if (isset($page['entetes']) and count($page['entetes'])) {
			foreach ($page['entetes'] as $k => $v) {
				$headers .= (strlen($v) ? "$k: $v" : $k) . "\n";
			}
		}

		include_spip('inc/notifications');
		notifications_envoyer_mails($adresse_neuf, $page['texte'], '', '', $headers);
		ecrire_meta('dernier_envoi_neuf', date('Y-m-d H:i:s', $now));
	} else {
		spip_log("mail nouveautes : rien de neuf depuis $jours_neuf jours");
	}

	return 1;
}
