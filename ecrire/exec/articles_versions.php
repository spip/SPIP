<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@exec_articles_versions_dist
function exec_articles_versions_dist()
{
	exec_articles_versions_args(intval(_request('id_article')),
		intval(_request('id_version')),
		intval(_request('id_diff'))); // code mort ?
}

// http://doc.spip.org/@exec_articles_versions_args
function exec_articles_versions_args($id_article, $id_version, $id_diff)
{
	global $les_notes, $spip_lang_left, $spip_lang_right;

	if (!autoriser('voirrevisions', 'article', $id_article) 
	OR !$row = sql_fetsel("*", "spip_articles", "id_article=".sql_quote($id_article))){
		include_spip('inc/minipres');
		echo minipres();
		return;
	}

	include_spip('inc/suivi_versions');
	include_spip('inc/presentation');
	include_spip('inc/revisions');

	// recuperer les donnees actuelles de l'article
	$id_article = $row["id_article"];
	$id_rubrique = $row["id_rubrique"];
	$titre_defaut = $titre = $row["titre"];
	$surtitre = $row["surtitre"];
	$soustitre = $row["soustitre"];
	$descriptif = $row["descriptif"];
	$chapo = $row["chapo"];
	$texte = $row["texte"];
	$ps = $row["ps"];
	$nom_site = $row["nom_site"];
	$url_site = $row["url_site"];
	$date = $row["date"];
	$statut_article = $row["statut"];
	$maj = $row["maj"];
	$date_redac = $row["date_redac"];
	$visites = $row["visites"];
	$referers = $row["referers"];
	$extra = $row["extra"];
	$id_trad = $row["id_trad"];
	$lang = $row["lang"];

	// Afficher le debut de la page (y compris rubrique)
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('info_historique')." &laquo; $titre &raquo;", "naviguer", "articles", $id_rubrique);

	echo debut_grand_cadre(true);

	echo afficher_hierarchie($id_rubrique);

	echo fin_grand_cadre(true);


//////////////////////////////////////////////////////
// Affichage de la colonne de gauche
//

	echo debut_gauche('', true);

	echo bloc_des_raccourcis(icone_horizontale(_T('icone_retour_article'), generer_url_ecrire("articles","id_article=$id_article"), "article-24.gif","rien.gif", false) .
				 icone_horizontale(_T('icone_suivi_revisions'), generer_url_ecrire("suivi_revisions",""), "historique-24.gif","rien.gif", false));



//////////////////////////////////////////////////////
// Affichage de la colonne de droite
//

	echo debut_droite('', true);

	$lang_dir = lang_dir(changer_typo($lang));

	echo debut_cadre_relief('', true);


//
// recuperer les donnees versionnees
//
	$last_version = false;
	if (!$id_version) {
		$id_version = $row['id_version'];
		$last_version = true;
	}
	$textes = revision_comparee($id_article, $id_version, 'complet', $id_diff);

	unset($id_rubrique); # on n'en n'aura besoin que si on affiche un diff

	if (is_array($textes)) foreach ($textes as $var => $t) 
	  { 
	    //	cles de $textes = array('surtitre', 'titre', 'soustitre', 'descriptif', 'nom_site', 'url_site', 'chapo', 'texte', 'ps');
	    // defini dans suivi_versions.
	    // Suicidaire. A reerire.
	    $$var = $t;
	  }




