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

//
// Affiche un mini-navigateur ajax positionne sur la rubrique $sel
//

// http://doc.spip.org/@inc_selectionner_dist
function inc_selectionner_dist ($sel, $idom="",$fonction="", $exclus=0, $aff_racine=false, $recur=true) {

	global $couleur_foncee, $spip_lang_right, $spip_lang_left;

	if (!$fonction)
		$fonction = "document.location='"
		. generer_url_ecrire('naviguer', "id_rubrique=::sel::")
		. "';";

	if ($recur) $recur = mini_hier($sel); else $sel = 0;

	$idom1 = $idom . "_champ_recherche";
	$idom2 = $idom . "_principal";
	$idom3 = $idom . "_selection";
	$idom4 = $idom . "_col_1";
	$idom5 = 'img_' . $idom4;
	$idom6 = $idom."_fonc";

	if ($aff_racine) {
		$info = generer_url_ecrire('informer', "type=rubrique&rac=$idom&id=");
		$onClick = " aff_selection(0,'$idom3', '$info');";

		$ondbClick = strtr(str_replace("'", "&#8217;",
				str_replace('"', "&#34;",
					textebrut(_T('info_racine_site')))),
				"\n\r", "  ");
		$ondbClick = "aff_selection_titre('$ondbClick',0,'selection_rubrique');";
	}

	if ($recur) {
		$plonger = generer_url_ecrire('plonger',"rac=$idom&exclus=$exclus&id=0&col=1", true);
		$onClick .= "charger_id_url('$plonger', '$idom4');";
	}

	$plonger = charger_fonction('plonger', 'inc');
	$plonger = $plonger($sel, $idom, $recur, 1, $exclus);
		
	// url completee par la fonction JS onkeypress_rechercher
	$url = generer_url_ecrire('rechercher', "exclus=$exclus&rac=$idom&type=");

	return "<div id='$idom'>"
	. "<div style='display: none;'>"
	. "<input type='text' id='$idom6' value=\"$fonction\" />"
	. "</div>\n"
	. "<table width='100%' cellpadding='0' cellspacing='0'><tr>"
	. "<td style='vertical-align: bottom;'>"
	. "\n<div class='arial11 petite-racine'\nonclick=\""
	. $onClick
	. "\"\nondbclick=\""
	. $ondbClick
	. $onClick
	. "\">\n<div class='pashighlight'>"
	. _T("info_racine_site")
	. "</div></div></td>\n<td>"
	. http_img_pack("searching.gif", "*", "style='visibility: hidden;' id='$idom5'")
	. "</td>"
	. "\n<td style='text-align: $spip_lang_right'>"
	. "<input style='width: 100px;' type='search' id='$idom1'"
	. "\nonkeypress=\"t=setTimeout('onkeypress_rechercher(\'"
	. $idom1
	. "\',\'"
	. $idom4
	. "\',\'"
	. $url
	. "\')', 200); key = event.keyCode; if (key == 13 || key == 3) { return false;} \" />"
	. "</td></tr></table>\n<div id='$idom2'"
	. " style='position: relative; height: 170px; background-color: white; border: 1px solid $couleur_foncee; overflow: auto;'><div id='$idom4'"
	. " class='arial1'>" 
	. $plonger
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
