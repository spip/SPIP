<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

use Spip\Admin\Bouton;

/**
 * Gestion des boutons de l'interface privée
 *
 * @package SPIP\Core\Boutons
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Définir la liste des onglets dans une page de l'interface privée.
 *
 * On passe la main au pipeline "ajouter_onglets".
 *
 * @see plugin_ongletbouton() qui crée la fonction `onglets_plugins()`
 * @pipeline_appel ajouter_onglets
 *
 * @param string $script
 * @return array
 */
function definir_barre_onglets($script) {

	$onglets = [];
	$liste_onglets = [];

	// ajouter les onglets issus des plugin via paquet.xml
	if (function_exists('onglets_plugins')) {
		$liste_onglets = onglets_plugins();
	}


	foreach ($liste_onglets as $id => $infos) {
		if (
			($parent = $infos['parent'])
			&& $parent == $script
			&& autoriser('onglet', "_$id")
		) {
			$onglets[$id] = new Bouton(
				isset($infos['icone']) ? find_in_theme($infos['icone']) : '',  // icone
				$infos['titre'],  // titre
				(isset($infos['action']) && $infos['action'])
					? generer_url_ecrire(
						$infos['action'],
						(isset($infos['parametres']) && $infos['parametres']) ? $infos['parametres'] : ''
					)
					: null
			);
		}
	}

	return pipeline('ajouter_onglets', ['data' => $onglets, 'args' => $script]);
}

/**
 *
 * Création de la barre d'onglets
 *
 * @uses definir_barre_onglets()
 * @uses onglet()
 * @uses debut_onglet()
 * @uses fin_onglet()
 *
 * @param string $rubrique
 * @param string $ongletCourant
 * @param string $class
 * @return string
 */
function barre_onglets($rubrique, $ongletCourant, $class = 'barre_onglet') {
	include_spip('inc/presentation');

	$res = '';

	foreach (definir_barre_onglets($rubrique) as $exec => $onglet) {
		$url = $onglet->url ?: generer_url_ecrire($exec);
		$res .= onglet(_T($onglet->libelle), $url, $exec, $ongletCourant, $onglet->icone);
	}

	return $res ? debut_onglet($class) . $res . fin_onglet() : ('');
}
