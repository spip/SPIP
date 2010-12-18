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

/**
 * une classe definissant un bouton dans la barre du haut de l'interface
 * privee ou dans un de ses sous menus
 */
// http://doc.spip.org/@Bouton
class Bouton {
  var $icone;         /* l'icone a mettre dans le bouton */
  var $libelle;       /* le nom de l'entree d'i18n associe */
  var $url= null;     /* l'url de la page (null => ?exec=nom) */
  var $urlArg= null;  /* arguments supplementaires de l'url */
  var $url2= null;    /* url jscript */
  var $target= null;  /* pour ouvrir dans une fenetre a part */
  var $sousmenu= null;/* sous barre de boutons / onglets */

// http://doc.spip.org/@Bouton
  function Bouton($icone, $libelle, $url=null, $urlArg=null,
				  $url2=null, $target=null) {
	$this->icone  = $icone;
	$this->libelle= $libelle;
	$this->url    = $url;
	$this->urlArg = $urlArg;
	$this->url2   = $url2;
	$this->target = $target;
  }
}


// http://doc.spip.org/@barre_onglets_rep_depuis
function barre_onglets_rep_depuis() {

	$onglets = array();
	$onglets['statistiques_lang']=
		  new Bouton(null, 'icone_repartition_actuelle');
	$onglets['debut']=
		  new Bouton(null, 'onglet_repartition_debut',
			generer_url_ecrire("statistiques_lang","critere=debut"));
	return $onglets;
}

// http://doc.spip.org/@barre_onglets_stat_depuis
function barre_onglets_stat_depuis() {
	$onglets = array();
	$onglets['popularite']=
		  new Bouton(null, 'icone_repartition_actuelle',
			generer_url_ecrire("statistiques_repartition",""));
	$onglets['debut']=
		  new Bouton(null, 'onglet_repartition_debut',
			generer_url_ecrire("statistiques_repartition","critere=debut"));
	return $onglets;
}


// http://doc.spip.org/@barre_onglets_administration
function barre_onglets_administration() {

	$onglets = array();
	if (autoriser('sauvegarder')) {
			$onglets['sauver']=
			  new Bouton('spip-pack-24.png', 'onglet_save_restaur_base',
				generer_url_ecrire("admin_tech"));
	}
	if (autoriser('detruire')) {
			$onglets['effacer']=
			  new Bouton('supprimer.gif', 'onglet_affacer_base',
				generer_url_ecrire("admin_effacer"));

			$onglets['declarer']=
			  new Bouton('base-24.gif', 'onglet_declarer_une_autre_base',
				generer_url_ecrire("admin_declarer"));
		}
	return $onglets;
}

// http://doc.spip.org/@barre_onglets_configuration
function barre_onglets_configuration() {

	$onglets = array();
	$onglets['contenu']=
		  new Bouton('racine-site-24.gif', 'onglet_contenu_site',
			generer_url_ecrire("configuration"));
	$onglets['interactivite']=
		  new Bouton('forum-interne-24.gif', 'onglet_interactivite',
			generer_url_ecrire("config_contenu"));
	$onglets['fonctions']=
		  new Bouton('image-24.gif', 'onglet_fonctions_avances',
			generer_url_ecrire("config_fonctions"));
/*
		if (true)
		$onglets['plugins']=
		  new Bouton('plugin-24.gif', 'onglet_plugins',
			generer_url_ecrire("config_plugins"));
*/
	return $onglets;
}


// http://doc.spip.org/@barre_onglets_config_lang
function barre_onglets_config_lang() {

	$onglets=array();
	$onglets['langues']=
		  new Bouton('langues-24.gif', 'info_langue_principale',
			generer_url_ecrire("config_lang"));
	$onglets['multi']=
		  new Bouton('traductions-24.gif', 'info_multilinguisme',
			generer_url_ecrire("config_multilang"));
		$onglets['fichiers']=
		  new Bouton('traductions-24.gif', 'module_fichiers_langues',
			generer_url_ecrire("lang_raccourcis"));
	return $onglets;
}


function barre_onglets_plugins() {

	$onglets=array();
	$onglets['plugins_actifs']=
		  new Bouton('plugin-24.gif', 'plugins_actifs_liste',
			generer_url_ecrire("admin_plugin"));
	$onglets['admin_plugin']=
		  new Bouton('plugin-24.gif', 'plugins_liste',
			generer_url_ecrire("admin_plugin","voir=tous"));
	$onglets['charger_plugin']=
		  new Bouton('spip-pack-24.png', 'plugin_titre_automatique_ajouter',
			generer_url_ecrire("charger_plugin"));
	return $onglets;
}


/**
 * definir la liste des onglets dans une page de l'interface privee
 * on passe la main au pipeline "ajouter_onglets".
 */
// http://doc.spip.org/@definir_barre_onglets
function definir_barre_onglets($script) {

	if (function_exists($f = 'barre_onglets_' . $script))
		$onglets = $f();
	else  $onglets=array();

	// ajouter les onglets issus des plugin via plugin.xml
	if (function_exists('onglets_plugins')){
		$liste_onglets_plugins = onglets_plugins();

		foreach($liste_onglets_plugins as $id => $infos){
			if (($parent = $infos['parent'])
				&& $parent == $script
				&& autoriser('onglet',$id)) {
					$onglets[$id] = new Bouton(
					  find_in_path($infos['icone']),  // icone
					  $infos['titre'],	// titre
					  $infos['url']?generer_url_ecrire($infos['url'],$infos['args']?$infos['args']:''):null
					  );
			}
		}
	}

	return pipeline('ajouter_onglets', array('data'=>$onglets,'args'=>$script));
}


// http://doc.spip.org/@barre_onglets
function barre_onglets($rubrique, $ongletCourant){

	$res = '';

	foreach(definir_barre_onglets($rubrique) as $exec => $onglet) {
		$url= $onglet->url ? $onglet->url : generer_url_ecrire($exec);
		$res .= onglet(_T($onglet->libelle), $url, $exec, $ongletCourant, $onglet->icone);
	}

	return  !$res ? '' : (debut_onglet() . $res . fin_onglet());
}

// http://doc.spip.org/@definir_barre_gadgets
function definir_barre_gadgets() {
	global $barre_gadgets;
	$barre_gadgets= array(
						  // ?????????
	);
}


?>
