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
 * Ce fichier gère la balise dynamique `#FORMULAIRE_ECRIRE_AUTEUR`
 *
 * @package SPIP\Core\Compilateur\Balises
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('base/abstract_sql');

/**
 * Compile la balise dynamique `#FORMULAIRE_ECRIRE_AUTEUR` qui permet
 * très logiquement d'afficher un formulaire pour écrire à un auteur
 *
 * Cette balise récupère l'id_auteur (et son email) ou l'id_article de
 * la boucle AUTEURS ou ARTICLES englobante.
 *
 * Le ou les emails correspondants à l'auteur ou aux auteurs de l'article
 * sont transmis au formulaire CVT (mais ils ne seront pas dévoilés
 * au visiteur).
 *
 * @balise
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée du code compilé
 **/
function balise_FORMULAIRE_ECRIRE_AUTEUR($p) {
	return calculer_balise_dynamique($p, 'FORMULAIRE_ECRIRE_AUTEUR', ['id_auteur', 'id_article', 'email']);
}

/**
 * Calculs de paramètres de contexte automatiques pour la balise FORMULAIRE_ECRIRE_AUTEUR
 *
 * Retourne le contexte du formulaire uniquement si l'email de l'auteur
 * est valide, sinon rien (pas d'exécution/affichage du formulaire)
 *
 * @param array $args
 *   Liste des arguments demandés obtenus du contexte (id_auteur, id_article, email)
 * @param array $context_compil
 *   Tableau d'informations sur la compilation
 * @return array|string
 *   - Liste (id_auteur, id_article, email) des paramètres du formulaire CVT
 *   - chaîne vide sinon (erreur ou non affichage).
 */
function balise_FORMULAIRE_ECRIRE_AUTEUR_stat($args, $context_compil) {
	include_spip('inc/filtres');
	// Pas d'id_auteur ni d'id_article ? Erreur de contexte
	$id = intval($args[1]);
	if (!$args[0] and !$id) {
		$msg = [
			'zbug_champ_hors_motif',
			[
				'champ' => 'FORMULAIRE_ECRIRE_AUTEUR',
				'motif' => 'AUTEURS/ARTICLES'
			]
		];

		erreur_squelette($msg, $context_compil);

		return '';
	}
	// Si on est dans un contexte article,
	// sortir tous les mails des auteurs de l'article
	if (!$args[0] and $id) {
		$r = '';
		$s = sql_allfetsel(
			'email',
			'spip_auteurs AS A LEFT JOIN spip_auteurs_liens AS L ON (A.id_auteur=L.id_auteur AND L.objet=\'article\')',
			"A.email != '' AND L.id_objet=$id"
		);
		foreach ($s as $row) {
			if (email_valide($row['email'])) {
				$r .= ', ' . $row['email'];
			}
		}
		$args[2] = substr($r, 2);
	}

	// On ne peut pas ecrire a un auteur dont le mail n'est pas valide
	if (!$args[2] or !email_valide($args[2])) {
		return '';
	}

	// OK
	return $args;
}
