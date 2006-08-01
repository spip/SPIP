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
	$s = spip_query("SELECT id_rubrique FROM spip_rubriques ORDER BY id_parent=0 DESC, date DESC LIMIT 500");
	while ($t = spip_fetch_array($s))
		$liste .=",".$t['id_rubrique']; 
	 
	$res = spip_query("SELECT id_rubrique, id_parent, titre FROM spip_rubriques WHERE id_rubrique IN ($liste) ORDER BY id_parent,0+titre,titre");

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
	global $max_lignes, $width_col;

	gen_liste_rubriques(); 
	$arr_low = extraire_article(0);

	$total_lignes = $i = sizeof($arr_low);

	$nb_col = min(10,max(1,ceil($total_lignes / 10)));
	$max_lignes = ceil($total_lignes / $nb_col);
	$width_col=min(120, 800/$nb_col);
	$width_col =  "position: absolute; left: $width_col" . "px;";

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
	global $max_lignes, $width_col;
	global $spip_ecran, $spip_display;
	global $spip_lang, $spip_lang_rtl, $spip_lang_left, $spip_lang_right;

	$titre_rubrique = preg_replace(',[\x00-\x1f]+,', ' ', $titre_rubrique);
	$count_ligne = 0;
	$zdecal = $zdecal + 1;
	// Limiter volontairement le nombre de sous-menus 
	$zmax = 6;

	if ($zdecal == 1) $image = "secteur-12.gif";
	//else $image = "rubrique-12.gif";
	else $image = '';
	
	if (strlen($image) > 1)
		$image = " style='background-image:url(" . http_wrapper($image) .");'";

	$arr_rub = extraire_article($id_rubrique);

	$i = sizeof($arr_rub);
	if ($i > 0 AND $zdecal < $zmax) {
		$ret = '<div class=\"pos_r\" style=\"$width_col' .'z-index: '.$z.';\" onMouseOver=\"montrer(\'b_'.$id_rubrique.'\');\" onMouseOut=\"cacher(\'b_'.$id_rubrique.'\');\">';
		$ret .= '<div class=\"brt\"><a href=\\"' . generer_url_ecrire('naviguer', 'id_rubrique='.$id_rubrique)
		  . '\\" class=\"bandeau_rub\"'.$image.'>'.addslashes(supprimer_tags($titre_rubrique)).'</a></div>'
		  . '<div class=\"bandeau_rub\" style=\"z-index: '.($z+1).';\" id=\"b_'.$id_rubrique.'\">';
		
		$ret .= '<table cellspacing=\"0\" cellpadding=\"0\"><tr><td valign=\"top\">';		
		$ret .= "<div  style='width: 200px;'>";
		
		if ($nb_rub = count($arr_rub))
			$ret_ligne =  ceil($nb_rub / ceil($nb_rub / $max_lignes)) + 1;
				
		foreach( $arr_rub as $id_rub => $titre_rub) {
			$count_ligne ++;
			
			if ($count_ligne == $ret_ligne) {
				$count_ligne = 0;
				$ret .= "</div>";
				$ret .= "</td>";
				$ret .= '<td valign=\"top\" style=\"border-left: 1px solid #cccccc;\">';
				$ret .= "<div  style='width: 200px;'>";

			}
		
			$titre_rub = supprimer_numero(typo($titre_rub));
			$ret .= bandeau_rubrique($id_rub, $titre_rub, ($z+$i));
			$i = $i - 1;
		}
		
		$ret .= '</div></td></tr></table>';
		
		$ret .= "</div></div>";
	} else {
		$ret = '<div><a href=\"' . generer_url_ecrire('naviguer', 'id_rubrique='.$id_rubrique)
		  . '\" class=\"bandeau_rub\"'.$image.'>'.addslashes(supprimer_tags($titre_rubrique)).'</a></div>';
	}
	$zdecal = $zdecal - 1;
	return $ret;
}


function http_last_modified($lastmodified, $expire = 0) {
	if (!$lastmodified) return false;
	$headers_only = false;
	$gmoddate = gmdate("D, d M Y H:i:s", $lastmodified);
	if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
	AND !preg_match(',IIS/,', $_SERVER['SERVER_SOFTWARE'])) # MSoft IIS is dumb
	{
		$if_modified_since = preg_replace('/;.*/', '',
			$_SERVER['HTTP_IF_MODIFIED_SINCE']);
		$if_modified_since = trim(str_replace('GMT', '', $if_modified_since));
		if ($if_modified_since == $gmoddate) {
			include_spip('inc/headers');
			http_status(304);
			$headers_only = true;
		}
	}
	@Header ("Last-Modified: ".$gmoddate." GMT");
	if ($expire) 
		@Header ("Expires: ".gmdate("D, d M Y H:i:s", $expire)." GMT");
	return $headers_only;
}

function exec_js_menu_rubriques_dist()
{
	if (http_last_modified(_request('date'))) exit;
	header('Content-type: text/javascript; charset='.$GLOBALS['meta']['charset']);
	include_spip('inc/texte');
	echo "document.write(\"";
	echo "<table><tr><td valign='top' width='200'>";
	echo bandeau_menu();
	echo "</td></tr></table>";
	echo "\");\n";
}
?>
