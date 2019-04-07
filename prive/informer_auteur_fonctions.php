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
	$login = strval(_request('var_login'));
	$row = auth_informer_login($login);
	if ($row and is_array($row) and isset($row['id_auteur'])) {
		unset($row['id_auteur']);
	}
	
	return json_export($row);
}
