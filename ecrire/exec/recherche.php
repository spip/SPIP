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
include_spip('inc/presentation');
include_spip('inc/sites_voir');

function exec_recherche_dist()
{
	global $couleur_foncee, $recherche;

	$recherche_aff = entites_html($recherche);

	debut_page(_T('titre_page_recherche', array('recherche' => $recherche_aff)));
 
	debut_gauche();

	if ($recherche) {
	  $onfocus = "this.value='" . addslashes($recherche) . "';";
	} else {
	  $recherche_aff = _T('info_rechercher');
	  $onfocus = "this.value='';";
	}
	echo "<form method='get' style='margin: 0px;' action='" . generer_url_ecrire("recherche","") . "'>";
	echo "<input type='hidden' name='exec' value='recherche' />";
	echo '<input type="text" size="10" value="'.$recherche_aff.'" name="recherche" class="spip_recherche" accesskey="r" onfocus="'.$onfocus . '">';
	echo "</form>";

	debut_droite();

	if (strlen($recherche) > 0) {

	echo "<FONT FACE='Verdana,Arial,Sans,sans-serif'><B>"._T('info_resultat_recherche')."</B><BR>";
	echo "<FONT SIZE=5 COLOR='$couleur_foncee'><B>$recherche_aff</B></FONT><p>";

	$query_articles['FROM'] = 'spip_articles AS articles';
	$query_breves['FROM'] = 'spip_breves';
	$query_rubriques['FROM'] = 'spip_rubriques';
	$query_sites['FROM'] = 'spip_syndic';
	$testnum = ereg("^[0-9]+$", $recherche);

	// Eviter les symboles '%', caracteres SQL speciaux

	$where = split("[[:space:]]+", $recherche);
	if ($where) {
		foreach ($where as $k => $v) 
		  $where[$k] = "'%" . substr(str_replace("%","\%", spip_abstract_quote($v)),1,-1) . "%'";
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

	if ($activer_moteur) {
		if ($nba) {
			$doublons = join($nba, ",");
			$query_articles_int['WHERE'] .= " AND objet.id_article NOT IN ($doublons)";
		}
		$nba1 = afficher_articles (_T('info_articles_trouves_dans_texte'), $query_articles_int);
	}
	
	$nbb = afficher_breves (_T('info_breves_touvees'), $query_breves, true);

	if ($activer_moteur) {
		if ($nbb) {
			$doublons = join($nbb, ",");
			$query_breves_int["WHERE"].= " AND objet.id_breve NOT IN ($doublons)";
		}
		$nbb1 = afficher_breves (_T('info_breves_touvees_dans_texte'), $query_breves_int, true);
	}

	$nbr = afficher_rubriques (_T('info_rubriques_trouvees'), $query_rubriques);
	if ($activer_moteur) {
		if ($nbr) {
			$doublons = join($nbr, ",");
			$query_rubriques_int["WHERE"].= " AND objet.id_rubrique NOT IN ($doublons)";
		}
		$nbr1 = afficher_rubriques (_T('info_rubriques_trouvees_dans_texte'), $query_rubriques_int);
	}
	
	$nbt = afficher_auteurs (_T('info_auteurs_trouves'), $query_auteurs_int);
	
	$nbs = afficher_sites (_T('info_sites_trouves'), $query_sites);
	if ($activer_moteur) {
		if ($nbs) {
			$doublons = join($nbs, ",");
			$query_sites_int["WHERE"].= " AND objet.id_syndic NOT IN ($doublons)";
		}
		$nbs1 = afficher_sites (_T('info_sites_trouves_dans_texte'), $query_sites_int);
	}
	
	if (!$nba AND !$nba1 AND !$nbb AND !$nbb1 AND !$nbr AND !$nbr1 AND !$nbt AND !$nbs AND !$nbs1) {
		echo "<FONT FACE='Verdana,Arial,Sans,sans-serif'>"._T('avis_aucun_resultat')."</FONT><P>";
	}

	}

fin_page();
}
?>
