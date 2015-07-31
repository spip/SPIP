<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation_mini');
include_spip('inc/agenda'); // inclut inc/layer, inc/texte, inc/filtre
include_spip('inc/boutons');
include_spip('inc/actions');
include_spip('inc/puce_statut');

define('_ACTIVER_PUCE_RAPIDE', true);
define('_SIGNALER_ECHOS', true);
define('_INTERFACE_ONGLETS', false);

// Faux HR, avec controle de couleur
// http://doc.spip.org/@hr
function hr($color, $retour = false) {
	$ret = "\n<div style='height: 1px; margin-top: 5px; padding-top: 5px; border-top: 1px solid $color;'></div>";

	if ($retour) return $ret; else echo_log('hr',$ret);
}

//
// Cadres
//
// http://doc.spip.org/@afficher_onglets_pages
function afficher_onglets_pages($ordre,$onglets){
	static $onglet_compteur = 0;
	$res = "";
	$corps = "";
	$cpt = 0;
	$actif = 0;
	// ordre des onglets
	foreach($ordre as $id => $label) {
		$cpt++;
		$disabled = strlen(trim($onglets[$id]))?"":" class='tabs-disabled'";
		if (!$actif && !$disabled) $actif = $cpt;
		$res .= "<li$disabled><a rel='$cpt' href='#$id'><span>" . $label . "</span></a></li>";
	}
	$res = "<ul class='tabs-nav'>$res</ul>";
	foreach((_INTERFACE_ONGLETS ? array_keys($ordre):array_keys($onglets)) as $id){
		$res .= "<div id='$id' class='tabs-container'>" . $onglets[$id] . "<br class='nettoyeur' /></div>";
	}
	$onglet_compteur++;
	return "<div class='boite_onglets' id='boite_onglet_$onglet_compteur'>$res</div>"
	. (_INTERFACE_ONGLETS ?
	   http_script("$('#boite_onglet_$onglet_compteur').tabs(".($actif?"$actif,":"")."{ fxAutoHeight: true });
	 if (!$.browser.safari)
	 $('ul.tabs-nav li').hover(
	 	function(){
	 		\$('#boite_onglet_$onglet_compteur').triggerTab(parseInt(\$(this).attr('rel')));
	 		return false;
	 	}
	 	,
	 	function(){}
	 	);")
	   :"");
}

// http://doc.spip.org/@debut_cadre
function debut_cadre($style, $icone = "", $fonction = "", $titre = "", $id="", $class="", $padding=true) {
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
		if ($icone_renommer = charger_fonction('icone_renommer','inc',true))
			list($icone,$fonction) = $icone_renommer($icone,$fonction);
		if ($fonction) {
			
			$ret .= http_img_pack("$fonction", "", " class='cadre-icone' ".http_style_background($icone, "no-repeat; padding: 0px; margin: 0px"));
		}
		else $ret .=  http_img_pack("$icone", "", " class='cadre-icone'");
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

	$ret .= "<div". ($padding ?" class='cadre_padding'" : '') .">";

	return $ret;
}

// http://doc.spip.org/@fin_cadre
function fin_cadre($style='') {

	$ret = "<div class='nettoyeur'></div></div>".
	"</div>\n";

	return $ret;
}


// http://doc.spip.org/@debut_cadre_relief
function debut_cadre_relief($icone='', $return = false, $fonction='', $titre = '', $id="", $class=""){
	$retour_aff = debut_cadre('r', $icone, $fonction, $titre, $id, $class);

	if ($return) return $retour_aff; else echo($retour_aff);
}

// http://doc.spip.org/@fin_cadre_relief
function fin_cadre_relief($return = false){
	$retour_aff = fin_cadre('r');

	if ($return) return $retour_aff; else echo($retour_aff);
}


// http://doc.spip.org/@debut_cadre_enfonce
function debut_cadre_enfonce($icone='', $return = false, $fonction='', $titre = '', $id="", $class=""){
	$retour_aff = debut_cadre('e', $icone, $fonction, $titre, $id, $class);

	if ($return) return $retour_aff; else echo($retour_aff);
}

// http://doc.spip.org/@fin_cadre_enfonce
function fin_cadre_enfonce($return = false){

	$retour_aff = fin_cadre('e');

	if ($return) return $retour_aff; else echo_log('fin_cadre_enfonce',$retour_aff);
}


// http://doc.spip.org/@debut_cadre_sous_rub
function debut_cadre_sous_rub($icone='', $return = false, $fonction='', $titre = '', $id="", $class=""){
	$retour_aff = debut_cadre('sous_rub', $icone, $fonction, $titre, $id, $class);
	if ($return) return $retour_aff; else echo_log('debut_cadre_sous_rub',$retour_aff);
}

// http://doc.spip.org/@fin_cadre_sous_rub
function fin_cadre_sous_rub($return = false){
	$retour_aff = fin_cadre('sous_rub');
	if ($return) return $retour_aff; else echo_log('fin_cadre_sous_rub',$retour_aff);
}

// http://doc.spip.org/@debut_cadre_couleur
function debut_cadre_couleur($icone='', $return = false, $fonction='', $titre='', $id="", $class=""){
	$retour_aff = debut_cadre('couleur', $icone, $fonction, $titre, $id, $class);

	if ($return) return $retour_aff; else echo_log('debut_cadre_couleur',$retour_aff);
}

// http://doc.spip.org/@fin_cadre_couleur
function fin_cadre_couleur($return = false){
	$retour_aff = fin_cadre('couleur');

	if ($return) return $retour_aff; else echo_log('fin_cadre_couleur',$retour_aff);
}


// http://doc.spip.org/@debut_cadre_couleur_foncee
function debut_cadre_couleur_foncee($icone='', $return = false, $fonction='', $titre='', $id="", $class=""){
	$retour_aff = debut_cadre('couleur-foncee', $icone, $fonction, $titre, $id, $class);

	if ($return) return $retour_aff; else echo_log('debut_cadre_couleur_foncee',$retour_aff);
}

// http://doc.spip.org/@fin_cadre_couleur_foncee
function fin_cadre_couleur_foncee($return = false){
	$retour_aff = fin_cadre('couleur-foncee');

	if ($return) return $retour_aff; else echo_log('fin_cadre_couleur_foncee',$retour_aff);
}

// http://doc.spip.org/@debut_cadre_trait_couleur
function debut_cadre_trait_couleur($icone='', $return = false, $fonction='', $titre='', $id="", $class=""){
	$retour_aff = debut_cadre('trait-couleur', $icone, $fonction, $titre, $id, $class);
	if ($return) return $retour_aff; else echo_log('debut_cadre_trait_couleur',$retour_aff);
}

// http://doc.spip.org/@fin_cadre_trait_couleur
function fin_cadre_trait_couleur($return = false){
	$retour_aff = fin_cadre('trait-couleur');

	if ($return) return $retour_aff; else echo_log('fin_cadre_trait_couleur',$retour_aff);
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
	if ($return) return $r; else echo_log('debut_boite_info',$r);
}

// http://doc.spip.org/@fin_boite_info
function fin_boite_info($return=false) {
	$r = fin_cadre('info');
	if ($return) return $r; else echo_log('fin_boite_info',$r);
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
			  http_img_pack(chemin_image("plus-info-16.png"), "+", "") ."</a>";
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

// http://doc.spip.org/@navigation_pagination
function navigation_pagination($num_rows, $nb_aff=10, $href=null, $debut, $tmp_var=null, $on='') {

	$texte = '';
	$self = parametre_url(self(), 'date', '');
	$deb_aff = intval($debut);

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
				if ($on) $on = generer_onclic_ajax($href, $tmp_var, $deb-1);
				$texte .= "<a href=\"$script\"$on>$deb</a>";
			}
		}
	}

	return $texte;
}

