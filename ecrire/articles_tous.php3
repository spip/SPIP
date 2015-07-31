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
	global $rubrique, $enfant, $article;
	global $spip_lang_left, $spip_lang_right;
	global $couleur_claire;
	
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
			
			echo "<div style='padding-top: 5px; padding-bottom: 5px; padding-$spip_lang_left: 28px; background: url(img_pack/$icone) $spip_lang_left center no-repeat;$bgcolor'>";
			
			if ($enfant[$id_rubrique] OR $article[$id_rubrique]) echo bouton_block_invisible("rubrique$id_rubrique");
			
			echo "<b class='verdana2'><a href='naviguer.php3?coll=$id_rubrique'>";
			echo $rubrique[$id_rubrique];
			echo "</b></a></div>\n";
			

			if ($enfant[$id_rubrique] OR $article[$id_rubrique]) {
				echo debut_block_invisible("rubrique$id_rubrique");			

				echo "<div style='margin-$spip_lang_left: 12px; padding-$spip_lang_left: 10px; border-$spip_lang_left: 1px dotted #666666;'>";
				if ($article[$id_rubrique]) {
					while(list(,$zarticle) = each($article[$id_rubrique]) ) {
						
						echo "$zarticle\n";
					}
								
				}

				afficher_rubriques_filles($id_rubrique);	
				echo "</div>";
				echo fin_block();
			}
			
		}
	}
}


	// Recuperer toutes les rubriques 
	$query = "SELECT * FROM spip_rubriques ORDER BY titre";
	$result=spip_query($query);
	while($row=spip_fetch_array($result)){
		$id_rubrique=$row['id_rubrique'];
		$titre = typo($row['titre']);
		$id_parent=$row['id_parent'];
		
		$les_rubriques[] = "rubrique$id_rubrique";
		
		$nom_block = "rubrique$id_rubrique";
		if (!$numero_block["$nom_block"] > 0){
			$compteur_block++;
			$numero_block["$nom_block"] = $compteur_block;

			if (!$first_couche) $first_couche = $compteur_block;
			$last_couche = $compteur_block;
		}

		if ($id_parent == '0') 	{
			$rubrique[$id_rubrique] = "$titre";
		}
		else {
			$rubrique[$id_rubrique] =  "$titre";
		}

		$enfant[$id_parent][] = $id_rubrique;		
	}

	// Recuperer tous les articles
	$query = "SELECT * FROM spip_articles WHERE statut IN ($statut_art) ORDER BY titre";
	$result=spip_query($query);
	while($row=spip_fetch_array($result)){
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
		
		$article[$id_rubrique][] = "<div class='puce-article' style='background: url(img_pack/$puce) $spip_lang_left center no-repeat;'><div><a href='articles.php3?id_article=$id_article' class='verdana1'>$titre</a></div></div>";
	}
	
	$javasc_ouvrir="manipuler_couches('ouvrir','$spip_lang_rtl',$first_couche,$last_couche)";
	$javasc_fermer="manipuler_couches('fermer','$spip_lang_rtl',$first_couche,$last_couche)";

	// Demarrer l'affichage
	if ($les_rubriques AND test_layer()) {
		$les_rubriques = join($les_rubriques,",");
		echo "<div>&nbsp;</div>";
		echo "<b class='verdana2'>";
		echo "<a href=\"javascript:$javasc_ouvrir\">";
		echo _T('lien_tout_deplier');
		echo "</a>";
		echo "</b>";
		echo " | ";
		echo "<b class='verdana2'>";
		echo "<a href=\"javascript:$javasc_fermer\">";
		echo _T('lien_tout_replier');
		echo "</a>";
		echo "</b>";
		echo "<div>&nbsp;</div>";
	}
	
	afficher_rubriques_filles(0);


fin_page();

?>

