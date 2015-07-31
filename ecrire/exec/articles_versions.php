<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

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
	global $spip_lang_left, $spip_lang_right;

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
	$titre = $row["titre"];
	$statut_article = $row["statut"];
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


//
// Titre, surtitre, sous-titre
//

	$debut = $corps = '';

	if (is_array($textes))
	foreach ($textes as $var => $t) {
		switch ($var) {
			case 'id_rubrique':
				$debut .= "<div dir='$lang_dir' class='arial1 spip_x-small'>"
					. $t
					. "</div>\n";
				break;

			case 'surtitre':
			case 'soustitre':
				$debut .= "<div  dir='$lang_dir' class='arial1 spip_medium'><b>" . propre_diff($t) . "</b></div>\n";
				break;

			case 'titre':
				$debut .= gros_titre(propre_diff($t),
					puce_statut($statut_article, " style='vertical-align: center'"), false);
				break;

			// trois champs a affichage combine
			case 'descriptif':
			case 'url_site':
			case 'nom_site':
				if (!$vudesc++) {
					$debut .= "<div style='text-align: $spip_lang_left; padding: 5px; border: 1px dashed #aaaaaa; background-color: #e4e4e4;'  dir='$lang_dir'>";
					$texte_case = ($textes['descriptif']) ? "{{"._T('info_descriptif')."}} ".$textes['descriptif']."\n\n" : '';
					$texte_case .= ($textes['nom_site'].$textes['url_site']) ? "{{"._T('info_urlref')."}} [".$textes['nom_site']."->".$textes['url_site']."]" : '';
					$debut .= "<span class='verdana1 spip_small'>"
					. propre($texte_case). "</span>";
					$debut .= "</div>";
				}
				break;

			default:
				$corps .= "<div dir='$lang_dir' class='champ contenu_$var'>"
					. "<div class='label'>$var</div>"
					. "<div class='$var'>"
					. propre_diff($t)
					. "</div></div>\n";
				break;
		}
	}

	echo '<div id="contenu">';

	echo debut_cadre_relief('', true);

	echo "\n<table id='diff' cellpadding='0' cellspacing='0' border='0' width='100%'>";
	echo "<tr><td style='width: 100%' valign='top'>";
	echo $debut;
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
	echo "\n\n<div id='wysiwyg' style='text-align: justify;'>$corps";

	// notes de bas de page
	if (strlen($GLOBALS['les_notes']))
		echo "<div class='champ contenu_notes'>
			<div class='label'>"._T('info_notes')."</div>
			<div class='notes' dir='$lang_dir'>"
			.$GLOBALS['les_notes']
			."</div></div>\n";

	echo "</div>\n";

	echo fin_cadre_relief(true);

	echo '</div>'; // /#contenu


	echo  fin_gauche(), fin_page();

}

?>
