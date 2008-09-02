<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;


function action_preferer_dist() {
	//
	// Preferences de presentation
	//
	
	$prefs_mod = false;
	
	if (_request('set_couleur')) {
		$GLOBALS['visiteur_session']['prefs']['couleur'] = intval(_request('set_couleur'));
		$prefs_mod = true;
	}
	if (_request('set_disp')) {
		$GLOBALS['visiteur_session']['prefs']['display'] = intval(_request('set_disp'));
		$prefs_mod = true;
	}
	if ($prefs_mod AND intval($GLOBALS['visiteur_session']['id_auteur'])) {
		sql_updateq('spip_auteurs', array('prefs' => serialize($GLOBALS['visiteur_session']['prefs'])), "id_auteur=" .intval($GLOBALS['visiteur_session']['id_auteur']));
	
		// Si modif des couleurs en ajax, stop ici
		if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') exit;
	}
	
	if (isset($_GET['set_ecran'])) {
		// Poser un cookie,
		// car ce reglage depend plus du navigateur que de l'utilisateur
		$GLOBALS['spip_ecran'] = $_GET['set_ecran'];
		include_spip('inc/cookie');
		spip_setcookie('spip_ecran', $GLOBALS['spip_ecran'], time() + 365 * 24 * 3600);
	}
}

?>