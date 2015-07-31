<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


/**
 * une classe definissant un bouton dans la barre du haut de l'interface
 * privee ou dans un de ses sous menus
 */
class Bouton {
  var $icone;         /* l'icone a mettre dans le bouton */
  var $libelle;       /* le nom de l'entree d'i18n associe */
  var $url= null;     /* l'url de la page (null => ?exec=nom) */
  var $urlArg= null;  /* arguments supplementaires de l'url */
  var $url2= null;    /* url jscript */
  var $target= null;  /* pour ouvrir dans une fenetre a part */
  var $sousmenu= null;/* sous barre de boutons / onglets */

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

/**
 * definir la liste des boutons du haut et de ses sous-menus
 * On defini les boutons a metrtre selon les droits de l'utilisateur
 * puis on balance le tout au pipeline "ajouter_boutons" pour que des plugins
 * puissent y mettre leur grain de sel
 */
function definir_barre_boutons() {
	global $boutons_admin;

	global $REQUEST_URI, $HTTP_HOST, $adresse_site;
	$adresse_site = $GLOBALS['meta']["adresse_site"];
	if (!$adresse_site) {
			$adresse_site = "http://$HTTP_HOST".substr($REQUEST_URI, 0, strpos($REQUEST_URI, "/" . _DIR_RESTREINT_ABS));
			ecrire_meta("adresse_site", $adresse_site);
			ecrire_metas();
	}
	if (strlen($adresse_site)<10) $adresse_site = _DIR_RACINE;

	global $spip_lang, $spip_lang_rtl, $spip_lang_left, $spip_lang_right;

	$boutons_admin=array(
		'accueil' => new Bouton('asuivre-48.png', 'icone_a_suivre'),
		'naviguer' => new Bouton("documents-48$spip_lang_rtl.png",
								 'icone_edition_site'),
		'forum' => new Bouton('messagerie-48.png', 'titre_forum'),
		'auteurs' => new Bouton('redacteurs-48.png', 'icone_auteurs')
	);

	if ($GLOBALS['connect_statut'] == "0minirezo"
	AND $GLOBALS['meta']["activer_statistiques"] != 'non') {
		$boutons_admin['statistiques_visites']=
		  new Bouton('statistiques-48.png', 'icone_statistiques_visites');
	}
	if ($GLOBALS['connect_statut'] == '0minirezo') {

		$boutons_admin['configuration']=
		  new Bouton('administration-48.png', 'icone_configuration_site');
	}
	$boutons_admin['espacement']=null;
	$urlAide= generer_url_ecrire('aide_index')."&amp;var_lang=$spip_lang";
	$boutons_admin['aide_index']=
		  new Bouton('aide-48'.$spip_lang_rtl.'.png', 'icone_aide_ligne',
					 $urlAide, null, "javascript:window.open('$urlAide', 'aide_spip', 'scrollbars=yes,resizable=yes,width=740,height=580');", 'aide_spip');
	$boutons_admin['visiter']=
	  new Bouton("visiter-48$spip_lang_rtl.png", 'icone_visiter_site',
				 "$adresse_site/");

	// les sous menu des boutons, que si on est admin
	if ($GLOBALS['connect_statut'] == '0minirezo'
		AND $GLOBALS['connect_toutes_rubriques']) {

	// sous menu edition

	$sousmenu=array();

	$nombre_articles = spip_num_rows(spip_query("SELECT art.id_article FROM spip_articles AS art, spip_auteurs_articles AS lien WHERE lien.id_auteur = '".$GLOBALS['connect_id_auteur']."' AND art.id_article = lien.id_article LIMIT 1"));
	if ($nombre_articles > 0) {
		$sousmenu['articles_page']=
		  new Bouton('article-24.gif', 'icone_tous_articles');
	}

	if ($GLOBALS['meta']["activer_breves"] != "non") {
		$sousmenu['breves']=
		  new Bouton('breve-24.gif', 'icone_breves');
	}

	if ($GLOBALS['options'] == "avancees"){
		$articles_mots = $GLOBALS['meta']['articles_mots'];
		if ($articles_mots != "non") {
			$sousmenu['mots_tous']=
			  new Bouton('mot-cle-24.gif', 'icone_mots_cles');
		}

		$activer_sites = $GLOBALS['meta']['activer_sites'];
		if ($activer_sites<>'non')
			$sousmenu['sites_tous']=
			  new Bouton('site-24.gif', 'icone_sites_references');

		$n = spip_num_rows(spip_query("SELECT * FROM spip_documents_rubriques LIMIT 1"));
		if ($n) {
			$sousmenu['documents_liste']=
			  new Bouton('doc-24.gif', 'icone_doc_rubrique');
		}
	}
	$boutons_admin['naviguer']->sousmenu= $sousmenu;

	// sous menu forum

	$sousmenu=array();

	if ($GLOBALS['meta']['forum_prive_admin'] == 'oui')
		$sousmenu['forum_admin']=
		  new Bouton('forum-admin-24.gif', 'icone_forum_administrateur');

	$sousmenu['controle_forum']=
		  new Bouton("suivi-forum-24.gif", "icone_suivi_forums");
	$sousmenu['controle_petition']=
		  new Bouton("suivi-petition-24.gif", "icone_suivi_pettions");
	
	$boutons_admin['forum']->sousmenu= $sousmenu;

	// sous menu auteurs

	$boutons_admin['auteurs']->sousmenu= array(
		'auteurs_edit' => 
		  new Bouton("fiche-perso-24.gif", "icone_informations_personnelles",
			null, 'id_auteur='.$GLOBALS['connect_id_auteur']),
		'auteur_infos' => 
		  new Bouton("auteur-24.gif", "icone_creer_nouvel_auteur",
					 null, 'new=oui')
	);

	// sous menu statistiques
	if (isset($boutons_admin['statistiques_visites'])) {
		$sousmenu=array(
			'espacement' => null,
			'statistiques_repartition' =>
				new Bouton("rubrique-24.gif", "icone_repartition_visites")
		);

		if ($GLOBALS['meta']['multi_articles'] == 'oui'
		OR $GLOBALS['meta']['multi_rubriques'] == 'oui')
			$sousmenu['statistiques_lang']=
				new Bouton("langues-24.gif", "onglet_repartition_lang");

		$sousmenu['statistiques_referers']=
		  new Bouton("referers-24.gif", "titre_liens_entrants");

		$boutons_admin['statistiques_visites']->sousmenu= $sousmenu;
	}
	
	// sous menu configuration

	$sousmenu=array(
		'config_lang' => 
			new Bouton("langues-24.gif", "icone_gestion_langues"),
		'espacement' => null
	);

	if ($GLOBALS['options'] == "avancees") {
		$sousmenu['admin_tech']= 
			new Bouton("base-24.gif", "icone_maintenance_site");
		$sousmenu['admin_vider']=
			new Bouton("cache-24.gif", "onglet_vider_cache");
  	if ((@file_exists(_DIR_PLUGINS))&&(is_dir(_DIR_PLUGINS)))
			$sousmenu['admin_plugin']=
				new Bouton("plugin-24.png", "icone_admin_plugin");
	} else {
		$sousmenu['admin_tech']=
			new Bouton("base-24.gif", "icone_sauver_site");
	}

	$boutons_admin['configuration']->sousmenu= $sousmenu;

	} // fin si admin

	$boutons_admin = pipeline('ajouter_boutons', $boutons_admin);
}

/**
 * definir la liste des onglets dans une page de l'interface privee
 * on passe la main au pipeline "ajouter_onglets".
 */
function definir_barre_onglets($rubrique) {
	global $id_auteur, $connect_id_auteur, $connect_statut, $statut_auteur, $options;

	$onglets=array();

	switch($rubrique) {
	case 'statistiques_repartition':
	case 'repartition':
		if ($GLOBALS['meta']['multi_articles'] == 'oui' OR $GLOBALS['meta']['multi_rubriques'] == 'oui') {
			$onglets['rubriques']=
			  new Bouton('rubrique-24.gif', 'onglet_repartition_rubrique');
			$onglets['langues']=
			  new Bouton('langues-24.gif', 'onglet_repartition_lang');
		}
	break;

	case 'rep_depuis':
		$onglets['statistiques_lang']=
		  new Bouton(null, 'icone_repartition_actuelle');
		$onglets['debut']=
		  new Bouton(null, 'onglet_repartition_debut',
			generer_url_ecrire("statistiques_lang","critere=debut"));
	break;

	case 'stat_depuis':
		$onglets['popularite']=
		  new Bouton(null, 'icone_repartition_actuelle',
			generer_url_ecrire("statistiques_repartition",""));
		$onglets['debut']=
		  new Bouton(null, 'onglet_repartition_debut',
			generer_url_ecrire("statistiques_repartition","critere=debut"));
	break;

	case 'stat_referers':
		$onglets['jour']=
		  new Bouton(null, 'date_aujourdhui',
			generer_url_ecrire("statistiques_referers",""));
		$onglets['veille']=
		  new Bouton(null, 'date_hier',
			generer_url_ecrire("statistiques_referers","jour=veille"));
	break;

	case 'administration':
		$onglets['sauver']=
		  new Bouton('base-24.gif', 'onglet_save_restaur_base',
			generer_url_ecrire("admin_tech",""));
		$onglets['effacer']=
		  new Bouton('supprimer.gif', 'onglet_affacer_base',
			generer_url_ecrire("admin_effacer",""));
	break;

	//??????
	case 'auteur':
		$onglets['auteur']=
		  new Bouton('auteur-24.gif', 'onglet_auteur',
			generer_url_ecrire("auteurs_edit","id_auteur=$id_auteur"));
		$onglets['infos']=
		  new Bouton('fiche-perso-24.gif', 'onglet_informations_personnelles',
			generer_url_ecrire("auteurs_infos","id_auteur=$id_auteur"));
	break;

	case 'configuration':
		$onglets['contenu']=
		  new Bouton('racine-site-24.gif', 'onglet_contenu_site',
			generer_url_ecrire("configuration"));
		$onglets['interactivite']=
		  new Bouton('forum-interne-24.gif', 'onglet_interactivite',
			generer_url_ecrire("config_contenu"));
		$onglets['fonctions']=
		  new Bouton('image-24.gif', 'onglet_fonctions_avances',
			generer_url_ecrire("config_fonctions"));
	break;

	case 'config_lang':
		$onglets['langues']=
		  new Bouton('langues-24.gif', 'info_langue_principale',
			generer_url_ecrire("config_lang"));
		$onglets['multi']=
		  new Bouton('traductions-24.gif', 'info_multilinguisme',
			generer_url_ecrire("config_multilang"));
		if ($GLOBALS['meta']['multi_articles'] == "oui" OR $GLOBALS['meta']['multi_rubriques'] == "oui") {
			$onglets['fichiers']=
			  new Bouton('traductions-24.gif', 'module_fichiers_langues',
				generer_url_ecrire("lang_raccourcis"));
		}
	break;

	// inutilise
	case 'suivi_forum':
	break;

	}

	$onglets = pipeline('ajouter_onglets', array('data'=>$onglets,'args'=>$rubrique));

	return $onglets;
}

function definir_barre_gadgets() {
	global $barre_gadgets;
	$barre_gadgets= array(
						  // ?????????
	);
}

?>
