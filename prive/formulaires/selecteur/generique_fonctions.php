<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/*
 * Fournit la liste des objets ayant un sélecteur
 * Concrètement, va chercher tous les formulaires/selecteur/hierarchie-trucs.html
 * Ensuite on ajoute les parents obligatoires éventuels
 *
 * @param array $whitelist Liste blanche décrivant les objets à lister
 * @param array $blacklist Liste noire décrivant les objets à ne pas lister
 * @return array Retourne un tableau de deux entrées listant les objets à lister et les objets sélectionnables
 *    selectionner : tableau des objets que l'on pourra sélectionner (avec un +)
 *    afficher : tableau des objets à afficher (mais pas forcément sélectionnables)
 */
function selecteur_lister_objets($whitelist=array(), $blacklist=array()){
	static $liste_selecteurs, $liste_parents;
	
	if (!$liste_selecteurs){
		$liste_selecteurs = find_all_in_path('formulaires/selecteur/', 'hierarchie-[\w]+[.]html$');
	}
	$objets_selectionner = array();
	foreach ($liste_selecteurs as $fichier=>$chemin){
		$objets_selectionner[] = preg_replace('/^hierarchie-([\w]+)[.]html$/', '$1', $fichier);
	}
	
	// S'il y a une whitelist on ne garde que ce qui est dedans
	if (!empty($whitelist)){
		$whitelist = array_map('table_objet', $whitelist);
		$objets_selectionner = array_intersect($objets_selectionner, $whitelist);
	}
	// On supprime ce qui est dans la blacklist
	$blacklist = array_map('table_objet', $blacklist);
	// On enlève toujours la racine
	$blacklist[] = 'racine';
	$objets_selectionner = array_diff($objets_selectionner, $blacklist);
	
	// Ensuite on cherche ce qu'on doit afficher : au moins ceux qu'on peut sélectionner
	$objets_afficher = $objets_selectionner;
	
	// Il faut alors chercher d'éventuels parents obligatoires en plus :
	// lister-trucs-bidules.html => on doit afficher des "trucs" pour trouver des "bidules"
	if (!$liste_parents){
		$liste_parents = find_all_in_path('formulaires/selecteur/', 'lister-[\w]+-[\w]+[.]html$');
	}
	foreach ($liste_parents as $fichier=>$chemin){
		preg_match('/^lister-([\w]+)-([\w]+)[.]html$/', $fichier, $captures);
		$parent = $captures[1];
		$type = $captures[2];
		// Si le type fait partie de ce qu'on doit afficher alors on ajoute aussi le parent à l'affichage
		if (in_array($type, $objets_afficher)){
			$objets_afficher[] = $parent;
		}
	}
	
	$objets =  array(
		'selectionner' => array_unique($objets_selectionner),
		'afficher' => array_unique($objets_afficher),
	);
	
	return $objets;
}

/**
 * Transformer un tableau d'entrees array("rubrique|9","article|8",...)
 * en un tableau contenant uniquement les identifiants d'un type donne.
 * Accepte aussi que les valeurs d'entrees soient une chaine brute
 * "rubrique|9,article|8,..."
 *
 * @param array/string $selected liste des entrees : tableau ou chaine separee par des virgules
 * @param string $type type de valeur a recuperer ('rubrique', 'article')
 *
 * @return array liste des identifiants trouves.
**/
function picker_selected($selected, $type=''){
	$select = array();
	$type = preg_replace(',\W,','',$type);

	if ($selected and !is_array($selected))
		$selected = explode(',', $selected);

	if (is_array($selected))
		foreach($selected as $value){
			// Si c'est le bon format déjà
			if (preg_match('/^([\w]+)[|]([0-9]+)$/', $value, $captures)){
				$objet = $captures[1];
				$id_objet = intval($captures[2]);
				
				// Si on cherche un type et que c'est le bon, on renvoit un tableau que d'identifiants
				if (is_string($type) AND $type == $objet AND ($id_objet OR in_array($objet, array('racine', 'rubrique')))){
					$select[] = $id_objet;
				}
				elseif(!$type AND ($id_objet OR in_array($objet, array('racine', 'rubrique')))){
					$select[] = array('objet' => $objet, 'id_objet' => $id_objet);
				}
			}
		}
	return $select;
}

