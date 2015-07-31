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

include_spip('inc/editer_auteurs');

//
// Affiche un mini-navigateur ajax sur les auteurs
//

function inc_selectionner_auteur_dist($id_article)
{
	global $spip_lang_right, $couleur_foncee;

	$idom = 'bloc_selectionner_auteur';
	$idom1 = $idom . "_champ_recherche";
	$idom2 = $idom . "_principal";
	$idom3 = $idom . "_selection";
	$idom4 = $idom . "_col_1";
	$idom5 = 'img_' . $idom4;
	$idom6 = $idom."_fonc";

	$les_auteurs = join(',', determiner_auteurs_article($id_article));
	$futurs = selectionner_auteur_boucle(determiner_non_auteurs($les_auteurs, "nom, statut"), $idom);

	// url completee par la fonction JS onkeypress_rechercher
	$url = generer_url_ecrire('rechercher_auteur', "idom=$idom&nom=");

	return "<div id='$idom'>"
	. "<input style='width: 100px;' type='search' id='$idom1'"
	. "\nonkeypress=\"t=setTimeout('onkeypress_rechercher(\'"
	. $idom1
	. "\',\'"
	. $idom4
	. "\',\'"
	. $url
	. "\')', 200); key = event.keyCode; if (key == 13 || key == 3) { return false;} \" />"
	. http_img_pack("searching.gif", "*", "style='visibility: hidden;' id='$idom5'") 
	. "<div id='$idom2'"
	. " style='position: relative; height: 170px; background-color: white; border: 1px solid $couleur_foncee; overflow: auto;'><div id='$idom4'"
	. " class='arial1'>" 
	. $futurs
	. "</div></div>\n<div id='$idom3'></div></div>\n";
}

function selectionner_auteur_boucle($query, $idom)
{
	global  $spip_lang_left;

	$info = generer_url_ecrire('informer_auteur', "id=");
#	$args = "'$idom',this, '$col', '$spip_lang_left', '$info'";
	$args = "'$idom" . "_selection', '$info'";
	
	$res = '';

	while ($row = spip_fetch_array($query)) {

		$id = $row["id_auteur"];
		$titre = typo(extraire_multi($row["nom"]));

		$email = $row["email"];
		$statut = $row["statut"];

		$commun = "findObj_forcer('nouv_auteur').value="
		. $id;

		// attention, les <a></a> doivent etre au premier niveau
		// et se suivrent pour que changerhighligth fonctionne

		$res .= "<a class='pashighlight'"
		. "\nonclick=\"changerhighlight(this);"
		. $commun
		. "; aff_selection($id,$args); return false;"
		. "\"\nondbclick=\""
		. $commun  
		  . ";findObj_forcer('selection_auteur').style.display="
		. "'none'; return false"
		. "\">$titre</a>";
	}

	return $res;
}
?>
