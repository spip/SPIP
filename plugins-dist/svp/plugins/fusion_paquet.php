<?php

/**
 * Fichier permettant de calculer les descriptions
 * d'un paquet.xml contenant plusieurs balises <spip>
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Plugins
**/

if (!defined('_ECRIRE_INC_VERSION')) return;
include_spip('inc/plugin');
include_spip('inc/svp_outiller');


/**
 * Fusion des informations de chaque balise spip d'un paquet.xml en
 * considérant la compatibilité SPIP
 * 
 * Pour les balises paquets sans balise spip cette fonction permet de générer
 * une structure identique pour les balises dites techniques
 * 
 * @param array $plugins
 *     Arbre de description du paquet.xml
 * @return array
 *     Fusion des éléments classé par balise, puis par compatibilité à SPIP.
 *     L'index 0 dans la compatibilité est valable quelque soit la version de SPIP.
 */
function plugins_fusion_paquet($plugins) {
	global $balises_techniques;

	$fusion = array();
	if (!$plugins)
		return $fusion;

	// On initialise les informations a retourner avec l'index 0 du tableau qui contient les donnees communes
	// de la balise paquet
	$fusion = $plugins[0];

	// On relit les balises paquet et spip et :
	// -- pour la balise paquet on reindexe les balises techniques dans un sous-tableau d'index 0
	// -- pour chaque balise spip on merge les informations additionnelles avec les donnees
	// communes dans un sous-tableau d'index egal a l'intervalle de compatibilite
	foreach ($plugins as $_compatibilite => $_paquet_spip) {
		if ($_paquet_spip['balise'] == 'paquet') {
			// Deplacement du contenu de chaque balise technique commune si elle est non vide
			foreach ($balises_techniques as $_btech) {
				if (isset($fusion[$_btech]) and $fusion[$_btech]) {
					$balise = $fusion[$_btech];
					unset($fusion[$_btech]);
					$fusion[$_btech][0] = $balise;
				}
			}
		}
		else {
			// Balise spip
			// On merge les balises techniques existantes en les rangeant dans un sous tableau indexe par
			// la compatibilite et ce pour chaque balise
			foreach ($_paquet_spip as $_index => $_balise) {
				if ($_index AND $_index != 'balise') {
					$fusion[$_index][$_compatibilite] = $_balise;
					if (!isset($fusion[$_index][0]))
						$fusion[$_index][0] = array();
				}
			}
		}
	}

	return $fusion;
}

?>
