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

// http://doc.spip.org/@exec_brouteur_frame_dist
function exec_brouteur_frame_dist() {
  global $connect_statut,$connect_id_auteur, $spip_ecran, $spip_lang_left, $frame, $effacer_suivant, $special;
	$id_rubrique = is_numeric(_request('rubrique')) ? intval(_request('rubrique')) : "";

	include_spip('inc/headers');
	http_no_cache();
	echo init_entete();

	if ($spip_ecran == "large") {
		$nb_col = 4;
	} else {
		$nb_col = 3;
	}

	if ($effacer_suivant == "oui" && $frame < $nb_col) {
	  echo '<script>';
		for ($i = $frame+1; $i < $nb_col; $i++) {
		  echo "\nparent.iframe$i.location.href='", generer_url_ecrire('brouteur_frame',"frame=$i"), "'";
		}
	  echo '</script>';
	}
	echo "<div class='arial2'>";


	if ($special == "redac") {
		$result=spip_query("SELECT articles.id_article, articles.id_rubrique, articles.titre, articles.statut FROM spip_articles AS articles, spip_auteurs_articles AS lien WHERE articles.statut = 'prepa' AND articles.id_article = lien.id_article AND lien.id_auteur = $connect_id_auteur GROUP BY id_article ORDER BY articles.date DESC");
		if (spip_num_rows($result)>0) {
			echo "<div style='padding-top: 6px; padding-bottom: 3px;'><b class='verdana2'>"._T("info_cours_edition")."</b></div>";
			echo "<div class='plan-articles'>";
			while($row=spip_fetch_array($result)){
				$id_article=$row['id_article'];
				$titre = typo($row['titre']);
				$statut = $row['statut'];
				echo "<a class='$statut' href='javascript:window.parent.location=\"" . generer_url_ecrire('articles',"id_article=$id_article"),"\"'>",$titre,"</a>";
			}
			echo "</div>";
		}
	
	}
	else if ($special == "valider") {
		$result=spip_query("SELECT id_article, id_rubrique, titre, statut FROM spip_articles WHERE statut = 'prop' ORDER BY date DESC");
		if (spip_num_rows($result)>0) {
			echo "<div style='padding-top: 6px; padding-bottom: 3px;'><b class='verdana2'>"._T("info_articles_proposes")."</b></div>";
			echo "<div class='plan-articles'>";
			while($row=spip_fetch_array($result)){
				$id_article=$row['id_article'];
				$titre = typo($row['titre']);
				$statut = $row['statut'];
				echo "<a class='$statut' href='javascript:window.parent.location=\"", generer_url_ecrire('articles',"id_article=$id_article"),"\"'>",$titre,"</a>";
			}
			echo "</div>";
		}
	
		$result=spip_query("SELECT * FROM spip_breves WHERE statut = 'prop' ORDER BY date_heure DESC LIMIT  20");
		if (spip_num_rows($result)>0) {
			echo "<div style='padding-top: 6px;'><b class='verdana2'>"._T("info_breves_valider")."</b></div>";
			echo "<div class='plan-articles'>";
			while($row=spip_fetch_array($result)){
				$id_breve=$row['id_breve'];
				$titre = typo($row['titre']);
				$statut = $row['statut'];
				echo "<a class='$statut' href='javascript:window.parent.location=\"", generer_url_ecrire('breves_voir',"id_breve=$id_breve"),"\"'>",$titre,"</a>";
			}
			echo "</div>";
		}

	}
	else {
	  if ($id_rubrique !== "") {

		$result=spip_query("SELECT id_parent, id_rubrique, titre FROM spip_rubriques WHERE id_rubrique='$id_rubrique' ORDER BY 0+titre, titre");
		if ($row=spip_fetch_array($result)){
			$titre = typo($row['titre']);
			$id_parent=$row['id_parent'];
			
			if ($id_parent == 0) $icone = "secteur-24.gif";
			else $icone = "rubrique-24.gif";
			
			echo "<div style='background-color: #cccccc; border: 1px solid #444444;'>";
			icone_horizontale($titre, "javascript:window.parent.location=\"" . generer_url_ecrire('naviguer',"id_rubrique=$id_rubrique") .'"', $icone);
			echo "</div>";
		}  else if ($frame == 0) {
			echo "<div style='background-color: #cccccc; border: 1px solid #444444;'>";
			icone_horizontale(_T('info_racine_site'), "javascript:window.parent.location=\"" . generer_url_ecrire('naviguer') . '"', "racine-site-24.gif","");
			echo "</div>";
		}


		$result=spip_query("SELECT id_rubrique, id_parent, titre FROM spip_rubriques WHERE id_parent='$id_rubrique' ORDER BY 0+titre, titre");
		while($row=spip_fetch_array($result)){
			$ze_rubrique=$row['id_rubrique'];
			$titre = typo($row['titre']);
			$id_parent=$row['id_parent'];
			
			echo "<div class='brouteur_rubrique'
onMouseOver=\"changeclass(this, 'brouteur_rubrique_on');\"
onMouseOut=\"changeclass(this, 'brouteur_rubrique');\">";

			if ($id_parent == '0') 	{
			  echo "<div style='", frame_background_image("secteur-24.gif"), ";'><a href='", generer_url_ecrire('brouteur_frame', "rubrique=$ze_rubrique&frame=".($frame+1)."&effacer_suivant=oui"), "' target='iframe", ($frame+1), "'>",
			    $titre,
			    "</a></div>";
			}
			else {
				if ($frame+1 < $nb_col)
				  echo "<div style='",
				    frame_background_image("rubrique-24.gif"), ";'><a href='", generer_url_ecrire('brouteur_frame', "rubrique=$ze_rubrique&frame=".($frame+1)."&effacer_suivant=oui"), "' target='iframe",
				    ($frame+1),
				    "'>$titre</a></div>";
				else  echo "<div style='",
				  frame_background_image("rubrique-24.gif"), ";'><a href='javascript:window.parent.location=\"" . generer_url_ecrire('brouteur',"id_rubrique=$ze_rubrique")."\"'>",$titre,"</a></div>";
			}
			echo "</div>\n";
		}

	
		if ($id_rubrique > 0) {
			if ($connect_statut == "0minirezo")
				$result = spip_query("SELECT id_article, id_rubrique, titre, statut FROM spip_articles WHERE id_rubrique=$id_rubrique ORDER BY date DESC");
			else 
				$result = spip_query("SELECT articles.id_article, articles.id_rubrique, articles.titre, articles.statut FROM spip_articles AS articles, spip_auteurs_articles AS lien WHERE articles.id_rubrique=$id_rubrique AND (articles.statut = 'publie' OR articles.statut = 'prop' OR (articles.statut = 'prepa' AND articles.id_article = lien.id_article AND lien.id_auteur = $connect_id_auteur)) GROUP BY id_article ORDER BY articles.date DESC");

			if (spip_num_rows($result)>0) {
				echo "<div style='padding-top: 6px; padding-bottom: 3px;'><b class='verdana2'>"._T('info_articles')."</b></div>";
				echo "<div class='plan-articles'>";
				while($row=spip_fetch_array($result)){
					$id_article=$row['id_article'];
					$titre = typo($row['titre']);
					$statut = $row['statut'];
					echo "<a class='$statut' href='javascript:window.parent.location=\"" . generer_url_ecrire('articles',"id_article=$id_article")."\"'>",$titre,"</a>";
				}
				echo "</div>";
			}
	
			$result=spip_query("SELECT * FROM spip_breves WHERE id_rubrique=$id_rubrique ORDER BY date_heure DESC LIMIT  20");
			if (spip_num_rows($result)>0) {
				echo "<div style='padding-top: 6px;'><b class='verdana2'>"._T('info_breves_02')."</b></div>";
				echo "<div class='plan-articles'>";
				while($row=spip_fetch_array($result)){
					$id_breve=$row['id_breve'];
					$titre = typo($row['titre']);
					$statut = $row['statut'];
					echo "<a class='$statut' href='javascript:window.parent.location=\"", generer_url_ecrire('breves_voir',"id_breve=$id_breve")."\"'>",$titre,"</a>";
				}
				echo "</div>";


			}
	
			$result=spip_query("SELECT * FROM spip_syndic WHERE id_rubrique='$id_rubrique' AND statut!='refuse' ORDER BY nom_site");
			if (spip_num_rows($result)>0) {
				echo "<div style='padding-top: 6px;'><b class='verdana2'>"._T('icone_sites_references')."</b></div>";
				while($row=spip_fetch_array($result)){
					$id_syndic=$row['id_syndic'];
					$titre = typo($row['nom_site']);
					$statut = $row['statut'];
					echo "<div " . http_style_background('site-24.gif',  "$spip_lang_left center no-repeat; margin:3px; padding-top: 5px; padding-bottom: 5px; padding-$spip_lang_left: 28px") . "><b><a href='javascript:window.parent.location=\"", generer_url_ecrire('sites',"id_syndic=$id_syndic"),"\"'>",$titre,"</a></b></div>";
				}
			}
		}

		// en derniere colonne, afficher articles et breves
		if ($frame == 0 AND $id_rubrique==0) {

			$cpt=spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_articles AS articles, spip_auteurs_articles AS lien WHERE articles.statut = 'prepa' AND articles.id_article = lien.id_article AND lien.id_auteur = $connect_id_auteur GROUP BY articles.id_article"));
			if ($cpt['n']) {

			  echo "<div ", http_style_background('article-24.gif',  "$spip_lang_left center no-repeat; margin:3px; padding-top: 5px; padding-bottom: 5px; padding-$spip_lang_left: 28px"),
			    "><b class='verdana2'><a href='", generer_url_ecrire('brouteur_frame', "special=redac&frame=".($frame+1)."&effacer_suivant=oui"), "' target='iframe",($frame+1),"'>",
			    _T("info_cours_edition"),"</a></b></div>";
			}
			
			$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_articles AS articles WHERE articles.statut = 'prop'"));
			if (!$cpt['n'])
				$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_breves WHERE statut = 'prop'"));
			if ($cpt['n'])
				echo "<div ", http_style_background('article-24.gif',  "$spip_lang_left center no-repeat; margin:3px; padding-top: 5px; padding-bottom: 5px; padding-$spip_lang_left: 28px"),
			    "><b class='verdana2'><a href='", generer_url_ecrire('brouteur_frame', "special=valider&frame=".($frame+1)."&effacer_suivant=oui"), "' target='iframe",
			    ($frame+1)."'>",
			    _T("info_articles_proposes"),
			    " / "._T("info_breves_valider")."</a></b></div>";
		}
	}
   }
	echo "</div>";

echo "</body></html>";
}

// http://doc.spip.org/@frame_background_image
function frame_background_image($f)
{
	return "background-image: url(" . 
		_DIR_IMG_PACK . $f .
		")";
}
?>
