<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
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
include_spip('inc/filtres_boites');

// http://doc.spip.org/@debut_cadre
function debut_cadre($style, $icone = "", $fonction = "", $titre = "", $id="", $class="", $padding=true) {
	$style_mapping=array('r'=>'simple','e'=>'raccourcis','couleur'=>'basic highlight','couleur-foncee'=>'basic highlight','trait-couleur'=>'important','alerte'=>'notice','info'=>'info','sous_rub'=>'simple sous-rub');
	$style_titre_mapping=array('couleur'=>'topper','trait-couleur'=>'section');
	$c = isset($style_mapping[$style])?$style_mapping[$style]:'simple';
	$class = $c . ($class?" $class":"");
	if (!$padding)
		$class .= ($class?" ":"")."no-padding";

	//($id?"id='$id' ":"")
	if (strlen($icone) > 1) {
		if ($icone_renommer = charger_fonction('icone_renommer','inc',true))
			list($fond,$fonction) = $icone_renommer($icone,$fonction);
		$size = 24;
		if (preg_match("/-([0-9]{1,3})[.](gif|png)$/i",$fond,$match))
			$size = $match[1];
		if ($fonction){
			// 2 images pour composer l'icone : le fond (article) en background,
			// la fonction (new) en image
			$icone = http_img_pack($fonction, "", "class='cadre-icone' width='$size' height='$size'\n" .
						http_style_background($fond, "no-repeat center center"));
		}
		else {
			$icone = http_img_pack($fond, "", "class='cadre-icone' width='$size' height='$size'");
		}
		$titre = $icone . $titre;
	}
	return boite_ouvrir($titre, $class,isset($style_titre_mapping[$style])?$style_titre_mapping[$style]:'',$id);
}

// http://doc.spip.org/@fin_cadre
function fin_cadre() {return boite_fermer();}


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
function fin_boite_info() {return fin_cadre('info');}

// http://doc.spip.org/@gros_titre
function gros_titre($titre, $ze_logo=''){return "<h1 class='grostitre'>" . $ze_logo.' ' . typo($titre)."</h1>\n";}

// La boite des raccourcis
// Se place a droite si l'ecran est en mode panoramique.
// http://doc.spip.org/@bloc_des_raccourcis
function bloc_des_raccourcis($bloc) {
	return creer_colonne_droite()
	  . boite_ouvrir(_T('titre_cadre_raccourcis'),'raccourcis') . $bloc . boite_fermer();
}

// Compatibilite
// http://doc.spip.org/@afficher_plus
function afficher_plus($lien) {include_spip('inc/filtres_ecrire');afficher_plus_info($lien);}



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
// @param string $sous_classe	prend la valeur second pour definir les onglet de deuxieme niveau
function debut_onglet($classe="barre_onglet"){return "<div class='$classe clearfix'><ul>\n";}
// http://doc.spip.org/@fin_onglet
function fin_onglet(){return "</ul></div>\n";}
// http://doc.spip.org/@onglet
function onglet($texte, $lien, $onglet_ref, $onglet, $icone=""){
	return "<li>"
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