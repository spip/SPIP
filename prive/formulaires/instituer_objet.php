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
 * Gestion du formulaire d'institution (changement de statut) d'un objet
 *
 * @package SPIP\Core\Formulaires
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/editer');
include_spip('inc/autoriser');
include_spip('inc/puce_statut');

/**
 * Filtres les statuts utilisable selon les droits de publication
 *
 * @param array $desc
 * @param bool $publiable
 * @return array
 */
function lister_statuts_proposes($desc, $publiable = true) {
	if (!isset($desc['statut_textes_instituer'])) {
		return false;
	}

	$l = $desc['statut_textes_instituer'];
	if (!$publiable) {
		unset($l['publie']);
		unset($l['refuse']);
	}

	return $l;
}

/**
 * Chargement du formulaire instituer objet
 *
 * @uses formulaires_editer_objet_charger()
 *
 * @param string $objet
 *     Type d'objet
 * @param int $id_objet
 *     Identifiant de l'objet
 * @param string $retour
 *     URL de redirection après le traitement
 * @param bool $editable
 *     Indique si le statut est éditable ou non.
 *     Dans tous les cas, si l'on n'a pas la permission de modifier l'objet,
 *     cette option sera mise à false.
 * @return array|bool
 *     Environnement du formulaire ou false si aucun affichage à faire.
 */
function formulaires_instituer_objet_charger_dist($objet, $id_objet, $retour = '', $editable = true) {
	$editable = (bool) $editable;

	$table = table_objet_sql($objet);
	$desc = lister_tables_objets_sql($table);

	if (!isset($desc['statut_textes_instituer'])) {
		return false;
	}

	if (!autoriser('modifier', $objet, $id_objet)) {
		$editable = false;
	}

	// charger le contenu de l'objet
	// dont son champ statut
	$v = formulaires_editer_objet_charger($objet, $id_objet, 0, 0, '', '');

	$publiable = true;
	$statuts = lister_statuts_proposes($desc);
	// tester si on a le droit de publier, si un statut publie existe
	if (
		isset($statuts['publie'])
		&& !autoriser('instituer', $objet, $id_objet, null, ['statut' => 'publie'])
	) {
		if ($v['statut'] == 'publie') {
			$editable = false;
		} else {
			$publiable = false;
		}
	}
	$statuts = lister_statuts_proposes($desc, $editable ? $publiable : true);
	if (count($statuts) == 1 && isset($statuts[$v['statut']])) {
		$editable = false;
	}

	#if (!count($valeurs['statuts']))
	return [
		'editable' => $editable,
		'statut' => $v['statut'],
		'_objet' => $objet,
		'_id_objet' => $id_objet,
		'_statuts' => $statuts,
		'_publiable' => $publiable,
		'_label' => $desc['texte_changer_statut'] ?? 'texte_article_statut',
		'_aide' => $desc['aide_changer_statut'] ?? '',
		'_hidden' => "<input type='hidden' name='statut_old' value='" . $v['statut'] . "'>",
	];
}

/**
 * Vérifications du formulaire instituer objet
 *
 * @uses formulaires_editer_objet_charger()
 *
 * @param string $objet
 *     Type d'objet
 * @param int $id_objet
 *     Identifiant de l'objet
 * @param string $retour
 *     URL de redirection après le traitement
 * @param bool $editable
 *     Indique si le statut est éditable ou non.
 * @return array
 *     Tableau des erreurs
 */
function formulaires_instituer_objet_verifier_dist($objet, $id_objet, $retour = '', $editable = true) {
	$erreurs = [];
	// charger le contenu de l'objet
	// dont son champ statut
	$v = formulaires_editer_objet_charger($objet, $id_objet, 0, 0, '', '');

	if ($v['statut'] !== _request('statut_old')) {
		$erreurs['statut'] = _T('instituer_erreur_statut_a_change');
	} else {
		$table = table_objet_sql($objet);
		$desc = lister_tables_objets_sql($table);

		$publiable = true;
		if (
			isset($v['id_rubrique'])
			&& !autoriser('publierdans', 'rubrique', $v['id_rubrique'])
		) {
			$publiable = false;
		}
		$l = lister_statuts_proposes($desc, $publiable);
		$statut = _request('statut');
		if (
			!isset($l[$statut])
			|| !autoriser('instituer', $objet, $id_objet, '', ['statut' => $statut])
		) {
			$erreurs['statut'] = _T('instituer_erreur_statut_non_autorise');
		}
	}

	return $erreurs;
}

/**
 * Traitements du formulaire instituer objet
 *
 * @param string $objet
 *     Type d'objet
 * @param int $id_objet
 *     Identifiant de l'objet
 * @param string $retour
 *     URL de redirection après le traitement
 * @param bool $editable
 *     Indique si le statut est éditable ou non.
 * @return array
 *     Retour des traitements
 */
function formulaires_instituer_objet_traiter_dist($objet, $id_objet, $retour = '', $editable = true) {

	$c = ['statut' => _request('statut')];
	// si on a envoye une 'date_posterieure', l'enregistrer
	// todo dans le HTML
	if ($d = _request('date_posterieure')) {
		$c['date'] = $d;
	}


	include_spip('action/editer_objet');
	if ($err = objet_instituer($objet, $id_objet, $c)) {
		$res = ['message_erreur' => $err];
	} else {
		$res = ['message_ok' => _T('info_modification_enregistree')];
		if ($retour) {
			$res['redirect'] = $retour;
		}
		set_request('statut');
		set_request('date_posterieure');
	}

	return $res;
}
