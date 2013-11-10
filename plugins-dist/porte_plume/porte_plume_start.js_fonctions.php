<?php

/**
 * Déclarations de fonctions servant à la construction du javascript
 * 
 * @plugin Porte Plume pour SPIP
 * @license GPL
 * @package SPIP\PortePlume\Javascript
**/
if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Retourne la définition de la barre markitup désignée.
 * (cette déclaration est au format json)
 * 
 * Deux pipelines 'porte_plume_pre_charger' et 'porte_plume_charger' 
 * permettent de récuperer l'objet de classe Barre_outil
 * avant son export en json pour modifier des elements.
 *
 * @pipeline_appel porte_plume_barre_pre_charger
 *     Charge des nouveaux boutons au besoin
 * @pipeline_appel porte_plume_barre_charger
 *     Affiche ou cache certains boutons
 * 
 * @return string Déclaration json
 */
function porte_plume_creer_json_markitup(){
	// on recupere l'ensemble des barres d'outils connues
	include_spip('porte_plume_fonctions');
	if (!$sets = barre_outils_liste()) {
		return null;
	}

	// 1) On initialise tous les jeux de barres
	$barres = array();
	foreach($sets as $set) {
		if (($barre = barre_outils_initialiser($set)) AND is_object($barre))
			$barres[$set] = $barre;
	}

	// 2) Préchargement

	/**
	 * Charger des nouveaux boutons au besoin
	 *
	 * @example
	 *     $barre = &$flux['spip'];
	 *     $barre->ajouterApres('bold',array(params));
	 *     $barre->ajouterAvant('bold',array(params));
	 * 
	 *     $bold = $barre->get('bold');
	 *     $bold['id'] = 'bold2';
	 *     $barre->ajouterApres('italic',$bold);
	 * @pipeline_appel porte_plume_barre_pre_charger
	 */
	$barres = pipeline('porte_plume_barre_pre_charger', $barres);


	// 3) Chargement

	/**
	 * Cacher ou afficher certains boutons au besoin
	 *
	 * @example
	 *     $barre = &$flux['spip'];
	 *     $barre->afficher('bold');
	 *     $barre->cacher('bold');
	 * 
	 *     $barre->cacherTout();
	 *     $barre->afficher(array('bold','italic','header1'));
	 * @pipeline_appel porte_plume_barre_charger
	 */
	$barres = pipeline('porte_plume_barre_charger', $barres);


	// 4 On crée les jsons
	$json = "";
	foreach($barres as $set=>$barre) {
		$json .= $barre->creer_json();
	}
	return $json;
}


?>
