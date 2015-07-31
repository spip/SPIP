<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;

// Ce fichier doit imperativement definir la fonction ci-dessous:

// Actuellement tous les squelettes se terminent par .html
// pour des raisons historiques, ce qui est trompeur

// http://doc.spip.org/@public_styliser_dist
function public_styliser_dist($fond, $id_rubrique, $lang='', $connect='', $ext='html') {

	// trouver un squelette du nom demande
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
			'connect' => $connect
		),
		'data' => $squelette,
	));

	// pas de squelette : erreur !
	if (!$squelette) {
		include_spip('public/debug');
		erreur_squelette(_T('info_erreur_squelette2',
			array('fichier'=>"'$fond'")),
			$GLOBALS['dossier_squelettes']);
		$f = find_in_path(".$ext"); // on ne renvoie rien ici, c'est le resultat vide qui provoquere un 404 si necessaire
		return array(substr($f, 0, -strlen(".$ext")), $ext, $ext, $f);
	}

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
?>
