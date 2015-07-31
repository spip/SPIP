<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

global $balise_URL_LOGOUT_collecte;
$balise_URL_LOGOUT_collecte = array();

// filtres[0] = url destination apres logout [(#URL_LOGOUT|url)]
function balise_URL_LOGOUT_stat ($args, $filtres) {
	return array($filtres[0]);
}

function balise_URL_LOGOUT_dyn($cible) {
	if (!$login = $GLOBALS['auteur_session']['login'])
		return '';

	if (!$cible)
		$cible = self();

	return generer_url_public('spip_cookie',
		"logout_public=".urlencode($login)."&amp;url=" . urlencode($cible)
	);
}


function balise_URL_LOGOUT ($p) {return declencher_balise_dynamique($p,'URL_LOGOUT');}
?>
