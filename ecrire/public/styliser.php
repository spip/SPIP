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


if (!defined('_ECRIRE_INC_VERSION')) return;

// Ce fichier doit imperativement definir la fonction ci-dessous:

/**
 * Determiner le squelette qui sera utilise pour rendre la page ou le bloc
 * a partir de $fond et du $contetxe
 * 
 * Actuellement tous les squelettes se terminent par .html
 * pour des raisons historiques, ce qui est trompeur
 *
 * @param string $fond
 * @param array $contexte
 * @param string $lang
 * @param string $connect
 * @param string $ext
 * @return array
 *
 * http://doc.spip.org/@public_styliser_dist
 */
function public_styliser_dist($fond, $contexte, $lang='', $connect='') {

	// s'assurer que le fond est licite
	// car il peut etre construit a partir d'une variable d'environnement
	if (strpos($fond,"../")!==false OR strncmp($fond,'/',1)==0)
		$fond = "404";
  
	// Choisir entre $fond-dist.html, $fond=7.html, etc?
	$id_rubrique = 0;
	// Chercher le fond qui va servir de squelette
	if ($r = quete_rubrique_fond($contexte))
		list($id_rubrique, $lang) = $r;

	// trouver un squelette du nom demande
	// ne rien dire si on ne trouve pas, 
	// c'est l'appelant qui sait comment gerer la situation
	// ou les plugins qui feront mieux dans le pipeline
	$squelette = trouver_fond($fond,"",true);
	$ext = $squelette['extension'];

	$flux = array(
		'args' => array(
			'id_rubrique' => $id_rubrique,
			'ext' => $ext,
			'fond' => $fond,
			'lang' => $lang,
			'contexte' => $contexte, // le style d'un objet peut dependre de lui meme
			'connect' => $connect
		),
		'data' => $squelette['fond'],
	);

	if (test_espace_prive() OR defined('_ZPIP')) {
		$styliser_par_z = charger_fonction('styliser_par_z','public');
		$flux = $styliser_par_z($flux);
	}

	$flux = styliser_par_objets($flux);

	// pipeline styliser
	$squelette = pipeline('styliser', $flux);

	return array($squelette, $ext, $ext, "$squelette.$ext");
}

function styliser_par_objets($flux){
	if (test_espace_prive()
		AND !$squelette = $flux['data']
	  AND strncmp($flux['args']['fond'],'prive/objets/',13)==0
	  AND $echaffauder = charger_fonction('echaffauder','prive',true)) {
		if (strncmp($flux['args']['fond'],'prive/objets/liste/',19)==0){
			$table = table_objet(substr($flux['args']['fond'],19));
			$table_sql = table_objet_sql($table);
			$objets = lister_tables_objets_sql();
			if (isset($objets[$table_sql]))
				$flux['data'] = $echaffauder($table,$table,$table_sql,"prive/objets/liste/objets",$flux['args']['ext']);
		}
		if (strncmp($flux['args']['fond'],'prive/objets/contenu/',21)==0){
			$type = substr($flux['args']['fond'],21);
			$table = table_objet($type);
			$table_sql = table_objet_sql($table);
			$objets = lister_tables_objets_sql();
			if (isset($objets[$table_sql]))
				$flux['data'] = $echaffauder($type,$table,$table_sql,"prive/objets/contenu/objet",$flux['args']['ext']);
		}
	}
	return $flux;
}

/**
 * Options de recherche de squelette par le styliseur, appele par le pipeline 'styliser' :
 * Squelette par rubrique squelette-XX.html ou squelette=XX.html
 * 
 * @param <type> $flux
 * @return <type>
 */
function styliser_par_rubrique($flux) {

	// uniquement si un squelette a ete trouve
	if ($squelette = $flux['data']) {
		$ext = $flux['args']['ext'];

		// On selectionne, dans l'ordre :
		// fond=10
		if ($id_rubrique = $flux['args']['id_rubrique']) {
			$f = "$squelette=$id_rubrique";
			if (@file_exists("$f.$ext"))
				$squelette = $f;
			else {
				// fond-10 fond-<rubriques parentes>
				do {
					$f = "$squelette-$id_rubrique";
					if (@file_exists("$f.$ext")) {
						$squelette = $f;
						break;
					}
				} while ($id_rubrique = quete_parent($id_rubrique));
			}
			// sauver le squelette
			$flux['data'] = $squelette;
		}		
	}
	
	return $flux;
}

/**
 * Options de recherche de squelette par le styliseur, appele par le pipeline 'styliser' :
 * Squelette par langue squelette.en.html
 *
 * @param array $flux
 * @return array
 */
function styliser_par_langue($flux) {

	// uniquement si un squelette a ete trouve
	if ($squelette = $flux['data']) {
		$ext = $flux['args']['ext'];

		// Affiner par lang
		if ($lang = $flux['args']['lang']) {
			$l = lang_select($lang);
			$f = "$squelette.".$GLOBALS['spip_lang'];
			if ($l) lang_select();
			if (@file_exists("$f.$ext")) {
				// sauver le squelette
				$flux['data'] = $f;
			}
		}
	}
	
	return $flux;
}

/**
 * Calcul de la rubrique associee a la requete
 * (selection de squelette specifique par id_rubrique & lang)
 *
 * attention, on repete cela a chaque inclusion,
 * on optimise donc pour ne faire la recherche qu'une fois
 * par contexte semblable du point de vue des id_xx
 *
 * http://doc.spip.org/@quete_rubrique_fond
 *
 * @staticvar array $liste_objets
 * @param array $contexte
 * @return array
 */
function quete_rubrique_fond($contexte) {
	static $liste_objets = null;
	static $quete = array();
	if (!$liste_objets) {
		include_spip('inc/urls');
		$l = urls_liste_objets(false);
		// placer la rubrique en tete des objets
		$l = array_diff($l,array('rubrique'));
		array_unshift($l, 'rubrique');
		foreach($l as $objet){
			$liste_objets[id_table_objet($objet)] = $objet;
		}
	}
	$c = array_intersect_key($contexte,$liste_objets);
	if (!count($c)) return false;

	$c = array_map(intval,$c);
	$s = serialize($c);
	if (isset($quete[$s]))
		return $quete[$s];

	foreach($c as $_id=>$id) {
		if ($id
		  AND $row = quete_parent_lang(table_objet_sql($liste_objets[$_id]),$id)) {
			$lang = isset($row['lang']) ? $row['lang'] : '';
			return $quete[$s] = array ($id, $lang);
		}
	}
	return $quete[$s] = false;
}
?>
