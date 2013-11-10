<?php

/**
 * Gestion du formulaire d'édition de dépot
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Formulaires
 */
 
if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/editer');

/**
 * Chargement du formulaire d'édition de dépot
 *
 * @param int $id_depot
 *     Identifiant du dépot
 * @param string $redirect
 *     URL de redirection
 * @return array
 *     Environnement du formulaire
**/
function formulaires_editer_depot_charger_dist($id_depot, $redirect){
	$valeurs = formulaires_editer_objet_charger('depot', $id_depot, 0, 0, $redirect, 'depots_edit_config');
	return $valeurs;
}

/**
 * Vérification du formulaire d'édition de dépot
 *
 * @param int $id_depot
 *     Identifiant du dépot
 * @param string $redirect
 *     URL de redirection
 * @return array
 *     Tableau des erreurs
**/
function formulaires_editer_depot_verifier_dist($id_depot, $redirect){
	$erreurs = formulaires_editer_objet_verifier('depot', $id_depot, array('titre'));
	return $erreurs;
}

/**
 * Traitement du formulaire d'édition de dépot
 *
 * @param int $id_depot
 *     Identifiant du dépot
 * @param string $redirect
 *     URL de redirection
 * @return array
 *     Retours du traitement
**/
function formulaires_editer_depot_traiter_dist($id_depot, $redirect){
	return formulaires_editer_objet_traiter('depot', $id_depot, 0, 0, $redirect);
}

/**
 * Préparation des configurations particulières du formulaire d'édition de dépot
 *
 * @param array $row
 *     Données SQL actuelles de l'objet qui va être édité
 * @return array
 *     Tableau de configurations qui seront ajoutés à l'environnement
 *     du formulaire sous la clé 'config'
**/
function depots_edit_config($row)
{
	global $spip_ecran, $spip_lang;

	$config = $GLOBALS['meta'];
	$config['lignes'] = ($spip_ecran == "large") ? 8 : 5;
	$config['langue'] = $spip_lang;
	return $config;
}

?>
