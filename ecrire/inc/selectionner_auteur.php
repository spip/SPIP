<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/editer_auteurs');
include_spip('inc/selectionner');

//
// Affiche un mini-navigateur ajax sur les auteurs
//

// http://doc.spip.org/@inc_selectionner_auteur_dist
function inc_selectionner_auteur_dist($id_article)
{
	global $spip_lang_right, $couleur_foncee;

	$idom = 'bloc_selectionner_auteur';

	$futurs = selectionner_auteur_boucle(determiner_non_auteurs('article',$id_article,'', "nom, statut"), $idom);

	// url completee par la fonction JS onkeypress_rechercher
	$url = generer_url_ecrire('rechercher_auteur', "idom=$idom&nom=");

	return construire_selectionner_hierarchie($idom, $futurs, '', $url, 'nouv_auteur');
}

// http://doc.spip.org/@selectionner_auteur_boucle
function selectionner_auteur_boucle($query, $idom)
{
	global  $spip_lang_left;

	$info = generer_url_ecrire('informer_auteur', "id=");
	$args = "'$idom" . "_selection', '$info', event";
	$res = '';

	while ($row = spip_fetch_array($query)) {

		$id = $row["id_auteur"];

		// attention, les <a></a> doivent etre au premier niveau
		// et se suivrent pour que changerhighligth fonctionne
		// De plus, leur zone doit avoir une balise et une seule
		// autour de la valeur pertinente pour que aff_selection
		// fonctionne (faudrait concentrer tout ca).

		$res .= "<a class='pashighlight'"
		. "\nonclick=\"changerhighlight(this);"
		. "findObj_forcer('nouv_auteur').value="
		. $id
		. "; aff_selection($id,$args); return false;"
		. "\"\nondbclick=\""
		. "findObj_forcer('nouv_auteur').value="
		. $id
		. ";findObj_forcer('selection_auteur').style.display="
		. "'none'; return false"
		. "\"><b>"
		. typo(extraire_multi($row["nom"]))
		. "</b></a>";
	}

	return $res;
}
?>
