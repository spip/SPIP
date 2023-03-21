<?php

/**
 * Filtres pour les sélecteurs d'objets
 *
 * @package SPIP\Core\Filtres\Selecteurs
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Fournit la liste des objets ayant un sélecteur
 *
 * Concrètement, va chercher tous les `formulaires/selecteur/hierarchie-{trucs}.html`
 * Ensuite on ajoute les parents obligatoires éventuels
 *
 * @uses find_all_in_path()
 *
 * @param array $includelist
 *     Liste d’inclusion décrivant les objets à lister
 * @param array $excludelist
 *     Liste d’exclusion décrivant les objets à ne pas lister
 * @return array
 *     Retourne un tableau de deux entrées listant les objets à lister et les objets sélectionnables
 *     - selectionner : tableau des objets que l'on pourra sélectionner (avec un +)
 *     - afficher : tableau des objets à afficher (mais pas forcément sélectionnables)
 */
function selecteur_lister_objets($includelist = [], $excludelist = []) {
	static $liste_selecteurs, $liste_parents;

	if (!$liste_selecteurs) {
		$liste_selecteurs = find_all_in_path('formulaires/selecteur/', 'hierarchie-[\w]+[.]html$');
	}
	$objets_selectionner = [];
	foreach ($liste_selecteurs as $fichier => $chemin) {
		$objets_selectionner[] = preg_replace('/^hierarchie-([\w]+)[.]html$/', '$1', (string) $fichier);
	}

	// S'il y a une whitelist on ne garde que ce qui est dedans
	if (!empty($includelist)) {
		$includelist = array_map('table_objet', $includelist);
		$objets_selectionner = array_intersect($objets_selectionner, $includelist);
	}
	// On supprime ce qui est dans la liste d’exclusion
	$excludelist = array_map('table_objet', $excludelist);
	// On enlève toujours la racine
	$excludelist[] = 'racine';
	$objets_selectionner = array_diff($objets_selectionner, $excludelist);

	// Ensuite on cherche ce qu'on doit afficher : au moins ceux qu'on peut sélectionner
	$objets_afficher = $objets_selectionner;

	// Il faut alors chercher d'éventuels parents obligatoires en plus :
	// lister-trucs-bidules.html => on doit afficher des "trucs" pour trouver des "bidules"
	if (!$liste_parents) {
		$liste_parents = find_all_in_path('formulaires/selecteur/', 'lister-[\w]+-[\w]+[.]html$');
	}
	foreach ($liste_parents as $fichier => $chemin) {
		preg_match('/^lister-([\w]+)-([\w]+)[.]html$/', (string) $fichier, $captures);
		$parent = $captures[1];
		$type = $captures[2];
		// Si le type fait partie de ce qu'on doit afficher alors on ajoute aussi le parent à l'affichage
		if (in_array($type, $objets_afficher)) {
			$objets_afficher[] = $parent;
		}
	}

	return [
		'selectionner' => array_unique($objets_selectionner),
		'afficher' => array_unique($objets_afficher),
	];
}

/**
 * Extrait des informations d'un tableau d'entrées `array("rubrique|9", "article|8", ...)`
 * ou une chaine brute `rubrique|9,article|8,...`
 *
 * Peut retourner un tableau de couples (objet => id_objet) ou la liste
 * des identifiants d'un objet précis si `$type` est fourni.
 *
 * @example
 *     `picker_selected(array('article|1', 'article|2', 'rubrique|5'))`
 *     retourne `array('article' => 1, 'article' => 2, 'rubrique' => 5)`
 * @example
 *     `picker_selected(array('article|1', 'article|2', 'rubrique|5'), 'article')`
 *     retourne `array(1, 2)`
 *
 * @filtre
 *
 * @param array|string $selected
 *     Liste des entrées : tableau ou chaine séparée par des virgules
 * @param string $type
 *     Type de valeur à recuperer tel que `rubrique`, `article`
 * @return array
 *     liste des couples (objets => id_objet) ou liste des identifiants d'un type d'objet.
 **/
function picker_selected($selected, $type = '') {
	$select = [];
	$type = preg_replace(',\W,', '', $type);

	if ($selected && !is_array($selected)) {
		$selected = explode(',', $selected);
	}

	if (is_array($selected)) {
		foreach ($selected as $value) {
			// Si c'est le bon format déjà
			if (preg_match('/^([\w]+)[|](\d+)$/', (string) $value, $captures)) {
				$objet = $captures[1];
				$id_objet = (int) $captures[2];

				// Si on cherche un type et que c'est le bon, on renvoit un tableau que d'identifiants
				if (is_string($type) && $type == $objet && ($id_objet || in_array($objet, ['racine', 'rubrique']))) {
					$select[] = $id_objet;
				} elseif (!$type && ($id_objet || in_array($objet, ['racine', 'rubrique']))) {
					$select[] = ['objet' => $objet, 'id_objet' => $id_objet];
				}
			}
		}
	}

	return $select;
}

