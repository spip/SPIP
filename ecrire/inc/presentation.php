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

include_spip('inc/agenda'); // inclut inc/layer, inc/texte, inc/filtre
include_spip('inc/boutons');

// Choix dynamique de la couleur

function choix_couleur() {
	global $couleurs_spip;
	if ($couleurs_spip) {
		foreach ($couleurs_spip as $key => $val) {
			echo "<a href=\"".parametre_url(self(), 'set_couleur', $key)."\">" .
				http_img_pack("rien.gif", " ", "width='8' height='8' style='margin: 1px; background-color: ".$val['couleur_claire'].";' onmouseover=\"changestyle('bandeauinterface','visibility', 'visible');\""). "</a>";
		}
	}
}

//
// affiche un bouton imessage
//
function bouton_imessage($destinataire, $row = '') {
	// si on passe "force" au lieu de $row, on affiche l'icone sans verification
	global $connect_id_auteur;
	global $spip_lang_rtl;

	// verifier que ce n'est pas un auto-message
	if ($destinataire == $connect_id_auteur)
		return;
	// verifier que le destinataire a un login
	if ($row != "force") {
		$login_req = spip_query("SELECT login, messagerie FROM spip_auteurs where id_auteur=$destinataire AND en_ligne>DATE_SUB(NOW(),INTERVAL 15 DAY)");
		$row = spip_fetch_array($login_req);

		if (($row['login'] == "") OR ($row['messagerie'] == "non")) {
			return;
		}
	}

	if ($destinataire) $title = _T('info_envoyer_message_prive');
	else $title = _T('info_ecire_message_prive');

	$texte_bouton = http_img_pack("m_envoi$spip_lang_rtl.gif", "m&gt;", "width='14' height='7'", $title);
		
	return "<a href='". generer_url_ecrire("message_edit","new=oui&dest=$destinataire&type=normal"). "' title=\"$title\">$texte_bouton</a>";
}

// Faux HR, avec controle de couleur

function hr($color, $retour = false) {
	$ret = "<div style='height: 1px; margin-top: 5px; padding-top: 5px; border-top: 1px solid $color;'></div>";
	
	if ($retour) return $ret;
	else echo $ret;
}


//
// Cadres
//

function debut_cadre($style, $icone = "", $fonction = "", $titre = "") {
	global $spip_display, $spip_lang_left;
	static $accesskey = 97; // a

	if ($spip_display != 1 AND $spip_display != 4 AND strlen($icone) > 1) {
		$style_gauche = " padding-$spip_lang_left: 38px;";
		$style_cadre = " style='margin-top: 14px;'";
	} else $style_cadre = $style_gauche = '';
	
	// accesskey pour accessibilite espace prive
	if ($accesskey <= 122) // z
	{
		$accesskey_c = chr($accesskey++);
		$ret = "<a name='access-$accesskey_c' href='#access-$accesskey_c' accesskey='$accesskey_c'></a>";
	} else $ret ='';

	if ($style == "e") {
		$ret .= "<div class='cadre-e-noir'$style_cadre><div class='cadre-$style'>";
	}
	else {
		$ret .= "<div class='cadre-$style'$style_cadre>";
	}

	$ret .= "<div style='position: relative;'>";

	if ($spip_display != 1 AND $spip_display != 4 AND strlen($icone) > 1) {
		$ret .= "<div style='position: absolute; top: -16px; $spip_lang_left: 10px;'>";
		if ($fonction) {
			$ret .= "<div " . http_style_background($icone, "no-repeat; padding: 0px; margin: 0px") . ">";
			$ret .= http_img_pack($fonction, "", "");
			$ret .= "</div>";
		}
		else $ret .=  http_img_pack("$icone", "", "");
		$ret .= "</div>";

		$style_cadre = " style='position: relative; top: 15px; margin-bottom: 14px;'";
	}


	if (strlen($titre) > 0) {
		if ($spip_display == 4) {
			$ret .= "<h3 class='cadre-titre'>$titre</h3>";
		} else {
			$ret .= "<div class='cadre-titre' style='margin: 0px;$style_gauche'>$titre</div>";
		}
	}
	
	
	$ret .= "</div>";
	
	$ret .= "<div class='cadre-padding' style='overflow:hidden;'>";


	return $ret;
}

function fin_cadre($style) {

	$ret = "</div>";
	$ret .= "</div>";
	if ($style == "e") $ret .= "</div>";
	if ($style != "forum" AND $style != "thread-forum") $ret .= "<div style='height: 5px;'></div>";

	return $ret;
}


