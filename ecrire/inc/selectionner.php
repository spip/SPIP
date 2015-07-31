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

include_spip('inc/filtres');

//
// Affiche un mini-navigateur ajax positionne sur la rubrique $sel
//

// http://doc.spip.org/@inc_selectionner_dist
function inc_selectionner_dist ($sel, $idom="", $exclus=0, $aff_racine=false, $recur=true) {

	if ($recur) $recur = mini_hier($sel); else $sel = 0;

	if ($aff_racine) {
		$info = generer_url_ecrire('informer', "type=rubrique&rac=$idom&id=");
		$idom3 = $idom . "_selection";

		$onClick = " aff_selection(0, '$idom3', '$info', event);";

		$ondbClick = strtr(str_replace("'", "&#8217;",
				str_replace('"', "&#34;",
					textebrut(_T('info_racine_site')))),
				"\n\r", "  ");

		$ondbClick = "aff_selection_titre('$ondbClick',0,'selection_rubrique','id_parent');";

		$aff_racine = "<div class='arial11 petite-racine'\nonclick=\""
		. $onClick
		. "\"\nondbclick=\""
		. $ondbClick
		. $onClick
		. "\">\n<div class='pashighlight'>"
		. _T("info_racine_site")
		. "</div></div>";
	} else $onClick = '';

	$url_init = generer_url_ecrire('plonger',"rac=$idom&exclus=$exclus&id=0&col=1", true);

	$plonger = charger_fonction('plonger', 'inc');
	$plonger_r = $plonger($sel, $idom, $recur, 1, $exclus);

	// url completee par la fonction JS onkeypress_rechercher
	$url = generer_url_ecrire('rechercher', "exclus=$exclus&rac=$idom&type=");
	return construire_selectionner_hierarchie($idom, $plonger_r, $aff_racine, $url, 'id_parent', $url_init);
}

// http://doc.spip.org/@construire_selectionner_hierarchie
function construire_selectionner_hierarchie($idom, $liste, $racine, $url, $name, $url_init='')
{
	global $spip_lang_right;

	$idom1 = $idom . "_champ_recherche";
	$idom2 = $idom . "_principal";
	$idom3 = $idom . "_selection";
	$idom4 = $idom . "_col_1";
	$idom5 = 'img_' . $idom4;
	$idom6 = $idom."_fonc";

	return "<div id='$idom'>"
	. "<a id='$idom6' style='visibility: hidden;'"
	. ($url_init ?  "\nhref='$url_init'" : '')
	. "></a>"
	. "<table width='100%' cellpadding='0' cellspacing='0'><tr>"
	. "<td style='vertical-align: bottom;'>\n"
	. $racine
	. "</td>\n<td>"
	. http_img_pack("searching.gif", "*", "style='visibility: hidden;' id='$idom5'")
	. "</td><td style='text-align: $spip_lang_right'>"
	. "<input style='width: 100px;' type='search' id='$idom1'"
	  // eliminer Return car il provoque la soumission (balise unique)
	  // ce serait encore mieux de ne le faire que s'il y a encore plusieurs
	  // resultats retournes par la recherche
	. "\nonkeypress=\"k=event.keyCode;if (k==13 || k==3){return false;}\""
	  // lancer la recherche apres le filtrage ci-dessus
	. "\nonkeyup=\"return onkey_rechercher(this.value,"
	  // la destination de la recherche
	. "'$idom4'"
#	. "this.parentNode.parentNode.parentNode.parentNode.nextSibling.firstChild.id"
	. ",'"
	  // l'url effectuant la recherche
	. $url
	. "',"	
	  // le noeud contenant un gif anime 
	  // . "'idom5'"
	. "this.parentNode.previousSibling.firstChild"
	. ",'"
	  // la valeur de l'attribut Name a remplir
	.  $name
	. "','"
	  // noeud invisible memorisant l'URL initiale (pour re-initialisation)
	. $idom6
	. "')\"" 
	. " />"
	. "\n</td></tr></table><div id='"
	.  $idom2
	.  "' class='toile_blanche bordure_foncee' style='position: relative; height: 170px; overflow: auto;'><div id='$idom4'"
	. " class='arial1'>" 
	. $liste
	. "</div></div>\n<div id='$idom3'></div></div>\n";
}

// http://doc.spip.org/@mini_hier
function mini_hier ($id_rubrique) {
	
	$id_parent = $id_rubrique;
	$liste = $id_rubrique;
	while ($id_parent = mini_hierarchie_rub ($id_parent)) {
		$liste = $id_parent.",".$liste;
	}
	$liste = "0,".$liste;
	return explode(',',$liste);
}


// http://doc.spip.org/@mini_hierarchie_rub
function mini_hierarchie_rub ($id_rubrique) {
	$row = spip_fetch_array(spip_query("SELECT id_parent FROM spip_rubriques WHERE id_rubrique = " . intval($id_rubrique)));
	return $row["id_parent"];
}

?>
