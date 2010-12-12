<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

//
// creer une boucle sur un iterateur ENUM
// annonce au compilo les "champs" disponibles
//
function public_creer_boucle_ENUM_dist($b) {
	$b->iterateur = 'IterENUM'; # designe la classe d'iterateur
	$b->show = array(
		'field' => array(
			'valeur' => 'STRING',
		)
	);
	return $b;
}

