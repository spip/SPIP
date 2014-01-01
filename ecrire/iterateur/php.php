<?php


/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

//
// creer une boucle sur un iterateur
// annonce au compilo les "champs" disponibles
//
function iterateur_php_dist($b, $iteratorName) {
	$b->iterateur = $iteratorName; # designe la classe d'iterateur
	$b->show = array(
		'field' => array(
			'cle' => 'STRING',
			'valeur' => 'STRING',
		)
	);
	foreach (get_class_methods($iteratorName) as $method) {
		$b->show['field'][ strtolower($method) ] = 'METHOD';
	}
	/*
	foreach (get_class_vars($iteratorName) as $property) {
		$b->show['field'][ strtolower($property) ] = 'PROPERTY';
	}
	*/
	return $b;
}


?>
