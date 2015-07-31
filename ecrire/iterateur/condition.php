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

include_spip('iterateur/data');

//
// creer une boucle sur un iterateur CONDITION
// annonce au compilo les "champs" disponibles
//
function iterateur_CONDITION_dist($b) {
	$b->iterateur = 'CONDITION'; # designe la classe d'iterateur
	$b->show = array(
		'field' => array()
	);
	return $b;
}


class IterateurCONDITION extends IterateurData {
	protected function select($command) {
		$this->tableau = array(0=>1);
	}
}