/*
 * Récupère des informations sur un objet pour pouvoir l'ajouter aux éléments sélectionnés
 *
 * @param string $ref Référence de l'objet à chercher, de la forme "type|id", par exemple "rubrique|123".
 * @param mixed $rubriques_ou_objets Soit un booléen (pouvant être une chaîne vide aussi) indiquant que les rubriques sont sélectionnables soit un tableau complet des objets sélectionnables.
 * @param bool $articles Booléen indiquant si les articles sont sélectionnables
 */
function picker_identifie_id_rapide($ref, $rubriques_ou_objets=false, $articles=false){
	include_spip('inc/json');
	include_spip('inc/lien');
	
	// On construit un tableau des objets sélectionnables suivant les paramètres
	$objets = array();
	if ($rubriques_ou_objets and is_array($rubriques_ou_objets)){
		$objets = $rubriques_ou_objets;
	}
	else{
		if ($rubriques_ou_objets){ $objets[] = 'rubriques'; }
		if ($articles){ $objets[] = 'articles'; }
	}
	
	// Si la référence ne correspond à rien, c'est fini
	if (!($match = typer_raccourci($ref))){
		return json_export(false);
	}
	// Sinon on récupère les infos utiles
	@list($type,,$id,,,,) = $match;
	
	// On regarde si le type trouvé fait partie des objets sélectionnables
	if (!in_array(table_objet($type), $objets)){
		return json_export(false);
	}
	
	// Maintenant que tout est bon, on cherche les informations sur cet objet
	include_spip('inc/filtres');
	if (!$titre = generer_info_entite($id, $type, 'titre')){
		return json_export(false);
	}
	
	// On simplifie le texte
	$titre = attribut_html($titre);
	
	return json_export(array('type' => $type, 'id' => "$type|$id", 'titre' => $titre));
}

/**
 * Determiner si une rubrique a des enfants a afficher ou non
 * on test d'abord si la rubrique a des sous rubriques, et sinon on regarde les autrs types selectionnables
 * et on regarde la rubrique contient certains de ces objets
 *
 * Pour optimiser, la fonction calcule sa valeur sur toute la fratrie d'un coup, puisqu'elle est appellee N fois
 * pour toutes les rubriques d'un meme niveau
 *
 * @param $id_rubrique
 * @param array $types
 * @return string
 */
function test_enfants_rubrique($id_rubrique,$types=array()){
	static $has_child = array();
	if (!isset($has_child[$id_rubrique])){
		$types = (is_array($types)?array_filter($types):array());
		// recuperer tous les freres et soeurs de la rubrique visee
		$id_parent = sql_getfetsel('id_parent','spip_rubriques','id_rubrique='.intval($id_rubrique));
		$fratrie = sql_allfetsel('id_rubrique','spip_rubriques','id_parent='.intval($id_parent));
		$fratrie = array_map('reset',$fratrie);
		$has = sql_allfetsel("DISTINCT id_parent","spip_rubriques",sql_in('id_parent',$fratrie));
		$has = array_map('reset',$has);
		$fratrie = array_diff($fratrie,$has);
		while (count($fratrie) AND is_array($types) AND count($types)){
			$type = array_shift($types);
			$h = sql_allfetsel("DISTINCT id_rubrique",table_objet_sql($type),sql_in('id_rubrique',$fratrie));
			$h = array_map('reset',$h);
			$has = array_merge($has,$h);
			$fratrie = array_diff($fratrie,$h);
		}
		if (count($has))
			$has_child = $has_child + array_combine($has,array_pad(array(),count($has),true));
		if (count($fratrie))
			$has_child = $has_child + array_combine($fratrie,array_pad(array(),count($fratrie),false));
	}

	return $has_child[$id_rubrique]?' ':'';
}

?>
