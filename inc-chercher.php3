<?php

// Ce fichier ne sera execute qu'une fois
if (defined("_INC_CHERCHER")) return;
define("_INC_CHERCHER", "1");

// Ce fichier doit imperativement contenir la fonction chercher-squelette
// (cf commentaires dans inc-calcul)

function chercher_squelette($fond, $id_rubrique, $dossier, $lang) {
	$ext = $GLOBALS['extension_squelette'];

	$d ="$dossier$fond";

	// On selectionne, dans l'ordre :
	// fond=10
	
	$f = "$d=$id_rubrique";
	if (($id_rubrique > 0) AND (@file_exists("$f.$ext")))
		$squelette = $f;

	// fond-10 fond-<rubriques parentes>
	if (!$squelette)
		while ($id_rubrique > 0) {
			if (@file_exists("$d-$id_rubrique.$ext")) {
				$squelette = "$d-$id_rubrique";
				break;
			}
			else
				$id_rubrique = sql_parent($id_rubrique);
		}

	if (!$squelette) {
		// fond
		if (@file_exists("$d.$ext"))
			$squelette = $d;
		// fond, a la racine
		else if (@file_exists("$fond.$ext"))
			$squelette = $fond;
		else if (@file_exists("$fond-dist.$ext"))
			$squelette = "$fond-dist";
		else {
			// erreur webmaster : $fond ne correspond a rien
			erreur_squelette(_T('zbug_info_erreur_squelette2',
				 array('fichier'=>$fond)),
				 $dossier);
			return '';
		}
	}

	// Affiner par lang
	if ($lang) {
		lang_select($lang);
		$f = "$squelette.$lang";
		if (@file_exists("$f.$ext"))
			$squelette = $f;
	}

	return $squelette;
}

?>
