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
include_spip('inc/revisions');

// http://doc.spip.org/@exec_articles_versions_dist
function exec_articles_versions_dist()
{
  global
    $champs_extra,
    $chapo,
    $connect_id_auteur,
    $descriptif,
    $dir_lang,
    $id_article,
    $id_diff,
    $id_version,
    $les_notes,
    $nom_site,
    $options,
    $ps,
    $soustitre,
    $surtitre,
    $texte,
    $titre,
    $url_site;


//
// Lire l'article
//

    $id_article = intval($id_article);
    $result = spip_query("SELECT * FROM spip_articles WHERE id_article='$id_article'");

if ($row = spip_fetch_array($result)) {
	$id_article = $row["id_article"];
	$id_rubrique = $row["id_rubrique"];
	$titre = $row["titre"];
	$date = $row["date"];
	$statut_article = $row["statut"];
	$maj = $row["maj"];
	$date_redac = $row["date_redac"];
	$visites = $row["visites"];
	$referers = $row["referers"];
	$extra = $row["extra"];
	$id_trad = $row["id_trad"];
}

if (!($id_version = intval($id_version))) {
	$id_version = $row['id_version'];
}
$textes = recuperer_version($id_article, $id_version);

$id_diff = intval($id_diff);
if (!$id_diff) {
	$diff_auto = true;
	$row = spip_fetch_array(spip_query("SELECT id_version FROM spip_versions WHERE id_article=$id_article AND id_version<$id_version ORDER BY id_version DESC LIMIT 1"));
	if ($row) $id_diff = $row['id_version'];
}

//
// Calculer le diff
//

if ($id_version && $id_diff) {
	include_spip('inc/diff');

	// code a unifier avec suivi_versions
	if ($id_diff > $id_version) {
		$t = $id_version;
		$id_version = $id_diff;
		$id_diff = $t;
		$old = $textes;
		$new = recuperer_version($id_article, $id_version);
	}
	else {
		$old = recuperer_version($id_article, $id_diff);
		$new = $textes;
	}

	$textes = array();
	$champs = array('surtitre', 'titre', 'soustitre', 'descriptif', 'nom_site', 'url_site', 'chapo', 'texte', 'ps');

	foreach ($champs as $champ) {
		// Si on n'en a qu'un, pas de modif, on peut afficher directement les donnees courantes ; mais il faut remonter a la precedente version disposant de ce champ
		$id_ref = $id_diff-1;
		while (!isset($old[$champ])
		AND $id_ref>0) {
			$prev = recuperer_version($id_article, $id_ref--); 
			if (isset($prev[$champ]))
				$old[$champ] = $prev[$champ];
		}

		if (!strlen($new[$champ]) && !strlen($old[$champ])) continue;

		if (!isset($new[$champ])) {
			$textes[$champ] = $old[$champ];
		} else {
			$diff = new Diff(new DiffTexte);
			$textes[$champ] = afficher_diff($diff->comparer(preparer_diff($new[$champ]), preparer_diff($old[$champ])));
		}
	}
}

if (is_array($textes))
foreach ($textes as $var => $t) $$var = $t;



debut_page(_T('info_historique')." &laquo; $titre &raquo;", "naviguer", "articles", "", $id_rubrique);

debut_grand_cadre();

echo afficher_hierarchie($id_rubrique);

fin_grand_cadre();



//////////////////////////////////////////////////////
// Affichage de la colonne de gauche
//

debut_gauche();

echo bloc_des_raccourcis(icone_horizontale(_T('icone_retour_article'), generer_url_ecrire("articles","id_article=$id_article"), "article-24.gif","rien.gif", false) .
		    icone_horizontale(_T('icone_suivi_revisions'), generer_url_ecrire("suivi_revisions",""), "historique-24.gif","rien.gif", false));



//////////////////////////////////////////////////////
// Affichage de la colonne de droite
//

debut_droite();

changer_typo('','article'.$id_article);

echo "<a name='diff'></a>\n";

debut_cadre_relief();

//
// Titre, surtitre, sous-titre
//

if ($statut_article=='publie') {
	$logo_statut = "puce-verte.gif";
}
else if ($statut_article=='prepa') {
	$logo_statut = "puce-blanche.gif";
}
else if ($statut_article=='prop') {
	$logo_statut = "puce-orange.gif";
}
else if ($statut_article == 'refuse') {
	$logo_statut = "puce-rouge.gif";
}
else if ($statut_article == 'poubelle') {
	$logo_statut = "puce-poubelle.gif";
}


echo "\n<table cellpadding='0' cellspacing='0' border='0' width='100%'>";
echo "<tr width='100%'><td width='100%' valign='top'>";
if ($surtitre) {
	echo "<span $dir_lang><font face='arial,helvetica' size='3'><b>";
	echo typo($surtitre);
	echo "</b></font></span>\n";
}
gros_titre($titre, $logo_statut);

if ($soustitre) {
	echo "<span $dir_lang><font face='arial,helvetica' size='3'><b>";
	echo typo($soustitre);
	echo "</b></font></span>\n";
}


if ($descriptif OR $url_site OR $nom_site) {
	echo "<p><div align='left' style='padding: 5px; border: 1px dashed #aaaaaa; background-color: #e4e4e4;' $dir_lang>";
	echo "<font size='2' face='Verdana,Arial,Sans,sans-serif'>";
	$texte_case = ($descriptif) ? "{{"._T('info_descriptif')."}} $descriptif\n\n" : '';
	$texte_case .= ($nom_site.$url_site) ? "{{"._T('info_urlref')."}} [".$nom_site."->".$url_site."]" : '';
	echo propre($texte_case);
	echo "</font>";
	echo "</div>";
}

echo "</td>";

echo "<td align='center'>";

// L'article est-il editable ?
 $result_auteur = spip_query("SELECT * FROM spip_auteurs_articles WHERE id_article=$id_article AND id_auteur=$connect_id_auteur");

$flag_auteur = (spip_num_rows($result_auteur) > 0);
$flag_editable = (acces_rubrique($id_rubrique)
	OR ($flag_auteur AND ($statut_article == 'prepa' OR $statut_article == 'prop' OR $statut_article == 'poubelle')));

if ($flag_editable)
	icone(_T('icone_modifier_article').'<br />('._T('version')." $id_version)", generer_url_ecrire("articles_edit","id_article=$id_article&id_version=$id_version"), "article-24.gif", "edit.gif");

echo "</td>";

echo "</tr></table>";


//////////////////////////////////////////////////////
// Affichage des versions
//

debut_cadre_relief();

$result = spip_query("SELECT id_version, titre_version, date, id_auteur	FROM spip_versions WHERE id_article=$id_article ORDER BY id_version DESC");

echo "<ul class='verdana3'>";
while ($row = spip_fetch_array($result)) {
	echo "<li>\n";
	$date = affdate_heure($row['date']);
	$version_aff = $row['id_version'];
	$titre_version = typo($row['titre_version']);
	$titre_aff = $titre_version ? $titre_version : $date;
	if ($version_aff != $id_version) {
		$lien = parametre_url(self(), 'id_version', $version_aff);
		$lien = parametre_url($lien, 'id_diff', '');
		echo "<a href='".($lien.'#diff')."' title=\""._T('info_historique_affiche')."\">$titre_aff</a>";
	}
	else {
		echo "<b>$titre_aff</b>";
	}

	if (isset($row['id_auteur'])) {
		$t = spip_fetch_array(spip_query("SELECT nom FROM spip_auteurs WHERE id_auteur='".addslashes($row['id_auteur'])."'"));
		if ($t)
			echo " (".typo($t['nom']).")";
		else
			echo " (".$row['id_auteur'].")"; #IP edition anonyme
	}

	if ($version_aff != $id_version) {
		echo " <span class='verdana2'>";
		if ($version_aff == $id_diff) {
			echo "<b>("._T('info_historique_comparaison').")</b>";
		}
		else {
			$lien = parametre_url(self(), 'id_version', $id_version);
			$lien = parametre_url($lien, 'id_diff', $version_aff);
			echo "(<a href='".($lien.'#diff').
			"'>"._T('info_historique_comparaison')."</a>)";
		}
		echo "</span>";
	}
	echo "</li>\n";
}
echo "</ul>\n";

fin_cadre_relief();


//////////////////////////////////////////////////////
// Corps de la version affichee
//

if ($id_version) {
	echo "\n\n<div align='justify'>";

	// pour l'affichage du virtuel

	if (substr($chapo, 0, 1) == '=') {
		$virtuel = substr($chapo, 1);
	}
	
	if ($virtuel) {
		debut_boite_info();
		echo _T('info_renvoi_article')." ".propre("<center>[->$virtuel]</center>");
		fin_boite_info();
	}
	else {
		echo "<div $dir_lang><b>";
		$revision_nbsp = ($options == "avancees");	// a regler pour relecture des nbsp dans les articles
		echo justifier(propre_diff($chapo));
		echo "</b></div>\n\n";
	
		echo "<div $dir_lang>";
		echo justifier(propre_diff($texte));
		echo "</div>";
	
		if ($ps) {
			echo debut_cadre_enfonce();
			echo "<div $dir_lang><font size='2' face='Verdana,Arial,Sans,sans-serif'>";
			echo justifier("<b>"._T('info_ps')."</b> ".propre_diff($ps));
			echo "</font></div>";
			echo fin_cadre_enfonce();
		}
		$revision_nbsp = false;
	
		if ($les_notes) {
			echo debut_cadre_relief();
			echo "<div $dir_lang><font size='2'>";
			echo justifier("<b>"._T('info_notes')."&nbsp;:</b> ".$les_notes);
			echo "</font></div>";
			echo fin_cadre_relief();
		}
	
		if ($champs_extra AND $extra) {
			include_spip('inc/extra');
			echo extra_affichage($extra, "articles");
		}
	}
}

fin_cadre_relief();


echo fin_page();
}
?>
