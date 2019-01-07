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
 * Ce fichier gère la balise dynamique dépréciée `#LOGIN_PRIVE`
 *
 * @package SPIP\Core\Compilateur\Balises
 **/

if (!defined("_ECRIRE_INC_VERSION")) {
	return;
}  #securite


/**
 * Compile la balise dynamique `#LOGIN_PRIVE` qui permet d'afficher le
 * formulaire de connexion vers l'espace privé
 *
 * @balise
 * @deprecated Utiliser `#FORMULAIRE_LOGIN`
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée du code compilé
 **/
function balise_LOGIN_PRIVE($p) {
	return calculer_balise_dynamique($p, 'LOGIN_PRIVE', array('url'));
}

/**
 * Calculs de paramètres de contexte automatiques pour la balise LOGIN_PRIVE
 *
 * Retourne le contexte du formulaire en prenant :
 *
 * 1. l'URL collectée ci-dessus (args0) ou donnée en premier paramètre (args1)
 *    `#LOGIN_PRIVE{#URL_ECRIRE}`
 * 2. un éventuel paramètre (args2) indiquant le login et permettant une écriture
 *    `<boucle(AUTEURS)>[(#LOGIN_PRIVE{#URL_ECRIRE, #LOGIN})]`
 *
 * @param array $args
 *   Liste des arguments demandés obtenus du contexte (url)
 * @param array $context_compil
 *   Tableau d'informations sur la compilation
 * @return array
 *   Liste (url, login) des arguments collectés.
 */
function balise_LOGIN_PRIVE_stat($args, $context_compil) {
	return array(isset($args[1]) ? $args[1] : $args[0], (isset($args[2]) ? $args[2] : ''));
}

/**
 * Exécution de la balise dynamique `#LOGIN_PRIVE`
 *
 * Exécution mappée sur le formulaire de login.
 *
 * @param string $url
 *     URL de destination après l'identification. Par défaut la page
 *     d'accueil de l'espace privé.
 * @param string $login
 *     Login de la personne à identifié (si connu)
 * @return array
 *     Liste : Chemin du squelette, durée du cache, contexte
 **/
function balise_LOGIN_PRIVE_dyn($url, $login) {
	include_spip('balise/formulaire_');
	if (!$url    # pas d'url passee en filtre ou dans le contexte
		and !$url = _request('url') # ni d'url passee par l'utilisateur
	) {
		$url = generer_url_ecrire('accueil', '', true);
	}

	return balise_FORMULAIRE__dyn('login', $url, $login, true);
}
