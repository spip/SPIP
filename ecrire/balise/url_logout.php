<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// http://doc.spip.org/@balise_URL_LOGOUT
function balise_URL_LOGOUT ($p) {return calculer_balise_dynamique($p,'URL_LOGOUT', array());
}

// $args[0] = url destination apres logout [(#URL_LOGOUT{url})]
// http://doc.spip.org/@balise_URL_LOGOUT_stat
function balise_URL_LOGOUT_stat ($args, $context_compil) {
	return array($args[0]);
}

// http://doc.spip.org/@balise_URL_LOGOUT_dyn
function balise_URL_LOGOUT_dyn($cible) {

	if (!$GLOBALS['visiteur_session']['login']) return '';

	return generer_url_action('logout',"logout=public&url=" . rawurlencode($cible ? $cible : self('&')));
}
?>
