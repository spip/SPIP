<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/actions');
include_spip('inc/editer');

/**
 * Charger les valeurs du #FORMULAIRE_CHANGER_LANGUE
 * @param  $objet
 * @param  $id_objet
 * @param string $retour
 * @return array|bool
 */
function formulaires_changer_langue_charger_dist($objet,$id_objet,$retour=''){
	if (!intval($id_objet))
		return false;
	$valeurs = formulaires_editer_objet_charger($objet,$id_objet,0,0,$retour,'');
	// attention, charger renomme lang => langue pour ne pas perturber la langue d'affichage du squelette
	if (!isset($valeurs['langue']))
		return false;

	$langue_parent = '';
	if (isset($valeurs['id_rubrique']))
		$langue_parent = sql_getfetsel("lang", "spip_rubriques", "id_rubrique=".$valeurs['id_rubrique']);

	$valeurs['_langues'] = liste_options_langues('changer_lang', $valeurs['langue'], $langue_parent);
	$valeurs['_objet'] = $objet;
	$valeurs['_id_objet'] = $id_objet;
	$valeurs['changer_lang'] = $valeurs['lang'];

	$valeurs['_pipeline'] = array('changer_langue',array('type'=>$objet,'id'=>$id_objet));
	
	return $valeurs;
}

/**
 * Verifier les saisies des valeurs du #FORMULAIRE_CHANGER_LANGUE
 * @param  $objet
 * @param  $id_objet
 * @param string $retour
 * @return array
 */
function formulaires_changer_langue_verifier_dist($objet,$id_objet,$retour=''){
	
	$erreurs = formulaires_editer_objet_verifier($objet,$id_objet,array('changer_lang'));
	return $erreurs;
}

/**
 * Enregistrer en base les saisies du #FORMULAIRE_CHANER_LANGUE
 * @param  $objet
 * @param  $id_objet
 * @param string $retour
 * @return array
 */
function formulaires_changer_langue_traiter_dist($objet,$id_objet,$retour=''){
	$res = array();
	if (!_request('annuler')) {
		$res = formulaires_editer_objet_traiter($objet,$id_objet,0,0,$retour);
	}
	$res['editable'] = true;
	return $res;
}

?>
