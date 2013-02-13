<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


/**
 * Ce fichier gère la balise dynamique `#CONFIGURER_METAS`
 * 
 * @package SPIP\Core\Compilateur\Balises
**/
if (!defined('_ECRIRE_INC_VERSION')) return;


/**
 * Compile la balise dynamique `#CONFIGURER_METAS` appelant un formulaire
 * de configuration
 *
 * L'usage `#CONFIGURER_METAS{nom}` suppose qu'il existe un formulaire
 * nommé `nom.html` et une table SQL `spip_nom` ayant deux colonnes
 * `nom` et `valeur` où sont stockées les configurations (comme la table `spip_meta`).
 * L'enregistrement des saisies du formulaire sera alors automatique.
 *
 * Cette fonction est dépréciée. Pour obtenir ce résultat, il faut
 * utiliser `#FORMULAIRE_CONFIGURER_NOM` qui appelle le formulaire `nom.html`
 * et dans ce formulaire indiquer un champ hidden spécifiant la table d'enregistrement
 * tel que :
 * 
 *     Chaque saisie à la racine de la table spip_nom :
 *     <input type="hidden" name="_meta_casier" value="/nom" />
 *     
 *     Toutes les saisies dans la clé 'config' de spip_nom :
 *     <input type="hidden" name="_meta_casier" value="/nom/config" />
 *     
 *     Chaque saisie à la racine de la table spip_meta :
 *     <input type="hidden" name="_meta_casier" value="" />
 *     
 *     Toutes les saisies dans la clé 'config' de spip_meta :
 *     <input type="hidden" name="_meta_casier" value="config" />
 *
 * @note
 *   Comme l'emplacement du squelette est calcule (par l'argument de la balise)
 *   on ne peut rien dire sur l'existence du squelette lors de la compil.
 *   On pourrait toutefois traiter le cas de l'argument qui est une constante.
 *
 * @balise CONFIGURER_METAS
 * @deprecated Utiliser `#FORMULAIRE_CONFIGURER_XX`
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée du code compilé
**/
function balise_CONFIGURER_METAS_dist($p) {
	return calculer_balise_dynamique($p, $p->nom_champ, array());
}

/**
 * Exécution de la balise dynamique `#CONFIGURER_METAS`
 *
 * À l'exécution on dispose du nom du squelette, on verifie qu'il existe.
 * Pour le calcul du contexte, c'est comme la balise `#FORMULAIRE_`
 * y compris le contrôle au retour pour faire apparaître le message d'erreur.
 *
 * @see balise_CONFIGURER_METAS_dist()
 * @param string $form
 *     Nom du formulaire 
 * @return array
 *     Liste : Chemin du squelette, durée du cache, contexte
**/
function balise_CONFIGURER_METAS_dyn($form) {

	include_spip('balise/formulaire_');
	if (!existe_formulaire($form)) return '';
	$args = func_get_args();
	$contexte = balise_FORMULAIRE__contexte('configurer_metas', $args);
	if (!is_array($contexte)) return $contexte;
	return array('formulaires/' . $form, 3600, $contexte);
}

?>
