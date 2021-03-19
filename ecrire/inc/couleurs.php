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
 * Couleurs de l'interface de l’espace privé de SPIP.
 *
 * @package SPIP\Core\Couleurs
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Obtenir ou définir les différents jeux de couleurs de l'espace privé
 *
 * - Appelée _sans argument_, cette fonction retourne un tableau décrivant les jeux les couleurs possibles.
 * - Avec un _argument numérique_, elle retourne les paramètres d'URL
 *   pour les feuilles de style calculées (cf. formulaire configurer_preferences)
 * - Avec un _argument de type tableau_ :
 *   - soit elle remplace le tableau par défaut par celui donné en argument
 *   - soit elle le complète, si `$ajouter` vaut `true`.
 *
 * @see formulaires_configurer_preferences_charger_dist()
 *
 * @staticvar array $couleurs_spip
 * @param null|int|array $choix
 * @param bool $ajouter
 * @return array|string
 */
function inc_couleurs_dist($choix = null, $ajouter = false) {
	static $couleurs_spip = array(
		// Saumon
		4 => array(
			"couleur_foncee" => "#CDA261",
			"couleur_claire" => "#FFDDAA",
		),
		// Orange
		3 => array(
			"couleur_foncee" => "#fa9a00",
			"couleur_claire" => "#ffc000",
		),
		// Rouge
		8 => array(
			"couleur_foncee" => "#DF4543",
			"couleur_claire" => "#FAACB0",
		),
		// Framboise
		2 => array(
			"couleur_foncee" =>  "#D51B60",
			"couleur_claire" => "#EF91B4",
		),
		// Vert de gris
		7 => array(
			"couleur_foncee" => "#999966",
			"couleur_claire" => "#CCCC99",
		),
		// Vert
		1 => array(
			"couleur_foncee" => "#9DBA00",
			"couleur_claire" => "#C5E41C",
		),
		//  Bleu pastel
		5 => array(
			"couleur_foncee" => "#5da7c5",
			"couleur_claire" => "#97d2e1",
		),
		// Violet
		9 => array(
			"couleur_foncee" => "#8F8FBD",
			"couleur_claire" => "#C4C4DD",
		),
		//  Gris
		6 => array(
			"couleur_foncee" => "#85909A",
			"couleur_claire" => "#C0CAD4",
		),
		//  Gris
		10 => array(
			"couleur_foncee" => "#909090",
			"couleur_claire" => "#D3D3D3",
		),
	);

	if (is_numeric($choix)) {
		return
			"couleur_claire=" . substr($couleurs_spip[$choix]['couleur_claire'], 1) .
			'&couleur_foncee=' . substr($couleurs_spip[$choix]['couleur_foncee'], 1);
	} else {
		if (is_array($choix)) {
			if ($ajouter) {
				foreach ($choix as $c) {
					$couleurs_spip[] = $c;
				}

				return $couleurs_spip;
			} else {
				return $couleurs_spip = $choix;
			}
		}

	}

	return $couleurs_spip;
}
