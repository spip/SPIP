<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/agenda'); // inclut inc/layer, inc/texte, inc/filtre
include_spip('inc/boutons');
include_spip('inc/actions');

define('_ACTIVER_PUCE_RAPIDE', true);

// Faux HR, avec controle de couleur

// http://doc.spip.org/@hr
function hr($color, $retour = false) {
	$ret = "\n<div style='height: 1px; margin-top: 5px; padding-top: 5px; border-top: 1px solid $color;'></div>";
	
	if ($retour) return $ret; else echo $ret;
}

//
// Cadres
//

// http://doc.spip.org/@debut_cadre
function debut_cadre($style, $icone = "", $fonction = "", $titre = "") {
	global $spip_display, $spip_lang_left;
	static $accesskey = 97; // a

	//zoom:1 fixes all expanding blocks in IE, see authors block in articles.php
	//being not standard, next step can be putting this kind of hacks in a different stylesheet
	//visible to IE only using conditional comments.  
	
	$style_cadre = " style='";
	if ($spip_display != 1 AND $spip_display != 4 AND strlen($icone) > 1) {
		$style_gauche = "padding-$spip_lang_left: 38px;";
		$style_cadre .= "margin-top: 14px;'";
	} else {
		$style_cadre .= "'"; 
		$style_gauche = '';
	}
	
	// accesskey pour accessibilite espace prive
	if ($accesskey <= 122) // z
	{
		$accesskey_c = chr($accesskey++);
		$ret = "<a id='access-$accesskey_c' href='#access-$accesskey_c' accesskey='$accesskey_c'></a>";
	} else $ret ='';

	if ($style == "e") {
		$ret .= "\n<div class='cadre-e-noir'$style_cadre><div class='cadre-$style'>";
	}
	else {
		$ret .= "\n<div class='cadre-$style'$style_cadre>";
	}

	$ret .= "\n<div style='position: relative;'>";

	if ($spip_display != 1 AND $spip_display != 4 AND strlen($icone) > 1) {
		$ret .= "\n<div style='position: absolute; top: -16px; $spip_lang_left: 10px;'>";
		if ($fonction) {
			$ret .= "\n<div " . http_style_background($icone, "no-repeat; padding: 0px; margin: 0px") . ">"
			. http_img_pack($fonction, "", "")
			. "</div>";
		}
		else $ret .=  http_img_pack("$icone", "", "");
		$ret .= "</div>";

		$style_cadre = " style='position: relative; top: 15px; margin-bottom: 14px;'";
	}

	if (strlen($titre) > 0) {
		if ($spip_display == 4) {
			$ret .= "\n<h3 class='cadre-titre'>$titre</h3>";
		} else {
			$ret .= "\n<div class='cadre-titre' style='margin: 0px;$style_gauche'>$titre</div>";
		}
	}
	
	return $ret . "</div>\n<div class='cadre-padding' style='overflow:hidden'>";
}

// http://doc.spip.org/@fin_cadre
function fin_cadre($style='') {

	$ret = ($style == "e") ? "</div></div></div>\n" : "</div></div>\n";

	if ($style != "forum" AND $style != "thread-forum")
		$ret .= "<div style='height: 5px;'></div>\n";

	return $ret;
}


// http://doc.spip.org/@debut_cadre_relief
function debut_cadre_relief($icone='', $return = false, $fonction='', $titre = ''){
	$retour_aff = debut_cadre('r', $icone, $fonction, $titre);

	if ($return) return $retour_aff; else echo $retour_aff;
}

// http://doc.spip.org/@fin_cadre_relief
function fin_cadre_relief($return = false){
	$retour_aff = fin_cadre('r');

	if ($return) return $retour_aff; else echo $retour_aff;
}


// http://doc.spip.org/@debut_cadre_enfonce
function debut_cadre_enfonce($icone='', $return = false, $fonction='', $titre = ''){
	$retour_aff = debut_cadre('e', $icone, $fonction, $titre);

	if ($return) return $retour_aff; else echo $retour_aff;
}

// http://doc.spip.org/@fin_cadre_enfonce
function fin_cadre_enfonce($return = false){

	$retour_aff = fin_cadre('e');

	if ($return) return $retour_aff; else echo $retour_aff;
}


// http://doc.spip.org/@debut_cadre_sous_rub
function debut_cadre_sous_rub($icone='', $return = false, $fonction='', $titre = ''){
	$retour_aff = debut_cadre('sous_rub', $icone, $fonction, $titre);
	if ($return) return $retour_aff; else echo $retour_aff;
}

// http://doc.spip.org/@fin_cadre_sous_rub
function fin_cadre_sous_rub($return = false){
	$retour_aff = fin_cadre('sous_rub');
	if ($return) return $retour_aff; else echo $retour_aff;
}



// http://doc.spip.org/@debut_cadre_forum
function debut_cadre_forum($icone='', $return = false, $fonction='', $titre = ''){
	$retour_aff = debut_cadre('forum', $icone, $fonction, $titre);

	if ($return) return $retour_aff; else echo $retour_aff;
}

// http://doc.spip.org/@fin_cadre_forum
function fin_cadre_forum($return = false){
	$retour_aff = fin_cadre('forum');

	if ($return) return $retour_aff; else echo $retour_aff;
}

// http://doc.spip.org/@debut_cadre_thread_forum
function debut_cadre_thread_forum($icone='', $return = false, $fonction='', $titre = ''){
	$retour_aff = debut_cadre('thread-forum', $icone, $fonction, $titre);

	if ($return) return $retour_aff; else echo $retour_aff;
}

// http://doc.spip.org/@fin_cadre_thread_forum
function fin_cadre_thread_forum($return = false){
	$retour_aff = fin_cadre('thread-forum');

	if ($return) return $retour_aff; else echo $retour_aff;
}


// http://doc.spip.org/@debut_cadre_couleur
function debut_cadre_couleur($icone='', $return = false, $fonction='', $titre=''){
	$retour_aff = debut_cadre('couleur', $icone, $fonction, $titre);

	if ($return) return $retour_aff; else echo $retour_aff;
}

// http://doc.spip.org/@fin_cadre_couleur
function fin_cadre_couleur($return = false){
	$retour_aff = fin_cadre('couleur');

	if ($return) return $retour_aff; else echo $retour_aff;
}


// http://doc.spip.org/@debut_cadre_couleur_foncee
function debut_cadre_couleur_foncee($icone='', $return = false, $fonction='', $titre=''){
	$retour_aff = debut_cadre('couleur-foncee', $icone, $fonction, $titre);

	if ($return) return $retour_aff; else echo $retour_aff;
}

// http://doc.spip.org/@fin_cadre_couleur_foncee
function fin_cadre_couleur_foncee($return = false){
	$retour_aff = fin_cadre('couleur-foncee');

	if ($return) return $retour_aff; else echo $retour_aff;
}

// http://doc.spip.org/@debut_cadre_trait_couleur
function debut_cadre_trait_couleur($icone='', $return = false, $fonction='', $titre=''){
	$retour_aff = debut_cadre('trait-couleur', $icone, $fonction, $titre);
	if ($return) return $retour_aff; else echo $retour_aff;
}

// http://doc.spip.org/@fin_cadre_trait_couleur
function fin_cadre_trait_couleur($return = false){
	$retour_aff = fin_cadre('trait-couleur');

	if ($return) return $retour_aff; else echo $retour_aff;
}


//
// une boite alerte
//
// http://doc.spip.org/@debut_boite_alerte
function debut_boite_alerte() {
	return "<table width='100%' cellpadding='6' border='0'><tr><td style='width: 100%; background-color: red'><table width='100%' cellpadding='12' border='0'><tr><td style='width: 100%; background-color: white'>";
}

// http://doc.spip.org/@fin_boite_alerte
function fin_boite_alerte() {
	return "</td></tr></table></td></tr></table>";
}


//
// une boite info
//
// http://doc.spip.org/@debut_boite_info
function debut_boite_info($return=false) {
	$r ="\n<div class='cadre-info verdana1'>";
	if ($return) return $r; else echo $r;
}

// http://doc.spip.org/@fin_boite_info
function fin_boite_info($return=false) {
	$r = "</div>";
	if ($return) return $r; else echo $r;
}

//
// une autre boite
//
// http://doc.spip.org/@bandeau_titre_boite2
function bandeau_titre_boite2($titre, $logo="", $fond="toile_blanche", $texte="ligne_noire") {
	global $spip_lang_left, $spip_display, $browser_name;
	
	if (strlen($logo) > 0 AND $spip_display != 1 AND $spip_display != 4) {
		$ie_style = ($browser_name == "MSIE") ? "height:1%" : '';

		return "\n<div style='position: relative;$ie_style'>"
		. "\n<div style='position: absolute; top: -12px; $spip_lang_left: 3px;'>"
		. http_img_pack($logo, "", "")
		. "</div>"
		. "\n<div style='padding: 3px; padding-$spip_lang_left: 30px; border-bottom: 1px solid #444444;' class='verdana2 $fond $texte'>$titre</div>"
		 . "</div>";
	} else {
		return "<h3 style='padding: 3px; border-bottom: 1px solid #444444; margin: 0px;' class='verdana2 $fond $texte'>$titre</h3>";
	}
}

