<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

if (!isset($GLOBALS['spip_pipeline']['styliser']))
	$GLOBALS['spip_pipeline']['styliser'] = '';
$GLOBALS['spip_pipeline']['styliser'] .= '||squelettes_par_rubrique_styliser_par_rubrique|squelettes_par_rubrique_styliser_par_langue';

/**
 * Options de recherche de squelette par le styliseur, appele par le pipeline 'styliser' :
 * Squelette par rubrique squelette-XX.html ou squelette=XX.html
 *
 * @param <type> $flux
 * @return <type>
 */
function squelettes_par_rubrique_styliser_par_rubrique($flux) {

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
				$maxiter = 10000; // on ne remonte pas au dela en profondeur
				// fond-10 fond-<rubriques parentes>
				do {
					$f = "$squelette-$id_rubrique";
					if (@file_exists("$f.$ext")) {
						$squelette = $f;
						break;
					}
				} while (
					$maxiter--
				  AND $id_rubrique = quete_parent($id_rubrique)
					// se proteger des references circulaires
				  AND $id_rubrique != $flux['args']['id_rubrique']
				);
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
function squelettes_par_rubrique_styliser_par_langue($flux) {

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
