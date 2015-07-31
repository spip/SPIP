<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/autoriser'); // necessaire si appel de l'espace public



/**
 * Retourne les valeurs a charger pour un formulaire d'edition d'un objet
 * Lors d'une creation, certains champs peuvent être preremplis (c'est le cas des traductions) 
 *
 * @param string $type Type d'objet (article,breve...)
 * @param string|int $id_objet, Identifiant de l'objet, ou "new" pour une creation
 * @param int $id_rubrique, identifiant eventuel de la rubrique parente
 * @param int $lier_trad, identifiant eventuel de la traduction de reference
 * @param string $champ_titre, nom de la colonne SQL de l'objet donnant le titre :
 *                             pas ideal ? on devrait pouvoir le savoir
 *                             dans la declaration de l'objet
 * @return array couples cles / valeurs des champs du formulaire à charger.
**/
function precharger_objet($type, $id_objet, $id_rubrique=0, $lier_trad=0, $champ_titre = 'titre') {
	global $connect_id_rubrique, $spip_lang;
	
	$table = table_objet_sql($type);
	$_id_objet = id_table_objet($table);

	// si l'objet existe deja, on retourne simplement ses valeurs
	if (is_numeric($id_objet))
		return sql_fetsel("*", $table, "$_id_objet=$id_objet");

	// ici, on demande une creation.
	// on prerempli certains elements : les champs si traduction,
	// les id_rubrique et id_secteur si l'objet a ces champs
	$desc = lister_tables_objets_sql($table);
	# il faudrait calculer $champ_titre ici
	$is_rubrique = isset($desc['field']['id_rubrique']);
	$is_secteur  = isset($desc['field']['id_secteur']);

	// si demande de traduction
	// on recupere les valeurs de la traduction
	if ($lier_trad){
		if ($select = charger_fonction("precharger_traduction_" . $type,'inc',true))
			$row = $select($id_objet, $id_rubrique, $lier_trad);
		else
			$row = precharger_traduction_objet($type, $id_objet, $id_rubrique, $lier_trad, $champ_titre);
	} else {
		$row[$champ_titre] = '';
		if ($is_rubrique) {
			$row['id_rubrique'] = $id_rubrique;
		}
	}

	// calcul de la rubrique
	# note : comment faire pour des traductions sur l'objet rubriques ?
	if ($is_rubrique) {
		// appel du script a la racine, faut choisir 
		// admin restreint ==> sa premiere rubrique
		// autre ==> la derniere rubrique cree
		if (!$row['id_rubrique']) {
			if ($connect_id_rubrique)
				$row['id_rubrique'] = $id_rubrique = current($connect_id_rubrique); 
			else {
				$row_rub = sql_fetsel("id_rubrique", "spip_rubriques", "", "", "id_rubrique DESC", 1);
				$row['id_rubrique'] = $id_rubrique = $row_rub['id_rubrique'];
			}
			if (!autoriser('creerarticledans','rubrique',$row['id_rubrique'] )){
				// manque de chance, la rubrique n'est pas autorisee, on cherche un des secteurs autorises
				$res = sql_select("id_rubrique", "spip_rubriques", "id_parent=0");
				while (!autoriser('creerarticledans','rubrique',$row['id_rubrique'] ) && $row_rub = sql_fetch($res)){
					$row['id_rubrique'] = $row_rub['id_rubrique'];
				}
			}
		}
	}
	
	// recuperer le secteur, pour affecter les bons champs extras
	if ($id_rubrique and $is_secteur) {
		if (!$row['id_secteur']) {
			$row_rub = sql_getfetsel("id_secteur", "spip_rubriques", "id_rubrique=" . sql_quote($id_rubrique));
			$row['id_secteur'] = $row_rub;
		}
	}

	return $row;
}


/**
 * Recupere les valeurs d'une traduction de reference pour la creation
 * d'un objet (preremplissage du formulaire). 
 *
 * @param string $type Type d'objet (article,breve...)
 * @param string|int $id_objet, Identifiant de l'objet, ou "new" pour une creation
 * @param int $id_rubrique, identifiant eventuel de la rubrique parente
 * @param int $lier_trad, identifiant eventuel de la traduction de reference
 * @param string $champ_titre, nom de la colonne SQL de l'objet donnant le titre
 * 
 * @return array couples cles / valeurs des champs du formulaire à charger
**/
function precharger_traduction_objet($type, $id_objet, $id_rubrique=0, $lier_trad=0, $champ_titre = 'titre') {
	$table = table_objet_sql($type);
	$_id_objet = id_table_objet($table);

	// Recuperer les donnees de l'objet original
	$row = sql_fetsel("*", $table, "$_id_objet=$lier_trad");
	if ($row) {
		$row[$champ_titre] = filtrer_entites(_T('info_nouvelle_traduction')).' '.$row[$champ_titre];
	} else {
		$row = array();
	}

	// on met l'objet dans une rubrique si l'objet le peut
	$desc = lister_tables_objets_sql($table);
	$is_rubrique = isset($desc['field']['id_rubrique']);
	
	if ($is_rubrique) {
		if ($id_rubrique) {
			$row['id_rubrique'] = $id_rubrique;
			return $row;
		}
		$id_rubrique = $row['id_rubrique'];
	

		// Regler la langue, si possible, sur celle du redacteur
		// Cela implique souvent de choisir une rubrique ou un secteur
		if (in_array($GLOBALS['spip_lang'],
		explode(',', $GLOBALS['meta']['langues_multilingue']))) {

			// Si le menu de langues est autorise sur l'objet,
			// on peut changer la langue quelle que soit la rubrique
			// donc on reste dans la meme rubrique
			if (in_array($table, explode(',',$GLOBALS['meta']['multi_objets']))) {
				$row['id_rubrique'] = $row['id_rubrique']; # explicite :-)

			// Sinon, chercher la rubrique la plus adaptee pour
			// accueillir l'objet dans la langue du traducteur
			} elseif ($is_rubrique and $GLOBALS['meta']['multi_rubriques'] == 'oui') {
				if ($GLOBALS['meta']['multi_secteurs'] == 'oui') {
					$id_parent = 0;
				} else {
					// on cherche une rubrique soeur dans la bonne langue
					$row_rub = sql_fetsel("id_parent", "spip_rubriques", "id_rubrique=$id_rubrique");
					$id_parent = $row_rub['id_parent'];
				}
				
				$row_rub = sql_fetsel("id_rubrique", "spip_rubriques", "lang='".$GLOBALS['spip_lang']."' AND id_parent=$id_parent");
				if ($row_rub)
					$row['id_rubrique'] = $row_rub['id_rubrique'];	
			}
		}
	}
	return $row;
}



?>