//
// La boite des raccourcis
// Se place a droite si l'ecran est en mode panoramique.

// http://doc.spip.org/@bloc_des_raccourcis
function bloc_des_raccourcis($bloc) {
	global $spip_display;

	return "\n<div>&nbsp;</div>"
	. creer_colonne_droite('',true)
	. debut_cadre_enfonce('',true)
	. (($spip_display != 4)
	     ? ("\n<div style='font-size: x-small' class='verdana1'><b>"
		._T('titre_cadre_raccourcis')
		."</b>")
	       : ( "<h3>"._T('titre_cadre_raccourcis')."</h3><ul>"))
	. $bloc
	. (($spip_display != 4) ? "</div>" :  "</ul>")
	. fin_cadre_enfonce(true);
}

// Afficher un petit "+" pour lien vers autre page

// http://doc.spip.org/@afficher_plus
function afficher_plus($lien) {
	global $spip_lang_right, $spip_display;
	
	if ($spip_display != 4) {
			return "\n<a href='$lien' style='float:$spip_lang_right; padding-right: 10px;'>" .
			  http_img_pack("plus.gif", "+", "") ."</a>";
	}
}



//
// Fonctions d'affichage
//

// http://doc.spip.org/@afficher_objets
function afficher_objets($type, $titre_table,$requete,$formater=''){
	$afficher_objets = charger_fonction('afficher_objets','inc');
	return $afficher_objets($type, $titre_table,$requete,$formater);
}

// http://doc.spip.org/@afficher_liste
function afficher_liste($largeurs, $table, $styles = '') {
	global $spip_display;

	if (!is_array($table)) return "";

	if ($spip_display != 4) {
		$res = '';
		foreach ($table as $t) {
			$res .= afficher_liste_display_neq4($largeurs, $t, $styles);
		}
	} else {
		$res = "\n<ul style='text-align: $spip_lang_left; background-color: white;'>";
		foreach ($table as $t) {
			$res .= afficher_liste_display_eq4($largeurs, $t, $styles);
		}
		$res .= "\n</ul>";
	}

	return $res;
}

// http://doc.spip.org/@afficher_liste_display_neq4
function afficher_liste_display_neq4($largeurs, $t, $styles = '') {

	global $spip_lang_left,$browser_name;

	$evt = (preg_match(",msie,i", $browser_name) ? " onmouseover=\"changeclass(this,'tr_liste_over');\" onmouseout=\"changeclass(this,'tr_liste');\"" :'');

	reset($largeurs);
	if ($styles) reset($styles);
	$res ='';
	while (list(, $texte) = each($t)) {
		$style = $largeur = "";
		list(, $largeur) = each($largeurs);
		if ($styles) list(, $style) = each($styles);
		if (!trim($texte)) $texte .= "&nbsp;";
		$res .= "\n<td" .
			($largeur ? (" style='width: $largeur" ."px;'") : '') .
			($style ? " class=\"$style\"" : '') .
			">" . lignes_longues($texte) . "\n</td>";
	}

	return "\n<tr class='tr_liste'$evt>$res</tr>";
}

// http://doc.spip.org/@afficher_liste_display_eq4
function afficher_liste_display_eq4($largeurs, $t, $styles = '') {
	global $spip_lang_left;

	$res = "\n<li>";
	reset($largeurs);
	if ($styles) reset($styles);
	while (list(, $texte) = each($t)) {
		$style = $largeur = "";
		list(, $largeur) = each($largeurs);
		if (!$largeur) $res .= $texte." ";
	}
	$res .= "</li>\n";
	return $res;
}

// http://doc.spip.org/@afficher_tranches_requete
function afficher_tranches_requete($num_rows, $tmp_var, $url='', $nb_aff = 10, $old_arg=NULL) {
	static $ancre = 0;
	global $browser_name, $spip_lang_right, $spip_display;
	if ($old_arg!==NULL){ // eviter de casser la compat des vieux appels $cols_span ayant disparu ...
		$tmp_var = $url;		$url = $nb_aff; $nb_aff=$old_arg;
	}

	$deb_aff = intval(_request($tmp_var));
	$ancre++;
	$self = self();
	$ie_style = ($browser_name == "MSIE") ? "height:1%" : '';

	$texte = "\n<div style='position: relative;$ie_style; background-color: #dddddd; border-bottom: 1px solid #444444; padding: 2px;' class='arial1' id='a$ancre'>";
	$on ='';

	for ($i = 0; $i < $num_rows; $i += $nb_aff){
		$deb = $i + 1;
		$fin = $i + $nb_aff;
		if ($fin > $num_rows) $fin = $num_rows;
		if ($deb > 1) $texte .= " |\n";
		if ($deb_aff + 1 >= $deb AND $deb_aff + 1 <= $fin) {
			$texte .= "<b>$deb</b>";
		}
		else {
			$script = parametre_url($self, $tmp_var, $deb-1);
			if ($url) {
				$on = "\nonclick=\"return charger_id_url('"
				. $url
				. "&amp;"
				. $tmp_var
				. '='
				. ($deb-1)
				. "','"
				. $tmp_var
				. '\');"';
			}
			$texte .= "<a href=\"$script#a$ancre\"$on>$deb</a>";
		}
	}
	
	$style = " class='arial2' style='border-bottom: 1px solid #444444; position: absolute; top: 1px; $spip_lang_right: 15px;'";

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
	$texte .= "<a$style\nhref=\"$script#a$ancre\"$on><img\nsrc='". _DIR_IMG_PACK . "plus.gif' title=\"$l\" alt=\"$l\" /></a>";
	

	$texte .= "</div>\n";

	return $texte;
}


// http://doc.spip.org/@affiche_tranche_bandeau
function affiche_tranche_bandeau($requete, $icone, $fg, $bg, $tmp_var,  $titre, $force, $largeurs, $styles, $skel, $own='')
{

	global $spip_display ;

	$voir_logo = ($spip_display != 1 AND $spip_display != 4 AND isset($GLOBALS['meta']['image_process'])) ? ($GLOBALS['meta']['image_process'] != "non") : false;

	if (!isset($requete['GROUP BY'])) $requete['GROUP BY'] = '';

	$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM " . $requete['FROM'] . ($requete['WHERE'] ? (' WHERE ' . $requete['WHERE']) : '') . ($requete['GROUP BY'] ? (' GROUP BY ' . $requete['GROUP BY']) : '')));

	if (! ($force OR ($cpt = $cpt['n']))) return '';

	$res = bandeau_titre_boite2($titre, $icone, $fg, $bg);
	if (isset($requete['LIMIT'])) $cpt = min($requete['LIMIT'], $cpt);

	$deb_aff = intval(_request($tmp_var));
	$nb_aff = floor(1.5 * _TRANCHES);

	if ($cpt > $nb_aff) {
		$nb_aff = (_TRANCHES); 
		$res .= afficher_tranches_requete($cpt, $tmp_var, '', $nb_aff);
	}

	$result = spip_query($u = "SELECT " . (isset($requete["SELECT"]) ? $requete["SELECT"] : "*") . " FROM " . $requete['FROM'] . ($requete['WHERE'] ? (' WHERE ' . $requete['WHERE']) : '') . ($requete['GROUP BY'] ? (' GROUP BY ' . $requete['GROUP BY']) : '') . ($requete['ORDER BY'] ? (' ORDER BY ' . $requete['ORDER BY']) : '') . " LIMIT " . ($deb_aff >= 0 ? "$deb_aff, $nb_aff" : ($requete['LIMIT'] ? $requete['LIMIT'] : "99999")));

	$table = array();
	while ($row = spip_fetch_array($result)) {
		$table[]= $skel($row, $tous_id, $voir_logo, $own);
	}
	spip_free_result($result);

	$t = afficher_liste($largeurs, $table, $styles);
	if ($spip_display != 4)
	  $t = "<table width='100%' cellpadding='2' cellspacing='0' border='0'>"
	    . $t
	    . "</table>";
	return
	  (!$titre ? '': "\n<div style='height: 12px;'></div>")
	  . "\n<div class='liste'>"
	  . $res
	  . $t
	  . "</div>\n";
}


