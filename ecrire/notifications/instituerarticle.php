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

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// Fonction appelee par divers pipelines
// https://code.spip.net/@notifications_instituerarticle_dist
function notifications_instituerarticle_dist($quoi, $id_article, $options) {

	// ne devrait jamais se produire
	if ($options['statut'] == $options['statut_ancien']) {
		spip_log('statut inchange', 'notifications');

		return;
	}

	include_spip('inc/texte');

	$modele = '';
	if ($options['statut'] == 'publie') {
		if (
			$GLOBALS['meta']['post_dates'] == 'non'
			and strtotime($options['date']) > time()
		) {
			$modele = 'notifications/article_valide';
		} else {
			$modele = 'notifications/article_publie';
		}
	}

	if ($options['statut'] == 'prop' and $options['statut_ancien'] != 'publie') {
		$modele = 'notifications/article_propose';
	}

	if ($modele) {
		$destinataires = [];
		if ($GLOBALS['meta']['suivi_edito'] == 'oui') {
			$destinataires = explode(',', $GLOBALS['meta']['adresse_suivi']);
		}


		$destinataires = pipeline(
			'notifications_destinataires',
			[
				'args' => ['quoi' => $quoi, 'id' => $id_article, 'options' => $options],
				'data' => $destinataires
			]
		);

		$texte = email_notification_article($id_article, $modele);
		notifications_envoyer_mails($destinataires, $texte);
	}
}
