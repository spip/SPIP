<?php

include ("inc.php3");

if (count($aff_art) > 0) $aff_art = join(',', $aff_art);
else $aff_art = 'prop,publie';

$statut_art = "'".join("','", explode(",", $aff_art))."'";

debut_page(_T('titre_page_articles_tous'), "asuivre", "tout-site");
debut_gauche();


// Recuperer la direction globale de la langue
$direction_generale = $spip_dir_lang;


// Recuperer toutes les rubriques 
$query = "SELECT id_rubrique, titre, id_parent FROM spip_rubriques ORDER BY titre";
$result = spip_query($query);
while ($row = spip_fetch_array($result)) {
	$id_rubrique = $row['id_rubrique'];
	$titre = typo($row['titre']);
	$id_parent = $row['id_parent'];
	
	$les_rubriques[] = "rubrique$id_rubrique";
	
	$nom_block = "rubrique$id_rubrique";
	if (!$numero_block["$nom_block"] > 0){
		$compteur_block++;
		$numero_block["$nom_block"] = $compteur_block;

		if (!$first_couche) $first_couche = $compteur_block;
		$last_couche = $compteur_block;
	}

	if ($id_parent == '0') {
		$rubrique[$id_rubrique] = "$titre";
	}
	else {
		$rubrique[$id_rubrique] =  "$titre";
	}

	$enfant[$id_parent][] = $id_rubrique;		
}

$query = "SELECT DISTINCT id_rubrique FROM spip_articles";
$result = spip_query($query);
while ($row = spip_fetch_array($result)) {
	$id_rubrique = $row['id_rubrique'];
	$rubriques_actives[$id_rubrique] = $id_rubrique;
}

// Recuperer tous les articles
if (is_array($sel_lang)) {
	while (list(,$l) = each($sel_lang))
		$sel[$l] = $l;
	$sel_lang = $sel;
}
$sel_lang[$spip_lang] = $spip_lang;

if ($connect_statut == "0minirezo") $query = "SELECT articles.id_article, articles.titre, articles.statut, articles.id_rubrique, articles.lang, articles.id_trad, articles.date_modif FROM spip_articles AS articles ORDER BY date DESC";
else $query = "SELECT articles.id_article, articles.titre, articles.statut, articles.id_rubrique, articles.lang, articles.id_trad, articles.date_modif FROM spip_articles AS articles, spip_auteurs_articles AS lien WHERE (articles.statut = 'publie' OR articles.statut = 'prop' OR (articles.statut = 'prepa' AND articles.id_article = lien.id_article AND lien.id_auteur = $connect_id_auteur)) GROUP BY id_article ORDER BY articles.date DESC";

//$query = "SELECT id_rubrique, id_article, titre, statut FROM spip_articles WHERE statut IN ($statut_art) ORDER BY titre";
$result = spip_query($query);
while($row = spip_fetch_array($result)) {
	$id_rubrique=$row['id_rubrique'];
	$id_article = $row['id_article'];
	$titre = typo($row['titre']);
	$statut = $row['statut'];
	$lang = $row['lang'];
	$id_trad = $row['id_trad'];
	$date_modif = $row['date_modif'];
	
	$aff_statut["$statut"] = true;
	$aff_lang["$lang"] = true;
	$text_article[$id_article]["titre"] = "$titre";
	$text_article[$id_article]["statut"] = $statut;
	$text_article[$id_article]["lang"] = $lang;
	$text_article[$id_article]["id_trad"] = $id_trad;
	$text_article[$id_article]["date_modif"] = $date_modif;
	$GLOBALS['langues_utilisees'][$lang] = true;


		$langues = explode(',', lire_meta('langues_multilingue'));
		if ((lire_meta('multi_rubriques') == 'oui' OR lire_meta('multi_articles') == 'oui') AND lire_meta('gerer_trad') == 'oui') {
			if (count($langues) > 1) {
				while (list(, $l) = each ($langues)) {
					if ($sel_lang[$l]) $text_article[$id_article]["trad"]["$l"] =  "<span class='creer'>$l</span>";
				}
			}
		}
	
	
	if ($id_trad == $id_article OR $id_trad == 0) {
		$text_article[$id_article]["trad"]["$lang"] = "<span class='lang_base'$direction_generale>$lang</span>";
	}
		
	if (ereg("'$statut'","$statut_art")) {
		$article[$id_rubrique][] = $id_article;
	}
}

$tmp = $text_article;