// http://doc.spip.org/@afficher_liste_debut_tableau
function afficher_liste_debut_tableau() {
	global $spip_display;

	if ($spip_display != 4) return "<table width='100%' cellpadding='2' cellspacing='0' border='0'>";
	else return '<ul>';
}

// http://doc.spip.org/@afficher_liste_fin_tableau
function afficher_liste_fin_tableau() {
	global $spip_display;
	if ($spip_display != 4) return "</table>";
	else return '</ul>';
}


// http://doc.spip.org/@puce_statut_article
function puce_statut_article($id, $statut, $id_rubrique, $type='article', $ajax = false) {
	global $lang_objet;
	
	$lang_dir = lang_dir($lang_objet);
	if (!$id) {
	  $id = $id_rubrique;
	  $ajax_node ='';
	} else	$ajax_node = " id='imgstatut$type$id'";

	switch ($statut) {
	case 'publie':
		$clip = 2;
		$puce = 'puce-verte.gif';
		$title = _T('info_article_publie');
		break;
	case 'prepa':
		$clip = 0;
		$puce = 'puce-blanche.gif';
		$title = _T('info_article_redaction');
		break;
	case 'prop':
		$clip = 1;
		$puce = 'puce-orange.gif';
		$title = _T('info_article_propose');
		break;
	case 'refuse':
		$clip = 3;
		$puce = 'puce-rouge.gif';
		$title = _T('info_article_refuse');
		break;
	case 'poubelle':
		$clip = 4;
		$puce = 'puce-poubelle.gif';
		$title = _T('info_article_supprime');
		break;
	}

	$inser_puce = http_img_pack($puce, $title, " style='margin: 1px;'$ajax_node");

	if (!autoriser('publierdans', 'rubrique', $id_rubrique)
	OR !_ACTIVER_PUCE_RAPIDE)
		return $inser_puce;

	$titles = array(
			  "blanche" => _T('texte_statut_en_cours_redaction'),
			  "orange" => _T('texte_statut_propose_evaluation'),
			  "verte" => _T('texte_statut_publie'),
			  "rouge" => _T('texte_statut_refuse'),
			  "poubelle" => _T('texte_statut_poubelle'));
	if ($ajax){
		$action = "\nonmouseover=\"montrer('statutdecal$type$id');\"";
		return 	"<span class='puce_article_fixe'\n$action>"
		. $inser_puce
		. "</span>"
		. "<span class='puce_article_popup' id='statutdecal$type$id'\nonmouseout=\"cacher('statutdecal$type$id');\" style='margin-left: -".((11*$clip)+1)."px;'>"
		  . afficher_script_statut($id, $type, -1, 'puce-blanche.gif', 'prepa', $titles['blanche'], $action)
		  . afficher_script_statut($id, $type, -12, 'puce-orange.gif', 'prop', $titles['orange'], $action)
		  . afficher_script_statut($id, $type, -23, 'puce-verte.gif', 'publie', $titles['verte'], $action)
		  . afficher_script_statut($id, $type, -34, 'puce-rouge.gif', 'refuse', $titles['rouge'], $action)
		  . afficher_script_statut($id, $type, -45, 'puce-poubelle.gif', 'poubelle', $titles['poubelle'], $action)
		  . "</span>";
	}

	$nom = "puce_statut_";

	if ((! _SPIP_AJAX) AND $type != 'article') 
	  $over ='';
	else {

	  $action = generer_url_ecrire('puce_statut_article',"",true);
	  $action = "if (!this.puce_loaded) { this.puce_loaded = true; prepare_selec_statut('$nom', '$type', $id, '$action'); }";
	  $over = "\nonmouseover=\"$action\"";
	}

	return 	"<span class='puce_article' id='$nom$type$id' dir='$lang_dir'$over>"
	. $inser_puce
	. '</span>';
}

// http://doc.spip.org/@puce_statut_breve
function puce_statut_breve($id, $statut, $id_rubrique, $type) {
	global $lang_objet;

	$lang_dir = lang_dir($lang_objet);
	$puces = array(
		       0 => 'puce-orange-breve.gif',
		       1 => 'puce-verte-breve.gif',
		       2 => 'puce-rouge-breve.gif',
		       3 => 'puce-blanche-breve.gif');

	switch ($statut) {
			case 'prop':
				$clip = 0;
				$puce = $puces[0];
				$title = _T('titre_breve_proposee');
				break;
			case 'publie':
				$clip = 1;
				$puce = $puces[1];
				$title = _T('titre_breve_publiee');
				break;
			case 'refuse':
				$clip = 2;
				$puce = $puces[2];
				$title = _T('titre_breve_refusee');
				break;
			default:
				$clip = 0;
				$puce = $puces[3];
				$title = '';
	}

	$type1 = "statut$type$id"; 
	$inser_puce = http_img_pack($puce, $title, "id='img$type1' style='margin: 1px;'");

	if (!autoriser('publierdans','rubrique',$id_rubrique)
	OR !_ACTIVER_PUCE_RAPIDE)
		return $inser_puce;

	$type2 = "statutdecal$type$id";
	$action = "\nonmouseover=\"montrer('$type2');\"";

	return	"<span class='puce_breve' id='$type1' dir='$lang_dir'>"
		. "<span class='puce_breve_fixe' $action>"
		. $inser_puce
		. "</span>"
		. "<span class='puce_breve_popup' id='$type2'\nonmouseout=\"cacher('$type2');\" style='margin-left: -".((9*$clip)+1)."px;'>\n"
		. afficher_script_statut($id, $type, -1, $puces[0], 'prop',_T('texte_statut_propose_evaluation'), $action)
		. afficher_script_statut($id, $type, -10, $puces[1], 'publie',_T('texte_statut_publie'), $action)
	  	. afficher_script_statut($id, $type, -19, $puces[2], 'refuse',_T('texte_statut_refuse'), $action)
		.  "</span></span>";
}

// http://doc.spip.org/@puce_statut_site
function puce_statut_site($id_site, $statut, $id_rubrique, $type){
	switch ($statut) {
		case 'publie':
				if (acces_restreint_rubrique($id_rubrique))
					$puce = 'puce-verte-anim.gif';
				else
					$puce='puce-verte-breve.gif';
				$title = _T('info_site_reference');
				break;
			case 'prop':
				if (acces_restreint_rubrique($id_rubrique))
					$puce = 'puce-orange-anim.gif';
				else
					$puce='puce-orange-breve.gif';
				$title = _T('info_site_attente');
				break;
			case 'refuse':
				if (acces_restreint_rubrique($id_rubrique))
					$puce = 'puce-poubelle-anim.gif';
				else
					$puce='puce-poubelle-breve.gif';
				$title = _T('info_site_refuse');
				break;
	}
	return http_img_pack($puce, $statut, "class='puce'",$title);
}

// http://doc.spip.org/@puce_statut_syndic_article
function puce_statut_syndic_article($id_syndic, $statut, $id_rubrique, $type){
		if ($statut=='publie') {
				$puce='puce-verte.gif';
		}
		else if ($statut == "refuse") {
				$puce = 'puce-poubelle.gif';
		}
	
		else if ($statut == "dispo") { // moderation : a valider
				$puce = 'puce-rouge.gif';
		}
	
		else if ($statut == "off") { // feed d'un site en mode "miroir"
				$puce = 'puce-rouge-anim.gif';
		}
	
		return http_img_pack($puce, $statut, "class='puce'");
}


// http://doc.spip.org/@afficher_script_statut
function afficher_script_statut($id, $type, $n, $img, $statut, $titre, $act)
{
  $i = http_wrapper($img);
  $h = generer_action_auteur("instituer_$type","$id-$statut");
  $h = "javascript:selec_statut('$id', '$type', $n, '$i', '$h');";
  $t = supprimer_tags($titre);
  return "<a href=\"$h\"\ntitle=\"$t\"$act><img src='$i' alt=' '/></a>";
}

//
// Afficher des auteurs sur requete SQL
//
// http://doc.spip.org/@bonhomme_statut
function bonhomme_statut($row) {
	global $connect_statut;

	switch($row['statut']) {
		case "0minirezo":
			return http_img_pack("admin-12.gif", _T('titre_image_administrateur'), "",
					_T('titre_image_administrateur'));
			break;
		case "1comite":
			if ($connect_statut == '0minirezo' AND ($row['source'] == 'spip' AND !($row['pass'] AND $row['login'])))
			  return http_img_pack("visit-12.gif",_T('titre_image_redacteur'), "", _T('titre_image_redacteur'));
			else
			  return http_img_pack("redac-12.gif",_T('titre_image_redacteur'), "", _T('titre_image_redacteur_02'));
			break;
		case "5poubelle":
		  return http_img_pack("poubelle.gif", _T('titre_image_auteur_supprime'), "",_T('titre_image_auteur_supprime'));
			break;
		case "6forum":
		  return http_img_pack("visit-12.gif", _T('titre_image_visiteur'), "",_T('titre_image_visiteur'));
			break;
		case "nouveau":
		default:
			return '';
			break;
	}
}

