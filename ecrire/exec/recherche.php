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
include_spip('inc/presentation');
include_spip('inc/sites_voir');

// http://doc.spip.org/@exec_recherche_dist
function exec_recherche_dist()
{
	global $couleur_foncee, $recherche;

	$recherche_aff = entites_html($recherche);

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_recherche', array('recherche' => $recherche_aff)));
 
	debut_gauche();

	if ($recherche) {
	  $onfocus = "this.value='" . addslashes($recherche) . "';";
	} else {
	  $recherche_aff = _T('info_rechercher');
	  $onfocus = "this.value='';";
	}
	echo "<form method='get' style='margin: 0px;' action='" . generer_url_ecrire("recherche","") . "'><div>";
	echo "<input type='hidden' name='exec' value='recherche' />";
	echo '<input type="text" size="10" value="'.$recherche_aff.'" name="recherche" class="spip_recherche" accesskey="r" onfocus="'.$onfocus . '" />';
	echo "</div></form>";

	debut_droite();

	if (strlen($recherche) > 0) {

	echo "<span style='font-family: Verdana,Arial,Sans,sans-serif'><b>"._T('info_resultat_recherche')."</b></span><br />";
	echo "<span style='font-size: 20px; font-family: Verdana,Arial,Sans,sans-serif; color: $couleur_foncee'><b>$recherche_aff</b></span>";

	$query_articles['FROM'] = 'spip_articles AS articles';
	$query_breves['FROM'] = 'spip_breves';
	$query_rubriques['FROM'] = 'spip_rubriques';
	$query_sites['FROM'] = 'spip_syndic';
	$testnum = ereg("^[0-9]+$", $recherche);

	// Eviter les symboles '%', caracteres SQL speciaux

	$where = split("[[:space:]]+", $recherche);
	if ($where) {
		foreach ($where as $k => $v) 
		  $where[$k] = "'%" . substr(str_replace("%","\%", _q($v)),1,-1) . "%'";
		$where = ($testnum ? "OR " : '') .
		  ("(titre LIKE " . join(" AND titre LIKE ", $where) . ")");
	}

	$query_articles['WHERE']= ($testnum ? "(articles.id_article = $recherche)" :'') . $where;
	$query_breves['WHERE']= ($testnum ? "(id_breve = $recherche)" : '') . $where;
	$query_rubriques['WHERE']= ($testnum ? "(id_rubrique = $recherche)" : '') . $where;
	$query_sites['WHERE']= ($testnum ? "(id_syndic = $recherche)" : '') . ereg_replace("titre LIKE", "nom_site LIKE",$where);

	$query_articles['ORDER BY']= "date_modif DESC";
	$query_breves['ORDER BY']= "maj DESC";
	$query_rubriques['ORDER BY']= "maj DESC";
	$query_sites['ORDER BY']= "maj DESC";
	
	$activer_moteur = ($GLOBALS['meta']['activer_moteur'] == 'oui');

	if ($activer_moteur) {	// texte integral
		include_spip('inc/indexation');
		list($hash_recherche,) = requete_hash(str_replace("%","\%",$recherche));
		$query_articles_int = requete_txt_integral('spip_articles', $hash_recherche);
		$query_breves_int = requete_txt_integral('spip_breves', $hash_recherche);
		$query_rubriques_int = requete_txt_integral('spip_rubriques', $hash_recherche);
		$query_sites_int = requete_txt_integral('spip_syndic', $hash_recherche);
		$query_auteurs_int = requete_txt_integral('spip_auteurs', $hash_recherche);
	}
	
	$nba = afficher_articles (_T('info_articles_trouves'), $query_articles);
	echo $nba;

	if ($activer_moteur) {
		if ($nba) 
			$query_articles_int['WHERE'] .= " AND NOT (" . $query_articles['WHERE'] . ")";

		$nba1 = afficher_articles (_T('info_articles_trouves_dans_texte'), $query_articles_int);
		echo $nba1;
	}
	
	$nbb = afficher_breves ('<b>' . _T('info_breves_touvees') . '</b>', $query_breves, true);
	echo $nbb;
	if ($activer_moteur) {
		if ($nbb) {
			$query_breves_int["WHERE"].= " AND NOT (" . $query_breves['WHERE'] . ")";
		}
		$nbb1 = afficher_breves ('<b>' . _T('info_breves_touvees_dans_texte') . '</b>', $query_breves_int, true);
		echo $nbb1;
	}

	$nbr = afficher_rubriques ('<b>' . _T('info_rubriques_trouvees') . '</b>', $query_rubriques);
	echo $nbr;
	if ($activer_moteur) {
		if ($nbr) {
			$query_rubriques_int["WHERE"].= " AND NOT (" . $query_rubriques['WHERE'] . ")";
		}
		$nbr1 = afficher_rubriques ('<b>' . _T('info_rubriques_trouvees_dans_texte') . '</b>', $query_rubriques_int);
		echo $nbr1;
	}
	
	$nbt = afficher_auteurs (_T('info_auteurs_trouves'), $query_auteurs_int);
	
	$nbs = afficher_sites ('<b>' . _T('info_sites_trouves') . '</b>', $query_sites);
	echo $nbs;
	if ($activer_moteur) {
		if ($nbs) {
			$query_sites_int["WHERE"].=  " AND NOT (" . $query_sites['WHERE'] . ")";
		}
		$nbs1 = afficher_sites ('<b>' . _T('info_sites_trouves_dans_texte') . '</b>', $query_sites_int);
		echo $nbs1;
	}
	
	if (!$nba AND !$nba1 AND !$nbb AND !$nbb1 AND !$nbr AND !$nbr1 AND !$nbt AND !$nbs AND !$nbs1) {
		echo "<p style='font-face: Verdana,Arial,Sans,sans-serif'>"._T('avis_aucun_resultat')."</p>";
	}
	}
echo fin_gauche(), fin_page();
}


