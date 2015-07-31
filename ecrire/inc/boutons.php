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

define('_LARGEUR_ICONES_BANDEAU', 
       ((@$GLOBALS['spip_display'] == 3) ? 60 : 80)
       + ((@$GLOBALS['spip_ecran'] == 'large') ? 30 : 0)
       + (($GLOBALS['connect_toutes_rubriques']) ? 0 : 30));

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

/**
 * definir la liste des boutons du haut et de ses sous-menus
 * On defini les boutons a metrtre selon les droits de l'utilisateur
 * puis on balance le tout au pipeline "ajouter_boutons" pour que des plugins
 * puissent y mettre leur grain de sel
 */
// http://doc.spip.org/@definir_barre_boutons
function definir_barre_boutons() {
	global $boutons_admin;

	global $spip_lang, $spip_lang_rtl, $spip_lang_left, $spip_lang_right;

	$boutons_admin=array(
		'accueil' => new Bouton('asuivre-48.png', 'icone_a_suivre'),
		'naviguer' => new Bouton("documents-48$spip_lang_rtl.png",
								 'icone_edition_site'),
		'forum' => new Bouton('messagerie-48.png', 'titre_forum'),
		'auteurs' => new Bouton('redacteurs-48.png', 'icone_auteurs')
	);

	if ($GLOBALS['meta']["activer_statistiques"] != 'non'
	AND autoriser('voirstats')) {
		$boutons_admin['statistiques_visites']=
		  new Bouton('statistiques-48.png', 'icone_statistiques_visites');
	}

	// autoriser('configurer') => forcement admin complet (ou webmestre)
	if (autoriser('configurer')) {
		$boutons_admin['configuration']=
		  new Bouton('administration-48.png', 'icone_configuration_site');
	}
	// autres admins (restreints ou non webmestres) peuvent aller sur les backups
	else
	if (autoriser('backup', 'admin_tech')) {
		$boutons_admin['admin_tech']=
		  new Bouton('administration-48.png', 'icone_maintenance_site');
	}

	$boutons_admin['espacement']=null;

	$urlAide= generer_url_ecrire('aide_index')."&amp;var_lang=$spip_lang";
	$boutons_admin['aide_index']=
		  new Bouton('aide-48'.$spip_lang_rtl.'.png', 'icone_aide_ligne',
					 $urlAide, null, "javascript:window.open('$urlAide', 'spip_aide', 'scrollbars=yes,resizable=yes,width=740,height=580');", 'aide_spip');

	$boutons_admin['visiter']=
		new Bouton("visiter-48$spip_lang_rtl.png", 'icone_visiter_site',
		url_de_base());

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

	$articles_mots = $GLOBALS['meta']['articles_mots'];
	if ($articles_mots != "non") {
			$sousmenu['mots_tous']=
			  new Bouton('mot-cle-24.gif', 'icone_mots_cles');
	}

	$activer_sites = $GLOBALS['meta']['activer_sites'];
	if ($activer_sites<>'non')
			$sousmenu['sites_tous']=
			  new Bouton('site-24.gif', 'icone_sites_references');

	$n = spip_num_rows(spip_query("SELECT id_document FROM spip_documents_rubriques LIMIT 1"));
	if ($n) {
			$sousmenu['documents_liste']=
			  new Bouton('doc-24.gif', 'icone_doc_rubrique');
	}
	$boutons_admin['naviguer']->sousmenu= $sousmenu;

	// sous menu forum

	$sousmenu=array();

	if ($GLOBALS['meta']['forum_prive_admin'] == 'oui')
		$sousmenu['forum_admin']=
		  new Bouton('forum-admin-24.gif', 'icone_forum_administrateur');

	if (spip_num_rows(spip_query("SELECT id_forum FROM spip_forum LIMIT 1")))
		$sousmenu['controle_forum']=
			new Bouton("suivi-forum-24.gif", "icone_suivi_forums");
	if (spip_num_rows(spip_query("SELECT id_signature FROM spip_signatures LIMIT 1")))
		$sousmenu['controle_petition']=
			new Bouton("suivi-petition-24.gif", "icone_suivi_pettions");

	if ($sousmenu)
		$boutons_admin['forum']->sousmenu= $sousmenu;


	// sous menu auteurs

	$sousmenu=array();

	$n = spip_num_rows(spip_query("SELECT id_auteur FROM spip_auteurs WHERE statut='6forum' LIMIT 1"));

	if ($n)
		$sousmenu['auteurs'] = 
			new Bouton("fiche-perso.png", 'icone_afficher_visiteurs', null, "statut=6forum");

	$sousmenu['auteur_infos']=
		new Bouton("auteur-24.gif", "icone_creer_nouvel_auteur", null, 'new=oui');

	$boutons_admin['auteurs']->sousmenu= $sousmenu;

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
	$sousmenu = array();
	if (autoriser('configurer', 'lang')) {
		$soumenu['config_lang'] =
			new Bouton("langues-24.gif", "icone_gestion_langues");
		$soumenu['espacement'] = null;
	}

	if (autoriser('backup')) {
		$sousmenu['admin_tech']= 
			new Bouton("base-24.gif", "icone_maintenance_site");
	}
	if (autoriser('configurer', 'admin_vider')) {
		$sousmenu['admin_vider']=
			new Bouton("cache-24.gif", "onglet_vider_cache");
	}

	if (@file_exists(_DIR_PLUGINS)
	AND is_dir(_DIR_PLUGINS)
	AND autoriser('configurer', 'admin_plugins')
	) {
		$sousmenu['admin_plugin']=
			new Bouton("plugin-24.gif", "icone_admin_plugin");
	}

	if ($sousmenu)
		$boutons_admin['configuration']->sousmenu= $sousmenu;

	} // fin si admin

	$boutons_admin = pipeline('ajouter_boutons', $boutons_admin);
}