// La couleur du statut
// http://doc.spip.org/@puce_statut
function puce_statut($statut, $type='article') {
	switch ($statut) {
		case 'publie':
			return 'verte';
		case 'prepa':
			return 'blanche';
		case 'prop':
			return 'orange';
		case 'refuse':
			return 'rouge';
		case 'poubelle':
			return 'poubelle';
	}
}


//
// Afficher les forums
//

// http://doc.spip.org/@afficher_forum
function afficher_forum($request, $retour, $arg, $controle_id_article = false) {
	global $spip_display;
	static $compteur_forum = 0;
	static $nb_forum = array();
	static $thread = array();

	$compteur_forum++;
	$nb_forum[$compteur_forum] = spip_num_rows($request);
	$thread[$compteur_forum] = 1;
	
	$res = '';

 	while($row = spip_fetch_array($request)) {
		$statut=$row['statut'];
		if (($controle_id_article) ? ($statut!="perso") :
			(($statut=="prive" OR $statut=="privrac" OR $statut=="privadm" OR $statut=="perso")
			 OR ($statut=="publie" AND $id_parent > 0))) {
		  $res .= afficher_forum_thread($row, $controle_id_article, $compteur_forum, $nb_forum, $thread, $retour, $arg);

		  $res .= afficher_forum(spip_query("SELECT * FROM spip_forum WHERE id_parent='" . $row['id_forum'] . "'" . ($controle_id_article ? '':" AND statut<>'off'") . " ORDER BY date_heure"), $retour, $arg, $controle_id_article);
		}
		$thread[$compteur_forum]++;
	}

	spip_free_result($request);
	$compteur_forum--;
	if ($spip_display == 4 AND $res) $res = "<ul>$res</ul>";	
	return $res;
}

// http://doc.spip.org/@afficher_forum_thread
function afficher_forum_thread($row, $controle_id_article, $compteur_forum, $nb_forum, $i, $retour, $arg) {
	global $spip_lang_rtl, $spip_lang_left, $spip_lang_right, $spip_display;
	static $voir_logo = array(); // pour ne calculer qu'une fois

	if (is_array($voir_logo)) {
		$voir_logo = (($spip_display != 1 AND $spip_display != 4 AND $GLOBALS['meta']['image_process'] != "non") ? 
		      "position: absolute; $spip_lang_right: 0px; margin: 0px; margin-top: -3px; margin-$spip_lang_right: 0px;" 
		      : '');
	}

	$id_forum=$row['id_forum'];
	$id_parent=$row['id_parent'];
	$id_rubrique=$row['id_rubrique'];
	$id_article=$row['id_article'];
	$id_breve=$row['id_breve'];
	$id_message=$row['id_message'];
	$id_syndic=$row['id_syndic'];
	$date_heure=$row['date_heure'];
	$titre=$row['titre'];
	$texte=$row['texte'];
	$auteur= extraire_multi($row['auteur']);
	$email_auteur=$row['email_auteur'];
	$nom_site=$row['nom_site'];
	$url_site=$row['url_site'];
	$statut=$row['statut'];
	$ip=$row["ip"];
	$id_auteur=$row["id_auteur"];
	
	$deb = "<a id='id$id_forum'></a>";

	if ($spip_display == 4) {
		$res = "\n<li>$deb".typo($titre)."<br />";
	} else {

		$titre_boite = '';
		if ($id_auteur AND $voir_logo) {
			$chercher_logo = charger_fonction('chercher_logo', 'inc');
			if ($logo = $chercher_logo($id_auteur, 'id_auteur', 'on')) {
				list($fid, $dir, $nom, $format) = $logo;
				include_spip('inc/filtres_images');
				$logo = image_reduire("<img src='$fid' alt='' />", 48, 48);
				if ($logo)
					$titre_boite = "\n<div style='$voir_logo'>$logo</div>" ;
			}
		} 

		$titre_boite .= typo($titre);

		$res = "$deb<table width='100%' cellpadding='0' cellspacing='0' border='0'><tr>"
		. afficher_forum_4($compteur_forum, $nb_forum, $i)
		. "\n<td style='width: 100%' valign='top'>";
		if ($compteur_forum == 1) 
			$res .= '<br />'
			  . debut_cadre_forum(forum_logo($statut), true, "", $titre_boite);
		else $res .= debut_cadre_thread_forum("", true, "", $titre_boite);
	}

	// Si refuse, cadre rouge
	if ($statut=="off") {
		$res .= "\n<div style='border: 2px dashed red; padding: 5px;'>";
	}
	// Si propose, cadre jaune
	else if ($statut=="prop") {
		$res .= "\n<div style='border: 1px solid yellow; padding: 5px;'>";
	}
	// Si original, cadre vert
	else if ($statut=="original") {
		$res .= "\n<div style='border: 1px solid green; padding: 5px;'>";
	}
	$res .= "<table width='100%' cellpadding='5' cellspacing='0'>\n<tr><td>";
	$res .= "<div style='font-weight: normal;'>". date_interface($date_heure) . "&nbsp;&nbsp;";

	if ($id_auteur) {
		$formater_auteur = charger_fonction('formater_auteur', 'inc');
		$res .= join(' ',$formater_auteur($id_auteur));
	} else {
		if ($email_auteur) {
			if (email_valide($email_auteur))
				$email_auteur = "<a href='mailto:"
				.htmlspecialchars($email_auteur)
				."?subject=".rawurlencode($titre)."'>".$email_auteur
				."</a>";
			$auteur .= " &mdash; $email_auteur";
		}
		$res .= safehtml("<span class='arial2'> / <b>$auteur</b></span>");
	}

	$res .= '</div>';

	// boutons de moderation
	if ($controle_id_article)
		$res .= boutons_controle_forum($id_forum, $statut, $id_auteur, "id_article=$id_article", $ip);

	$res .= "<div style='font-weight: normal;'>".safehtml(justifier(propre($texte)))."</div>\n";

	if ($nom_site) {
		if (strlen($url_site) > 10)
			$res .= "\n<div style='text-align: left' class='verdana2'><b><a href='$url_site'>$nom_site</a></b></div>";
		else $res .= "<b>$nom_site</b>";
	}

	if (!$controle_id_article) {
	  	$tm = rawurlencode($titre);
		$res .= "\n<div style='text-align: right' class='verdana1'>"
		. "<b><a href='"
		  . generer_url_ecrire("forum_envoi", "id_parent=$id_forum&titre_message=$tm&script=" . urlencode("$retour?$arg")) . '#formulaire'
		. "'>"
		. _T('lien_repondre_message')
		. "</a></b></div>";
	}

	if ($GLOBALS['meta']["mots_cles_forums"] == "oui")
		$res .= afficher_forum_mots($id_forum);

	$res .= "</td></tr></table>";

	if ($statut == "off" OR $statut == "prop") $res .= "</div>";

	if ($spip_display != 4) {
		if ($compteur_forum == 1) $res .= fin_cadre_forum(true);
		else $res .= fin_cadre_thread_forum(true);
		$res .= "</td></tr></table>\n";
	} else $res .= "</li>\n";

	return $res;
}


// http://doc.spip.org/@afficher_forum_mots
function afficher_forum_mots($id_forum)
{
	$result = spip_query("SELECT * FROM spip_mots AS mots, spip_mots_forum AS lien WHERE lien.id_forum = '$id_forum' AND lien.id_mot = mots.id_mot");

	$res = "";
	while ($row = spip_fetch_array($result)) {
		$res .= "\n<li> <b>"
		. propre($row['titre'])
		. " :</b> "
		.  propre($row['type'])
		.  "</li>";
	}
	
	return $res ? "\n<ul>$res</ul>\n" : $res;
}

// affiche les traits de liaisons entre les reponses

// http://doc.spip.org/@afficher_forum_4
function afficher_forum_4($compteur_forum, $nb_forum, $thread)
{
	global $spip_lang_rtl;
	$fleche2="forum-droite$spip_lang_rtl.gif";
	$fleche='rien.gif';
	$vertical = _DIR_IMG_PACK . 'forum-vert.gif';
	$rien = _DIR_IMG_PACK . 'rien.gif';
	$res = '';
	for ($j=2;$j<=$compteur_forum AND $j<20;$j++){
		$res .= "<td style='width: 10px; vertical-align: top; background-image: url("
		. (($thread[$j]!=$nb_forum[$j]) ? $vertical : $rien)
		.  ");'>"
		. http_img_pack(($j==$compteur_forum) ? $fleche2 : $fleche, "", "width='10' height='13'")
		. "</td>\n";
	}
	return $res;
}


