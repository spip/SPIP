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
 * Gestion de listes d'objets
 *
 * @package SPIP\Core\Listes
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Affichage des liste d'objets
 *
 * Surcharge pour aiguiller vers la mise en squelettes des listes
 *
 * @deprecated 3.1
 * @see Créer ou utiliser un squelette dans `prive/objets/liste/`
 *      pour la table en question et l'appeler avec une inclusion.
 *
 * @param string $vue
 *     Nom de l'objet
 * @param array $contexte
 *     Contexte du squelette
 * @param bool $force
 *     Si `true` le titre est affiché même s'il n'y a aucun élément dans la liste.
 * @return string
 *     Code HTML de la liste
 */
function inc_lister_objets_dist($vue, $contexte = array(), $force = false) {
	$res = ""; // debug
	if (!is_array($contexte)) {
		return _L('$contexte doit etre un tableau dans inc/lister_objets');
	}

	$fond = "prive/objets/liste/$vue";
	if (!find_in_path($fond . "." . _EXTENSION_SQUELETTES)) {
		// traiter les cas particuliers
		include_spip('base/connect_sql');
		$vue = table_objet($vue);
		$fond = "prive/objets/liste/$vue";
		if (!find_in_path($fond . "." . _EXTENSION_SQUELETTES)) {
			return _L("vue $vue introuvable pour lister les objets");
		}
	}


	$contexte['sinon'] = ($force ? $contexte['titre'] : '');

	$res = recuperer_fond($fond, $contexte, array('ajax' => true));
	if (_request('var_liste')) {
		echo var_export($contexte, true);
	}

	return $res;
}
