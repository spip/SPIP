<?php

include ("inc.php3");


// Gestion d'expiration de ce jaja
$expire = $date + 3600*24;
$date = gmdate("D, d M Y H:i:s", $date);
$expire = gmdate("D, d M Y H:i:s", $expire);
@Header ("Last-Modified: ".$date." GMT");
@Header ("Expires: ".$expire." GMT");
@Header ("Content-Type: text/javascript");


function bandeau_menu() {
		$result_racine = spip_query("SELECT * FROM spip_rubriques WHERE id_parent=0 ORDER BY titre");
		$i = spip_num_rows($result_racine);
		if ($i > 0) {
			$ret = "<div>&nbsp;</div>";
			$ret .= "<div class='bandeau_rubriques' style='z-index: 1;'>";
			while ($row = spip_fetch_array($result_racine)) {
				$id_rubrique = $row["id_rubrique"];
				$titre_rubrique = supprimer_numero(typo($row["titre"]));
				
				$ret .= bandeau_rubrique ($id_rubrique, $titre_rubrique, $i);
				
				$i = $i - 1;
			}
			$ret .= "</div>";
		}
		
		return $ret;
}


function bandeau_rubrique ($id_rubrique, $titre_rubrique, $z = 1) {
	global $zdecal;
	global $spip_ecran, $spip_display;
	global $spip_lang, $spip_lang_rtl, $spip_lang_left, $spip_lang_right;

	// Calcul du nombre max de sous-menus
	$zdecal = $zdecal + 1;
	if ($spip_ecran == "large") $zmax = 8;
	else $zmax= 6;
	
	// Limiter volontairement le nombre de sous-menus 
	$zmax = 6;

	if ($zindex < 1) $zindex = 1;
	if ($zdecal == 1) $image = "secteur-12.gif";
	else $image = "rubrique-12.gif";
	
	
	
	$result_rub = spip_query("SELECT * FROM spip_rubriques WHERE id_parent=$id_rubrique ORDER BY titre");

	$i = spip_num_rows($result_rub);
	if ($i > 0 AND $zdecal < $zmax) {
		$ret .= '<div style=\"position: relative; z-index: '.$z.';\" onMouseOver=\"setvisibility(\'bandeau_rub'.$id_rubrique.'\', \'visible\');\" onMouseOut=\"setvisibility(\'bandeau_rub'.$id_rubrique.'\', \'hidden\');\">';
		$ret .= '<div style=\"background: url(img_pack/triangle-droite'.$spip_lang_rtl.'.gif) '.$spip_lang_right.' center no-repeat;\"><a href=\"naviguer.php3?coll='.$id_rubrique.'\" class=\"bandeau_rub\" style=\"background-image: url(img_pack/'.$image.');\">'.addslashes(supprimer_tags($titre_rubrique)).'</a></div>';
		$ret .= '<div class=\"bandeau_rub\" style=\"z-index: '.($z+1).';\" id=\"bandeau_rub'.$id_rubrique.'\">';
		while ($row_rub = spip_fetch_array($result_rub)) {
			$id_rub = $row_rub["id_rubrique"];
			$titre_rub = supprimer_numero(typo($row_rub["titre"]));
			$ret .= bandeau_rubrique ($id_rub, $titre_rub, ($z+$i));
			$i = $i - 1;
		}
		$ret .= "</div></div>";
	} else {
		$ret .= '<div><a href=\"naviguer.php3?coll='.$id_rubrique.'\" class=\"bandeau_rub\" style=\"background-image: url(img_pack/'.$image.'); padding-'.$spip_lang_right.': 2px;\">'.addslashes(supprimer_tags($titre_rubrique)).'</a></div>';
	}
	$zdecal = $zdecal - 1;
	return $ret;
}

echo "document.write(\"";
echo bandeau_menu();
echo "\");\n";

?>