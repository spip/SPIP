<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_PRESENTATION")) return;
define("_ECRIRE_INC_PRESENTATION", "1");

include_ecrire("inc_filtres.php3"); # pour les fonctions http_* (normalement deja la)
include_ecrire ("inc_lang.php3");
utiliser_langue_visiteur();

//
// Aide
//

// en hebreu le ? ne doit pas etre inverse
function aide_lang_dir($spip_lang,$spip_lang_rtl) {
	return ($spip_lang<>'he') ? $spip_lang_rtl : '';
}

function aide($aide='') {
	global $couleur_foncee, $spip_lang, $spip_lang_rtl, $spip_display;

	if (!$aide OR $spip_display == 4) return;

	return "&nbsp;&nbsp;<a class='aide' href=\"". _DIR_RESTREINT
		. "aide_index.php3?aide=$aide&amp;"
		. "var_lang=$spip_lang\" target=\"spip_aide\" "
		. "onclick=\"javascript:window.open(this.href,"
		. "'spip_aide', 'scrollbars=yes, resizable=yes, width=740, "
		. "height=580'); return false;\">"
		. http_img_pack("aide".aide_lang_dir($spip_lang,$spip_lang_rtl).".gif",
			_T('info_image_aide'), "title=\""._T('titre_image_aide')
			. "\" width=\"12\" height=\"12\" border=\"0\" align=\"middle\"")
		. "</a>";
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

	$url = new Link("message_edit.php3");

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
	$url->addVar('dest',$destinataire);
	$url->addVar('new','oui');
	$url->addVar('type','normal');

	if ($destinataire) $title = _T('info_envoyer_message_prive');
	else $title = _T('info_ecire_message_prive');

	$texte_bouton = http_img_pack("m_envoi$spip_lang_rtl.gif", "m&gt;", "width='14' height='7' border='0'", $title);
		
	
	$ret .= "<a href='". $url->getUrl() ."' title=\"$title\">";
	$ret .= "$texte_bouton</a>";

	return $ret;
	

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

function afficher_tranches_requete(&$query, $colspan) {
	static $ancre = 0;
	global $spip_lang_right, $spip_display;

	$query = trim($query);
	$query_count = eregi_replace('^(SELECT)[[:space:]].*[[:space:]](FROM)[[:space:]]', '\\1 COUNT(*) \\2 ', $query);
	$query_count = eregi_replace('ORDER[[:space:]]+BY.*$', '', $query_count);

	list($num_rows) = spip_fetch_array(spip_query($query_count));
	
	if (!$num_rows) return;

	$nb_aff = 10;
	// Ne pas couper pour trop peu
	if ($num_rows <= 1.5 * $nb_aff) $nb_aff = $num_rows;
	if (ereg('LIMIT .*,([0-9]+)', $query, $regs)) {
		if ($num_rows > $regs[1]) $num_rows = $regs[1];
	}

	$texte = "\n";

	if ($num_rows > $nb_aff) {
		$tmp_var = 't_'.substr(md5($query), 0, 4);
		$deb_aff = intval($GLOBALS[$tmp_var]);
		$ancre++;

		$texte .= "<a name='a$ancre'></a>";
		if ($spip_display != 4) $texte .= "<tr style='background-color: #dddddd;'><td class=\"arial2\" style='border-bottom: 1px solid #444444;' colspan=\"".($colspan - 1)."\">";

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
				$texte .= "<a href=\"".$link->getUrl()."#a$ancre\">$deb</a>";
			}
		}
		if ($spip_display != 4) {
			$texte .= "</td>\n";
			$texte .= "<td class=\"arial2\" style='border-bottom: 1px solid #444444; text-align: $spip_lang_right;' colspan=\"1\" align=\"right\" valign=\"top\">";
		} else {
			$texte .= " | ";
		}
		
		if ($deb_aff == -1) {
			$texte .= "<B>"._T('info_tout_afficher')."</B>";
		} else {
			$link = new Link;
			$link->addVar($tmp_var, -1);
			$texte .= "<A HREF=\"".$link->getUrl()."#a$ancre\">"._T('lien_tout_afficher')."</A>";
		}

		if ($spip_display != 4) $texte .= "</td>\n";
		if ($spip_display != 4) $texte .= "</tr>\n";


		if ($deb_aff != -1) {
			$query = eregi_replace('LIMIT[[:space:]].*$', '', $query);
			$query .= " LIMIT $deb_aff, $nb_aff";
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
	  $inser_puce = "<div class='puce_article' id='statut$id'$dir_lang>"
			. "<div class='puce_article_fixe' $action>" .
		  http_img_pack("$puce", "", "id='imgstatutarticle$id' border='0' style='margin: 1px;'") ."</div>"
			. "<div class='puce_article_popup' id='statutdecalarticle$id' onmouseout=\"cacher('statutdecalarticle$id');\" style=' margin-left: -".((11*$clip)+1)."px;'>"
		  . http_href_img("javascript:selec_statut($id, 'article', -1,'" . _DIR_IMG_PACK . "puce-blanche.gif', 'prepa');",
				  "puce-blanche.gif", 
				  "title=\"$titles[blanche]\"",
				  "",'','',
				  $action)
		  . http_href_img("javascript:selec_statut($id, 'article', -12,'" . _DIR_IMG_PACK . "puce-orange.gif', 'prop');",
				  "puce-orange.gif", 
				  "title=\"$titles[orange]\"",
				  "",'','',
				  $action)
		  . http_href_img("javascript:selec_statut($id, 'article', -23,'" . _DIR_IMG_PACK . "puce-verte.gif', 'publie');",
				  "puce-verte.gif", 
				  "title=\"$titles[verte]\"",
				  "",'','',
				  $action)
		  . http_href_img("javascript:selec_statut($id, 'article', -34,'" . _DIR_IMG_PACK . "puce-rouge.gif', 'refuse');",
				  "puce-rouge.gif", 
				  "title=\"$titles[rouge]\"",
				  "",'','',
				  $action)
		  . http_href_img("javascript:selec_statut($id, 'article', -45,'" . _DIR_IMG_PACK . "puce-poubelle.gif', 'poubelle');",
				  "puce-poubelle.gif", 
				  "title=\"$titles[poubelle]\"",
				  "",'','',
				  $action)
			. "</div></div>";
	} else {
		$inser_puce = http_img_pack("$puce", "", "id='imgstatutarticle$id' border='0' style='margin: 1px;'");
	}
	return $inser_puce;
}

function puce_statut_breve($id, $statut, $type, $id_rubrique=0) {
	global $spip_lang_left, $dir_lang, $connect_statut, $options;

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

	if (!($connect_statut == '0minirezo' AND $options == 'avancees' AND acces_rubrique($id_rubrique)))
		return $inser_puce;
	
	$type2 = "statutdecal$type$id";
	$action = "onmouseover=\"montrer('$type2');\"\n";

	  // les versions de MSIE ne font pas toutes pareil sur alt/title
	  // la combinaison suivante semble ok pour tout le monde.

	return	"<div class='puce_breve' id='$type1'$dir_lang>"
		. "<div class='puce_breve_fixe' $action>"
		. $inser_puce
		. "</div>"
		. "<div class='puce_breve_popup' id='$type2' onmouseout=\"cacher('$type2');\" style=' margin-left: -".((9*$clip)+1)."px;'>"
		. http_href_img("javascript:selec_statut($id, '$type', -1, '" . _DIR_IMG_PACK . $puces[0] . "', 'prop');",
			$puces[0],
			"title=\""._T('texte_statut_propose_evaluation')."\"",
			'','','',
			$action)
		. http_href_img("javascript:selec_statut($id, '$type', -10, '" . _DIR_IMG_PACK .$puces[1] . "', 'publie');",
		 	$puces[1],
			"title=\""._T('texte_statut_publie')."\"",
			'','','',
			$action)
		. http_href_img("javascript:selec_statut($id, '$type', -19, '" . _DIR_IMG_PACK .$puces[2] . "', 'refuse');",
			$puces[2],
			"title=\""._T('texte_statut_refuse')."\"",
			'','','',
			$action)
		.  "</div></div>";
}


