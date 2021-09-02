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
 * Gestion de l'action confirmer_inscription
 *
 * @package SPIP\Core\Inscription
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Action de confirmation d'une inscription
 *
 * @global array $GLOBALS ['visiteur_session']
 * @global string $GLOBALS ['redirect']
 * @return void
 */
function action_confirmer_inscription_dist() {
	$jeton = _request('jeton');
	$email = _request('email');

	include_spip('action/inscrire_auteur');
	if (
		$auteur = auteur_verifier_jeton($jeton)
		and $auteur['email'] == $email
		and $auteur['statut'] == 'nouveau'
	) {
		// d'abord on confirme son statut
		$auteur = confirmer_statut_inscription($auteur);

		// OK c'est un nouvel inscrit qui confirme :
		// on le loge => ca va confirmer son statut et c'est plus sympa
		include_spip('inc/auth');
		auth_loger($auteur);

		// et on efface son jeton
		auteur_effacer_jeton($auteur['id_auteur']);

		// si pas de redirection demandee, rediriger vers public ou prive selon le statut de l'auteur
		// TODO: ne semble pas marcher si inscrit non visiteur, a debug
		if (!_request('redirect')) {
			// on passe id_auteur explicite pour forcer une lecture en base de toutes les infos
			if (autoriser('ecrire', '', '', $auteur['id_auteur'])) {
				// poser un cookie admin aussi
				$cookie = charger_fonction('cookie', 'action');
				$cookie('@' . $GLOBALS['visiteur_session']['login']);
				$GLOBALS['redirect'] = _DIR_RESTREINT_ABS;
			} else {
				$GLOBALS['redirect'] = $GLOBALS['meta']['adresse_site'];
			}
		}
	} else {
		// lien perime :
		if ($GLOBALS['visiteur_session']['id_auteur']) {
			// on passe id_auteur explicite pour forcer une lecture en base de toutes les infos
			if (autoriser('ecrire', '', '', $GLOBALS['visiteur_session']['id_auteur'])) {
				$GLOBALS['redirect'] = _DIR_RESTREINT_ABS;
			} else {
				$GLOBALS['redirect'] = $GLOBALS['meta']['adresse_site'];
			}
		} else // rediriger vers la page de login si pas encore loge
		{
			$GLOBALS['redirect'] = parametre_url(generer_url_public('login', '', false), 'url', _request('redirect'));
		}
	}
}
