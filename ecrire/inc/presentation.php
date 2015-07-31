<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/presentation_mini');
include_spip('inc/layer');
include_spip('inc/texte');
include_spip('inc/filtres');
include_spip('inc/boutons');
include_spip('inc/actions');
include_spip('inc/puce_statut');
include_spip('inc/filtres_ecrire');

define('_ACTIVER_PUCE_RAPIDE', true);

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
		//$style_cadre .= "margin-top: 20px;'";
		$style_cadre .= "'";
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


function debut_cadre_relief($icone='', $dummy='', $fonction='', $titre = '', $id="", $class=""){return debut_cadre('r', $icone, $fonction, $titre, $id, $class);}
function fin_cadre_relief(){return fin_cadre('r');}
function debut_cadre_enfonce($icone='', $dummy='', $fonction='', $titre = '', $id="", $class=""){return debut_cadre('e', $icone, $fonction, $titre, $id, $class);}
function fin_cadre_enfonce(){return fin_cadre('e');}
function debut_cadre_sous_rub($icone='', $dummy='', $fonction='', $titre = '', $id="", $class=""){return debut_cadre('sous_rub', $icone, $fonction, $titre, $id, $class);}
function fin_cadre_sous_rub(){return fin_cadre('sous_rub');}
function debut_cadre_couleur($icone='', $dummy='', $fonction='', $titre='', $id="", $class=""){return debut_cadre('couleur', $icone, $fonction, $titre, $id, $class);}
function fin_cadre_couleur(){return fin_cadre('couleur');}
function debut_cadre_couleur_foncee($icone='', $dummy='', $fonction='', $titre='', $id="", $class=""){return debut_cadre('couleur-foncee', $icone, $fonction, $titre, $id, $class);}
function fin_cadre_couleur_foncee(){return fin_cadre('couleur-foncee');}
function debut_cadre_trait_couleur($icone='', $dummy='', $fonction='', $titre='', $id="", $class=""){return debut_cadre('trait-couleur', $icone, $fonction, $titre, $id, $class);}
function fin_cadre_trait_couleur(){return fin_cadre('trait-couleur');}
function debut_boite_alerte() {return debut_cadre('alerte', '', '', '', '', '');}
function fin_boite_alerte() {return fin_cadre('alerte');}
function debut_boite_info() {return debut_cadre('info', '', '', '', '', '');}
function fin_boite_info($return=false) {return fin_cadre('info');}

// http://doc.spip.org/@gros_titre
function gros_titre($titre, $ze_logo=''){return "<h1 class='grostitre'>" . $ze_logo.' ' . typo($titre)."</h1>\n";}

// La boite des raccourcis
// Se place a droite si l'ecran est en mode panoramique.
// http://doc.spip.org/@bloc_des_raccourcis
function bloc_des_raccourcis($bloc) {
	return creer_colonne_droite()
	. debut_cadre_enfonce('','','',"<h3>"._T('titre_cadre_raccourcis')."</h3>")
	. $bloc
	. fin_cadre_enfonce();
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

// Fonctions onglets
// http://doc.spip.org/@debut_onglet
function debut_onglet(){return "<div class='barre_onglet'><ul class='clearfix'>\n";}
// http://doc.spip.org/@fin_onglet
function fin_onglet(){return "</ul></div>\n";}
// http://doc.spip.org/@onglet
function onglet($texte, $lien, $onglet_ref, $onglet, $icone=""){
	global $spip_display, $spip_lang_left ;

	return "<li class='box_onglet'>"
	 . ($icone?http_img_pack($icone, '', " class='cadre-icone'"):'')
	 . lien_ou_expose($lien,$texte,$onglet == $onglet_ref)
	 . "</li>";
}

// http://doc.spip.org/@icone_inline
function icone_verticale($texte, $lien, $fond, $fonction="", $align="", $javascript=""){
	// cas d'ajax_action_auteur: faut defaire le boulot
	// (il faudrait fusionner avec le cas $javascript)
	if (preg_match(",^<a\shref='([^']*)'([^>]*)>(.*)</a>$,i",$lien,$r)) {
		list($x,$lien,$atts,$texte)= $r;
		$javascript .= $atts;
	}

	return icone_base($lien, $texte, $fond, $fonction,"verticale $align",$javascript);
}

// http://doc.spip.org/@icone_horizontale
function icone_horizontale($texte, $lien, $fond, $fonction="", $dummy="", $javascript="") {
	$retour = '';
	// cas d'ajax_action_auteur: faut defaire le boulot
	// (il faudrait fusionner avec le cas $javascript)
	if (preg_match(",^<a\shref='([^']*)'([^>]*)>(.*)</a>$,i",$lien,$r)) {
		list($x,$lien,$atts,$texte)= $r;
		$javascript .= $atts;
	}

	$retour = icone_base($lien, $texte, $fond, $fonction,"horizontale",$javascript);
	return $retour;
}

?>