/**
 * definir la liste des onglets dans une page de l'interface privee
 * on passe la main au pipeline "ajouter_onglets".
 */
// http://doc.spip.org/@definir_barre_onglets
function definir_barre_onglets($rubrique) {
	global $id_auteur, $connect_id_auteur, $connect_statut, $statut_auteur;

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
		if (autoriser('backup')) {
			$onglets['sauver']=
			  new Bouton('base-24.gif', 'onglet_save_restaur_base',
				generer_url_ecrire("admin_tech"));
		}
		if (autoriser('destroy')) {
			$onglets['effacer']=
			  new Bouton('supprimer.gif', 'onglet_affacer_base',
				generer_url_ecrire("admin_effacer"));
		}
	break;

	case 'auteur':
		$onglets['auteur']=
		  new Bouton('auteur-24.gif', 'onglet_auteur',
			generer_url_ecrire("auteur_infos","id_auteur=$id_auteur"));
		$onglets['infos']=
		  new Bouton('fiche-perso-24.gif', 'icone_informations_personnelles',
			generer_url_ecrire("auteur_infos","id_auteur=$id_auteur"));
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
		$onglets['fichiers']=
		  new Bouton('traductions-24.gif', 'module_fichiers_langues',
			generer_url_ecrire("lang_raccourcis"));
	break;

	// inutilise
	case 'suivi_forum':
	break;

	}

	$onglets = pipeline('ajouter_onglets', array('data'=>$onglets,'args'=>$rubrique));

	return $onglets;
}

// http://doc.spip.org/@barre_onglets
function barre_onglets($rubrique, $ongletCourant){
	$onglets= definir_barre_onglets($rubrique);
	if(count($onglets)==0) return '';

	$res = debut_onglet();

	foreach($onglets as $exec => $onglet) {
		$url= $onglet->url ? $onglet->url : generer_url_ecrire($exec);
		$res .= onglet(_T($onglet->libelle), $url,	$exec, $ongletCourant, $onglet->icone);
	}
	$res .= fin_onglet();
	return $res;
}

// http://doc.spip.org/@definir_barre_gadgets
function definir_barre_gadgets() {
	global $barre_gadgets;
	$barre_gadgets= array(
						  // ?????????
	);
}


