<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;

// Ce fichier doit imperativement contenir la fonction chercher-squelette
// (cf commentaires dans inc-calcul)

function chercher_squelette($fond, $id_rubrique, $lang) {
	$ext = $GLOBALS['extension_squelette'];

	// Accrocher un squelette de base dans le chemin
	if (!$base = find_in_path("$fond.$ext")) {
		// erreur webmaster : $fond ne correspond a rien
		erreur_squelette(_T('info_erreur_squelette2',
			 array('fichier'=>$fond)),
			 $dossier);
		return '';
	}

	// supprimer le ".html" pour pouvoir affiner par id_rubrique ou par langue
	$squelette = substr($base, 0, - strlen(".$ext"));

	// On selectionne, dans l'ordre :
	// fond=10
	$f = "$fond=$id_rubrique";
	if (($id_rubrique > 0) AND ($squel=find_in_path("$f.$ext")))
		$squelette = substr($squel, 0, - strlen(".$ext"));
	else {
		// fond-10 fond-<rubriques parentes>
		while ($id_rubrique > 0) {
			$f = "$fond-$id_rubrique";
			if ($squel=find_in_path("$f.$ext")) {
				$squelette = substr($squel, 0, - strlen(".$ext"));
				break;
			}
			else
				$id_rubrique = sql_parent($id_rubrique);
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
