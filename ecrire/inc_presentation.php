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

include_ecrire ("inc_layer");
include_ecrire("inc_agenda");
include_ecrire("inc_boutons");

// Choix dynamique de la couleur

function choix_couleur() {
	global $couleurs_spip;
	$link = new Link;
	if ($couleurs_spip) {
		while (list($key,$val) = each($couleurs_spip)) {
			$link->delVar('set_couleur');
			$link->addVar('set_couleur', $key);
					
			echo "<a href=\"".$link->getUrl()."\">" .
				http_img_pack("rien.gif", " ", "width='8' height='8' border='0' style='margin: 1px; background-color: ".$val['couleur_claire'].";' onMouseOver=\"changestyle('bandeauinterface','visibility', 'visible');\""). "</a>";
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
	global $couche_invisible;
	$couche_invisible ++;

	// verifier que ce n'est pas un auto-message
	if ($destinataire == $connect_id_auteur)
		return;
	// verifier que le destinataire a un login
	if ($row != "force") {
		$login_req = "select login, messagerie from spip_auteurs where id_auteur=$destinataire AND en_ligne>DATE_SUB(NOW(),INTERVAL 15 DAY)";
		$row = spip_fetch_array(spip_query($login_req));

		if (($row['login'] == "") OR ($row['messagerie'] == "non")) {
			return;
		}
	}

	if ($destinataire) $title = _T('info_envoyer_message_prive');
	else $title = _T('info_ecire_message_prive');

	$texte_bouton = http_img_pack("m_envoi$spip_lang_rtl.gif", "m&gt;", "width='14' height='7' border='0'", $title);
		
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
	global $browser_name;
	global $spip_display, $spip_lang_left;
	static $accesskey = 97; // a

	if ($spip_display != 1 AND $spip_display != 4 AND strlen($icone) > 1) {
		$style_gauche = " padding-$spip_lang_left: 38px;";
		$style_cadre = " style='margin-top: 14px;'";
	}
	
	// accesskey pour accessibilite espace prive
	if ($accesskey <= 122) // z
	{
		$accesskey_c = chr($accesskey++);
		$ret = "<a name='access-$accesskey_c' href='#access-$accesskey_c' accesskey='$accesskey_c'></a>";
	}

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
			$ret .= "<div " . http_style_background($icone, "no-repeat; padding: 0px; margin: 0px");
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
	
	$ret .= "<div class='cadre-padding'>";


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
/*	global $couleur_claire,  $couleur_foncee;
	echo "&nbsp;<p><div style='border: 1px dashed #666666;'><table cellpadding='5' cellspacing='0' border='0' width='100%' style='border-left: 1px solid $couleur_foncee; border-top: 1px solid $couleur_foncee; border-bottom: 1px solid white; border-bottom: 1px solid white' background=''>";
	echo "<tr><td bgcolor='$couleur_claire' width='100%'>";
	echo "<font face='Verdana,Arial,Sans,sans-serif' size='2' color='#333333'>";
	*/
	
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
		echo http_img_pack("rien.gif", " ", "width='100' height='12' border='0'");
		echo "</TD>";
	}
	echo "<TD WIDTH='90'>";
	echo http_img_pack("rien.gif", " ", "width='90' height='12' border='0'");
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
	global $options, $spip_lang_right, $browser_name;
	
	if ($options == "avancees" AND $spip_display != 4) {
		if ($browser_name == "MSIE") 
			return "<a href='$lien'>" .
			  http_img_pack("plus.gif", "+", "border='0'"). "</a> ";
		else
			return "<div style='float:$spip_lang_right; padding-top: 2px;'><a href='$lien'>" .
			  http_img_pack("plus.gif", "+", "border='0'") ."</a></div>";
	}
}



//
// Fonctions d'affichage
//

function afficher_liste($largeurs, $table, $styles = '') {
	global $couleur_claire;
	global $browser_name;
	global $spip_display;
	global $spip_lang_left;

	if (!is_array($table)) return;
	reset($table);
	echo "\n";
	if ($spip_display != 4) {
		while (list(, $t) = each($table)) {
			if (eregi("msie", $browser_name)) $msover = " onMouseOver=\"changeclass(this,'tr_liste_over');\" onMouseOut=\"changeclass(this,'tr_liste');\"";
			echo "<tr class='tr_liste'$msover>";
			reset($largeurs);
			if ($styles) reset($styles);
			while (list(, $texte) = each($t)) {
				$style = $largeur = "";
				list(, $largeur) = each($largeurs);
				if ($styles) list(, $style) = each($styles);
				if (!trim($texte)) $texte .= "&nbsp;";
				echo "<td";
				if ($largeur) echo " width=\"$largeur\"";
				if ($style) echo " class=\"$style\"";
				echo ">$texte</td>";
			}
			echo "</tr>\n";
		}
	} else {
		echo "<ul style='text-align: $spip_lang_left;'>";
		while (list(, $t) = each($table)) {
			echo "<li>";
			reset($largeurs);
			if ($styles) reset($styles);
			while (list(, $texte) = each($t)) {
				$style = $largeur = "";
				list(, $largeur) = each($largeurs);
				
				if (!$largeur) {
					echo $texte." ";
				}
			}
			echo "</li>\n";
		}
		echo "</ul>";
	}
	echo "\n";
}

function afficher_tranches_requete(&$query, $colspan, $tmp_var=false, $javascript=false, $nb_aff = 10) {
	static $ancre = 0;
	global $spip_lang_right, $spip_display;

	$query = trim($query);
	$query_count = $query;
	if (preg_match('{GROUP[[:space:]]BY}',$query_count)==FALSE){
		$query_count = eregi_replace('^(SELECT)[[:space:]].*[[:space:]](FROM)[[:space:]]', '\\1 COUNT(*) \\2 ', $query);
	}
	$query_count = eregi_replace('ORDER[[:space:]]+BY.*$', '', $query_count);

	$res = spip_query($query_count);
	$num_rows = spip_num_rows($res);
	if ($num_rows == 1) // ca n'est pas une requete avec jointure
		list($num_rows) = spip_fetch_array($res);

	if (!$num_rows) return;

	// Ne pas couper pour trop peu
	if ($num_rows <= 1.5 * $nb_aff) $nb_aff = $num_rows;
	if (preg_match('/LIMIT .*(,|OFFSET) *([0-9]+)/', $query, $regs)) {
		if ($num_rows > $regs[2]) $num_rows = $regs[2];
	}

	$texte = "\n";

	if ($num_rows > $nb_aff) {
		if (!$tmp_var) $tmp_var = substr(md5($query), 0, 4);
		$tmp_var = 't_'. $tmp_var;
		
		$deb_aff = intval($GLOBALS[$tmp_var]);
		$ancre++;

		$texte .= "<a name='a$ancre'></a>";
		if ($spip_display != 4) $texte .= "<tr style='background-color: #dddddd;'><td class=\"arial1\" style='border-bottom: 1px solid #444444;' colspan=\"".($colspan - 1)."\">";

		for ($i = 0; $i < $num_rows; $i += $nb_aff){
			$deb = $i + 1;
			$fin = $i + $nb_aff;
			if ($fin > $num_rows) $fin = $num_rows;
			if ($deb > 1) $texte .= " | ";
			if ($deb_aff + 1 >= $deb AND $deb_aff + 1 <= $fin) {
				$texte .= "<B>$deb</B>";
			}
			else {
				$link = new Link;
				$link->addVar($tmp_var, strval($deb - 1));
				if ($javascript) {
					$jj = str_replace("::deb::", "&amp;$tmp_var=$deb", $javascript);
					$texte .= "<a onClick=\"$jj; return false;\" href=\"".$link->getUrl()."#a$ancre\">$deb</a>";
				}
				else $texte .= "<a href=\"".$link->getUrl()."#a$ancre\">$deb</a>";
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
			$link = new Link;
			$link->addVar($tmp_var, -1);
				if ($javascript) {
					$jj = str_replace("::deb::", "&amp;$tmp_var=-1", $javascript);
					$texte .= "<a onClick=\"$jj; return false; \" href=\"".$link->getUrl()."#a$ancre\"><img src='". _DIR_IMG_PACK . "plus.gif' title='"._T('lien_tout_afficher')."' style='border: 0px;'></a>";
				}
				else  $texte .= "<A HREF=\"".$link->getUrl()."#a$ancre\"><img src='". _DIR_IMG_PACK . "plus.gif' title='"._T('lien_tout_afficher')."' style='border: 0px;'></A>";
		}


		if ($spip_display != 4) $texte .= "</td>\n";
		if ($spip_display != 4) $texte .= "</tr>\n";


		if ($deb_aff != -1) {
			if ($deb_aff > 0) $deb_aff --;  // Correction de bug: si on affiche "de 1 a 10", alors LIMIT 0 OFFSET 10
			$query = eregi_replace('LIMIT[[:space:]].*$', '', $query);
#			$query .= " LIMIT  $nb_aff OFFSET $deb_aff";
			$query .= " LIMIT  $deb_aff, $nb_aff ";
		}
	}

	return $texte;
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
	global $spip_lang_left, $dir_lang, $connect_statut, $options, $browser_name;
	
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
		  http_img_pack("$puce", "", "id='imgstatutarticle$id' border='0' style='margin: 1px;'") ."</div>"
			. "\n<div class='puce_article_popup' id='statutdecalarticle$id' onmouseout=\"cacher('statutdecalarticle$id');\" style=' margin-left: -".((11*$clip)+1)."px;'>\n"
			. afficher_script_statut($id, 'article', -1, 'puce-blanche.gif', 'prepa', $titles['blanche'], $action)
			. afficher_script_statut($id, 'article', -12, 'puce-orange.gif', 'prop', $titles['orange'], $action)
			. afficher_script_statut($id, 'article', -23, 'puce-verte.gif', 'publie', $titles['verte'], $action)
			. afficher_script_statut($id, 'article', -34, 'puce-rouge.gif', 'refuse', $titles['rouge'], $action)
			. afficher_script_statut($id, 'article', -45, 'puce-poubelle.gif', 'poubelle', $titles['poubelle'], $action)
		. "</div></div>";
	} else {
		$inser_puce = http_img_pack("$puce", "", "id='imgstatutarticle$id' border='0' style='margin: 1px;'");
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
	$inser_puce = http_img_pack($puce, "", "id='img$type1' border='0' style='margin: 1px;'");

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
		      _DIR_IMG_PACK . $img .
		      "', '" .
		       generer_action_auteur('instituer', "$type $id $statut") .
		      "');",
		      $img,
			"title=\"".$title."\"",
			'','','',
		      $act);
}

//
// Afficher tableau d'articles
//
function afficher_articles($titre_table, $requete, $afficher_visites = false, $afficher_auteurs = true,
		$toujours_afficher = false, $afficher_cadre = true, $afficher_descriptif = true) {

	global $connect_id_auteur, $connect_statut, $dir_lang;
	global $options, $spip_display;
	global $spip_lang_left, $spip_lang_right;




	// Preparation pour basculter vers liens de traductions
	$afficher_trad = ($GLOBALS['meta']['gerer_trad'] == "oui");
	if ($afficher_trad) {
		$jjscript_trad["fonction"] = "afficher_articles_trad";
		$jjscript_trad["titre_table"] = $titre_table;
		$jjscript_trad["requete"] = $requete;
		$jjscript_trad["afficher_visites"] = $afficher_visites;
		$jjscript_trad["afficher_auteurs"] = $afficher_auteurs;
		$jjscript_trad = addslashes(serialize($jjscript_trad));
		$hash = "0x".substr(md5($connect_id_auteur.$jjscript_trad), 0, 16);
	
		$div_trad = substr(md5($requete), 0, 4);

		$res_proch = spip_query("SELECT id_ajax_fonc FROM spip_ajax_fonc WHERE hash=$hash AND id_auteur=$connect_id_auteur ORDER BY id_ajax_fonc DESC LIMIT 1");
		if ($row = spip_fetch_array($res_proch)) {
			$id_ajax_trad = $row["id_ajax_fonc"];
		} else  {
			include_ecrire ("inc_abstract_sql");
			$id_ajax_trad = spip_abstract_insert("spip_ajax_fonc", "(id_auteur, variables, hash, date)", "($connect_id_auteur, '$jjscript_trad', $hash, NOW())");
		}
	}

	$activer_statistiques = $GLOBALS['meta']["activer_statistiques"];
	$afficher_visites = ($afficher_visites AND $connect_statut == "0minirezo" AND $activer_statistiques != "non");

	// Preciser la requete (alleger les requetes)
	if (!ereg("^SELECT", $requete)) {
		$select = "SELECT articles.id_article, articles.titre, articles.id_rubrique, articles.statut, articles.date";

		if (($GLOBALS['meta']['multi_rubriques'] == 'oui' AND $GLOBALS['id_rubrique'] == 0) OR $GLOBALS['meta']['multi_articles'] == 'oui') {
			$afficher_langue = true;
			if ($GLOBALS['langue_rubrique']) $langue_defaut = $GLOBALS['langue_rubrique'];
			else $langue_defaut = $GLOBALS['meta']['langue_site'];
			$select .= ", articles.lang";
		}
		if ($afficher_visites)
			$select .= ", articles.visites, articles.popularite";
		if ($afficher_descriptif)
			$select .= ", articles.descriptif";
		$select .= ", petitions.id_article AS petition ";
		$requete = $select . "FROM spip_articles AS articles " . $requete;
	}
	
	if ($options == "avancees")  $ajout_col = 1;
	else $ajout_col = 0;

	
	$jjscript["fonction"] = "afficher_articles";
	$jjscript["titre_table"] = $titre_table;
	$jjscript["requete"] = $requete;
	$jjscript["afficher_visites"] = $afficher_visites;
	$jjscript["afficher_auteurs"] = $afficher_auteurs;
	$jjscript = addslashes(serialize($jjscript));
	$hash = "0x".substr(md5($connect_id_auteur.$jjscript), 0, 16);



	$tmp_var = substr(md5($jjscript), 0, 4);
	$javascript = "charger_id_url('" . generer_url_ecrire("ajax_page","fonction=sql&id_ajax_fonc=::id_ajax_fonc::::deb::", true) . "','$tmp_var')";
	$tranches = afficher_tranches_requete($requete, $afficher_auteurs ? 4 + $ajout_col : 3 + $ajout_col, $tmp_var, $javascript);

	$requete = str_replace("FROM spip_articles AS articles ", "FROM spip_articles AS articles LEFT JOIN spip_petitions AS petitions USING (id_article)", $requete);

	if (strlen($tranches) OR $toujours_afficher) {

		$res_proch = spip_query("SELECT id_ajax_fonc FROM spip_ajax_fonc WHERE hash=$hash AND id_auteur=$connect_id_auteur ORDER BY id_ajax_fonc DESC LIMIT 1");
		if ($row = spip_fetch_array($res_proch)) {
			$id_ajax_fonc = $row["id_ajax_fonc"];
		} else  {
			include_ecrire ("inc_abstract_sql");
			$id_ajax_fonc = spip_abstract_insert("spip_ajax_fonc", "(id_auteur, variables, hash, date)", "($connect_id_auteur, '$jjscript', $hash, NOW())");
		}

		if (!$GLOBALS["t_$tmp_var"]) {

			if ($afficher_trad) {
				$tmp_trad = substr(md5($requete_trad), 0, 4);

				echo "<div id='$div_trad'>";
				
			}

			echo "<div style='height: 12px;'></div>";
			echo "<div class='liste'>";

			$id_img = "img_".$tmp_var;
			$texte_img = http_img_pack("searching.gif", "*", "style='border: 0px; visibility: hidden; float: $spip_lang_right' id = '$id_img'");

			if ($afficher_trad) {
				$texte_img .= http_img_pack("searching.gif", "*", "style='border: 0px; visibility: hidden; float: $spip_lang_right' id = 'img_$div_trad'");
				$texte_img .= "<div style='float: $spip_lang_right;'><a href=\"javascript:charger_id_url('" . generer_url_ecrire("ajax_page", "fonction=sql&id_ajax_fonc=$id_ajax_trad", true). "','$div_trad');\"><img src='". _DIR_IMG_PACK . "langues-12.gif' border='0' /></a></div>";
			}
			bandeau_titre_boite2($texte_img.$titre_table, "article-24.gif");

			echo "<div id='$tmp_var'>";

		}
		
		$voir_logo = ($spip_display != 1 AND $spip_display != 4 AND $GLOBALS['meta']['image_process'] != "non");
		
		if ($voir_logo) include_ecrire("inc_logos");

		//echo "<table width='100%' cellpadding='2' cellspacing='0' border='0'>";
		echo afficher_liste_debut_tableau();

		$tranches = str_replace("::id_ajax_fonc::", $id_ajax_fonc, $tranches);
		echo $tranches;
		$result = spip_query($requete);
		while ($row = spip_fetch_array($result)) {
			$vals = '';

			$id_article = $row['id_article'];
			$tous_id[] = $id_article;
			$titre = sinon($row['titre'], _T('ecrire:info_sans_titre'));
			$id_rubrique = $row['id_rubrique'];
			$date = $row['date'];
			$statut = $row['statut'];
			$visites = $row['visites'];
			if ($lang = $row['lang']) changer_typo($lang);
			$popularite = ceil(min(100,100 * $row['popularite'] / max(1, 0 + $GLOBALS['meta']['popularite_max'])));
			$descriptif = $row['descriptif'];
			if ($descriptif) $descriptif = ' title="'.attribut_html(typo($descriptif)).'"';
			$petition = $row['petition'];

			if ($afficher_auteurs) {
				$les_auteurs = "";
				$query2 = "SELECT auteurs.id_auteur, nom, messagerie, login, bio ".
					"FROM spip_auteurs AS auteurs, spip_auteurs_articles AS lien ".
					"WHERE lien.id_article=$id_article AND auteurs.id_auteur=lien.id_auteur";
				$result_auteurs = spip_query($query2);

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

			$s .= "<a href='" . generer_url_ecrire("articles","id_article=$id_article") . "'$descriptif$dir_lang style=\"display:block;\">";

			if ($voir_logo)	$s .= baliser_logo("art", $id_article, 26, 20);
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
			$s = affdate_jourcourt($date);
			$vals[] = $s;

			// Le numero (moche)
			if ($options == "avancees") {
				$vals[] = "<b>"._T('info_numero_abbreviation')."$id_article</b>";
			}
			

			$table[] = $vals;
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
		afficher_liste($largeurs, $table, $styles);

		//echo "</table>";
		echo afficher_liste_fin_tableau();
		echo "</div>";
		
		if (!$GLOBALS["t_$tmp_var"]) {
			echo "</div>";
			if ($afficher_trad) echo "</div>";
			
		}
		
		//if ($afficher_cadre) fin_cadre_gris_clair();

	}

	return $tous_id;
}

function afficher_articles_trad($titre_table, $requete, $afficher_visites = false, $afficher_auteurs = true,
		$toujours_afficher = false, $afficher_cadre = true, $afficher_descriptif = true) {

	global $connect_id_auteur, $connect_statut, $dir_lang;
	global $options, $spip_display;
	global $spip_lang_left, $spip_lang_right;

	$langues_site = explode(',', $GLOBALS['meta']['langues_multilingue']);

	// Preparation pour basculter vers liste normale
		$jjscript_trad["fonction"] = "afficher_articles";
		$jjscript_trad["titre_table"] = $titre_table;
		$jjscript_trad["requete"] = $requete;
		$jjscript_trad["afficher_visites"] = $afficher_visites;
		$jjscript_trad["afficher_auteurs"] = $afficher_auteurs;
		$jjscript_trad = addslashes(serialize($jjscript_trad));
		$hash = "0x".substr(md5($connect_id_auteur.$jjscript_trad), 0, 16);
	
		$div_trad = substr(md5($requete), 0, 4);

		$res_proch = spip_query("SELECT id_ajax_fonc FROM spip_ajax_fonc WHERE hash=$hash AND id_auteur=$connect_id_auteur ORDER BY id_ajax_fonc DESC LIMIT 1");
		if ($row = spip_fetch_array($res_proch)) {
			$id_ajax_trad = $row["id_ajax_fonc"];
		} else  {
			include_ecrire ("inc_abstract_sql");
			$id_ajax_trad = spip_abstract_insert("spip_ajax_fonc", "(id_auteur, variables, hash, date)", "($connect_id_auteur, '$jjscript_trad', $hash, NOW())");
		}


	$activer_statistiques = $GLOBALS['meta']["activer_statistiques"];
	$afficher_visites = ($afficher_visites AND $connect_statut == "0minirezo" AND $activer_statistiques != "non");

	// Preciser la requete (alleger les requetes)
	if (!ereg("^SELECT", $requete)) {
		$select = "SELECT articles.id_article, articles.titre, articles.id_rubrique, articles.statut, articles.date, articles.id_trad, articles.lang";
		$requete = $select . " FROM spip_articles AS articles " . $requete;
	}
	
	if ($options == "avancees")  $ajout_col = 1;
	else $ajout_col = 0;

	
	$jjscript["fonction"] = "afficher_articles_trad";
	$jjscript["titre_table"] = $titre_table;
	$jjscript["requete"] = $requete;
	$jjscript["afficher_visites"] = $afficher_visites;
	$jjscript["afficher_auteurs"] = $afficher_auteurs;
	$jjscript = addslashes(serialize($jjscript));
	$hash = "0x".substr(md5($connect_id_auteur.$jjscript), 0, 16);
	$tmp_var = substr(md5($jjscript), 0, 4);
	
	$javascript = "charger_id_url('" . generer_url_ecrire("ajax_page", 'fonction=sql&id_ajax_fonc=::id_ajax_fonc::::deb::', true) . "','$tmp_var')";
	$tranches = afficher_tranches_requete($requete, 4, $tmp_var, $javascript);

	$requete = str_replace("FROM spip_articles AS articles ", "FROM spip_articles AS articles LEFT JOIN spip_petitions AS petitions USING (id_article)", $requete);

	if (strlen($tranches) OR $toujours_afficher) {

		$res_proch = spip_query("SELECT id_ajax_fonc FROM spip_ajax_fonc WHERE hash=$hash AND id_auteur=$connect_id_auteur ORDER BY id_ajax_fonc DESC LIMIT 1");
		if ($row = spip_fetch_array($res_proch)) {
			$id_ajax_fonc = $row["id_ajax_fonc"];
		} else  {
			include_ecrire ("inc_abstract_sql");
			$id_ajax_fonc = spip_abstract_insert("spip_ajax_fonc", "(id_auteur, variables, hash, date)", "($connect_id_auteur, '$jjscript', $hash, NOW())");
		}

		if (!$GLOBALS["t_$tmp_var"]) {

			echo "<div id='$div_trad'>";

			echo "<div style='height: 12px;'></div>";
			echo "<div class='liste'>";

			$id_img = "img_".$tmp_var;
			$texte_img = http_img_pack("searching.gif", "*", "style='border: 0px; visibility: hidden; float: $spip_lang_right' id = '$id_img'");
			
			$texte_img .= http_img_pack("searching.gif", "*", "style='border: 0px; visibility: hidden; float: $spip_lang_right' id = 'img_$div_trad'");

			$texte_img .= "<div style='float: $spip_lang_right;'><a href=\"javascript:charger_id_url('" . generer_url_ecrire("ajax_page", "fonction=sql&id_ajax_fonc=$id_ajax_trad", true) . "','$div_trad');\"><img src='". _DIR_IMG_PACK . "langues-off-12.gif' border='0' /></a></div>";

			bandeau_titre_boite2($texte_img.$titre_table, "article-24.gif");

			echo "<div id='$tmp_var'>";

		}
		

		//echo "<table width='100%' cellpadding='2' cellspacing='0' border='0'>";
		echo afficher_liste_debut_tableau();

		$tranches = ereg_replace("\:\:id\_ajax\_fonc\:\:", $id_ajax_fonc, $tranches);
		echo $tranches;

		$result = spip_query($requete);
		while ($row = spip_fetch_array($result)) {
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

			$s .= "<a href='" . generer_url_ecrire("articles","id_article=$id_article") . "'$descriptif$dir_lang style=\"display:block;\">";
			
			
			if ($id_article == $id_trad) $titre = "<b>$titre</b>";
			
			$s .= typo($titre);
			if ($afficher_langue AND $lang != $langue_defaut)
				$s .= " <font size='1' color='#666666'$dir_lang>(".traduire_nom_langue($lang).")</font>";
			if ($petition) $s .= " <font size=1 color='red'>"._T('lien_petitions')."</font>";
			$s .= "</a>";
			$s .= "</div>";
			
			$vals[] = $s;
			
			$vals[] = "";

			$table[] = $vals;
		}
		spip_free_result($result);

		$largeurs = array(11, 24, '', '1');
		$styles = array('', 'arial1', 'arial1', '');

		afficher_liste($largeurs, $table, $styles);

		//echo "</table>";
		echo afficher_liste_fin_tableau();
		echo "</div>";
		
		if (!$GLOBALS["t_$tmp_var"]) echo "</div>";
		
		//if ($afficher_cadre) fin_cadre_gris_clair();

	}

	return $tous_id;
}



//
// Afficher tableau de breves
//

function afficher_breves($titre_table, $requete, $affrub=false) {
	global $connect_id_auteur, $spip_lang_right, $spip_lang_left, $dir_lang, $couleur_claire, $couleur_foncee;
	global $connect_statut, $options;	


	if (($GLOBALS['meta']['multi_rubriques'] == 'oui' AND $GLOBALS['id_rubrique'] == 0) OR $GLOBALS['meta']['multi_articles'] == 'oui') {
		$afficher_langue = true;
		$requete = ereg_replace(" FROM", ", lang FROM", $requete);
		if ($GLOBALS['langue_rubrique']) $langue_defaut = $GLOBALS['langue_rubrique'];
		else $langue_defaut = $GLOBALS['meta']['langue_site'];
	}
	
	if ($options == "avancees") $tranches = afficher_tranches_requete($requete, 4);
	else  $tranches = afficher_tranches_requete($requete, 3);

	if (strlen($tranches)) {

		//debut_cadre_relief("breve-24.gif");

		if ($titre_table) echo "<div style='height: 12px;'></div>";
		echo "<div class='liste'>";

		if ($titre_table) {
			bandeau_titre_boite2($titre_table, "breve-24.gif", $couleur_foncee, "white");
		}

		echo "<table width='100%' cellpadding='2' cellspacing='0' border='0' background=''>";

		echo $tranches;

		$result = spip_query($requete);

		$table = '';
		$droit = ($connect_statut == '0minirezo' && $options == 'avancees');
		$voir_logo = ($spip_display != 1 AND $spip_display != 4 AND $GLOBALS['meta']['image_process'] != "non");
		
		if ($voir_logo) include_ecrire("inc_logos");

		while ($row = spip_fetch_array($result)) {
			$vals = '';

			$id_breve = $row['id_breve'];
			$tous_id[] = $id_breve;
			$date_heure = $row['date_heure'];
			$titre = sinon($row['titre'], _T('ecrire:info_sans_titre'));
			$statut = $row['statut'];
			if ($lang = $row['lang']) changer_typo($lang);
			$id_rubrique = $row['id_rubrique'];
			
			$vals[] = puce_statut_breve($id_breve, $statut, 'breve', ($droit && acces_rubrique($id_rubrique)), $id_rubrique);

			$s = "<div>";
			$s .= "<a href='" . generer_url_ecrire("breves_voir","id_breve=$id_breve") . "' style=\"display:block;\">";

			if ($voir_logo) $s .= baliser_logo("breve", $id_breve, 26, 20);
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
			
			$table[] = $vals;
		}
		spip_free_result($result);

		if ($options == "avancees") {
			if ($affrub) $largeurs = array('7', '', '188', '35');
			else  $largeurs = array('7','', '100', '35');
			$styles = array('', 'arial11', 'arial1', 'arial1');
		} else {
			if ($affrub) $largeurs = array('7','', '188');
			else  $largeurs = array('7','', '100');
			$styles = array('','arial11', 'arial1');
		}

		afficher_liste($largeurs, $table, $styles);

		echo "</table></div>";
		//fin_cadre_relief();
	}
	return $tous_id;
}


//
// Afficher tableau de rubriques
//

function afficher_rubriques($titre_table, $requete) {
	global $connect_id_auteur;
	global $spip_lang_rtl;

	$tranches = afficher_tranches_requete($requete, 3);

	if (strlen($tranches)) {

		if ($titre_table) echo "<div style='height: 12px;'></div>";
		echo "<div class='liste'>";
		//debut_cadre_relief("rubrique-24.gif");

		if ($titre_table) {
			bandeau_titre_boite2($titre_table, "rubrique-24.gif", "#999999", "white");
		}
		echo "<table width=100% cellpadding=3 cellspacing=0 border=0 background=''>";

		echo $tranches;

		$result = spip_query($requete);

		$table = '';
		while ($row = spip_fetch_array($result)) {
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

			$s = http_img_pack($puce, '- ', "border='0'");
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
			$table[] = $vals;
		}
		spip_free_result($result);

		$largeurs = array('12','', '');
		$styles = array('', 'arial2', 'arial11');
		afficher_liste($largeurs, $table, $styles);

		echo "</TABLE>";
		//fin_cadre_relief();
		echo "</div>";
	}
	return $tous_id;
}


//
// Afficher des auteurs sur requete SQL
//
function bonhomme_statut($row) {
	global $connect_statut;

	switch($row['statut']) {
		case "0minirezo":
			return http_img_pack("admin-12.gif", "", "border='0'",
					_T('titre_image_administrateur'));
			break;
		case "1comite":
			if ($connect_statut == '0minirezo' AND ($row['source'] == 'spip' AND !($row['pass'] AND $row['login'])))
			  return http_img_pack("visit-12.gif",'', "border='0'", _T('titre_image_redacteur'));
			else
			  return http_img_pack("redac-12.gif",'', "border='0'", _T('titre_image_redacteur_02'));
			break;
		case "5poubelle":
		  return http_img_pack("poubelle.gif", '', "border='0'",_T('titre_image_auteur_supprime'));
			break;
		case "6forum":
		  return http_img_pack("visit-12.gif", '', "border='0'",_T('titre_image_visiteur'));
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
	$tranches = afficher_tranches_requete($requete, 2);

	if (strlen($tranches)) {

		debut_cadre_relief("auteur-24.gif");

		if ($titre_table) {
			echo "<p><table width=100% cellpadding=0 cellspacing=0 border=0 background=''>";
			echo "<tr><td width=100% background=''>";
			echo "<table width=100% cellpadding=3 cellspacing=0 border=0>";
			echo "<tr bgcolor='#333333'><td width=100% colspan=2><font face='Verdana,Arial,Sans,sans-serif' size=3 color='#FFFFFF'>";
			echo "<b>$titre_table</b></font></td></tr>";
		}
		else {
			echo "<p><table width=100% cellpadding=3 cellspacing=0 border=0 background=''>";
		}

		echo $tranches;

		$result = spip_query($requete);

		$table = '';
		while ($row = spip_fetch_array($result)) {
			$vals = '';

			$id_auteur = $row['id_auteur'];
			$tous_id[] = $id_auteur;
			$nom = $row['nom'];

			$s = bonhomme_statut($row);
			$s .= "<a href='" . generer_url_ecrire("auteurs_edit","id_auteur=$id_auteur") . "'>";
			$s .= typo($nom);
			$s .= "</a>";
			$vals[] = $s;
			$table[] = $vals;
		}
		spip_free_result($result);

		$largeurs = array('');
		$styles = array('arial2');
		afficher_liste($largeurs, $table, $styles);

		if ($titre_table) echo "</TABLE></TD></TR>";
		echo "</TABLE>";
		fin_cadre_relief();
	}
	return $tous_id;
}

/*
 * Afficher liste de messages
 */

function afficher_messages($titre_table, $query_message, $afficher_auteurs = true, $important = false, $boite_importante = true, $obligatoire = false) {
	global $messages_vus;
	global $connect_id_auteur;
	global $couleur_claire, $couleur_foncee;
	global $spip_lang_rtl, $spip_lang_left;

	// Interdire l'affichage de message en double
	if ($messages_vus) {
		$query_message .= ' AND messages.id_message NOT IN ('.join(',', $messages_vus).')';
	}


	if ($afficher_auteurs) $cols = 4;
	else $cols = 2;
	$query_message .= ' ORDER BY date_heure DESC';
	$tranches = afficher_tranches_requete($query_message, $cols);

	if ($tranches OR $obligatoire) {
		if ($important) debut_cadre_couleur();

		echo "<div style='height: 12px;'></div>";
		echo "<div class='liste'>";
	//	bandeau_titre_boite($titre_table, $afficher_auteurs, $boite_importante);
		bandeau_titre_boite2($titre_table, "messagerie-24.gif", $couleur_foncee, "white");
		echo "<TABLE WIDTH='100%' CELLPADDING='2' CELLSPACING='0' BORDER='0'>";


		echo $tranches;

		$result_message = spip_query($query_message);
		$num_rows = spip_num_rows($result_message);

		while($row = spip_fetch_array($result_message)) {
			$vals = '';

			$id_message = $row['id_message'];
			$date = $row["date_heure"];
			$date_fin = $row["date_fin"];
			$titre = sinon($row['titre'], _T('ecrire:info_sans_titre'));
			$type = $row["type"];
			$statut = $row["statut"];
			$page = $row["page"];
			$rv = $row["rv"];
			$vu = $row["vu"];
			$messages_vus[$id_message] = $id_message;

			//
			// Titre
			//

			$s = "<a href='" . generer_url_ecrire("message","id_message=$id_message") . "' style='display: block;'>";

			switch ($type) {
			case 'pb' :
				$puce = "m_envoi_bleu$spip_lang_rtl.gif";
				break;
			case 'memo' :
				$puce = "m_envoi_jaune$spip_lang_rtl.gif";
				break;
			case 'affich' :
				$puce = "m_envoi_jaune$spip_lang_rtl.gif";
				break;
			case 'normal':
			default:
				$puce = "m_envoi$spip_lang_rtl.gif";
				break;
			}
				
			$s .= http_img_pack("$puce", "", "width='14' height='7' border='0'");
			$s .= "&nbsp;&nbsp;".typo($titre)."</A>";
			$vals[] = $s;

			//
			// Auteurs

			if ($afficher_auteurs) {
				$query_auteurs = "SELECT auteurs.id_auteur, auteurs.nom FROM spip_auteurs AS auteurs, spip_auteurs_messages AS lien WHERE lien.id_message=$id_message AND lien.id_auteur!=$connect_id_auteur AND lien.id_auteur=auteurs.id_auteur";
				$result_auteurs = spip_query($query_auteurs);
				$auteurs = '';
				while ($row_auteurs = spip_fetch_array($result_auteurs)) {
					$id_auteur = $row_auteurs['id_auteur'];
					$auteurs[] = "<a href='" . generer_url_ecrire("auteurs_edit","id_auteur=$id_auteur") . "'>".typo($row_auteurs['nom'])."</a>";
				}

				if ($auteurs AND $type == 'normal') {
					$s = "<FONT FACE='Arial,Sans,sans-serif' SIZE=1>";
					$s .= join(', ', $auteurs);
					$s .= "</FONT>";
				}
				else $s = "&nbsp;";
				$vals[] = $s;
			}
			
			//
			// Messages de forums
			
			$query_forum = "SELECT * FROM spip_forum WHERE id_message = $id_message";
			$total_forum = spip_num_rows(spip_query($query_forum));
			
			if ($total_forum > 0) $vals[] = "($total_forum)";
			else $vals[] = "";
			
		
			
			//
			// Date
			//
			
			$s = affdate($date);
			if ($rv == 'oui') {
				$jour=journum($date);
				$mois=mois($date);
				$annee=annee($date);
				
				$heure = heures($date).":".minutes($date);
				if (affdate($date) == affdate($date_fin))
					$heure_fin = heures($date_fin).":".minutes($date_fin);
				else 
					$heure_fin = "...";

				$s = "<div " . 
				  http_style_background('rv-12.gif', "$spip_lang_left center no-repeat; padding-$spip_lang_left: 15px") .
				  "><a href='" . generer_url_ecrire("calendrier","type=jour&jour=$jour&mois=$mois&annee=$annee") . "'><b style='color: black;'>$s</b><br />$heure-$heure_fin</a></div>";
			} else {
				$s = "<font color='#999999'>$s</font>";
			}
			
			$vals[] = $s;

			$table[] = $vals;
		}

		if ($afficher_auteurs) {
			$largeurs = array('', 130, 20, 120);
			$styles = array('arial2', 'arial1', 'arial1', 'arial1');
		}
		else {
			$largeurs = array('', 20, 120);
			$styles = array('arial2', 'arial1', 'arial1');
		}
		afficher_liste($largeurs, $table, $styles);

		echo "</TABLE>";
		echo "</div>\n\n";
		spip_free_result($result_message);
		if ($important) fin_cadre_couleur();
	}
}


//
// Afficher les forums
//

function afficher_forum($request, $adresse_retour, $controle_id_article = false) {
	global $debut;
	static $compteur_forum;
	static $nb_forum;
	static $i;
	global $couleur_foncee;
	global $connect_id_auteur;
	global $mots_cles_forums;
	global $spip_lang_rtl, $spip_lang_left, $spip_lang_right, $spip_display;

	$compteur_forum++;

	$nb_forum[$compteur_forum] = spip_num_rows($request);
	$i[$compteur_forum] = 1;
	
	if ($spip_display == 4) echo "<ul>";
 
	$voir_logo = ($spip_display != 1 AND $spip_display != 4 AND $GLOBALS['meta']['image_process'] != "non");
		
	if ($voir_logo) include_ecrire("inc_logos");

 	while($row = spip_fetch_array($request)) {
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
	
		$forum_stat = $statut;
		if ($forum_stat == "prive") $logo = "forum-interne-24.gif";
		else if ($forum_stat == "privadm") $logo = "forum-admin-24.gif";
		else if ($forum_stat == "privrac") $logo = "forum-interne-24.gif";
		else $logo = "forum-public-24.gif";

		if ($compteur_forum==1) echo "\n<br /><br />";
		$afficher = ($controle_id_article) ? ($statut!="perso") :
			(($statut=="prive" OR $statut=="privrac" OR $statut=="privadm" OR $statut=="perso")
			OR ($statut=="publie" AND $id_parent > 0));

		if ($afficher) {
			echo "<a id='$id_forum'></a>";
			if ($spip_display != 4) echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'><tr>";
			for ($count=2;$count<=$compteur_forum AND $count<20;$count++){
				$fond[$count]=_DIR_IMG_PACK . 'rien.gif';
				if ($i[$count]!=$nb_forum[$count]){
					$fond[$count]=_DIR_IMG_PACK . 'forum-vert.gif';
				}
				$fleche='rien.gif';
				if ($count==$compteur_forum){
					$fleche="forum-droite$spip_lang_rtl.gif";
				}
				if ($spip_display != 4) echo "<td width='10' valign='top' background=$fond[$count]>" .
				  http_img_pack($fleche, " ", "width='10' height='13' border='0'"). "</td>\n";
			}

			if ($spip_display != 4) echo "\n<td width=100% valign='top'>";

			if ($id_auteur AND $voir_logo) {
				$titre_boite = baliser_logo("aut", $id_auteur, 48, 48,"position: absolute; $spip_lang_right: 0px; margin: 0px; margin-top: -3px; margin-$spip_lang_right: 0px;") .typo($titre);
			} else $titre_boite = $titre;
		
			if ($spip_display == 4) {
				echo "<li>".typo($titre)."<br>";
			} else {
				if ($compteur_forum == 1) echo debut_cadre_forum($logo, false, "", $titre_boite);
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
			else
				echo typo($auteur);

			if ($id_auteur) {
				$bouton = bouton_imessage($id_auteur,$row_auteur);
				if ($bouton) echo "&nbsp;".$bouton;
			}

			// boutons de moderation
			if ($controle_id_article && is_numeric($controle_id_article))
				echo boutons_controle_forum($id_forum, $statut, $id_auteur, "id_article=$controle_id_article", $ip);

			echo safehtml(justifier(propre($texte)));

			if (strlen($url_site) > 10 AND $nom_site) {
				echo "<div align='left' class='verdana2'><b><a href='$url_site'>$nom_site</a></b></div>";
			}

			if (!$controle_id_article) {
				echo "<div align='right' class='verdana1'>";
				echo "<b><a href='", generer_url_ecrire("forum_envoi","id_parent=$id_forum&adresse_retour=".rawurlencode($adresse_retour)."&titre_message=".rawurlencode($titre)), 
				  "'>",
				  _T('lien_repondre_message'),
				  "</a></b></div>";
			}

			if ($mots_cles_forums == "oui"){

				$query_mots = "SELECT * FROM spip_mots AS mots, spip_mots_forum AS lien WHERE lien.id_forum = '$id_forum' AND lien.id_mot = mots.id_mot";
				$result_mots = spip_query($query_mots);

				while ($row_mots = spip_fetch_array($result_mots)) {
					$id_mot = $row_mots['id_mot'];
					$titre_mot = propre($row_mots['titre']);
					$type_mot = propre($row_mots['type']);
					echo "<li> <b>$type_mot :</b> $titre_mot";
				}

			}
	
			if ($statut == "off" OR $statut == "prop") echo "</div>";

			if ($spip_display != 4) {
				if ($compteur_forum == 1) echo fin_cadre_forum();
				else echo fin_cadre_thread_forum();
			}

			if ($spip_display != 4) echo "</td></tr></table>\n";

			afficher_thread_forum($id_forum,$adresse_retour,$controle_id_article);

		}
		$i[$compteur_forum]++;
	}
	if ($spip_display == 4) echo "</ul>";
	spip_free_result($request);
	$compteur_forum--;
}

function afficher_thread_forum($le_forum, $adresse_retour, $controle = 0) {
	
	if ($controle) {
		$query_forum2 = "SELECT * FROM spip_forum WHERE id_parent='$le_forum' ORDER BY date_heure";
	}
	else {
		$query_forum2 = "SELECT * FROM spip_forum WHERE id_parent='$le_forum' AND statut<>'off' ORDER BY date_heure";
	}
 	$result_forum2 = spip_query($query_forum2);
	afficher_forum($result_forum2, $adresse_retour, $controle);
	
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

	// CSS par defaut /spip_style.css
	$res = '<link rel="stylesheet" type="text/css" href="'
	. _DIR_RACINE . 'spip_style.css'.'" />'

	// CSS espace prive
	. '<link rel="stylesheet" type="text/css" href="'
	. generer_url_public('style_prive', $args) .'" />
'
	// CSS calendrier
	. '<link rel="stylesheet" type="text/css" href="' . _DIR_IMG_PACK
	. 'calendrier.css"  />' . "\n"

	// CSS imprimante (masque des trucs, a completer)
	. '<link rel="stylesheet" type="text/css" href="' . _DIR_IMG_PACK
	. 'spip_style_print.css" media="print" />' . "\n"

	// CSS "visible au chargement", hack necessaire pour garder un depliement
	// sympathique meme sans javascript (on exagere ?)
	// Pour l'explication voir http://www.alistapart.com/articles/alternate/
	. '<link rel="alternate stylesheet" type="text/css" href="' . _DIR_IMG_PACK
	. 'spip_style_invisible.css" title="invisible" />' . "\n"
	. '<link rel="stylesheet" href="' . _DIR_IMG_PACK
	. 'spip_style_visible.css"  title="visible" />' . "\n"

	// favicon.ico
	  . '<link rel="shortcut icon" href="' . _DIR_IMG_PACK . "favicon.ico\" />\n";
	$js = debut_javascript($connect_statut == "0minirezo"
			AND $connect_toutes_rubriques,
			($GLOBALS['meta']["activer_statistiques"] != 'non'));

	if ($spip_display == 4) return $res . $js;

	$res .= "<link rel='alternate' type='application/rss+xml'
			title=\"".entites_html($nom_site_spip)."\" href='"
			. generer_url_public('backend') . "' />\n";
	$res .= "<link rel='help' type='text/html' title=\""._T('icone_aide_ligne') . 
			"\" href='"
			. generer_url_ecrire('aide_index',"var_lang=$spip_lang")
			."' />\n";
	if ($GLOBALS['meta']["activer_breves"] != "non")
		$res .= "\n<link rel='alternate' type='application/rss+xml' title='"
			. addslashes($nom_site_spip)
			. " ("._T("info_breves_03")
			. ")' href='" . generer_url_public('backend-breves') . "' />\n";

	return $res . $js;
}

function debut_javascript($admin, $stat)
{
	global $spip_lang_left, $browser_name, $browser_version;
	include_ecrire("inc_charsets");


	# teste la capacite ajax : on envoie un cookie -1
	# et un script ajax ; si le script reussit le cookie passera a +1
	if (!$GLOBALS['_COOKIE']['spip_accepte_ajax']) {
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
	global $spip_display;
	
	echo "<td>&nbsp;</td>";
	
}

function debut_onglet(){
	global $spip_display;

	echo "\n\n";
	echo "<div style='padding: 7px;'><table cellpadding='0' cellspacing='0' border='0' align='center'>";
	echo "<tr>";
}

function fin_onglet(){
	global $spip_display;
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
		
		echo "<div onMouseOver=\"changeclass(this, 'onglet_on');\" onMouseOut=\"changeclass(this, 'onglet');\" class='onglet' style='position: relative;$style'><a href='$lien'>$texte</a></div>";
		
		
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
	global $spip_display, $spip_ecran, $couleur_foncee ;
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
	
	if (!$menu_accesskey) $menu_accesskey = 1;
	if ($menu_accesskey < 10) {
		$accesskey = " accesskey='$menu_accesskey'";
		$menu_accesskey++;
	}
	else if ($menu_accesskey == 10) {
		$accesskey = " accesskey='0'";
		$menu_accesskey++;
	}

	if ($sous_rubrique_icone == $sous_rubrique) $class_select = " class='selection'";

	if (eregi("^javascript:",$lien)) {
		$a_href = "<a$accesskey onClick=\"$lien; return false;\" href='$lien_noscript' target='spip_aide'$class_select>";
	}
	else {
		$a_href = "<a$accesskey href=\"$lien\"$class_select>";
	}

	$compteur_survol ++;

	if ($spip_display != 1 AND $spip_display != 4) {
		echo "<td class='cellule48' onMouseOver=\"changestyle('bandeau$rubrique_icone', 'visibility', 'visible');\" width='$largeur'>$a_href" .
		  http_img_pack("$fond", $alt, "$title width='48' height='48'");
		if ($spip_display != 3) {
			echo "<span>$texte</span>";
		}
	}
	else echo "<td class='cellule-texte' onMouseOver=\"changestyle('bandeau$rubrique_icone', 'visibility', 'visible');\" width='$largeur'>$a_href".$texte;
	echo "</a></td>\n";
}




function icone_bandeau_secondaire($texte, $lien, $fond, $rubrique_icone = "vide", $rubrique, $aide=""){
	global $spip_display;
	global $menu_accesskey, $compteur_survol;

	$alt = '';
	$title = '';
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
	
	if (!$menu_accesskey) $menu_accesskey = 1;
	if ($menu_accesskey < 10) {
		$accesskey = " accesskey='$menu_accesskey'";
		$menu_accesskey++;
	}
	else if ($menu_accesskey == 10) {
		$accesskey = " accesskey='0'";
		$menu_accesskey++;
	}
	if ($spip_display == 3) $accesskey_icone = $accesskey;

	if ($rubrique_icone == $rubrique) $class_select = " class='selection'";
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
}



function icone($texte, $lien, $fond, $fonction="", $align="", $afficher='oui'){
	global $spip_display, $couleur_claire, $couleur_foncee, $compteur_survol;

	if (strlen($fonction) < 3) $fonction = "rien.gif";
	if (strlen($align) > 2) $aligner = " ALIGN='$align' ";

	if ($spip_display == 1){
		$hauteur = 20;
		$largeur = 100;
		$alt = "";
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
		$alt = $texte;
	}

	if ($fonction == "supprimer.gif") {
		$style = '-danger';
	} else {
		$style = '';
	}

	$compteur_survol ++;
	$icone .= "\n<table cellpadding='0' class='pointeur' cellspacing='0' border='0' $aligner width='$largeur'>";
		$icone .= "<tr><td class='icone36$style' style='text-align:center;'><a href='$lien'>";
	if ($spip_display != 1 AND $spip_display != 4){
		if ($fonction != "rien.gif"){
		  $icone .= http_img_pack($fonction, $alt, "$title width='24' height='24' border='0'" .
					  http_style_background($fond, "no-repeat center center"));
		}
		else {
			$icone .= http_img_pack($fond, $alt, "$title width='24' height='24' border='0'");
		}
	}
	if ($spip_display != 3){
		$icone .= "<span>$texte</span>";
	}
	$icone .= "</a></td></tr>";
	$icone .= "</table>";

	if ($afficher == 'oui')
		echo $icone;
	else
		return $icone;
}

function icone_horizontale($texte, $lien, $fond = "", $fonction = "", $echo = true, $javascript='') {
	global $spip_display, $couleur_claire, $couleur_foncee, $compteur_survol;

	$retour = '';


	if ($spip_display != 4) {
		if (!$fonction) $fonction = "rien.gif";
		$danger = ($fonction == "supprimer.gif");
	
		if ($danger) $retour .= "<div class='danger'>";
		if ($spip_display != 1) {
			$retour .= "<a href='$lien' class='cellule-h' $javascript><table cellpadding='0' valign='middle'><tr>\n";
			$retour .= "<td><a href='$lien' class='cellule-h'><div class='cell-i'>" .
			  http_img_pack($fonction, "", http_style_background($fond, "center center no-repeat")) .
			  "</div></a></td>\n" .
			  "<td class='cellule-h-lien'><a href='$lien' class='cellule-h'>$texte</a></td>\n";
			$retour .= "</tr></table></a>\n";
		}
		else {
			$retour .= "<a href='$lien' class='cellule-h-texte' $javascript><div>$texte</div></a>\n";
		}
		if ($danger) $retour .= "</div>";
	} else {
		$retour = "<li><a href='$lien'>$texte</li>";
	}

	if ($echo) echo $retour;
	return $retour;
}


function bandeau_barre_verticale(){
	echo "<td class='separateur'></td>\n";
}


// lien changement de couleur
function lien_change_var($lien, $set, $couleur, $coords, $titre, $mouseOver="") {
	$lien->addVar($set, $couleur);
	return "\n<area shape='rect' href='". $lien->getUrl() ."' coords='$coords' title=\"$titre\" $mouseOver>";
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

function debut_page($titre = "", $rubrique = "asuivre", $sous_rubrique = "asuivre", $onLoad = "", $css="") {

	init_entete($titre, $rubrique, $css);
	definir_barre_boutons();
	init_body($rubrique, $sous_rubrique, $onLoad);
	debut_corps_page();
}
 
function init_entete($titre, $rubrique, $css='') {

	if (!$nom_site_spip =
	entites_html(textebrut(typo($GLOBALS['meta']["nom_site"]))))
		$nom_site_spip=  _T('info_mon_site_spip');

	// envoi des en-tetes, du doctype et du <head><title...
	include_ecrire('inc_headers');
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
function init_body($rubrique='asuivre', $sous_rubrique='asuivre', $onLoad='') {
	global $couleur_foncee, $couleur_claire, $adresse_site;
	global $connect_id_auteur;
	global $connect_statut;
	global $connect_toutes_rubriques;
	global $auth_can_disconnect, $connect_login;
	global $options, $spip_display, $spip_ecran;
	global $spip_lang, $spip_lang_rtl, $spip_lang_left, $spip_lang_right;
	global $browser_verifForm;

	echo "<body ". _ATTRIBUTES_BODY
		. 'onLoad="'
		. "setActiveStyleSheet('invisible');$browser_verifForm$onLoad"
		. '">';

	if ($spip_ecran == "large") $largeur = 974;
	else $largeur = 750;

	$link = new Link();
	echo "\n<map name='map_layout'>";
	echo lien_change_var ($link, 'set_disp', 1, '1,0,18,15', _T('lien_afficher_texte_seul'), "onMouseOver=\"changestyle('bandeauvide','visibility', 'visible');\"");
	echo lien_change_var ($link, 'set_disp', 2, '19,0,40,15', _T('lien_afficher_texte_icones'), "onMouseOver=\"changestyle('bandeauvide','visibility', 'visible');\"");
	echo lien_change_var ($link, 'set_disp', 3, '41,0,59,15', _T('lien_afficher_icones_seuls'), "onMouseOver=\"changestyle('bandeauvide','visibility', 'visible');\"");
	echo "\n</map>";



	if ($spip_display == "4") {
		// Icones principales
		echo "<ul>";
		echo "<li><a href='./'>"._T('icone_a_suivre')."</a>";
		echo "<li><a href='" . generer_url_ecrire("naviguer") . "'>"._T('icone_edition_site')."</a>";
		echo "<li><a href='" . generer_url_ecrire("forum_admin"). "'>"._T('titre_forum')."</a>";
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

	$decal=0;

	foreach($GLOBALS['boutons_admin'] as $page => $detail) {
		if ($rubrique == $page) {
			$class = "visible_au_chargement";
		} else {
			$class = "invisible_au_chargement";
		}

		$sousmenu= $detail->sousmenu;
		if($sousmenu) {
			echo "<div class='$class' id='bandeau$page' style='position: absolute; $spip_lang_left: ".$decal."px;'><div class='bandeau_sec'><table class='gauche'><tr>\n";
		
			foreach($sousmenu as $souspage => $sousdetail) {
				if($souspage=='espacement') {
					echo "<td class='separateur'></td>\n";
				} else {
					icone_bandeau_secondaire (_T($sousdetail->libelle), generer_url_ecrire($sousdetail->url?$sousdetail->url:$souspage, $sousdetail->urlArg), $sousdetail->icone, $souspage, $sous_rubrique);
				}
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
	
	echo "</div>\n";

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
//		  http_img_pack("tout-site.png", "", "width='26' height='20' border='0'") . "</a>";

		$id_rubrique = $GLOBALS['id_rubrique'];
		echo "<a href='" . generer_url_ecrire("articles_tous") . "' class='icone26' onMouseOver=\"changestyle('bandeautoutsite','visibility','visible'); charger_id_url_si_vide('" . generer_url_ecrire("ajax_page", "fonction=aff_nav_recherche&id=$id_rubrique", true) . "','nav-recherche');\">",
		  http_img_pack("tout-site.png", "", "width='26' height='20' border='0'") . "</a>";
		if ($id_rubrique > 0) echo "<a href='" . generer_url_ecrire("brouteur","id_rubrique=$id_rubrique") . "' class='icone26' onMouseOver=\"changestyle('bandeaunavrapide','visibility','visible');\">" .
		  http_img_pack("naviguer-site.png", "", "width='26' height='20' border='0'") ."</a>";
		else echo "<a href='" . generer_url_ecrire("brouteur") . "' class='icone26' onMouseOver=\"changestyle('bandeaunavrapide','visibility','visible');\" >" .
		  http_img_pack("naviguer-site.png", "", "width='26' height='20' border='0'") . "</a>";

		echo "<a href='" . generer_url_ecrire("recherche") . "' class='icone26' onMouseOver=\"changestyle('bandeaurecherche','visibility','visible'); findObj('form_recherche').focus();\" >" .
		  http_img_pack("loupe.png", "", "width='26' height='20' border='0'") ."</a>";

		echo http_img_pack("rien.gif", " ", "width='10'");

		echo "<a href='" . generer_url_ecrire("calendrier","type=semaine") . "' class='icone26' onMouseOver=\"changestyle('bandeauagenda','visibility','visible');\">" .
		  http_img_pack("cal-rv.png", "", "width='26' height='20' border='0'") ."</a>";
		echo "<a href='" . generer_url_ecrire("messagerie") . "' class='icone26' onMouseOver=\"changestyle('bandeaumessagerie','visibility','visible');\">" .
		  http_img_pack("cal-messagerie.png", "", "width='26' height='20' border='0'") ."</a>";
		echo "<a href='" . generer_url_ecrire("synchro") . "' class='icone26' onMouseOver=\"changestyle('bandeausynchro','visibility','visible');\">" .
		  http_img_pack("cal-suivi.png", "", "width='26' height='20' border='0'") . "</a>";
		

		if (!($connect_statut == "0minirezo" AND $connect_toutes_rubriques)) {
			echo http_img_pack("rien.gif", " ", "width='10'");
			echo "<a href='" . generer_url_ecrire("auteurs_edit","id_auteur=$connect_id_auteur") . "' class='icone26' onMouseOver=\"changestyle('bandeauinfoperso','visibility','visible');\">" .
			  http_img_pack("fiche-perso.png", "", "border='0' onMouseOver=\"changestyle('bandeauvide','visibility', 'visible');\"");
			echo "</a>";
		}
		
	echo "</td>";
	echo "<td valign='middle' class='bandeau_couleur' style='text-align: $spip_lang_left;'>";
		// overflow pour masquer les noms tres longs (et eviter debords, notamment en ecran etroit)
		if ($spip_ecran == "large") $largeur_nom = 300;
		else $largeur_nom= 110;
		echo "<div style='width: ".$largeur_nom."px; height: 14px; overflow: hidden;'>";
		// Redacteur connecte
		echo typo($GLOBALS["connect_nom"]);
		echo "</div>";
	
	echo "</td>";

	echo "<td> &nbsp; </td>";


	echo "<td class='bandeau_couleur' style='text-align: $spip_lang_right;' valign='middle'>";

			// Choix display
		//	echo"<img src=_DIR_IMG_PACK . 'rien.gif' width='10' />";
			if ($options != "avancees") {
				$lien = new Link;
				$lien->addVar('set_options', 'avancees');
				$simple = "<b>"._T('icone_interface_simple')."</b>/<a href='".$lien->getUrl()."' class='lien_sous'>"._T('icone_interface_complet')."</a>";
				$icone = "interface-display-comp.png";
			} else {
				$lien = new Link;
				$lien->addVar('set_options', 'basiques');
				$simple = "<a href='".$lien->getUrl()."' class='lien_sous'>"._T('icone_interface_simple')."</a>/<b>"._T('icone_interface_complet')."</b>";
				$icone = "interface-display.png";
			}
			echo "<a href='". $lien->getUrl() ."' class='icone26' onMouseOver=\"changestyle('bandeaudisplay','visibility', 'visible');\">" .
			  http_img_pack("$icone", "", "width='26' height='20' border='0'")."</a>";

			echo http_img_pack("rien.gif", " ", "width='10' height='1'");
			echo http_img_pack("choix-layout$spip_lang_rtl".($spip_lang=='he'?'_he':'').".png", "abc", "class='format_png' valign='middle' width='59' height='15' usemap='#map_layout' border='0'");


			echo http_img_pack("rien.gif", " ", "width='10' height='1'");
			// grand ecran
			$lien = new Link;
			if ($spip_ecran == "large") {
				$lien->addVar('set_ecran', 'etroit');
				$i = _T('info_petit_ecran');
				echo "<a href='". $lien->getUrl() ."' class='icone26' onMouseOver=\"changestyle('bandeauecran','visibility', 'visible');\" title=\"$i\">" .
				  http_img_pack("set-ecran-etroit.png", $i, "width='26' height='20' border='0'") . "</a>";
				$ecran = "<div><a href='".$lien->getUrl()."' class='lien_sous'>"._T('info_petit_ecran')."</a>/<b>"._T('info_grand_ecran')."</b></div>";
			}
			else {
				$lien->addVar('set_ecran', 'large');
				$i = _T('info_grand_ecran');
				echo "<a href='". $lien->getUrl() ."' class='icone26' onMouseOver=\"changestyle('bandeauecran','visibility', 'visible');\" title=\"$i\">" .
				  http_img_pack("set-ecran.png", $i, "width='26' height='20' border='0'") ."</a>";
				$ecran = "<div><b>"._T('info_petit_ecran')."</b>/<a href='".$lien->getUrl()."' class='lien_sous'>"._T('info_grand_ecran')."</a></div>";
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
				echo "<a href='" . generer_url_public("spip_cookie","logout=$connect_login") . "' class='icone26' onMouseOver=\"changestyle('bandeaudeconnecter','visibility', 'visible');\">" .
				  http_img_pack("deconnecter-24.gif", "", "border='0'") . "</a>";
			}
		echo "</td>";
	
	
	echo "</tr></table>";

} // fin bandeau colore

	//
	// Barre des gadgets
	// (elements invisibles qui s'ouvrent sous la barre precedente)
	//

// debut des gadgets
if (true /*$gadgets*/) {

	echo "<table width='$largeur' cellpadding='0' cellspacing='0' align='center'><tr><td>";


	// GADGET Menu rubriques
	echo "<div style='position: relative; z-index: 1000;'>";
	echo "<div id='bandeautoutsite' class='bandeau_couleur_sous' style='$spip_lang_left: 0px;'>";
	echo "<a href='" . generer_url_ecrire("articles_tous") . "' class='lien_sous'>"._T('icone_site_entier')."</a>";
	echo "<img src='"._DIR_IMG_PACK."searching.gif' id='img_nav-recherche' style='border:0px; visibility: hidden' />";
	afficher_menu_rubriques();

//	echo "<div id='nav-recherche' style='width: 450px; visibility: hidden;'></div>";
	echo "</div>";
	// FIN GADGET Menu rubriques
	
	
	
	
	// GADGET Navigation rapide
	echo "<div id='bandeaunavrapide' class='bandeau_couleur_sous' style='$spip_lang_left: 30px; width: 300px;'>";

	if ($id_rubrique > 0) echo "<a href='" . generer_url_ecrire("brouteur","id_rubrique=$id_rubrique") . "' class='lien_sous'>";
	else echo "<a href='" . generer_url_ecrire("brouteur") . "' class='lien_sous'>";
	echo _T('icone_brouteur');
	echo "</a>";

	$gadget = '';
		$vos_articles = spip_query("SELECT articles.id_article, articles.titre, articles.statut FROM spip_articles AS articles, spip_auteurs_articles AS lien WHERE articles.id_article=lien.id_article ".
			"AND lien.id_auteur=$connect_id_auteur AND articles.statut='prepa' ORDER BY articles.date DESC LIMIT 5");
		if (spip_num_rows($vos_articles) > 0) {
			$gadget .= "<div>&nbsp;</div>";
			$gadget .= "<div class='bandeau_rubriques' style='z-index: 1;'>";
			$gadget .= bandeau_titre_boite2(afficher_plus(generer_url_ecrire("articles_page",""))._T('info_en_cours_validation'), "article-24.gif", '', '', false);
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
	
		$vos_articles = spip_query("SELECT articles.id_article, articles.titre, articles.statut FROM spip_articles AS articles WHERE articles.statut='prop' ".
			" ORDER BY articles.date DESC LIMIT 5");
		if (spip_num_rows($vos_articles) > 0) {
			$gadget .= "<div>&nbsp;</div>";
			$gadget .= "<div class='bandeau_rubriques' style='z-index: 1;'>";
			$gadget .= bandeau_titre_boite2(afficher_plus('./')._T('info_articles_proposes'), "article-24.gif", '', '', false);
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
			
		$vos_articles = spip_query("SELECT * FROM spip_breves WHERE statut='prop' ".
			" ORDER BY date_heure DESC LIMIT 5");
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


		$query = "SELECT id_rubrique FROM spip_rubriques LIMIT 1";
		$result = spip_query($query);
		
		if (spip_num_rows($result) > 0) {
			$gadget .= "<div>&nbsp;</div>";
			$id_rubrique = $GLOBALS['id_rubrique'];
			if ($id_rubrique > 0) {
				$dans_rub = "&id_rubrique=$id_rubrique";
				$dans_parent = "&id_parent=$id_rubrique";
			}
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
			
			$activer_breves = $GLOBALS['meta']["activer_breves"];
			if ($activer_breves != "non") {
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

		$gadget .= "</div>";

	echo afficher_javascript($gadget);
	// FIN GADGET Navigation rapide


	// GADGET Recherche
	echo "<div id='bandeaurecherche' class='bandeau_couleur_sous' style='width: 146px; $spip_lang_left: 60px;'>";
	echo "<form method='get' style='margin: 0px; position: relative;' action='" . generer_url_ecrire("recherche") . "'>";
	echo "<input type='hidden' name='exec' value='recherche' />";
	echo "<input type=\"search\" id=\"form_recherche\" style=\"width: 140px;\" size=\"10\" value=\""._T('info_rechercher')."\" name=\"recherche\" onkeypress=\"t=window.setTimeout('lancer_recherche(\'form_recherche\',\'resultats_recherche\')', 200);\" autocomplete=\"off\" class=\"formo\" accesskey=\"r\" ".$onfocus.">";
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
	
		// Taches (ne calculer que la valeur booleenne...)
		if (spip_num_rows(spip_query("SELECT type FROM spip_messages AS messages WHERE id_auteur=$connect_id_auteur AND statut='publie' AND type='pb' AND rv!='oui' LIMIT 1")) OR
		    spip_num_rows(spip_query("SELECT type FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE ((lien.id_auteur='$connect_id_auteur' AND lien.id_message=messages.id_message) OR messages.type='affich') AND messages.rv='oui' AND messages.date_heure > DATE_SUB(NOW(), INTERVAL 1 DAY) AND messages.date_heure < DATE_ADD(NOW(), INTERVAL 1 MONTH) AND messages.statut='publie' GROUP BY messages.id_message ORDER BY messages.date_heure LIMIT 1"))) {
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
		echo "<a href='" . generer_url_public("spip_cookie","logout=$connect_login") . "' class='lien_sous'>"._T('icone_deconnecter')."</a>".aide("deconnect");
		echo "</div>";
	
		$decal = 0;
		$decal = $decal + 150;

		echo "<div id='bandeauinterface' class='bandeau_couleur_sous' style='$spip_lang_right: ".$decal."px; text-align: $spip_lang_right;'>";
			echo _T('titre_changer_couleur_interface');
		echo "</div>";
		
		$decal = $decal + 70;
		
		echo "<div id='bandeauecran' class='bandeau_couleur_sous' style='width: 200px; $spip_lang_right: ".$decal."px; text-align: $spip_lang_right;'>";
			echo $ecran;
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

} // fin des gadgets

	echo "</div>";
	echo "</div>";

	if ($options != "avancees") echo "<div style='height: 18px;'>&nbsp;</div>";
	
}

function debut_corps_page() {
	global $couleur_foncee;
	global $connect_id_auteur;
  
	// Ouverture de la partie "principale" de la page
	// Petite verif pour ne pas fermer le formulaire de recherche pendant qu'on l'edite	
	echo "<center onMouseOver=\"if (findObj('bandeaurecherche') && findObj('bandeaurecherche').style.visibility == 'visible') { ouvrir_recherche = true; } else { ouvrir_recherche = false; } changestyle('bandeauvide', 'visibility', 'hidden'); if (ouvrir_recherche == true) { changestyle('bandeaurecherche','visibility','visible'); }\">";

			$result_messages = spip_query("SELECT * FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE lien.id_auteur=$connect_id_auteur AND vu='non' AND statut='publie' AND type='normal' AND lien.id_message=messages.id_message");
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
	global $connect_activer_imessage;

	if ($changer_config!="oui"){
		$activer_imessage = "oui";
	}
	
			if ($activer_imessage != "non" AND ($connect_activer_imessage != "non" OR $connect_statut == "0minirezo")) {
				$query2 = "SELECT id_auteur, nom FROM spip_auteurs WHERE id_auteur!=$connect_id_auteur AND imessage!='non' AND en_ligne>DATE_SUB(NOW(),INTERVAL 15 MINUTE)";
				$result_auteurs = spip_query($query2);
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


function gros_titre($titre, $ze_logo=''){
	global $couleur_foncee, $spip_display;
	if ($spip_display == 4) {
		echo "\n<h1>".typo($titre)."</h1>&nbsp;\n";
	}
	else {
		echo "<div class='verdana2' style='font-size: 18px; color: $couleur_foncee; font-weight: bold;'>";
		if (strlen($ze_logo) > 3) echo http_img_pack("$ze_logo", "", "border=0 align='middle'") . " &nbsp; ";
		echo typo($titre);
		echo "</div>\n";
	}
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
	}

	echo "<br><table width='$largeur_ecran' cellpadding=0 cellspacing=0 border=0>
		<tr><td width='$largeur' class='colonne_etroite' valign='top' class='serif' $rspan>
		<div style='width: ${largeur}px; overflow:hidden;'>
\n";
		
	if ($spip_display == 4) echo "<!-- ";

}


//
// Presentation de l''interface privee, marge de droite
//

function creer_colonne_droite($rubrique=""){
	global $deja_colonne_droite;
	global $changer_config;
	global $activer_imessage;
	global $connect_activer_imessage;
	global $connect_statut;
	global $options;
	global $connect_id_auteur, $spip_ecran;
	global $flag_3_colonnes, $flag_centre_large;
	global $spip_lang_rtl, $lang_left;

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
		echo "<td width=$largeur rowspan=2 align='$lang_left' valign='top' class='colonne_etroite'><p />";

	}

}

function debut_droite($rubrique="") {
	global $options, $spip_ecran, $deja_colonne_droite, $spip_display;
	global $connect_id_auteur, $connect_statut, $connect_toutes_rubriques;
	global $flag_3_colonnes, $flag_centre_large, $couleur_foncee, $couleur_claire;
	global $lang_left;

	if ($spip_display == 4) echo " -->";

	echo "</div>\n"; # largeur fixe, cf. debut_gauche

	if ($options == "avancees") {
		// liste des articles bloques
		if ($GLOBALS['meta']["articles_modif"] != "non") {
			$query = "SELECT id_article, titre FROM spip_articles WHERE auteur_modif = '$connect_id_auteur' AND date_modif > DATE_SUB(NOW(), INTERVAL 1 HOUR) ORDER BY date_modif DESC";
			$result = spip_query($query);
			$num_articles_ouverts = spip_num_rows($result);
			if ($num_articles_ouverts) {
				echo "<p>";
				debut_cadre_enfonce('article-24.gif');
				//echo "<font face='Verdana,Arial,Sans,sans-serif' size='2'>";
				echo "<div class='verdana2' style='padding: 2px; background-color:$couleur_foncee; color: white; font-weight: bold;'>";
					echo _T('info_cours_edition')."&nbsp;:".aide('artmodif');
				echo "</div>";
				while ($row = @spip_fetch_array($result)) {
					$ze_article = $row['id_article'];
					$ze_titre = typo($row['titre']);


					if ($ifond == 1) {
						$couleur = $couleur_claire;
						$ifond = 0;
					} else {
						$couleur = "#eeeeee";
						$ifond = 1;
					}
					
					echo "<div style='padding: 3px; background-color: $couleur;'>";
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
			}
		}
		
		if (!$deja_colonne_droite) creer_colonne_droite($rubrique);
	}

	echo "<div>&nbsp;</div></td>";

	if (!$flag_3_colonnes) {
		echo "<td width=50>&nbsp;</td>";
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

	echo '<td width="'.$largeur.'" valign="top" align="'.$lang_left.'" rowspan="1" class="serif">';

	// touche d'acces rapide au debut du contenu
	echo "\n<a name='saut' href='#saut' accesskey='s'></a>\n";
}


//
// Presentation de l'interface privee, fin de page et flush()
//

function fin_html() {

	echo "</font>";

	// rejouer le cookie de session si l'IP a change
	if ($GLOBALS['spip_session'] && $GLOBALS['auteur_session']['ip_change']) {
		echo 
		  http_img_pack('rien.gif', " ", "name='img_session' width='0' height='0'"),
		  http_script("\ndocument.img_session.src='" . generer_url_public('spip_cookie','change_session=oui') . "'");
	}

	echo "</body></html>\n";

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

function debloquer_article($arg, $texte)
{
	$lien = new Link;
	$lien->addVar('debloquer_article', $arg);
	$lien = (_DIR_RESTREINT_ABS . $lien->getUrl());
	return "<a href='" . generer_action_auteur('instituer', "collaboration $arg", $lien) .
	  "' title='" .
	  addslashes($texte) .
	  "'>$texte&nbsp;" .
	  http_img_pack("croix-rouge.gif", ($arg=='tous' ? "" : "X"),
			"width='7' height='7' border='0' align='middle'") .
	  "</a>";
}

//
// Afficher la hierarchie des rubriques
//

function afficher_hierarchie($id_rubrique, $parents="") {
	global $couleur_foncee, $spip_lang_left, $lang_dir;

	if ($id_rubrique) {
		$query = "SELECT id_rubrique, id_parent, titre, lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique";
		$result = spip_query($query);

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

	switch ($type) {
		case 'article':
			if ($statut == "publie" AND $GLOBALS['meta']["post_dates"] == 'non'
			AND !spip_fetch_array(spip_query("SELECT id_article
			FROM spip_articles WHERE id_article=$id AND date<=NOW()")))
				$statut = 'prop';
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


function http_style_background($img, $att='')
{
  return " style='background: url(\"" . _DIR_IMG_PACK . $img .  '")' .
    ($att ? (' ' . $att) : '') . ";'";
}


//
// Creer un bouton qui renvoie vers la bonne url spip_rss
function bouton_spip_rss($op, $args, $fmt='rss') {

	include_ecrire('inc_acces');
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
		case 'rss':
		default:
		  
			$button = 'RSS';
			break;
	}

	return "<a href='"
	. $url
	. "'>"
	. http_img_pack('feed.png', $button, ' border="0" ')
	. "</a>";
}


?>