if ($tmp) {
	for (reset($tmp); $id_article = key($tmp); next($tmp)) {
		$id_trad = $tmp[$id_article]["id_trad"];
		$date = $tmp[$id_article]['date'];
		$date_modif = $tmp[$id_article]['date_modif'];
		$lang = $tmp[$id_article]['lang'];
				
			
		if ($id_trad > 0 AND $id_trad != $id_article AND $sel_lang[$lang]) {
			if ($text_article[$id_trad]["date_modif"] < $text_article[$id_article]["date_modif"]) {
				$text_article[$id_trad]["trad"]["$lang"] = "<a class='foncee' href='articles.php3?id_article=$id_article'>$lang</a>";
			} else {
				$text_article[$id_trad]["trad"]["$lang"] = "<a class='claire' href='articles.php3?id_article=$id_article'>$lang</a>";
			}
		}
	}
}

echo "<form action='articles_tous.php3' method='get'>";
echo "<input type='hidden' name='aff_art[]' value='x'>";

debut_boite_info();

echo "<B>"._T('titre_cadre_afficher_article')."&nbsp;:</B><BR>";





if ($aff_statut["prepa"]) {
	if (ereg('prepa', $aff_art)) {
		echo "<input type='checkbox' CHECKED name='aff_art[]' value='prepa' id='prepa'>";
	}
	else {
		echo "<input type='checkbox' name='aff_art[]' value='prepa' id='prepa'>";
	}
	echo " <label for='prepa'>". http_img_pack('puce-blanche-breve.gif', "alt='' width='8' height='9' border='0'");
	echo "  "._T('texte_statut_en_cours_redaction')."</label><BR>";
}


if ($aff_statut["prop"]) {
	if (ereg('prop', $aff_art)) {
		echo "<input type='checkbox' CHECKED name='aff_art[]' value='prop' id='prop'>";
	}
	else {
		echo "<input type='checkbox' name='aff_art[]' value='prop' id='prop'>";
	}
	echo " <label for='prop'>". http_img_pack('puce-orange-breve.gif', "alt='' width='8' height='9' border='0'");
	echo "  "._T('texte_statut_attente_validation')."</label><BR>";
}
	
if ($aff_statut["publie"]) {
	if (ereg('publie', $aff_art)) {
		echo "<input type='checkbox' CHECKED name='aff_art[]' value='publie' id='publie'>";
	}
	else {
		echo "<input type='checkbox' name='aff_art[]' value='publie' id='publie'>";
	}
	echo " <label for='publie'>". http_img_pack('puce-verte-breve.gif', "alt='' width='8' height='9' border='0'");
	echo "  "._T('texte_statut_publies')."</label><BR>";
}

if ($aff_statut["refuse"]) {
	if (ereg("refuse",$aff_art)) {
		echo "<input type='checkbox' CHECKED name='aff_art[]' value='refuse' id='refuse'>";
	}
	else {
		echo "<input type='checkbox' name='aff_art[]' value='refuse' id='refuse'>";
	}
	echo " <label for='refuse'>" . http_img_pack('puce-rouge-breve.gif', "alt='' width='8' height='9' border='0'");
	echo "  "._T('texte_statut_refuses')."</label><BR>";
}

if ($aff_statut["poubelle"]) {
	if (ereg('poubelle',$aff_art)) {
		echo "<input type='checkbox' CHECKED name='aff_art[]' value='poubelle' id='poubelle'>";
	}
	else {
		echo "<input type='checkbox' name='aff_art[]' value='poubelle' id='poubelle'>";
	}
	echo " <label for='poubelle'>" . http_img_pack('puce-poubelle-breve.gif', "alt='' width='8' height='9' border='0'");
	echo "  "._T('texte_statut_poubelle')."</label>";
}

echo "<div align='$spip_lang_right'><INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='"._T('bouton_changer')."'></div>";


// GERER LE MULTILINGUISME
if ((lire_meta('multi_rubriques') == 'oui' OR lire_meta('multi_articles') == 'oui') AND lire_meta('gerer_trad') == 'oui') {

	// bloc legende
	$lf = lire_meta('langue_site');
	echo "<hr /><div class='verdana2'>";
	echo _T('info_tout_site6');
	echo "<div><span class='lang_base'>$lf</span> ". _T('info_tout_site5') ." </div>";
	echo "<div><span class='creer'>$lf</span> ". _T('info_tout_site2') ." </div>";
	echo "<div><a class='claire'>$lf</a> ". _T('info_tout_site3'). " </div>";
	echo "<div><a class='foncee'>$lf</a> ". _T('info_tout_site4'). " </div>";
	echo "</div>\n";

	// bloc choix de langue
	$langues = explode(',', lire_meta('langues_multilingue'));
	if (count($langues) > 1) {
		sort($langues);
		echo "<br /><div class='verdana2'><b>"._T('titre_cadre_afficher_traductions')."</b><br />";
		echo "<SELECT STYLE='width:100%' NAME='sel_lang[]' size='".count($langues)."' ORDERED MULTIPLE>";
		while (list(, $l) = each ($langues)) {
			if ($sel_lang[$l])
				echo "<option value='$l' selected>".traduire_nom_langue($l)."</option>\n"; 
			else
				echo "<option value='$l'>".traduire_nom_langue($l)."</option>\n"; 
		}
		echo "</select></div>\n";

		echo "<div align='$spip_lang_right'><INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='"._T('bouton_changer')."'></div>";
	}

}




