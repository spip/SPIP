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

include_spip('inc/editer_auteurs');
include_spip('inc/selectionner');

//
// Affiche un mini-navigateur ajax sur les auteurs
//

// http://doc.spip.org/@inc_selectionner_auteur_dist
function inc_selectionner_auteur_dist($id_article, $type='article')
{
	$idom = "auteur_$type" . "_$id_article";
	$new = $idom . '_new';
	if (!$determiner_non_auteurs = charger_fonction('determiner_non_auteurs_'.$type,'inc',true))
		$determiner_non_auteurs = 'determiner_non_auteurs';

	$futurs = selectionner_auteur_boucle($determiner_non_auteurs($type, $id_article), $idom);

	// url completee par la fonction JS onkeypress_rechercher
	$url = generer_url_ecrire('rechercher_auteur', "idom=$idom&nom=");

	return construire_selectionner_hierarchie($idom, $futurs, '', $url, $new);
}

// http://doc.spip.org/@selectionner_auteur_boucle
function selectionner_auteur_boucle($where, $idom)
{
	$info = generer_url_ecrire('informer_auteur', "id=");
	$idom3 = $idom . '_selection';
	$idom2 = $idom . '_new';
	$idom1 = $idom . '_div';
	$args = "'$idom3', '$info', event";
	$res = '';
	$all = sql_allfetsel("nom, id_auteur", "spip_auteurs", $where, '', "nom, statut");
	foreach ($all as $row) {

		$id = $row["id_auteur"];
		$nom = typo($row["nom"]);

		// attention, les <a></a> doivent etre au premier niveau
		// et se suivrent pour que changerhighligth fonctionne
		// De plus, leur zone doit avoir une balise et une seule
		// autour de la valeur pertinente pour que aff_selection
		// fonctionne (faudrait concentrer tout ca).

		$res .= "<a class='highlight off'"
		. "\nonclick=\"changerhighlight(this);"
		. "findObj_forcer('$idom2').value="
		. $id
		. "; aff_selection($id,$args); return false;"
		. "\"\nondblclick=\""
		  // incomplet: le selecteur devient indisponible. A ameliorer
		. "findObj_forcer('$idom').parentNode.innerHTML='"
		. attribut_html($nom)
		. "'; findObj_forcer('$idom2').value="
		. $id
		. "; return false"
		. "\"><b>"
		. $nom
		. "</b></a>";
	}

	return $res;
}
?>
