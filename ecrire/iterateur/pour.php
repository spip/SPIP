<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

use Spip\Compilateur\Noeud\Boucle;

/**
 * Gestion de l'itérateur POUR
 *
 * @package SPIP\Core\Iterateur\POUR
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Créer une boucle sur un itérateur POUR
 *
 * Annonce au compilateur les "champs" disponibles,
 * c'est à dire 'cle' et 'valeur'.
 *
 * @deprecated 4.0
 * @see Utiliser une boucle (DATA){source tableau,#XX}
 * @see iterateur_DATA_dist()
 *
 * @param Boucle $b
 *     Description de la boucle
 * @return Boucle
 *     Description de la boucle complétée des champs
 */
function iterateur_POUR_dist($b) {
	trigger_deprecation('spip', '4.0', 'Using "%s" BOUCLE type is deprecated, use "%s" instead', 'POUR', '<BOUCLE_x(DATA){source tableau,#XX}>');

	$b->iterateur = 'DATA'; # designe la classe d'iterateur
	$b->show = [
		'field' => [
			'cle' => 'STRING',
			'valeur' => 'STRING',
		]
	];

	return $b;
}