function debut_cadre_relief($icone='', $return = false, $fonction='', $titre = ''){
	$retour_aff = debut_cadre('r', $icone, $fonction, $titre);

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

function fin_cadre_relief($return = false){
	$retour_aff = fin_cadre('r');

	if ($return) return $retour_aff;
	else echo $retour_aff;
}


function debut_cadre_enfonce($icone='', $return = false, $fonction='', $titre = ''){
	$retour_aff = debut_cadre('e', $icone, $fonction, $titre);

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

function fin_cadre_enfonce($return = false){

	$retour_aff = fin_cadre('e');

	if ($return) return $retour_aff;
	else echo $retour_aff;
}


function debut_cadre_sous_rub($icone='', $return = false, $fonction='', $titre = ''){
	$retour_aff = debut_cadre('sous_rub', $icone, $fonction, $titre);
	if ($return) return $retour_aff;
	else echo $retour_aff;
}

function fin_cadre_sous_rub($return = false){
	$retour_aff = fin_cadre('sous_rub');
	if ($return) return $retour_aff;
	else echo $retour_aff;
}



function debut_cadre_forum($icone='', $return = false, $fonction='', $titre = ''){
	$retour_aff = debut_cadre('forum', $icone, $fonction, $titre);

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

function fin_cadre_forum($return = false){
	$retour_aff = fin_cadre('forum');

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

function debut_cadre_thread_forum($icone='', $return = false, $fonction='', $titre = ''){
	$retour_aff = debut_cadre('thread-forum', $icone, $fonction, $titre);

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

function fin_cadre_thread_forum($return = false){
	$retour_aff = fin_cadre('thread-forum');

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

function debut_cadre_gris_clair($icone='', $return = false, $fonction='', $titre = ''){
	$retour_aff = debut_cadre('gris-clair', $icone, $fonction, $titre);

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

function fin_cadre_gris_clair($return = false){
	$retour_aff = fin_cadre('gris-clair');

	if ($return) return $retour_aff;
	else echo $retour_aff;
}


function debut_cadre_couleur($icone='', $return = false, $fonction='', $titre=''){
	$retour_aff = debut_cadre('couleur', $icone, $fonction, $titre);

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

function fin_cadre_couleur($return = false){
	$retour_aff = fin_cadre('couleur');

	if ($return) return $retour_aff;
	else echo $retour_aff;
}


function debut_cadre_couleur_foncee($icone='', $return = false, $fonction='', $titre=''){
	$retour_aff = debut_cadre('couleur-foncee', $icone, $fonction, $titre);

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

function fin_cadre_couleur_foncee($return = false){
	$retour_aff = fin_cadre('couleur-foncee');

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

function debut_cadre_trait_couleur($icone='', $return = false, $fonction='', $titre=''){
	$retour_aff = debut_cadre('trait-couleur', $icone, $fonction, $titre);

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

function fin_cadre_trait_couleur($return = false){
	$retour_aff = fin_cadre('trait-couleur');

	if ($return) return $retour_aff;
	else echo $retour_aff;
}



//
// une boite alerte
//
function debut_boite_alerte() {
	echo "<p><table cellpadding='6' border='0'><tr><td width='100%' bgcolor='red'>";
	echo "<table width='100%' cellpadding='12' border='0'><tr><td width='100%' bgcolor='white'>";
}

function fin_boite_alerte() {
	echo "</td></tr></table>";
	echo "</td></tr></table>";
}


//
// une boite info
//
function debut_boite_info() {
	echo "<div class='cadre-info verdana1'>";
}

function fin_boite_info() {
	//echo "</font></td></tr></table></div>\n\n";
	echo "</div>";
}

//
// une autre boite
//
function bandeau_titre_boite($titre, $afficher_auteurs, $boite_importante = true) {
	global $couleur_foncee;
	if ($boite_importante) {
		$couleur_fond = $couleur_foncee;
		$couleur_texte = '#FFFFFF';
	}
	else {
		$couleur_fond = '#EEEECC';
		$couleur_texte = '#000000';
	}
	echo "<tr bgcolor='$couleur_fond'><td width=\"100%\"><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3 COLOR='$couleur_texte'>";
	echo "<B>$titre</B></FONT></TD>";
	if ($afficher_auteurs){
		echo "<TD WIDTH='100'>";
		echo http_img_pack("rien.gif", " ", "width='100' height='12'");
		echo "</TD>";
	}
	echo "<TD WIDTH='90'>";
	echo http_img_pack("rien.gif", " ", "width='90' height='12'");
	echo "</TD>";
	echo "</TR>";
}
//
// une autre boite
//
function bandeau_titre_boite2($titre, $logo="", $fond="white", $texte="black", $echo = true) {
	global $spip_lang_left, $spip_display;
	
	$retour = '';

	if (strlen($logo) > 0 AND $spip_display != 1 AND $spip_display != 4) {
		$retour .= "<div style='position: relative;'>";
		$retour .= "<div style='position: absolute; top: -12px; $spip_lang_left: 3px;'>" .
		  http_img_pack("$logo", "", "") . "</div>";
		$retour .= "<div style='background-color: $fond; color: $texte; padding: 3px; padding-$spip_lang_left: 30px; border-bottom: 1px solid #444444;' class='verdana2'><b>$titre</b></div>";
	
		$retour .= "</div>";
	} else {
		$retour .= "<h3 style='background-color: $fond; color: $texte; padding: 3px; border-bottom: 1px solid #444444; margin: 0px;' class='verdana2'><b>$titre</b></h3>";
	}

	if ($echo) echo $retour;
	return $retour;
}


//
// La boite raccourcis
//

function debut_raccourcis() {
	global $spip_display;
	echo "<div>&nbsp;</div>";
	creer_colonne_droite();

	debut_cadre_enfonce();
	if ($spip_display != 4) {
		echo "<font face='Verdana,Arial,Sans,sans-serif' size=1>";
		echo "<b>"._T('titre_cadre_raccourcis')."</b><p />";
	} else {
		echo "<h3>"._T('titre_cadre_raccourcis')."</h3>";
		echo "<ul>";
	}
}

function fin_raccourcis() {
	global $spip_display;
	
	if ($spip_display != 4) echo "</font>";
	else echo "</ul>";
	
	fin_cadre_enfonce();
}


// Afficher un petit "+" pour lien vers autre page

function afficher_plus($lien) {
	global $options, $spip_lang_right, $spip_display, $browser_name;
	
	if ($options == "avancees" AND $spip_display != 4) {
		if ($browser_name == "MSIE") 
			return "<a href='$lien'>" .
			  http_img_pack("plus.gif", "+", ""). "</a> ";
		else
			return "<div style='float:$spip_lang_right; padding-top: 2px;'><a href='$lien'>" .
			  http_img_pack("plus.gif", "+", "") ."</a></div>";
	}
}



//
// Fonctions d'affichage
//

function afficher_liste($largeurs, $table, $styles = '') {
	global $browser_name, $spip_display, $spip_lang_left;

	if (!is_array($table)) return "";
	reset($table);
	if ($spip_display != 4) {
		$res = '';
		while (list(, $t) = each($table)) {
			$res .= "\n<tr class='tr_liste'" .
			  (eregi("msie", $browser_name) ? " onmouseover=\"changeclass(this,'tr_liste_over');\" onmouseout=\"changeclass(this,'tr_liste');\"" :'') .
			  ">";
			reset($largeurs);
			if ($styles) reset($styles);
			while (list(, $texte) = each($t)) {
				$style = $largeur = "";
				list(, $largeur) = each($largeurs);
				if ($styles) list(, $style) = each($styles);
				if (!trim($texte)) $texte .= "&nbsp;";
				$res .= "\n<td" .
				  ($largeur ? " width=\"$largeur\"" : '') .
				  ($style ? " class=\"$style\"" : '') .
				  ">$texte\n</td>";
			}
			$res .= "\n</tr>";
		}
	} else {
	  	$res = "\n<ul style='text-align: $spip_lang_left; background-color: white;'>";
		while (list(, $t) = each($table)) {
			$res .= "<li>";
			reset($largeurs);
			if ($styles) reset($styles);
			while (list(, $texte) = each($t)) {
				$style = $largeur = "";
				list(, $largeur) = each($largeurs);
				
				if (!$largeur) {
					$res .= $texte." ";
				}
			}
			$res .= "</li>\n";
		}
		$res .= "\n</ul>";
	}
	return $res;
}

function afficher_tranches_requete($num_rows, $colspan, $tmp_var, $javascript=false, $nb_aff = 10) {
	static $ancre = 0;
	global $spip_lang_right, $spip_display;

	$tmp_var = 't_'. $tmp_var;
	$deb_aff = intval(_request($tmp_var));
	$ancre++;
	$self = self();

	$texte = ($spip_display == 4) ? '' :  "<tr style='background-color: #dddddd;'><td class=\"arial1\" style='border-bottom: 1px solid #444444;' colspan=\"".($colspan - 1)."\">";
	$texte .= "\n<a name='a$ancre'></a>";

	for ($i = 0; $i < $num_rows; $i += $nb_aff){
			$deb = $i + 1;
			$fin = $i + $nb_aff;
			if ($fin > $num_rows) $fin = $num_rows;
			if ($deb > 1) $texte .= " | ";
			if ($deb_aff + 1 >= $deb AND $deb_aff + 1 <= $fin) {
				$texte .= "<B>$deb</B>";
			}
			else {
				$url = parametre_url($self, $tmp_var, $deb-1);
				if ($javascript) {
					$jj = str_replace("::deb::", "&amp;$tmp_var=$deb", $javascript);
					$texte .= "<a onClick=\"$jj; return false;\" href=\"$url#a$ancre\">$deb</a>";
				}
				else $texte .= "<a href=\"$url#a$ancre\">$deb</a>";
			}
	}
	
	if ($spip_display != 4) {
			$texte .= "</td>\n";
			$texte .= "<td class=\"arial2\" style='border-bottom: 1px solid #444444; text-align: $spip_lang_right;' colspan=\"1\" align=\"right\" valign=\"top\">";
	} else {
			$texte .= " | ";
	}
		
	if ($deb_aff == -1) {
			//$texte .= "<B>"._T('info_tout_afficher')."</B>";
	} else {
			$lien = parametre_url(self(), $tmp_var, -1);
			if ($javascript) {
				$jj = str_replace("::deb::", "&amp;$tmp_var=-1", $javascript);
				$texte .= "<a onClick=\"$jj; return false; \" href=\"$lien#a$ancre\"><img src='". _DIR_IMG_PACK . "plus.gif' title='"._T('lien_tout_afficher')."' /></a>";
			}
			else
				$texte .= "<A HREF=\"$lien#a$ancre\"><img src='". _DIR_IMG_PACK . "plus.gif' title='"._T('lien_tout_afficher')."' /></A>";
	}

	if ($spip_display != 4) $texte .= "</td></tr>\n";

	return $texte;
}


function affiche_tranche_bandeau($requete, $icone, $col, $fg, $bg, $tmp_var, $deb_aff, $titre, $force, $largeurs, $styles, $skel, $own='')
{
	global $spip_display ;

	$voir_logo = ($spip_display != 1 AND $spip_display != 4 AND $GLOBALS['meta']['image_process'] != "non");

	$tous_id = array();

	if (!isset($requete['GROUP BY'])) $requete['GROUP BY'] = '';

	$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM " . $requete['FROM'] . ($requete['WHERE'] ? (' WHERE ' . $requete['WHERE']) : '') . ($requete['GROUP BY'] ? (' GROUP BY ' . $requete['GROUP BY']) : '')));
	if (! ($force OR ($cpt = $cpt['n']))) return  array();

	if (!isset($requete["SELECT"])) $requete["SELECT"]= "*";
	if (isset($requete['LIMIT'])) $cpt = min($requete['LIMIT'], $cpt);

	$nb_aff = 1.5 * _TRANCHES;
	$tranches = '';
	if ($cpt > $nb_aff) {
		$nb_aff = (_TRANCHES); 
		$tranches = afficher_tranches_requete($cpt, $col, $tmp_var, '', $nb_aff);
	}

	if ($titre) echo "<div style='height: 12px;'></div>";
	echo "<div class='liste'>";
	echo bandeau_titre_boite2($titre, $icone, $fg, $bg, false);
	echo "<table width='100%' cellpadding='2' cellspacing='0' border='0'>";

	echo $tranches;

	$result = spip_query("SELECT " . $requete['SELECT'] . " FROM " . $requete['FROM'] . ($requete['WHERE'] ? (' WHERE ' . $requete['WHERE']) : '') . ($requete['GROUP BY'] ? (' GROUP BY ' . $requete['GROUP BY']) : '') . ($requete['ORDER BY'] ? (' ORDER BY ' . $requete['ORDER BY']) : '') . " LIMIT " . ($deb_aff >= 0 ? "$deb_aff, $nb_aff" : ($requete['LIMIT'] ? $requete['LIMIT'] : "99999")));

	$table = array();
	while ($row = spip_fetch_array($result)) {
		$table[]= $skel($row, $tous_id, $voir_logo, $own);
	}
	spip_free_result($result);
		
	echo afficher_liste($largeurs, $table, $styles);
	echo "</table>";
	echo "</div>\n";
	return $tous_id;
}


function afficher_liste_debut_tableau() {
	global $spip_display;

	if ($spip_display != 4) return "<table width='100%' cellpadding='2' cellspacing='0' border='0'>";
}

function afficher_liste_fin_tableau() {
	global $spip_display;
	if ($spip_display != 4) return "</table>";
}


function puce_statut_article($id, $statut, $id_rubrique) {
	global $spip_lang_left, $dir_lang, $connect_statut, $options;
	
	switch ($statut) {
	case 'publie':
		$clip = 2;
		$puce = 'verte';
		$title = _T('info_article_publie');
		break;
	case 'prepa':
		$clip = 0;
		$puce = 'blanche';
		$title = _T('info_article_redaction');
		break;
	case 'prop':
		$clip = 1;
		$puce = 'orange';
		$title = _T('info_article_propose');
		break;
	case 'refuse':
		$clip = 3;
		$puce = 'rouge';
		$title = _T('info_article_refuse');
		break;
	case 'poubelle':
		$clip = 4;
		$puce = 'poubelle';
		$title = _T('info_article_supprime');
		break;
	}
	$puce = "puce-$puce.gif";
	
	if ($connect_statut == '0minirezo' AND $options == 'avancees' AND acces_rubrique($id_rubrique)) {
	  // les versions de MSIE ne font pas toutes pareil sur alt/title
	  // la combinaison suivante semble ok pour tout le monde.
	  $titles = array(
			  "blanche" => _T('texte_statut_en_cours_redaction'),
			  "orange" => _T('texte_statut_propose_evaluation'),
			  "verte" => _T('texte_statut_publie'),
			  "rouge" => _T('texte_statut_refuse'),
			  "poubelle" => _T('texte_statut_poubelle'));
	  $action = "onmouseover=\"montrer('statutdecalarticle$id');\"";
	  $inser_puce = "\n<div class='puce_article' id='statut$id'$dir_lang>"
			. "\n<div class='puce_article_fixe' $action>" .
		  http_img_pack("$puce", "", "id='imgstatutarticle$id' style='margin: 1px;'") ."</div>"
			. "\n<div class='puce_article_popup' id='statutdecalarticle$id' onmouseout=\"cacher('statutdecalarticle$id');\" style=' margin-left: -".((11*$clip)+1)."px;'>\n"
			. afficher_script_statut($id, 'article', -1, 'puce-blanche.gif', 'prepa', $titles['blanche'], $action)
			. afficher_script_statut($id, 'article', -12, 'puce-orange.gif', 'prop', $titles['orange'], $action)
			. afficher_script_statut($id, 'article', -23, 'puce-verte.gif', 'publie', $titles['verte'], $action)
			. afficher_script_statut($id, 'article', -34, 'puce-rouge.gif', 'refuse', $titles['rouge'], $action)
			. afficher_script_statut($id, 'article', -45, 'puce-poubelle.gif', 'poubelle', $titles['poubelle'], $action)
		. "</div></div>";
	} else {
		$inser_puce = http_img_pack("$puce", "", "id='imgstatutarticle$id' style='margin: 1px;'");
	}
	return $inser_puce;
}

function puce_statut_breve($id, $statut, $type, $droit) {
	global $spip_lang_left, $dir_lang;

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
	$inser_puce = http_img_pack($puce, "", "id='img$type1' style='margin: 1px;'");

	if (!$droit) return $inser_puce;
	
	$type2 = "statutdecal$type$id";
	$action = "onmouseover=\"montrer('$type2');\"\n";

	  // les versions de MSIE ne font pas toutes pareil sur alt/title
	  // la combinaison suivante semble ok pour tout le monde.

	return	"<div class='puce_breve' id='$type1'$dir_lang>"
		. "<div class='puce_breve_fixe' $action>"
		. $inser_puce
		. "</div>"
		. "\n<div class='puce_breve_popup' id='$type2' onmouseout=\"cacher('$type2');\" style=' margin-left: -".((9*$clip)+1)."px;'>\n"
		. afficher_script_statut($id, $type, -1, $puces[0], 'prop',_T('texte_statut_propose_evaluation'), $action)
		. afficher_script_statut($id, $type, -10, $puces[1], 'publie',_T('texte_statut_publie'), $action)
	  	. afficher_script_statut($id, $type, -19, $puces[2], 'refuse',_T('texte_statut_refuse'), $action)
		.  "</div></div>";
}

function afficher_script_statut($id, $type, $n, $img, $statut, $title, $act)
{
  return http_href_img("javascript:selec_statut('$id', '$type', -1, '" .
		      http_wrapper($img) .
		      "', '" .
		       generer_action_auteur('instituer', "$type-$id-$statut") .
		      "');",
		      $img,
			"title=\"".$title."\"",
			'','','',
		      $act);
}

//
// Afficher tableau d'articles
//
function afficher_articles($titre_table, $requete, $afficher_visites = false, $afficher_auteurs = true, $obligatoire = false, $afficher_cadre = true, $afficher_descriptif = true) {

	global $connect_id_auteur, $connect_statut, $dir_lang;
	global $options, $spip_display;
	global $spip_lang_left, $spip_lang_right;

	if (!isset($requete['FROM']))  $requete['FROM'] = 'spip_articles AS articles';
	// Preparation pour basculer vers liens de traductions
	$afficher_trad = ($GLOBALS['meta']['gerer_trad'] == "oui");

	if ($afficher_trad) {
		$jjscript_trad["fonction"] = "afficher_articles_trad";
		$jjscript_trad["titre_table"] = $titre_table;
		$jjscript_trad["requete"] = $requete;
		$jjscript_trad["afficher_visites"] = $afficher_visites;
		$jjscript_trad["afficher_auteurs"] = $afficher_auteurs;
		$jjscript_trad = (serialize($jjscript_trad));
		$hash = "0x".substr(md5($connect_id_auteur.$jjscript_trad), 0, 16);
		$div_trad = substr($hash, 2, 6);

		$res_proch = spip_query("SELECT id_ajax_fonc FROM spip_ajax_fonc WHERE hash=$hash AND id_auteur=$connect_id_auteur ORDER BY id_ajax_fonc DESC LIMIT 1");
		if ($row = spip_fetch_array($res_proch)) {
			$id_ajax_trad = $row["id_ajax_fonc"];
		} else  {
			include_spip ('base/abstract_sql');
			$id_ajax_trad = spip_abstract_insert("spip_ajax_fonc", "(id_auteur, variables, hash, date)", "($connect_id_auteur, " . spip_abstract_quote($jjscript_trad) . ", $hash, NOW())");
		}
	}

	$activer_statistiques = $GLOBALS['meta']["activer_statistiques"];
	$afficher_visites = ($afficher_visites AND $connect_statut == "0minirezo" AND $activer_statistiques != "non");
	$afficher_langue = false;
	$langue_defaut = $GLOBALS['meta']['langue_site'];
	// Preciser la requete (alleger les requetes)
	if (!isset($requete['SELECT'])) {
		$requete['SELECT'] = "articles.id_article, articles.titre, articles.id_rubrique, articles.statut, articles.date";

		if (($GLOBALS['meta']['multi_rubriques'] == 'oui' AND (!isset($GLOBALS['id_rubrique']))) OR $GLOBALS['meta']['multi_articles'] == 'oui') {
			$afficher_langue = true;
			if (isset($GLOBALS['langue_rubrique'])) $langue_defaut = $GLOBALS['langue_rubrique'];
			$requete['SELECT'] .= ", articles.lang";
		}
		if ($afficher_visites)
			$requete['SELECT'] .= ", articles.visites, articles.popularite";
		if ($afficher_descriptif)
			$requete['SELECT'] .= ", articles.descriptif";
	}
	
	if ($options == "avancees")  $ajout_col = 1;
	else $ajout_col = 0;
	
	$jjscript["fonction"] = "afficher_articles";
	$jjscript["titre_table"] = $titre_table;
	$jjscript["requete"] = $requete;
	$jjscript["afficher_visites"] = $afficher_visites;
	$jjscript["afficher_auteurs"] = $afficher_auteurs;
	$jjscript = (serialize($jjscript));
	$hash = "0x".substr(md5($connect_id_auteur.$jjscript), 0, 16);

	$tmp_var = substr($hash, 2, 6);
	$javascript = "charger_id_url('" . generer_url_ecrire("ajax_page","fonction=sql&id_ajax_fonc=::id_ajax_fonc::::deb::", true) . "','$tmp_var')";

	if (!isset($requete['GROUP BY'])) $requete['GROUP BY'] = '';
	$tous_id = array();
	$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM " . $requete['FROM'] . ($requete['WHERE'] ? (' WHERE ' . $requete['WHERE']) : '') . ($requete['GROUP BY'] ? (' GROUP BY ' . $requete['GROUP BY']) : '')));
	if (! ($obligatoire OR ($cpt = $cpt['n']))) return $tous_id ;
	if (isset($requete['LIMIT'])) $cpt = min($requete['LIMIT'], $cpt);

	$nb_aff = 1.5 * _TRANCHES;
	$deb_aff = intval(_request('t_' .$tmp_var));
	$tranches = '';
	if ($cpt > $nb_aff) {
		$nb_aff = (_TRANCHES); 
		$tranches = afficher_tranches_requete($cpt, $afficher_auteurs ? 4 + $ajout_col : 3 + $ajout_col, $tmp_var, $javascript, $nb_aff);

	}

	$requete['FROM'] = preg_replace("/(spip_articles AS \w*)/", "\\1 LEFT JOIN spip_petitions AS petitions USING (id_article)", $requete['FROM']);

	$requete['SELECT'] .= ", petitions.id_article AS petition ";

	$res_proch = spip_query("SELECT id_ajax_fonc FROM spip_ajax_fonc WHERE hash=$hash AND id_auteur=$connect_id_auteur ORDER BY id_ajax_fonc DESC LIMIT 1");
	if ($row = spip_fetch_array($res_proch)) {
			$id_ajax_fonc = $row["id_ajax_fonc"];
	} else  {
			include_spip('base/abstract_sql');
			$id_ajax_fonc = spip_abstract_insert("spip_ajax_fonc", "(id_auteur, variables, hash, date)", "($connect_id_auteur, " . spip_abstract_quote($jjscript) . ", $hash, NOW())");
	}

	if (!$deb_aff) {

			if ($afficher_trad) echo "<div id='$div_trad'>";
			echo "<div style='height: 12px;'></div>";
			echo "<div class='liste'>";

			$id_img = "img_".$tmp_var;
			$texte_img = http_img_pack("searching.gif", "*", "style='visibility: hidden; float: $spip_lang_right' id = '$id_img'");

			if ($afficher_trad) {
				$texte_img .= http_img_pack("searching.gif", "*", "style='visibility: hidden; float: $spip_lang_right' id = 'img_$div_trad'");
				$texte_img .= "<div style='float: $spip_lang_right;'><a href=\"javascript:charger_id_url('" . generer_url_ecrire("ajax_page", "fonction=sql&id_ajax_fonc=$id_ajax_trad"). "','$div_trad');\"><img src='". _DIR_IMG_PACK . "langues-12.gif' /></a></div>";
			}
			bandeau_titre_boite2($texte_img.$titre_table, "article-24.gif");

			echo "<div id='$tmp_var'>";

	}
		
	$voir_logo = ($spip_display != 1 AND $spip_display != 4 AND $GLOBALS['meta']['image_process'] != "non");
		

	//echo "<table width='100%' cellpadding='2' cellspacing='0' border='0'>";
	echo afficher_liste_debut_tableau(), str_replace("::id_ajax_fonc::", $id_ajax_fonc, $tranches);

	$result = spip_query("SELECT " . $requete['SELECT'] . " FROM " . $requete['FROM'] . ($requete['WHERE'] ? (' WHERE ' . $requete['WHERE']) : '') . ($requete['GROUP BY'] ? (' GROUP BY ' . $requete['GROUP BY']) : '') . ($requete['ORDER BY'] ? (' ORDER BY ' . $requete['ORDER BY']) : '') . " LIMIT " . ($deb_aff >= 0 ? "$deb_aff, $nb_aff" : ($requete['LIMIT'] ? $requete['LIMIT'] : "99999")));

	$table = array();
	while ($row = spip_fetch_array($result)) {
		$table[]= afficher_articles_boucle($row, $tous_id, $afficher_auteurs, $afficher_langue, $langue_defaut, $voir_logo);
	}
	spip_free_result($result);

	if ($options == "avancees") { // Afficher le numero (JMB)
		if ($afficher_auteurs) {
				$largeurs = array(11, '', 80, 100, 50);
				$styles = array('', 'arial2', 'arial1', 'arial1', 'arial1');
			} else {
				$largeurs = array(11, '', 100, 50);
				$styles = array('', 'arial2', 'arial1', 'arial1');
			}
	} else {
			if ($afficher_auteurs) {
				$largeurs = array(11, '', 100, 100);
				$styles = array('', 'arial2', 'arial1', 'arial1');
			} else {
				$largeurs = array(11, '', 100);
				$styles = array('', 'arial2', 'arial1');
			}
	}
	echo afficher_liste($largeurs, $table, $styles);

	//echo "</table>";
	echo afficher_liste_fin_tableau();
	echo "</div>";
		
	if (!$deb_aff) {
			echo "</div>";
			if ($afficher_trad) echo "</div>";
			
	}
		
	//if ($afficher_cadre) fin_cadre_gris_clair();

	return $tous_id;
}

function afficher_articles_boucle($row, &$tous_id, $afficher_auteurs, $afficher_langue, $langue_defaut, $voir_logo)
{
  global $connect_id_auteur, $dir_lang, $options, $spip_lang_right;

	$vals = '';

	$id_article = $row['id_article'];
	$tous_id[] = $id_article;
	$titre = sinon($row['titre'], _T('ecrire:info_sans_titre'));
	$id_rubrique = $row['id_rubrique'];
	$date = $row['date'];
	$statut = $row['statut'];
	if ($lang = $row['lang']) changer_typo($lang);
	$descriptif = $row['descriptif'];
	if ($descriptif) $descriptif = ' title="'.attribut_html(typo($descriptif)).'"';
	$petition = $row['petition'];

	if ($afficher_auteurs) {
		$les_auteurs = "";
		$result_auteurs = spip_query("SELECT auteurs.id_auteur, nom, messagerie, login, bio FROM spip_auteurs AS auteurs, spip_auteurs_articles AS lien WHERE lien.id_article=$id_article AND auteurs.id_auteur=lien.id_auteur");


		while ($row = spip_fetch_array($result_auteurs)) {
			$id_auteur = $row['id_auteur'];
			$nom_auteur = typo($row['nom']);
			$auteur_messagerie = $row['messagerie'];

			if ($bio = texte_backend(supprimer_tags(couper($row['bio'],50))))
				$bio = " title=\"$bio\"";


			$les_auteurs .= ", <a href='" . generer_url_ecrire("auteurs_edit","id_auteur=$id_auteur") . "'$bio>$nom_auteur</a>";
			if ($id_auteur != $connect_id_auteur AND $auteur_messagerie != "non") {
				$les_auteurs .= "&nbsp;".bouton_imessage($id_auteur, $row);
			}
		}
		$les_auteurs = substr($les_auteurs, 2);
	}

	// La petite puce de changement de statut
	$vals[] = puce_statut_article($id_article, $statut, $id_rubrique);

	// Le titre (et la langue)
	$s = "<div>";

	if (acces_restreint_rubrique($id_rubrique))
		$s .= http_img_pack("admin-12.gif", "", "width='12' height='12'", _T('titre_image_admin_article'));

	$s .= "<a href='" . generer_url_ecrire("articles","id_article=$id_article") .
		"'$descriptif$dir_lang style=\"display:block;\">";

	if ($voir_logo) {
		$logo_f = charger_fonction('chercher_logo', 'inc');
		if ($logo = $logo_f($id_article, 'id_article', 'on'))
			if ($logo = decrire_logo("id_article", 'on', $id_article, 26, 20, $logo))
				$s .= "<div style='float: $spip_lang_right; margin-top: -2px; margin-bottom: -2px;'>$logo</div>";
	}

	$s .= typo($titre);
	if ($afficher_langue AND $lang != $langue_defaut)
		$s .= " <font size='1' color='#666666'$dir_lang>(".traduire_nom_langue($lang).")</font>";
	if ($petition) $s .= " <font size=1 color='red'>"._T('lien_petitions')."</font>";
	$s .= "</a>";
	$s .= "</div>";
	
	$vals[] = $s;

	// Les auteurs
	if ($afficher_auteurs) $vals[] = $les_auteurs;

	// La date
	$vals[] = affdate_jourcourt($date);

	// Le numero (moche)
	if ($options == "avancees") {
		$vals[] = "<b>"._T('info_numero_abbreviation')."$id_article</b>";
	}
	

	return $vals;
}


function afficher_articles_trad($titre_table, $requete, $afficher_visites = false, $afficher_auteurs = true,
		$obligatoire = false, $afficher_cadre = true, $afficher_descriptif = true) {

	global $connect_id_auteur, $connect_statut, $dir_lang;
	global $options, $spip_lang_left, $spip_lang_right;

	if (!$requete['FROM']) $requete['FROM']=  'spip_articles AS articles';

	$langues_site = explode(',', $GLOBALS['meta']['langues_multilingue']);

	// Preparation pour basculer vers liste normale
		$jjscript_trad["fonction"] = "afficher_articles";
		$jjscript_trad["titre_table"] = $titre_table;
		$jjscript_trad["requete"] = $requete;
		$jjscript_trad["afficher_visites"] = $afficher_visites;
		$jjscript_trad["afficher_auteurs"] = $afficher_auteurs;
		$jjscript_trad = (serialize($jjscript_trad));
		$hash = "0x".substr(md5($connect_id_auteur.$jjscript_trad), 0, 16);
		$div_trad = substr($hash, 2, 6);

		$res_proch = spip_query("SELECT id_ajax_fonc FROM spip_ajax_fonc WHERE hash=$hash AND id_auteur=$connect_id_auteur ORDER BY id_ajax_fonc DESC LIMIT 1");
		if ($row = spip_fetch_array($res_proch)) {
			$id_ajax_trad = $row["id_ajax_fonc"];
		} else  {
			include_spip('base/abstract_sql');
			$id_ajax_trad = spip_abstract_insert("spip_ajax_fonc", "(id_auteur, variables, hash, date)", "($connect_id_auteur, " . spip_abstract_quote($jjscript_trad) . ", $hash, NOW())");
		}


	$activer_statistiques = $GLOBALS['meta']["activer_statistiques"];
	$afficher_visites = ($afficher_visites AND $connect_statut == "0minirezo" AND $activer_statistiques != "non");

	// Preciser la requete (alleger les requetes)
	if (!$requete['SELECT']) {
		$requete['SELECT'] = "articles.id_article, articles.titre, articles.id_rubrique, articles.statut, articles.date, articles.id_trad, articles.lang";

	}
	
	if ($options == "avancees")  $ajout_col = 1;
	else $ajout_col = 0;

	
	$jjscript["fonction"] = "afficher_articles_trad";
	$jjscript["titre_table"] = $titre_table;
	$jjscript["requete"] = $requete;
	$jjscript["afficher_visites"] = $afficher_visites;
	$jjscript["afficher_auteurs"] = $afficher_auteurs;
	$jjscript = (serialize($jjscript));
	$hash = "0x".substr(md5($connect_id_auteur.$jjscript), 0, 16);
	$tmp_var = substr($hash, 2, 6);	
	$javascript = "charger_id_url('" . generer_url_ecrire("ajax_page", 'fonction=sql&id_ajax_fonc=::id_ajax_fonc::::deb::') . "','$tmp_var')";

	$tous_id = array();
	$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM " . $requete['FROM'] . ($requete['WHERE'] ? (' WHERE ' . $requete['WHERE']) : '') . ($requete['GROUP BY'] ? (' GROUP BY ' . $requete['GROUP BY']) : '')));
	if (! ($obligatoire OR ($cpt = $cpt['n']))) return $tous_id ;
	if ($requete['LIMIT']) $cpt = min($requete['LIMIT'], $cpt);

	$nb_aff = 1.5 * _TRANCHES;
	$deb_aff = intval(_request('t_' .$tmp_var));
	$tranches = '';
	if ($cpt > $nb_aff) {
		$nb_aff = (_TRANCHES); 
		$tranches = afficher_tranches_requete($cpt,  4, $tmp_var, $javascript, $nb_aff);
	}

	$res_proch = spip_query("SELECT id_ajax_fonc FROM spip_ajax_fonc WHERE hash=$hash AND id_auteur=$connect_id_auteur ORDER BY id_ajax_fonc DESC LIMIT 1");
	if ($row = spip_fetch_array($res_proch)) {
			$id_ajax_fonc = $row["id_ajax_fonc"];
	} else  {
			include_spip('base/abstract_sql');
			$id_ajax_fonc = spip_abstract_insert("spip_ajax_fonc", "(id_auteur, variables, hash, date)", "($connect_id_auteur, " . spip_abstract_quote($jjscript) . ", $hash, NOW())");
		}

	if (!$deb_aff) {
			echo "<div id='$div_trad'>";
			echo "<div style='height: 12px;'></div>";
			echo "<div class='liste'>";

			$id_img = "img_".$tmp_var;
			$texte_img = http_img_pack("searching.gif", "*", "style='visibility: hidden; float: $spip_lang_right' id = '$id_img'");
			
			$texte_img .= http_img_pack("searching.gif", "*", "style='visibility: hidden; float: $spip_lang_right' id = 'img_$div_trad'");

			$texte_img .= "<div style='float: $spip_lang_right;'><a href=\"javascript:charger_id_url('" . generer_url_ecrire("ajax_page", "fonction=sql&id_ajax_fonc=$id_ajax_trad") . "','$div_trad');\"><img src='". _DIR_IMG_PACK . "langues-off-12.gif' /></a></div>";

			bandeau_titre_boite2($texte_img.$titre_table, "article-24.gif");

			echo "<div id='$tmp_var'>";
	}
		
	//echo "<table width='100%' cellpadding='2' cellspacing='0' border='0'>";
	echo afficher_liste_debut_tableau(), str_replace("::id_ajax_fonc::", "$id_ajax_fonc", $tranches);

	$result = spip_query("SELECT " . $requete['SELECT'] . " FROM " . $requete['FROM'] . ($requete['WHERE'] ? (' WHERE ' . $requete['WHERE']) : '') . ($requete['GROUP BY'] ? (' GROUP BY ' . $requete['GROUP BY']) : '') . ($requete['ORDER BY'] ? (' ORDER BY ' . $requete['ORDER BY']) : '') . " LIMIT " . ($deb_aff >= 0 ? "$deb_aff, $nb_aff" : ($requete['LIMIT'] ? $requete['LIMIT'] : "99999")));

	$table = array();
	while ($row = spip_fetch_array($result)) {
		$table[]=afficher_articles_trad_boucle($row, $tous_id, $afficher_langue, $langue_defaut, $langues_site);
	}
	spip_free_result($result);

	$largeurs = array(11, 24, '', '1');
	$styles = array('', 'arial1', 'arial1', '');

	echo afficher_liste($largeurs, $table, $styles);

	//echo "</table>";
	echo afficher_liste_fin_tableau();
	echo "</div>";
		
	if (!$GLOBALS["t_$tmp_var"]) echo "</div>";
		
	//if ($afficher_cadre) fin_cadre_gris_clair();

	return $tous_id;
}

function afficher_articles_trad_boucle($row, &$tous_id, $afficher_langue, $langue_defaut, $langues_site)
{
	global $dir_lang,  $spip_lang_right;

	$vals = '';

	$id_article = $row['id_article'];
	$tous_id[] = $id_article;
	$titre = sinon($row['titre'], _T('ecrire:info_sans_titre'));
	$id_rubrique = $row['id_rubrique'];
	$date = $row['date'];
	$statut = $row['statut'];
	$id_trad = $row['id_trad'];
	$lang = $row['lang'];


	// La petite puce de changement de statut
	$vals[] = puce_statut_article($id_article, $statut, $id_rubrique);

	// Le titre (et la langue)
	
	$langues_art = "";
	$dates_art = "";
	$l = "";
	$res_trad = spip_query("SELECT id_article, lang, date_modif  FROM spip_articles WHERE id_trad = $id_trad AND id_trad > 0");
	while ($row_trad = spip_fetch_array($res_trad)) {
		$id_article_trad = $row_trad["id_article"];
		$lang_trad = $row_trad["lang"];
		$date_trad = $row_trad["date_modif"];
		
		$dates_art[$lang_trad] = $date_trad;
		$langues_art[$lang_trad] = $id_article_trad;
		if ($id_article_trad == $id_trad) $date_ref = $date;
	}

	reset($langues_site);
	$span_lang = false;
	while (list(,$k) = each($langues_site)) {
		if ($langues_art[$k]) {
			if ($langues_art[$k] == $id_trad) {
				$span_lang = "<a href='" . generer_url_ecrire("articles","id_article=".$langues_art[$k]) . "'><span class='lang_base'>$k</a></a>";
				$l .= $span_lang;
			} else {
				$date = $dates_art[$k];
				if ($date < $date_ref) 
					$l .= "<a href='" . generer_url_ecrire("articles","id_article=".$langues_art[$k]) . "' class='claire'>$k</a>";
				else $l .= "<a href='" . generer_url_ecrire("articles","id_article=".$langues_art[$k]) . "' class='foncee'>$k</a>";
			}			
		}
#				else $l.= "<span class='creer'>$k</span>";
	}
			
	if (!$span_lang)
		$span_lang = "<a href='" . generer_url_ecrire("articles","id_article=$id_article") . "'><span class='lang_base'>$lang</a></a>";

	$vals[] = "<div style='text-align: center;'>$span_lang</div>";
			
			
	$s = "<div>";
	$s .= "<div style='float: $spip_lang_right; margin-right: -10px;'>$l</div>";
	
	if (acces_restreint_rubrique($id_rubrique))
		$s .= http_img_pack("admin-12.gif", "", "width='12' height='12'", _T('titre_image_admin_article'));

	$s .= "<a href='" . generer_url_ecrire("articles","id_article=$id_article") . "'$dir_lang style=\"display:block;\">";
			
			
	if ($id_article == $id_trad) $titre = "<b>$titre</b>";
			
	$s .= typo($titre);
	if ($afficher_langue AND $lang != $langue_defaut)
		$s .= " <font size='1' color='#666666'$dir_lang>(".traduire_nom_langue($lang).")</font>";

	$s .= "</a>";
	$s .= "</div>";
	
	$vals[] = $s;
	
	$vals[] = "";
	
	return  $vals;
}

//
// Afficher tableau de breves
//

function afficher_breves($titre_table, $requete, $affrub=false) {
	global  $couleur_foncee, $options;	
 
	if (($GLOBALS['meta']['multi_rubriques'] == 'oui'
	     AND (!isset($GLOBALS['id_rubrique'])))
	OR $GLOBALS['meta']['multi_articles'] == 'oui') {
		$afficher_langue = true;

		if (isset($GLOBALS['langue_rubrique'])) $langue_defaut = $GLOBALS['langue_rubrique'];
		else $langue_defaut = $GLOBALS['meta']['langue_site'];
	}


	$tmp_var = substr(md5(join('', $requete)), 0, 4);
	$deb_aff = intval(_request('t_' .$tmp_var));
	$col = ($options == "avancees") ? 4 : 3;
	if ($options == "avancees") {
		if ($affrub) $largeurs = array('7', '', '188', '35');
		else  $largeurs = array('7','', '100', '35');
		$styles = array('', 'arial11', 'arial1', 'arial1');
	} else {
		if ($affrub) $largeurs = array('7','', '188');
		else  $largeurs = array('7','', '100');
		$styles = array('','arial11', 'arial1');
	}

	return affiche_tranche_bandeau($requete, "breve-24.gif", 3, $couleur_foncee, "white", $tmp_var, $deb_aff, $titre_table, false, $largeurs, $styles, 'afficher_breves_boucle', array( $afficher_langue, $affrub, $langue_defaut));

}

function afficher_breves_boucle($row, &$tous_id,  $voir_logo, $own)
{
  global  $dir_lang, $options, $connect_statut, $spip_lang_right;
	$droit = ($connect_statut == '0minirezo' && $options == 'avancees');
	list($afficher_langue, $affrub, $langue_defaut) = $own;
	$vals = '';

	$id_breve = $row['id_breve'];
	$tous_id[] = $id_breve;
	$date_heure = $row['date_heure'];
	$titre = sinon($row['titre'], _T('ecrire:info_sans_titre'));
	$statut = $row['statut'];
	if (isset($row['lang']))
	  changer_typo($lang = $row['lang']);
	else $lang = $langue_defaut;
	$id_rubrique = $row['id_rubrique'];
			
	$vals[] = puce_statut_breve($id_breve, $statut, 'breve', ($droit && acces_rubrique($id_rubrique)), $id_rubrique);

	$s = "<div>";
	$s .= "<a href='" . generer_url_ecrire("breves_voir","id_breve=$id_breve") . "' style=\"display:block;\">";

	if ($voir_logo) {
		$logo_f = charger_fonction('chercher_logo', 'inc');
		if ($logo = $logo_f($id_breve, 'id_breve', 'on'))
		  if ($logo = decrire_logo("id_breve", 'on', $id_breve, 26, 20, $logo))
				$s .= "<div style='float: $spip_lang_right; margin-top: -2px; margin-bottom: -2px;'>$logo</div>";
	}

	$s .= typo($titre);
	if ($afficher_langue AND $lang != $langue_defaut)
		$s .= " <font size='1' color='#666666'$dir_lang>(".traduire_nom_langue($lang).")</font>";
	$s .= "</a>";

	$s .= "</div>";
	$vals[] = $s;

	$s = "";
	if ($affrub) {
		$rub = spip_fetch_array(spip_query("SELECT id_rubrique, titre FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
		$id_rubrique = $rub['id_rubrique'];
		$s .= "<a href='" . generer_url_ecrire("naviguer","id_rubrique=$id_rubrique") . "' style=\"display:block;\">".typo($rub['titre'])."</a>";
	} else if ($statut != "prop")
			$s = affdate_jourcourt($date_heure);
		else
			$s .= _T('info_a_valider');
	$vals[] = $s;
			
	if ($options == "avancees") {
		$vals[] = "<b>"._T('info_numero_abbreviation')."$id_breve</b>";
	}
			
	return $vals;
}


//
// Afficher tableau de rubriques
//

function afficher_rubriques($titre_table, $requete) {
	global $options;

        $tmp_var = substr(md5(join('', $requete)), 0, 4);
        $deb_aff = intval(_request('t_' .$tmp_var));
	$col = ($options == "avancees") ? 4 : 3;
	$largeurs = array('12','', '');
	$styles = array('', 'arial2', 'arial11');
	return affiche_tranche_bandeau($requete, "rubrique-24.gif", $col, "#999999", "white", $tmp_var, $deb_aff, $titre_table, false, $largeurs, $styles, 'afficher_rubriques_boucle');
}

function afficher_rubriques_boucle($row, &$tous_id)
{
	$vals = '';

	$id_rubrique = $row['id_rubrique'];
	$id_parent = $row['id_parent'];
	$tous_id[] = $id_rubrique;
	$titre = sinon($row['titre'], _T('ecrire:info_sans_titre'));
	$lang = traduire_nom_langue($row['lang']);
	$langue_choisie = $row['langue_choisie'];
	
	if ($langue_choisie == "oui") $lang = "<b>$lang</b>";
	else $lang = "($lang)";
	
	if ($id_parent == 0) $puce = "secteur-12.gif";
	else $puce = "rubrique-12.gif";
	
	$s = http_img_pack($puce, '- ', "");
	$vals[] = $s;
	
	$s = "<b><a href='" . generer_url_ecrire("naviguer","id_rubrique=$id_rubrique") . "'>";
	$s .= typo($titre);
	$s .= "</A></b>";
	$vals[] = $s;
	
	$s = "<div align=\"right\">";
	if  ($GLOBALS['meta']['multi_rubriques'] == 'oui') {
				$s .= ($lang);
	}
	$s .= "</div>";
	$vals[] = $s;
	return $vals;
}

//
// Afficher des auteurs sur requete SQL
//
function bonhomme_statut($row) {
	global $connect_statut;

	switch($row['statut']) {
		case "0minirezo":
			return http_img_pack("admin-12.gif", "", "",
					_T('titre_image_administrateur'));
			break;
		case "1comite":
			if ($connect_statut == '0minirezo' AND ($row['source'] == 'spip' AND !($row['pass'] AND $row['login'])))
			  return http_img_pack("visit-12.gif",'', "", _T('titre_image_redacteur'));
			else
			  return http_img_pack("redac-12.gif",'', "", _T('titre_image_redacteur_02'));
			break;
		case "5poubelle":
		  return http_img_pack("poubelle.gif", '', "",_T('titre_image_auteur_supprime'));
			break;
		case "6forum":
		  return http_img_pack("visit-12.gif", '', "",_T('titre_image_visiteur'));
			break;
		case "nouveau":
		default:
			return '';
			break;
	}
}

// La couleur du statut
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


function afficher_auteurs ($titre_table, $requete) {

	if (!$requete['SELECT']) $requete['SELECT'] = '*' ;

	$tous_id = array();
	$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM " . $requete['FROM'] . ($requete['WHERE'] ? (' WHERE ' . $requete['WHERE']) : '') . ($requete['GROUP BY'] ? (' GROUP BY ' . $requete['GROUP BY']) : '')));
	if (! ($cpt = $cpt['n'])) return $tous_id ;
	if ($requete['LIMIT']) $cpt = min($requete['LIMIT'], $cpt);

	$tmp_var = substr(md5(join('', $requete)), 0, 4);
	$nb_aff = 1.5 * _TRANCHES;
	$deb_aff = intval(_request('t_' .$tmp_var));
	$tranches = '';
	if ($cpt > $nb_aff) {
		$nb_aff = (_TRANCHES); 
		$tranches = afficher_tranches_requete($cpt,  ($options == "avancees") ? 4 : 3, $tmp_var, '', $nb_aff);
	}

	debut_cadre_relief("auteur-24.gif");

	if ($titre_table) {
			echo "<p><table width='100%' cellpadding='0' cellspacing='0' border='0' background=''>";
			echo "<tr><td width='100%' background=''>";
			echo "<table width='100%' cellpadding='3' cellspacing='0' border='0'>";
			echo "<tr bgcolor='#333333'><td width='100%' colspan='2'><font face='Verdana,Arial,Sans,sans-serif' size=3 color='#FFFFFF'>";
			echo "<b>$titre_table</b></font></td></tr>";
		}
	else {
			echo "<p><table width='100%' cellpadding='3' cellspacing='0' border='0' background=''>";
		}

	echo $tranches;

	$result = spip_query("SELECT " . $requete['SELECT'] . " FROM " . $requete['FROM'] . ($requete['WHERE'] ? (' WHERE ' . $requete['WHERE']) : '') . ($requete['GROUP BY'] ? (' GROUP BY ' . $requete['GROUP BY']) : '') . ($requete['ORDER BY'] ? (' ORDER BY ' . $requete['ORDER BY']) : '') . " LIMIT " . ($deb_aff >= 0 ? "$deb_aff, $nb_aff" : ($requete['LIMIT'] ? $requete['LIMIT'] : "99999")));

	$table = array();
	while ($row = spip_fetch_array($result)) {
		$table[]= affiche_auteur_boucle($row, $tous_id);
	}
	spip_free_result($result);
	$largeurs = array('');
	$styles = array('arial2');
	echo afficher_liste($largeurs, $table, $styles);

	if ($titre_table) echo "</TABLE></TD></TR>";
	echo "</TABLE>";
	fin_cadre_relief();

	return $tous_id;
}

function affiche_auteur_boucle($row, &$tous_id)
{
	$vals = '';

	$id_auteur = $row['id_auteur'];
	$tous_id[] = $id_auteur;
	$nom = $row['nom'];

	$s = bonhomme_statut($row);
	$s .= "<a href='" . generer_url_ecrire("auteurs_edit","id_auteur=$id_auteur") . "'>";
	$s .= typo($nom);
	$s .= "</a>";
	$vals[] = $s;

	return $vals;
}

//
// Afficher les forums
//

function afficher_forum($request, $retour, $arg, $controle_id_article = false) {
	global $spip_display;
	static $compteur_forum = 0;
	static $nb_forum = array();
	static $i = array();

	$compteur_forum++;
	$nb_forum[$compteur_forum] = spip_num_rows($request);
	$i[$compteur_forum] = 1;
	
	if ($spip_display == 4) echo "<ul>";
 
 	while($row = spip_fetch_array($request)) {
		$statut=$row['statut'];
		if ($compteur_forum==1) echo "\n<br /><br />";
		if (($controle_id_article) ? ($statut!="perso") :
			(($statut=="prive" OR $statut=="privrac" OR $statut=="privadm" OR $statut=="perso")
			 OR ($statut=="publie" AND $id_parent > 0))) {

			afficher_forum_thread($row, $controle_id_article, $compteur_forum, $nb_forum, $i, $retour, $arg);
			afficher_forum(spip_query("SELECT * FROM spip_forum WHERE id_parent='" . $row['id_forum'] . "'" . ($controle_id_article ? " AND statut<>'off'" : '') . " ORDER BY date_heure"), $retour, $arg, $controle_id_article);
		}
		$i[$compteur_forum]++;
	}
	if ($spip_display == 4) echo "</ul>";
	spip_free_result($request);
	$compteur_forum--;
}

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
	$auteur=$row['auteur'];
	$email_auteur=$row['email_auteur'];
	$nom_site=$row['nom_site'];
	$url_site=$row['url_site'];
	$statut=$row['statut'];
	$ip=$row["ip"];
	$id_auteur=$row["id_auteur"];
	
	echo "<a id='$id_forum'></a>";
	if ($spip_display != 4) {
		echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'><tr>";
		afficher_forum_4($compteur_forum, $nb_forum, $i);
	}
	$titre_boite = '';
	if ($id_auteur AND $voir_logo) {
		$logo_f = charger_fonction('chercher_logo', 'inc');
		if ($logo = $logo_f($id_auteur, 'id_auteur', 'on'))
		  if ($logo = decrire_logo("id_auteur", 'on', $id_auteur, 48, 48, $logo))
			    $titre_boite = "<div style='$voir_logo'>$$logo</div>" ;
	} 

	$titre_boite .= typo($titre);
		
	if ($spip_display == 4) {
		echo "<li>".typo($titre)."<br>";
	} else {
		if ($compteur_forum == 1) 
			echo afficher_forum_logo($statut, $titre_boite);
		else echo debut_cadre_thread_forum("", false, "", $titre_boite);
	}
			
	// Si refuse, cadre rouge
	if ($statut=="off") {
		echo "<div style='border: 2px dashed red; padding: 5px;'>";
	}
	// Si propose, cadre jaune
	else if ($statut=="prop") {
		echo "<div style='border: 1px solid yellow; padding: 5px;'>";
	}
		
	echo "<span class='arial2'>";
	//	echo affdate_court($date_heure);
	//	echo ", ";
	//	echo heures($date_heure).":".minutes($date_heure);
	
	echo date_relative($date_heure);
	
	echo "</span> ";
	
	if ($id_auteur)
		echo "<a href='" . generer_url_ecrire("auteurs_edit","id_auteur=$id_auteur") . "'>".typo($auteur)."</a>";
	else if ($email_auteur)
		echo "<a href='mailto:$email_auteur'>".typo($auteur)."</a>";
	else	echo typo($auteur);

	if ($id_auteur) {
		$bouton = bouton_imessage($id_auteur);
		if ($bouton) echo "&nbsp;".$bouton;
	}

	// boutons de moderation
	if ($controle_id_article)
		echo boutons_controle_forum($id_forum, $statut, $id_auteur, "id_article=$id_article", $ip);

	echo safehtml(justifier(propre($texte)));

	if ($nom_site) {
		if (strlen($url_site) > 10)
			echo "<div align='left' class='verdana2'><b><a href='$url_site'>$nom_site</a></b></div>";
		else echo "<b>$nom_site</b>";
	}

	if (!$controle_id_article) {
	  	$tm = rawurlencode($titre);
		echo "<div align='right' class='verdana1'>";
		echo "<b><a href='", generer_url_ecrire("forum_envoi","id_parent=$id_forum&titre_message=$tm&url=" . generer_url_retour($retour, $arg)  .'#formulaire'), 
		  "'>",
		  _T('lien_repondre_message'),
		  "</a></b></div>";
	}

	if ($GLOBALS['meta']["mots_cles_forums"] == "oui")
		afficher_forum_mots($id_forum);
	
	if ($statut == "off" OR $statut == "prop") echo "</div>";

	if ($spip_display != 4) {
		if ($compteur_forum == 1) echo fin_cadre_forum();
		else echo fin_cadre_thread_forum();
		echo "</td></tr></table>\n";
	}
}


function afficher_forum_logo($statut, $titre_boite)
{
	if ($statut == "prive") $logo = "forum-interne-24.gif";
	else if ($statut == "privadm") $logo = "forum-admin-24.gif";
	else if ($statut == "privrac") $logo = "forum-interne-24.gif";
	else $logo = "forum-public-24.gif";
	return debut_cadre_forum($logo, false, "", $titre_boite);
}

function afficher_forum_mots($id_forum)
{
	$result_mots = spip_query("SELECT * FROM spip_mots AS mots, spip_mots_forum AS lien WHERE lien.id_forum = '$id_forum' AND lien.id_mot = mots.id_mot");

	echo '<ul>';
	while ($row_mots = spip_fetch_array($result_mots)) {
		$id_mot = $row_mots['id_mot'];
		$titre_mot = propre($row_mots['titre']);
		$type_mot = propre($row_mots['type']);
		echo "<li> <b>",$type_mot," :</b> ",$titre_mot, "<li>";
	}
	echo '</ul>';
}

// affiche les traits de liaisons entre les reponses

function afficher_forum_4($compteur_forum, $nb_forum, $i)
{
	global $spip_lang_rtl;
	$fleche='rien.gif';
	for ($count=2;$count<=$compteur_forum AND $count<20;$count++){
		$fond[$count]=_DIR_IMG_PACK . 'rien.gif';
		if ($i[$count]!=$nb_forum[$count]){
			$fond[$count]=_DIR_IMG_PACK . 'forum-vert.gif';
		}
		echo "<td width='10' valign='top' background=$fond[$count]>";
		if ($count==$compteur_forum){
			$fleche="forum-droite$spip_lang_rtl.gif";
		}
		echo http_img_pack($fleche, " ", "width='10' height='13'"), "</td>\n";
	}
	echo "\n<td width=100% valign='top'>";
}


function envoi_link($nom_site_spip, $rubrique="") {
	global $connect_statut, $connect_toutes_rubriques, $spip_display;
	global $spip_lang, $couleur_claire, $couleur_foncee;

	$args = "couleur_claire=" .
		substr($couleur_claire,1) .
		'&couleur_foncee=' .
		substr($couleur_foncee,1) .
		'&ltr=' . 
		$GLOBALS['spip_lang_left'];

	// CSS de secours en cas de non fonct de la suivante
	$res = '<link rel="stylesheet" type="text/css" href="' . _DIR_IMG_PACK
	. 'style_prive_defaut.css" >'  . "\n"
	
	// CSS espace prive : la vraie
	. '<link rel="stylesheet" type="text/css" href="'
	. generer_url_public('style_prive', $args) .'" >' . "\n"

	// CSS calendrier
	. '<link rel="stylesheet" type="text/css" href="'
	. find_in_path('calendrier.css') .'" >' . "\n"

	// CSS imprimante (masque des trucs, a completer)
	. '<link rel="stylesheet" type="text/css" href="' . _DIR_IMG_PACK
	. 'spip_style_print.css" media="print" >' . "\n"

	// CSS "visible au chargement", hack necessaire pour garder un depliement
	// sympathique meme sans javascript (on exagere ?)
	// Pour l'explication voir http://www.alistapart.com/articles/alternate/
	. '<link rel="alternate stylesheet" type="text/css" href="' . _DIR_IMG_PACK
	. 'spip_style_invisible.css" title="invisible" >' . "\n"
	. '<link rel="stylesheet" href="' . _DIR_IMG_PACK
	. 'spip_style_visible.css"  title="visible" >' . "\n"

	// favicon.ico
	. '<link rel="shortcut icon" href="'
	. url_absolue('x/'.find_in_path('favicon.ico')) # petit hack sur l'url
	. "\" >\n";
	$js = debut_javascript($connect_statut == "0minirezo"
			AND $connect_toutes_rubriques,
			($GLOBALS['meta']["activer_statistiques"] != 'non'));

	if ($spip_display == 4) return $res . $js;

	$nom = entites_html($nom_site_spip);

	$res .= "<link rel='alternate' type='application/rss+xml' title=\"$nom\" href='"
			. generer_url_public('backend') . "' >\n";
	$res .= "<link rel='help' type='text/html' title=\""._T('icone_aide_ligne') . 
			"\" href='"
			. generer_url_ecrire('aide_index',"var_lang=$spip_lang")
			."' >\n";
	if ($GLOBALS['meta']["activer_breves"] != "non")
		$res .= "<link rel='alternate' type='application/rss+xml' title=\""
			. $nom
			. " ("._T("info_breves_03")
			. ")\" href='" . generer_url_public('backend-breves') . "' >\n";

	return $res . $js;
}

function debut_javascript($admin, $stat)
{
	global $spip_lang_left, $browser_name, $browser_version;
	include_spip('inc/charsets');


	# teste la capacite ajax : on envoie un cookie -1
	# et un script ajax ; si le script reussit le cookie passera a +1
	if (!isset($GLOBALS['_COOKIE']['spip_accepte_ajax'])) {
		spip_setcookie('spip_accepte_ajax', -1);
		$ajax = "if (a = createXmlHttp()) {
	a.open('GET', '" . generer_url_ecrire("ajax_page","fonction=test", true) .
		  "', true) ;
	a.send(null);
}";
	} else $ajax = "";

	return 
	// envoi le fichier JS de config si browser ok.
		$GLOBALS['browser_layer'] .
	 	http_script(
	# tester la capacite ajax si ce n'est pas deja fait
			$ajax . 
			"\nvar admin = " . ($admin ? 1 : 0) .
			"\nvar stat = " . ($stat ? 1 : 0) .
			"\nvar largeur_icone = " .
			largeur_icone_bandeau_principal(_T('icone_a_suivre')) .
			"\nvar  bug_offsetwidth = " .
// uniquement affichage ltr: bug Mozilla dans offsetWidth quand ecran inverse!
			((($spip_lang_left == "left") &&
			  (($browser_name != "MSIE") ||
			   ($browser_version >= 6))) ? 1 : 0) .
			"\nvar confirm_changer_statut = '" .
			unicode_to_javascript(addslashes(html2unicode(_T("confirm_changer_statut")))) . 
			"';\n") .
		http_script('',_DIR_IMG_PACK . 'presentation.js');
}

// Fonctions onglets

function onglet_relief_inter(){
	
	echo "<td>&nbsp;</td>";
	
}

function debut_onglet(){

	echo "\n\n";
	echo "<div style='padding: 7px;'><table cellpadding='0' cellspacing='0' border='0' align='center'>";
	echo "<tr>";
}

function fin_onglet(){
	echo "</tr>";
	echo "</table></div>\n\n";
}

function onglet($texte, $lien, $onglet_ref, $onglet, $icone=""){
	global $spip_display, $spip_lang_left ;


	echo "<td>";
	
	if ($onglet != $onglet_ref) {
		echo "<div style='position: relative;'>";
		if ($spip_display != 1) {
			if (strlen($icone) > 0) {
				echo "<div style='z-index: 2; position: absolute; top: 0px; $spip_lang_left: 5px;'>" .
				  http_img_pack("$icone", "", "") . "</div>";
				$style = " top: 7px; padding-$spip_lang_left: 32px; z-index: 1;";
			} else {
				$style = " top: 7px;";
			}
		}
		
		echo "<div onmouseover=\"changeclass(this, 'onglet_on');\" onmouseout=\"changeclass(this, 'onglet');\" class='onglet' style='position: relative;$style'><a href='$lien'>$texte</a></div>";
		
		
		echo "</div>";
	} else {
		echo "<div style='position: relative;'>";
		if ($spip_display != 1) {
			if (strlen($icone) > 0) {
				echo "<div style='z-index: 2; position: absolute; top: 0px; $spip_lang_left: 5px;'>" .
				  http_img_pack("$icone", "", "") . "</div>";
				$style = " top: 7px; padding-$spip_lang_left: 32px; z-index: 1;";
			} else {
				$style = " top: 7px;";
			}
		}
		
		echo "<div class='onglet_off' style='position: relative;$style'>$texte</div>";
		
		
		echo "</div>";
	}
	echo "</td>";
}

function barre_onglets($rubrique, $ongletCourant){
	$onglets= definir_barre_onglets($rubrique);
	if(count($onglets)==0) return;

	debut_onglet();

	foreach($onglets as $exec => $onglet) {
		$url= $onglet->url ? $onglet->url : generer_url_ecrire($exec);
		onglet(_T($onglet->libelle), $url,
 			$exec, $ongletCourant, $onglet->icone);
	}
	fin_onglet();
}

function largeur_icone_bandeau_principal($texte) {
	global $spip_display, $spip_ecran ;
	global $connect_statut, $connect_toutes_rubriques;

	if ($spip_display == 1){
		$largeur = 80;
	}
	else if ($spip_display == 3){
		$largeur = 60;
	}
	else {
		if (count(explode(" ", $texte)) > 1) $largeur = 84;
		else $largeur = 80;
	}
	if ($spip_ecran == "large") $largeur = $largeur + 30;

	if (!($connect_statut == "0minirezo" AND $connect_toutes_rubriques)) {
		$largeur = $largeur + 30;
	}


	return $largeur;
}

function icone_bandeau_principal($texte, $lien, $fond, $rubrique_icone = "vide", $rubrique = "", $lien_noscript = "", $sous_rubrique_icone = "", $sous_rubrique = ""){
	global $spip_display, $spip_ecran;
	global $menu_accesskey, $compteur_survol;

	$largeur = largeur_icone_bandeau_principal($texte);


	$alt = '';
	$title = '';
	if ($spip_display == 1){
	}
	else if ($spip_display == 3){
		$title = "title=\"$texte\"";
		$alt = $texte;
	}
	else {
		$alt = ' ';
	}
	
	if (!$menu_accesskey = intval($menu_accesskey)) $menu_accesskey = 1;
	if ($menu_accesskey < 10) {
		$accesskey = " accesskey='$menu_accesskey'";
		$menu_accesskey++;
	}
	else if ($menu_accesskey == 10) {
		$accesskey = " accesskey='0'";
		$menu_accesskey++;
	}

	$class_select = ($sous_rubrique_icone == $sous_rubrique) ? " class='selection'" : '';

	if (eregi("^javascript:",$lien)) {
		$a_href = "<a$accesskey onClick=\"$lien; return false;\" href='$lien_noscript' target='spip_aide'$class_select>";
	}
	else {
		$a_href = "<a$accesskey href=\"$lien\"$class_select>";
	}

	$compteur_survol ++;

	if ($spip_display != 1 AND $spip_display != 4) {
		echo "<td class='cellule48' onmouseover=\"changestyle('bandeau$rubrique_icone', 'visibility', 'visible');\" width='$largeur'>$a_href" .
		  http_img_pack("$fond", $alt, "$title width='48' height='48'");
		if ($spip_display != 3) {
			echo "<span>$texte</span>";
		}
	}
	else echo "<td class='cellule-texte' onmouseover=\"changestyle('bandeau$rubrique_icone', 'visibility', 'visible');\" width='$largeur'>$a_href".$texte;
	echo "</a></td>\n";
}




function icone_bandeau_secondaire($texte, $lien, $fond, $rubrique_icone = "vide", $rubrique, $aide=""){
	global $spip_display;
	global $menu_accesskey, $compteur_survol;

	$alt = '';
	$title = '';
	$accesskey = '';
	if ($spip_display == 1) {
		//$hauteur = 20;
		$largeur = 80;
	}
	else if ($spip_display == 3){
		//$hauteur = 26;
		$largeur = 40;
		$title = "title=\"$texte\"";
		$alt = $texte;
	}
	else {
		//$hauteur = 68;
		if (count(explode(" ", $texte)) > 1) $largeur = 80;
		else $largeur = 70;
		$alt = " ";
	}
	if ($aide AND $spip_display != 3) {
		$largeur += 50;
		//$texte .= aide($aide);
	}
	if ($spip_display != 3 AND strlen($texte)>16) $largeur += 20;
	
	if (!$menu_accesskey = intval($menu_accesskey)) $menu_accesskey = 1;
	if ($menu_accesskey < 10) {
		$accesskey = " accesskey='$menu_accesskey'";
		$menu_accesskey++;
	}
	else if ($menu_accesskey == 10) {
		$accesskey = " accesskey='0'";
		$menu_accesskey++;
	}
	if ($spip_display == 3) $accesskey_icone = $accesskey;

	$class_select =  ($rubrique_icone != $rubrique) ? '' : " class='selection'";
	$compteur_survol ++;

	$a_href = "<a$accesskey href=\"$lien\"$class_select>";

	if ($spip_display != 1) {
		echo "<td class='cellule36' style='width: ".$largeur."px;'>";
		echo "$a_href" .
		  http_img_pack("$fond", $alt, "$title");
		if ($aide AND $spip_display != 3) echo aide($aide)." ";
		if ($spip_display != 3) {
			echo "<span>$texte</span>";
		}
	}
	else echo "<td class='cellule-texte' width='$largeur'>$a_href".$texte;
	echo "</a>";	
	echo "</td>\n";
	return $largeur;
}



function icone($texte, $lien, $fond, $fonction="", $align="", $afficher='oui'){
	global $spip_display, $compteur_survol;

	$compteur_survol ++;
	if ($fonction == "supprimer.gif") {
		$style = '-danger';
	} else {
		$style = '';
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
		$title = "title=\"$texte\"";
		$alt = $texte;
	}
	else {
		$hauteur = 70;
		$largeur = 100;
		$title = '';
		$alt = $texte;
	}

	if ($spip_display != 1 AND $spip_display != 4){
		if ($fonction != "rien.gif"){
		  $icone = http_img_pack($fonction, $alt, "$title width='24' height='24'" .
					  http_style_background($fond, "no-repeat center center"));
		}
		else {
			$icone = http_img_pack($fond, $alt, "$title width='24' height='24'");
		}
	} else $icone = '';

	if ($spip_display != 3){
		$icone .= "<span>$texte</span>";
	}
	$icone = "\n<table cellpadding='0' class='pointeur' cellspacing='0' border='0' width='$largeur'" .
	  ((strlen($align) > 2) ? " align='$align' " : '') .
	">\n<tr><td class='icone36$style' style='text-align:center;'><a
	href='$lien'>$icone</a></td>\n</tr></table>";

	if ($afficher == 'oui')
		echo $icone;
	else
		return $icone;
}

function icone_horizontale($texte, $lien, $fond = "", $fonction = "", $echo = true, $javascript='') {
	global $spip_display;

	$retour = '';


	if ($spip_display != 4) {
		//if (!$fonction) $fonction = "rien.gif";
	
		if ($spip_display != 1) {
			$retour .= "<a href='$lien' class='cellule-h' $javascript>";
			$retour .= "<table cellpadding='0' valign='middle'><tr>\n";
			$retour .= "<td><a href='$lien' class='cellule-h'><div class='cell-i'>" ;
			if ($fonction){
			  $retour .= http_img_pack($fonction, "", http_style_background($fond, "center center no-repeat"));
			}
			else {
				$retour .= http_img_pack($fond, "", "");
			}
			$retour .= "</div></a></td>\n" .
			  "<td class='cellule-h-lien'><a href='$lien' class='cellule-h'>$texte</a></td>\n";
			$retour .= "</tr></table>\n";
			$retour .= "</a>\n";
		}
		else {
			$retour .= "<a href='$lien' class='cellule-h-texte' $javascript><div>$texte</div></a>\n";
		}
		if ($fonction == "supprimer.gif")
			$retour = "<div class='danger'>$retour</div>";
	} else {
		$retour = "<li><a href='$lien'>$texte</a></li>";
	}

	if ($echo) echo $retour;
	return $retour;
}


function bandeau_barre_verticale(){
	echo "<td class='separateur'></td>\n";
}


// lien changement de couleur
function lien_change_var($lien, $set, $couleur, $coords, $titre, $mouseOver="") {
	$lien = parametre_url($lien, $set, $couleur);
	return "\n<area shape='rect' href='$lien' coords='$coords' title=\"$titre\" alt=' ' $mouseOver>";
}

//
// Debut du corps de la page
//

function afficher_menu_rubriques() {
	global $spip_lang_rtl, $spip_ecran;
	$date_maj = $GLOBALS['meta']["date_calcul_rubriques"];

	echo http_script('',generer_url_ecrire("js_menu_rubriques","date=$date_maj&spip_ecran=$spip_ecran&dir=$spip_lang_rtl"),'');
}


function afficher_javascript ($html) {
	  return http_script("
document.write(\"" . addslashes(strtr($html, "\n\r", "  "))."\")");
}


//
// Presentation de l'interface privee, debut du HTML
//

function debut_page($titre = "", $rubrique = "asuivre", $sous_rubrique = "asuivre", $onLoad = "", $css="", $id_rubrique = "") {

	init_entete($titre, $rubrique, $css);
	definir_barre_boutons();
	init_body($rubrique, $sous_rubrique, $onLoad, $id_rubrique);
	debut_corps_page($rubrique);
}
 
function init_entete($titre, $rubrique, $css='') {

	if (!$nom_site_spip = textebrut(typo($GLOBALS['meta']["nom_site"])))
		$nom_site_spip=  _T('info_mon_site_spip');

	// envoi des en-tetes, du doctype et du <head><title...
	include_spip('inc/headers');
	http_no_cache();
	$head = _DOCTYPE_ECRIRE
		. "<html lang='".$GLOBALS['spip_lang']."' dir='"
		. ($GLOBALS['spip_lang_rtl'] ? 'rtl' : 'ltr')
		. "'>\n<head>\n<title>["
		. $nom_site_spip
		. "] " . textebrut(typo($titre)) . "</title>\n"
		. envoi_link($nom_site_spip, $rubrique)
		. (!$css ? "" : (
			'<link rel="stylesheet" href="' . entites_html($css)
			. '" type="text/css" />'. "\n"
		) ) ."\n";

	echo pipeline('header_prive', $head)
		. "</head>\n";
}

// fonction envoyant la double serie d'icones de redac
function init_body($rubrique='asuivre', $sous_rubrique='asuivre', $onLoad='', $id_rubrique='') {
	global $couleur_foncee, $couleur_claire, $adresse_site;
	global $connect_id_auteur;
	global $connect_statut;
	global $connect_toutes_rubriques;
	global $auth_can_disconnect, $connect_login;
	global $options, $spip_display, $spip_ecran;
	global $spip_lang, $spip_lang_rtl, $spip_lang_left, $spip_lang_right;
	global $browser_verifForm;

	$id_rubrique = intval($id_rubrique);

	echo pipeline('body_prive',"<body ". _ATTRIBUTES_BODY
		. ' onLoad="'
		. "setActiveStyleSheet('invisible');$browser_verifForm$onLoad"
		. '">');

	if ($spip_ecran == "large") $largeur = 974;
	else $largeur = 750;

	echo "\n<map name='map_layout'>";
	echo lien_change_var (self(), 'set_disp', 1, '1,0,18,15', _T('lien_afficher_texte_seul'), "onmouseover=\"changestyle('bandeauvide','visibility', 'visible');\"");
	echo lien_change_var (self(), 'set_disp', 2, '19,0,40,15', _T('lien_afficher_texte_icones'), "onmouseover=\"changestyle('bandeauvide','visibility', 'visible');\"");
	echo lien_change_var (self(), 'set_disp', 3, '41,0,59,15', _T('lien_afficher_icones_seuls'), "onmouseover=\"changestyle('bandeauvide','visibility', 'visible');\"");
	echo "\n</map>";



	if ($spip_display == "4") {
		// Icones principales
		echo "<ul>";
		echo "<li><a href='./'>"._T('icone_a_suivre')."</a>";
		echo "<li><a href='" . generer_url_ecrire("naviguer") . "'>"._T('icone_edition_site')."</a>";
		echo "<li><a href='" . generer_url_ecrire("forum"). "'>"._T('titre_forum')."</a>";
		echo "<li><a href='" . generer_url_ecrire("auteurs") . "'>"._T('icone_auteurs')."</a>";
		echo "<li><a href=\"$adresse_site/\">"._T('icone_visiter_site')."</a>";
		echo "</ul>";

		return;
	}

	// iframe permettant de passer les changements de statut rapides
	echo "<iframe id='iframe_action' name='iframe_action' width='1' height='1' style='position: absolute; visibility: hidden;'></iframe>";

	// Lien oo
	echo "<div class='invisible_au_chargement' style='position: absolute; height: 0px; visibility: hidden;'><a href='oo'>"._T("access_mode_texte")."</a></div>";
	
	echo "<div id='haut-page'>";

	// Icones principales
	echo "<div class='bandeau-principal' align='center'>\n";
	echo "<div class='bandeau-icones'>\n";
	echo "<table width='$largeur' cellpadding='0' cellspacing='0' border='0' align='center'><tr>\n";

	foreach($GLOBALS['boutons_admin'] as $page => $detail) {
		if($page=='espacement') {
			echo "<td> &nbsp; </td>";
		} else {
			if ($detail->url)
				$lien_noscript = $detail->url;
			else
				$lien_noscript = generer_url_ecrire($page);

			if ($detail->url2)
				$lien = $detail->url2;
			else
				$lien = $lien_noscript;

			icone_bandeau_principal(
				_T($detail->libelle),
				$lien,
				$detail->icone,
				$page,
				$rubrique,
				$lien_noscript,
				$page,
				$sous_rubrique);
		}
	}
	echo "</tr></table>\n";

	echo "</div>\n";

	echo "<table width='$largeur' cellpadding='0' cellspacing='0' align='center'><tr><td>";
	echo "<div style='text-align: $spip_lang_left; width: ".$largeur."px; position: relative; z-index: 2000;'>";

	// Icones secondaires

	global $browser_name;
	$coeff_decalage = 0;
	if ($browser_name=="MSIE")
		$coeff_decalage = 1.0;
	$decal=0;
	$largitem_moy = 85;
	$largeur_maxi_menu = $largeur-100;
	foreach($GLOBALS['boutons_admin'] as $page => $detail) {
		if ($rubrique == $page) {
			$class = "visible_au_chargement";
		} else {
			$class = "invisible_au_chargement";
		}

		$sousmenu= $detail->sousmenu;
		if($sousmenu) {
			$offset = (int)round($decal-$coeff_decalage*max(0,($decal+count($sousmenu)*$largitem_moy-$largeur_maxi_menu)));
			if ($offset<0){	$offset = 0; }
			echo "<div class='$class' id='bandeau$page' style='position: absolute; $spip_lang_left: ".$offset."px;'><div class='bandeau_sec'><table class='gauche'><tr>\n";
			$width=0;
			foreach($sousmenu as $souspage => $sousdetail) {
				if ($width+1.25*$largitem_moy>$largeur_maxi_menu){echo "</tr><tr>\n";$width=0;}
				if($souspage=='espacement') {
					if ($width>0){
						echo "<td class='separateur'></td>\n";
						$largitem = 0;
					}
				} else {
					$largitem = icone_bandeau_secondaire (_T($sousdetail->libelle), generer_url_ecrire($sousdetail->url?$sousdetail->url:$souspage, $sousdetail->urlArg), $sousdetail->icone, $souspage, $sous_rubrique);
				}
				$width+=$largitem+10;
			}
			echo "</tr></table></div></div>";
		}
		
		$decal += largeur_icone_bandeau_principal(_T($detail->libelle));
	}

	// Refermer tout de suite le bandeau deroule par defaut
	echo "
	<script type='text/javascript'><!--
		changestyle('-', '-', '-');
	// --></script>\n";

	echo "</div>";
	
	echo "</td></tr></table>";
	
	echo "</div>\n"; // referme: <div class='bandeau-principal' align='center'>"

	//
	// Bandeau colore
	//

if (true /*$bandeau_colore*/) {
	if ($rubrique == "administration") {
		$style = "background: url(" . _DIR_IMG_PACK . "rayures-danger.png); background-color: $couleur_foncee";
		echo "<style>a.icone26 { color: white; }</style>";
	}
	else {
		$style = "background-color: $couleur_claire";
	}

	echo "\n<div style=\"max-height: 40px; width: 100%; border-bottom: solid 1px white;$style\">";
	echo "<table align='center' cellpadding='0' background='' width='$largeur'><tr width='$largeur'>";

	echo "<td valign='middle' class='bandeau_couleur' style='text-align: $spip_lang_left;'>";
//		echo "<a href='" . generer_url_ecrire("articles_tous","") . "' class='icone26' onMouseOver=\"changestyle('bandeautoutsite','visibility','visible');\">" .
//		  http_img_pack("tout-site.png", "", "width='26' height='20'") . "</a>";

		echo "<a href='" . generer_url_ecrire("articles_tous") . "' class='icone26' onmouseover=\"changestyle('bandeautoutsite','visibility','visible'); charger_id_url_si_vide('" . generer_url_ecrire("ajax_page", "fonction=aff_nav_recherche&id=$id_rubrique") . "','nav-recherche');\">",
		  http_img_pack("tout-site.png", "", "width='26' height='20'") . "</a>";
		if ($id_rubrique > 0) echo "<a href='" . generer_url_ecrire("brouteur","id_rubrique=$id_rubrique") . "' class='icone26' onmouseover=\"changestyle('bandeaunavrapide','visibility','visible');\">" .
		  http_img_pack("naviguer-site.png", "", "width='26' height='20'") ."</a>";
		else echo "<a href='" . generer_url_ecrire("brouteur") . "' class='icone26' onmouseover=\"changestyle('bandeaunavrapide','visibility','visible');\" >" .
		  http_img_pack("naviguer-site.png", "", "width='26' height='20'") . "</a>";

		echo "<a href='" . generer_url_ecrire("recherche") . "' class='icone26' onmouseover=\"changestyle('bandeaurecherche','visibility','visible'); findObj('form_recherche').focus();\" >" .
		  http_img_pack("loupe.png", "", "width='26' height='20'") ."</a>";

		echo http_img_pack("rien.gif", " ", "width='10'");

		echo "<a href='" . generer_url_ecrire("calendrier","type=semaine") . "' class='icone26' onmouseover=\"changestyle('bandeauagenda','visibility','visible');\">" .
		  http_img_pack("cal-rv.png", "", "width='26' height='20'") ."</a>";
		echo "<a href='" . generer_url_ecrire("messagerie") . "' class='icone26' onmouseover=\"changestyle('bandeaumessagerie','visibility','visible');\">" .
		  http_img_pack("cal-messagerie.png", "", "width='26' height='20'") ."</a>";
		echo "<a href='" . generer_url_ecrire("synchro") . "' class='icone26' onmouseover=\"changestyle('bandeausynchro','visibility','visible');\">" .
		  http_img_pack("cal-suivi.png", "", "width='26' height='20'") . "</a>";
		

		if (!($connect_statut == "0minirezo" AND $connect_toutes_rubriques)) {
			echo http_img_pack("rien.gif", " ", "width='10'");
			echo "<a href='" . generer_url_ecrire("auteurs_edit","id_auteur=$connect_id_auteur") . "' class='icone26' onmouseover=\"changestyle('bandeauinfoperso','visibility','visible');\">" .
			  http_img_pack("fiche-perso.png", "", "onmouseover=\"changestyle('bandeauvide','visibility', 'visible');\"");
			echo "</a>";
		}
		
	echo "</td>";
	echo "<td valign='middle' class='bandeau_couleur' style='text-align: $spip_lang_left;'>";
		// overflow pour masquer les noms tres longs (et eviter debords, notamment en ecran etroit)
		if ($spip_ecran == "large") $largeur_nom = 300;
		else $largeur_nom= 110;
		echo "<div style='width: ".$largeur_nom."px; height: 14px; overflow: hidden;'>";
		// Redacteur connecte
		echo typo($GLOBALS['auteur_session']['nom']);
		echo "</div>";
	
	echo "</td>";

	echo "<td> &nbsp; </td>";


	echo "<td class='bandeau_couleur' style='text-align: $spip_lang_right;' valign='middle'>";

			// Choix display
		//	echo"<img src=_DIR_IMG_PACK . 'rien.gif' width='10' />";
			if ($options != "avancees") {
				$lien = parametre_url(self(), 'set_options', 'avancees');
				$simple = "<b>"._T('icone_interface_simple')."</b>/<a href='$lien' class='lien_sous'>"._T('icone_interface_complet')."</a>";
				$icone = "interface-display-comp.png";
			} else {
				$lien = parametre_url(self(), 'set_options', 'basiques');
				$simple = "<a href='$lien' class='lien_sous'>"._T('icone_interface_simple')."</a>/<b>"._T('icone_interface_complet')."</b>";
				$icone = "interface-display.png";
			}
			echo "<a href='$lien' class='icone26' onmouseover=\"changestyle('bandeaudisplay','visibility', 'visible');\">" .
			  http_img_pack("$icone", "", "width='26' height='20'")."</a>";

			echo http_img_pack("rien.gif", " ", "width='10' height='1'");
			echo http_img_pack("choix-layout$spip_lang_rtl".($spip_lang=='he'?'_he':'').".gif", "abc", "class='format_png' valign='middle' width='59' height='15' usemap='#map_layout'");


			echo http_img_pack("rien.gif", " ", "width='10' height='1'");
			// grand ecran
			if ($spip_ecran == "large") {
				$i = _T('info_petit_ecran');
				echo "<a href='". parametre_url(self(),'set_ecran', 'etroit') ."' class='icone26' onmouseover=\"changestyle('bandeauecran','visibility', 'visible');\" title=\"$i\">" .
				  http_img_pack("set-ecran-etroit.png", $i, "width='26' height='20'") . "</a>";
				$ecran = "<div><a href='".parametre_url(self(),'set_ecran', 'etroit')."' class='lien_sous'>"._T('info_petit_ecran')."</a>/<b>"._T('info_grand_ecran')."</b></div>";
			}
			else {
				$i = _T('info_grand_ecran');
				echo "<a href='".parametre_url(self(),'set_ecran', 'large')."' class='icone26' onmouseover=\"changestyle('bandeauecran','visibility', 'visible');\" title=\"$i\">" .
				  http_img_pack("set-ecran.png", $i, "width='26' height='20'") ."</a>";
				$ecran = "<div><b>"._T('info_petit_ecran')."</b>/<a href='".parametre_url(self(),'set_ecran', 'large')."' class='lien_sous'>"._T('info_grand_ecran')."</a></div>";
			}

		echo "</td>";
		
		echo "<td class='bandeau_couleur' style='width: 60px; text-align:$spip_lang_left;' valign='middle'>";
		choix_couleur();
		
		echo "</td>";
	//
	// choix de la langue
	//
	if ($GLOBALS['all_langs']) {
		echo "<td class='bandeau_couleur' style='width: 100px; text-align: $spip_lang_right;' valign='middle'>";
		echo menu_langues('var_lang_ecrire');
		echo "</td>";
	}

		echo "<td class='bandeau_couleur' style='text-align: $spip_lang_right; width: 28px;' valign='middle'>";

		if ($auth_can_disconnect) {
			echo "<a href='",
			  generer_url_action("logout","logout=prive"),
			  "' class='icone26' onmouseover=\"changestyle('bandeaudeconnecter','visibility', 'visible');\">",
			  http_img_pack("deconnecter-24.gif", "", ""),
			  "</a>";
			}
		echo "</td>";
	
	
	echo "</tr></table>";

} // fin bandeau colore

	//
	// Barre des gadgets
	// (elements invisibles qui s'ouvrent sous la barre precedente)
	//

	bandeau_gadgets($largeur, $simple, $id_rubrique);

	echo "</div>";
	echo "</div>";

	if ($options != "avancees") echo "<div style='height: 18px;'>&nbsp;</div>";
}

function bandeau_gadgets($largeur, $simple, $id_rubrique)
{
  global $connect_id_auteur, $connect_login, $connect_statut, $options, $couleur_claire,$couleur_foncee, $spip_lang_left, $spip_lang_right, $spip_ecran;


	echo "\n<table width='$largeur' cellpadding='0' cellspacing='0' align='center'><tr><td>\n<div style='position: relative; z-index: 1000;'>";

	// GADGET Menu rubriques
	echo "\n<div id='bandeautoutsite' class='bandeau_couleur_sous' style='$spip_lang_left: 0px;'>";
	echo "<a href='" . generer_url_ecrire("articles_tous") . "' class='lien_sous'>"._T('icone_site_entier')."</a>";
	echo "<img src='"._DIR_IMG_PACK."searching.gif' id='img_nav-recherche' style='visibility: hidden' />";
	afficher_menu_rubriques();
	echo "</div>";
	// FIN GADGET Menu rubriques
	
	// GADGET Navigation rapide
	echo "<div id='bandeaunavrapide' class='bandeau_couleur_sous' style='$spip_lang_left: 30px; width: 300px;'>";

	echo "<a href='", generer_url_ecrire("brouteur", ($id_rubrique ? "id_rubrique=$id_rubrique" : '')), "' class='lien_sous'>", _T('icone_brouteur'), "</a>";
	$gadget = '';
	$vos_articles = spip_query("SELECT articles.id_article, articles.id_rubrique, articles.titre, articles.statut FROM spip_articles AS articles, spip_auteurs_articles AS lien WHERE articles.id_article=lien.id_article AND lien.id_auteur=$connect_id_auteur AND articles.statut='prepa' ORDER BY articles.date DESC LIMIT 5");
	if (spip_num_rows($vos_articles) > 0) {
			$gadget .= "<div>&nbsp;</div>";
			$gadget .= "<div class='bandeau_rubriques' style='z-index: 1;'>";
			$gadget .= bandeau_titre_boite2(afficher_plus(generer_url_ecrire("articles_page",""))._T('info_en_cours_validation'), "article-24.gif", $couleur_foncee, 'white', false);
			$gadget .= "\n<div class='plan-articles'>\n";
			while($row = spip_fetch_array($vos_articles)) {
				$id_article = $row['id_article'];
				$titre = typo(sinon($row['titre'], _T('ecrire:info_sans_titre')));
				$statut = $row['statut'];
				$gadget .= "<a class='$statut' style='font-size: 10px;' href='" . generer_url_ecrire("articles","id_article=$id_article") . "'>$titre</a>\n";
			}
			$gadget .= "</div>";
			$gadget .= "</div>";
	}
	
	$vos_articles = spip_query("SELECT id_article, id_rubrique, titre, statut FROM spip_articles WHERE statut='prop' ORDER BY date DESC LIMIT 5");
	if (spip_num_rows($vos_articles) > 0) {
			$gadget .= "<div>&nbsp;</div>";
			$gadget .= "<div class='bandeau_rubriques' style='z-index: 1;'>";
			$gadget .= bandeau_titre_boite2(afficher_plus('./')._T('info_articles_proposes'), "article-24.gif", $couleur_foncee, 'white', false);
			$gadget .= "<div class='plan-articles'>";
			while($row = spip_fetch_array($vos_articles)) {
				$id_article = $row['id_article'];
				$titre = sinon($row['titre'], _T('ecrire:info_sans_titre'));
				$statut = $row['statut'];
	
				$gadget .= "<a class='$statut' style='font-size: 10px;' href='" . generer_url_ecrire("articles","id_article=$id_article") . "'>$titre</a>";
			}
			$gadget .= "</div>";
			$gadget .= "</div>";
	}
			
	$vos_articles = spip_query("SELECT * FROM spip_breves WHERE statut='prop' ORDER BY date_heure DESC LIMIT 5");
	if (spip_num_rows($vos_articles) > 0) {
			$gadget .= "<div>&nbsp;</div>";
			$gadget .= "<div class='bandeau_rubriques' style='z-index: 1;'>";
			$gadget .= bandeau_titre_boite2(afficher_plus(generer_url_ecrire("breves"))._T('info_breves_valider'), "breve-24.gif", "$couleur_foncee", "white", false);
			$gadget .= "<div class='plan-articles'>";
			while($row = spip_fetch_array($vos_articles)) {
				$id_breve = $row['id_breve'];
				$titre = typo(sinon($row['titre'], _T('ecrire:info_sans_titre')));
				$statut = $row['statut'];
	
				$gadget .= "<a class='$statut' style='font-size: 10px;' href='" . generer_url_ecrire("breves_voir","id_breve=$id_breve") . "'>$titre</a>";
			}
			$gadget .= "</div>";
			$gadget .= "</div>";
	}

	$result = spip_query("SELECT id_rubrique FROM spip_rubriques LIMIT 1");
		
	if (spip_num_rows($result) > 0) {
			$gadget .= "<div>&nbsp;</div>";
			if ($id_rubrique > 0) {
				$dans_rub = "&id_rubrique=$id_rubrique";
				$dans_parent = "&id_parent=$id_rubrique";
			} else $dans_rub = $dans_parent = '';
			if ($connect_statut == "0minirezo") {	
				$gadget .= "<div style='width: 140px; float: $spip_lang_left;'>";
				if ($id_rubrique > 0)
					$gadget .= icone_horizontale(_T('icone_creer_sous_rubrique'), generer_url_ecrire("rubriques_edit","new=oui$dans_parent"), "rubrique-24.gif", "creer.gif", false);
				else 
					$gadget .= icone_horizontale(_T('icone_creer_rubrique'), generer_url_ecrire("rubriques_edit","new=oui"), "rubrique-24.gif", "creer.gif", false);
				$gadget .= "</div>";
			}		
			$gadget .= "<div style='width: 140px; float: $spip_lang_left;'>";
			$gadget .= icone_horizontale(_T('icone_ecrire_article'), generer_url_ecrire("articles_edit","new=oui$dans_rub"), "article-24.gif","creer.gif", false);
			$gadget .= "</div>";
			
			if ($GLOBALS['meta']["activer_breves"] != "non") {
				$gadget .= "<div style='width: 140px;  float: $spip_lang_left;'>";
				$gadget .= icone_horizontale(_T('icone_nouvelle_breve'), generer_url_ecrire("breves_edit","new=oui$dans_rub"), "breve-24.gif","creer.gif", false);
				$gadget .= "</div>";
			}
			
			if ($GLOBALS['meta']["activer_sites"] == 'oui') {
				if ($connect_statut == '0minirezo' OR $GLOBALS['meta']["proposer_sites"] > 0) {
					$gadget .= "<div style='width: 140px; float: $spip_lang_left;'>";
					$gadget .= icone_horizontale(_T('info_sites_referencer'), generer_url_ecrire("sites_edit","new=oui$dans_parent"), "site-24.gif","creer.gif", false);
					$gadget .= "</div>";
				}
			}
			
	}

	echo afficher_javascript($gadget);
	echo "</div>";
	// FIN GADGET Navigation rapide

	// GADGET Recherche
	echo "<div id='bandeaurecherche' class='bandeau_couleur_sous' style='width: 146px; $spip_lang_left: 60px;'>";
	echo "<form method='get' style='margin: 0px; position: relative;' action='" . generer_url_ecrire("recherche") . "'>";
	echo "<input type='hidden' name='exec' value='recherche' />";
	echo "<input type=\"text\" id=\"form_recherche\" style=\"width: 140px;\" size=\"10\" value=\""._T('info_rechercher')."\" name=\"recherche\" onkeypress=\"t=window.setTimeout('lancer_recherche(\'form_recherche\',\'resultats_recherche\')', 200);\" autocomplete=\"off\" class=\"formo\" accesskey=\"r\" />";
	echo "</form>";
	echo "</div>";
	// FIN GADGET recherche

	// GADGET Agenda
	$gadget = '';
	$today = getdate(time());
	$jour_today = $today["mday"];
	$mois_today = $today["mon"];
	$annee_today = $today["year"];
	$date = date("Y-m-d", mktime(0,0,0,$mois_today, 1, $annee_today));
	$mois = mois($date);
	$annee = annee($date);
	$jour = jour($date);

	$n = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_messages AS messages WHERE id_auteur=$connect_id_auteur AND statut='publie' AND type='pb' AND rv!='oui' LIMIT 1"));
	if (!$n['n'])
		$n = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE ((lien.id_auteur='$connect_id_auteur' AND lien.id_message=messages.id_message) OR messages.type='affich') AND messages.rv='oui' AND messages.date_heure > DATE_SUB(NOW(), INTERVAL 1 DAY) AND messages.date_heure < DATE_ADD(NOW(), INTERVAL 1 MONTH) AND messages.statut='publie' GROUP BY messages.id_message ORDER BY messages.date_heure LIMIT 1"));
	if ($n['n']) {
			$largeur = "410px";
			$afficher_cal = true;
		}
	else {
			$largeur = "200px";
			$afficher_cal = false;
	}


	// Calendrier
	$gadget .= "<div id='bandeauagenda' class='bandeau_couleur_sous' style='width: $largeur; $spip_lang_left: 100px;'>";
	$gadget .= "<a href='" . generer_url_ecrire("calendrier","type=semaine") . "' class='lien_sous'>";
	$gadget .= _T('icone_agenda');
	$gadget .= "</a>";
			
	$gadget .= "<table><tr>";
	$gadget .= "<td valign='top' width='200'>";
	$gadget .= "<div>";
	$gadget .= http_calendrier_agenda($annee_today, $mois_today, $jour_today, $mois_today, $annee_today, false, generer_url_ecrire('calendrier'));
	$gadget .= "</div>";
	$gadget .= "</td>";
	if ($afficher_cal) {
					$gadget .= "<td valign='top' width='10'> &nbsp; </td>";
					$gadget .= "<td valign='top' width='200'>";
					$gadget .= "<div>&nbsp;</div>";
					$gadget .= "<div style='color: black;'>";
					$gadget .=  http_calendrier_rv(sql_calendrier_taches_annonces(),"annonces");
					$gadget .=  http_calendrier_rv(sql_calendrier_taches_pb(),"pb");
					$gadget .=  http_calendrier_rv(sql_calendrier_taches_rv(), "rv");
					$gadget .= "</div>";
					$gadget .= "</td>";
				}
			
	$gadget .= "</tr></table>";
	$gadget .= "</div>";
	echo afficher_javascript($gadget);
	// FIN GADGET Agenda

	// GADGET Messagerie
	$gadget = '';
	$gadget .= "<div id='bandeaumessagerie' class='bandeau_couleur_sous' style='$spip_lang_left: 130px; width: 200px;'>";
	$gadget .= "<a href='" . generer_url_ecrire("messagerie") . "' class='lien_sous'>";
	$gadget .= _T('icone_messagerie_personnelle');
	$gadget .= "</a>";
		
	$gadget .= "<div>&nbsp;</div>";
	$gadget .= icone_horizontale(_T('lien_nouvea_pense_bete'),generer_url_ecrire("message_edit","new=oui&type=pb"), "pense-bete.gif", '', false);
	$gadget .= icone_horizontale(_T('lien_nouveau_message'),generer_url_ecrire("message_edit","new=oui&type=normal"), "message.gif", '', false);
	if ($connect_statut == "0minirezo") {
		  $gadget .= icone_horizontale(_T('lien_nouvelle_annonce'),generer_url_ecrire("message_edit","new=oui&type=affich"), "annonce.gif", '', false);
		}
	$gadget .= "</div>";

	echo afficher_javascript($gadget);

	// FIN GADGET Messagerie


	// Suivi activite	
	echo "<div id='bandeausynchro' class='bandeau_couleur_sous' style='$spip_lang_left: 160px;'>";
	echo "<a href='" . generer_url_ecrire("synchro") . "' class='lien_sous'>";
	echo _T('icone_suivi_activite');
	echo "</a>";
	echo "</div>";
	
		// Infos perso
	echo "<div id='bandeauinfoperso' class='bandeau_couleur_sous' style='width: 200px; $spip_lang_left: 200px;'>";
	echo "<a href='" . generer_url_ecrire("auteurs_edit","id_auteur=$connect_id_auteur") . "' class='lien_sous'>";
	echo _T('icone_informations_personnelles');
	echo "</a>";
	echo "</div>";

		
		//
		// -------- Affichage de droite ----------
	
		// Deconnection
	echo "<div class='bandeau_couleur_sous' id='bandeaudeconnecter' style='$spip_lang_right: 0px;'>";
	echo "<a href='" . generer_url_action("logout","logout=prive") . "' class='lien_sous'>"._T('icone_deconnecter')."</a>".aide("deconnect");
	echo "</div>";
	
	$decal = 0;
	$decal = $decal + 150;

	echo "<div id='bandeauinterface' class='bandeau_couleur_sous' style='$spip_lang_right: ".$decal."px; text-align: $spip_lang_right;'>";
	echo _T('titre_changer_couleur_interface');
	echo "</div>";
		
	$decal = $decal + 70;
		
	echo "<div id='bandeauecran' class='bandeau_couleur_sous' style='width: 200px; $spip_lang_right: ".$decal."px; text-align: $spip_lang_right;'>";
	if ($spip_ecran == "large") 
			echo "<div><a href='".parametre_url(self(),'set_ecran', 'etroit')."' class='lien_sous'>"._T('info_petit_ecran')."</a>/<b>"._T('info_grand_ecran')."</b></div>";
	else
			echo "<div><b>"._T('info_petit_ecran')."</b>/<a href='".parametre_url(self(),'set_ecran', 'large')."' class='lien_sous'>"._T('info_grand_ecran')."</a></div>";
	echo "</div>";
		
	$decal = $decal + 110;
		
	// En interface simplifiee, afficher un permanence l'indication de l'interface
	if ($options != "avancees") {
			echo "<div id='displayfond' class='bandeau_couleur_sous' style='$spip_lang_right: ".$decal."px; text-align: $spip_lang_right; visibility: visible; background-color: white; color: $couleur_foncee; z-index: -1000; border: 1px solid $couleur_claire; border-top: 0px;'>";
			echo "<b>"._T('icone_interface_simple')."</b>";
			echo "</div>";
	}
	echo "<div id='bandeaudisplay' class='bandeau_couleur_sous' style='$spip_lang_right: ".$decal."px; text-align: $spip_lang_right;'>";
	echo $simple;

	if ($options != "avancees") {		
				echo "<div>&nbsp;</div><div style='width: 250px; text-align: $spip_lang_left;'>"._T('texte_actualite_site_1')."<a href='./?set_options=avancees'>"._T('texte_actualite_site_2')."</a>"._T('texte_actualite_site_3')."</div>";
	}

	echo "</div>";
	echo "</div>";
	echo "</td></tr></table>";
}

function debut_corps_page($rubrique='') {
	global $couleur_foncee;
	global $connect_id_auteur;
  
	// Ouverture de la partie "principale" de la page

	echo "<center onmouseover='recherche_desesperement()'>";

	$result_messages = spip_query("SELECT lien.id_message FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE lien.id_auteur=$connect_id_auteur AND vu='non' AND statut='publie' AND type='normal' AND lien.id_message=messages.id_message");
	$total_messages = @spip_num_rows($result_messages);
	if ($total_messages == 1) {
				while($row = @spip_fetch_array($result_messages)) {
					$ze_message=$row['id_message'];
					echo "<div class='messages'><a href='" . generer_url_ecrire("message","id_message=$ze_message") . "'><font color='$couleur_foncee'>"._T('info_nouveau_message')."</font></a></div>";
				}
			}
			if ($total_messages > 1) echo "<div class='messages'><a href='" . generer_url_ecrire("messagerie") . "'><font color='$couleur_foncee'>"._T('info_nouveaux_messages', array('total_messages' => $total_messages))."</font></a></div>";


	// Afficher les auteurs recemment connectes
	
	global $changer_config;
	global $activer_imessage;

	if ($changer_config!="oui"){
		$activer_imessage = "oui";
	}
	
			if ($activer_imessage != "non") {
				$result_auteurs = spip_query("SELECT id_auteur, nom FROM spip_auteurs WHERE id_auteur!=$connect_id_auteur AND imessage!='non' AND en_ligne>DATE_SUB(NOW(),INTERVAL 15 MINUTE) AND statut IN ('0minirezo','1comite')");

				$nb_connectes = spip_num_rows($result_auteurs);
			}
				
			$flag_cadre = (($nb_connectes > 0) OR $rubrique == "messagerie");
			if ($flag_cadre) echo "<div class='messages' style='color: #666666;'>";

			
			if ($nb_connectes > 0) {
				if ($nb_connectes > 0) {
					echo "<b>"._T('info_en_ligne')."</b>";
					while ($row = spip_fetch_array($result_auteurs)) {
						$id_auteur = $row["id_auteur"];
						$nom_auteur = typo($row["nom"]);
						echo " &nbsp; ".bouton_imessage($id_auteur,$row)."&nbsp;<a href='" . generer_url_ecrire("auteurs_edit","id_auteur=$id_auteur") . "' style='color: #666666;'>$nom_auteur</a>";
					}
				}
			}
			if ($flag_cadre) echo "</div>";
}


function gros_titre($titre, $ze_logo='', $aff=true){
	global $couleur_foncee, $spip_display;
	if ($spip_display == 4) {
		$res = "\n<h1>".typo($titre)."</h1>&nbsp;\n";
	}
	else {
		$res = "<div class='verdana2' style='font-size: 18px; color: $couleur_foncee; font-weight: bold;'>" .
		  (strlen($ze_logo) <= 3 ? '':  (http_img_pack($ze_logo, "", "align='middle'") . " &nbsp; ")) .
		  typo($titre) .
		  "</div>\n";
	}
	if ($aff) echo $res; else return $res;
}


//
// Cadre centre (haut de page)
//

function debut_grand_cadre(){
	global $spip_ecran;
	
	if ($spip_ecran == "large") $largeur = 974;
	else $largeur = 750;
	echo "\n<br><br><table width='$largeur' cellpadding='0' cellspacing='0' border='0'>";
	echo "\n<tr>";
	echo "<td width='$largeur' class='serif'>";

}

function fin_grand_cadre(){
	echo "\n</td></tr></table>";
}

// Cadre formulaires

function debut_cadre_formulaire($style=''){
	echo "\n<div class='cadre-formulaire'" .
	  (!$style ? "" : " style='$style'") .
	   ">";
}

function fin_cadre_formulaire(){
	echo "</div>\n";
}



//
// Debut de la colonne de gauche
//

function debut_gauche($rubrique = "asuivre") {
	global $connect_statut;
	global $options, $spip_display;
	global $connect_id_auteur;
	global $spip_ecran;
	global $flag_3_colonnes, $flag_centre_large;
	global $spip_lang_rtl;

	$flag_3_colonnes = false;
	$largeur = 200;

	// Ecran panoramique ?
	if ($spip_ecran == "large") {
		$largeur_ecran = 974;
		
		// Si edition de texte, formulaires larges
		if (ereg('((articles|breves|rubriques)_edit|forum_envoi)', $GLOBALS['REQUEST_URI'])) {
			$flag_centre_large = true;
		}
		
		$flag_3_colonnes = true;
		$rspan = " rowspan=2";

	}
	else {
		$largeur_ecran = 750;
		$rspan = '';
	}

	echo "<br /><table width='$largeur_ecran' cellpadding='0' cellspacing='0' border='0'>
		<tr><td width='$largeur' class='colonne_etroite serif' valign='top' $rspan>
		<div style='width: ${largeur}px; overflow:hidden;'>
\n";
		
	if ($spip_display == 4) echo "<!-- ";

}


//
// Presentation de l''interface privee, marge de droite
//

function creer_colonne_droite($rubrique=""){
	global $deja_colonne_droite;
	global $flag_3_colonnes, $flag_centre_large;
	global $spip_lang_rtl, $spip_lang_left;

	if ($flag_3_colonnes AND !$deja_colonne_droite) {
		$deja_colonne_droite = true;

		if ($flag_centre_large) {
			$espacement = 17;
			$largeur = 140;
		}
		else {
			$espacement = 37;
			$largeur = 200;
		}


		echo "<td width=$espacement rowspan=2 class='colonne_etroite'>&nbsp;</td>";
		echo "<td rowspan=1 class='colonne_etroite'></td>";
		echo "<td width=$espacement rowspan=2 class='colonne_etroite'>&nbsp;</td>";
		echo "<td width=$largeur rowspan=2 align='$spip_lang_left' valign='top' class='colonne_etroite'><p />";

	}

}

function debut_droite($rubrique="") {
	global $options, $spip_ecran, $deja_colonne_droite, $spip_display;
	global $connect_id_auteur, $connect_statut, $connect_toutes_rubriques;
	global $flag_3_colonnes, $flag_centre_large, $couleur_foncee, $couleur_claire;
	global $spip_lang_left;

	if ($spip_display == 4) echo " -->";

	echo "</div>\n"; # largeur fixe, cf. debut_gauche

	if ($options == "avancees") {
		// liste des articles bloques
		if ($GLOBALS['meta']["articles_modif"] != "non") {
			include_spip('inc/drapeau_edition');
			$articles_ouverts = liste_drapeau_edition ($connect_id_auteur, 'article');
			if (count($articles_ouverts)) {



		//	$vos_articles = spip_query("SELECT id_article, id_rubrique, titre, statut FROM spip_articles WHERE statut='prop' ORDER BY date DESC LIMIT 5");
		//	if (spip_num_rows($vos_articles) > 0) {
				echo "<div>&nbsp;</div>";
				echo "<div class='bandeau_rubriques' style='z-index: 1;'>";
				echo bandeau_titre_boite2(_T('info_cours_edition'), "article-24.gif", $couleur_foncee, 'white', false);
				echo "<div class='plan-articles-bloques'>";


				foreach ($articles_ouverts as $row) {
					$ze_article = $row['id_article'];
					$ze_titre = $row['titre'];
					$statut = $row["statut"];
					
					echo "<div class='$statut'><a style='font-size: 10px;' href='" . generer_url_ecrire("articles","id_article=$ze_article") . "'>$ze_titre</a>";

					if ($ze_article != $GLOBALS['id_article_bloque']) {
						$nb_liberer ++;
						echo "<div style='text-align:right; font-size: 9px;'>", debloquer_article($ze_article,_T('lien_liberer')), "</div>";
					}

					echo "</div>";	
						
				
				}
				echo "</div></div>";

				/*


				echo "<p>";
				debut_cadre_enfonce('article-24.gif');
				//echo "<font face='Verdana,Arial,Sans,sans-serif' size='2'>";
				echo "<div class='verdana2' style='padding: 2px; background-color:$couleur_foncee; color: white; font-weight: bold;'>";
					echo _T('info_cours_edition')."&nbsp;:".aide('artmodif');
				echo "</div>";
				foreach ($articles_ouverts as $row) {
					$ze_article = $row['id_article'];
					$ze_titre = $row['titre'];

					if ($ifond == 1) {
						$couleur = $couleur_claire;
						$ifond = 0;
					} else {
						$couleur = "#eeeeee";
						$ifond = 1;
					}
					
					echo "<div style='padding: 3px; background-color: $couleur; overflow:hidden;'>";
					echo "<div class='verdana1'><b><a href='" . generer_url_ecrire("articles","id_article=$ze_article") . "'>$ze_titre</a></div></b>";
					
					// ne pas proposer de debloquer si c'est l'article en cours d'edition
					if ($ze_article != $GLOBALS['id_article_bloque']) {
						$nb_liberer ++;
						echo "<div class='arial1' style='text-align:right;'>", debloquer_article($ze_article,_T('lien_liberer')), "</div>";
					}
				
					echo "</div>";
				}
				if ($nb_liberer >= 4) {
				  echo "<div class='arial2' style='text-align:right;'>", debloquer_article('tous',_T('lien_liberer_tous')), "</div>";
				}
				//echo "</font>";
				fin_cadre_enfonce();
				
				*/
			}
		}
		
		if (!$deja_colonne_droite) creer_colonne_droite($rubrique);
	}

	echo "<div>&nbsp;</div></td>";

	if (!$flag_3_colonnes) {
		echo "<td width='50'>&nbsp;</td>";
	}
	else {
		if (!$deja_colonne_droite) {
			creer_colonne_droite($rubrique);
		}
		echo "</td></tr><tr>";
	}

	if ($spip_ecran == 'large' AND $flag_centre_large)
		$largeur = 600;
	else
		$largeur = 500;

	echo '<td width="'.$largeur.'" valign="top" align="'.$spip_lang_left.'" rowspan="1" class="serif">';

	// touche d'acces rapide au debut du contenu
	echo "\n<a name='saut' href='#saut' accesskey='s'></a>\n";
}


//
// Presentation de l'interface privee, fin de page et flush()
//

function fin_html() {

	echo "</font>", $GLOBALS['rejoue_session'], "</body></html>\n";
}


function fin_page($credits='') {
	global $spip_display;

	echo "</td></tr></table>";

	debut_grand_cadre();

	# ici on en profite pour glisser une tache de fond

	echo generer_spip_cron();

	if ($spip_display == 4) {
	  echo "<div><a href='./?set_disp=2'>",
	    _T("access_interface_graphique"),
	    "</a></div>";
	} else {
	  echo '<div style="text-align: right; font-family: Verdana; font-size: 8pt">',
	    info_copyright(), "<br />",_T('info_copyright_doc'),'</div>' ;
	}

	fin_grand_cadre();
	echo "</center>";

	fin_html();
}

function debloquer_article($arg, $texte) {
	$lien = parametre_url(self(), 'debloquer_article', $arg, '&');
	return "<a href='" .
	  generer_action_auteur('instituer', "collaboration-$arg", _DIR_RESTREINT_ABS . $lien) .
	  "' title=\"" .
	  entites_html($texte) .
	  "\">$texte&nbsp;" .
	  http_img_pack("croix-rouge.gif", ($arg=='tous' ? "" : "X"),
			"width='7' height='7' align='baseline'") .
	  "</a>";
}

//
// Afficher la hierarchie des rubriques
//

function afficher_hierarchie($id_rubrique, $parents="") {
	global $spip_lang_left;

	if ($id_rubrique) {
		$result = spip_query("SELECT id_rubrique, id_parent, titre, lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique");

		while ($row = spip_fetch_array($result)) {
			$id_rubrique = $row['id_rubrique'];
			$id_parent = $row['id_parent'];
			$titre = sinon($row['titre'], _T('ecrire:info_sans_titre'));
			changer_typo($row['lang']);

			if (acces_restreint_rubrique($id_rubrique))
				$logo = "admin-12.gif";
			if (!$id_parent)
				$logo = "secteur-12.gif";
			else
				$logo = "rubrique-12.gif";


			$parents = "<div class='verdana3' ". 
			  http_style_background($logo, "$spip_lang_left center no-repeat; padding-$spip_lang_left: 15px"). 
			  "><a href='" . generer_url_ecrire("naviguer","id_rubrique=$id_rubrique") . "'>".typo($titre)."</a></div>\n<div style='margin-$spip_lang_left: 15px;'>".$parents."</div>";


		}
		afficher_hierarchie($id_parent, $parents);
	}
	else {
		$logo = "racine-site-12.gif";
		$parents = "<div class='verdana3' " .
		  http_style_background($logo, "$spip_lang_left center no-repeat; padding-$spip_lang_left: 15px"). 
		  "><a href='" . generer_url_ecrire("naviguer","id_rubrique=$id_rubrique") . "'><b>"._T('lien_racine_site')."</b></a>".aide ("rubhier")."</div>\n<div style='margin-$spip_lang_left: 15px;'>".$parents."</div>";
	
		echo $parents;
		
	}
}

// Pour construire des menu avec SELECTED
function mySel($varaut,$variable, $option = NULL) {
	$res = ' value="'.$varaut.'"' . (($variable==$varaut) ? ' selected="selected"' : '');

	return  (!isset($option) ? $res : "<option$res>$option</option>\n");
}


// Voir en ligne, ou apercu, ou rien (renvoie tout le bloc)
function voir_en_ligne ($type, $id, $statut=false, $image='racine-24.gif') {
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
		icone_horizontale($message, generer_url_action('redirect', "id_$type=$id&var_mode=$en_ligne"), $image, "rien.gif");

}

//
// Creer un bouton qui renvoie vers la bonne url spip_rss
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
	. http_img_pack('feed.png', $button, '')
	. "</a>";
}


// Fonction pour la messagerie et la page d'accueil

function http_calendrier_rv($messages, $type) {
	global $spip_lang_rtl, $spip_lang_left, $spip_lang_right;

	$total = '';
	if (!$messages) return $total;
	foreach ($messages as $row) {
		if (ereg("^=([^[:space:]]+)$",$row['texte'],$match))
			$url = quote_amp($match[1]);
		else
			$url = generer_url_ecrire("message", "id_message=".$row['id_message']);

		$rv = ($row['rv'] == 'oui');
		$date = $row['date_heure'];
		$date_fin = $row['date_fin'];
		if ($row['type']=="pb") $bouton = "pense-bete";
		else if ($row['type']=="affich") $bouton = "annonce";
		else $bouton = "message";

		if ($rv) {
			$date_jour = affdate_jourcourt($date);
			$total .= "<tr><td colspan='2'>" .
				(($date_jour == $date_rv) ? '' :
				"<div  class='calendrier-arial11'><b>$date_jour</b></div>") .
				"</td></tr>";
			$date_rv = $date_jour;
			$rv =
		((affdate($date) == affdate($date_fin)) ?
		 ("<div class='calendrier-arial9 fond-agenda'>"
		  . heures($date).":".minutes($date)."<br />"
		  . heures($date_fin).":".minutes($date_fin)."</div>") :
		( "<div class='calendrier-arial9 fond-agenda' style='text-align: center;'>"
		  . heures($date).":".minutes($date)."<br />...</div>" ));
		}

		$total .= "<tr><td style='width: 24px' valign='middle'>" .
		  http_href($url,
				     ($rv ?
				      http_img_pack("rv.gif", 'rv',
						    http_style_background($bouton . '.gif', "no-repeat;'")) : 
				      http_img_pack($bouton.".gif", $bouton, "")),
				     '', '') .
		"</td>" .
		"<td valign='middle'>" .
		$rv .
		"<div><b>" .
		  http_href($url, typo($row['titre']), '', '', 'calendrier-verdana10') .
		"</b></div>" .
		"</td>" .
		"</tr>\n";
	}

	if ($type == 'annonces') {
		$titre = _T('info_annonces_generales');
		$couleur_titre = "ccaa00";
		$couleur_texte = "black";
		$couleur_fond = "#ffffee";
	}
	else if ($type == 'pb') {
		$titre = _T('infos_vos_pense_bete');
		$couleur_titre = "#3874B0";
		$couleur_fond = "#EDF3FE";
		$couleur_texte = "white";
	}
	else if ($type == 'rv') {
		$titre = _T('info_vos_rendez_vous');
		$couleur_titre = "#666666";
		$couleur_fond = "#eeeeee";
		$couleur_texte = "white";
	}

	return
	  debut_cadre_enfonce("", true, "", $titre) .
	  "<table width='100%' border='0' cellpadding='0' cellspacing='2'>" .
	  $total .
	  "</table>" .
	  fin_cadre_enfonce(true);
}

?>
