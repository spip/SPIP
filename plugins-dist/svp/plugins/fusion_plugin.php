<?php

/**
 * Fichier permettant de calculer les descriptions
 * d'un plugin.xml contenant plusieurs balises <plugin>
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Plugins
**/

if (!defined('_ECRIRE_INC_VERSION')) return;
include_spip('inc/plugin');
include_spip('inc/svp_outiller');

/**
 * Fusion des informations de chaque balise plugin d'un plugin.xml en
 * considérant la compatibilité SPIP
 * 
 * Pour les balises plugins uniques cette fonction permet de générer une structure
 * identique pour les balises dites techniques
 * 
 * On limite le traitement a deux balises plugins maximum,
 * ce qui est le cas de tous les plugin.xml actuellement connus
 *
 * @param array $plugins
 *     Arbre des balises plugins présents dans un plugin.xml
 * @return array
 *     Fusion des éléments classé par balise, puis par compatibilité à SPIP.
 *     L'index 0 dans la compatibilité est valable quelque soit la version de SPIP.
 */
function plugins_fusion_plugin($plugins) {
	global $balises_techniques;

	$fusion = array();
	if (!$plugins)
		return $fusion;

	if (count($plugins) == 1) {
		// Balise plugin unique : on ne traite que les balises techniques
		$fusion = $plugins[0];
		foreach ($balises_techniques as $_btech) {
			if (isset($fusion[$_btech]) and $fusion[$_btech]) {
				$balise = $fusion[$_btech];
				unset($fusion[$_btech]);
				$fusion[$_btech][0] = $balise;
			}
			else {
				$fusion[$_btech] = array();
			}
		}
	}
	else {
		// On initialise les informations a retourner avec le bloc a priori le plus recent determine par la compatibilite SPIP :
		// On selectionne le bloc dont la borne min de compatibilite SPIP est la plus elevee
		$cle_min_max = -1;
		$borne_min_max = _SVP_VERSION_SPIP_MIN;
		foreach ($plugins as $_cle => $_plugin) {
			if (!$_plugin['compatibilite'])
				$borne_min = _SVP_VERSION_SPIP_MIN;
			$bornes_spip = extraire_bornes($_plugin['compatibilite']);
			$borne_min = ($bornes_spip['min']['valeur']) ? $bornes_spip['min']['valeur'] : _SVP_VERSION_SPIP_MIN;
			if (spip_version_compare($borne_min_max, $borne_min, '<=')) {
				$cle_min_max = $_cle;
				$borne_min_max = $borne_min;
			}
		}
		$fusion = $plugins[$cle_min_max];

		// On relit les autres blocs que celui venant d'etre selectionne et on fusionne les informations necessaires
		// les traitements effectues sont les suivants :
		// -- nom, prefix, documentation, version, etat, version_base, description : *rien*, on conserve ces informations en l'etat
		// -- options, fonctions, install : *rien*, meme si certaines pourraient etre fusionnees ces infos ne sont pas stockees
		// -- auteur, licence : *rien*, l'heuristique pour fusionner ces infos est trop compliquee aujourdhui car c'est du texte libre
		// -- categorie, logo : si la valeur du bloc selectionne est vide on essaye d'en trouver une non vide dans les autres blocs
		// -- compatible : on constuit l'intervalle global de compatibilite SPIP
		// -- necessite, utilise, lib : on construit le tableau par intervalle de compatibilite SPIP
		$cle_min_min = ($cle_min_max==0) ? 1 : 0;
		if (!$fusion['categorie'] AND $plugins[$cle_min_min]['categorie'])
			$fusion['categorie'] = $plugins[$cle_min_min]['categorie'];
		if ((!isset($fusion['logo']) OR !$fusion['logo']) AND $plugins[$cle_min_min]['logo'])
			$fusion['logo'] = $plugins[$cle_min_min]['logo'];
		$fusion['compatibilite'] = fusionner_intervalles($fusion['compatibilite'], $plugins[$cle_min_min]['compatibilite']);

		// necessite, utilise, lib, chemin, pipeline, bouton, onglet : on indexe chaque liste de dependances
		// par l'intervalle de compatibilite sans regrouper les doublons pour l'instant
		foreach ($balises_techniques as $_btech) {
			if (!isset($fusion[$_btech]) AND !isset($plugins[$cle_min_min][$_btech])) {
				// Aucun des tableaux ne contient cette balise technique : on la positionne a un array vide
				$fusion[$_btech] = array();
			}
			else if (!isset($fusion[$_btech]) OR !$fusion[$_btech]) {
				if ($plugins[$cle_min_min][$_btech]) {
					// La balise technique est vide dans le tableau de fusion mais non vide dans la deuxieme balise plugin
					// On range cette balise dans le tableau fusion de sa compatibilite et on cree la cle commune vide
					$fusion[$_btech][$plugins[$cle_min_min]['compatibilite']] = $plugins[$cle_min_min][$_btech];
					$fusion[$_btech][0] = array();
				}
			}
			else if (!isset($plugins[$cle_min_min][$_btech]) OR !$plugins[$cle_min_min][$_btech]) {
				// La balise technique est non vide dans le tableau de fusion mais vide dans la deuxieme balise plugin
				// On deplace cette balise dans le tableau fusion de sa compatibilite et on cree la cle commune vide
				$balise = $fusion[$_btech];
				unset($fusion[$_btech]);
				$fusion[$_btech][$plugins[$cle_min_max]['compatibilite']] = $balise;
				$fusion[$_btech][0] = array();
			}
			else {
				// Les deux tableaux contiennent une balise technique non vide : il faut fusionner cette balise technique !
				// On parcourt le premier tableau (fusion) en verifiant une egalite avec le deuxieme tableau
				foreach ($fusion[$_btech] as $_cle0 => $_balise0) {
					$balise_commune = false;
					foreach ($plugins[$cle_min_min][$_btech] as $_cle1 => $_balise1) {
						if (balise_identique($_balise0, $_balise1)) {
							// On classe cette balise dans le bloc commun (index 0) et on la supprime dans les
							// 2 tableaux en cours de comparaison
							unset($fusion[$_btech][$_cle0]);
							$fusion[$_btech][0][] = $_balise1;
							unset($plugins[$cle_min_min][$_btech][$_cle1]);
							$balise_commune = true;
							break;
						}
					}
					if (!$balise_commune) {
						$fusion[$_btech][$plugins[$cle_min_max]['compatibilite']][] = $_balise0;
						unset($fusion[$_btech][$_cle0]);
					}
					if (!isset($fusion[$_btech][0]))
						$fusion[$_btech][0] = array();
				}

				// On traite maintenant les balises restantes du deuxieme tableau
				if ($plugins[$cle_min_min][$_btech]) {
					foreach ($plugins[$cle_min_min][$_btech] as $_balise2) {
						$fusion[$_btech][$plugins[$cle_min_min]['compatibilite']][] = $_balise2;
					}
				}
			}
		}

	}
	
	return $fusion;
}

?>
