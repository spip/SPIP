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

$var_f = find_in_path("inc_articles_edit.php");
if ($var_f)
  include($var_f);
 else
   include_ecrire(_DIR_INCLUDE . "inc_articles_edit.php");


// securite
$id_article = intval($id_article);
$id_rubrique = intval($id_rubrique);
$lier_trad = intval($lier_trad);
unset ($flag_editable);

//
// Creation de l'objet article
//

if ($id_article) {
	// Recuperer les donnees de l'article
	$query = "SELECT * FROM spip_articles WHERE id_article=$id_article";
	$result = spip_query($query);

	if ($row = spip_fetch_array($result)) {
		$id_article = $row["id_article"];
		$surtitre = $row["surtitre"];
		$titre = $row["titre"];
		$soustitre = $row["soustitre"];
		$id_rubrique = $row["id_rubrique"];
		$id_secteur = $row['id_secteur'];
		$descriptif = $row["descriptif"];
		$nom_site = $row["nom_site"];
		$url_site = $row["url_site"];
		$chapo = $row["chapo"];
		$texte = $row["texte"];
		$ps = $row["ps"];
		$date = $row["date"];
		$statut = $row['statut'];
		$date_redac = $row['date_redac'];
	    	if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})",$date_redac,$regs)){
		        $mois_redac = $regs[2];
		        $jour_redac = $regs[3];
		        $annee_redac = $regs[1];
		        if ($annee_redac > 4000) $annee_redac -= 9000;
		}
		$extra=$row["extra"];

		$query = "SELECT * FROM spip_auteurs_articles WHERE id_article=$id_article AND id_auteur=$connect_id_auteur";
		$result_auteur = spip_query($query);
		$flag_auteur = (spip_num_rows($result_auteur) > 0);

		$flag_editable = (acces_rubrique($id_rubrique) OR ($flag_auteur > 0 AND ($statut == 'prepa' OR $statut == 'prop' OR $new == 'oui')));
	}
}
else if ($new=='oui') {
	if ($lier_trad) {
		// Pas de langue choisie par defaut
		$changer_lang = '';

		// Recuperer les donnees de la traduction
		$query = "SELECT * FROM spip_articles WHERE id_article=$lier_trad";
		$result = spip_query($query);
	
		if ($row = spip_fetch_array($result)) {
			$surtitre = $row["surtitre"];
			$titre = filtrer_entites(_T('info_nouvelle_traduction')).' '.$row["titre"];
			$soustitre = $row["soustitre"];
			$id_rubrique_trad = $row["id_rubrique"];
			$descriptif = $row["descriptif"];
			$nom_site = $row["nom_site"];
			$url_site = $row["url_site"];
			$chapo = $row["chapo"];
			$texte = $row["texte"];
			$ps = $row["ps"];
			$date = $row["date"];
			$date_redac = $row['date_redac'];
			if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})",$date_redac,$regs)) {
				$mois_redac = $regs[2];
				$jour_redac = $regs[3];
				$annee_redac = $regs[1];
				if ($annee_redac > 4000) $annee_redac -= 9000;
			}
			$extra = $row["extra"];
		}
		$langues_autorisees = lire_meta('langues_multilingue');
		
		// Regler la langue, si possible
		if (ereg(",$spip_lang,", ",$langues_autorisees,")) {
			if (lire_meta('multi_articles') == 'oui') {
				// Si le menu de langues est autorise sur les articles,
				// on peut changer la langue quelle que soit la rubrique
				$changer_lang = $spip_lang;
			}
			else if (lire_meta('multi_rubriques') == 'oui') {
				// Chercher la rubrique la plus adaptee pour accueillir l'article
				if (lire_meta('multi_secteurs') == 'oui') 
					$id_parent = 0;
				else {
					$query = "SELECT id_parent FROM spip_rubriques WHERE id_rubrique=$id_rubrique";
					$row_rub = spip_fetch_array(spip_query($query));
					$id_parent = $row_rub['id_parent'];
				}
				$query = "SELECT id_rubrique FROM spip_rubriques WHERE lang='$spip_lang' AND id_parent=$id_parent";
				if ($row_rub = spip_fetch_array(spip_query($query))) {
					$id_rubrique = $id_secteur = $row_rub['id_rubrique'];
					$changer_lang = 'herit';
				}
			}
		}
	}
	else {
		// Nouvel article : titre par defaut
		$titre = filtrer_entites(_T('info_nouvel_article'));
		$onfocus = " onfocus=\"if(!antifocus){this.value='';antifocus=true;}\"";
	}
	if (!$id_secteur) {
		$row_rub = spip_fetch_array(spip_query("SELECT id_secteur FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
		$id_secteur = $row_rub['id_secteur'];
	}
	$flag_editable = true;
}

if (!$flag_editable) {
	die ("<H3>"._T('info_acces_interdit')."</H3>");
}


spip_query("UPDATE spip_articles SET date_modif=NOW(), auteur_modif=$connect_id_auteur WHERE id_article=$id_article");

if (function_exists('affiche_articles_edit'))
  $var_nom = 'affiche_articles_edit';
 else
  $var_nom = 'affiche_articles_edit_dist';

$var_nom($flag_editable, $id_article, $id_rubrique, $titre, $soustitre, $surtitre, $descriptif, $nom, $url, $chapo, $texte, $ps, $new, $nom_site, $url_site, $champs_extra, $extra, $id_secteur, $date, $onfocus, $lier_trad);
?>
