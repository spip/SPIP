<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Ce fichier gère le bandeau supérieur de l'espace privé
 * 
 * @package SPIP\Core\Bandeau
**/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/boutons');

/**
 * Calcule le contexte pour le menu du bandeau
 *
 * La fonction tente de retrouver la rubrique et le secteur d'appartenance
 * à partir du nom du fichier exec, si celui ci correspond à un objet
 * éditorial de SPIP (et qu'il possède ces champs), et dans ce cas,
 * l'ajoute au contexte.
 * 
 * @param null|array $contexte
 *     Contexte connu.
 *     S'il n'est pas transmis, on prend `$_GET`
 * @return array
 *     Contexte
**/
function definir_barre_contexte($contexte = null){
	if (is_null($contexte))
		$contexte = $_GET;
	elseif(is_string($contexte))
		$contexte = unserialize($contexte);
	if (!isset($contexte['id_rubrique']) AND isset($contexte['exec'])){
		if (!function_exists('trouver_objet_exec'))
			include_spip('inc/pipelines_ecrire');
		if ($e=trouver_objet_exec($contexte['exec'])){
			$_id = $e['id_table_objet'];
			if (isset($contexte[$_id]) AND $id=intval($contexte[$_id])){
				$table = $e['table_objet_sql'];
				$row = sql_fetsel('*',$table,"$_id=".intval($id));
				if (isset($row['id_rubrique'])){
					$contexte['id_rubrique'] = $row['id_rubrique'];
					if (isset($row['id_secteur']))
						$contexte['id_secteur'] = $row['id_secteur'];
				}
			}
		}
	}
	return $contexte;
}

/**
 * Définir la liste des boutons du haut et de ses sous-menus
 * 
 * On defini les boutons à mettre selon les droits de l'utilisateur
 * puis on balance le tout au pipeline "ajouter_menus" pour que des plugins
 * puissent y mettre leur grain de sel
 *
 * @param array $contexte
 * @param bool $icones Rechercher les icones
 * @param bool $autorise Ne renvoyer que les boutons autorisés
 * @return array
 */
function definir_barre_boutons($contexte=array(),$icones = true, $autorise = true) {
    include_spip('inc/autoriser');
	$boutons_admin=array();

	// les boutons du core, issus de prive/navigation.xml
	$liste_boutons = array();

	// ajouter les boutons issus des plugin via plugin.xml
	if (function_exists('boutons_plugins')
	  AND is_array($liste_boutons_plugins = boutons_plugins()))
		$liste_boutons = &$liste_boutons_plugins;

	foreach($liste_boutons as $id => $infos){
		$parent = "";
		// les boutons principaux ne sont pas soumis a autorisation
		if (!isset($infos['parent']) OR !($parent = $infos['parent']) OR !$autorise OR autoriser('menu',"_$id",0,NULL,array('contexte'=>$contexte))){
			if ($parent
			  AND $parent = preg_replace(',^bando_,','menu_',$parent)
			  AND isset($boutons_admin[$parent])){
				if (!is_array($boutons_admin[$parent]->sousmenu))
					$boutons_admin[$parent]->sousmenu = array();
				$position = (isset($infos['position']) AND strlen($infos['position'])) ? intval($infos['position']) : count($boutons_admin[$parent]->sousmenu);
				if ($position<0) $position = count($boutons_admin[$parent]->sousmenu)+1+$position;
				$boutons_admin[$parent]->sousmenu = array_slice($boutons_admin[$parent]->sousmenu,0,$position)
				+ array($id=> new Bouton(
					($icones AND $infos['icone'])?find_in_theme($infos['icone']):'',  // icone
					$infos['titre'],	// titre
					(isset($infos['action']) AND $infos['action'])?$infos['action']:null,
					(isset($infos['parametres']) AND $infos['parametres'])?$infos['parametres']:null
					))
				+ array_slice($boutons_admin[$parent]->sousmenu,$position,100);
			}
			if (!$parent
			// provisoire, eviter les vieux boutons
			AND (!in_array($id,array('forum','statistiques_visites')))

			) {
				$position = (isset($infos['position']) and $infos['position']) ? $infos['position'] : count($boutons_admin);
				$boutons_admin = array_slice($boutons_admin,0,$position)
				+array($id=> new Bouton(
					($icones AND isset($infos['icone']) AND $infos['icone'])?find_in_theme($infos['icone']):'',  // icone
					$infos['titre'],	// titre
					(isset($infos['action']) AND $infos['action'])?$infos['action']:null,
					(isset($infos['parametres']) AND $infos['parametres'])?$infos['parametres']:null
					))
				+ array_slice($boutons_admin,$position,100);
			}
		}
	}

	return pipeline('ajouter_menus', $boutons_admin);
}

/**
 * Créer l'URL à partir de exec et args, sauf si c'est déjà une url formatée
 *
 * @param string $url
 * @param string $args
 * @param array|null $contexte
 * @return string
 */
function bandeau_creer_url($url, $args="", $contexte=null){
	if (!preg_match(',[\/\?],',$url)) {
		$url = generer_url_ecrire($url,$args,true);
		// recuperer les parametres du contexte demande par l'url sous la forme
		// &truc=@machin@
		// @machin@ etant remplace par _request('machin')
		$url = str_replace('&amp;','&',$url);
		while (preg_match(",[&?]([a-z_]+)=@([a-z_]+)@,i",$url,$matches)){
			if ($matches[2]=='id_secteur' AND !isset($contexte['id_secteur']) AND isset($contexte['id_rubrique']))
				$contexte['id_secteur'] = sql_getfetsel('id_secteur','spip_rubriques','id_rubrique='.intval($contexte['id_rubrique']));
			$val = _request($matches[2],$contexte);
			$url = parametre_url($url,$matches[1],$val?$val:'','&');
		}
		$url = str_replace('&','&amp;',$url);
	}
	return $url;
}


/**
 * Construire tout le bandeau supérieur de l'espace privé
 *
 * @return string
 *     Code HTML du bandeau
 */
function inc_bandeau_dist() {
	return recuperer_fond('prive/squelettes/inclure/barre-nav',$_GET);
}

?>
