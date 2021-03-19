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
		4 => ["couleur_theme" => "#c89851"], 
		// Orange
		3 => ["couleur_theme" => "#e69900"],
		// Rouge
		8 => ["couleur_theme" => "#de3d3b"],
		// Framboise
		2 => ["couleur_theme" =>  "#d51a5f"],
		// Vert de gris
		7 => ["couleur_theme" => "#999966"],
		// Vert
		1 => ["couleur_theme" => "#8ea824"],
		//  Bleu pastel
		5 => ["couleur_theme" => "#56a4c2"],
		// Violet
		9 => ["couleur_theme" => "#6e6eaa"],
		//  Gris
		6 => ["couleur_theme" => "#828d97"],
		//  Gris
		10 => ["couleur_theme" => "#8c8c8c"],
	);

	if (is_numeric($choix)) {
		$c = $couleurs_spip[$choix];
		// compat < SPIP 3.3
		include_spip('inc/filtres_images_mini');
		$c["couleur_foncee"] = $c["couleur_theme"];
		$c["couleur_claire"] = "#" . couleur_eclaircir($c["couleur_theme"], .5);

		return
			'couleur_theme=' . substr($c['couleur_theme'], 1)
			// compat < SPIP 3.3
			. '&couleur_claire=' . substr($c['couleur_claire'], 1)
			. '&couleur_foncee=' . substr($c['couleur_foncee'], 1);
	} else {
		if (is_array($choix)) {
			// compat < SPIP 3.3
			$compat_spip_33 = function($c) {
				if (!isset($c["couleur_theme"])) {
					$c["couleur_theme"] = $c["couleur_foncee"];
					unset($c["couleur_foncee"]);
					unset($c["couleur_claire"]);
					unset($c["couleur_lien"]);
					unset($c["couleur_lien_off"]);
				}
				return $c;
			};
			if ($ajouter) {
				foreach ($choix as $c) {
					$couleurs_spip[] = $compat_spip_33($c);
				}

				return $couleurs_spip;
			} else {
				$choix = array_map($compat_spip_33, $choix);
				return $couleurs_spip = $choix;
			}
		}
	}

	return $couleurs_spip;
}
