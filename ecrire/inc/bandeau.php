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

include_spip('inc/boutons');

function definir_barre_contexte(){
	$contexte = $_GET;
	if (!isset($contexte['id_rubrique'])){
		foreach(array('article','site','breve') as $type) {
			$_id = id_table_objet($type);
			if ($id = _request($_id,$contexte)){
				$table = table_objet_sql($type);
				$id_rubrique = sql_getfetsel('id_rubrique',$table,"$_id=".intval($id));
				$contexte['id_rubrique'] = $id_rubrique;
				continue;
			}
		}
	}
	return $contexte;
}


function boutons_parse($arbre){
	$ret = array('bouton'=>array(),'onglet'=>array());
	// recuperer les boutons et onglets si necessaire
	spip_xml_match_nodes(",^(bouton|onglet)\s,",$arbre,$les_boutons);
	if (is_array($les_boutons) && count($les_boutons)){
		$ret['bouton'] = array();
		$ret['onglet'] = array();
		foreach($les_boutons as $bouton => $val) {
			$bouton = spip_xml_decompose_tag($bouton);
			$type = reset($bouton);
			$bouton = end($bouton);
			if (isset($bouton['id'])){
				$id = $bouton['id'];
				$val = reset($val);
				if(is_array($val)){
					$ret[$type][$id]['parent'] = isset($bouton['parent'])?$bouton['parent']:'';
					$ret[$type][$id]['position'] = isset($bouton['position'])?$bouton['position']:'';
					$ret[$type][$id]['titre'] = isset($val['titre'])?trim(spip_xml_aplatit($val['titre'])):'';
					$ret[$type][$id]['icone'] = isset($val['icone'])?trim(end($val['icone'])):'';
					$ret[$type][$id]['url'] = isset($val['url'])?trim(end($val['url'])):'';
					$ret[$type][$id]['args'] = isset($val['args'])?trim(end($val['args'])):'';
				}
			}
		}
	}
	return $ret;
}

/**
 * Construire le tableau qui correspond aux boutons du core
 * decrits dans prive/navigation.xml
 *
 */
function boutons_core($type='bouton'){
	static $ret=null;
	if (!in_array($type,array('bouton','onglet')))
		return array();
	if (
		!is_array($ret)
		/*OR $GLOBALS['var_mode']='recalcul'*/){
		include_spip('inc/xml');
		$xml = spip_xml_load(find_in_path("prive/navigation.xml"));
		$ret = boutons_parse($xml);
	}

	return $ret[$type];
}

/**
 * definir la liste des boutons du haut et de ses sous-menus
 * On defini les boutons a metrtre selon les droits de l'utilisateur
 * puis on balance le tout au pipeline "ajouter_boutons" pour que des plugins
 * puissent y mettre leur grain de sel
 *
 * @param array $contexte
 * @param bool $icones // rechercher les icones
 * @param bool $autorise // ne renvoyer que les boutons autorises
 * @return array
 */
function definir_barre_boutons($contexte=array(),$icones = true, $autorise = true) {
	include_spip('inc/autoriser');
	$boutons_admin=array();

	// les boutons du core, issus de prive/navigation.xml
	$liste_boutons = boutons_core();

	// ajouter les boutons issus des plugin via plugin.xml
	if (function_exists('boutons_plugins')
	  AND is_array($liste_boutons_plugins = boutons_plugins()))
		$liste_boutons = $liste_boutons + $liste_boutons_plugins;

	foreach($liste_boutons as $id => $infos){
		// les boutons principaux ne sont pas soumis a autorisation
		if (!($parent = $infos['parent']) OR !$autorise OR autoriser('bouton',$id,0,NULL,array('contexte'=>$contexte))){
			if ($parent AND isset($boutons_admin[$parent])){
				if (!is_array($boutons_admin[$parent]->sousmenu))
					$boutons_admin[$parent]->sousmenu = array();
				$position = $infos['position']?$infos['position']:count($boutons_admin[$parent]->sousmenu);
				$boutons_admin[$parent]->sousmenu = array_slice($boutons_admin[$parent]->sousmenu,0,$position)
				+ array($id=> new Bouton(
					($icones AND $infos['icone'])?find_in_theme($infos['icone']):'',  // icone
					$infos['titre'],	// titre
					$infos['url']?$infos['url']:null,
					$infos['args']?$infos['args']:null
					))
				+ array_slice($boutons_admin[$parent]->sousmenu,$position,100);
			}
			if (!$parent
			// provisoire, eviter les vieux boutons
			AND (!in_array($id,array('forum','statistiques_visites')))

			) {
				$position = $infos['position']?$infos['position']:count($boutons_admin);
				$boutons_admin = array_slice($boutons_admin,0,$position)
				+array($id=> new Bouton(
					($icones AND $infos['icone'])?find_in_theme($infos['icone']):'',  // icone
					$infos['titre'],	// titre
					$infos['url']?$infos['url']:null,
					$infos['args']?$infos['args']:null
					))
				+ array_slice($boutons_admin,$position,100);
			}
		}
	}

	return pipeline('ajouter_boutons', $boutons_admin);
}