// http://doc.spip.org/@afficher_auteurs
function afficher_auteurs ($titre_table, $requete) {

	if (!$requete['SELECT']) $requete['SELECT'] = '*' ;

	$tous_id = array();
	$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM " . $requete['FROM'] . ($requete['WHERE'] ? (' WHERE ' . $requete['WHERE']) : '') . ($requete['GROUP BY'] ? (' GROUP BY ' . $requete['GROUP BY']) : '')));
	if (! ($cpt = $cpt['n'])) return 0 ;
	if ($requete['LIMIT']) $cpt = min($requete['LIMIT'], $cpt);

	$tmp_var = 't_' . substr(md5(join('', $requete)), 0, 4);
	$nb_aff = floor(1.5 * _TRANCHES);
	$deb_aff = intval(_request($tmp_var));
	$tranches = '';
	if ($cpt > $nb_aff) {
		$nb_aff = (_TRANCHES); 
		$tranches = afficher_tranches_requete($cpt, $tmp_var, '', $nb_aff);
	}

	debut_cadre_relief("auteur-24.gif");

	if ($titre_table) {
			echo "<p><table width='100%' cellpadding='0' cellspacing='0' border='0'>";
			echo "<tr><td style='width: 100%'>";
			echo "<table width='100%' cellpadding='3' cellspacing='0' border='0'>";
			echo "<tr bgcolor='#333333'><td style='width: 100%' colspan='5'><font face='Verdana,Arial,Sans,sans-serif' size='3' color='#FFFFFF'>";
			echo "<b>$titre_table</b></font></td></tr>";
		}
	else {
			echo "<p><table width='100%' cellpadding='3' cellspacing='0' border='0'>";
		}

	echo $tranches;

	$result = spip_query("SELECT " . $requete['SELECT'] . " FROM " . $requete['FROM'] . ($requete['WHERE'] ? (' WHERE ' . $requete['WHERE']) : '') . ($requete['GROUP BY'] ? (' GROUP BY ' . $requete['GROUP BY']) : '') . ($requete['ORDER BY'] ? (' ORDER BY ' . $requete['ORDER BY']) : '') . " LIMIT " . ($deb_aff >= 0 ? "$deb_aff, $nb_aff" : ($requete['LIMIT'] ? $requete['LIMIT'] : "99999")));

	$table = array();
	while ($row = spip_fetch_array($result)) {
		$tous_id[] = $row['id_auteur'];
		$formater_auteur = charger_fonction('formater_auteur', 'inc');
		$table[]= $formater_auteur($row['id_auteur']);
	}
	spip_free_result($result);
	$largeurs = array(20, 20, 200, 20, 50);
	$styles = array('','','arial2','arial1','arial1');
	echo afficher_liste($largeurs, $table, $styles);

	if ($titre_table) echo "</table></td></tr>";
	echo "</table>";
	fin_cadre_relief();

	return $cpt;
}
?>
