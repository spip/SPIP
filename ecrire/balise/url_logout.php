<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Ce fichier gère la balise dynamique `#URL_LOGOUT`
 *
 * @package SPIP\Core\Compilateur\Balises
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Compile la balise dynamique `#URL_LOGOUT` qui génère une URL permettant
 * de déconnecter l'auteur actuellement connecté
 *
 * @balise
 * @example
 *     ```
 *     [<a href="(#URL_LOGOUT)">déconnexion</a>]
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée du code compilé
 **/
function balise_URL_LOGOUT($p) {
	return calculer_balise_dynamique($p, 'URL_LOGOUT', []);
}


/**
 * Calculs de paramètres de contexte automatiques pour la balise URL_LOGOUT
 *
 * @param array $args
 *   Liste des arguments transmis à la balise
 *   - `$args[0]` = URL destination après logout `[(#URL_LOGOUT{url})]`
 * @param array $context_compil
 *   Tableau d'informations sur la compilation
 * @return array
 *   Liste (url) des arguments collectés.
 */
function balise_URL_LOGOUT_stat($args, $context_compil) {
	$url = isset($args[0]) ? $args[0] : '';

	return [$url];
}

/**
 * Exécution de la balise dynamique `#URL_LOGOUT`
 *
 * Retourne une URL de déconnexion uniquement si le visiteur est connecté.
 *
 * @param string $cible
 *     URL de destination après déconnexion
 * @return string
 *     URL de déconnexion ou chaîne vide.
 **/
function balise_URL_LOGOUT_dyn($cible) {

	if (empty($GLOBALS['visiteur_session']['login']) and empty($GLOBALS['visiteur_session']['statut'])) {
		return '';
	}

	return generer_url_action('logout', 'logout=public&url=' . rawurlencode($cible ? $cible : self('&')));
}
