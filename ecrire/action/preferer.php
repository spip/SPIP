<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;


// http://doc.spip.org/@action_preferer_dist
function action_preferer_dist() {
	//
	// Preferences de presentation de l'espace prive
	//
	if ($_GET['arg'] !== 'display:4') {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	} else $arg = $_GET['arg'];

	if (!preg_match(",^(.+):(.*)$,", $arg, $r))
		spip_log("action_preferer_dist: $arg pas compris");
	else {
	$prefs_mod = false;

	list(, $op, $val) = $r;
	if ($op == 'couleur') {
		$GLOBALS['visiteur_session']['prefs']['couleur'] = $val;
		$prefs_mod = true;
	}
	elseif ($op == 'display') {
		$GLOBALS['visiteur_session']['prefs']['display'] = $val;
		$prefs_mod = true;
	}
	elseif ($op == 'display_outils') {
		$GLOBALS['visiteur_session']['prefs']['display_outils'] = $val;
		$prefs_mod = true;
	}

	if ($prefs_mod AND intval($GLOBALS['visiteur_session']['id_auteur']))
		sql_updateq('spip_auteurs', array('prefs' => serialize($GLOBALS['visiteur_session']['prefs'])), "id_auteur=" .intval($GLOBALS['visiteur_session']['id_auteur']));
	
	if ($op == 'spip_ecran') {
		// Poser un cookie,
		// car ce reglage depend plus du navigateur que de l'utilisateur
		$GLOBALS['spip_ecran'] = $val;
		include_spip('inc/cookie');
		spip_setcookie('spip_ecran', $val, time() + 365 * 24 * 3600);
	}

	// Si modif des couleurs en ajax, redirect inutile on a change de CSS
	if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') exit;

	}
}
?>
