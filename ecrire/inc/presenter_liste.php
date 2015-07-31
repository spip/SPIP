<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// Cette fonction prend un argument un tableau decrivant une requete Select
// et une fonction formatant chaque ligne du resultat de la requete
// Elle renvoie une enumeration HTML de ces lignes formatees, 
// avec une pagination appelable en Ajax si $idom et $url sont fournis

// http://doc.spip.org/@inc_presenter_liste_dist
function inc_presenter_liste_dist($requete, $fonc, &$prims, $own, $force, $styles, $idom='', $title='', $icone='', $url='', $cpt=NULL)
{
	global $spip_display, $spip_lang_left;

	// $requete est passe par reference, pour modifier l'index LIMIT
	if ($idom AND $spip_display != 4)
		$tranches = affiche_tranche_bandeau($requete, $idom, $url, $cpt);
	else $tranches = '';

	$prim = $prims;
	$prims = array();
	$result = sql_select((isset($requete["SELECT"]) ? $requete["SELECT"] : "*"), $requete['FROM'], $requete['WHERE'], $requete['GROUP BY'], $requete['ORDER BY'], $requete['LIMIT']);

	if (!sql_count($result)) {
		if (!$force) return '';
	} else {
	if ($spip_display != 4) {
		$evt = !preg_match(",msie,i", $GLOBALS['browser_name']) ? ''
		: "
			onmouseover=\"changeclass(this,'tr_liste_over');\"
			onmouseout=\"changeclass(this,'tr_liste');\"" ;

		$table = '';
		while ($r = sql_fetch($result)) {
		  if ($prim) $prims[]= $r[$prim];
		  if ($vals = $fonc($r, $own)) {
			reset($styles);
			$res = '';
			foreach ($vals as $t) {
				list(,list($style, $largeur)) = each($styles);
				if ($largeur) $largeur = " style='width: $largeur" ."px;'";
				if ($style) $style = " class=\"$style\"";
				$t = !trim($t) ? "&nbsp;" : lignes_longues($t);
				$res .= "\n<td$class$style>$t</td>";
			}
			$table .= "\n<tr class='tr_liste'$evt>$res</tr>";
		  }
		}

		$tranches .= "<table width='100%' cellpadding='2' cellspacing='0' border='0'>$table</table>";
	} else {
		while ($r = sql_fetch($req)) {
			if ($prim) $prims[]= $r[$prim];
			if ($t = $fonc($r, $own)) {
			  	$tranches = '<li>' . join('</li><li>', $t) . '</li>';
		$tranches = "\n<ul style='text-align: $spip_lang_left; background-color: white;'>"
		. $tranches
		. "</ul>";
			}
		}
	}
	sql_free($result);
	}

	$id = 't'.substr(md5(join('',$requete)),0,8);
	$bouton = !$icone ? '' : bouton_block_depliable($title, true, $id);

	return debut_cadre('liste', $icone, "", $bouton, "", "", false)
	  . debut_block_depliable(true,  $id)
	  . $tranches
	  . fin_block()
	  . fin_cadre('liste');
}

// http://doc.spip.org/@afficher_tranches_requete
function afficher_tranches_requete($num_rows, $idom, $url='', $nb_aff = 10, $old_arg=NULL) {
	static $ancre = 0;
	global $browser_name, $spip_lang_right, $spip_display;
	if ($old_arg!==NULL){ // eviter de casser la compat des vieux appels $cols_span ayant disparu ...
		$idom = $url;		$url = $nb_aff; $nb_aff=$old_arg;
	}

	$ancre++;
	$self = self();
	$ie_style = ($browser_name == "MSIE") ? "height:1%" : '';
	$style = "style='visibility: hidden; float: $spip_lang_right'";
	$texte = http_img_pack("searching.gif", "*", "$style id='img_$idom'")

	  . "\n<div style='$ie_style;' class='arial1 tranches' id='a$ancre'>"
	  . navigation_pagination($num_rows, $nb_aff, $url, $onclick=true, $idom);

	$on ='';
	$script = parametre_url($self, $idom, -1);
	if ($url) {
				$on = "\nonclick=\"return charger_id_url('"
				. $url
				. "&amp;"
				. $idom
				. "=-1','"
				. $idom
				. '\');"';
	}
	$l = htmlentities(_T('lien_tout_afficher'));
	$texte .= "<a href=\"$script#a$ancre\"$on class='plus'><img\nsrc='". chemin_image("plus.gif") . "' title=\"$l\" alt=\"$l\" /></a>";

	$texte .= "</div>\n";
	return $texte;
}

// http://doc.spip.org/@affiche_tranche_bandeau
function affiche_tranche_bandeau(&$requete, $idom, $url='', $cpt=NULL)
{
	if (!isset($requete['GROUP BY'])) $requete['GROUP BY'] = '';

	if ($cpt === NULL)
		$cpt = sql_countsel($requete['FROM'], $requete['WHERE'], $requete['GROUP BY']);

	$deb_aff = intval(_request($idom));
	$nb_aff = floor(1.5 * _TRANCHES);

	if (isset($requete['LIMIT'])) $cpt = min($requete['LIMIT'], $cpt);
	if ($cpt > $nb_aff) {
		$nb_aff = (_TRANCHES); 
		$res = afficher_tranches_requete($cpt, $idom, $url, $nb_aff);
	} else $res = '';

	if (!isset($requete['LIMIT']))
		$requete['LIMIT'] = "$deb_aff, $nb_aff";

	return $res;
}
?>