// http://doc.spip.org/@generer_onclic_ajax
function generer_onclic_ajax($url, $idom, $val)
{
	return "\nonclick=\"return charger_id_url('"
	  . parametre_url($url, $idom, $val)
	  . "','"
	  . $idom
	  . '\');"';
}

// http://doc.spip.org/@avoir_visiteurs
function avoir_visiteurs($past=false, $accepter=true) {
	if ($GLOBALS['meta']["forums_publics"] == 'abo') return true;
	if ($accepter AND $GLOBALS['meta']["accepter_visiteurs"] <> 'non') return true;
	if (sql_countsel('spip_articles', "accepter_forum='abo'"))return true;
	if (!$past) return false;
	return sql_countsel('spip_auteurs',  "statut NOT IN ('0minirezo','1comite', 'nouveau', '5poubelle')");
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
	if ($echo) echo_log('icone',$retour); else return $retour;
}

// http://doc.spip.org/@icone_inline
function icone_inline($texte, $lien, $fond, $fonction="", $align="", $ajax=false, $javascript=''){
	global $spip_display;
	if ($icone_renommer = charger_fonction('icone_renommer','inc',true))
		list($fond,$fonction) = $icone_renommer($fond,$fonction);

	if ($fonction == "del") {
		$style = 'icone36 danger';
	} else {
		$style = 'icone36';
		if (strlen($fonction) < 3) $fonction = "rien.gif";
	}
	$style .= " " . substr(basename($fond),0,-4);

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

	if ($align && $align!='center') $align = "float: $align; ";

	$icone = "<a style='$align' class='$style'"
	. $atts
	. (!$ajax ? '' : (' onclick=' . ajax_action_declencheur($lien,$ajax)))
	. $javascript
	. "\nhref='"
	. $lien
	. "'>"
	. $icone
	. (($spip_display == 3)	? '' : "<span>$texte</span>")
	  . "</a>\n";

	if ($align <> 'center') return $icone;
	$style = " style='text-align:center;'";
	return "<div$style>$icone</div>";
}