// http://doc.spip.org/@forum_logo
function forum_logo($statut)
{
	if ($statut == "prive") return "forum-interne-24.gif";
	else if ($statut == "privadm") return "forum-admin-24.gif";
	else if ($statut == "privrac") return "forum-interne-24.gif";
	else return "forum-public-24.gif";
}


// http://doc.spip.org/@envoi_link
function envoi_link($nom_site_spip, $minipres=false) {
	global $auteur_session, $connect_toutes_rubriques, $spip_display, $spip_lang;

	$couleurs = charger_fonction('couleurs', 'inc');
	$paramcss = 'ltr='
	. $GLOBALS['spip_lang_left'] . '&'
	. $couleurs($auteur_session['prefs']['couleur']);

	// CSS de secours en cas de non fonct de la suivante
	$res = '<link rel="stylesheet" type="text/css" href="'
	. find_in_path('style_prive_defaut.css')
	. '" />'  . "\n"
	
	// CSS espace prive : la vraie
	. '<link rel="stylesheet" type="text/css" href="'
	. generer_url_public('style_prive', $paramcss) .'" />' . "\n"
  . "<!--[if lt IE 8]>\n"
  . '<link rel="stylesheet" type="text/css" href="'
  . generer_url_public('style_prive_ie', $paramcss) .'" />' . "\n"
  . "<![endif]-->\n"
  
	// CSS calendrier
	. '<link rel="stylesheet" type="text/css" href="'
	. find_in_path('agenda.css') .'" />' . "\n"

	// CSS imprimante (masque des trucs, a completer)
	. '<link rel="stylesheet" type="text/css" href="'
	. find_in_path('spip_style_print.css')
	. '" media="print" />' . "\n"

	// CSS "visible au chargement" differente selon js actif ou non

	. '<link rel="stylesheet" type="text/css" href="'
	. find_in_path('spip_style_'
		. (_SPIP_AJAX ? 'invisible' : 'visible')
		. '.css')
	.'" />' . "\n"
	
	// CSS optionelle minipres
	. ($minipres?'<link rel="stylesheet" type="text/css" href="'
	. find_in_path('minipres.css').'" />' . "\n":"")

	// favicon.ico
	. '<link rel="shortcut icon" href="'
	. url_absolue(find_in_path('favicon.ico'))
	. "\" />\n";
	$js = debut_javascript($connect_toutes_rubriques,
			($GLOBALS['meta']["activer_statistiques"] != 'non'));

	if ($spip_display == 4) return $res . $js;

	$nom = entites_html($nom_site_spip);

	$res .= "<link rel='alternate' type='application/rss+xml' title=\"$nom\" href='"
			. generer_url_public('backend') . "' />\n";
	$res .= "<link rel='help' type='text/html' title=\""._T('icone_aide_ligne') . 
			"\" href='"
			. generer_url_ecrire('aide_index',"var_lang=$spip_lang")
			."' />\n";
	if ($GLOBALS['meta']["activer_breves"] != "non")
		$res .= "<link rel='alternate' type='application/rss+xml' title=\""
			. $nom
			. " ("._T("info_breves_03")
			. ")\" href='" . generer_url_public('backend-breves') . "' />\n";

	return $res . $js;
}

// http://doc.spip.org/@debut_javascript
function debut_javascript($admin, $stat)
{
	global $spip_lang_left, $browser_name, $browser_version;
	include_spip('inc/charsets');


	// tester les capacites JS :

	// On envoie un script ajah ; si le script reussit le cookie passera a +1
	// on installe egalement un <noscript></noscript> qui charge une image qui
	// pose un cookie valant -1

	$testeur = generer_url_ecrire('test_ajax', 'var_ajaxcharset=utf-8&js=1');

	if (_SPIP_AJAX) {
	  // pour le pied de page
		define('_TESTER_NOSCRIPT',
			"<noscript>\n<div style='display:none;'><img src='"
		        . generer_url_ecrire('test_ajax', 'var_ajaxcharset=utf-8&js=-1')
		        . "' width='1' height='1' alt='' /></div></noscript>\n"); 
	}

	return 
	// envoi le fichier JS de config si browser ok.
		$GLOBALS['browser_layer'] .
	 	http_script(
			((isset($_COOKIE['spip_accepte_ajax']) && $_COOKIE['spip_accepte_ajax'] >= 1)
			? ''
			: "jQuery.ajax({'url':'$testeur'});") .
			(_OUTILS_DEVELOPPEURS ?"var _OUTILS_DEVELOPPEURS=true;":"") .
			"\nvar ajax_image_searching = \n'<div style=\"float: ".$GLOBALS['spip_lang_right'].";\"><img src=\"".url_absolue(_DIR_IMG_PACK."searching.gif")."\" alt=\"\" /></div>';" .
			"\nvar stat = " . ($stat ? 1 : 0) .
			"\nvar largeur_icone = " .
			intval(_LARGEUR_ICONES_BANDEAU) .
			"\nvar  bug_offsetwidth = " .
// uniquement affichage ltr: bug Mozilla dans offsetWidth quand ecran inverse!
			((($spip_lang_left == "left") &&
			  (($browser_name != "MSIE") ||
			   ($browser_version >= 6))) ? 1 : 0) .
			"\nvar confirm_changer_statut = '" .
			unicode_to_javascript(addslashes(html2unicode(_T("confirm_changer_statut")))) . 
			"';\n") .
		//plugin needed to fix the select showing through the submenus o IE6  
    (($browser_name == "MSIE" && $browser_version <= 6) ? http_script('',_DIR_JAVASCRIPT . 'bgiframe.js'):'' ) .
    http_script('',_DIR_JAVASCRIPT . 'presentation.js');
}

// Fonctions onglets


// http://doc.spip.org/@debut_onglet
function debut_onglet(){

	return "
\n<div style='padding: 7px;'><table cellpadding='0' cellspacing='0' border='0' class='centered'><tr>
";
}

// http://doc.spip.org/@fin_onglet
function fin_onglet(){
	return "</tr></table></div>\n";
}

// http://doc.spip.org/@onglet
function onglet($texte, $lien, $onglet_ref, $onglet, $icone=""){
	global $spip_display, $spip_lang_left ;

	$res = "<td>";
	$res .= "\n<div style='position: relative;'>";
	if ($spip_display != 1) {
		if (strlen($icone) > 0) {
			$res .= "\n<div style='z-index: 2; position: absolute; top: 0px; $spip_lang_left: 5px;'>" .
			  http_img_pack("$icone", "", "") . "</div>";
			$style = " top: 7px; padding-$spip_lang_left: 32px; z-index: 1;";
		} else {
			$style = " top: 7px;";
		}
	}

	if ($onglet != $onglet_ref) {
		$res .= "\n<div onmouseover=\"changeclass(this, 'onglet_on');\" onmouseout=\"changeclass(this, 'onglet');\" class='onglet' style='position: relative;$style'><a href='$lien'>$texte</a></div>";
		$res .= "</div>";
	} else {
		$res .= "\n<div class='onglet_off' style='position: relative;$style'>$texte</div>";
		$res .= "</div>";
	}
	$res .= "</td>";
	return $res;
}

// http://doc.spip.org/@icone
function icone($texte, $lien, $fond, $fonction="", $align="", $echo=false){
	$retour = "<div style='padding-top: 20px;width:100px' class='icone36'>" . icone_inline($texte, $lien, $fond, $fonction, $align) . "</div>";
	if ($echo) echo $retour; else return $retour;
}

// http://doc.spip.org/@icone_inline
function icone_inline($texte, $lien, $fond, $fonction="", $align=""){	
	global $spip_display;

	if ($fonction == "supprimer.gif") {
		$style = 'icone36-danger';
	} else {
		$style = 'icone36';
		if (strlen($fonction) < 3) $fonction = "rien.gif";
	}

	if ($spip_display == 1){
		$hauteur = 20;
		$largeur = 100;
		$title = $alt = "";
	}
	else if ($spip_display == 3){
		$hauteur = 30;
		$largeur = 30;
		$title = "\ntitle=\"$texte\"";
		$alt = $texte;
	}
	else {
		$hauteur = 70;
		$largeur = 100;
		$title = '';
		$alt = $texte;
	}

	$size = 24;
	if (preg_match("/-([0-9]{1,3})[.](gif|png)$/i",$fond,$match))
		$size = $match[1];
	if ($spip_display != 1 AND $spip_display != 4){
		if ($fonction != "rien.gif"){
		  $icone = http_img_pack($fonction, $alt, "$title width='$size' height='$size'\n" .
					  http_style_background($fond, "no-repeat center center"));
		}
		else {
			$icone = http_img_pack($fond, $alt, "$title width='$size' height='$size'");
		}
	} else $icone = '';

	// cas d'ajax_action_auteur: faut defaire le boulot 
	// (il faudrait fusionner avec le cas $javascript)
	if (preg_match(",^<a\shref='([^']*)'([^>]*)>(.*)</a>$,i",$lien,$r))
		list($x,$lien,$atts,$texte)= $r;
	else $atts = '';
	
	if ($align) $align = "float: $align; ";
	$icone = "\n<a style='width: 72px;$align' class='$style'"
	. $atts
	. "\nhref='"
	. $lien
	. "'>"
	. $icone
	. (($spip_display == 3)	? '' : "<span>$texte</span>")
	. "</a>\n";

	return $icone;
}

