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
 * Ce fichier gère la balise dynamique `#MENU_LANG_ECRIRE`
 *
 * @package SPIP\Core\Compilateur\Balises
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Compile la balise dynamique `#MENU_LANG_ECRIRE` qui affiche
 * un sélecteur de langue pour l'interface privée
 *
 * Affiche le menu des langues de l'espace privé
 * et présélectionne celle la globale `$lang`
 * ou de l'arguemnt fourni: `#MENU_LANG_ECRIRE{#ENV{malangue}}`
 *
 * @balise
 * @link https://www.spip.net/4626
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée du code compilé
 **/
function balise_MENU_LANG_ECRIRE($p) {
	return calculer_balise_dynamique($p, 'MENU_LANG_ECRIRE', ['lang']);
}

/**
 * Calculs de paramètres de contexte automatiques pour la balise MENU_LANG_ECRIRE
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
 *   - array: Liste (lang) des arguments collectés et fournis.
 *   - string: Si pas de multilinguisme
 */
function balise_MENU_LANG_ECRIRE_stat($args, $context_compil) {
	include_spip('inc/lang');
	if (strpos($GLOBALS['meta']['langues_proposees'], ',') === false) {
		return '';
	}

	return $args;
}

/**
 * Exécution de la balise dynamique `#MENU_LANG_ECRIRE`
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
function balise_MENU_LANG_ECRIRE_dyn($opt) {
	return menu_lang_pour_tous('var_lang_ecrire', $opt);
}

/**
 * Calcule l'environnement et le squelette permettant d'afficher
 * le formulaire de sélection de changement de langue
 *
 * Le changement de langue se fait par l'appel à l'action `converser`
 *
 * @uses lang_select()
 * @see  action_converser_dist()
 *
 * @param string $nom
 *     Nom de la variable qui sera postée par le formulaire
 * @param string $default
 *     Valeur par défaut de la langue
 * @return array
 *     Liste : Chemin du squelette, durée du cache, contexte
 **/
function menu_lang_pour_tous($nom, $default) {
	include_spip('inc/lang');

	if ($GLOBALS['spip_lang'] <> $default) {
		$opt = lang_select($default);  # et remplace
		if ($GLOBALS['spip_lang'] <> $default) {
			$default = '';  # annule tout choix par defaut
			if ($opt) {
				lang_select();
			}
		}
	}

	# lien a partir de /
	$cible = parametre_url(self(), 'lang', '', '&');
	$post = generer_url_action('converser', 'redirect=' . rawurlencode($cible), true);

	return [
		'formulaires/menu_lang',
		3600,
		[
			'nom' => $nom,
			'url' => $post,
			'name' => $nom,
			'default' => $default,
		]
	];
}
