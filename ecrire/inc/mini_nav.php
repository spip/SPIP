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

function mini_afficher_rubrique ($id_rubrique, $rac="", $liste="", $col = 1, $rub_exclus=0) {
	global  $spip_lang_left;
	
	if (strlen($liste) > 0) {
		$rubs = explode(",", $liste);
		$id_rubrique = $rubs[$col-1];
	}
	
	
	$ret = "<div id = '".$rac."_col_".$col."' class='arial1'>"; 
	$ret .= http_img_pack("searching.gif", "*", "style='visibility: hidden; position: absolute; $spip_lang_left: ".(($col*150)-30)."px; top: 2px; z-index: 2;' id = 'img_".$rac."_col_".($col+1)."'");

	$ret .= "<div style='width: 150px; height: 100%; overflow: auto; position: absolute; top: 0px; $spip_lang_left: ".(($col-1)*150)."px;'>";

	# recherche les filles et petites-filles de la rubrique donnee
	$res = spip_query("SELECT rub1.* FROM spip_rubriques AS rub1, spip_rubriques AS rub2 WHERE ((rub1.id_parent = $id_rubrique) OR (rub2.id_parent = $id_rubrique AND rub1.id_parent=rub2.id_rubrique)) AND rub1.id_rubrique!=$rub_exclus GROUP BY rub1.id_rubrique ORDER BY rub1.titre");
	while ($row = spip_fetch_array($res)) {
		$le_parent = $row["id_parent"];
		$la_rub = $row["id_rubrique"];
		$titre = typo($row["titre"]);
		$lang = $row["lang"];
		$langue_choisie = $row["langue_choisie"];
		
		if ($langue_choisie == "oui") $titre = $titre. " [$lang]";
		
		$rub[$la_rub]["id_rubrique"] = $la_rub;
		$rub[$la_rub]["id_parent"] = $le_parent;
		$rub[$la_rub]["titre"] = $titre;
		$rub[$le_parent]["enfants"] = true;
		$ordre[$la_rub] = trim($titre);
	}

	if ($ordre) {
		asort($ordre);
		while (list($i, $k) = each($ordre)) {
			$le_parent = $rub[$i]["id_parent"];
			$la_rub = $rub[$i]["id_rubrique"];
			$titre = $rub[$i]["titre"];
	
			if ($le_parent == $id_rubrique) {
				if ($la_rub == $rubs[$col]) $class="highlight";
				else $class = "pashighlight";
				
				if ($rub[$i]["id_parent"] == 0) $style = " style='background-image: url(" . _DIR_IMG_PACK . "secteur-12.gif)'";

				$titre = "<div class='petite-rubrique'$style>"
					.supprimer_numero($titre)."</div>";
				# ce lien provoque la selection (directe) de la rubrique cliquee
				$ondbClick = "findObj('id_parent').value=$la_rub;";
				# et l'affichage de son titre dans le bandeau
				$ondbClick .= "findObj('titreparent').value='"
					. strtr(
						str_replace("'", "&#8217;",
						str_replace('"', "&#34;",
							textebrut($titre))),
						"\n\r", "  ")."';";
				$ondbClick .= "findObj('selection_rubrique').style.display='none';";

				if ($rub[$i]["enfants"]) {
					$titre = "<div class='rub-ouverte'>$titre</div>";

					# ensuite, l'ouverture du menu des sous-rubriques
					$url = generer_url_ecrire('plonger',"&var_ajax=1&rac=$rac&exclus=$rub_exclus&id=$la_rub&col=".($col+1), true);
					$onClick .= "charger_id_url('$url',"
					. "'".$rac."_col_".($col+1)
					."', 'slide_horizontal(\'".$rac."_principal\', \'"
					.(($col-1)*150)."\', \'$spip_lang_left\')');";
				} else {
					# ou la fermeture du menu des sous-rubriques
					$onClick .= "findObj_forcer('".$rac."_col_"
					. ($col+1)."').innerHTML='';";
				}

				## afficher le descriptif de la rubrique dans la div du dessous?
				# si trop lent, commenter la ligne ci-dessous
				$onClick .= " aff_selection('rubrique','$rac','$la_rub');";
				##

				$ret .= "<div class='$class' onClick=\"changerhighlight(this); $onClick\" ondblclick=\"$ondbClick$onClick\">";
				$ret .= $titre;
				$ret .= "</div>";
			}
		}
	}
	
	$ret .= "</div>";
	
	if ($rubs[$col]) $ret .= mini_afficher_rubrique ($id_rubrique, $rac, $liste, $col+1, $rub_exclus);
	else $ret .= "<div id = '".$rac."_col_".($col+1)."'></div>";
	
	$ret .= "</div>";
	
	return $ret;
}


function mini_hierarchie_rub ($id_rubrique) {
	$row = spip_fetch_array(spip_query("SELECT id_parent FROM spip_rubriques WHERE id_rubrique = " . intval($id_rubrique)));
	return $row["id_parent"];
}


function mini_afficher_hierarchie ($id_rubrique, $rac="", $rub_exclus=0) {
	
	$id_parent = $id_rubrique;
	while ($id_parent = mini_hierarchie_rub ($id_parent)) {
		$liste = $id_parent.",".$liste;
	}
	
	$liste = "0,".$liste.$id_rubrique;
		
	$ret = mini_afficher_rubrique ($id_rubrique, $rac, $liste, $col = 1, $rub_exclus);
	
	return $ret;
	
}

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
function mini_nav ($sel, $rac="",$fonction="", $rub_exclus=0, $aff_racine=false) {

	if (!$fonction)
		$fonction = "document.location='" . generer_url_ecrire('naviguer', "id_rubrique=::sel::") .
			"';";

	global $couleur_foncee, $spip_lang_right, $spip_lang_left;
	if ($id_rubrique < 1) $id_rubrique = 0;

	$ret .= "<div id='$rac'>";
	$ret .= "<div style='display: none;'>";
	$ret .= "<input type='text' id='".$rac."_fonc' value=\"$fonction\" />";
	$ret .= "</div>\n";
	
	$ret .= "<table width='100%' cellpadding='0' cellspacing='0'>";
	$ret .= "<tr>";

	$ret .= "<td style='vertical-align: bottom;'>";

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

	$ret .= "<div class='arial11 petite-rubrique' onclick=\"$onClick\" ondblclick=\"$ondbClick$onClick\" style='background-image: url(" . _DIR_IMG_PACK . "racine-site-12.gif); background-color: white; border: 1px solid $couleur_foncee; border-bottom: 0px; width: 134px;'><div class='pashighlight'>";
	$ret .= _T("info_racine_site");
	$ret .= "</div></div>";
	$ret .= "</td>";

	$ret .= "<td>";
	$ret .= http_img_pack("searching.gif", "*", "style='visibility: hidden;' id='img_".$rac."_col_1'");
	$ret .= "</td>";

	$ret .= "<td style='text-align: $spip_lang_right'>";
	$ret .= "<input id='".$rac."_champ_recherche' type='search' onkeypress=\"t=setTimeout('lancer_recherche_rub(\'".$rac."_champ_recherche\',\'$rac\',\'$rub_exclus\')', 200); key = event.keyCode; if (key == 13 || key == 3) { return false;} \" style='width: 100px;' />";
	$ret .= "</td></tr></table>\n";
	
	$ret .= mini_nav_principal($sel, $rac, $rub_exclus);
	
	$ret .= "<div id='".$rac."_selection'></div>";
	
	$ret .= "</div>\n";
	return $ret;
}


?>