// http://doc.spip.org/@icone_horizontale
function icone_horizontale($texte, $lien, $fond = "", $fonction = "", $af = true, $javascript='') {
	global $spip_display;

	$retour = '';
	// cas d'ajax_action_auteur: faut defaire le boulot 
	// (il faudrait fusionner avec le cas $javascript)
	if (preg_match(",^<a href='([^']*)'([^>]*)>(.*)</a>$,i",$lien,$r))
	  list($x,$lien,$atts,$texte)= $r;
	else $atts = '';
	$lien = "\nhref='$lien'$atts";

	if ($spip_display != 4) {
	
		if ($spip_display != 1) {
			$retour .= "\n<table class='cellule-h-table' cellpadding='0' style='vertical-align: middle'>"
			. "\n<tr><td><a $javascript$lien class='cellule-h'>"
			. "<span class='cell-i'>" ;
			if ($fonction){
				$retour .= http_img_pack($fonction, $texte, http_style_background($fond, "center center no-repeat"));
			}
			else {
				$retour .= http_img_pack($fond, $texte, "");
			}
			$retour .= "</span></a></td>"
			. "\n<td class='cellule-h-lien'><a $javascript$lien class='cellule-h'>"
			. $texte
			. "</a></td></tr></table>\n";
		}
		else {
			$retour .= "\n<div><a class='cellule-h-texte' $javascript$lien>$texte</a></div>\n";
		}
		if ($fonction == "supprimer.gif")
			$retour = "\n<div class='danger'>$retour</div>";
	} else {
		$retour = "\n<li><a$lien>$texte</a></li>";
	}

	if ($af) echo $retour; else return $retour;
}


// http://doc.spip.org/@gros_titre
function gros_titre($titre, $ze_logo='', $aff=true){
	global $spip_display;
	if ($spip_display == 4) {
		$res = "\n<h1>".typo($titre)."</h1>&nbsp;\n";
	}
	else {
		$res = "\n<div class='verdana2 spip_large ligne_foncee' style='font-weight: bold;'>" .
		  (strlen($ze_logo) <= 3 ? '':  (http_img_pack($ze_logo, "", "style='vertical-align: bottom'") . " &nbsp; ")) .
		  typo($titre) .
		  "</div>\n";
	}
	if ($aff) echo $res; else return $res;
}


//
// Cadre centre (haut de page)
//

// http://doc.spip.org/@debut_grand_cadre
function debut_grand_cadre($return=false){
	global $spip_ecran;
	
	if ($spip_ecran == "large") $largeur = 974;
	else $largeur = 750;
	$res =  "\n<br /><br />\n<div class='table_page' style='width:${largeur}px;'>\n";
	if ($return) return $res; else echo $res;
}

// http://doc.spip.org/@fin_grand_cadre
function fin_grand_cadre($return=false){
	$res = "\n</div>";
	if ($return) return $res; else echo $res;
}

// Cadre formulaires

// http://doc.spip.org/@debut_cadre_formulaire
function debut_cadre_formulaire($style='', $return=false){
	$x = "\n<div class='cadre-formulaire'" .
	  (!$style ? "" : " style='$style'") .
	   ">";
	if ($return) return  $x; else echo $x;
}

// http://doc.spip.org/@fin_cadre_formulaire
function fin_cadre_formulaire($return=false){
	if ($return) return  "</div>\n"; else echo "</div>\n";
}



//
// Debut de la colonne de gauche
//

// http://doc.spip.org/@debut_gauche
function debut_gauche($rubrique = "accueil", $return=false) {
	global $spip_display;
	global $spip_ecran;

	// Ecran panoramique ?
	if ($spip_ecran == "large") {
		$largeur_ecran = 974;
		$rspan = " rowspan='2'";
	}
	else {
		$largeur_ecran = 750;
		$rspan = '';
	}

	// table fermee par fin_gauche()
	// div fermee par debut_droite() ou creer_colonne_droite

	$res = "<br /><table class='table_page' width='$largeur_ecran' cellpadding='0' cellspacing='0' border='0'>
		<tr>\n<td style='width: 200px' class='colonne_etroite serif' valign='top' $rspan>
		\n<div style='width: 200px; overflow:hidden;'>
\n";
		
	if ($spip_display == 4) $res .= "<!-- ";

	if ($return) return $res; else echo $res;
}

// http://doc.spip.org/@fin_gauche
function fin_gauche()
{
	return "</td></tr></table>";
}

//
// Presentation de l''interface privee, marge de droite
//

// http://doc.spip.org/@creer_colonne_droite
function creer_colonne_droite($rubrique="", $return= false){
	static $deja_colonne_droite;
	global $spip_ecran, $spip_lang_rtl, $spip_lang_left;

	if ((!($spip_ecran == "large")) OR $deja_colonne_droite) return '';
	$deja_colonne_droite = true;

	if  (formulaire_large()) {
			$espacement = 17;
			$largeur = 140;
	} else {
			$espacement = 37;
			$largeur = 200;
	}

	$res = "\n</div></td><td style='width: "
	.  $espacement
	.  "px' rowspan='2' class='colonne_etroite'>&nbsp;</td>"
	. "\n<td rowspan='1' class='colonne_etroite'></td>"
	. "\n<td style='width: "
	.  $espacement
	.  "px ' rowspan='2' class='colonne_etroite'>&nbsp;</td>"
	. "\n<td style='width: "
	. $largeur 
	. "px' rowspan='2' align='"
	. $spip_lang_left
	. "' valign='top' class='colonne_etroite'><div style='width:$largeur'>";

	if ($return) return $res; else echo $res;
}

// http://doc.spip.org/@formulaire_large
function formulaire_large()
{
	return isset($_GET['exec'])?preg_match(',^((articles|breves|rubriques)_edit|forum_envoi),', $_GET['exec']):false;
}

// http://doc.spip.org/@debut_droite
function debut_droite($rubrique="", $return= false) {
	global $spip_ecran, $spip_display, $spip_lang_left; 

	$res = '';

	if ($spip_display == 4) $res .= " -->";

	$res .= liste_articles_bloques();

	if ($spip_ecran != "large") {
		$res .= "</div></td><td style='width: 50px'>&nbsp;</td>";
	}
	else {
		$res .= creer_colonne_droite($rubrique, true)
		. "</div></td></tr>\n<tr>";
	}

	if ($spip_ecran == 'large' AND formulaire_large())
		$largeur = 600;
	else
		$largeur = 500;

	$res .= "\n<td style='width:" . $largeur. "px' valign='top' align='" . $spip_lang_left."' rowspan='1' class='serif'>";

	// touche d'acces rapide au debut du contenu : z
	// Attention avant c'etait 's' mais c'est incompatible avec
	// le ctrl-s qui fait "enregistrer"
	$res .= "\n<a id='saut' href='#saut' accesskey='z'></a>\n";

	if ($return) return $res; else echo $res;
}

// http://doc.spip.org/@liste_articles_bloques
function liste_articles_bloques()
{
	global $connect_id_auteur;

	$res = '';
	if ($GLOBALS['meta']["articles_modif"] != "non") {
		include_spip('inc/drapeau_edition');
		$articles_ouverts = liste_drapeau_edition ($connect_id_auteur, 'article');
		if (count($articles_ouverts)) {
			$res .= "\n<div>&nbsp;</div>"
			. "\n<div class='bandeau_rubriques' style='z-index: 1;'>"
			. bandeau_titre_boite2('<b>' . _T('info_cours_edition')  . '</b>', "article-24.gif", 'toile_foncee', 'ligne_blanche')
			. "\n<div class='plan-articles-bloques'>";

			foreach ($articles_ouverts as $row) {
				$ze_article = $row['id_article'];
				$ze_titre = $row['titre'];
				$statut = $row["statut"];

				$res .= "\n<div class='$statut spip_xx-small'>"
				. "\n<div style='float:right; '>"
				. debloquer_article($ze_article,_T('lien_liberer'))
				. "</div>"
				. "<a  href='" 
				. generer_url_ecrire("articles","id_article=$ze_article")
				. "'>$ze_titre</a>"
				. "</div>";
			}

			if (count($articles_ouverts) >= 4) {
				$res .= "\n<div class='spip_x-small'>"
				. "\n<div style='text-align:right; '>"
				. debloquer_article('tous', _T('lien_liberer_tous'))
				. "</div>"
				. "</div>";
			}


			$res .= "</div></div>";
		}
	}
	return $res;
}
	
