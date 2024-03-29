<?php

declare(strict_types=1);

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2020                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
#securite

function balise_FORMULAIRE_TEST_PHRASEUR($p) {
	return calculer_balise_dynamique($p, 'FORMULAIRE_TEST_PHRASEUR', ['id_rubrique']);
}

function balise_FORMULAIRE_TEST_PHRASEUR_stat($args, $context_compil) {
	// le denier arg peut contenir l'url sur lequel faire le retour
	// exemple dans un squelette article.html : [(#FORMULAIRE_FORUM{#SELF})]

	// recuperer les donnees du forum auquel on repond.
	[$idr, $url] = $args;

	return [$idr, $url];
}

function balise_FORMULAIRE_TEST_PHRASEUR_dyn($id_rubrique, $url) {
	$res = 'OK';

	if (!preg_match('#^\d+$#', $id_rubrique)) {
		$res = 'Erreur id_rubrique non numerique : ' . var_export($id_rubrique, true);
	}

	return [
		'formulaires/test_phraseur',
		0,
		[
			'result' => $res,
		],
	];
}
