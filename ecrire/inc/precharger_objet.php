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
 * Préchargement les formulaires d'édition d'objets, notament pour les traductions
 *
 * @package SPIP\Core\Objets
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/autoriser'); // necessaire si appel de l'espace public


/**
 * Retourne les valeurs à charger pour un formulaire d'édition d'un objet
 *
 * Lors d'une création, certains champs peuvent être préremplis
 * (c'est le cas des traductions)
 *
 * @param string $type
 *     Type d'objet (article, breve...)
 * @param string|int $id_objet
 *     Identifiant de l'objet, ou "new" pour une création
 * @param int $id_rubrique
 *     Identifiant éventuel de la rubrique parente
 * @param int $lier_trad
 *     Identifiant éventuel de la traduction de référence
 * @param string $champ_titre
 *     Nom de la colonne SQL de l'objet donnant le titre : pas vraiment idéal !
 *     On devrait pouvoir le savoir dans la déclaration de l'objet
 * @return array
 *     Couples clés / valeurs des champs du formulaire à charger.
 **/
function precharger_objet($type, $id_objet, $id_rubrique = 0, $lier_trad = 0, $champ_titre = 'titre') {

	$row = [];
	$table = table_objet_sql($type);
	$_id_objet = id_table_objet($table);

	// si l'objet existe deja, on retourne simplement ses valeurs
	if (is_numeric($id_objet)) {
		return sql_fetsel('*', $table, "$_id_objet=" . (int) $id_objet);
	}

	// ici, on demande une creation.
	// on prerempli certains elements : les champs si traduction,
	// les id_rubrique et id_secteur si l'objet a ces champs
	$desc = lister_tables_objets_sql($table);
	# il faudrait calculer $champ_titre ici
	$is_rubrique = isset($desc['field']['id_rubrique']);
	$is_secteur = isset($desc['field']['id_secteur']);

	// si demande de traduction
	// on recupere les valeurs de la traduction
	if ($lier_trad) {
		if ($select = charger_fonction('precharger_traduction_' . $type, 'inc', true)) {
			$row = $select($id_objet, $id_rubrique, $lier_trad);
		} else {
			$row = precharger_traduction_objet($type, $id_objet, $id_rubrique, $lier_trad, $champ_titre);
		}
	} else {
		$row[$champ_titre] = '';
		if ($is_rubrique) {
			$row['id_rubrique'] = $id_rubrique;
		}
	}

	// calcul de la rubrique
	# note : comment faire pour des traductions sur l'objet rubriques ?
	// appel du script a la racine, faut choisir
	// admin restreint ==> sa premiere rubrique
	// autre ==> la derniere rubrique cree
	if ($is_rubrique && !$row['id_rubrique']) {
		if ($GLOBALS['connect_id_rubrique']) {
			$row['id_rubrique'] = $id_rubrique = current($GLOBALS['connect_id_rubrique']);
		} else {
			$row_rub = sql_fetsel('id_rubrique', 'spip_rubriques', '', '', 'id_rubrique DESC', 1);
			$row['id_rubrique'] = $id_rubrique = $row_rub['id_rubrique'];
		}
		if (!autoriser('creerarticledans', 'rubrique', $row['id_rubrique'])) {
			// manque de chance, la rubrique n'est pas autorisee, on cherche un des secteurs autorises
			$res = sql_select('id_rubrique', 'spip_rubriques', 'id_parent=0');
			while (!autoriser('creerarticledans', 'rubrique', $row['id_rubrique']) && $row_rub = sql_fetch($res)) {
				$row['id_rubrique'] = $row_rub['id_rubrique'];
			}
		}
	}

	// recuperer le secteur, pour affecter les bons champs extras
	if ($id_rubrique && $is_secteur && !$row['id_secteur']) {
		$row_rub = sql_getfetsel('id_secteur', 'spip_rubriques', 'id_rubrique=' . sql_quote($id_rubrique));
		$row['id_secteur'] = $row_rub;
	}

	return $row;
}