//
// Fin de page de l'interface privee. 
// Elle comporte une image invisible declenchant une tache de fond

// http://doc.spip.org/@fin_page
function fin_page()
{
	global $spip_display;

	return debut_grand_cadre(true)
	. "\n"
	. (($spip_display == 4)
		? ("<div><a href='"
		   	. parametre_url(self(),'set_disp', '2')
			. "'>"
			.  _T("access_interface_graphique")
			. "</a></div>")
		: ("<div style='text-align: right; ' class='verdana1 spip_xx-small'>"
			. info_copyright()
			. "<br />"
			. _T('info_copyright_doc')
			. '</div>'))

	. fin_grand_cadre(true)
	. "</div>" // cf. div align = center ouverte dans conmmencer_page()
	. $GLOBALS['rejoue_session']
	. '<div style="background-image: url(\''
	. generer_url_action('cron')
	. '\');"></div>'
	. (defined('_TESTER_NOSCRIPT') ? _TESTER_NOSCRIPT : '')
	. "</body></html>\n";
}

// http://doc.spip.org/@info_copyright
function info_copyright() {
	global $spip_version_affichee, $spip_lang;

	$version = $spip_version_affichee;

	//
	// Mention, le cas echeant, de la revision SVN courante
	//
	if ($svn_revision = version_svn_courante(_DIR_RACINE)) {
		$version .= ' ' . (($svn_revision < 0) ? 'SVN ':'')
		. "[<a href='http://trac.rezo.net/trac/spip/changeset/"
		. abs($svn_revision) . "' onclick=\"window.open(this.href); return false;\">"
		. abs($svn_revision) . "</a>]";
	}

	return _T('info_copyright', 
		   array('spip' => "<b>SPIP $version</b> ",
			 'lien_gpl' => 
			 "<a href='". generer_url_ecrire("aide_index", "aide=licence&var_lang=$spip_lang") . "' onclick=\"window.open(this.href, 'spip_aide', 'scrollbars=yes,resizable=yes,width=740,height=580'); return false;\">" . _T('info_copyright_gpl')."</a>"));

}

// http://doc.spip.org/@debloquer_article
function debloquer_article($arg, $texte) {

	// cas d'un article pas liberable : on esst sur sa page d'edition
	if (_request('exec') == 'articles_edit'
	AND $arg == _request('id_article'))
		return '';

	$lien = parametre_url(self(), 'debloquer_article', $arg, '&');
	return "<a href='" .
	  generer_action_auteur('instituer_collaboration',$arg, _DIR_RESTREINT_ABS . $lien) .
	  "' title=\"" .
	  attribut_html($texte) .
	  "\">"
	  . ($arg == 'tous' ? "$texte&nbsp;" : '')
	  . http_img_pack("croix-rouge.gif", ($arg=='tous' ? "" : "X"),
			"width='7' height='7' ") .
	  "</a>";
}

// http://doc.spip.org/@meme_rubrique
function meme_rubrique($id_rubrique, $id, $type, $order='date', $limit=NULL, $ajax=false)
{
	include_spip('inc/afficher_objets');

	// propose de tout publier + nombre d'elements
	// du code experimental qu'on peut activer via un switch
	define('_MODE_MEME_RUBRIQUE', 'non');

	if (!$limit) $limit = 10;

	$table = $type . 's';
	$key = 'id_' . $type;
	$where = ($GLOBALS['auteur_session']['statut'] == '0minirezo')
	? ''
	:  " AND (statut = 'publie' OR statut = 'prop')"; 

	$query = "SELECT $key AS id, titre, statut FROM spip_$table WHERE id_rubrique=$id_rubrique$where AND ($key != $id)";

	$n = spip_num_rows(spip_query($query));

	if (!$n) return '';

	$voss = spip_query($query . " ORDER BY $order DESC LIMIT $limit");

	$limit = $n - $limit;
	$retour = '';
	$fstatut = 'puce_statut_' . $type;
	$idom = 'rubrique_' . $table;

	if (_MODE_MEME_RUBRIQUE == 'oui') {
		$statut = 'prop';// arbitraire

		$puce_rubrique = ($type == 'article')
		  ?  (puce_statut_article(0, $statut, $id_rubrique, $idom).'&nbsp;')
		  : ''; 
# (puce_statut_breve(0, $statut, $id_rubrique, $type) .'&nbsp;'); manque Ajax
	} else $puce_rubrique = '';

	while($row = spip_fetch_array($voss)) {
		$id = $row['id'];
		$num = afficher_numero_edit($id, $key, $type);
		$statut = $row['statut'];
		$statut = $fstatut($id, $statut, $id_rubrique, $type);
		$href = "<a class='verdana1' href='"
		. generer_url_ecrire($type=='article' ? $table : 'breves_voir',"$key=$id")
		. "'>"
		. typo($row['titre'])
		. "</a>";
		$retour .= "<tr class='tr_liste' style='background-color: #e0e0e0;'><td>$statut</td><td>$href</td><td style='width: 25%;'>$num</td></tr>";
	}

	$icone =  $puce_rubrique . '<b>' . _T('info_meme_rubrique')  . '</b>';
	

	$retour = bandeau_titre_boite2($icone,  'article-24.gif', 'toile_blanche', 'ligne_noire')
	. "\n<table class='spip_x-small' style='background-color: #e0e0e0;border: 0px; padding-left:4px; width: 100%;'>"
	. $retour;
	
	if (_MODE_MEME_RUBRIQUE == 'oui')
	$retour .= (($limit <= 0) ? ''
		: "<tr><td colspan='3' style='text-align: center'>+ $limit</td></tr>");

	$retour .= "</table>";

	if ($ajax) return $retour;

	// id utilise dans puce_statut_article
	return "\n<div>&nbsp;</div>"
	. "\n<div id='imgstatut$idom$id_rubrique' class='bandeau_rubriques' style='z-index: 1;'>$retour</div>";
}

//
// Afficher la hierarchie des rubriques
//

