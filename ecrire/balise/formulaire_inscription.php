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
 * Ce fichier gère la balise dynamique `#FORMULAIRE_INSCRIPTION`
 *
 * @package SPIP\Core\Inscription
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('base/abstract_sql');
include_spip('inc/filtres');

// Balise independante du contexte


/**
 * Compile la balise dynamique `#FORMULAIRE_INSCRIPTION` qui affiche
 * un formulaire d'inscription au site
 *
 * @balise
 * @example
 *     ```
 *     #FORMULAIRE_INSCRIPTION
 *     #FORMULAIRE_INSCRIPTION{nom_inscription, #ID_RUBRIQUE}
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée du code compilé
 **/
function balise_FORMULAIRE_INSCRIPTION($p) {
	return calculer_balise_dynamique($p, 'FORMULAIRE_INSCRIPTION', []);
}

/**
 * Calculs de paramètres de contexte automatiques pour la balise FORMULAIRE_INSCRIPTION
 *
 * En absence de mode d'inscription transmis à la balise, celui-ci est
 * calculé en fonction de la configuration :
 *
 * - '1comite' si les rédacteurs peuvent s'inscrire,
 * - '6forum' sinon si les forums sur abonnements sont actifs,
 * - rien sinon.
 *
 * @example
 *     ```
 *     #FORMULAIRE_INSCRIPTION
 *     [(#FORMULAIRE_INSCRIPTION{mode_inscription, #ID_RUBRIQUE})]
 *     ```
 *
 * @param array $args
 *   - args[0] un statut d'auteur (rédacteur par defaut)
 *   - args[1] indique la rubrique éventuelle de proposition
 * @param array $context_compil
 *   Tableau d'informations sur la compilation
 * @return array|string
 *   - Liste (statut, id) si un mode d'inscription est possible
 *   - chaîne vide sinon.
 */
function balise_FORMULAIRE_INSCRIPTION_stat($args, $context_compil) {
	[$mode, $id_ou_options, $retour] = array_pad($args, 3, null);
	include_spip('formulaires/inscription');
	list($options, $id) = formulaires_inscription_arguments_id_options($id_ou_options);

	include_spip('action/inscrire_auteur');
	$mode = tester_statut_inscription($mode, $id);

	return $mode ? [$mode, $id_ou_options, $retour] : '';
}
