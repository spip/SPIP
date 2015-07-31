<?php
/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


include ("inc.php3");
$charset = lire_meta("charset");
echo "<"."?xml version='1.0' encoding='$charset'?>";

	if ($fonction == "aff_rub") {
		include_ecrire("inc_mini_nav.php");
		echo mini_afficher_rubrique ($id_rubrique, $rac, "", $col, $exclus);
	}
	else if ($fonction == "aff_parent") {
		include_ecrire("inc_mini_nav.php");
		echo mini_nav ($id_rubrique, "choix-parent", "this.form.id_parent.value=::sel::;this.form.titreparent.value='::sel2::';findObj('selection_rubrique').style.display='none';", $exclus, $aff_racine=true);
	}
	else if ($fonction == "aff_rubrique") {
		include_ecrire("inc_mini_nav.php");
		echo mini_nav ($id_rubrique, "choix-parent", "this.form.id_rubrique.value=::sel::;this.form.titreparent.value='::sel2::';findObj('selection_rubrique').style.display='none';", 0, $aff_racine=false);
	}
	else if ($fonction == "aff_info") {
		// echo "$type - $id - $rac";
		
		if ($type == "rubrique") {
			$res = spip_query("SELECT titre, descriptif FROM spip_rubriques WHERE id_rubrique = $id");
			if ($row = spip_fetch_array($res)) {
				$titre = typo($row["titre"]);
				$descriptif = propre($row["descriptif"]);
			} else {
				$titre = addslashes(_T('info_racine_site'));
			}
		} 
		
		echo "<div style='display: none;'>";
		echo "<input type='text' id='".$rac."_sel' value='$id' />";
		echo "<input type='text' id='".$rac."_sel2' value='".addslashes($titre)."' />";
		echo "</div>";

		include_ecrire ("inc_logos.php3");


		echo "<div class='arial2' style='padding: 5px; background-color: white; border: 1px solid $couleur_foncee; border-top: 0px;'>";
		if ($type == "rubrique" AND $spip_display != 1 AND $spip_display!=4 AND lire_meta('image_process') != "non") {
			include_ecrire("inc_logos.php3");
			$logo = decrire_logo("rubon$id");
			if ($logo) {
				$fichier = $logo[0];
					echo  "<div style='float: $spip_lang_right; margin-$spip_lang_right: -5px; margin-top: -5px;'>";
					echo reduire_image_logo(_DIR_IMG.$fichier, 100, 48);
					echo "</div>";
			}
		}

		echo "<div><p><b>$titre</b></p></div>";
		if (strlen($descriptif) > 0) echo "<div>$descriptif</div>";

		echo "<div style='text-align: $spip_lang_right;'>";
		echo "<input type='button' value='"._T('bouton_choisir')."' class='fondo' onClick=\"sel=findObj_forcer('".$rac."_sel').value; sel2=findObj_forcer('".$rac."_sel2').value; func = findObj('".$rac."_fonc').value; func = func.replace('::sel::', sel); func = func.replace('::sel2::', sel2); eval(func);\">";
		echo "</div>";


		echo "</div>";
		
	}
	else if ($recherche_rub) {
	
		function exclure_enfants ($id_parent, $liste_exclus) {
			$res = spip_query("SELECT id_rubrique FROM spip_rubriques WHERE id_parent=$id_parent");
			while ($row = spip_fetch_array($res)) {
				$id_rubrique = $row["id_rubrique"];
				$liste_exclus .= ", $id_rubrique";
				$liste_exclus = exclure_enfants($id_rubrique, $liste_exclus);
			}
			return $liste_exclus;
		}
	
	
		if ($exclus > 0) {
			$liste_exclus = $exclus;
			$liste_exclus = exclure_enfants($exclus, $liste_exclus);
		}
	
		$recherche = str_replace("%","\%",$recherche_rub);
		$rech2 = split("[[:space:]]+", $recherche);
		if ($rech2) {
			$where_titre = " (titre LIKE '%".join("%' AND titre LIKE '%", $rech2)."%') ";
			$where_desc = " (descriptif LIKE '%".join("%' AND descriptif LIKE '%", $rech2)."%') ";
			$where_id = " (id_rubrique = '".join("' AND id_rubrique = '", $rech2)."') ";
		}
		else {
			$where_titre = " 1=2";
			$where_desc = " 1=2";
			$where_id = " 1=2";
		}
		if ($liste_exclus) $where_exclus = " AND id_rubrique NOT IN ($liste_exclus)";
		

		$res = spip_query("SELECT id_rubrique, id_parent, titre FROM spip_rubriques WHERE $where_id$where_exclus");
		while ($row = spip_fetch_array($res)) {
			$id_rubrique = $row["id_rubrique"];
			$rub[$id_rubrique]["titre"] = typo ($row["titre"]);
			$rub[$id_rubrique]["id_parent"] = $row["id_parent"];
			$points[$id_rubrique] = $points[$id_rubrique] + 3;			
		}
		$res = spip_query("SELECT id_rubrique, id_parent, titre FROM spip_rubriques WHERE $where_titre$where_exclus");
		while ($row = spip_fetch_array($res)) {
			$id_rubrique = $row["id_rubrique"];
			$rub[$id_rubrique]["titre"] = typo ($row["titre"]);
			$rub[$id_rubrique]["id_parent"] = $row["id_parent"];
			$points[$id_rubrique] = $points[$id_rubrique] + 2;			
		}
		$res = spip_query("SELECT id_rubrique, id_parent, titre FROM spip_rubriques WHERE $where_desc$where_exclus");
		while ($row = spip_fetch_array($res)) {
			$id_rubrique = $row["id_rubrique"];
			$rub[$id_rubrique]["titre"] = typo ($row["titre"]);
			$rub[$id_rubrique]["id_parent"] = $row["id_parent"];
			$points[$id_rubrique] = $points[$id_rubrique] + 1;			
		}
		
		if ($points) {
			arsort($points);
			while (list($id,$pts) = each($points)) {
				
				$id_rubrique = $id;
				$titre = $rub[$id]["titre"];
				$id_parent = $rub[$id]["id_parent"];
				
				// Eviter une premiere fois d'afficher la rubrique exclue
					if ($id_parent == 0) $style = "style='background-image: url(" . _DIR_IMG_PACK . "secteur-12.gif)'";
					else $style = "";
					$onClick = " aff_selection('rubrique','$rac','$id_rubrique');";
	
					$ret .= "<div class='pashighlight' onClick=\"changerhighlight(this); $onClick\"><div class='arial11 petite-rubrique'$style>";
					$ret .= "&nbsp; $titre";
					$ret .= "</div></div>";
			}
				
		}
		if ($ret) echo $ret;
		else echo "<div style='padding: 5px; color: red;'>"._T('avis_aucun_resultat')."</div>";
		
		
	}
	else if ($GLOBALS["id_ajax_fonc"]) {
		$res = spip_query("SELECT * FROM spip_ajax_fonc WHERE id_ajax_fonc = $id_ajax_fonc AND id_auteur=$connect_id_auteur");
		if ($row = spip_fetch_array($res)) {
			$variables = $row["variables"];
			
			$variables = unserialize($variables);
			while (list($i, $k) = each($variables)) {
				$$i = $k;
				
			}
			
			// Appliquer la fonction
			if ($fonction == "afficher_articles") {
				afficher_articles ($titre_table, $requete, $afficher_visites, $afficher_auteurs);
			}

			if ($fonction == "afficher_articles_trad") {
				afficher_articles_trad ($titre_table, $requete, $afficher_visites, $afficher_auteurs);
			}
			if ($fonction == "afficher_groupe_mots") {
				include_ecrire("inc_mots.php3");
				afficher_groupe_mots ($id_groupe);
			}
			
		}

	}



?>