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

// Actuellement tous les squelettes se terminent par .html
// pour des raisons historiques, ce qui est trompeur

// http://doc.spip.org/@public_styliser_dist
function public_styliser_dist($fond, $contexte, $lang='', $connect='', $ext='html') {

	// s'assurer que le fond et licite
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
	$base = find_in_path("$fond.$ext");
	
	// supprimer le ".html" pour pouvoir affiner par id_rubrique ou par langue
	$squelette = substr($base, 0, - strlen(".$ext"));

	// pipeline styliser
	$squelette = pipeline('styliser', array(
		'args' => array(
			'id_rubrique' => $id_rubrique,
			'ext' => $ext,
			'fond' => $fond,
			'lang' => $lang,
			'contexte' => $contexte, // le style d'un objet peut dependre de lui meme
			'connect' => $connect
		),
		'data' => $squelette,
	));

	return array($squelette, $ext, $ext, "$squelette.$ext");
}


/*
 * Options de recherche de squelette par le styliseur, appele par le pipeline 'styliser' :
 * Squelette par rubrique squelette-XX.html ou squelette=XX.html
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

/*
 * Options de recherche de squelette par le styliseur, appele par le pipeline 'styliser' :
 * Squelette par langue squelette.en.html
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

// Calcul de la rubrique associee a la requete
// (selection de squelette specifique par id_rubrique & lang)

// http://doc.spip.org/@quete_rubrique_fond
function quete_rubrique_fond($contexte) {

	if (isset($contexte['id_rubrique'])
	AND $id = intval($contexte['id_rubrique'])
	AND $row = quete_parent_lang('spip_rubriques',$id)) {
		$lang = isset($row['lang']) ? $row['lang'] : '';
		return array ($id, $lang);
	}

	if (isset($contexte['id_breve'])
	AND $id = intval($contexte['id_breve'])
	AND $row = quete_parent_lang('spip_breves',$id)
	AND $id_rubrique_fond = $row['id_rubrique']) {
		$lang = isset($row['lang']) ? $row['lang'] : '';
		return array($id_rubrique_fond, $lang);
	}

	if (isset($contexte['id_syndic'])
	AND $id = intval($contexte['id_syndic'])
	AND $row = quete_parent_lang('spip_syndic',$id)
	AND $id_rubrique_fond = $row['id_rubrique']
	AND $row = quete_parent_lang('spip_rubriques',$id_rubrique_fond)) {
		$lang = isset($row['lang']) ? $row['lang'] : '';
		return array($id_rubrique_fond, $lang);
	}

	if (isset($contexte['id_article'])
	AND $id = intval($contexte['id_article'])
	AND $row = quete_parent_lang('spip_articles',$id)
	AND $id_rubrique_fond = $row['id_rubrique']) {
		$lang = isset($row['lang']) ? $row['lang'] : '';
		return array($id_rubrique_fond, $lang);
	}
}
?>
