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
 * Ce fichier gère la balise dynamique `#MENU_LANG`
 *
 * @package SPIP\Core\Compilateur\Balises
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Compile la balise dynamique `#MENU_LANG` qui affiche
 * un sélecteur de langue pour l'espace public
 *
 * Affiche le menu des langues de l'espace public
 * et présélectionne celle la globale `$lang`
 * ou de l'arguemnt fourni: `#MENU_LANG{#ENV{malangue}}`
 *
 * @balise
 * @link https://www.spip.net/4626
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée du code compilé
 **/
function balise_MENU_LANG($p) {
	return calculer_balise_dynamique($p, 'MENU_LANG', ['lang']);
}

/**
 * Calculs de paramètres de contexte automatiques pour la balise MENU_LANG
 *
 * S'il n'y a qu'une langue proposée, pas besoin du formulaire
 * (éviter une balise ?php inutile)
 *
 * @param array $args
 *   Liste des arguments demandés obtenus du contexte (lang)
 *   complétés de ceux fournis à la balise
 * @param array $context_compil
 *   Tableau d'informations sur la compilation
 * @return array|string
 *   array: Liste (lang) des arguments collectés et fournis.
 *   string: (vide) si pas de multilinguisme
 */
function balise_MENU_LANG_stat($args, $context_compil) {
	if (!str_contains((string) $GLOBALS['meta']['langues_multilingue'], ',')) {
		return '';
	}

	return $args;
}

/**
 * Exécution de la balise dynamique `#MENU_LANG`
 *
 * @uses menu_lang_pour_tous()
 * @note
 *   Normalement `$opt` sera toujours non vide suite au test ci-dessus
 *
 * @param string $opt
 *     Langue par défaut
 * @return array
 *     Liste : Chemin du squelette, durée du cache, contexte
 **/
function balise_MENU_LANG_dyn($opt) {
	include_spip('balise/menu_lang_ecrire');

	return menu_lang_pour_tous('var_lang', $opt);
}
