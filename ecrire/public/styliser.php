<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;

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

	// pipeline styliser
	$squelette = pipeline('styliser', $flux);

	return array($squelette, $ext, $ext, "$squelette.$ext");
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
 * @staticvar array $liste_objets
 * @param array $contexte
 * @return array
 *
 * http://doc.spip.org/@quete_rubrique_fond
 */
function quete_rubrique_fond($contexte) {
	static $liste_objets = null;
	if (!$liste_objets) {
		include_spip('inc/urls');
		$liste_objets = urls_liste_objets(false);
		// placer la rubrique en tete des objets
		$liste_objets = array_diff($liste_objets,array('rubrique'));
		array_unshift($liste_objets, 'rubrique');
	}

	foreach($liste_objets as $objet) {
		$_id = id_table_objet($objet);
		if (isset($contexte[$_id])
		AND $id = intval($contexte[$_id])
		AND $row = quete_parent_lang(table_objet_sql($objet),$id)) {
			$lang = isset($row['lang']) ? $row['lang'] : '';
			return array ($id, $lang);
		}
	}
}
?>
