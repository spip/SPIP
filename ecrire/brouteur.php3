<?php

include ("inc.php3");

debut_page(_T('titre_page_articles_tous'), "asuivre", "tout-site");

echo "<div>&nbsp;</div>";

	if ($spip_ecran == "large") {
		$largeur_table = 974;
		$hauteur_table = 400;
		$nb_col = 4;
	} else {
		$largeur_table = 750;
		$hauteur_table = 300;
		$nb_col = 3;
	}
	$largeur_col = round($largeur_table/$nb_col);
	
	

	echo "<table border='0' cellpadding='0' cellspacing='2' width='$largeur_table'>";

	if ($id_rubrique) {
		$j = $nb_col;
		while ($id_rubrique > 0) {
			$query = "SELECT * FROM spip_rubriques WHERE id_rubrique=$id_rubrique ORDER BY titre";
			$result=spip_query($query);
			while($row=spip_fetch_array($result)){
				$j = $j-1;
				$ze_rubrique = $row['id_rubrique'];
				$titre = typo($row['titre']);
				$id_rubrique =$row['id_parent'];
				
				$dest[$j] = $ze_rubrique;
			}
		}
		
		$dest[$j-1] = 0;
		
		while (!$dest[1]) {
			for ($i = 0; $i < $nb_col; $i++) {
				$dest[$i] = $dest[$i+1];
			}
		}




		if ($dest[0] > 0 AND $parent = $dest[$nb_col-2]) {
			// Afficher la hierarchie pour "remonter"
			echo "<tr><td colspan='$nb_col' style='text-align: $spip_lang_left;'>";
			$la_rubrique = $dest[0];
			
			$query = "SELECT * FROM spip_rubriques WHERE id_rubrique = $la_rubrique";
			$result = spip_query($query);
			while ($row = spip_fetch_array($result)) {
				$la_rubrique =$row['id_parent'];
			}
			
			while ($la_rubrique > 0) {
				$query = "SELECT * FROM spip_rubriques WHERE id_rubrique = $la_rubrique";
				$result = spip_query($query);
				while ($row = spip_fetch_array($result)) {
					$compteur = $compteur + 1;
					$ze_rubrique = $row['id_rubrique'];
					$titre = typo($row['titre']);
					$la_rubrique =$row['id_parent'];
					$lien = $dest[$nb_col-$compteur-1];
					if ($la_rubrique == 0) $icone = "secteur-24.gif";
					else $icone = "rubrique-24.gif";
					$ret = "<div style='padding-top: 5px; padding-bottom: 5px; padding-$spip_lang_left: 28px; background: url(img_pack/$icone) $spip_lang_left no-repeat;'><a href='brouteur.php3?id_rubrique=$lien'>$titre</a></div><div style='margin-$spip_lang_left: 28px;'>$ret</div>";
				}
			}
			$lien = $dest[$nb_col-$compteur-2];
			$icone = "racine-site-24.gif";
			$ret = "<div style='padding-top: 5px; padding-bottom: 5px; padding-$spip_lang_left: 28px; background: url(img_pack/$icone) $spip_lang_left no-repeat;'><a href='brouteur.php3?id_rubrique=$lien'>"._T('info_racine_site')."</a></div><div style='margin-$spip_lang_left: 28px;'>$ret</div>";
			echo $ret;
			echo "</td></tr>";
			
		}
	} else {
		$id_rubrique = 0;
		$dest[0] = "$id_rubrique";
	}


	
	
	echo "<tr width='$largeur_table'>";
	
	for ($i=0; $i < $nb_col; $i++) {
		echo "<td valign='top' width='$largeur_col'>";
		
		echo "<iframe width='100%' id='iframe$i' name='iframe$i' src='brouteur_frame.php3?id_rubrique=".$dest[$i]."&frame=$i' height='$hauteur_table' style='background-color: #eeeeee; border-right: 1px solid white; border-bottom: 1px solid white; border-left: 1px solid #cccccc; border-top: 1px solid #cccccc;'></iframe>";
		
		
		echo "</td>";
	
	}
	
	echo "</tr>";
	echo "</table>";	


?>