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
 * Préchargement les formulaires d'édition d'article, notament pour les traductions
 *
 * @package SPIP\Core\Objets
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/precharger_objet');


/**
 * Retourne les valeurs à charger pour un formulaire d'édition d'un article
 *
 * Lors d'une création, certains champs peuvent être préremplis
 * (c'est le cas des traductions)
 *
 * @param string|int $id_article
 *     Identifiant de l'article, ou "new" pour une création
 * @param int $id_rubrique
 *     Identifiant éventuel de la rubrique parente
 * @param int $lier_trad
 *     Identifiant éventuel de la traduction de référence
 * @return array
 *     Couples clés / valeurs des champs du formulaire à charger.
 **/
function inc_precharger_article_dist($id_article, $id_rubrique = 0, $lier_trad = 0) {
	return precharger_objet('article', $id_article, $id_rubrique, $lier_trad, 'titre');
}


/**
 * Récupère les valeurs d'une traduction de référence pour la création
 * d'un article (préremplissage du formulaire).
 *
 * @note
 *     Fonction facultative si pas de changement dans les traitements
 *
 * @param string|int $id_article
 *     Identifiant de l'article, ou "new" pour une création
 * @param int $id_rubrique
 *     Identifiant éventuel de la rubrique parente
 * @param int $lier_trad
 *     Identifiant éventuel de la traduction de référence
 * @return array
 *     Couples clés / valeurs des champs du formulaire à charger
 **/
function inc_precharger_traduction_article_dist($id_article, $id_rubrique = 0, $lier_trad = 0) {
	return precharger_traduction_objet('article', $id_article, $id_rubrique, $lier_trad, 'titre');
}
