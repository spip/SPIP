<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion de l'itérateur CONDITION
 *
 * @package SPIP\Core\Iterateur\CONDITION
**/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('iterateur/data');


/**
 * Créer une boucle sur un itérateur CONDITION
 * 
 * Annonce au compilateur les "champs" disponibles,
 * c'est à dire aucun. Une boucle CONDITION n'a pas de données !
 *
 * @param Boucle $b
 *     Description de la boucle
 * @return Boucle
 *     Description de la boucle complétée des champs
 */
function iterateur_CONDITION_dist($b) {
	$b->iterateur = 'CONDITION'; # designe la classe d'iterateur
	$b->show = array(
		'field' => array()
	);
	return $b;
}

/**
 * Iterateur CONDITION pour itérer sur des données
 *
 * La boucle condition n'a toujours qu'un seul élément.
 */
class IterateurCONDITION extends IterateurData {
	/**
	 * Obtenir les données de la boucle CONDITION
	 *
	 * @param array $command
	**/
	protected function select($command) {
		$this->tableau = array(0=>1);
	}
}
