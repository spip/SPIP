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

function inc_presenter_liste_dist($requete, $fonc, &$prims, $own, $force, $largeurs, $styles, $tranches = '', $title='', $icone='')
{
	global $spip_display, $spip_lang_left;

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

		while ($r = sql_fetch($result)) {
		  if ($prim) $prims[]= $r[$prim];
		  if ($vals = $fonc($r, $own)) {
			reset($largeurs);
			reset($styles);
			$res = '';
			foreach ($vals as $t) {
				list(, $largeur) = each($largeurs);
				list(, $style) = each($styles);
				if ($largeur) $largeur = " style='width: $largeur" ."px;'";
				if ($style) $style = " class=\"$style\"";
				$t = !trim($t) ? "&nbsp;" : lignes_longues($t);
				$res .= "\n<td$class$style>$t</td>";
			}
			$tranches .= "\n<tr class='tr_liste'$evt>$res</tr>";
		  }
		}

		$tranches = "<table width='100%' cellpadding='2' cellspacing='0' border='0'>$tranches</table>";
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

	$id = 't'.substr(md5($title),0,8);
	$bouton = !$icone ? '' : bouton_block_depliable($title, true, $id);

	return debut_cadre('liste', $icone, "", $bouton, "", "", false)
	  . debut_block_depliable(true,  $id)
	  . $tranches
	  . fin_block()
	  . fin_cadre('liste');
}

// http://doc.spip.org/@afficher_tranches_requete
function afficher_tranches_requete($num_rows, $tmp_var, $url='', $nb_aff = 10, $old_arg=NULL) {
	static $ancre = 0;
	global $browser_name, $spip_lang_right, $spip_display;
	if ($old_arg!==NULL){ // eviter de casser la compat des vieux appels $cols_span ayant disparu ...
		$tmp_var = $url;		$url = $nb_aff; $nb_aff=$old_arg;
	}

	$ancre++;
	$self = self();
	$ie_style = ($browser_name == "MSIE") ? "height:1%" : '';

	$texte = "\n<div style='$ie_style;' class='arial1 tranches' id='a$ancre'>";
	$texte .= navigation_pagination($num_rows, $nb_aff, $url, $onclick=true, $tmp_var);

	$on ='';
	$script = parametre_url($self, $tmp_var, -1);
	if ($url) {
				$on = "\nonclick=\"return charger_id_url('"
				. $url
				. "&amp;"
				. $tmp_var
				. "=-1','"
				. $tmp_var
				. '\');"';
	}
	$l = htmlentities(_T('lien_tout_afficher'));
	$texte .= "<a href=\"$script#a$ancre\"$on class='plus'><img\nsrc='". chemin_image("plus.gif") . "' title=\"$l\" alt=\"$l\" /></a>";

	$texte .= "</div>\n";
	return $texte;
}

// http://doc.spip.org/@affiche_tranche_bandeau
function affiche_tranche_bandeau($requete, $tmp_var, $force, $skel, $own='')
{
	global $spip_display ;
	$res = "";

	if (!isset($requete['GROUP BY'])) $requete['GROUP BY'] = '';

	$cpt = sql_countsel($requete['FROM'], $requete['WHERE'], $requete['GROUP BY']);

	if (!($cpt OR $force)) return '';

	$nb_aff = floor(1.5 * _TRANCHES);

	if (isset($requete['LIMIT'])) $cpt = min($requete['LIMIT'], $cpt);

	else if (!($deb_aff = intval(_request($tmp_var))))
		 $requete['LIMIT'] = $nb_aff;

	if ($cpt > $nb_aff) {
		$nb_aff = (_TRANCHES); 
		return afficher_tranches_requete($cpt, $tmp_var, '', $nb_aff);
	}
	return '';
}
?>