/**
 * Creer l'url a partir de exec et args, sauf si c'est deja une url formatee
 *
 * @param string $url
 * @param string $args
 * @return string
 */
// http://doc.spip.org/@bandeau_creer_url
function bandeau_creer_url($url, $args="", $contexte=null){
	if (!preg_match(',[\/\?],',$url)) {
		$url = generer_url_ecrire($url,$args,true);
		// recuperer les parametres du contexte demande par l'url sous la forme
		// &truc=@machin@
		// @machin@ etant remplace par _request('machin')
		$url = str_replace('&amp;','&',$url);
		while (preg_match(",[&?]([a-z_]+)=@([a-z_]+)@,i",$url,$matches)){
			$val = _request($matches[2],$contexte);
			$url = parametre_url($url,$matches[1],$val?$val:'','&');
		}
		$url = str_replace('&','&amp;',$url);
	}
	return $url;
}

/**
 * Lister le contenu d'un sous menu dans des elements li de class $class
 *
 * @param array $sousmenu
 * @param string $class
 * @return string
 */
function bando_lister_sous_menu($sousmenu,$contexte=null,$class="",$image=false){
	$class = $class ? " class='$class'":"";
	$sous = "";
	if (is_array($sousmenu)){
		$sous = "";
		foreach($sousmenu as $souspage => $sousdetail){
			$url = bandeau_creer_url($sousdetail->url?$sousdetail->url:$souspage, $sousdetail->urlArg, $contexte);
			if (!$image){
					$sous .= "<li$class>"
			 . "<a href='$url' id='bando2_$souspage'>"
			 . _T($sousdetail->libelle)
			 . "</a>"
			 . "</li>";
			}
			else {
					//$image = "<img src='".$sousdetail->icone."' width='".largeur($sousdetail->icone)."' height='".hauteur($sousdetail->icone)."' alt='".attribut_html(_T($sousdetail->libelle))."' />";
					$sous .= "<li$class>"
			 . "<a href='$url' id='bando2_$souspage' title='".attribut_html(_T($sousdetail->libelle))."'>"
			 . "<span>"._T($sousdetail->libelle)."</span>"
			 . "</a>"
			 . "</li>";
			}
		}
	}
	return $sous;
}

/**
 * Construire le bandeau de navigation principale de l'espace prive
 * a partir de la liste des boutons definies dans un tableau d'objets
 *
 * @param array $boutons
 * @return string
 */
function bando_navigation($boutons, $contexte = array())
{
	$res = "";

	$first = " class = 'first'";
	foreach($boutons as $page => $detail){
        // les outils rapides sont traites a part, dans une barre dediee
        if (!in_array($page,array('outils_rapides','outils_collaboratifs'))){

            // les icones de premier niveau sont ignoree si leur sous menu est vide
            // et si elles pointent vers exec=navigation
            if (
             ($detail->libelle AND is_array($detail->sousmenu) AND count($detail->sousmenu))
             OR ($detail->libelle AND $detail->url AND $detail->url!='navigation')) {
                $url = bandeau_creer_url($detail->url?$detail->url:$page, $detail->urlArg,$contexte);
                $res .= "<li$first>"
                 . "<a href='$url' id='bando1_$page'>"
                 . _T($detail->libelle)
                 . "</a>";
            }

            $sous = bando_lister_sous_menu($detail->sousmenu, $contexte);
            $res .= $sous ? "<ul>$sous</ul>":"";

            $res .= "</li>";
            $first = "";
        }
	}

	return "<div id='bando_navigation'><div class='largeur'><ul class='deroulant'>\n$res</ul><div class='nettoyeur'></div></div></div>";
}

/**
 * Construire le bandeau identite de l'espace prive
 *
 * @return unknown
 */