/**
 * Récupère des informations sur un objet pour pouvoir l'ajouter aux éléments sélectionnés
 *
 * @uses typer_raccourci()
 *
 * @param string|int $ref
 *     Référence de l'objet à chercher sous forme raccourcie rub123 art123 ou meme 123 si pas d'ambiguité ou si un article
 * @param array|bool $rubriques_ou_objets
 *     Soit un booléen (pouvant être une chaîne vide aussi) indiquant que les rubriques sont sélectionnables
 *     soit un tableau complet des objets sélectionnables.
 * @param bool $articles
 *     Booléen indiquant si les articles sont sélectionnables
 */
function picker_identifie_id_rapide($ref, $rubriques_ou_objets = false, $articles = false) {
	include_spip('inc/json');
	include_spip('inc/lien');

	// On construit un tableau des objets sélectionnables suivant les paramètres
	$objets = [];
	if ($rubriques_ou_objets && is_array($rubriques_ou_objets)) {
		$objets = $rubriques_ou_objets;
	} else {
		if ($rubriques_ou_objets) {
			$objets[] = 'rubriques';
		}
		if ($articles) {
			$objets[] = 'articles';
		}
	}

	// si id numerique et un seul objet possible, pas d'ambiguite
	if (is_numeric($ref) && count($objets) === 1) {
		$type = reset($objets);
		$type = objet_type($type);
		$id = (int) $ref;
		$ref = $type . $ref;
	}
	else {
		// Si la référence ne correspond à rien, c'est fini
		if (!($match = typer_raccourci($ref))) {
			return json_export(false);
		}
		// Sinon on récupère les infos utiles
		[$type, , $id, , , , ] = array_pad($match, 7, null);

		// On regarde si le type trouvé fait partie des objets sélectionnables
		if (!in_array(table_objet($type), $objets)) {
			return json_export(false);
		}
	}

	// Maintenant que tout est bon, on cherche les informations sur cet objet
	include_spip('inc/filtres');
	if (!$titre = generer_objet_info($id, $type, 'titre')) {
		return json_export(false);
	}

	// On simplifie le texte
	$titre = attribut_html($titre);

	return json_export(['type' => $type, 'id' => "$type|$id", 'titre' => $titre]);
}

/**
 * Déterminer si une rubrique a des enfants à afficher ou non
 *
 * On test d'abord si la rubrique a des sous rubriques, et sinon on regarde
 * les autres types sélectionnables, puis on regarde si la rubrique contient
 * certains de ces objets
 *
 * @note
 *     Pour optimiser, la fonction calcule sa valeur sur toute la fratrie d'un coup,
 *     puisqu'elle est appellée N fois pour toutes les rubriques d'un même niveau
 *
 * @param int $id_rubrique
 *     Identifiant de la rubrique
 * @param array $types
 *     Liste de type d'objets. Si l'un de ces objet est présent dans la rubrique,
 *     alors cette rubrique est à afficher
 * @return string
 *     Comme le filtre `oui` : espace (` `) si rubrique à afficher, chaîne vide sinon.
 */
function test_enfants_rubrique($id_rubrique, $types = []) {
	static $has_child = [];

	if (!isset($has_child[$id_rubrique])) {
		$types = (is_array($types) ? array_filter($types) : []);

		// recuperer tous les freres et soeurs de la rubrique visee
		$id_parent = sql_getfetsel('id_parent', 'spip_rubriques', 'id_rubrique=' . (int) $id_rubrique);
		$fratrie = sql_allfetsel('id_rubrique', 'spip_rubriques', 'id_parent=' . (int) $id_parent);
		$fratrie = array_column($fratrie, 'id_rubrique');
		$has = sql_allfetsel('DISTINCT id_parent', 'spip_rubriques', sql_in('id_parent', $fratrie));
		$has = array_column($has, 'id_parent');
		$fratrie = array_diff($fratrie, $has);

		while (count($fratrie) && is_array($types) && count($types)) {
			$type = array_shift($types);
			$h = sql_allfetsel('DISTINCT id_rubrique', table_objet_sql($type), sql_in('id_rubrique', $fratrie));
			$h = array_column($h, 'id_rubrique');
			$has = [...$has, ...$h];
			$fratrie = array_diff($fratrie, $h);
		}

		if ($has !== []) {
			$has_child += array_combine($has, array_pad([], count($has), true));
		}
		if ($fratrie !== []) {
			$has_child += array_combine($fratrie, array_pad([], count($fratrie), false));
		}
	}

	return $has_child[$id_rubrique] ? ' ' : '';
}
