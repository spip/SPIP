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
include_spip('inc/puce_statut');

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
function debut_cadre($style, $icone = "", $fonction = "", $titre = "", $id="", $class="") {
	global $spip_display, $spip_lang_left;
	static $accesskey = 97; // a

	//zoom:1 fixes all expanding blocks in IE, see authors block in articles.php
	//being not standard, next step can be putting this kind of hacks in a different stylesheet
	//visible to IE only using conditional comments.  
	
	$style_cadre = " style='";
	if ($spip_display != 1 AND $spip_display != 4 AND strlen($icone) > 1) {
		$style_gauche = "padding-$spip_lang_left: 38px;";
		$style_cadre .= "margin-top: 20px;'";
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

	$ret .= "\n<div "
	. ($id?"id='$id' ":"")
	."class='cadre cadre-$style"
	. ($class?" $class":"")
	."'$style_cadre>";

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
		if (strpos($titre,'titrem')!==false) {
			$ret .= $titre;
		} elseif ($spip_display == 4) {
			$ret .= "\n<h3 class='cadre-titre'>$titre</h3>";
		} else {
			$ret .= bouton_block_depliable($titre,-1);
		}
	}
	
	return $ret
	."<div class='cadre_padding'>"
	;
}

// http://doc.spip.org/@fin_cadre
function fin_cadre($style='') {

	$ret = "</div><div class='nettoyeur'></div>".
	"</div>\n";

	/*if ($style != "forum" AND $style != "thread-forum")
		$ret .= "<div style='height: 5px;'></div>\n";*/

	return $ret;
}


// http://doc.spip.org/@debut_cadre_relief
function debut_cadre_relief($icone='', $return = false, $fonction='', $titre = '', $id="", $class=""){
	$retour_aff = debut_cadre('r', $icone, $fonction, $titre, $id, $class);

	if ($return) return $retour_aff; else echo $retour_aff;
}

// http://doc.spip.org/@fin_cadre_relief
function fin_cadre_relief($return = false){
	$retour_aff = fin_cadre('r');

	if ($return) return $retour_aff; else echo $retour_aff;
}


// http://doc.spip.org/@debut_cadre_enfonce
function debut_cadre_enfonce($icone='', $return = false, $fonction='', $titre = '', $id="", $class=""){
	$retour_aff = debut_cadre('e', $icone, $fonction, $titre, $id, $class);

	if ($return) return $retour_aff; else echo $retour_aff;
}

// http://doc.spip.org/@fin_cadre_enfonce
function fin_cadre_enfonce($return = false){

	$retour_aff = fin_cadre('e');

	if ($return) return $retour_aff; else echo $retour_aff;
}


// http://doc.spip.org/@debut_cadre_sous_rub
function debut_cadre_sous_rub($icone='', $return = false, $fonction='', $titre = '', $id="", $class=""){
	$retour_aff = debut_cadre('sous_rub', $icone, $fonction, $titre, $id, $class);
	if ($return) return $retour_aff; else echo $retour_aff;
}

// http://doc.spip.org/@fin_cadre_sous_rub
function fin_cadre_sous_rub($return = false){
	$retour_aff = fin_cadre('sous_rub');
	if ($return) return $retour_aff; else echo $retour_aff;
}



// http://doc.spip.org/@debut_cadre_forum
function debut_cadre_forum($icone='', $return = false, $fonction='', $titre = '', $id="", $class=""){
	$retour_aff = debut_cadre('forum', $icone, $fonction, $titre, $id, $class);

	if ($return) return $retour_aff; else echo $retour_aff;
}

// http://doc.spip.org/@fin_cadre_forum
function fin_cadre_forum($return = false){
	$retour_aff = fin_cadre('forum');

	if ($return) return $retour_aff; else echo $retour_aff;
}

// http://doc.spip.org/@debut_cadre_thread_forum
function debut_cadre_thread_forum($icone='', $return = false, $fonction='', $titre = '', $id="", $class=""){
	$retour_aff = debut_cadre('thread-forum', $icone, $fonction, $titre, $id, $class);

	if ($return) return $retour_aff; else echo $retour_aff;
}

