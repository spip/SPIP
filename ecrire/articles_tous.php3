<?php

include ("inc.php3");
include_ecrire("inc_layer.php3");

if (count($aff_art) > 0) $aff_art = join(',', $aff_art);
else $aff_art = 'prop,publie';

$statut_art = "'".join("','", explode(",", $aff_art))."'";

debut_page(_T('titre_page_articles_tous'), "asuivre", "tout-site");
debut_gauche();


echo "<form action='articles_tous.php3' method='get'>";
echo "<input type='hidden' name='liste_coll' value='$liste_coll'>";
echo "<input type='hidden' name='aff_art[]' value='x'>";

debut_boite_info();

echo "<B>"._T('titre_cadre_afficher_article')."&nbsp;:</B><BR>";


if ($connect_statut == "0minirezo") {
	if (ereg('prepa', $aff_art)) {
		echo "<input type='checkbox' CHECKED name='aff_art[]' value='prepa' id='prepa'>";
	}
	else {
		echo "<input type='checkbox' name='aff_art[]' value='prepa' id='prepa'>";
	}
	echo " <label for='prepa'><img src='img_pack/puce-blanche-breve.gif' alt='' width='8' height='9' border='0'>";
	echo "  "._T('texte_statut_en_cours_redaction')."</label><BR>";
}


if (ereg('prop', $aff_art)) {
	echo "<input type='checkbox' CHECKED name='aff_art[]' value='prop' id='prop'>";
}
else {
	echo "<input type='checkbox' name='aff_art[]' value='prop' id='prop'>";
}
echo " <label for='prop'><img src='img_pack/puce-orange-breve.gif' alt='' width='8' height='9' border='0'>";
echo "  "._T('texte_statut_attente_validation')."</label><BR>";

if (ereg('publie', $aff_art)) {
	echo "<input type='checkbox' CHECKED name='aff_art[]' value='publie' id='publie'>";
}
else {
	echo "<input type='checkbox' name='aff_art[]' value='publie' id='publie'>";
}
echo " <label for='publie'><img src='img_pack/puce-verte-breve.gif' alt='' width='8' height='9' border='0'>";
echo "  "._T('texte_statut_publies')."</label><BR>";

if ($connect_statut == "0minirezo") {
	if (ereg("refuse",$aff_art)) {
		echo "<input type='checkbox' CHECKED name='aff_art[]' value='refuse' id='refuse'>";
	}
	else {
		echo "<input type='checkbox' name='aff_art[]' value='refuse' id='refuse'>";
	}
	echo " <label for='refuse'><img src='img_pack/puce-rouge-breve.gif' alt='' width='8' height='9' border='0'>";
	echo "  "._T('texte_statut_refuses')."</label><BR>";

	if (ereg('poubelle',$aff_art)) {
		echo "<input type='checkbox' CHECKED name='aff_art[]' value='poubelle' id='poubelle'>";
	}
	else {
		echo "<input type='checkbox' name='aff_art[]' value='poubelle' id='poubelle'>";
	}
	echo " <label for='poubelle'><img src='img_pack/puce-poubelle-breve.gif' alt='' width='8' height='9' border='0'>";
	echo "  "._T('texte_statut_poubelle')."</label>";
}

echo "<div align='right'><INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='"._T('bouton_changer')."'></div>";
fin_boite_info();
echo "</form>";


debut_droite();

function afficher_rubriques_filles($id_parent) {
	global $rubrique, $enfant, $article, $rubriques_actives;
	global $spip_lang_left, $spip_lang_right;
	global $couleur_claire;
	
	if ($enfant[$id_parent]) {
		while (list(,$id_rubrique) = each($enfant[$id_parent]) ) {
			$vide = !($enfant[$id_rubrique] OR $article[$id_rubrique]);
			
			if ($id_parent == 0) {
				$icone = "secteur-24.gif";
				$bgcolor = " background-image: none; background-color: $couleur_claire;";
			}
			else {
				$icone = "rubrique-24.gif";
				$bgcolor = "";
			}

			echo "<div style='padding: 3px; $bgcolor'>";
			if (!$vide) {
				echo "<div style='float: $spip_lang_left; padding: 1px;'>";
				echo bouton_block_invisible("rubrique$id_rubrique");
				echo "</div>";
			}
			echo "<div style='float: $spip_lang_left; margin-$spip_lang_left: 5px; padding: 4px; padding-$spip_lang_left: 28px; background: url(img_pack/$icone) $spip_lang_left center no-repeat;'>";

			echo "<b class='verdana3'><a href='naviguer.php3?coll=$id_rubrique'>";
			if ($vide && !$rubriques_actives[$id_rubrique]) echo "<font color='#909090'>";
			echo $rubrique[$id_rubrique];
			if ($vide && !$rubriques_actives[$id_rubrique]) echo "</font>";
			echo "</a></b></div>\n";
			echo "<div style='clear:both;'></div>";
			echo "</div>\n";

			if (!$vide) {
				echo debut_block_invisible("rubrique$id_rubrique");

				if ($id_parent)
					echo "<div class='plan-rubrique'>";
				else 
					echo "<div class='plan-secteur'>";
				
				if ($article[$id_rubrique]) {
					echo "<div class='plan-articles'>\n";
					echo join("", $article[$id_rubrique]);
					echo "</div>\n";
				}

				afficher_rubriques_filles($id_rubrique);
				echo "</div>";
				echo fin_block();
			}
			if (!$id_parent) echo "<p>";
		}
	}
}


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
$query = "SELECT id_rubrique, id_article, titre, statut FROM spip_articles WHERE statut IN ($statut_art) ORDER BY titre";
$result = spip_query($query);
while($row = spip_fetch_array($result)) {
	$id_rubrique=$row['id_rubrique'];
	$id_article = $row['id_article'];
	$titre = typo($row['titre']);
	$statut = $row['statut'];
	switch ($statut) {
		case 'publie':
			$puce = 'verte';
			break;
		case 'prepa':
			$puce = 'blanche';
			break;
		case 'prop':
			$puce = 'orange';
			break;
		case 'refuse':
			$puce = 'rouge';
			break;
		case 'poubelle':
			$puce = 'poubelle';
			break;
	}
	$puce = "puce-$puce-breve.gif";
	
	$article[$id_rubrique][] = "<a class='$statut' href='articles.php3?id_article=$id_article'>$titre</a>\n";
}

$javasc_ouvrir = "manipuler_couches('ouvrir','$spip_lang_rtl',$first_couche,$last_couche)";
$javasc_fermer = "manipuler_couches('fermer','$spip_lang_rtl',$first_couche,$last_couche)";

// Demarrer l'affichage
if ($les_rubriques AND test_layer()) {
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