// http://doc.spip.org/@afficher_hierarchie
function afficher_hierarchie($id_rubrique) {
	global $spip_lang_left;

	$parents = '';
	$style1 = "$spip_lang_left center no-repeat; padding-$spip_lang_left: 15px";
	$style2 = "margin-$spip_lang_left: 15px;";

	while ($id_rubrique) {

		$res = spip_fetch_array(spip_query("SELECT id_parent, titre, lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));

		if (!$res) break; // rubrique inexistante

		$id_parent = $res['id_parent'];
		changer_typo($res['lang']);

		$logo = (!$id_parent) ? "secteur-12.gif"
		: (acces_restreint_rubrique($id_rubrique)
		? "admin-12.gif" : "rubrique-12.gif");

		$parents = "\n<div class='verdana3' "
		. http_style_background($logo, $style1)
		. "><a href='"
		. generer_url_ecrire("naviguer","id_rubrique=$id_rubrique")
		. "'>"
		. typo(sinon($res['titre'], _T('ecrire:info_sans_titre')))
		. "</a></div>\n<div style='$style2'>"
		. $parents
		. "</div>";

		$id_rubrique = $id_parent;
	}

	return "\n<div class='verdana3' " .
		  http_style_background("racine-site-12.gif", $style1). 
		  "><a href='" . generer_url_ecrire("naviguer","id_rubrique=$id_rubrique") . "'><b>"._T('lien_racine_site')."</b></a>".aide ("rubhier")."</div>\n<div style='$style2'>".$parents."</div>";
}


// http://doc.spip.org/@enfant_rub
function enfant_rub($collection){
	global $spip_display, $spip_lang_left, $spip_lang_right, $spip_lang;

	$voir_logo = ($spip_display != 1 AND $spip_display != 4 AND isset($GLOBALS['meta']['image_process']) AND $GLOBALS['meta']['image_process'] != "non");
		
	if ($voir_logo) {
		$voir_logo = "float: $spip_lang_right; margin-$spip_lang_right: -6px; margin-top: -6px;";
		$chercher_logo = charger_fonction('chercher_logo', 'inc');
	} else $logo ='';

	$res = "";

	$result = spip_query("SELECT id_rubrique, id_parent, titre, descriptif, lang FROM spip_rubriques WHERE id_parent='$collection' ORDER BY 0+titre,titre");

	while($row=spip_fetch_array($result)){
		$id_rubrique=$row['id_rubrique'];
		$id_parent=$row['id_parent'];
		$titre=$row['titre'];
		
		if (autoriser('voir','rubrique',$id_rubrique)){
	
			$les_sous_enfants = sous_enfant_rub($id_rubrique);
	
			changer_typo($row['lang']);
			$lang_dir = lang_dir($row['lang']);	
			$descriptif=propre($row['descriptif']);
	
			if ($voir_logo) {
				if ($logo = $chercher_logo($id_rubrique, 'id_rubrique', 'on')) {
					list($fid, $dir, $nom, $format) = $logo;
					include_spip('inc/filtres_images');
					$logo = image_reduire("<img src='$fid' alt='' />", 48, 36);
					if ($logo)
						$logo =  "\n<div style='$voir_logo'>$logo</div>";
				}
			}
	
			$les_enfants = "\n<div class='enfants'>" .
			  debut_cadre_sous_rub(($id_parent ? "rubrique-24.gif" : "secteur-24.gif"), true) .
			  (is_string($logo) ? $logo : '') .
			  (!$les_sous_enfants ? "" : bouton_block_invisible("enfants$id_rubrique")) .
			  (!acces_restreint_rubrique($id_rubrique) ? "" :
			   http_img_pack("admin-12.gif", '', " width='12' height='12'", _T('image_administrer_rubrique'))) .
			  " <span dir='$lang_dir'><b><a href='" . 
			  generer_url_ecrire("naviguer","id_rubrique=$id_rubrique") .
			  "'>".
			  typo($titre) .
			  "</a></b></span>" .
			  (!$descriptif ? '' : "\n<div class='verdana1'>$descriptif</div>") .
			  (($spip_display == 4) ? '' : $les_sous_enfants) .
			  "\n<div style='clear:both;'></div>"  .
			  fin_cadre_sous_rub(true) .
			  "</div>";
	
			$res .= ($spip_display != 4)
			? $les_enfants
			: "\n<li>$les_enfants</li>";
		}
	}

	changer_typo($spip_lang); # remettre la typo de l'interface pour la suite
	return (($spip_display == 4) ? "\n<ul>$res</ul>\n" :  $res);

}

// http://doc.spip.org/@sous_enfant_rub
function sous_enfant_rub($collection2){
	global $spip_lang_left;

	$result3 = spip_query("SELECT * FROM spip_rubriques WHERE id_parent='$collection2' ORDER BY 0+titre,titre");

	if (!spip_num_rows($result3)) return '';
	$retour = debut_block_invisible("enfants$collection2")."\n<ul style='margin: 0px; padding: 0px; padding-top: 3px;'>\n";
	while($row=spip_fetch_array($result3)){
		$id_rubrique2=$row['id_rubrique'];
		$id_parent2=$row['id_parent'];
		$titre2=$row['titre'];
		changer_typo($row['lang']);
		$lang_dir = lang_dir($row['lang']);
		if (autoriser('voir','rubrique',$id_rubrique2))
			$retour.="\n<li><div class='arial11' " .
			  http_style_background('rubrique-12.gif', "left center no-repeat; padding: 2px; padding-$spip_lang_left: 18px; margin-$spip_lang_left: 3px") . "><a href='" . generer_url_ecrire("naviguer","id_rubrique=$id_rubrique2") . "'><span dir='$lang_dir'>".typo($titre2)."</span></a></div></li>\n";
	}
	$retour .= "</ul>\n\n".fin_block()."\n\n";
	
	return $retour;
}

// http://doc.spip.org/@afficher_enfant_rub
function afficher_enfant_rub($id_rubrique, $bouton=false, $return=false) {
	global  $spip_lang_right, $spip_display;
	
	$les_enfants = enfant_rub($id_rubrique);
	$n = strlen($les_enfants);
	
	if (!($x = strpos($les_enfants,"\n<div class='enfants'>",round($n/2)))) {
		$les_enfants2="";
	}else{
		$les_enfants2 = substr($les_enfants, $x);
		$les_enfants = substr($les_enfants,0,$x);
		if ($spip_display == 4) {
		  $les_enfants .= '</li></ul>';
		  $les_enfants2 = '<ul><li>' . $les_enfants2;
		}
	}

	$res = "\n<div>&nbsp;</div>"
	. "\n<table cellpadding='0' cellspacing='0' border='0' width='100%'>"
	. "\n<tr><td style='width: 50%' valign='top' rowspan='2'>"
	. $les_enfants
	. "</td>\n<td style='width: 20px;' rowspan='2'>"
	. http_img_pack("rien.gif", ' ', "width='20'")
	. "</td>"
	. "\n<td style='width: 50%' valign='top'>"
	. $les_enfants2
	. "&nbsp;"
	. "</td></tr>"
	. "\n<tr><td style='text-align: "
	. $spip_lang_right
	. ";' valign='bottom'>\n<div style='float:"
	. $spip_lang_right
	. "'>"
	. (!$bouton ? ''
		 : (!$id_rubrique
		    ? icone(_T('icone_creer_rubrique'), generer_url_ecrire("rubriques_edit","new=oui&retour=nav"), "secteur-24.gif", "creer.gif",$spip_lang_right, false)
		    : icone(_T('icone_creer_sous_rubrique'), generer_url_ecrire("rubriques_edit","new=oui&retour=nav&id_parent=$id_rubrique"), "rubrique-24.gif", "creer.gif",$spip_lang_right,false)))
	. "</div></td></tr></table>";

	if ($return) return $res; else echo $res;
}

// Pour construire des menu avec SELECTED
// http://doc.spip.org/@mySel
function mySel($varaut,$variable, $option = NULL) {
	$res = ' value="'.$varaut.'"' . (($variable==$varaut) ? ' selected="selected"' : '');

	return  (!isset($option) ? $res : "<option$res>$option</option>\n");
}


// Voir en ligne, ou apercu, ou rien (renvoie tout le bloc)
// http://doc.spip.org/@voir_en_ligne
function voir_en_ligne ($type, $id, $statut=false, $image='racine-24.gif', $af = true) {
	global $connect_statut;

	$en_ligne = $message = '';
	switch ($type) {
		case 'article':
			if ($statut == "publie" AND $GLOBALS['meta']["post_dates"] == 'non') {
				$n = spip_fetch_array(spip_query("SELECT id_article FROM spip_articles WHERE id_article=$id AND date<=NOW()"));
				if (!$n) $statut = 'prop';
			}
			if ($statut == 'publie')
				$en_ligne = 'calcul';
			else if ($statut == 'prop')
				$en_ligne = 'preview';
			break;
		case 'rubrique':
			if ($id > 0)
				if ($statut == 'publie')
					$en_ligne = 'calcul';
				else
					$en_ligne = 'preview';
			break;
		case 'breve':
		case 'auteur':
		case 'site':
			if ($statut == 'publie')
				$en_ligne = 'calcul';
			else if ($statut == 'prop')
				$en_ligne = 'preview';
			break;
		case 'mot':
			$en_ligne = 'calcul';
			break;
	}

	if ($en_ligne == 'calcul')
		$message = _T('icone_voir_en_ligne');
	else if ($en_ligne == 'preview') {
		// est-ce autorise ?
		if (($GLOBALS['meta']['preview'] == 'oui' AND $connect_statut=='0minirezo')
			OR ($GLOBALS['meta']['preview'] == '1comite'))
			$message = _T('previsualiser');
		else
			$message = '';
	}

	if ($message)
	  return icone_horizontale($message, generer_url_action('redirect', "id_$type=$id&var_mode=$en_ligne"), $image, "rien.gif", $af);

}

//
// Creer un bouton qui renvoie vers la bonne url spip_rss
// http://doc.spip.org/@bouton_spip_rss
function bouton_spip_rss($op, $args, $fmt='rss') {

	include_spip('inc/acces');
	$a = '';
	if (is_array($args))
		foreach ($args as $val => $var)
			if ($var) $a .= ':' . $val.'-'.$var;
	$a = substr($a,1);

	$url = generer_url_action('rss', "op=$op" 
			    . (!$a ? "" : "&args=$a")
			    . ('&id=' . $GLOBALS['connect_id_auteur'])
			    . ('&cle=' . afficher_low_sec($GLOBALS['connect_id_auteur'], "rss $op $a"))
			    . ('&lang=' . $GLOBALS['spip_lang']));

	switch($fmt) {
		case 'ical':
			$url = preg_replace(',^.*?://,', 'webcal://', $url)
			  . "&amp;fmt=ical";
			$button = 'iCal';
			break;
		case 'atom':
			$button = 'atom';
			break;
		case 'rss':
		default:
		  
			$button = 'RSS';
			break;
	}

	return "<a href='"
	. $url
	. "'>"
	. http_img_pack('feed.png', $button, '', 'RSS')
	. "</a>";
}
?>