// http://doc.spip.org/@icone_horizontale
function icone_horizontale($texte, $lien, $fond = "", $fonction = "", $af = true, $javascript='') {
	global $spip_display;
	if ($icone_renommer = charger_fonction('icone_renommer','inc',true))
		list($fond,$fonction) = $icone_renommer($fond,$fonction);

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
		if ($fonction == "del")
			$retour = "\n<div class='danger'>$retour</div>";
	} else {
		$retour = "\n<li><a$lien>$texte</a></li>";
	}

	if ($af) echo_log('icone_horizontale',$retour); else return $retour;
}

// http://doc.spip.org/@icone_horizontale_display
function icone_horizontale_display($texte, $lien, $fond = "", $fonction = "", $af = true, $js='') {
	global $spip_display, $spip_lang_left;
	$img = icone_horizontale($texte, $lien, $fond, $fonction, $af, $js);
	if ($spip_display != 4)
		return "<div style='float: $spip_lang_left; width:140px;'>$img</div>\n";
	else return "<ul>$img</ul>";
}


// http://doc.spip.org/@gros_titre
function gros_titre($titre, $ze_logo='', $aff=true){
	global $spip_display;
	$res = "\n<h1 class='grostitre'>";
	if ($spip_display != 4) {
		$res .= $ze_logo.' ';
	}
	$res .= typo($titre)."</h1>\n";
	if ($aff) echo_log('gros_titre',$res); else return $res;
}



// Cadre formulaires

// http://doc.spip.org/@debut_cadre_formulaire
function debut_cadre_formulaire($style='', $return=false){
	$x = "\n<div class='cadre-formulaire'" .
	  (!$style ? "" : " style='$style'") .
	   ">";
	if ($return) return  $x; else echo_log('debut_cadre_formulaire',$x);
}

// http://doc.spip.org/@fin_cadre_formulaire
function fin_cadre_formulaire($return=false){
	if ($return) return  "</div>\n"; else echo_log('fin_cadre_formulaire', "</div>\n");
}


// http://doc.spip.org/@formulaire_recherche
function formulaire_recherche($page, $complement=""){
	$recherche = _request('recherche');
	$recherche_aff = entites_html($recherche);
	if (!strlen($recherche)) {
		$recherche_aff = _T('info_rechercher');
		$onfocus = " onfocus=\"this.value='';\"";
	} else $onfocus = '';

	$form = '<input type="text" size="10" value="'.$recherche_aff.'" name="recherche" class="recherche" accesskey="r"' . $onfocus . ' />';
	$form .= "<input type='image' src='" . chemin_image('loupe.png') . "' name='submit' class='submit' alt='"._T('info_rechercher')."' />";
	return "<div class='spip_recherche'>".generer_form_ecrire($page, $form . $complement, " method='get'")."</div>";
}