//
// Afficher tableau d'articles
//
function afficher_articles($titre_table, $requete, $afficher_visites = false, $afficher_auteurs = true,
		$toujours_afficher = false, $afficher_cadre = true, $afficher_descriptif = true) {

	global $connect_id_auteur, $connect_statut, $dir_lang;
	global $options, $spip_display;
	global $spip_lang_left, $spip_lang_right;


	$activer_messagerie = "oui";
	$activer_statistiques = lire_meta("activer_statistiques");
	$afficher_visites = ($afficher_visites AND $connect_statut == "0minirezo" AND $activer_statistiques != "non");

	// Preciser la requete (alleger les requetes)
	if (!ereg("^SELECT", $requete)) {
		$select = "SELECT articles.id_article, articles.titre, articles.id_rubrique, articles.statut, articles.date";

		if ((lire_meta('multi_rubriques') == 'oui' AND $GLOBALS['id_rubrique'] == 0) OR lire_meta('multi_articles') == 'oui') {
			$afficher_langue = true;
			if ($GLOBALS['langue_rubrique']) $langue_defaut = $GLOBALS['langue_rubrique'];
			else $langue_defaut = lire_meta('langue_site');
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

	$tranches = afficher_tranches_requete($requete, $afficher_auteurs ? 4 + $ajout_col : 3 + $ajout_col);

	$requete = str_replace("FROM spip_articles AS articles ", "FROM spip_articles AS articles LEFT JOIN spip_petitions AS petitions USING (id_article)", $requete);

	if (strlen($tranches) OR $toujours_afficher) {
		$result = spip_query($requete);

		// if ($afficher_cadre) debut_cadre_gris_clair("article-24.gif");


		echo "<div style='height: 12px;'></div>";
		echo "<div class='liste'>";
		bandeau_titre_boite2($titre_table, "article-24.gif");

		//echo "<table width='100%' cellpadding='2' cellspacing='0' border='0'>";
		echo afficher_liste_debut_tableau();

		echo $tranches;

		while ($row = spip_fetch_array($result)) {
			$vals = '';

			$id_article = $row['id_article'];
			$tous_id[] = $id_article;
			$titre = $row['titre'];
			$id_rubrique = $row['id_rubrique'];
			$date = $row['date'];
			$statut = $row['statut'];
			$visites = $row['visites'];
			if ($lang = $row['lang']) changer_typo($lang);
			$popularite = ceil(min(100,100 * $row['popularite'] / max(1, 0 + lire_meta('popularite_max'))));
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


					$les_auteurs .= ", <a href='auteurs_edit.php3?id_auteur=$id_auteur'$bio>$nom_auteur</a>";
					if ($id_auteur != $connect_id_auteur AND $auteur_messagerie != "non") {
						$les_auteurs .= "&nbsp;".bouton_imessage($id_auteur, $row);
					}
				}
				$les_auteurs = substr($les_auteurs, 2);
			}
			
			
			$les_auteurs = "$les_auteurs";

			// La petite puce de changement de statut
			$vals[] = puce_statut_article($id_article, $statut, $id_rubrique);

			// Le titre (et la langue)
			$s = "<div>";

			if (acces_restreint_rubrique($id_rubrique))
				$s .= http_img_pack("admin-12.gif", "", "width='12' height='12'", _T('titre_image_admin_article'));

			$s .= "<a href=\"articles.php3?id_article=$id_article\"$descriptif$dir_lang style=\"display:block;\">";

			if ($spip_display != 1 AND $spip_display != 4 AND lire_meta('image_process') != "non") {
				include_ecrire("inc_logos.php3");
				$logo = decrire_logo("arton$id_article");
				if ($logo) {	
					$fichier = $logo[0];
					$s .= "<div style='float: $spip_lang_right; margin-top: -2px; margin-bottom: -2px;'>";
					$s .= reduire_image_logo(_DIR_IMG.$fichier, 26, 20);
					$s .= "</div>";
				}
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
				$largeurs = array(11, '', 100, 35);
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
		//if ($afficher_cadre) fin_cadre_gris_clair();

	}

	return $tous_id;
}



//
// Afficher tableau de breves
//

function afficher_breves($titre_table, $requete, $affrub=false) {
	global $connect_id_auteur, $spip_lang_right, $spip_lang_left, $dir_lang, $couleur_claire, $couleur_foncee;
	global $options;
	


	if ((lire_meta('multi_rubriques') == 'oui' AND $GLOBALS['id_rubrique'] == 0) OR lire_meta('multi_articles') == 'oui') {
		$afficher_langue = true;
		$requete = ereg_replace(" FROM", ", lang FROM", $requete);
		if ($GLOBALS['langue_rubrique']) $langue_defaut = $GLOBALS['langue_rubrique'];
		else $langue_defaut = lire_meta('langue_site');
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
		while ($row = spip_fetch_array($result)) {
			$vals = '';

			$id_breve = $row['id_breve'];
			$tous_id[] = $id_breve;
			$date_heure = $row['date_heure'];
			$titre = $row['titre'];
			$statut = $row['statut'];
			if ($lang = $row['lang']) changer_typo($lang);
			$id_rubrique = $row['id_rubrique'];
			
			$vals[] = puce_statut_breve($id_breve, $statut, 'breve', $id_rubrique);

			$s = "<div>";
			$s .= "<a href='breves_voir.php3?id_breve=$id_breve'$dir_lang style=\"display:block;\">";

			if ($spip_display != 1 AND $spip_display != 4 AND lire_meta('image_process') != "non") {
				include_ecrire("inc_logos.php3");
				$logo = decrire_logo("breveon$id_breve");
				if ($logo) {
					$fichier = $logo[0];
					$s .= "<div style='float: $spip_lang_right; margin-top: -2px; margin-bottom: -2px;'>";
					$s .= reduire_image_logo(_DIR_IMG.$fichier, 26, 20);
					$s .= "</div>";
				}
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
				$s .= "<a href='naviguer.php3?id_rubrique=$id_rubrique' style=\"display:block;\">".typo($rub['titre'])."</a>";
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

	$tranches = afficher_tranches_requete($requete, 2);

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
			$titre = $row['titre'];
			
			if ($id_parent == 0) $puce = "secteur-12.gif";
			else $puce = "rubrique-12.gif";

			$s = "<b><a href=\"naviguer.php3?id_rubrique=$id_rubrique\">";
			$s .= http_img_pack($puce, '- ', "border='0'");
			$s .= typo($titre);
			$s .= "</A></b>";
			$vals[] = $s;

			$s = "<div align=\"right\">";
			$s .= "</div>";
			$vals[] = $s;
			$table[] = $vals;
		}
		spip_free_result($result);

		$largeurs = array('', '');
		$styles = array('arial2', 'arial2');
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
			$s .= "<a href=\"auteurs_edit.php3?id_auteur=$id_auteur\">";
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
			$titre = $row["titre"];
			$type = $row["type"];
			$statut = $row["statut"];
			$page = $row["page"];
			$rv = $row["rv"];
			$vu = $row["vu"];
			$messages_vus[$id_message] = $id_message;

			//
			// Titre
			//

			$s = "<A HREF='message.php3?id_message=$id_message' style='display: block;'>";

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
					$auteurs[] = "<a href='auteurs_edit.php3?id_auteur=$id_auteur'>".typo($row_auteurs['nom'])."</a>";
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
				  "><a href='calendrier.php3?type=jour&jour=$jour&mois=$mois&annee=$annee'><b style='color: black;'>$s</b><br />$heure-$heure_fin</a></div>";
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

function afficher_forum($request, $adresse_retour, $controle_id_article = 0) {
	global $debut;
	static $compteur_forum;
	static $nb_forum;
	static $i;
	global $couleur_foncee;
	global $connect_id_auteur, $connect_activer_messagerie;
	global $mots_cles_forums;
	global $spip_lang_rtl, $spip_lang_left, $spip_lang_right, $spip_display;

	$activer_messagerie = "oui";

	$compteur_forum++;

	$nb_forum[$compteur_forum] = spip_num_rows($request);
	$i[$compteur_forum] = 1;
	
	if ($spip_display == 4) echo "<ul>";
 
 	while($row = spip_fetch_array($request)) {
		$row = safehtml_sur_row($row);
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

			$titre_boite = $titre;
			if ($id_auteur AND $spip_display != 1 AND $spip_display!=4 AND lire_meta('image_process') != "non") {
				include_ecrire("inc_logos.php3");
				$logo_auteur = decrire_logo("auton$id_auteur");
				if ($logo_auteur) {
					$fichier = $logo_auteur[0];
	
					$s = "<div style='position: absolute; $spip_lang_right: 0px; margin: 0px; margin-top: -3px; margin-$spip_lang_right: 0px;'>";
					$s .= reduire_image_logo(_DIR_IMG.$fichier, 48, 48);
					$s .= "</div>";
					$titre_boite = $s.typo($titre_boite);
				}
			}
		
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
			
			echo "</span>";
			
			echo " <a href='auteurs_edit.php3?id_auteur=$id_auteur'>".typo($auteur)."</a>";

			if ($id_auteur AND $connect_activer_messagerie != "non") {
				$bouton = bouton_imessage($id_auteur,$row_auteur);
				if ($bouton) echo "&nbsp;".$bouton;
			}

			// boutons de moderation
			if ($controle_id_article)
				echo boutons_controle_forum($id_forum, $statut, $id_auteur, "id_article=$controle_id_article", $ip);

			echo justifier(propre($texte));

			if (strlen($url_site) > 10 AND $nom_site) {
				echo "<div align='left' class='verdana2'><b><a href='$url_site'>$nom_site</a></b></div>";
			}

			if (!$controle_id_article) {
				echo "<div align='right' class='verdana1'>";
				$url = "forum_envoi.php3?id_parent=$id_forum&adresse_retour=".rawurlencode($adresse_retour)
					."&titre_message=".rawurlencode($titre);
				echo "<b><a href=\"$url\">"._T('lien_repondre_message')."</a></b></div>";
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


//
// un bouton (en POST) a partir d'un URL en format GET
//
function bouton($titre,$lien) {
	$lapage=substr($lien,0,strpos($lien,"?"));
	$lesvars=substr($lien,strpos($lien,"?")+1,strlen($lien));

	echo "\n<form action='$lapage' method='get'>\n";
	$lesvars=explode("&",$lesvars);
	
	for($i=0;$i<count($lesvars);$i++){
		$var_loc=explode("=",$lesvars[$i]);
		echo "<input type='Hidden' name='$var_loc[0]' value=\"$var_loc[1]\">\n";
	}
	echo "<input type='submit' name='Submit' class='fondo' value=\"$titre\">\n";
	echo "</form>";
}

//
// Presentation de l'interface privee, debut du HTML
//

function debut_html($titre = "", $rubrique="", $onLoad="") {
	global $couleur_foncee, $couleur_claire, $couleur_lien, $couleur_lien_off;
	global $mode, $spip_lang_rtl, $spip_display;
	global $connect_statut, $connect_toutes_rubriques;
	global $browser_name, $browser_rev;

	// hack pour compatibilite spip-lab
	if (strpos($rubrique, 'script>')) {
		$code = $rubrique;
		$rubrique = '';
	}
	
	$nom_site_spip = entites_html(textebrut(typo(lire_meta("nom_site"))));
	$titre = textebrut(typo($titre));

	if (!$nom_site_spip) $nom_site_spip="SPIP";
	$adresse_site=lire_meta("adresse_site");

	@Header("Expires: 0");
	@Header("Cache-Control: no-cache,no-store");
	@Header("Pragma: no-cache");
	echo debut_entete("[$nom_site_spip] $titre");
	if ($spip_display != 4) {
		echo "<link rel='alternate' type='application/rss+xml' title='".addslashes($nom_site_spip)."' href='$adresse_site/backend.php3' />";
		$activer_breves=lire_meta("activer_breves");
		if ($activer_breves != "non")
			echo "<link rel='alternate' type='application/rss+xml' title='".addslashes($nom_site_spip)." ("._T("info_breves_03").")' href='$adresse_site/backend-breves.php3' />";
	}

	echo "\n",'<link rel="stylesheet" type="text/css" href="', _DIR_RESTREINT;
	$link = new Link('spip_style.php3');
	$link->addVar('couleur_claire', $couleur_claire);
	$link->addVar('couleur_foncee', $couleur_foncee);
	$link->addVar('left', $GLOBALS['spip_lang_left']);
	echo $link->getUrl()."\">\n";

	if ($code) echo $code."\n";

	// Supprime pour l'instant: pas de creation mathml
	// < script type="text/javascript" src="../mathmlinHTML.js"></script>
	// Supprime pour l'instant: pas de detection des plugin
	// < script type="text/javascript" src="js_detectplugins.js"></script>

	debut_javascript($connect_statut == "0minirezo" AND $connect_toutes_rubriques, (lire_meta("activer_statistiques") != 'non'));

	// CSS calendrier
	echo '<link rel="stylesheet" href="', _DIR_RESTREINT, 'calendrier.css" type="text/css">', "\n";

	// CSS imprimante (masque des trucs, a completer)
	echo '<link rel="stylesheet" href="', _DIR_RESTREINT, 'spip_style_print.css" type="text/css" media="print">', "\n";

	// CSS "visible au chargement", hack necessaire pour garder un depliement
	// sympathique meme sans javascript (on exagere ?)
	// Pour l'explication voir http://www.alistapart.com/articles/alternate/
	echo '<link rel="alternate stylesheet" href="', _DIR_RESTREINT, 'spip_style_invisible.css" type="text/css" title="invisible" />', "\n",
		'<link rel="stylesheet" href="', _DIR_RESTREINT, 'spip_style_visible.css" type="text/css" title="visible" />', "\n";
	$onLoadInvisible = " onLoad=\"setActiveStyleSheet('invisible'); ";

	// favicon.ico
	echo '<link rel="shortcut icon" href="', _DIR_IMG_PACK, 'favicon.ico" />';

	// Fin des entetes
	echo "\n</head>\n<body text='#000000' bgcolor='#f8f7f3' link='$couleur_lien' vlink='$couleur_lien_off' alink='$couleur_lien_off' topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' frameborder='0'";

	if ($spip_lang_rtl)
		echo " dir='rtl'";
	//if ($mode == "wysiwyg") echo " onLoad='debut_editor();'";
	echo $onLoadInvisible;

	// Hack pour forcer largeur des formo/forml sous Mozilla >= 1.7
	// meme principe que le behavior win_width.htc pour MSIE
	if (eregi("mozilla", $browser_name) AND $browser_rev >= 1.7)
		echo "verifForm();";
	echo "$onLoad\">";
}

function debut_javascript($admin, $stat)
{
	global $spip_lang_left;
	global $browser_name, $browser_version;

	// envoi le fichier JS de config si browser ok.
	echo $GLOBALS['browser_layer'];
?>
<script type='text/javascript'><!--
	var admin = <?php echo ($admin ? 1 : 0) ?>;
	var stat = <?php echo ($stat ? 1 : 0) ?>;
	var largeur_icone = <?php echo largeur_icone_bandeau_principal(_T('icone_a_suivre')); ?>;
	var  bug_offsetwidth = <?php 
// uniquement affichage ltr: bug Mozilla dans offsetWidth quand ecran inverse!
	  echo ((($spip_lang_left == "left") &&
		 (($browser_name != "MSIE") ||
		  ($browser_version >= 6))) ? 1 : 0) ?> ;

	var confirm_changer_statut = '<?php include_ecrire("inc_charsets.php3"); echo unicode_to_javascript(addslashes(html2unicode(_T("confirm_changer_statut")))); ?>';
//--></script>
<?php
	echo http_script('',_DIR_RESTREINT . 'presentation.js');
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


function barre_onglets($rubrique, $onglet){
	global $id_auteur, $connect_id_auteur, $connect_statut, $statut_auteur, $options;

	debut_onglet();

	if ($rubrique == "statistiques") {
	//	onglet(_T('onglet_evolution_visite_mod'), "statistiques_visites.php3", "evolution", $onglet, "statistiques-24.gif");
	//	onglet(_T('titre_liens_entrants'), "statistiques_referers.php3", "referers", $onglet, "referers-24.gif");
	}
	if ($rubrique == "repartition") {
		if (lire_meta('multi_articles') == 'oui' OR lire_meta('multi_rubriques') == 'oui') {
			onglet(_T('onglet_repartition_rubrique'), "statistiques.php3", "rubriques", $onglet, "rubrique-24.gif");
			onglet(_T('onglet_repartition_lang'), "statistiques_lang.php3", "langues", $onglet, "langues-24.gif");
		}
	}

	if ($rubrique == "rep_depuis") {
		onglet(_T('icone_repartition_actuelle'), "statistiques_lang.php3", "popularite", $onglet);
		onglet(_T('onglet_repartition_debut'), "statistiques_lang.php3?critere=debut", "debut", $onglet);

	}

	if ($rubrique == "stat_depuis") {
		onglet(_T('icone_repartition_actuelle'), "statistiques.php3", "popularite", $onglet);
		onglet(_T('onglet_repartition_debut'), "statistiques.php3?critere=debut", "debut", $onglet);

	}

	if ($rubrique == "stat_referers") {
		onglet(ucfirst(_T('date_aujourdhui')), "statistiques_referers.php3", "jour", $onglet);
		onglet(ucfirst(_T('date_hier')), "statistiques_referers.php3?jour=veille", "veille", $onglet);
	}

	if ($rubrique == "administration"){
		onglet(_T('onglet_save_restaur_base'), "admin_tech.php3", "sauver", $onglet, "base-24.gif");
		onglet(_T('onglet_affacer_base'), "admin_effacer.php3", "effacer", $onglet, "supprimer.gif");
	}

	if ($rubrique == "auteur"){
		onglet(_T('onglet_auteur'), "auteurs_edit.php3?id_auteur=$id_auteur", "auteur", $onglet, "auteur-24.gif");
		onglet(_T('onglet_informations_personnelles'), "auteur_infos.php3?id_auteur=$id_auteur", "infos", $onglet, "fiche-perso-24.gif");
	}

	if ($rubrique == "configuration"){
		onglet(_T('onglet_contenu_site'), "configuration.php3", "contenu", $onglet, "racine-site-24.gif");
		onglet(_T('onglet_interactivite'), "config-contenu.php3", "interactivite", $onglet, "forum-interne-24.gif");
		onglet(_T('onglet_fonctions_avances'), "config-fonctions.php3", "fonctions", $onglet, "image-24.gif");
	}

	if ($rubrique == "config_lang") {
		onglet(_T('info_langue_principale'), "config-lang.php3", "langues", $onglet, "langues-24.gif");
		onglet(_T('info_multilinguisme'), "config-multilang.php3", "multi", $onglet, "traductions-24.gif");
		if (lire_meta('multi_articles') == "oui" OR lire_meta('multi_rubriques') == "oui") {
			onglet(_T('module_fichiers_langues'), "lang_raccourcis.php3", "fichiers", $onglet, "traductions-24.gif");
		}
	}

	if ($rubrique == "suivi_forum"){
		onglet(_T('onglet_messages_publics'), "controle_forum.php3?page=public", "public", $onglet, "forum-public-24.gif");
		onglet(_T('onglet_messages_internes'), "controle_forum.php3?page=interne", "interne", $onglet, "forum-interne-24.gif");

		$query_forum = "SELECT * FROM spip_forum WHERE statut='publie' AND texte='' LIMIT 0,1";
		$result_forum = spip_query($query_forum);
		if ($row = spip_fetch_array($result_forum))
			onglet(_T('onglet_messages_vide'), "controle_forum.php3?page=vide", "vide", $onglet);

		$query_forum = "SELECT * FROM spip_forum WHERE statut='prop' LIMIT 0,1";
		$result_forum = spip_query($query_forum);
		if ($row = spip_fetch_array($result_forum))
			onglet(_T('texte_statut_attente_validation'), "controle_forum.php3?page=prop", "prop", $onglet);

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
			$retour .= "<td><a href='$lien'><div class='cell-i'>" .
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
	$date_maj = lire_meta("date_calcul_rubriques");

	echo http_script('',"js_menu_rubriques.php?date=$date_maj&spip_ecran=$spip_ecran&dir=$spip_lang_rtl",'');
}


function afficher_javascript ($html) {
	  return http_script("
document.write(\"" . addslashes(strtr($html, "\n\r", "  "))."\")");
}

function debut_page($titre = "", $rubrique = "asuivre", $sous_rubrique = "asuivre", $onLoad = "") {
	global $couleurs_spip;
	global $couleur_foncee;
	global $couleur_claire;
	global $adresse_site;
	global $connect_id_auteur;
	global $connect_statut;
	global $connect_activer_messagerie;
	global $connect_toutes_rubriques;
	global $auth_can_disconnect, $connect_login;
	global $options, $spip_display, $spip_ecran;
	global $spip_lang, $spip_lang_rtl, $spip_lang_left, $spip_lang_right;
	$activer_messagerie = "oui";

	if ($spip_ecran == "large") $largeur = 974;
	else $largeur = 750;

	if (strlen($adresse_site)<10) $adresse_site="../";

	debut_html($titre, $rubrique, $onLoad);

	$link = new Link;
	echo "\n<map name='map_layout'>";
	echo lien_change_var ($link, 'set_disp', 1, '1,0,18,15', _T('lien_afficher_texte_seul'), "onMouseOver=\"changestyle('bandeauvide','visibility', 'visible');\"");
	echo lien_change_var ($link, 'set_disp', 2, '19,0,40,15', _T('lien_afficher_texte_icones'), "onMouseOver=\"changestyle('bandeauvide','visibility', 'visible');\"");
	echo lien_change_var ($link, 'set_disp', 3, '41,0,59,15', _T('lien_afficher_icones_seuls'), "onMouseOver=\"changestyle('bandeauvide','visibility', 'visible');\"");
	echo "\n</map>";



if ($spip_display == "4") {
	// Icones principales
	echo "<ul>";
	echo "<li><a href=\"index.php3\">"._T('icone_a_suivre')."</a>";
	echo "<li><a href=\"naviguer.php3\">"._T('icone_edition_site')."</a>";
	echo "<li><a href=\"forum.php3\">"._T('titre_forum')."</a>";
	echo "<li><a href=\"auteurs.php3\">"._T('icone_auteurs')."</a>";
	echo "<li><a href=\"$adresse_site/\">"._T('icone_visiter_site')."</a>";
	echo "</ul>";
}
else {

	// iframe permettant de passer les changements de statut rapides
	echo "<iframe id='iframe_action' name='iframe_action' width='1' height='1' style='position: absolute; visibility: hidden;'></iframe>";

	// Lien oo
	echo "<div class='invisible_au_chargement' style='position: absolute; height: 0px; visibility: hidden;'><a href='oo'>"._T("access_mode_texte")."</a></div>";
	
	echo "<div id='haut-page'>";

	// Icones principales
	echo "<div class='bandeau-principal' align='center'>\n";
	echo "<div class='bandeau-icones'>\n";
	echo "<table width='$largeur' cellpadding='0' cellspacing='0' border='0' align='center'><tr>\n";

	icone_bandeau_principal (_T('icone_a_suivre'), "index.php3", "asuivre-48.png", "asuivre", $rubrique, "", "asuivre", $sous_rubrique);
	icone_bandeau_principal (_T('icone_edition_site'), "naviguer.php3", "documents-48$spip_lang_rtl.png", "documents", $rubrique, "", "rubriques", $sous_rubrique);
	icone_bandeau_principal (_T('titre_forum'), "forum.php3", "messagerie-48.png", "redacteurs", $rubrique, "", "forum-interne", $sous_rubrique);
	icone_bandeau_principal (_T('icone_auteurs'), "auteurs.php3", "redacteurs-48.png", "auteurs", $rubrique, "", "redacteurs", $sous_rubrique);
	if ($connect_statut == "0minirezo" AND $connect_toutes_rubriques AND lire_meta("activer_statistiques") != 'non') {
		//bandeau_barre_verticale();
		icone_bandeau_principal (_T('icone_statistiques_visites'), "statistiques_visites.php3", "statistiques-48.png", "suivi", $rubrique, "", "statistiques", $sous_rubrique);
	}
	if ($connect_statut == '0minirezo' and $connect_toutes_rubriques) {
		icone_bandeau_principal (_T('icone_configuration_site'), "configuration.php3", "administration-48.png", "administration", $rubrique, "", "configuration", $sous_rubrique);
	}

	echo "<td> &nbsp; </td>";


	icone_bandeau_principal (_T('icone_aide_ligne'),
		"javascript:window.open('aide_index.php3?var_lang=$spip_lang', 'aide_spip', 'scrollbars=yes,resizable=yes,width=740,height=580');",
		"aide-48".aide_lang_dir($spip_lang,$spip_lang_rtl).".png",
		"vide", "", "aide_index.php3?var_lang=$spip_lang",
		"aide-en-ligne", $sous_rubrique);

	icone_bandeau_principal (_T('icone_visiter_site'), "$adresse_site/", "visiter-48$spip_lang_rtl.png", "visiter","", "visiter", $sous_rubrique);

	echo "</tr></table>\n";




	echo "</div>\n";
	
	echo "<table width='$largeur' cellpadding='0' cellspacing='0' align='center'><tr><td>";
	echo "<div style='text-align: $spip_lang_left; width: ".$largeur."px; position: relative; z-index: 2000;'>";
	
	// Icones secondaires
	$activer_messagerie = "oui";
	$connect_activer_messagerie = "oui";
	
	if ($rubrique == "asuivre"){
		$class = "visible_au_chargement";
	} else {
		$class = "invisible_au_chargement";
	}
	$decal = largeur_icone_bandeau_principal(_T('icone_a_suivre'));


	if ($rubrique == "documents"){
		$class = "visible_au_chargement";
	} else {
		$class = "invisible_au_chargement";
	}
	if ($connect_statut == "0minirezo" AND $connect_toutes_rubriques) {
		echo "<div class='$class' id='bandeaudocuments' style='position: absolute; $spip_lang_left: ".$decal."px;'><div class='bandeau_sec'><table class='gauche'><tr>\n";
		//icone_bandeau_secondaire (_T('icone_rubriques'), "naviguer.php3", "rubrique-24.gif", "rubriques", $sous_rubrique);

		$nombre_articles = spip_num_rows(spip_query("SELECT art.id_article FROM spip_articles AS art, spip_auteurs_articles AS lien WHERE lien.id_auteur = '$connect_id_auteur' AND art.id_article = lien.id_article LIMIT 0,1"));
		if ($nombre_articles > 0) {
			icone_bandeau_secondaire (_T('icone_tous_articles'), "articles_page.php3", "article-24.gif", "articles", $sous_rubrique);
		}

		/*if ($options == "avancees") {
			if ($connect_statut == "0minirezo") $req_where = " AND articles.statut IN ('prepa','prop','publie')"; 
			else $req_where = " AND articles.statut IN ('prop','publie')"; 
			$nombre_versions = spip_num_rows(spip_query("
				SELECT versions.*, articles.statut, articles.titre
				FROM spip_versions AS versions, spip_articles AS articles 
				WHERE versions.id_article = articles.id_article AND versions.id_version > 1$req_where LIMIT 0,1"));
			if ($nombre_versions > 0 OR 1==1) {
				icone_bandeau_secondaire (_T('icone_suivi_revisions'), "suivi_revisions.php3", "historique-24.gif", "revisions", $sous_rubrique);
			}
		}*/

		$activer_breves=lire_meta("activer_breves");
		if ($activer_breves != "non"){
			icone_bandeau_secondaire (_T('icone_breves'), "breves.php3", "breve-24.gif", "breves", $sous_rubrique);
		}

		if ($options == "avancees"){
			$articles_mots = lire_meta('articles_mots');
			if ($articles_mots != "non") {
				icone_bandeau_secondaire (_T('icone_mots_cles'), "mots_tous.php3", "mot-cle-24.gif", "mots", $sous_rubrique);
			}

			$activer_sites = lire_meta('activer_sites');
			if ($activer_sites<>'non')
				icone_bandeau_secondaire (_T('icone_sites_references'), "sites_tous.php3", "site-24.gif", "sites", $sous_rubrique);

			if (@spip_num_rows(spip_query("SELECT * FROM spip_documents_rubriques LIMIT 0,1")) > 0) {
				icone_bandeau_secondaire (_T('icone_doc_rubrique'), "documents_liste.php3", "doc-24.gif", "documents", $sous_rubrique);
			}
		}
		echo "</tr></table></div></div>";
	}

	$decal = $decal + largeur_icone_bandeau_principal(_T('icone_edition_site'));


	
	
	if ($connect_statut == "0minirezo" AND $connect_toutes_rubriques) {
		if ($rubrique == "redacteurs") {
			$class = "visible_au_chargement";
		} else {
			$class = "invisible_au_chargement";
		}

			echo "<div class='$class' id='bandeauredacteurs' style='position: absolute; $spip_lang_left: ".$decal."px;'><div class='bandeau_sec'><table class='gauche'><tr>\n";
				if (lire_meta('forum_prive_admin') == 'oui') icone_bandeau_secondaire (_T('icone_forum_administrateur'), "forum_admin.php3", "forum-admin-24.gif", "privadm", $sous_rubrique);

				icone_bandeau_secondaire (_T('icone_suivi_forums'), "controle_forum.php3", "suivi-forum-24.gif", "forum-controle", $sous_rubrique);
				icone_bandeau_secondaire (_T('icone_suivi_pettions'), "controle_petition.php3", "suivi-petition-24.gif", "suivi-petition", $sous_rubrique);

			echo "</tr></table></div></div>";
	
	}
	
	$decal = $decal + largeur_icone_bandeau_principal(_T('icone_discussions'));
	
	if ($connect_statut == "0minirezo" AND $connect_toutes_rubriques) {
		if ($rubrique == "auteurs") {
			$class = "visible_au_chargement";
		} else {
			$class = "invisible_au_chargement";
		}
		echo "<div class='$class' id='bandeauauteurs' style='position: absolute; $spip_lang_left: ".$decal."px;'><div class='bandeau_sec'><table class='gauche'><tr>\n";
		icone_bandeau_secondaire (_T('icone_informations_personnelles'), "auteurs_edit.php3?id_auteur=$connect_id_auteur", "fiche-perso-24.gif", "perso", $sous_rubrique);
		icone_bandeau_secondaire (_T('icone_creer_nouvel_auteur'), "auteur_infos.php3?new=oui", "auteur-24.gif", "xxx", $sous_rubrique);
	
		echo "</tr></table></div></div>";
	}	

	
	
	$decal = $decal + largeur_icone_bandeau_principal(_T('icone_auteurs'));

	// decalage pour barre verticale
	// $decal = $decal + 11;

	if ($connect_statut == "0minirezo" AND $connect_toutes_rubriques AND lire_meta("activer_statistiques") != 'non') {
		if ($rubrique == "suivi") {
			$class = "visible_au_chargement";
		} else {
			$class = "invisible_au_chargement";
		}
		echo "<div class='$class' id='bandeausuivi' style='position: absolute; $spip_lang_left: ".$decal."px;'><div class='bandeau_sec'><table class='gauche'><tr>\n";
		if ($connect_toutes_rubriques) bandeau_barre_verticale();

		icone_bandeau_secondaire (_T('icone_repartition_visites'), "statistiques.php3", "rubrique-24.gif", "repartition", $sous_rubrique);
		if (lire_meta('multi_articles') == 'oui' OR lire_meta('multi_rubriques') == 'oui')
			icone_bandeau_secondaire (_T('onglet_repartition_lang'), "statistiques_lang.php3", "langues-24.gif", "repartition-langues", $sous_rubrique);
		icone_bandeau_secondaire (_T('titre_liens_entrants'), "statistiques_referers.php3", "referers-24.gif", "referers", $sous_rubrique);

		echo "</tr></table></div></div>";

		$decal = $decal + largeur_icone_bandeau_principal(_T('icone_suivi_actualite'));
	
	}


	if ($connect_statut == '0minirezo' and $connect_toutes_rubriques) {
		if ($rubrique == "administration") {
			$class = "visible_au_chargement";
		} else {
			$class = "invisible_au_chargement";
		}
			echo "<div class='$class' id='bandeauadministration' style='position: absolute; $spip_lang_left: ".$decal."px;'><div class='bandeau_sec'><table class='gauche'><tr>\n";
			icone_bandeau_secondaire (_T('icone_gestion_langues'), "config-lang.php3", "langues-24.gif", "langues", $sous_rubrique);
	
			bandeau_barre_verticale();
			if ($options == "avancees") {
				icone_bandeau_secondaire (_T('icone_maintenance_site'), "admin_tech.php3", "base-24.gif", "base", $sous_rubrique);
				icone_bandeau_secondaire (_T('onglet_vider_cache'), "admin_vider.php3", "cache-24.gif", "cache", $sous_rubrique);
			}
			else {
				icone_bandeau_secondaire (_T('icone_sauver_site'), "admin_tech.php3", "base-24.gif", "base", $sous_rubrique);
			}
			echo "</tr></table></div></div>";


	}

	// Refermer tout de suite le bandeau deroule par defaut
	echo "
	<script type='text/javascript'><!--
		changestyle('-', '-', '-');
	// --></script>\n";

	echo "</div>";
	
	echo "</td></tr></table>";
	
	echo "</div>\n";

	// Bandeau
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
		echo "<a href='articles_tous.php3' class='icone26' onMouseOver=\"changestyle('bandeautoutsite','visibility','visible');\">" .
		  http_img_pack("tout-site.png", "", "width='26' height='20' border='0'") . "</a>";

		$id_rubrique = $GLOBALS['id_rubrique'];
		if ($id_rubrique > 0) echo "<a href='brouteur.php3?id_rubrique=$id_rubrique' class='icone26' onMouseOver=\"changestyle('bandeaunavrapide','visibility','visible');\">" .
		  http_img_pack("naviguer-site.png", "", "width='26' height='20' border='0'") ."</a>";
		else echo "<a href='brouteur.php3' class='icone26' onMouseOver=\"changestyle('bandeaunavrapide','visibility','visible');\" >" .
		  http_img_pack("naviguer-site.png", "", "width='26' height='20' border='0'") . "</a>";

		echo "<a href='recherche.php3' class='icone26' onMouseOver=\"changestyle('bandeaurecherche','visibility','visible');\" >" .
		  http_img_pack("loupe.png", "", "width='26' height='20' border='0'") ."</a>";

		echo http_img_pack("rien.gif", " ", "width='10'");

		echo "<a href='calendrier.php3?type=semaine' class='icone26' onMouseOver=\"changestyle('bandeauagenda','visibility','visible');\">" .
		  http_img_pack("cal-rv.png", "", "width='26' height='20' border='0'") ."</a>";
		echo "<a href='messagerie.php3' class='icone26' onMouseOver=\"changestyle('bandeaumessagerie','visibility','visible');\">" .
		  http_img_pack("cal-messagerie.png", "", "width='26' height='20' border='0'") ."</a>";
		echo "<a href='synchro.php3' class='icone26' onMouseOver=\"changestyle('bandeausynchro','visibility','visible');\">" .
		  http_img_pack("cal-suivi.png", "", "width='26' height='20' border='0'") . "</a>";
		

		if (!($connect_statut == "0minirezo" AND $connect_toutes_rubriques)) {
			echo http_img_pack("rien.gif", " ", "width='10'");
			echo "<a href='auteurs_edit.php3?id_auteur=$connect_id_auteur' class='icone26' onMouseOver=\"changestyle('bandeauinfoperso','visibility','visible');\">" .
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
			// Choix de la couleur: automatique en fonction de $couleurs_spip

			$link = new Link;
			if ($couleurs_spip) {
			  ksort($couleurs_spip);
			  while (list($key,$val) = each($couleurs_spip)) {
					$link->delVar('set_couleur');
					$link->addVar('set_couleur', $key);
					
					echo "<a href=\"".$link->getUrl()."\">" .
					  http_img_pack("rien.gif", " ", "width='8' height='8' border='0' style='margin: 1px; background-color: ".$couleurs_spip[$key]['couleur_claire'].";' onMouseOver=\"changestyle('bandeauinterface','visibility', 'visible');\""). "</a>";
			  }
			}
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
				echo "<a href='../spip_cookie.php3?logout=$connect_login' class='icone26' onMouseOver=\"changestyle('bandeaudeconnecter','visibility', 'visible');\">" .
				  http_img_pack("deconnecter-24.gif", "", "border='0'") . "</a>";
			}
		echo "</td>";
	
	
	echo "</tr></table>";
	

	//
	// Barre des gadgets
	//

	echo "<table width='$largeur' cellpadding='0' cellspacing='0' align='center'><tr><td>";


	// GADGET Menu rubriques
	echo "<div style='position: relative; z-index: 1000;'>";
	echo "<div id='bandeautoutsite' class='bandeau_couleur_sous' style='$spip_lang_left: 0px;'>";
	echo "<a href='articles_tous.php3' class='lien_sous'>"._T('icone_site_entier')."</a>";
	afficher_menu_rubriques();
	echo "</div>";
	// FIN GADGET Menu rubriques
	
	
	
	
	// GADGET Navigation rapide
	echo "<div id='bandeaunavrapide' class='bandeau_couleur_sous' style='$spip_lang_left: 30px; width: 300px;'>";

	if ($id_rubrique > 0) echo "<a href='brouteur.php3?id_rubrique=$id_rubrique' class='lien_sous'>";
	else echo "<a href='brouteur.php3' class='lien_sous'>";
	echo _T('icone_brouteur');
	echo "</a>";

	$gadget = '';
		$vos_articles = spip_query("SELECT articles.id_article, articles.titre, articles.statut FROM spip_articles AS articles, spip_auteurs_articles AS lien WHERE articles.id_article=lien.id_article ".
			"AND lien.id_auteur=$connect_id_auteur AND articles.statut='prepa' ORDER BY articles.date DESC LIMIT 0,5");
		if (spip_num_rows($vos_articles) > 0) {
			$gadget .= "<div>&nbsp;</div>";
			$gadget .= "<div class='bandeau_rubriques' style='z-index: 1;'>";
			$gadget .= bandeau_titre_boite2(afficher_plus("articles_page.php3")._T('info_en_cours_validation'), "article-24.gif", '', '', false);
			$gadget .= "\n<div class='plan-articles'>\n";
			while($row = spip_fetch_array($vos_articles)) {
				$id_article = $row['id_article'];
				$titre = typo($row['titre']);
				$statut = $row['statut'];
				$gadget .= "<a class='$statut' style='font-size: 10px;' href='articles.php3?id_article=$id_article'>$titre</a>\n";
			}
			$gadget .= "</div>";
			$gadget .= "</div>";
		}
	
		$vos_articles = spip_query("SELECT articles.id_article, articles.titre, articles.statut FROM spip_articles AS articles WHERE articles.statut='prop' ".
			" ORDER BY articles.date DESC LIMIT 0,5");
		if (spip_num_rows($vos_articles) > 0) {
			$gadget .= "<div>&nbsp;</div>";
			$gadget .= "<div class='bandeau_rubriques' style='z-index: 1;'>";
			$gadget .= bandeau_titre_boite2(afficher_plus("index.php3")._T('info_articles_proposes'), "article-24.gif", '', '', false);
			$gadget .= "<div class='plan-articles'>";
			while($row = spip_fetch_array($vos_articles)) {
				$id_article = $row['id_article'];
				$titre = typo($row['titre']);
				$statut = $row['statut'];
	
				$gadget .= "<a class='$statut' style='font-size: 10px;' href='articles.php3?id_article=$id_article'>$titre</a>";
			}
			$gadget .= "</div>";
			$gadget .= "</div>";
		}
			
		$vos_articles = spip_query("SELECT * FROM spip_breves WHERE statut='prop' ".
			" ORDER BY date_heure DESC LIMIT 0,5");
		if (spip_num_rows($vos_articles) > 0) {
			$gadget .= "<div>&nbsp;</div>";
			$gadget .= "<div class='bandeau_rubriques' style='z-index: 1;'>";
			$gadget .= bandeau_titre_boite2(afficher_plus("breves.php3")._T('info_breves_valider'), "breve-24.gif", "$couleur_foncee", "white", false);
			$gadget .= "<div class='plan-articles'>";
			while($row = spip_fetch_array($vos_articles)) {
				$id_breve = $row['id_breve'];
				$titre = typo($row['titre']);
				$statut = $row['statut'];
	
				$gadget .= "<a class='$statut' style='font-size: 10px;' href='breves_voir.php3?id_breve=$id_breve'>$titre</a>";
			}
			$gadget .= "</div>";
			$gadget .= "</div>";
		}


		$query = "SELECT id_rubrique FROM spip_rubriques LIMIT 0,1";
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
					$gadget .= icone_horizontale(_T('icone_creer_sous_rubrique'), "rubriques_edit.php3?new=oui$dans_parent", "rubrique-24.gif", "creer.gif", false);
				else 
					$gadget .= icone_horizontale(_T('icone_creer_rubrique'), "rubriques_edit.php3?new=oui", "rubrique-24.gif", "creer.gif", false);
				$gadget .= "</div>";
			}		
			$gadget .= "<div style='width: 140px; float: $spip_lang_left;'>";
			$gadget .= icone_horizontale(_T('icone_ecrire_article'), "articles_edit.php3?new=oui$dans_rub", "article-24.gif","creer.gif", false);
			$gadget .= "</div>";
			
			$activer_breves = lire_meta("activer_breves");
			if ($activer_breves != "non") {
				$gadget .= "<div style='width: 140px;  float: $spip_lang_left;'>";
				$gadget .= icone_horizontale(_T('icone_nouvelle_breve'), "breves_edit.php3?new=oui$dans_rub", "breve-24.gif","creer.gif", false);
				$gadget .= "</div>";
			}
			
			if (lire_meta("activer_sites") == 'oui') {
				if ($connect_statut == '0minirezo' OR lire_meta("proposer_sites") > 0) {
					$gadget .= "<div style='width: 140px; float: $spip_lang_left;'>";
					$gadget .= icone_horizontale(_T('info_sites_referencer'), "sites_edit.php3?new=oui&target=sites.php3$dans_rub", "site-24.gif","creer.gif", false);
					$gadget .= "</div>";
				}
			}
			
		}

		$gadget .= "</div>";

	echo afficher_javascript($gadget);
	// FIN GADGET Navigation rapide


	// GADGET Recherche
		echo "<div id='bandeaurecherche' class='bandeau_couleur_sous' style='width: 100px; $spip_lang_left: 60px;'>";
		global $recherche;
				$recherche_aff = _T('info_rechercher');
				$onfocus = "onfocus=this.value='';";
			echo "<form method='get' style='margin: 0px;' action='recherche.php3'>";
			echo '<input type="text" size="10" value="'.$recherche_aff.'" name="recherche" class="formo" accesskey="r" '.$onfocus.'>';
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
		if (spip_num_rows(spip_query("SELECT type FROM spip_messages AS messages WHERE id_auteur=$connect_id_auteur AND statut='publie' AND type='pb' AND rv!='oui' LIMIT 0,1")) OR
		    spip_num_rows(spip_query("SELECT type FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE ((lien.id_auteur='$connect_id_auteur' AND lien.id_message=messages.id_message) OR messages.type='affich') AND messages.rv='oui' AND messages.date_heure > DATE_SUB(NOW(), INTERVAL 1 DAY) AND messages.date_heure < DATE_ADD(NOW(), INTERVAL 1 MONTH) AND messages.statut='publie' GROUP BY messages.id_message ORDER BY messages.date_heure LIMIT 0,1"))) {
			$largeur = "410px";
			$afficher_cal = true;
		}
		else {
			$largeur = "200px";
			$afficher_cal = false;
		}



		// Calendrier
			$gadget .= "<div id='bandeauagenda' class='bandeau_couleur_sous' style='width: $largeur; $spip_lang_left: 100px;'>";
			$gadget .= "<a href='calendrier.php3?type=semaine' class='lien_sous'>";
			$gadget .= _T('icone_agenda');
			$gadget .= "</a>";
			
			$gadget .= "<table><tr>";
			$gadget .= "<td valign='top' width='200'>";
				$gadget .= "<div>";
				$gadget .= http_calendrier_agenda($annee_today, $mois_today, $jour_today, $mois_today, $annee_today, false, 'calendrier.php3');
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
		$gadget .= "<a href='messagerie.php3' class='lien_sous'>";
		$gadget .= _T('icone_messagerie_personnelle');
		$gadget .= "</a>";
		
		$gadget .= "<div>&nbsp;</div>";
		$gadget .= icone_horizontale(_T('lien_nouvea_pense_bete'),"message_edit.php3?new=oui&type=pb", "pense-bete.gif", '', false);
		$gadget .= icone_horizontale(_T('lien_nouveau_message'),"message_edit.php3?new=oui&type=normal", "message.gif", '', false);
		if ($connect_statut == "0minirezo") {
			$gadget .= icone_horizontale(_T('lien_nouvelle_annonce'),"message_edit.php3?new=oui&type=affich", "annonce.gif", '', false);
		}
		$gadget .= "</div>";

	echo afficher_javascript($gadget);

	// FIN GADGET Messagerie


		// Suivi activite	
		echo "<div id='bandeausynchro' class='bandeau_couleur_sous' style='$spip_lang_left: 160px;'>";
		echo "<a href='synchro.php3' class='lien_sous'>";
		echo _T('icone_suivi_activite');
		echo "</a>";
		echo "</div>";
	
		// Infos perso
		echo "<div id='bandeauinfoperso' class='bandeau_couleur_sous' style='width: 200px; $spip_lang_left: 200px;'>";
		echo "<a href='auteurs_edit.php3?id_auteur=$connect_id_auteur' class='lien_sous'>";
		echo _T('icone_informations_personnelles');
		echo "</a>";
		echo "</div>";

		
		//
		// -------- Affichage de droite ----------
	
		// Deconnection
		echo "<div class='bandeau_couleur_sous' id='bandeaudeconnecter' style='$spip_lang_right: 0px;'>";
		echo "<a href='../spip_cookie.php3?logout=$connect_login' class='lien_sous'>"._T('icone_deconnecter')."</a>".aide("deconnect");
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
				echo "<div>&nbsp;</div><div style='width: 250px; text-align: $spip_lang_left;'>"._T('texte_actualite_site_1')."<a href='index.php3?&set_options=avancees'>"._T('texte_actualite_site_2')."</a>"._T('texte_actualite_site_3')."</div>";
			}

		echo "</div>";
	
	
	echo "</div>";
	echo "</td></tr></table>";
	
	echo "</div>";
	echo "</div>";

	if ($options != "avancees") echo "<div style='height: 18px;'>&nbsp;</div>";
	
}

	// Ouverture de la partie "principale" de la page
	// Petite verif pour ne pas fermer le formulaire de recherche pendant qu'on l'edite	
	echo "<center onMouseOver=\"if (findObj('bandeaurecherche').style.visibility == 'visible') { ouvrir_recherche = true; } else { ouvrir_recherche = false; } changestyle('bandeauvide', 'visibility', 'hidden'); if (ouvrir_recherche == true) { changestyle('bandeaurecherche','visibility','visible'); }\">";

			$result_messages = spip_query("SELECT * FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE lien.id_auteur=$connect_id_auteur AND vu='non' AND statut='publie' AND type='normal' AND lien.id_message=messages.id_message");
			$total_messages = @spip_num_rows($result_messages);
			if ($total_messages == 1) {
				while($row = @spip_fetch_array($result_messages)) {
					$ze_message=$row['id_message'];
					echo "<div class='messages'><a href='message.php3?id_message=$ze_message'><font color='$couleur_foncee'>"._T('info_nouveau_message')."</font></a></div>";
				}
			}
			if ($total_messages > 1) echo "<div class='messages'><a href='messagerie.php3'><font color='$couleur_foncee'>"._T('info_nouveaux_messages', array('total_messages' => $total_messages))."</font></a></div>";


	// Afficher les auteurs recemment connectes
	
	global $changer_config;
	global $activer_messagerie;
	global $activer_imessage;
	global $connect_activer_messagerie;
	global $connect_activer_imessage;

		if ($changer_config!="oui"){
			$activer_messagerie = "oui";
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
						echo " &nbsp; ".bouton_imessage($id_auteur,$row)."&nbsp;<a href='auteurs_edit.php3?id_auteur=$id_auteur' style='color: #666666;'>$nom_auteur</a>";
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
	  (!$style ? "" : "style='$style'") .
	   ">";
}

function fin_cadre_formulaire(){
	echo "</div>\n";
}



//
// Debut de la colonne de gauche
//

function debut_gauche($rubrique = "asuivre") {
	global $connect_statut, $cookie_admin;
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
		if (ereg('((articles|breves|rubriques)_edit|forum_envoi)\.php3', $GLOBALS['REQUEST_URI'])) {
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
		<div style='width: ${largeur}px;'>
\n";
		
	if ($spip_display == 4) echo "<!-- ";

}


//
// Presentation de l''interface privee, marge de droite
//

function creer_colonne_droite($rubrique=""){
	global $deja_colonne_droite;
	global $changer_config;
	global $activer_messagerie;
	global $activer_imessage;
	global $connect_activer_messagerie;
	global $connect_activer_imessage;
	global $connect_statut, $cookie_admin;
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
		if (lire_meta("articles_modif") != "non") {
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
					echo "<div class='verdana1'><b><a href='articles.php3?id_article=$ze_article'>$ze_titre</a></div></b>";
					
					// ne pas proposer de debloquer si c'est l'article en cours d'edition
					if ($ze_article != $GLOBALS['id_article_bloque']) {
						$nb_liberer ++;
						$lien = new Link;
						$lien->addVar('debloquer_article', $ze_article);
						echo "<div class='arial1' style='text-align:right;'><a href='". $lien->getUrl() ."' title='"._T('lien_liberer')."'>"._T('lien_liberer')."&nbsp;" .
						  http_img_pack("croix-rouge.gif", "X", "width='7' height='7' border='0' align='middle'") . "</a></div>";
					}
				
					echo "</div>";
				}
				if ($nb_liberer >= 4) {
					$lien = new Link;
					$lien->addVar('debloquer_article', 'tous');
					echo "<div class='arial2' style='text-align:right; padding:2px; border-top: 1px solid $couleur_foncee;'><a href='". $lien->getUrl() ."'>"._T('lien_liberer_tous')."&nbsp;" .
					  http_img_pack("croix-rouge.gif", "", "width='7' height='7' border='0' align='middle'") ."</a></div>";
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
		  http_script("
document.img_session.src='../spip_cookie.php3?change_session=oui'");
	}

	echo "</body></html>\n";

}

function info_copyright() {
	global $spip_version_affichee;

	echo _T('info_copyright', 
		   array('spip' => "<b>SPIP $spip_version_affichee</b> ",
			      'lien_gpl' => 
				"<a href='aide_index.php3?aide=licence&var_lang=".$GLOBALS['spip_lang']."' target='spip_aide' onClick=\"javascript:window.open(this.href, 'aide_spip', 'scrollbars=yes,resizable=yes,width=740,height=580'); return false;\">" . _T('info_copyright_gpl')."</a>"));

}

function fin_page($credits='') {
	global $spip_display;

	echo "</td></tr></table>";

	debut_grand_cadre();

	# ici on en profite pour glisser une tache de fond
	echo "<div align='right' class='verdana2' ";
	echo "style='background: url(\"../spip_background.php3\");' ";
	echo ">";

	if ($spip_display == 4) {
		echo "<div><a href=\"index.php3?set_disp=2\">"._T("access_interface_graphique")."</a></div>";
	} else {
		echo info_copyright();
		echo "<br>"._T('info_copyright_doc');
		echo "</div><p>";
	}

	fin_grand_cadre();
	echo "</center>";

	fin_html();
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
			$titre = $row['titre'];
			changer_typo($row['lang']);

			if (acces_restreint_rubrique($id_rubrique))
				$logo = "admin-12.gif";
			if (!$id_parent)
				$logo = "secteur-12.gif";
			else
				$logo = "rubrique-12.gif";


			$parents = "<div class='verdana3' ". 
			  http_style_background($logo, "$spip_lang_left center no-repeat; padding-$spip_lang_left: 15px"). 
			  "><a href='naviguer.php3?id_rubrique=$id_rubrique'>".typo($titre)."</a></div>\n<div style='margin-$spip_lang_left: 15px;'>".$parents."</div>";


		}
		afficher_hierarchie($id_parent, $parents);
	}
	else {
		$logo = "racine-site-12.gif";
		$parents = "<div class='verdana3' " .
		  http_style_background($logo, "$spip_lang_left center no-repeat; padding-$spip_lang_left: 15px"). 
		  "><a href='naviguer.php3?id_rubrique=$id_rubrique'><b>"._T('lien_racine_site')."</b></a>".aide ("rubhier")."</div>\n<div style='margin-$spip_lang_left: 15px;'>".$parents."</div>";
	
		echo $parents;
		
	}
}

// Pour construire des menu avec SELECTED
function mySel($varaut,$variable, $option = NULL) {
	if (!isset($option))
		return ' value="'.$varaut.'"'
			. (($variable==$varaut) ? ' selected' : '');

	return "<option value='$varaut'"
		. (($variable==$varaut) ? ' selected' : '')
		. ">$option\n";
}


//
// Presentation des pages d'installation et d'erreurs
//

function install_debut_html($titre = 'AUTO') {
	global $spip_lang_rtl;

	if ($titre=='AUTO')
		$titre=_T('info_installation_systeme_publication');

	echo debut_entete($titre),
	  "<style type='text/css'>
	<!--
	a {text-decoration: none; }
	A:Hover {color:#FF9900; text-decoration: underline;}
	.forml {width: 100%; background-color: #FFCC66; background-position: center bottom; float: none; color: #000000}
	.formo {width: 100%; background-color: #FFF0E0; background-position: center bottom; weight: bold; float: none; color: #000000}
	.fondl {background-color: #FFCC66; background-position: center bottom; float: none; color: #000000}
	.fondo {background-color: #FFF0E0; background-position: center bottom; float: none; color: #000000}
	.fondf {background-color: #FFFFFF; border-style: solid ; border-width: 1; border-color: #E86519; color: #E86519}
	.serif { font-family: Georgia, Garamond, Times New Roman, serif; }
	-->
	</style>
	</head>
	<body bgcolor='#FFFFFF' text='#000000' link='#E86519' vlink='#6E003A' alink='#FF9900'";

	if ($spip_lang_rtl) echo " dir='rtl'";

	echo "><br><br><br>
	<center>
	<table width='450'>
	<tr><td width='450' class='serif'>
	<font face='Verdana,Arial,Sans,sans-serif' size='4' color='#970038'><B>$titre</b></font>\n<p>";
}

function install_fin_html($suite = '') {

	// bouton retour
	global $spip_lang_right;
	if ($suite) {
		$link = new Link($suite);
		echo $link->getForm();
		echo "<DIV align='$spip_lang_right'>"
		. "<INPUT TYPE='submit' CLASS='fondl'  VALUE='"
		. _T('ecrire:bouton_suivant')." >>'></div>"
		. "</FORM>";
	}

	echo '
	</td></tr></table>
	</center>
	</body>
	</html>
	';
}

// Voir en ligne, ou apercu, ou rien (renvoie tout le bloc)
function voir_en_ligne ($type, $id, $statut=false, $image='racine-24.gif') {
	global $connect_statut;

	switch ($type) {
		case 'article':
			if ($statut == "publie" AND lire_meta("post_dates") == 'non'
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
		if ((lire_meta('preview') == 'oui' AND $connect_statut=='0minirezo')
			OR (lire_meta('preview') == '1comite'))
			$message = _T('previsualiser');
		else
			$message = '';
	}

	if ($message)
		icone_horizontale($message, "../spip_redirect.php3?id_$type=$id&var_mode=$en_ligne", $image, "rien.gif");
}


function http_style_background($img, $att='')
{
  return " style='background: url(\"" . _DIR_IMG_PACK . $img .  '")' .
    ($att ? (' ' . $att) : '') . ";'";
}
?>
