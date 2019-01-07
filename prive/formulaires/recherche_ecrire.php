<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2019                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion du formulaire de recherche pour l'espace privé
 *
 * @package SPIP\Core\Formulaires
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Chargement des valeurs par defaut des champs du formulaire de recherche de l'espace privé
 *
 * Le formulaire dirige son action directement sur la page de l'action demandée.
 * Il n'y a pas de vérification ni de traitement dans ce formulaire.
 *
 * @param string $action
 *     URL de la page exec qui reçoit la recherche. Par défaut l'URL de l'exec 'recherche'.
 * @param string $class
 *     Classe CSS supplémentaire appliquée sur le formulaire
 * @return array Environnement du formulaire
 **/
function formulaires_recherche_ecrire_charger_dist($action = '', $class = '') {
	if ($GLOBALS['spip_lang'] != $GLOBALS['meta']['langue_site']) {
		$lang = $GLOBALS['spip_lang'];
	} else {
		$lang = '';
	}

	return
		array(
			'action' => ($action ? $action : generer_url_ecrire('recherche')),
			# action specifique, ne passe pas par Verifier, ni Traiter
			'recherche' => _request('recherche'),
			'lang' => $lang,
			'class' => $class,
			'_id_champ' => 'rechercher_' . substr(md5($action . $class), 0, 4),
		);
}