function bando_identite(){

	$nom_site = typo($GLOBALS['meta']['nom_site']);
	$img_info = find_in_theme('images/information-16.png');
	$url_config_identite = generer_url_ecrire('config_identite');

	$res = "";

	$moi = typo($GLOBALS['visiteur_session']['nom']);
	$img_langue = find_in_theme('images/langues.png');
	$url_aide = generer_url_ecrire('aide_index',"var_lang=".$GLOBALS['spip_lang']);
	$url_lang = generer_url_ecrire('config_langage');

	$res .= "<p class='session'>"
	  . "<a title='"._T('icone_informations_personnelles')."' href='".
	  //generer_url_ecrire("auteur_infos","id_auteur=".$GLOBALS['visiteur_session']['id_auteur'])
	  generer_url_ecrire("infos_perso")
	  ."'>"
	  . "<strong class='nom'>$moi</strong>"
	  . " <img alt='"._T('icone_informations_personnelles')."' src='$img_info'/></a>"
	  . "| "
	  . "<a class='menu_lang' href='$url_lang' title='"._T('titre_config_langage')."'><img alt='"._T('titre_config_langage')."' src='$img_langue'/>".traduire_nom_langue($GLOBALS['spip_lang'])."</a>"
	  . " | "
	  . "<a class='aide' onclick=\"window.open('$url_aide', 'spip_aide', 'scrollbars=yes,resizable=yes,width=740,height=580');return false;\" href='$url_aide'>"._T('icone_aide_ligne')."</a>"
	  . " | "
	  // $auth_can_disconnect?
	  . "<a href='".generer_url_action("logout","logout=prive")."'>"._T('icone_deconnecter')."</a>"
	  . "</p>";

	// informations sur le site
	$res .= "<p class='nom_site_spip'>"
	  . "<a class='info' title='Informations sur $nom_site' href='$url_config_identite'>"
	  . "<strong class='nom'> $nom_site </strong>"
	  . "<img alt='Informations sur $nom_site' src='$img_info' /></a>"
	  . "| "
	  . "<a class='voir' href='"._DIR_RACINE."'>"._T('icone_visiter_site')."</a>"
	  . "</p>";


	return "<div id='bando_identite'><div class='largeur'>\n$res<div class='nettoyeur'></div></div></div>";

}

/**
 * Construire le bandeau des raccourcis rapides
 *
 * @param array $boutons
 * @return string
 */
function bando_outils_rapides($boutons, $contexte = array()){
    $res = "";


	// le navigateur de rubriques
	$img = find_in_theme('images/boussole-24.png');
	$url = generer_url_ecrire("brouteur");
	$res .= "<ul class='bandeau_rubriques deroulant'><li class='boussole'>";
	$res .= "<a href='$url' id='boutonbandeautoutsite'><img src='$img' width='24' height='24' alt='' /></a>";
	include_spip('exec/menu_rubriques');
	$res .= menu_rubriques(false);
	$res .= "</li></ul>";

	// la barre de raccourcis rapides
	if (isset($boutons['outils_rapides']))
			$res .= "<ul class='rapides creer'>"
				. bando_lister_sous_menu($boutons['outils_rapides']->sousmenu,$contexte,'bouton',true)
				. "</ul>";

	$res .= "<div id='rapides'>";

	// la barre de raccourcis collaboratifs
	if (isset($boutons['outils_collaboratifs']))
			$res .= "<ul class='rapides collaborer'>"
				. bando_lister_sous_menu($boutons['outils_collaboratifs']->sousmenu,$contexte,'bouton',true)
				. "</ul>";

	$res .= formulaire_recherche("recherche")."</div>";

	return "<div id='bando_outils'><div class='largeur'>\n$res<div class='nettoyeur'></div></div></div>";
}

function bando_liens_acces_rapide(){
	$res = "";
	$res .= "<a href='#conteneur' onclick='return focus_zone(\"#conteneur\")'>Aller au contenu</a> | ";
	$res .= "<a href='#bando_navigation' onclick='return focus_zone(\"#bando_navigation\")'>Aller &agrave; la navigation</a> | ";
	$res .= "<a href='#recherche' onclick='return focus_zone(\"#recherche\")'>Aller &agrave; la recherche</a>";

	return "<div id='bando_liens_rapides'><div class='largeur'>\n$res<div class='nettoyeur'></div></div></div>";
}

/**
 * Construire tout le bandeau superieur de l'espace prive
 *
 * @param unknown_type $rubrique
 * @param unknown_type $sous_rubrique
 * @param unknown_type $largeur
 * @return unknown
 */
function inc_bandeau_dist($rubrique, $sous_rubrique, $largeur)
{
	$contexte = definir_barre_contexte();
	$boutons = definir_barre_boutons($contexte, false);
	return "<div id='bando_haut'>"
		. bando_liens_acces_rapide()
		. bando_identite()
		. bando_navigation($boutons,$contexte)
		. bando_outils_rapides($boutons,$contexte)
		. "</div>"
		;

}
// Pour memoire
define('_LARGEUR_ICONES_BANDEAU',
	((@$GLOBALS['spip_display'] == 3) ? 60 : 80)
	+ ((@$GLOBALS['spip_ecran'] == 'large') ? 30 : 0)
	+ (($GLOBALS['connect_toutes_rubriques']) ? 0 : 30));
?>