// http://doc.spip.org/@fin_cadre_thread_forum
function fin_cadre_thread_forum($return = false){
	$retour_aff = fin_cadre('thread-forum');

	if ($return) return $retour_aff; else echo $retour_aff;
}


// http://doc.spip.org/@debut_cadre_couleur
function debut_cadre_couleur($icone='', $return = false, $fonction='', $titre='', $id="", $class=""){
	$retour_aff = debut_cadre('couleur', $icone, $fonction, $titre, $id, $class);

	if ($return) return $retour_aff; else echo $retour_aff;
}

// http://doc.spip.org/@fin_cadre_couleur
function fin_cadre_couleur($return = false){
	$retour_aff = fin_cadre('couleur');

	if ($return) return $retour_aff; else echo $retour_aff;
}


// http://doc.spip.org/@debut_cadre_couleur_foncee
function debut_cadre_couleur_foncee($icone='', $return = false, $fonction='', $titre='', $id="", $class=""){
	$retour_aff = debut_cadre('couleur-foncee', $icone, $fonction, $titre, $id, $class);

	if ($return) return $retour_aff; else echo $retour_aff;
}

// http://doc.spip.org/@fin_cadre_couleur_foncee
function fin_cadre_couleur_foncee($return = false){
	$retour_aff = fin_cadre('couleur-foncee');

	if ($return) return $retour_aff; else echo $retour_aff;
}

// http://doc.spip.org/@debut_cadre_trait_couleur
function debut_cadre_trait_couleur($icone='', $return = false, $fonction='', $titre='', $id="", $class=""){
	$retour_aff = debut_cadre('trait-couleur', $icone, $fonction, $titre, $id, $class);
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
	return debut_cadre('alerte', '', '', '', '', '');
}

// http://doc.spip.org/@fin_boite_alerte
function fin_boite_alerte() {
	return fin_cadre('alerte');
}


//
// une boite info
//
// http://doc.spip.org/@debut_boite_info
function debut_boite_info($return=false) {
	$r = debut_cadre('info', '', '', '', '', 'verdana1');
	if ($return) return $r; else echo $r;
}