// http://doc.spip.org/@bandeau_principal
function bandeau_principal($rubrique, $sous_rubrique, $largeur)
{
	$res = '';
	$decal = 0;
	//cherche les espacement pour determiner leur largeur 
  $num_espacements = 0;
  foreach($GLOBALS['boutons_admin'] as $page => $detail) {
	 if ($page=='espacement') $num_espacements++;
	}
	$larg_espacements = ($largeur-(count($GLOBALS['boutons_admin'])-$num_espacements)*_LARGEUR_ICONES_BANDEAU)/$num_espacements;
  foreach($GLOBALS['boutons_admin'] as $page => $detail) {
		if ($page=='espacement') {
			$res .= "<li class='cellule48' style='width:".$larg_espacements."px'><span class='menu-item' style='width:"._LARGEUR_ICONES_BANDEAU."px'>&nbsp;</span></li>";
		} else {
			if ($detail->url)
				$lien_noscript = $detail->url;
			else
				$lien_noscript = generer_url_ecrire($page);

			if ($detail->url2)
				$lien = $detail->url2;
			else
				$lien = $lien_noscript;

			$res .= icone_bandeau_principal(
					$detail,
					$lien,
					$page,
					$rubrique,
					$lien_noscript,
					$page,
					$sous_rubrique,
          $largeur,$decal);
		}
		$decal += _LARGEUR_ICONES_BANDEAU;
	}

	return $res;
}

// http://doc.spip.org/@icone_bandeau_principal
function icone_bandeau_principal($detail, $lien, $rubrique_icone = "vide", $rubrique = "", $lien_noscript = "", $sous_rubrique_icone = "", $sous_rubrique = "",$largeur,$decal){
	global $spip_display, $menu_accesskey, $compteur_survol;

	$alt = $accesskey = $title = '';
	$texte = _T($detail->libelle);
	if ($spip_display == 3){
		$title = " title=\"$texte\"";
	}
	
	if (!$menu_accesskey = intval($menu_accesskey)) $menu_accesskey = 1;
	if ($menu_accesskey < 10) {
		$accesskey = " accesskey='$menu_accesskey'";
		$menu_accesskey++;
	}
	else if ($menu_accesskey == 10) {
		$accesskey = " accesskey='0'";
		$menu_accesskey++;
	}

	$class_select = " style='width:"._LARGEUR_ICONES_BANDEAU."px' class='menu-item boutons_admin".($sous_rubrique_icone == $sous_rubrique ? " selection" : "")."'";

	if (strncasecmp("javascript:",$lien,11)==0) {
		$a_href = "\nonclick=\"$lien; return false;\" href='$lien_noscript' ";
	}
	else {
		$a_href = "\nhref=\"$lien\"";
	}

	$compteur_survol ++;

	if ($spip_display != 1 AND $spip_display != 4) {
		$class ='cellule48';
		$texte = "<span class='icon_fond'><span".http_style_background($detail->icone)."></span></span>".($spip_display == 3 ? '' :  "<span>$texte</span>");
	} else {
		$class = 'cellule-texte';
	}  
		
	return "<li style='width: "
	. _LARGEUR_ICONES_BANDEAU
	. "px' class='$class boutons_admin' onmouseover=\"changestyle('bandeau$rubrique_icone');\"><a$accesskey$a_href$class_select$title>"
	. $texte
	. "</a>\n"
	. bandeau_principal2($detail->sousmenu,$rubrique, $sous_rubrique, $largeur, $decal)
  . "</li>\n";
}

// http://doc.spip.org/@bandeau_principal2
function bandeau_principal2($sousmenu,$rubrique, $sous_rubrique, $largeur, $decal) {
	global $spip_lang_left;

	$res = '';
	$coeff_decalage = 0;
	if ($GLOBALS['browser_name']=="MSIE") $coeff_decalage = 1.0;
	$largeur_maxi_menu = $largeur-100;
	$largitem_moy = 85;

	//    if (($rubrique == $page) AND (!_SPIP_AJAX)) {  $page ??????
	if ((!_SPIP_AJAX)) {
			$class = "visible_au_chargement";
		} else {
			$class = "invisible_au_chargement";
		}
    
    
		if($sousmenu) {
			//offset is not necessary when javascript is active. It can be usefull when js is disabled
      $offset = (int)round($decal-$coeff_decalage*max(0,($decal+count($sousmenu)*$largitem_moy-$largeur_maxi_menu)));
			if ($offset<0){	$offset = 0; }

			$width=0;
			$max_width=0;
			foreach($sousmenu as $souspage => $sousdetail) {
				if ($width+1.25*$largitem_moy>$largeur_maxi_menu){
          $res .= "</ul><ul>\n";
          if($width>$max_width) $max_width=$width;
          $width=0;
        }
				$largitem = 0;
				if($souspage=='espacement') {
					if ($width>0){
						$res .= "<li class='separateur'></li>\n";
					}
				} else {
				  list($html,$largitem) = icone_bandeau_secondaire (_T($sousdetail->libelle), generer_url_ecrire($sousdetail->url?$sousdetail->url:$souspage, $sousdetail->urlArg), $sousdetail->icone, $souspage, $sous_rubrique);
				  $res .= $html;
				}
				$width+=$largitem+10;
				if($width>$max_width) $max_width+=$largitem;
			}
			$res .= "</ul></div>\n";
			$res = "<div class='bandeau_sec h-list' style='width:{$max_width}px;'><ul>".$res;
		}
		
	return $res;
}

