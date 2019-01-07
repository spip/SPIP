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
 * Formulaire de configuration des préférences auteurs dans l'espace privé
 *
 * Ces préférences sont stockées dans la clé `prefs` dans la session de l'auteur
 * en tant que tableau, ainsi que dans la colonne SQL `prefs` de spip_auteurs
 * sous forme sérialisée.
 *
 * @package SPIP\Core\Formulaires
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Chargement du formulaire de préférences d'un auteur dans l'espace privé
 *
 * @return array
 *     Environnement du formulaire
 **/
function formulaires_configurer_preferences_charger_dist() {
	// travailler sur des meta fraiches
	include_spip('inc/meta');
	lire_metas();

	$valeurs = array();
	$valeurs['display_navigation'] = isset($GLOBALS['visiteur_session']['prefs']['display_navigation']) ? $GLOBALS['visiteur_session']['prefs']['display_navigation'] : 'navigation_avec_icones';
	$valeurs['display_outils'] = isset($GLOBALS['visiteur_session']['prefs']['display_outils']) ? $GLOBALS['visiteur_session']['prefs']['display_outils'] : 'oui';
	$valeurs['display'] = (isset($GLOBALS['visiteur_session']['prefs']['display']) and $GLOBALS['visiteur_session']['prefs']['display'] > 0) ? $GLOBALS['visiteur_session']['prefs']['display'] : 2;
	$valeurs['couleur'] = (isset($GLOBALS['visiteur_session']['prefs']['couleur']) and $GLOBALS['visiteur_session']['prefs']['couleur'] > 0) ? $GLOBALS['visiteur_session']['prefs']['couleur'] : 1;
	$valeurs['spip_ecran'] = $GLOBALS['spip_ecran'];

	$couleurs = charger_fonction('couleurs', 'inc');
	$les_couleurs = $couleurs(array(), true);
	foreach ($les_couleurs as $k => $c) {
		$valeurs['_couleurs_url'][$k] = generer_url_public('style_prive.css', 'ltr='
			. $GLOBALS['spip_lang_left'] . '&'
			. $couleurs($k));
		$valeurs['couleurs'][$k] = $c;
	}

	$valeurs['imessage'] = $GLOBALS['visiteur_session']['imessage'];

	return $valeurs;
}

/**
 * Traitements du formulaire de préférences d'un auteur dans l'espace privé
 *
 * @return array
 *     Retours des traitements
 **/
function formulaires_configurer_preferences_traiter_dist() {

	if ($couleur = _request('couleur')) {
		$GLOBALS['visiteur_session']['prefs']['couleur'] = $couleur;
	}
	if ($display = _request('display')) {
		$GLOBALS['visiteur_session']['prefs']['display'] = $display;
	}
	if ($display_navigation = _request('display_navigation')) {
		$GLOBALS['visiteur_session']['prefs']['display_navigation'] = $display_navigation;
	}
	if (!is_null($display_outils = _request('display_outils'))) {
		$GLOBALS['visiteur_session']['prefs']['display_outils'] = $display_outils;
	}

	if (intval($GLOBALS['visiteur_session']['id_auteur'])) {
		include_spip('action/editer_auteur');
		$c = array('prefs' => serialize($GLOBALS['visiteur_session']['prefs']));

		if (_request('imessage')) {
			$c['imessage'] = _request('imessage');
		}

		auteur_modifier($GLOBALS['visiteur_session']['id_auteur'], $c);
	}

	if ($spip_ecran = _request('spip_ecran')) {
		// Poser un cookie,
		// car ce reglage depend plus du navigateur que de l'utilisateur
		$GLOBALS['spip_ecran'] = $spip_ecran;
		include_spip('inc/cookie');
		spip_setcookie('spip_ecran', $_COOKIE['spip_ecran'] = $spip_ecran, time() + 365 * 24 * 3600);
	}

	return array('message_ok' => _T('config_info_enregistree'), 'editable' => true);
}
