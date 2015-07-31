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

// http://doc.spip.org/@mini_afficher_rubrique
function mini_afficher_rubrique ($id_rubrique, $rac="", $list=array(), $col = 1, $rub_exclus=0) {
	global  $spip_lang_left;
	
	if ($list) $id_rubrique = $list[$col-1];
	
	$nom_col = $rac . "_col_".($col+1);

	$ret = "<div id='"
	. $rac
	. "_col_"
	. $col
	."' class='arial1'>" 
	. http_img_pack("searching.gif", "*", "style='visibility: hidden; position: absolute; $spip_lang_left: "
		.(($col*150)-30)
		."px; top: 2px; z-index: 2;' id='img_$nom_col'")
	. "<div style='width: 150px; height: 100%; overflow: auto; position: absolute; top: 0px; $spip_lang_left: "
	.(($col-1)*150)
	."px;'>";

	# recherche les filles et petites-filles de la rubrique donnee
	$ordre = array();

	$res = spip_query("SELECT rub1.id_rubrique, rub1.titre, rub1.id_parent, rub1.lang, rub1.langue_choisie FROM spip_rubriques AS rub1, spip_rubriques AS rub2 WHERE ((rub1.id_parent = $id_rubrique) OR (rub2.id_parent = $id_rubrique AND rub1.id_parent=rub2.id_rubrique)) AND rub1.id_rubrique!=$rub_exclus GROUP BY rub1.id_rubrique ORDER BY rub1.titre");

	while ($row = spip_fetch_array($res)) {
		$id = $row["id_rubrique"];
		$rub[$id] = $row;
		$rub[$row["id_parent"]]["enfants"] = true;
		if ($row["id_parent"] == $id_rubrique)
			$ordre[$id] = trim(typo($row['titre']))
			. (($row["langue_choisie"] != "oui") ? '' : (" [" . $row["lang"] . "]"));

	}

	if ($ordre) {
		asort($ordre);

		while (list($id, $titre) = each($ordre)) {

			$titre = "<div class='"
			. ($id_rubrique ? 'petite-rubrique' : "petit-secteur")
			. "'>"
			. supprimer_numero($titre)
			. "</div>";

			if ($rub[$id]["enfants"]) {
				$titre = "\n<div class='rub-ouverte'>$titre</div>";

		# ensuite, ouverture ou fermeture du menu des sous-rubriques
				$url = generer_url_ecrire('plonger',"&var_ajax=1&rac=$rac&exclus=$rub_exclus&id=$id&col=".($col+1), true);

			} else {  $url = ''; }

			$class = ($id == $list[$col]) ? "highlight" : "pashighlight";

			$onClick = "\naff_selection_provisoire($id,\n\t'$rac',\n\t'$url',\n\t$col,\n\t'$spip_lang_left');";
				
# ce lien provoque la selection (directe) de la rubrique cliquee
# et l'affichage de son titre dans le bandeau
			$ondbClick = "aff_selection_titre($id,'"
			. strtr(str_replace("'", "&#8217;",
						str_replace('"', "&#34;",
							textebrut($titre))),
					"\n\r", "  ")
			. "');";

			$ret .= "\n<div class='$class'\nonClick=\"changerhighlight(this);$onClick\"\nondblclick=\"$ondbClick$onClick\">$titre\n</div>";
		}
	}
	
	$ret .= "\n</div>";

	if ($list[$col]) $ret .= mini_afficher_rubrique ($id_rubrique, $rac, $list, $col+1, $rub_exclus);
	else $ret .= "\n<div id='$nom_col'></div>";
	
	$ret .= "\n</div>";

	return $ret;
}


// http://doc.spip.org/@mini_hierarchie_rub
function mini_hierarchie_rub ($id_rubrique) {
	$row = spip_fetch_array(spip_query("SELECT id_parent FROM spip_rubriques WHERE id_rubrique = " . intval($id_rubrique)));
	return $row["id_parent"];
}


