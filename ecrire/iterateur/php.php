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

use Spip\Core\Boucle;

/**
 * Gestion de l'itérateur PHP
 *
 * @package SPIP\Core\Iterateur\PHP
 **/


if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Créer une boucle sur un itérateur PHP
 *
 * Annonce au compilateur les "champs" disponibles, c'est à dire
 * 'cle', 'valeur' et toutes les méthodes de l'itérateur désigné.
 *
 * @param Boucle $b
 *     Description de la boucle
 * @param string $iteratorName
 *     Nom de l'itérateur à utiliser
 * @return Boucle
 *     Description de la boucle complétée des champs
 */
function iterateur_php_dist($b, $iteratorName) {
	$b->iterateur = $iteratorName; # designe la classe d'iterateur
	$b->show = [
		'field' => [
			'cle' => 'STRING',
			'valeur' => 'STRING',
		]
	];

	foreach (get_class_methods($iteratorName) as $method) {
		$b->show['field'][strtolower($method)] = 'METHOD';
	}

	return $b;
}
