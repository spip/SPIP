<?php

include ("inc.php3");


// Gestion d'expiration de ce jaja
$expire = $date + 3600*24;

$headers_only = http_last_modified($expire);


$date = gmdate("D, d M Y H:i:s", $date);
$expire = gmdate("D, d M Y H:i:s", $expire);
@Header ("Content-Type: text/javascript");
if ($headers_only) exit;
@Header ("Last-Modified: ".$date." GMT");
@Header ("Expires: ".$expire." GMT");



function bandeau_menu() {
	global $spip_ecran;
		$result_racine = spip_query("SELECT * FROM spip_rubriques WHERE id_parent=0 ORDER BY titre");
		$i = spip_num_rows($result_racine);
		
		$total_lignes = $i;
		if ($spip_ecran == "large") $max_lignes = 20;
		else $max_lignes = 15;
		
		$nb_col = ceil($total_lignes / $max_lignes);
		if ($nb_col < 1) $nb_col = 1;
		$max_lignes = ceil($total_lignes / $nb_col);
		
		
		$count_lignes = 0;
		
		if ($i > 0) {
			$ret = "<div>&nbsp;</div>";
			$ret .= "<div class='bandeau_rubriques' style='z-index: 1;'>";
			while ($row = spip_fetch_array($result_racine)) {

				if ($count_lignes == $max_lignes) {			
					$count_lignes = 0;
					$ret .= "</div></td><td valign='top' width='200'><div>&nbsp;</div><div class='bandeau_rubriques' style='z-index: 1;'>";
				}
				$count_lignes ++;

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

	$titre_rubrique = preg_replace(',[\x00-\x1f]+,', ' ', $titre_rubrique);


	// Calcul du nombre max de sous-menus
	$zdecal = $zdecal + 1;
	if ($spip_ecran == "large") $zmax = 8;
	else $zmax= 6;
	
	// Limiter volontairement le nombre de sous-menus 
	$zmax = 6;

	if ($zindex < 1) $zindex = 1;
	if ($zdecal == 1) $image = "secteur-12.gif";
	//else $image = "rubrique-12.gif";
	else $image = '';
	
	if (strlen($image) > 1) $image = " style='background-image:url(" . _DIR_IMG_PACK . $image .");'";
	
	$result_rub = spip_query("SELECT * FROM spip_rubriques WHERE id_parent=$id_rubrique ORDER BY titre");

	$i = spip_num_rows($result_rub);
	if ($i > 0 AND $zdecal < $zmax) {
		$ret .= '<div class=\"pos_r\" style=\"z-index: '.$z.';\" onMouseOver=\"montrer(\'b_'.$id_rubrique.'\');\" onMouseOut=\"cacher(\'b_'.$id_rubrique.'\');\">';
		$ret .= '<div class=\"brt\"><a href=\"naviguer.php3?coll='.$id_rubrique.'\" class=\"bandeau_rub\"'.$image.'>'.addslashes(supprimer_tags($titre_rubrique)).'</a></div>';
		$ret .= '<div class=\"bandeau_rub\" style=\"z-index: '.($z+1).';\" id=\"b_'.$id_rubrique.'\">';
		while ($row_rub = spip_fetch_array($result_rub)) {
			$id_rub = $row_rub["id_rubrique"];
			$titre_rub = supprimer_numero(typo($row_rub["titre"]));
			$ret .= bandeau_rubrique ($id_rub, $titre_rub, ($z+$i));
			$i = $i - 1;
		}
		$ret .= "</div></div>";
	} else {
		$ret .= '<div><a href=\"naviguer.php3?coll='.$id_rubrique.'\" class=\"bandeau_rub\"'.$image.'>'.addslashes(supprimer_tags($titre_rubrique)).'</a></div>';
	}
	$zdecal = $zdecal - 1;
	return $ret;
}

echo "document.write(\"";
echo "<table><tr><td valign='top' width='200'>";
echo bandeau_menu();
echo "</td></tr></table>";
echo "\");\n";

?>