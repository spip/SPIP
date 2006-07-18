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

function exec_ajax_page_dist()
{
	global $flag_ob, $fonction, $id, $exclus, $col, $id_ajax_fonc, $type, $rac;
	$id = intval($id);
	$exclus = intval($exclus);
	$col = intval($col);

	$var_nom = 'ajax_page_' . $fonction;
	if (!function_exists($var_nom))
		spip_log("fonction $var_nom indisponible");
	else {
		if ($flag_ob) {
			ob_start();
			$charset = $GLOBALS['meta']["charset"];
		}
// Curieux: le content-type bloque MSIE!
//		@header('Content-type: text/html; charset=$charset');
		echo "<"."?xml version='1.0' encoding='$charset'?".">\n";
		$var_nom($id, $exclus, $col, $id_ajax_fonc, $type, $rac);

		if ($flag_ob) {
			$a = ob_get_contents();
			ob_end_clean();
			include_spip('inc/charsets');
			echo charset2unicode($a, 'AUTO', true);
		}
	}
}

# Une fonction stockee en base de donnees ?

function ajax_page_sql($id, $exclus, $col, $id_ajax_fonc, $type, $rac)
{
	global $connect_id_auteur;
	$res = spip_query("SELECT variables FROM spip_ajax_fonc	WHERE id_ajax_fonc =" . spip_abstract_quote($id_ajax_fonc) . " AND id_auteur=$connect_id_auteur");
	if ($row = spip_fetch_array($res)) {
		
		$variables = unserialize($row["variables"]);
		while (list($i, $k) = each($variables)) {
			$$i = $k;
			
		}
		include_spip('inc/presentation');		
		// Appliquer la fonction
		if ($fonction == "afficher_articles") {
			afficher_articles ($titre_table, $requete,
				$afficher_visites, $afficher_auteurs);
		}

		elseif ($fonction == "afficher_articles_trad") {
			afficher_articles_trad ($titre_table, $requete,
				$afficher_visites, $afficher_auteurs);
		}
		elseif ($fonction == "afficher_groupe_mots") {
			include_spip('inc/texte');
			include_spip('inc/mots');
			afficher_groupe_mots ($id_groupe);
		}
		
	}
}

function ajax_page_test($id, $exclus, $col, $id_ajax_fonc, $type, $rac)
{
	# tester si ca fonctionne pour ce brouteur
	// (si on arrive la c'est que c'est bon, donc poser le cookie)
	spip_setcookie('spip_accepte_ajax', 1);

}

# Un moteur de recherche ?
function ajax_page_recherche($id, $exclus, $col, $id_ajax_fonc, $type, $rac)
{

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

	# afficher un mini-navigateur de rubriques

function ajax_page_aff_rubrique($id, $exclus, $col, $id_ajax_fonc, $type, $rac)
{
		include_spip('inc/texte');
		include_spip('inc/mini_nav');
		echo mini_nav ($id, "choix_parent", "this.form.id_rubrique.value=::sel::;this.form.titreparent.value='::sel2::';findObj('selection_rubrique').style.display='none';", $exclus, $rac);

}

# afficher les sous-rubriques d'une rubrique (composant du mini-navigateur)

function ajax_page_aff_rub($id, $exclus, $col, $id_ajax_fonc, $type, $rac)
{
		include_spip('inc/texte');
		include_spip('inc/mini_nav');
		echo mini_afficher_rubrique ($id, 
					     htmlentities($rac),
					     "", $col, $exclus);
}

	# petit moteur de recherche sur les rubriques

function ajax_page_aff_nav_recherche($id, $exclus, $col, $id_ajax_fonc, $type, $rac)
{
	include_spip('inc/texte');
	include_spip('inc/mini_nav');
	echo mini_nav ($id, "aff_nav_recherche", 
			"document.location.href='" . generer_url_ecrire('naviguer', "id_rubrique=::sel::") .
			"';", 0, true);
}

# Affiche les infos d'une rubrique selectionnee dans le mini navigateur

function ajax_page_aff_info($id, $exclus, $col, $id_ajax_fonc, $type, $rac)
{
  global $couleur_foncee,$spip_display,$spip_lang_right ;
		include_spip('inc/texte');
		if ($type == "rubrique") {
			$res = spip_query("SELECT titre, descriptif FROM spip_rubriques WHERE id_rubrique = $id");
			if ($row = spip_fetch_array($res)) {
				$titre = typo($row["titre"]);
				$descriptif = propre($row["descriptif"]);
			} else {
				$titre = _T('info_racine_site');
			}
		} else
			$titre = '';
		
		$rac = htmlentities($rac);
		echo "<div style='display: none;'>";
		echo "<input type='text' id='".$rac."_sel' value='$id' />";
		echo "<input type='text' id='".$rac."_sel2' value=\"".entites_html($titre)."\" />";
		echo "</div>";

		echo "<div class='arial2' style='padding: 5px; background-color: white; border: 1px solid $couleur_foncee; border-top: 0px;'>";
		if ($type == "rubrique" AND $spip_display != 1 AND $spip_display!=4 AND $GLOBALS['meta']['image_process'] != "non") {
			$logo_f = charger_fonction('chercher_logo', 'inc');
			if ($res = $logo_f($id, 'id_rubrique', 'on'))
			    if ($res = decrire_logo("id_rubrique", 'on', $id, 100, 48, $res))
				echo  "<div style='float: $spip_lang_right; margin-$spip_lang_right: -5px; margin-top: -5px;'>$res</div>";
		}

		echo "<div><p><b>$titre</b></p></div>";
		if (strlen($descriptif) > 0) echo "<div>$descriptif</div>";

		echo "<div style='text-align: $spip_lang_right;'>";
		
				# ce lien provoque la selection (directe) de la rubrique cliquee
				$onClick = "findObj('id_parent').value=$id;";
				# et l'affichage de son titre dans le bandeau
				$onClick .= "findObj('titreparent').value='"
					. strtr(
						str_replace("'", "&#8217;",
						str_replace('"', "&#34;",
							textebrut($titre))),
						"\n\r", "  ")."';";
				$onClick .= "findObj('selection_rubrique').style.display='none';";
				$onClick .= "return false;";
		
		
		echo "<input type='submit' value='"._T('bouton_choisir')."' onClick=\"$onClick\" class=\"fondo\" />";
		echo "</div>";


		echo "</div>";

}
?>