// http://doc.spip.org/@fin_boite_info
function fin_boite_info($return=false) {
	$r = fin_cadre('info');
	if ($return) return $r; else echo $r;
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
function afficher_objets($type, $titre_table,$requete,$formater='',$force=false){
	$afficher_objets = charger_fonction('afficher_objets','inc');
	return $afficher_objets($type, $titre_table,$requete,$formater,$force);
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

// http://doc.spip.org/@navigation_pagination
function navigation_pagination($num_rows, $nb_aff=10, $href=null, $onclick=false, $tmp_var=null) {

	$texte = '';
	$self = self();
	$deb_aff = isset($tmp_var) ? intval(_request($tmp_var)) : 0;

	for ($i = 0; $i < $num_rows; $i += $nb_aff){
		$deb = $i + 1;

		// Pagination : si on est trop loin, on met des '...'
		if (abs($deb-$deb_aff)>101) {
			if ($deb<$deb_aff) {
				if (!isset($premiere)) {
					$premiere = '0 ... ';
					$texte .= $premiere;
				}
			} else {
				$derniere = ' | ... '.$num_rows;
				$texte .= $derniere;
				break;
			}
		} else {

			$fin = $i + $nb_aff;
			if ($fin > $num_rows)
				$fin = $num_rows;

			if ($deb > 1)
				$texte .= " |\n";
			if ($deb_aff + 1 >= $deb AND $deb_aff + 1 <= $fin) {
				$texte .= "<b>$deb</b>";
			}
			else {
				$script = parametre_url($self, $tmp_var, $deb-1);
				if ($onclick) {
					$on = "\nonclick=\"return charger_id_url('"
					. parametre_url($href, $tmp_var, $deb-1)
					. "','"
					. $tmp_var
					. '\');"';
				}
				$texte .= "<a href=\"$script\"$on>$deb</a>";
			}
		}
	}
	
	return $texte;
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

	$texte = "\n<div style='position: relative;$ie_style;' class='arial1 tranches' id='a$ancre'>";

	$texte .= navigation_pagination($num_rows, $nb_aff, $url, $onclick=true, $tmp_var);

	$on ='';

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

// $fg et $bg ne sont plus utilisees
// http://doc.spip.org/@affiche_tranche_bandeau
function affiche_tranche_bandeau($requete, $icone, $fg, $bg, $tmp_var,  $titre, $force, $largeurs, $styles, $skel, $own='')
{
	global $spip_display ;
	$res = "";

	$voir_logo = ($spip_display != 1 AND $spip_display != 4 AND isset($GLOBALS['meta']['image_process'])) ? ($GLOBALS['meta']['image_process'] != "non") : false;

	if (!isset($requete['GROUP BY'])) $requete['GROUP BY'] = '';

	$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM " . $requete['FROM'] . ($requete['WHERE'] ? (' WHERE ' . $requete['WHERE']) : '') . ($requete['GROUP BY'] ? (' GROUP BY ' . $requete['GROUP BY']) : '')));

	if (! (($cpt = $cpt['n']) OR $force)) return '';

	if (isset($requete['LIMIT'])) $cpt = min($requete['LIMIT'], $cpt);

	$deb_aff = intval(_request($tmp_var));
	$nb_aff = floor(1.5 * _TRANCHES);

	$tranches = "";
	if ($cpt > $nb_aff) {
		$nb_aff = (_TRANCHES); 
		$tranches = afficher_tranches_requete($cpt, $tmp_var, '', $nb_aff);
	}

	$result = spip_query($u = "SELECT " . (isset($requete["SELECT"]) ? $requete["SELECT"] : "*") . " FROM " . $requete['FROM'] . ($requete['WHERE'] ? (' WHERE ' . $requete['WHERE']) : '') . ($requete['GROUP BY'] ? (' GROUP BY ' . $requete['GROUP BY']) : '') . ($requete['ORDER BY'] ? (' ORDER BY ' . $requete['ORDER BY']) : '') . " LIMIT " . ($deb_aff >= 0 ? "$deb_aff, $nb_aff" : ($requete['LIMIT'] ? $requete['LIMIT'] : "99999")));
	$id_liste = 't'.substr(md5($u),0,8);
	$bouton = bouton_block_depliable($titre,true,$id_liste);

	$table = array();
	while ($row = spip_fetch_array($result)) {
		if ($a = $skel($row, $tous_id, $voir_logo, $own))
			$table[] = $a;
	}
	spip_free_result($result);

	$t = afficher_liste($largeurs, $table, $styles);
	if ($spip_display != 4)
	  $t = "<table width='100%' cellpadding='2' cellspacing='0' border='0'>"
	    . $t
	    . "</table>";
	return
	  debut_cadre('liste',$icone,'',$bouton)
	  . debut_block_depliable(true,$id_liste)
	  . $tranches
	  . $t
	  . fin_block()
	  . fin_cadre();
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

// http://doc.spip.org/@avoir_visiteurs
function avoir_visiteurs() {

	if ($GLOBALS['meta']["forums_publics"] == 'abo') return true;
	if ($GLOBALS['meta']['accepter_visiteurs'] == 'oui') return true;
	$n = spip_query("SELECT COUNT(*) AS n FROM spip_articles WHERE accepter_forum='abo' LIMIT 1");
	$n = spip_fetch_array($n);
	return $n['n'];
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
		  . generer_url_ecrire("forum_envoi", "statut=$statut&id_parent=$id_forum&titre_message=$tm&script=" . urlencode("$retour?$arg")) . '#formulaire'
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
	
	// CSS calendrier
	. '<link rel="stylesheet" type="text/css" href="'
	. find_in_path('agenda.css') .'" />' . "\n"
	
	// CSS espace prive : la vraie
	. '<link rel="stylesheet" type="text/css" href="'
	. generer_url_public('style_prive', $paramcss) .'" />' . "\n"
  . "<!--[if lt IE 8]>\n"
  . '<link rel="stylesheet" type="text/css" href="'
  . generer_url_public('style_prive_ie', $paramcss) .'" />' . "\n"
  . "<![endif]-->\n"
  
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

// Fonction standard pour le pipeline 'boite_infos'
// http://doc.spip.org/@f_boite_infos
function f_boite_infos($flux) {
	$boite = $flux['data'];
	$args = $flux['args'];
	$type = $args['type'];
	$id = $args['id'];
	$row = $args['row'];

	if ($type == 'article') {
		$boite .= "\n<div style='font-weight: bold; text-align: center' class='verdana1 spip_xx-small'>" 
		. _T('info_numero_article')
		.  "<br /><span class='spip_xx-large'>"
		.  $id
		.  '</span></div>';
	}

	$boite .= voir_en_ligne($type, $id, $row['statut'], 'racine-24.gif', false);

	// statistiques
	if ($type == 'article') {
		if ($row['statut'] == 'publie'
		AND $row['visites'] > 0
		AND $GLOBALS['meta']["activer_statistiques"] != "non"
		AND autoriser('voirstats', $type, $id)) {
			$boite .= icone_horizontale(_T('icone_evolution_visites', array('visites' => $row['visites'])), generer_url_ecrire("statistiques_visites","id_article=$id"), "statistiques-24.gif","rien.gif", false);
		}
	}

	// revisions d'articles
	if ($type == 'article') {
		if (($GLOBALS['meta']["articles_versions"]=='oui')
		AND $row['id_version']>1
		AND autoriser('voirrevisions', $type, $id))
			$boite .= icone_horizontale(_T('info_historique_lien'), generer_url_ecrire("articles_versions","id_article=$id"), "historique-24.gif", "rien.gif", false);
	}

	$flux['data'] = $boite;
	return $flux;
}


// http://doc.spip.org/@gros_titre
function gros_titre($titre, $ze_logo='', $aff=true){
	global $spip_display;
	$res = "\n<h1>";
	if ($spip_display != 4) {
		$res .= $ze_logo.' ';
	}
	$res .= typo($titre)."</h1>\n";
	if ($aff) echo $res; else return $res;
}


//
// Cadre centre (haut de page)
//

// http://doc.spip.org/@debut_grand_cadre
function debut_grand_cadre($return=false){
	$res =  "\n<br /><br />\n<div class='table_page'>\n";
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
	global $spip_ecran, $spip_lang_rtl, $spip_lang_left;

	// div navigation fermee par creer_colonne_droite qui ouvre
	// div extra lui-meme ferme par debut_droite qui ouvre 
	// div contenu lui-meme ferme par fin_gauche() ainsi que
	// div conteneur

	$res = "<br /><div id='conteneur'>
		\n<div id='navigation'>\n";
		
	if ($spip_display == 4) $res .= "<!-- ";

	if ($return) return $res; else echo $res;
}

// http://doc.spip.org/@fin_gauche
function fin_gauche()
{
	return "</div></div><br class='nettoyeur' />";
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

	$res = "\n</div><div id='extra'>";

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

	$res .= creer_colonne_droite($rubrique, true)
	. "</div>";

	$res .= "\n<div id='contenu' class='serif'>";

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
			$res .= 
				debut_cadre('bandeau-rubriques',"article-24.gif",'',_T('info_cours_edition'))
				. "\n<div class='plan-articles-bloques'>";
			foreach ($articles_ouverts as $row) {
				$ze_article = $row['id_article'];
				$ze_titre = $row['titre'];
				$statut = $row["statut"];

				$res .= "\n<div class='$statut'>"
				. "\n<div style='float:right; '>"
				. debloquer_article($ze_article,_T('lien_liberer'))
				. "</div>"
				. "<a  href='" 
				. generer_url_ecrire("articles","id_article=$ze_article")
				. "'>$ze_titre</a>"
				. "</div>";
			}

			if (count($articles_ouverts) >= 4) {
				$res .= "\n<div style='text-align:right; '>"
				. debloquer_article('tous', _T('lien_liberer_tous'))
				. "</div>";
			}
			$res .= fin_cadre('bandeau-rubriques') . "</div>";
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

	// avec &var_profile=1 on a le tableau de mesures SQL
	if (@count($GLOBALS['tableau_des_temps'])) {
		include_spip('public/debug');
		$chrono = chrono_requete($GLOBALS['tableau_des_temps']);
	} else $chrono = '';

	return debut_grand_cadre(true)
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
	. "</div>\n" // cf. div centered ouverte dans conmmencer_page()
	. $GLOBALS['rejoue_session']
	. '<div style="background-image: url(\''
	. generer_url_action('cron')
	. '\');"></div>'
	. (defined('_TESTER_NOSCRIPT') ? _TESTER_NOSCRIPT : '')
	. $chrono
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

	while($row = spip_fetch_array($voss)) {
		$id = $row['id'];
		$num = afficher_numero_edit($id, $key, $type);
		$statut = $row['statut'];
		$statut = $fstatut($id, $statut, $id_rubrique, $type);
		$href = "<a class='verdana1' href='"
		. generer_url_ecrire($type=='article' ? $table : 'breves_voir',"$key=$id")
		. "'>"
		. sinon(typo($row['titre']), _T('info_sans_titre'))
		. "</a>";
		$retour .= "<tr class='tr_liste' style='background-color: #e0e0e0;'><td>$statut</td><td>$href</td><td style='width: 25%;'>$num</td></tr>";
	}

	$icone =  '<b>' . _T('info_meme_rubrique')  . '</b>';
	$bouton = bouton_block_depliable(_T('info_meme_rubrique'),true,'memerub');	

	$retour = 
		debut_cadre('meme-rubriques',"article-24.gif",'',$bouton)
		. debut_block_depliable(true,'memerub')
		. "\n<table style='background-color: #e0e0e0;border: 0px; padding-left:4px; width: 100%;'>"
		. $retour;
	

	//	$retour .= (($limit <= 0) ? '' : "<tr><td colspan='3' style='text-align: center'>+ $limit</td></tr>");

	$retour .= "</table>"
		. fin_block()
		. fin_cadre('meme-rubriques');

	if ($ajax) return $retour;

	// id utilise dans puce_statut_article
	return "\n<div>&nbsp;</div>"
	. "\n<div id='imgstatut$idom$id_rubrique'>$retour</div>";
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
	
			$lib_bouton = (!acces_restreint_rubrique($id_rubrique) ? "" :
			   http_img_pack("admin-12.gif", '', " width='12' height='12'", _T('image_administrer_rubrique'))) .
			  " <span dir='$lang_dir'><a href='" . 
			  generer_url_ecrire("naviguer","id_rubrique=$id_rubrique") .
			  "'>".
			  typo($titre) .
			  "</a></span>";
			$les_enfants = "\n<div class='enfants'>" .
			  debut_cadre_sous_rub(($id_parent ? "rubrique-24.gif" : "secteur-24.gif"), true) .
			  (is_string($logo) ? $logo : '') .
			  bouton_block_depliable($lib_bouton,$les_sous_enfants ?false:-1,"enfants$id_rubrique") .
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
	$retour = debut_block_depliable(false,"enfants$collection2")."\n<ul style='margin: 0px; padding: 0px; padding-top: 3px;'>\n";
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
	global  $spip_lang_left,$spip_lang_right, $spip_display;
	
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
	. "<div style='float:$spip_lang_left;width:49%;position:relative;'>"
	. $les_enfants
	. "</div>"
	. "<div style='float:$spip_lang_right;width:49%;position:relative;'>"
	. $les_enfants2
	. "</div>"
	. "&nbsp;"
	. "<div style='float:"
	. $spip_lang_right
	. ";position:relative;'>"
	. (!$bouton ? ''
		 : (!$id_rubrique
		    ? icone(_T('icone_creer_rubrique'), generer_url_ecrire("rubriques_edit","new=oui&retour=nav"), "secteur-24.gif", "creer.gif",$spip_lang_right, false)
		    : icone(_T('icone_creer_sous_rubrique'), generer_url_ecrire("rubriques_edit","new=oui&retour=nav&id_parent=$id_rubrique"), "rubrique-24.gif", "creer.gif",$spip_lang_right,false)))
	. "</div>"
	. "<br class='nettoyeur' />";

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
	else if ($en_ligne == 'preview'
	AND autoriser('previsualiser'))
		$message = _T('previsualiser');
	else
		return '';

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
