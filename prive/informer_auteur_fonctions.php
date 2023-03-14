<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Retrouve pour le formulaire de login les informations d'un login qui permettront de crypter le mot de passe saisi
 *
 * Si le login n'est pas trouvé, retourne de fausses informations,
 * sauf si la constante `_AUTORISER_AUTH_FAIBLE` est déclarée à true.
 *
 * @note
 *     Le parametre var_login n'est pas dans le contexte pour optimiser le cache
 *     il faut aller le chercher à la main
 *
 * @uses auth_informer_login()
 * @uses json_export()
 *
 * @param string $bof
 *     Date de la demande
 * @return string
 *     JSON des différentes informations
 */
function informer_auteur($bof) {
	include_spip('inc/json');
	include_spip('formulaires/login');
	include_spip('inc/auth');
	$login = (string) _request('var_login');
	$row = auth_informer_login($login);
	if ($row && is_array($row) && isset($row['id_auteur'])) {
		unset($row['id_auteur']);
	}

	// on encode tout pour ne pas avoir de probleme au deballage dans le JS
	return json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
}
