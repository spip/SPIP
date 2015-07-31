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

//
// Presentation de l'interface privee, debut du HTML
//

// http://doc.spip.org/@inc_commencer_page_dist
function inc_commencer_page_dist($titre = "", $rubrique = "accueil", $sous_rubrique = "accueil", $id_rubrique = "",$menu=true,$minipres=false, $alertes = true) {
	global $connect_id_auteur;

	include_spip('inc/headers');

	http_no_cache();

	return init_entete($titre, $id_rubrique, $minipres)
	. init_body($rubrique, $sous_rubrique, $id_rubrique,$menu)
	. "<div id='page'>"
	. auteurs_recemment_connectes($connect_id_auteur)
	. ($alertes?alertes_auteur($connect_id_auteur):'')
	. '<div class="largeur">';
}

// envoi du doctype et du <head><title>...</head>
// http://doc.spip.org/@init_entete
function init_entete($titre='', $dummy=0, $minipres=false) {
	include_spip('inc/texte');
	if (!$nom_site_spip = textebrut(typo($GLOBALS['meta']["nom_site"])))
		$nom_site_spip=  _T('info_mon_site_spip');

	$titre = "["
		. $nom_site_spip
		. "]" 
	  . ($titre ? " ".textebrut(typo($titre)):"");

	return _DOCTYPE_ECRIRE
	. html_lang_attributes()
	. "<head>\n"
	. init_head($titre, $dummy, $minipres)
	. "</head>\n";
}

function init_head($titre='', $dummy=0, $minipres=false) {
	return recuperer_fond("prive/squelettes/head/dist",array('titre'=>$titre,'minipres'=>$minipres?' ':''));
}

// fonction envoyant la double serie d'icones de redac
// http://doc.spip.org/@init_body
function init_body($rubrique='accueil', $sous_rubrique='accueil', $id_rubrique='',$menu=true) {
	global $connect_id_auteur, $auth_can_disconnect;

	$res = pipeline('body_prive',"<body class='"
			. init_body_class()." "._request('exec')."'"
			. ($GLOBALS['spip_lang_rtl'] ? " dir='rtl'" : "")
			.'>');

	if (!$menu) return $res;


	$bandeau = charger_fonction('bandeau', 'inc');

	return $res
	 . $bandeau();
}

function init_body_class() {
	$GLOBALS['spip_display'] = isset($GLOBALS['visiteur_session']['prefs']['display'])
		? $GLOBALS['visiteur_session']['prefs']['display']
		: 2;
	$spip_display_navigation = isset($GLOBALS['visiteur_session']['prefs']['display_navigation'])
		? $GLOBALS['visiteur_session']['prefs']['display_navigation']
		: 'navigation_avec_icones';
	$spip_display_outils = isset($GLOBALS['visiteur_session']['prefs']['display_outils'])
		? ($GLOBALS['visiteur_session']['prefs']['display_outils']?'navigation_avec_outils':'navigation_sans_outils')
		: 'navigation_avec_outils';
	$GLOBALS['spip_ecran'] = isset($_COOKIE['spip_ecran']) ? $_COOKIE['spip_ecran'] : "etroit";

	$display_class=array(0=>'icones_img_texte'/*init*/,1=>'icones_texte',2=>'icones_img_texte',3=>'icones_img');
	return $GLOBALS['spip_ecran'] . " $spip_display_navigation $spip_display_outils ".$display_class[$GLOBALS['spip_display']];
}


// http://doc.spip.org/@lien_change_var
function lien_change_var($lien, $set, $couleur, $coords, $titre, $mouseOver="") {
	$lien = parametre_url($lien, $set, $couleur);
	return "\n<area shape='rect' href='$lien' coords='$coords' title=\"$titre\" alt=\"$titre\" $mouseOver />";
}

// http://doc.spip.org/@auteurs_recemment_connectes
function auteurs_recemment_connectes($id_auteur){
	return recuperer_fond('prive/objets/liste/auteurs_enligne');
}

?>
