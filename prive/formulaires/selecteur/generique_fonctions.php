<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/*
 * Fournit la liste des objets ayant un sélecteur
 * Concrètement, va chercher tous les formulaires/selecteur/hierarchie-truc.html
 * Ensuite on ajoute les parents obligatoires éventuels
 */
function selecteur_lister_objets($whitelist=array(), $blacklist=array()){
	$liste = find_all_in_path('formulaires/selecteur/', 'hierarchie-[\w]+[.]html$');
	$objets_selectionner = array();
	foreach ($liste as $fichier=>$chemin){
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
	// Il faut alors chercher d'éventuels parents obligatoires :
	// lister-trucs-bidules.html => on doit afficher des "trucs" pour trouver des "bidules"
	$liste = find_all_in_path('formulaires/selecteur/', 'lister-[\w]+-[\w]+[.]html$');
	foreach ($liste as $fichier=>$chemin){
		preg_match('/^lister-([\w]+)-([\w]+)[.]html$/', $fichier, $captures);
		$parent = $captures[1];
		$type = $captures[2];
		// Si le type fait partie de ce qu'on veut sélectionner alors on ajoute le parent à l'affichage
		if (in_array($type, $objets_selectionner)){
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
				if (is_string($type) and $type == $objet and $id_objet){
					$select[] = $id_objet;
				}
				elseif(!$type){
					$select[] = array('objet' => $objet, 'id_objet' => $id_objet);
				}
			}
		}
	return $select;
}

function picker_identifie_id_rapide($ref,$rubriques=0,$articles=0){
	include_spip("inc/json");
	include_spip("inc/lien");
	if (!($match = typer_raccourci($ref)))
		return json_export(false);
	@list($type,,$id,,,,) = $match;
	if (!in_array($type,array($rubriques?'rubrique':'x',$articles?'article':'x')))
		return json_export(false);
	$table_sql = table_objet_sql($type);
	$id_table_objet = id_table_objet($type);
	if (!$titre = sql_getfetsel('titre',$table_sql,"$id_table_objet=".intval($id)))
		return json_export(false);
	$titre = attribut_html(extraire_multi($titre));
	return json_export(array('type'=>$type,'id'=>"$type|$id",'titre'=>$titre));
}

?>