// http://doc.spip.org/@bandeau_double_rangee
function bandeau_double_rangee($rubrique, $sous_rubrique, $largeur)
{
	global $spip_lang_left;
	definir_barre_boutons();

	return "<div class='invisible_au_chargement' style='position: absolute; height: 0px; visibility: hidden;'><a href='oo'>"._T("access_mode_texte")."</a></div>"
	. "<div id='haut-page'>\n"
	. "<div id='bandeau-principal'>\n"
  . "<div class='h-list centered' style='width:{$largeur}px'><ul>\n"
	. bandeau_principal($rubrique, $sous_rubrique, $largeur)
	. "</ul></div>\n"
  . "</div>"
  //script to show the submenus in IE6, not supporting :hover on li elements
  . "<script type='text/javascript'>\n"
  . "var boutons_admin = jQuery('#bandeau-principal li.boutons_admin');\n"
  . "if(jQuery.browser.msie) boutons_admin.hover(\n"
  . "function(){jQuery(this).addClass('sfhover')},\n"
  . "function(){jQuery(this).removeClass('sfhover')}\n"
  . ");\n"
  . "boutons_admin.one('mouseover',decaleSousMenu);\n"
  . "</script>\n"; 
}


// http://doc.spip.org/@icone_bandeau_secondaire
function icone_bandeau_secondaire($texte, $lien, $fond, $rubrique_icone = "vide", $rubrique, $aide=""){
	global $spip_display;
	global $menu_accesskey, $compteur_survol;

	$alt = '';
	$title = '';
	$accesskey = '';
	if ($spip_display == 1) {
		//$hauteur = 20;
		$largeur = 80;
	}
	else if ($spip_display == 3){
		//$hauteur = 26;
		$largeur = 40;
		$title = "title=\"$texte\"";
		$alt = $texte;
	}
	else {
		//$hauteur = 68;
		if (count(explode(" ", $texte)) > 1) $largeur = 80;
		else $largeur = 70;
		$alt = "";
	}
	if ($aide AND $spip_display != 3) {
		$largeur += 50;
		//$texte .= aide($aide);
	}
	if ($spip_display != 3 AND strlen($texte)>16) $largeur += 20;
	
	if (!$menu_accesskey = intval($menu_accesskey)) $menu_accesskey = 1;
	if ($menu_accesskey < 10) {
		$accesskey = " accesskey='$menu_accesskey'";
		$menu_accesskey++;
	}
	else if ($menu_accesskey == 10) {
		$accesskey = " accesskey='0'";
		$menu_accesskey++;
	}
	if ($spip_display == 3) $accesskey_icone = $accesskey;

	$class_select = " class='menu-item".($rubrique_icone != $rubrique ? "" : " selection")."'";
	$compteur_survol ++;

	$a_href = "<a$accesskey href=\"$lien\"$class_select>";

	if ($spip_display != 1) {
		$res = "<li class='cellule36' style='width: ".$largeur."px;'>";
		$res .= $a_href .
		  http_img_pack("$fond", $alt, "$title");
		if ($aide AND $spip_display != 3) $res .= aide($aide)." ";
		if ($spip_display != 3) {
			$res .= "<span>$texte</span>";
		}
		$res .= "</a></li>\n";
	}
	else $res = "<li style='width: $largeur" . "px' class='cellule-texte'>$a_href". $texte . "</a></li>\n";

	return array($res, $largeur);
}


?>
