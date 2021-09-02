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
 * Gestion du formulaire iconifier pour ajouter des logos
 *
 * @package SPIP\Core\Logos
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/actions');

/**
 * Retourne le formulaire de gestion de logo sur les objets.
 *
 * @param string $objet
 * @param integer $id
 * @param string $script
 * @param bool $visible
 * @param bool $flag_modif
 *
 * @return string|array
 *     - Contenu du squelette calculé
 *     - ou tableau d'information sur le squelette.
 */
function inc_iconifier_dist($objet, $id, $script, $visible = false, $flag_modif = true) {
	// compat avec anciens appels
	$objet = objet_type($objet);

	return recuperer_fond(
		'prive/objets/editer/logo',
		['objet' => $objet, 'id_objet' => $id, 'editable' => $flag_modif]
	);
}
