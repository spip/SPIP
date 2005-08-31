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
	
	if ($fonction == "aff_parent") {
		include_ecrire("inc_mini_nav.php");
		echo mini_nav ($id_rubrique, "choix-parent", "this.form.id_parent.value=::sel::;this.form.titreparent.value='::sel2::';findObj('selection_rubrique').style.display='none';", $exclus, $aff_racine=true);
	}
	

	if ($fonction == "aff_info") {
		// echo "$type - $id - $rac";
		
		if ($type == "rubrique") {
			$res = spip_query("SELECT titre FROM spip_rubriques WHERE id_rubrique = $id");
			if ($row = spip_fetch_array($res)) {
				$titre = addslashes(typo($row["titre"]));
			}
		}
		
		echo "<div style='display: none;'>";
		echo "<input type='text' id='".$rac."_sel' value='$id' />";
		echo "<input type='text' id='".$rac."_sel2' value='$titre' />";
		echo "</div>";
		echo "<b>$titre</b>";
		
		echo "<div style='text-align: $spip_lang_right;'>";
		echo "<input type='button' value='Choisir' class='fondo' onClick=\"sel=findObj_forcer('".$rac."_sel').value; sel2=findObj_forcer('".$rac."_sel2').value; func = findObj('".$rac."_fonc').value; func = func.replace('::sel::', sel); func = func.replace('::sel2::', sel2); eval(func);\">";
		echo "</div>";
	}


	if ($GLOBALS["id_ajax_fonc"]) {
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