<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Gestion du formulaire de traduction
 *
 * @package SPIP\Core\Formulaires
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/actions');
include_spip('inc/editer');

/**
 * Charger les données de #FORMULAIRE_TRADUIRE
 *
 * @param string $objet
 *     Type d'objet
 * @param int $id_objet
 *     Identifiant de l'objet
 * @param string $retour
 *     URL de retour
 * @param bool $traduire
 *     Permet de désactiver la gestion de traduction sur un objet ayant id_trad
 * @return array|bool
 *     False si l'identifiant n'est pas numérique ou si l'objet n'a pas de langue
 *     Contexte à transmettre au squelette du formulaire sinon
 */
function formulaires_traduire_charger_dist($objet, $id_objet, $retour = '', $traduire = true) {
	if (!(int) $id_objet) {
		return false;
	}
	$valeurs = formulaires_editer_objet_charger($objet, $id_objet, null, 0, $retour, '');
	// verifier que l'objet indique possede bien des champs id_trad et lang
	// attention, charger renomme lang => langue pour ne pas perturber la langue d'affichage du squelette
	if (!isset($valeurs['langue'])) {
		return false;
	}

	$valeurs['editable'] = autoriser('changerlangue', $objet, $id_objet);
	$valeurs['_langue'] = '';
	$langue_parent = '';
	$id_parent = '';
	if (isset($valeurs['id_rubrique'])) {
		$id_parent = $valeurs['id_rubrique'];
	}
	if (isset($valeurs['id_parent'])) {
		$id_parent = $valeurs['id_parent'];
	}
	if ($id_parent) {
		$langue_parent = sql_getfetsel('lang', 'spip_rubriques', 'id_rubrique=' . (int) $id_parent);
	}

	if (!$langue_parent) {
		$langue_parent = $GLOBALS['meta']['langue_site'];
	}
	if (
		$valeurs['editable']
		&& in_array(table_objet_sql($objet), explode(',', (string) $GLOBALS['meta']['multi_objets']))
	) {
		$valeurs['_langue'] = $valeurs['langue'];
	}
	$valeurs['langue_parent'] = $langue_parent;

	$valeurs['_objet'] = $objet;
	$valeurs['_id_objet'] = $id_objet;
	$valeurs['changer_lang'] = '';


	$valeurs['_traduisible'] = autoriser('changertraduction', $objet, $id_objet);
	$valeurs['_traduire'] = '';
	if (isset($valeurs['id_trad'])) {
		$valeurs['_traduire'] = ($traduire ? ' ' : '');
		$valeurs['_vue_traductions'] = 'prive/objets/liste/' . (trouver_fond(
			$f = table_objet($objet) . '-trad',
			'prive/objets/liste'
		) ? $f : 'objets-trad');
		// pour afficher la liste des trad sur la base de l'id_trad en base
		// independamment d'une saisie en cours sur id_trad
		$valeurs['_lister_id_trad'] = $valeurs['id_trad'];
		$valeurs['_id_parent'] = $id_parent;
	}

	$valeurs['_saisie_en_cours'] = (!_request('annuler') && (_request('changer_lang') !== null
		|| _request('changer_id_trad') !== null));
	$valeurs['_pipeline'] = ['traduire', ['type' => $objet, 'id' => $id_objet]];

	return $valeurs;
}

/**
 * Vérifier les saisies des valeurs du #FORMULAIRE_TRADUIRE
 *
 * @param string $objet
 *     Type d'objet
 * @param int $id_objet
 *     Identifiant de l'objet
 * @param string $retour
 *     URL de retour
 * @param bool $traduire
 *     Permet de désactiver la gestion de traduction sur un objet ayant id_trad
 * @return array
 *     Erreurs des saisies
 */
function formulaires_traduire_verifier_dist($objet, $id_objet, $retour = '', $traduire = true) {
	$erreurs = [];

	if (null !== _request('changer_lang')) {
		$erreurs = formulaires_editer_objet_verifier($objet, $id_objet, ['changer_lang']);
	}

	// si id_trad fourni, verifier que cela ne conflicte pas avec un id_trad existant
	// et que ca reference bien un objet existant
	if ($id_trad = _request('id_trad')) {
		$table_objet_sql = table_objet_sql($objet);
		$_id_table_objet = id_table_objet($objet);
		if (
			sql_getfetsel(
				'id_trad',
				$table_objet_sql,
				"$_id_table_objet=" . (int) $id_objet
			)
		) {
			// ne devrait jamais arriver sauf concurence de saisie
			$erreurs['id_trad'] = _L('Une traduction est deja referencee');
		} elseif (!sql_getfetsel($_id_table_objet, $table_objet_sql, "$_id_table_objet=" . (int) $id_trad)) {
			$erreurs['id_trad'] = _L('Indiquez un contenu existant');
		}
	}

	return $erreurs;
}


/**
 * Enregistrer en base les saisies du #FORMULAIRE_TRADUIRE
 *
 * @param string $objet
 *     Type d'objet
 * @param int $id_objet
 *     Identifiant de l'objet
 * @param string $retour
 *     URL de retour
 * @param bool $traduire
 *     Permet de désactiver la gestion de traduction sur un objet ayant id_trad
 * @return array
 *     Retour des traitements
 */
function formulaires_traduire_traiter_dist($objet, $id_objet, $retour = '', $traduire = true) {
	$res = [];
	if (!_request('annuler') && autoriser('changerlangue', $objet, $id_objet)) {
		// action/editer_xxx doit traiter la modif de changer_lang
		$res = formulaires_editer_objet_traiter($objet, $id_objet, 0, 0, $retour);
	}
	if (!_request('annuler') && autoriser('changertraduction', $objet, $id_objet)) {
		if (($id_trad = _request('id_trad')) || _request('supprimer_trad')) {
			$referencer_traduction = charger_fonction('referencer_traduction', 'action');
			$referencer_traduction($objet, $id_objet, (int) $id_trad); // 0 si supprimer_trad
		} elseif (
			($new_id_trad = _request('changer_reference_trad')) && ($new_id_trad = array_keys($new_id_trad)) && ($new_id_trad = reset($new_id_trad))
		) {
			$table_objet_sql = table_objet_sql($objet);
			$_id_table_objet = id_table_objet($objet);
			if ($id_trad = sql_getfetsel('id_trad', $table_objet_sql, "$_id_table_objet=" . (int) $id_objet)) {
				$referencer_traduction = charger_fonction('referencer_traduction', 'action');
				$referencer_traduction($objet, $id_trad, $new_id_trad);
			}
		}
	}
	$res['editable'] = true;
	if (!isset($res['message_erreur'])) {
		set_request('annuler', 'annuler');
	} // provoquer la fermeture du forumlaire

	return $res;
}
