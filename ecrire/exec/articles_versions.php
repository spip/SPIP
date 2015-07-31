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
include_spip('inc/revisions');

// http://doc.spip.org/@exec_articles_versions_dist
function exec_articles_versions_dist()
{
	include_spip('inc/suivi_versions');

	global $champs_extra, $chapo, $descriptif, $dir_lang, $id_article, $id_diff, $id_version, $les_notes, $nom_site, $options, $ps, $soustitre, $surtitre, $texte, $titre, $url_site;


//
// Lire l'article
//

	$id_article = intval($id_article);
	$row = spip_fetch_array(spip_query("SELECT * FROM spip_articles WHERE id_article='$id_article'"));
	$commencer_page = charger_fonction('commencer_page', 'inc');
	if (!autoriser('voirrevisions', 'article', $id_article) 
		OR !$row) {
		echo $commencer_page(_T('info_historique'), "naviguer", "articles", isset($row["id_rubrique"])?$row["id_rubrique"]:0);
		echo "<strong>"._T('avis_acces_interdit')."</strong>";
		echo fin_page();
		exit;
	}

	$id_article = $row["id_article"];
	$id_rubrique = $row["id_rubrique"];
	$titre_defaut = $titre = $row["titre"];
	$date = $row["date"];
	$statut_article = $row["statut"];
	$maj = $row["maj"];
	$date_redac = $row["date_redac"];
	$visites = $row["visites"];
	$referers = $row["referers"];
	$extra = $row["extra"];
	$id_trad = $row["id_trad"];

	$last_version = false;
	if (!($id_version = intval($id_version))) {
		$id_version = $row['id_version'];
		$last_version = true;
	}
	$id_diff = intval($id_diff);

	$textes = revision_comparee($id_article, $id_version, 'complet', $id_diff);
	if (is_array($textes)) foreach ($textes as $var => $t) $$var = $t;



	echo $commencer_page(_T('info_historique')." &laquo; $titre &raquo;", "naviguer", "articles", $id_rubrique);

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
echo "<tr><td style='width: 100%' valign='top'>";
if ($surtitre) {
	echo "<span $dir_lang><span class='arial1 spip_medium'><b>", propre_diff($surtitre), "</b></span></span>\n";
}
 gros_titre(propre_diff($titre), $logo_statut);

if ($soustitre) {
	echo "<span $dir_lang><span class='arial1 spip_medium'><b>", propre_diff($soustitre), "</b></span></span>\n";
}


if ($descriptif OR $url_site OR $nom_site) {
	echo "<div align='left' style='padding: 5px; border: 1px dashed #aaaaaa; background-color: #e4e4e4;' $dir_lang>";
	$texte_case = ($descriptif) ? "{{"._T('info_descriptif')."}} $descriptif\n\n" : '';
	$texte_case .= ($nom_site.$url_site) ? "{{"._T('info_urlref')."}} [".$nom_site."->".$url_site."]" : '';
	echo "<span class='verdana1 spip_small'>", propre($texte_case), "</span>";
	echo "</div>";
}

echo "</td>";

echo "<td align='center'>";

// Icone de modification
if (autoriser('modifier', 'article', $id_article))
	icone(
		_T('icone_modifier_article').'<br />('._T('version')." $id_version)",
		generer_url_ecrire("articles_edit",
			"id_article=$id_article".((!$last_version)?"&id_version=$id_version":"")),
		"article-24.gif",
		"edit.gif"
	);

echo "</td>";

echo "</tr></table>";

fin_cadre_relief();

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
		if ($row['id_auteur'] == intval($row['id_auteur'])
		AND $s = spip_query("SELECT nom FROM spip_auteurs WHERE id_auteur='".addslashes($row['id_auteur'])."'")) {
			$t = spip_fetch_array($s);
			echo " (".typo($t['nom']).")";
		} else {
			echo " (".$row['id_auteur'].")"; #IP edition anonyme
		}
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


//////////////////////////////////////////////////////
// Corps de la version affichee
//

if ($id_version) {
	echo "\n\n<div style='text-align: justify;'>";

	// pour l'affichage du virtuel

	if (substr($chapo, 0, 1) == '=') {
		$virtuel = substr($chapo, 1);
	}
	
	if ($virtuel) {
		debut_boite_info();
		echo _T('info_renvoi_article'),
			propre("<span style='text-align: center'> [->$virtuel]</span>");
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
			echo "<div $dir_lang class='verdana1 spip_small'>", justifier("<b>"._T('info_ps')."</b> ".propre_diff($ps)), "</div>";
			echo fin_cadre_enfonce();
		}
		$revision_nbsp = false;
	
		if ($les_notes) {
			echo debut_cadre_relief();
			echo "<div $dir_lang><span class='spip_small'>", justifier("<b>"._T('info_notes')."&nbsp;:</b> ".$les_notes), "</span></div>";
			echo fin_cadre_relief();
		}
	
		if ($champs_extra AND $extra) {
			include_spip('inc/extra');
			echo extra_affichage($extra, "articles");
		}
	}
}

fin_cadre_relief();


echo  fin_gauche(), fin_page();

}

?>