// http://doc.spip.org/@mini_afficher_hierarchie
function mini_afficher_hierarchie ($id_rubrique, $rac="", $rub_exclus=0) {
	
	$id_parent = $id_rubrique;
	$liste = $id_rubrique;
	while ($id_parent = mini_hierarchie_rub ($id_parent)) {
		$liste = $id_parent.",".$liste;
	}
	
	$liste = "0,".$liste;
		
	return mini_afficher_rubrique($id_rubrique, $rac, explode(',',$liste), 1, $rub_exclus);
}

// http://doc.spip.org/@mini_nav_principal
function mini_nav_principal ($id_rubrique, $rac="", $rub_exclus=0) {
	global $couleur_foncee;
	$ret = "<div id='".$rac."_principal' style='position: relative; height: 170px; background-color: white; border: 1px solid $couleur_foncee; overflow: auto;'>";
	$ret .= mini_afficher_hierarchie($id_rubrique, $rac, $rub_exclus);
	$ret .= "</div>";
	
	return $ret;
}

//
// Affiche un mini-navigateur ajax positionne sur la rubrique $sel
//
// http://doc.spip.org/@mini_nav
function mini_nav ($sel, $rac="",$fonction="", $rub_exclus=0, $aff_racine=false) {

	if (!$fonction)
		$fonction = "document.location='" . generer_url_ecrire('naviguer', "id_rubrique=::sel::") .
			"';";

	global $couleur_foncee, $spip_lang_right, $spip_lang_left;
	if ($id_rubrique < 1) $id_rubrique = 0;

	$ret = "<div id='$rac'>"
	. "<div style='display: none;'>"
	. "<input type='text' id='".$rac."_fonc' value=\"$fonction\" />"
	. "</div>\n"
	. "<table width='100%' cellpadding='0' cellspacing='0'>"
	. "<tr>"
	. "<td style='vertical-align: bottom;'>";

	if ($aff_racine) {
		$onClick = " aff_selection('rubrique','$rac', '0');";
		# ce lien provoque la selection (directe) de la rubrique cliquee
		$ondbClick = "findObj('id_parent').value=0;";
		# et l'affichage de son titre dans le bandeau
		$ondbClick .= "findObj('titreparent').value='"
			. strtr(
				str_replace("'", "&#8217;",
				str_replace('"', "&#34;",
					textebrut(_T('info_racine_site')))),
				"\n\r", "  ")."';";
		$ondbClick .= "findObj('selection_rubrique').style.display='none';";
	}

	$onClick .= "charger_id_url('" . generer_url_ecrire('plonger',"&var_ajax=1&rac=$rac&exclus=$rub_exclus&id=0&col=1", true) . "', '".$rac."_col_1');";

	$ret .= "\n<div class='arial11 petite-racine'\nonclick=\""
	. $onClick
	. "\"\nondblclick=\""
	. $ondbClick
	. $onClick
	. "\">\n<div class='pashighlight'>"
	. _T("info_racine_site")
	. "</div></div>"
	. "</td>"	. "\n<td>"
	. http_img_pack("searching.gif", "*", "style='visibility: hidden;' id='img_".$rac."_col_1'")
	. "</td>"
	. "\n<td style='text-align: $spip_lang_right'>"
	. "<input style='width: 100px;' type='search' id='"
	. $rac
	. "_champ_recherche'\nonkeypress=\"t=setTimeout('lancer_recherche_rub(\'"
	  . $rac
	  . "_champ_recherche\',\'$rac\',\'$rub_exclus\')', 200); key = event.keyCode; if (key == 13 || key == 3) { return false;} \" />"
	. "</td></tr></table>\n"
	. mini_nav_principal($sel, $rac, $rub_exclus)
	. "\n<div id='"
	.$rac
	."_selection'></div>"
	. "</div>\n";

	return $ret;
}


?>