fin_boite_info();
echo "</form>";




debut_droite();



function afficher_rubriques_filles($id_parent) {
	global $rubrique, $enfant, $article, $text_article;
	global $spip_lang_left, $spip_lang_right, $spip_lang, $direction_generale;
	global $couleur_claire;
	global $decal;
	
	

	
	$decal = $decal + 1;
	$droite = 500 - (10 * $decal);
	
	if ($enfant[$id_parent]) {
		while (list(,$id_rubrique) = each($enfant[$id_parent]) ) {
			
			if ($id_parent == 0) {
				$icone = "secteur-24.gif";
				$bgcolor = " background-color: $couleur_claire;";
			}
			else {
				$icone = "rubrique-24.gif";
				$bgcolor = "";
			}
			
			echo "<div style='padding-top: 5px; padding-bottom: 5px; padding-$spip_lang_left: 28px; background: url(" . _DIR_IMG_PACK . "$icone) $spip_lang_left center no-repeat;$bgcolor'>";
			
			if ($enfant[$id_rubrique] OR $article[$id_rubrique]) echo bouton_block_invisible("rubrique$id_rubrique");
			
			echo "<b class='verdana2'><a href='naviguer.php3?coll=$id_rubrique'>";
			echo $rubrique[$id_rubrique];
			echo "</b></a></div>\n";
			

			if ($enfant[$id_rubrique] OR $article[$id_rubrique]) {
				echo debut_block_invisible("rubrique$id_rubrique");			

				echo "<div class='plan-rubrique'>";
				if ($article[$id_rubrique]) {
					echo "<div class='plan-articles'>";
					while(list(,$zarticle) = each($article[$id_rubrique]) ) {
						$zelang = $text_article[$zarticle]["lang"];
						$text_article[$zarticle]["trad"]["$zelang"] = "";
						if (count($text_article[$zarticle]["trad"]) > 0) {
							ksort($text_article[$zarticle]["trad"]);
							$traductions = join ($text_article[$zarticle]["trad"], "");
						} else {
							$traductions = "";
						}
						if ($text_article[$zarticle]["id_trad"] == 0 OR $text_article[$zarticle]["id_trad"] == $zarticle) {
							//echo "<div style='position: relative;'$direction_generale>";
							if (strlen($traductions)>0) echo "<div class='trad_float'>$traductions</div>";
							echo "<a class='".$text_article[$zarticle]["statut"]."' href='articles.php3?id_article=$zarticle'>";
							if ((lire_meta('multi_rubriques') == 'oui' OR lire_meta('multi_articles') == 'oui') AND lire_meta('gerer_trad') == 'oui') echo "<span class='lang_base'$direction_generale>".$text_article[$zarticle]["lang"]."</span> ";
							echo "<span>".$text_article[$zarticle]["titre"]."</span></a>";	
							//echo "</div>\n";
						}
					}
					echo "</div>";
								
				}

				afficher_rubriques_filles($id_rubrique);	
				echo "</div>";
				echo fin_block();
			}
			
		if ($id_parent == 0) echo "<div>&nbsp;</div>";
		}
	}
	$decal = $decal-1;
	
}



$javasc_ouvrir = "manipuler_couches('ouvrir','$spip_lang_rtl',$first_couche,$last_couche, '" . _DIR_IMG_PACK . "')";
$javasc_fermer = "manipuler_couches('fermer','$spip_lang_rtl',$first_couche,$last_couche, '" . _DIR_IMG_PACK . "')";

// Demarrer l'affichage
if ($les_rubriques AND $browser_layer) {
	$les_rubriques = join($les_rubriques,",");
	echo "<div>&nbsp;</div>";
	echo "<b class='verdana3'>";
	echo "<a href=\"javascript:$javasc_ouvrir\">";
	echo _T('lien_tout_deplier');
	echo "</a>";
	echo "</b>";
	echo " | ";
	echo "<b class='verdana3'>";
	echo "<a href=\"javascript:$javasc_fermer\">";
	echo _T('lien_tout_replier');
	echo "</a>";
	echo "</b>";
	echo "<div>&nbsp;</div>";
}

afficher_rubriques_filles(0);


fin_page();

?>
