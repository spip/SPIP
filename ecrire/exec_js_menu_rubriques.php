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

function extraire_article($id_p) {
	if (array_key_exists($id_p, $GLOBALS['db_art_cache'])) {
		return $GLOBALS['db_art_cache'][$id_p];
	} else {
		return array();
	}
}

function gen_liste_rubriques() {
	// se restreindre aux rubriques utilisees recemment +secteurs
	$liste="0";
	$s = spip_query("SELECT id_rubrique FROM spip_rubriques
		ORDER BY id_parent=0 DESC, date DESC LIMIT 500");
	while ($t = spip_fetch_array($s))
		$liste .=",".$t['id_rubrique']; 
	 
	$q = "SELECT id_rubrique, id_parent, titre 
		FROM spip_rubriques 
		WHERE id_rubrique IN ($liste)
		ORDER BY id_parent,0+titre,titre";

	$res = spip_query($q);

	$GLOBALS['db_art_cache'] = array();
	if (spip_num_rows($res) > 0) { 
		while ($row = spip_fetch_array($res)) {
			$parent = $row['id_parent'];
			$id = $row['id_rubrique'];
			$GLOBALS['db_art_cache'][$parent][$id] = sinon($row['titre'], _T('ecrire:info_sans_titre'));
		}
	}
}


function bandeau_menu() {
	global $spip_ecran;

	gen_liste_rubriques(); 
	$arr_low = extraire_article(0);

	$i = sizeof($arr_low);

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
		foreach( $arr_low as $id_rubrique => $titre_rubrique) {

			if ($count_lignes == $max_lignes) {
				$count_lignes = 0;
				$ret .= "</div></td><td valign='top' width='200'><div>&nbsp;</div><div class='bandeau_rubriques' style='z-index: 1;'>";
			}
			$count_lignes ++;

			$titre_rubrique = supprimer_numero(typo($titre_rubrique));
			$ret .= bandeau_rubrique($id_rubrique, $titre_rubrique, $i);
			$i = $i - 1;
		}
		$ret .= "</div>";
	}
	unset($GLOBALS['db_art_cache']); // On libere la memoire
	return $ret;
}


function bandeau_rubrique($id_rubrique, $titre_rubrique, $z = 1) {
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
	if ($spip_ecran == "large") $max_lignes = 20;
	else $max_lignes = 15;

	if ($zindex < 1) $zindex = 1;
	if ($zdecal == 1) $image = "secteur-12.gif";
	//else $image = "rubrique-12.gif";
	else $image = '';
	
	if (strlen($image) > 1) $image = " style='background-image:url(" . _DIR_IMG_PACK . $image .");'";


	$arr_rub = extraire_article($id_rubrique);

	$i = sizeof($arr_rub);
	if ($i > 0 AND $zdecal < $zmax) {
		$ret .= '<div class=\"pos_r\" style=\"z-index: '.$z.';\" onMouseOver=\"montrer(\'b_'.$id_rubrique.'\');\" onMouseOut=\"cacher(\'b_'.$id_rubrique.'\');\">';
		$ret .= '<div class=\"brt\"><a href=\\"' . generer_url_ecrire('naviguer', 'id_rubrique='.$id_rubrique)
		  . '\\" class=\"bandeau_rub\"'.$image.'>'.addslashes(supprimer_tags($titre_rubrique)).'</a></div>'
		  . '<div class=\"bandeau_rub\" style=\"z-index: '.($z+1).';\" id=\"b_'.$id_rubrique.'\">';
		
		$ret .= '<table cellspacing=\"0\" cellpadding=\"0\"><tr><td valign=\"top\">';		
		$ret .= "<div  style='width: 170px;'>";
		foreach( $arr_rub as $id_rub => $titre_rub) {
			$count_ligne ++;
			
			if ($count_ligne == $max_lignes) {
				$count_ligne = 0;
				$ret .= "</div>";
				$ret .= '</td><td>&nbsp;</td><td valign=\"top\">';
				$ret .= "<div  style='width: 170px;'>";

			}
		
			$titre_rub = supprimer_numero(typo($titre_rub));
			$ret .= bandeau_rubrique($id_rub, $titre_rub, ($z+$i));
			$i = $i - 1;
		}
		
		$ret .= '</div></td></tr></table>';
		
		$ret .= "</div></div>";
	} else {
		$ret .= '<div><a href=\"' . generer_url_ecrire('naviguer', 'id_rubrique='.$id_rubrique)
		  . '\" class=\"bandeau_rub\"'.$image.'>'.addslashes(supprimer_tags($titre_rubrique)).'</a></div>';
	}
	$zdecal = $zdecal - 1;
	return $ret;
}

function js_menu_rubriques_dist()
{
	if (http_last_modified(@filemtime("js_menu_rubriques.php"), time() + 24 * 3600)) 
		exit;
	header('Content-type: text/javascript; charset='.$GLOBALS['meta']['charset']);
	include_ecrire("inc_texte");
	echo "document.write(\"";
	echo "<table><tr><td valign='top' width='200'>";
	echo bandeau_menu();
	echo "</td></tr></table>";
	echo "\");\n";
}
?>