//
// Afficher la hierarchie des rubriques
//

// http://doc.spip.org/@afficher_hierarchie
function afficher_hierarchie($id_parent, $message='',$id_objet=0,$type='',$id_secteur=0,$restreint='') {
	global $spip_lang_left,$spip_lang_right;

	$out = "";
	$nav = "";
 	if ($id_objet) {
 		# desactiver le selecteur de rubrique sur le chemin
 		# $nav = chercher_rubrique($message,$id_objet, $id_parent, $type, $id_secteur, $restreint,true);
 		$nav = $nav ?"<div class='none'>$nav</div>":"";
 	}

	$parents = '';
	$style1 = "$spip_lang_left center no-repeat; padding-$spip_lang_left: 15px";
	$style2 = "margin-$spip_lang_left: 15px;";
	$tag = "a";
	$on = ' on';

	$id_rubrique = $id_parent;
	while ($id_rubrique) {

		$res = sql_fetsel("id_parent, titre, lang", "spip_rubriques", "id_rubrique=".intval($id_rubrique));

		if (!$res){  // rubrique inexistante
			$id_rubrique = 0;
			break;
		}

		$id_parent = $res['id_parent'];
		changer_typo($res['lang']);

		$class = (!$id_parent) ? "secteur"
		: (acces_restreint_rubrique($id_rubrique)
		? "admin" : "rubrique");

		$parents = "<ul><li><span class='bloc'><em> &gt; </em><$tag class='$class$on'"
		. ($tag=='a'?" href='". generer_url_ecrire("naviguer","id_rubrique=$id_rubrique")."'":"")
		. ">"
		. supprimer_numero(typo(sinon($res['titre'], _T('ecrire:info_sans_titre'))))
		. "</$tag></span>"
		. $parents
		. "</li></ul>";

		$id_rubrique = $id_parent;
		$tag = 'a';
		$on = '';
	}

	$out .=  $nav
		. "\n<ul id='chemin' class='verdana3' dir='".lang_dir()."'"
	  //. http_style_background("racine-site-12.gif", $style1)
	  . "><li><span class='bloc'><$tag class='racine$on'"
		. ($tag=='a'?" href='". generer_url_ecrire("naviguer","id_rubrique=$id_rubrique")."'":"")
	  . ">"._T('info_racine_site')."</$tag>"
 	  . "</span>"
	  . $parents
 	  . aide ("rubhier")
 	  . "</li></ul>"
 	  . ($nav?
 	    "&nbsp;<a href='#' onclick=\"$(this).prev().prev().toggle('fast');return false;\" class='verdana2'>"
 	    . _T('bouton_changer') ."</a>"
 	    :"");

	$out = pipeline('affiche_hierarchie',array('args'=>array(
			'id_parent'=>$id_parent,
			'message'=>$message,
			'id_objet'=>$id_objet,
			'objet'=>$type,
			'id_secteur'=>$id_secteur,
			'restreint'=>$restreint),
			'data'=>$out));

 	return $out;
}

// Pour construire des menu avec SELECTED
// http://doc.spip.org/@mySel
function mySel($varaut,$variable, $option = NULL) {
	$res = ' value="'.$varaut.'"' . (($variable==$varaut) ? ' selected="selected"' : '');

	return  (!isset($option) ? $res : "<option$res>$option</option>\n");
}


// http://doc.spip.org/@bouton_spip_rss
function bouton_spip_rss($op, $args=array(), $lang='') {

	global $spip_lang_right;
	include_spip('inc/acces');
	$clic = http_img_pack('feed.png', 'RSS', '', 'RSS');
	$args = param_low_sec($op, $args, $lang, 'rss');
	$url = generer_url_public('rss', $args);
	return "<a style='float: $spip_lang_right;' href='$url'>$clic</a>";
}
?>