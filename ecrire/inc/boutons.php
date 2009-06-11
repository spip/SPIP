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


// http://doc.spip.org/@barre_onglets_configuration
function barre_onglets_configuration() {

	$onglets = array();
	$onglets['contenu']=
		  new Bouton('racine-24.png', 'onglet_contenu_site',
			generer_url_ecrire("configuration"));
	$onglets['interactivite']=
		  new Bouton('auteur-6forum-24.png', 'onglet_interactivite',
			generer_url_ecrire("config_contenu"));
	$onglets['fonctions']=
		  new Bouton('image-24.png', 'onglet_fonctions_avances',
			generer_url_ecrire("config_fonctions"));

	return $onglets;
}


// http://doc.spip.org/@barre_onglets_config_lang
function barre_onglets_config_lang() {

	$onglets=array();
	$onglets['langues']=
		  new Bouton('langue-24.png', 'info_langue_principale',
			generer_url_ecrire("config_lang"));
	$onglets['multi']=
		  new Bouton('traduction-24.png', 'info_multilinguisme',
			generer_url_ecrire("config_multilang"));
		$onglets['fichiers']=
		  new Bouton('traduction-24.png', 'module_fichiers_langues',
			generer_url_ecrire("lang_raccourcis"));
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

	// les onglets du core, issus de prive/navigation.xml
	include_spip('inc/bandeau');
	$liste_onglets = boutons_core('onglet');

	// ajouter les onglets issus des plugin via plugin.xml
	if (function_exists('onglets_plugins')){
		$liste_onglets_plugins = onglets_plugins();
		$liste_onglets = $liste_onglets + $liste_onglets_plugins;
	}

	foreach($liste_onglets as $id => $infos){
		if (($parent = $infos['parent'])
			&& $parent == $script
			&& autoriser('onglet',$id)) {
				$onglets[$id] = new Bouton(
					find_in_skin($infos['icone']),  // icone
					$infos['titre'],	// titre
					$infos['url']?generer_url_ecrire($infos['url'],$infos['args']?$infos['args']:''):null
					);
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
