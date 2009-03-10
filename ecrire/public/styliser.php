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
	
	// Trouver un squelette de base dans le chemin
	if (!$base = find_in_path("$fond.$ext")) {
		// Si pas de squelette regarder si c'est une table
		$trouver_table = charger_fonction('trouver_table', 'base');
		if (preg_match('/^table:(.*)$/', $fond, $r)
		AND $table = $trouver_table($r[1], $connect)
		AND include_spip('inc/autoriser')
		AND autoriser('webmestre')
		) {
				$fond = $r[1];
				$base = _DIR_TMP . 'table_'.$fond . ".$ext";
				if (!file_exists($base)
				OR  $GLOBALS['var_mode']) {
					$vertebrer = charger_fonction('vertebrer', 'public');
					ecrire_fichier($base, $vertebrer($table));
				}
		} else {
			// Indiquer une erreur squelette
			include_spip('public/debug');
			erreur_squelette(_T('info_erreur_squelette2',
				array('fichier'=>"'$fond.$ext'")),
				$GLOBALS['dossier_squelettes']);
			// provoquer 404
			return array(null, $ext, $ext, null);
		}
	}

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

	// On selectionne, dans l'ordre :
	// fond=10
	if ($id_rubrique) {
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
	}

	// Affiner par lang
	if ($lang) {
		$l = lang_select($lang);
		$f = "$squelette.".$GLOBALS['spip_lang'];
		if ($l) lang_select();
		if (@file_exists("$f.$ext"))
			$squelette = $f;
	}

	return array($squelette, $ext, $ext, "$squelette.$ext");
}
?>
