<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
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

	$valeurs = [];
	$valeurs['display_navigation'] = $GLOBALS['visiteur_session']['prefs']['display_navigation'] ?? 'navigation_avec_icones';
	$valeurs['display'] = (isset($GLOBALS['visiteur_session']['prefs']['display']) && $GLOBALS['visiteur_session']['prefs']['display'] > 0) ? $GLOBALS['visiteur_session']['prefs']['display'] : 2;
	$valeurs['couleur'] = (isset($GLOBALS['visiteur_session']['prefs']['couleur']) && $GLOBALS['visiteur_session']['prefs']['couleur'] > 0) ? $GLOBALS['visiteur_session']['prefs']['couleur'] : 1;

	$couleurs = charger_fonction('couleurs', 'inc');
	$les_couleurs = $couleurs();
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
		$couleurs = charger_fonction('couleurs', 'inc');
		$les_couleurs = $couleurs([], true);
		if (isset($les_couleurs[$couleur])) {
			$GLOBALS['visiteur_session']['prefs']['couleur'] = $couleur;
		}
	}
	if ($display = (int) _request('display')) {
		$GLOBALS['visiteur_session']['prefs']['display'] = $display;
	}
	if (
		($display_navigation = _request('display_navigation'))
		&& in_array($display_navigation, ['navigation_sans_icone', 'navigation_avec_icones'])
	) {
		$GLOBALS['visiteur_session']['prefs']['display_navigation'] = $display_navigation;
	}

	if ((int) $GLOBALS['visiteur_session']['id_auteur']) {
		include_spip('action/editer_auteur');
		$c = ['prefs' => serialize($GLOBALS['visiteur_session']['prefs'])];

		if (($imessage = _request('imessage')) && in_array($imessage, ['oui', 'non'])) {
			$c['imessage'] = $imessage;
		}

		auteur_modifier($GLOBALS['visiteur_session']['id_auteur'], $c);
	}

	return ['message_ok' => _T('config_info_enregistree'), 'editable' => true];
}
