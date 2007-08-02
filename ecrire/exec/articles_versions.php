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

	global $les_notes, $champs_extra, $spip_lang_left, $spip_lang_right;

	$id_article = intval(_request('id_article'));
	$id_version = intval(_request('id_version'));
	$id_diff = intval(_request('id_diff')); // code mort ?

//
// Lire l'article
//

	$row = spip_fetch_array(spip_query("SELECT * FROM spip_articles WHERE id_article=$id_article"));

	if (!autoriser('voirrevisions', 'article', $id_article) 
		OR !$row) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}

	$commencer_page = charger_fonction('commencer_page', 'inc');
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
	$lang = $row["lang"];

	$last_version = false;
	if (!$id_version) {
		$id_version = $row['id_version'];
		$last_version = true;
	}

	$textes = revision_comparee($id_article, $id_version, 'complet', $id_diff);
	if (is_array($textes)) foreach ($textes as $var => $t) 
	  { 
	    //	cles de $textes = array('surtitre', 'titre', 'soustitre', 'descriptif', 'nom_site', 'url_site', 'chapo', 'texte', 'ps');
	    // defini dans suivi_versions.
	    // Suicidaire. A reerire.
	    $$var = $t;}

	echo $commencer_page(_T('info_historique')." &laquo; $titre &raquo;", "naviguer", "articles", $id_rubrique);

	echo debut_grand_cadre(true);

	echo afficher_hierarchie($id_rubrique);

	echo fin_grand_cadre(true);


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

	$lang_dir = lang_dir(changer_typo($lang));

	echo debut_cadre_relief(true);

//
// Titre, surtitre, sous-titre
//

	echo "\n<table id='diff' cellpadding='0' cellspacing='0' border='0' width='100%'>";
	echo "<tr><td style='width: 100%' valign='top'>";
	if ($surtitre) {
		echo "<span  dir='$lang_dir'><span class='arial1 spip_medium'><b>", propre_diff($surtitre), "</b></span></span>\n";
}
	echo gros_titre(propre_diff($titre), puce_statut($statut_article, " style='vertical-align: center'") . " &nbsp; ", true);

	if ($soustitre) {
		echo "<span  dir='$lang_dir'><span class='arial1 spip_medium'><b>", propre_diff($soustitre), "</b></span></span>\n";
}


	if ($descriptif OR $url_site OR $nom_site) {
		echo "<div style='text-align: $spip_lang_left; padding: 5px; border: 1px dashed #aaaaaa; background-color: #e4e4e4;'  dir='$lang_dir'>";
		$texte_case = ($descriptif) ? "{{"._T('info_descriptif')."}} $descriptif\n\n" : '';
		$texte_case .= ($nom_site.$url_site) ? "{{"._T('info_urlref')."}} [".$nom_site."->".$url_site."]" : '';
		echo "<span class='verdana1 spip_small'>", propre($texte_case), "</span>";
		echo "</div>";
	}

	echo "</td><td>";

// Icone de modification
	if (autoriser('modifier', 'article', $id_article))
		echo icone_inline(
		_T('icone_modifier_article').'<br />('._T('version')." $id_version)",
		generer_url_ecrire("articles_edit",
			"id_article=$id_article".((!$last_version)?"&id_version=$id_version":"")),
		"article-24.gif",
		"edit.gif",
		$spip_lang_right
		);

	echo "</td>";

	echo "</tr></table>";

	echo fin_cadre_relief(true);

//////////////////////////////////////////////////////
// Affichage des versions
//


	$result = spip_query("SELECT id_version, titre_version, date, id_auteur	FROM spip_versions WHERE id_article=$id_article ORDER BY id_version DESC");

	$zap = spip_num_rows($result);

	if (!$zap) return; 

	debut_cadre_relief();
// s'il y en a trop on en zappe (pagination a la va-vite)
	$zap = ($zap > 50);
	$zaps = '<li>...</li>';
	$zapn = 0;

	echo "<ul class='verdana3'>";
	while ($row = spip_fetch_array($result)) {

	// points de pagination
		if ($zap
		    AND $zapn++>10
		    AND abs($id_version - $row['id_version']) > 20) {
			echo $zaps;
			$zaps = '';
			if ($id_version > $row['id_version']) {
				echo '<li>...</li>';
				break;
			}
			continue;
		}

		echo "<li>\n";
		$date = affdate_heure($row['date']);
		$version_aff = $row['id_version'];
		$titre_version = typo($row['titre_version']);
		$titre_aff = $titre_version ? $titre_version : $date;
		if ($version_aff != $id_version) {
			$lien = parametre_url(self(), 'id_version', $version_aff);
			$lien = parametre_url($lien, 'id_diff', '');
			echo "<a href='".($lien.'#diff')."' title=\""._T('info_historique_affiche')."\">$titre_aff</a>";
		} else {
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
		echo "<div  dir='$lang_dir'><b>";
		echo justifier(propre_diff($chapo));
		echo "</b></div>\n\n";
	
		echo "<div  dir='$lang_dir'>";
		echo justifier(propre_diff($texte));
		echo "</div>";
	
		if ($ps) {
			echo debut_cadre_enfonce();
			echo "<div  dir='$lang_dir' class='verdana1 spip_small'>", justifier("<b>"._T('info_ps')."</b> ".propre_diff($ps)), "</div>";
			echo fin_cadre_enfonce();
		}
	
		if ($les_notes) {
			echo debut_cadre_relief();
			echo "<div  dir='$lang_dir'><span class='spip_small'>", justifier("<b>"._T('info_notes')."&nbsp;:</b> ".$les_notes), "</span></div>";
			echo fin_cadre_relief();
		}
	
		if ($champs_extra AND $extra) {
			include_spip('inc/extra');
			echo extra_affichage($extra, "articles");
		}
	}
}

echo fin_cadre_relief();


echo  fin_gauche(), fin_page();

}

?>
