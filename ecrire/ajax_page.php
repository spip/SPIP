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
			
		}

	}

if ($GLOBALS["recherche_sugg"]) {
	$recherche_sugg = $GLOBALS["recherche_sugg"];

	include_ecrire("inc_charsets.php3");

	// Eviter les symboles '%', caracteres SQL speciaux
	$recherche_sugg = str_replace("%","\%",$recherche_sugg);
	$recherche_sugg = translitteration($recherche_sugg);
	$rech2 = split("[[:space:]]+", $recherche_sugg);
	if ($rech2)
		$where = " (spip_index_dico.dico LIKE '".join("%' AND spip_index_dico.dico LIKE '", $rech2)."%') ";
	else
		$where = " 1=2";

	$result = spip_query("SELECT spip_index_dico.dico AS dico, spip_index_articles.points AS points FROM spip_index_dico, spip_index_articles WHERE ($where) AND spip_index_dico.hash = spip_index_articles.hash");
	while ($row = spip_fetch_array($result)) {
		$dico = $row["dico"]; 
		$points = $row["points"];
		$total["$dico"] = $total["dico"] + $points;
	}

	$result = spip_query("SELECT spip_index_dico.dico AS dico, spip_index_mots.points AS points FROM spip_index_dico, spip_index_mots WHERE ($where) AND spip_index_dico.hash = spip_index_mots.hash");
	while ($row = spip_fetch_array($result)) {
		$dico = $row["dico"]; 
		$points = $row["points"];
		$total["$dico"] = $total["dico"] + $points;
	}
	
	$result = spip_query("SELECT spip_index_dico.dico AS dico, spip_index_rubriques.points AS points FROM spip_index_dico, spip_index_rubriques WHERE ($where) AND spip_index_dico.hash = spip_index_rubriques.hash");
	while ($row = spip_fetch_array($result)) {
		$dico = $row["dico"]; 
		$points = $row["points"];
		$total["$dico"] = $total["dico"] + $points;
	}
	
	$result = spip_query("SELECT spip_index_dico.dico AS dico, spip_index_auteurs.points AS points FROM spip_index_dico, spip_index_auteurs WHERE ($where) AND spip_index_dico.hash = spip_index_auteurs.hash");
	while ($row = spip_fetch_array($result)) {
		$dico = $row["dico"]; 
		$points = $row["points"];
		$total["$dico"] = $total["dico"] + $points;
	}
	
	$result = spip_query("SELECT spip_index_dico.dico AS dico, spip_index_breves.points AS points FROM spip_index_dico, spip_index_breves WHERE ($where) AND spip_index_dico.hash = spip_index_breves.hash");
	while ($row = spip_fetch_array($result)) {
		$dico = $row["dico"]; 
		$points = $row["points"];
		$total["$dico"] = $total["dico"] + $points;
	}
	
	if (count($total)) arsort($total);
	while (list($k, $i) = each ($total) AND $compt < 20) {
		echo "<div><a href=\"recherche.php3?recherche=$k\" style='color: black;'>$k</a> ($i)</div>";
		$compt ++;
	}

	
	
}

if ($GLOBALS["recherche"]) {
	$recherche = $GLOBALS["recherche"];



	$query_articles = "SELECT * FROM spip_articles WHERE";
	$query_breves = "SELECT * FROM spip_breves WHERE ";
	$query_rubriques = "SELECT * FROM spip_rubriques WHERE ";
	$query_sites = "SELECT * FROM spip_syndic WHERE ";
	
	if (ereg("^[0-9]+$", $recherche)) {
		$query_articles .= " (id_article = $recherche) OR ";
		$query_breves .= " (id_breve = $recherche) OR ";
		$query_rubriques .= " (id_rubrique = $recherche) OR ";
		$query_sites .= " (id_syndic = $recherche) OR ";
	}
	
	// Eviter les symboles '%', caracteres SQL speciaux
	$recherche = str_replace("%","\%",$recherche);
	$rech2 = split("[[:space:]]+", $recherche);
	if ($rech2)
		$where = " (titre LIKE '%".join("%' AND titre LIKE '%", $rech2)."%') ";
	else
		$where = " 1=2";
	
	$query_articles .= " $where ORDER BY date_modif DESC";
	$query_breves .= " $where ORDER BY maj DESC";
	$query_rubriques .= " $where ORDER BY maj DESC";
	
	$query_sites .= " $where ORDER BY maj DESC";
	$query_sites  = ereg_replace("titre LIKE", "nom_site LIKE", $query_sites);
	
	$activer_moteur = (lire_meta('activer_moteur') == 'oui');
	if ($activer_moteur) {	// texte integral
		include_ecrire ('inc_index.php3');
		list($hash_recherche,) = requete_hash ($recherche);
		$query_articles_int = requete_txt_integral('article', $hash_recherche);
		$query_breves_int = requete_txt_integral('breve', $hash_recherche);
		$query_rubriques_int = requete_txt_integral('rubrique', $hash_recherche);
		$query_sites_int = requete_txt_integral('syndic', $hash_recherche);
		$query_auteurs_int = requete_txt_integral('auteur', $hash_recherche);
	}
	
	if ($query_articles)
		$nba = afficher_articles (_T('info_articles_trouves'), $query_articles, false, false);
	if ($activer_moteur) {
		if ($nba) {
			$doublons = join($nba, ",");
			$query_articles_int = ereg_replace ("WHERE", "WHERE objet.id_article NOT IN ($doublons) AND", $query_articles_int);
		}
		$nba1 = afficher_articles (_T('info_articles_trouves_dans_texte'), $query_articles_int, false, false);
	}
	
	if ($query_breves)
		$nbb = afficher_breves (_T('info_breves_touvees'), $query_breves, true);
	if ($activer_moteur) {
		if ($nbb) {
			$doublons = join($nbb, ",");
			$query_breves_int = ereg_replace ("WHERE", "WHERE objet.id_breve NOT IN ($doublons) AND", $query_breves_int);
		}
		$nbb1 = afficher_breves (_T('info_breves_touvees_dans_texte'), $query_breves_int, true);
	}
	
	if ($query_rubriques)
		$nbr = afficher_rubriques (_T('info_rubriques_trouvees'), $query_rubriques);
	if ($activer_moteur) {
		if ($nbr) {
			$doublons = join($nbr, ",");
			$query_rubriques_int = ereg_replace ("WHERE", "WHERE objet.id_rubrique NOT IN ($doublons) AND", $query_rubriques_int);
		}
		$nbr1 = afficher_rubriques (_T('info_rubriques_trouvees_dans_texte'), $query_rubriques_int);
	}
	
	if ($activer_moteur)
		$nbt = afficher_auteurs (_T('info_auteurs_trouves'), $query_auteurs_int);
	
	if ($query_sites)
		$nbs = afficher_sites (_T('info_sites_trouves'), $query_sites);
	if ($activer_moteur) {
		if ($nbs) {
			$doublons = join($nbs, ",");
			$query_sites_int = ereg_replace ("WHERE", "WHERE objet.id_syndic NOT IN ($doublons) AND", $query_sites_int);
		}
		$nbs1 = afficher_sites (_T('info_sites_trouves_dans_texte'), $query_sites_int);
	}
	
	if (!$nba AND !$nba1 AND !$nbb AND !$nbb1 AND !$nbr AND !$nbr1 AND !$nbt AND !$nbs AND !$nbs1) {
		echo "<FONT FACE='Verdana,Arial,Sans,sans-serif'>"._T('avis_aucun_resultat')."</FONT><P>";
	}
}



?>