//
// Titre, surtitre, sous-titre
//

	echo "\n<table id='diff' cellpadding='0' cellspacing='0' border='0' width='100%'>";
	echo "<tr><td style='width: 100%' valign='top'>";

	if (isset($id_rubrique)) {
		echo "<div dir='$lang_dir' class='arial1 spip_x-small'>",
		$id_rubrique,
		"</div>\n";
	}

	if ($surtitre) {
		echo "<span  dir='$lang_dir'><span class='arial1 spip_medium'><b>", propre_diff($surtitre), "</b></span></span>\n";
	}
	echo gros_titre(propre_diff($titre), puce_statut($statut_article, " style='vertical-align: center'") . " &nbsp; ", false);

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

	$result = sql_select("id_version, titre_version, date, id_auteur",
		"spip_versions",
		"id_article=".sql_quote($id_article)." AND  id_version>0",
		"", "id_version DESC");

	echo debut_cadre_relief('', true);

	$zapn = 0;
	$lignes = array();
	$points = '...';
	$tranches = 10;
	while ($row = sql_fetch($result)) {

		$res = '';
		// s'il y en a trop on zappe a partir de la 10e
		// et on s'arrete juste apres celle cherchee
		if ($zapn++ > $tranches
		AND abs($id_version - $row['id_version']) > $tranches<<1) {
			if ($points) {
				$lignes[]= $points;
				$points = '';
			}
			if ($id_version > $row['id_version']) break;
			continue;
		}

		$date = affdate_heure($row['date']);
		$version_aff = $row['id_version'];
		$titre_version = typo($row['titre_version']);
		$titre_aff = $titre_version ? $titre_version : $date;
		if ($version_aff != $id_version) {
			$lien = parametre_url(self(), 'id_version', $version_aff);
			$lien = parametre_url($lien, 'id_diff', '');
			$res .= "<a href='".($lien.'#diff')."' title=\""._T('info_historique_affiche')."\">$titre_aff</a>";
		} else {
			$res .= "<b>$titre_aff</b>";
		}

		if (is_numeric($row['id_auteur'])
		AND $t = sql_getfetsel('nom', 'spip_auteurs', "id_auteur=" . intval($row['id_auteur']))) {
				$res .= " (".typo($t).")";
			} else {
				$res .= " (".$row['id_auteur'].")"; #IP edition anonyme
		}
		
		if ($version_aff != $id_version) {
		  $res .= " <span class='verdana2'>";
		  if ($version_aff == $id_diff) {
			$res .= "<b>("._T('info_historique_comparaison').")</b>";
		  } else {
			$lien = parametre_url(self(), 'id_version', $id_version);
			$lien = parametre_url($lien, 'id_diff', $version_aff);
			$res .= "(<a href='".($lien.'#diff').
			"'>"._T('info_historique_comparaison')."</a>)";
		  }
		$res .= "</span>";
		}
		$lignes[]= $res;
	}
	if ($lignes) {
		echo "<ul class='verdana3'><li>\n";
		echo join("\n</li><li>\n", $lignes);
		echo "</li></ul>\n";
	}

//////////////////////////////////////////////////////
// Corps de la version affichee
//

if ($id_version) {
	
	$revision = "";

	// pour l'affichage du virtuel

	if (substr($chapo, 0, 1) == '=') {
		$virtuel = substr($chapo, 1);
	}
	
	if ($virtuel) {
		$revision .= debut_boite_info(true);
		$revision .= _T('info_renvoi_article').
			propre("<span style='text-align: center'> [->$virtuel]</span>");
		$revision .= fin_boite_info(true);
	}
	else {
		$revision .= "<div  dir='$lang_dir'><b>";
		$revision .= justifier(propre_diff($chapo));
		$revision .= "</b></div>\n\n";
	
		$revision .= "<div  dir='$lang_dir'>";
		$revision .= justifier(propre_diff($texte));
		$revision .= "</div>";
	
		if ($ps) {
			$revision .= debut_cadre_enfonce('',true);
			$revision .= "<div  dir='$lang_dir' class='verdana1 spip_small'>" . justifier("<b>"._T('info_ps')."</b> ".propre_diff($ps)) ."</div>";
			$revision .= fin_cadre_enfonce(true);
		}
	
		if ($les_notes) {
			$revision .= debut_cadre_relief('', true);
			$revision .= "<div  dir='$lang_dir'><span class='spip_xx-small'>" . justifier("<b>"._T('info_notes')."&nbsp;:</b> ".$les_notes) . "</span></div>";
			$revision .= fin_cadre_relief(true);
		}
		
		$contexte = array('id'=>$id_article,'id_rubrique'=>$id_rubrique);
		// permettre aux plugin de faire des modifs ou des ajouts
		$revision = pipeline('afficher_revision_objet',
			array(
				'args'=>array(
					'type'=>'article',
					'id_objet'=>$id_article,
					'contexte'=>$contexte,
					'id_version'=>$id_version
				),
				'data'=> $revision
			)
		);
		
	}
	echo "\n\n<div style='text-align: justify;'>$revision</div>";
}

echo fin_cadre_relief(true);


echo  fin_gauche(), fin_page();

}

?>
