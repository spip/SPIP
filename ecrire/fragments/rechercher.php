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

# gerer un charset minimaliste en convertissant tout en unicode &#xxx;

// http://doc.spip.org/@fragments_rechercher_dist
function fragments_rechercher_dist()
{
	global $flag_ob;

	if ($flag_ob) {			ob_start();		}
	ajax_page_recherche();

	if ($flag_ob) {
			$a = ob_get_contents();
			ob_end_clean();
			return $a;
	}
}

# Un moteur de recherche ?
// http://doc.spip.org/@ajax_page_recherche
function ajax_page_recherche()
{
	global $id, $exclus, $type, $rac;
	$id = intval($id);
	$exclus = intval($exclus);

		include_spip('inc/texte');
		$where = split("[[:space:]]+", $type);
		if ($where) {
			foreach ($where as $k => $v) 
				$where[$k] = "'%" . substr(str_replace("%","\%", spip_abstract_quote($v)),1,-1) . "%'";
			$where_titre = ("(titre LIKE " . join(" AND titre LIKE ", $where) . ")");
			$where_desc = ("(descriptif LIKE " . join(" AND descriptif LIKE ", $where) . ")");
			$where_id = ("(id_rubrique = " . join(" AND id_rubrique = ", $where) . ")");
		} else {
			$where_titre = " 1=2";
			$where_desc = " 1=2";
			$where_id = " 1=2";
		}

		if ($exclus) {
			include_spip('inc/rubriques');
			$where_exclus = " AND id_rubrique NOT IN (".calcul_branche($exclus).")";
		} else
			$where_exclus = '';

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
					$onClick = " aff_selection('rubrique','" .
					  htmlentities($rac) .
					  "','$id_rubrique');";
	
					$ondbClick = "findObj('id_parent').value=$id_rubrique;";
					# et l'affichage de son titre dans le bandeau
					$ondbClick .= "findObj('titreparent').value='"
					. strtr(
						str_replace("'", "&#8217;",
						str_replace('"', "&#34;",
							textebrut($titre))),
						"\n\r", "  ")."';";
				$ondbClick .= "findObj('selection_rubrique').style.display='none';";

	
					$ret .= "<div class='pashighlight' onClick=\"changerhighlight(this); $onClick\" ondblclick=\"$ondbClick$onClick\"><div class='arial11 petite-rubrique'$style>";
					$ret .= "&nbsp; $titre";
					$ret .= "</div></div>";
			}
				
		}
		if ($ret)
			echo $ret;
		else
			echo "<div style='padding: 5px; color: red;'><b>"
			.htmlentities($type)
			."</b> :  "._T('avis_aucun_resultat')."</div>";

}


?>