/**
 * Récupère les valeurs d'une traduction de référence pour la création
 * d'un objet (préremplissage du formulaire).
 *
 * @param string $type
 *     Type d'objet (article, breve...)
 * @param string|int $id_objet
 *     Identifiant de l'objet, ou "new" pour une création
 * @param int $id_rubrique
 *     Identifiant éventuel de la rubrique parente
 * @param int $lier_trad
 *     Identifiant éventuel de la traduction de référence
 * @param string $champ_titre
 *     Nom de la colonne SQL de l'objet donnant le titre
 * @return array
 *     Couples clés / valeurs des champs du formulaire à charger
 **/
function precharger_traduction_objet($type, $id_objet, $id_rubrique = 0, $lier_trad = 0, $champ_titre = 'titre') {
	$table = table_objet_sql($type);
	$_id_objet = id_table_objet($table);

	// Recuperer les donnees de l'objet original
	$row = sql_fetsel('*', $table, "$_id_objet=" . (int) $lier_trad);
	if ($row) {
		include_spip('inc/filtres');
		$row[$champ_titre] = filtrer_entites(objet_T($type, 'info_nouvelle_traduction')) . ' ' . $row[$champ_titre];
	} else {
		$row = [];
	}

	// on met l'objet dans une rubrique si l'objet le peut
	$desc = lister_tables_objets_sql($table);
	$is_rubrique = isset($desc['field']['id_rubrique']);

	if ($is_rubrique) {
		$langues_dispo = explode(',', (string) $GLOBALS['meta']['langues_multilingue']);
		// si le redacteur utilise une autre langue que celle de la source, on suppose que c'est pour traduire dans sa langue
		if (in_array($GLOBALS['spip_lang'], $langues_dispo) && $GLOBALS['spip_lang'] !== $row['lang']) {
			$row['lang'] = $GLOBALS['spip_lang'];
		}
		// sinon si il y a seulement 2 langues dispos, on bascule sur l'"autre"
		elseif (count($langues_dispo) == 2) {
			$autre_langue = array_diff($langues_dispo, [$row['lang']]);
			if (count($autre_langue) == 1) {
				$row['lang'] = reset($autre_langue);
			}
		}
		else {
			$row['lang'] = 'en';
		}

		if ($id_rubrique) {
			$row['id_rubrique'] = $id_rubrique;

			return $row;
		}
		$id_rubrique = $row['id_rubrique'];


		// Regler la langue, si possible, sur celle du redacteur
		// Cela implique souvent de choisir une rubrique ou un secteur
		if (in_array($GLOBALS['spip_lang'], $langues_dispo)) {
			// Si le menu de langues est autorise sur l'objet,
			// on peut changer la langue quelle que soit la rubrique
			// donc on reste dans la meme rubrique
			if (in_array($table, explode(',', (string) $GLOBALS['meta']['multi_objets']))) {
				$row['id_rubrique'] = $row['id_rubrique']; # explicite :-)

				// Sinon, chercher la rubrique la plus adaptee pour
				// accueillir l'objet dans la langue du traducteur
			} elseif ($is_rubrique && $GLOBALS['meta']['multi_rubriques'] == 'oui') {
				if ($GLOBALS['meta']['multi_secteurs'] == 'oui') {
					$id_parent = 0;
				} else {
					// on cherche une rubrique soeur dans la bonne langue
					$row_rub = sql_fetsel('id_parent', 'spip_rubriques', 'id_rubrique=' . (int) $id_rubrique);
					$id_parent = $row_rub['id_parent'];
				}

				$row_rub = sql_fetsel(
					'id_rubrique',
					'spip_rubriques',
					"lang='" . $GLOBALS['spip_lang'] . "' AND id_parent=" . (int) $id_parent
				);
				if ($row_rub) {
					$row['id_rubrique'] = $row_rub['id_rubrique'];
				}
			}
		}
	}

	return $row;
}
