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

  $recherche = addslashes(entites_html($recherche));


 debut_page(_T('titre_page_recherche', array('recherche' => $recherche)));
 
 debut_gauche();

 $recherche_aff = _T('info_rechercher');
 $onfocus = "onfocus=this.value='';";
			echo "<form method='get' style='margin: 0px;' action='" . generer_url_ecrire("recherche","") . "'>";
			echo "<input type='hidden' name='exec' value='recherche' />";
			echo '<input type="text" size="10" value="'.$recherche_aff.'" name="recherche" class="spip_recherche" accesskey="r" '.$onfocus.'>';
			echo "</form>";



debut_droite();

if (strlen($recherche) > 0) {

	echo "<FONT FACE='Verdana,Arial,Sans,sans-serif'><B>"._T('info_resultat_recherche')."</B><BR>";
	echo "<FONT SIZE=5 COLOR='$couleur_foncee'><B>$recherche</B></FONT><p>";

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
	
	$activer_moteur = ($GLOBALS['meta']['activer_moteur'] == 'oui');
	if ($activer_moteur) {	// texte integral
		include_spip('inc/indexation');
		list($hash_recherche,) = requete_hash ($recherche);
		$query_articles_int = requete_txt_integral('spip_articles', $hash_recherche);
		$query_breves_int = requete_txt_integral('spip_breves', $hash_recherche);
		$query_rubriques_int = requete_txt_integral('spip_rubriques', $hash_recherche);
		$query_sites_int = requete_txt_integral('spip_syndic', $hash_recherche);
		$query_auteurs_int = requete_txt_integral('spip_auteurs', $hash_recherche);
	}
	
	if ($query_articles)
		$nba = afficher_articles (_T('info_articles_trouves'), $query_articles);
	if ($activer_moteur) {
		if ($nba) {
			$doublons = join($nba, ",");
			$query_articles_int = ereg_replace ("WHERE", "WHERE objet.id_article NOT IN ($doublons) AND", $query_articles_int);
		}
		$nba1 = afficher_articles (_T('info_articles_trouves_dans_texte'), $query_articles_int);
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

echo "<p>";

fin_page();
}
?>
