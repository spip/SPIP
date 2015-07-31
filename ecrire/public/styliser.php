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
	static $styliser_par_z;

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
		if (!$styliser_par_z)
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
	  AND $echafauder = charger_fonction('echafauder','prive',true)) {
		if (strncmp($flux['args']['fond'],'prive/objets/liste/',19)==0){
			$table = table_objet(substr($flux['args']['fond'],19));
			$table_sql = table_objet_sql($table);
			$objets = lister_tables_objets_sql();
			if (isset($objets[$table_sql]))
				$flux['data'] = $echafauder($table,$table,$table_sql,"prive/objets/liste/objets",$flux['args']['ext']);
		}
		if (strncmp($flux['args']['fond'],'prive/objets/contenu/',21)==0){
			$type = substr($flux['args']['fond'],21);
			$table = table_objet($type);
			$table_sql = table_objet_sql($table);
			$objets = lister_tables_objets_sql();
			if (isset($objets[$table_sql]))
				$flux['data'] = $echafauder($type,$table,$table_sql,"prive/objets/contenu/objet",$flux['args']['ext']);
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
	if (is_null($liste_objets)) {
		$liste_objets = array();
		include_spip('inc/urls');
		include_spip('public/quete');
		$l = urls_liste_objets(false);
		// placer la rubrique en tete des objets
		$l = array_diff($l,array('rubrique'));
		array_unshift($l, 'rubrique');
		foreach($l as $objet){
			$id = id_table_objet($objet);
			if (!isset($liste_objets[$id]))
				$liste_objets[$id] = objet_type($objet,false);
		}
	}
	$c = array_intersect_key($contexte,$liste_objets);
	if (!count($c)) return false;

	$c = array_map('intval',$c);
	$s = serialize($c);
	if (isset($quete[$s]))
		return $quete[$s];

	if (isset($c['id_rubrique']) AND $r = $c['id_rubrique']){
		unset($c['id_rubrique']);
		$c = array('id_rubrique'=>$r) + $c;
	}

	foreach($c as $_id=>$id) {
		if ($id
		  AND $row = quete_parent_lang(table_objet_sql($liste_objets[$_id]),$id)) {
			$lang = isset($row['lang']) ? $row['lang'] : '';
			if ($_id=='id_rubrique' OR (isset($row['id_rubrique']) AND $id=$row['id_rubrique']))
				return $quete[$s] = array ($id, $lang);
		}
	}
	return $quete[$s] = false;
}
?>
