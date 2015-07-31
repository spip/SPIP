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


define('_LARGEUR_ICONES_BANDEAU', 
       (($GLOBALS['spip_display'] == 3) ? 60 : 80)
       + (($GLOBALS['spip_ecran'] == 'large') ? 30 : 0)
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

	global $REQUEST_URI, $HTTP_HOST;
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

		$n = spip_num_rows(spip_query("SELECT id_document FROM spip_documents_rubriques LIMIT 1"));
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
				new Bouton("plugin-24.gif", "icone_admin_plugin");
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
		$onglets['sauver']=
		  new Bouton('base-24.gif', 'onglet_save_restaur_base',
			generer_url_ecrire("admin_tech",""));
		$onglets['effacer']=
		  new Bouton('supprimer.gif', 'onglet_affacer_base',
			generer_url_ecrire("admin_effacer",""));
	break;

	case 'auteur':
		$onglets['auteur']=
		  new Bouton('auteur-24.gif', 'onglet_auteur',
			generer_url_ecrire("auteurs_edit","id_auteur=$id_auteur"));
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
	foreach($GLOBALS['boutons_admin'] as $page => $detail) {
		if ($page=='espacement') {
			$res .= "<td> &nbsp; </td>";
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
					_T($detail->libelle),
					$lien,
					$detail->icone,
					$page,
					$rubrique,
					$lien_noscript,
					$page,
					$sous_rubrique);
		}
	}

	return "<div class='bandeau-icones'>\n<table width='$largeur' cellpadding='0' cellspacing='0' border='0' align='center'><tr>\n$res</tr></table></div>\n";
}

// http://doc.spip.org/@icone_bandeau_principal
function icone_bandeau_principal($texte, $lien, $fond, $rubrique_icone = "vide", $rubrique = "", $lien_noscript = "", $sous_rubrique_icone = "", $sous_rubrique = ""){
	global $spip_display, $menu_accesskey, $compteur_survol;

	$largeur = _LARGEUR_ICONES_BANDEAU;

	$alt = '';
	$title = '';
	if ($spip_display == 1){
	}
	else if ($spip_display == 3){
		$title = "title=\"$texte\"";
		$alt = $texte;
	}
	else {
		$alt = ' ';
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

	$class_select = ($sous_rubrique_icone == $sous_rubrique) ? " class='selection'" : '';

	if (eregi("^javascript:",$lien)) {
		$a_href = "\nonclick=\"$lien; return false;\" href='$lien_noscript' target='spip_aide'$class_select";
	}
	else {
		$a_href = "\nhref=\"$lien\"$class_select";
	}

	$compteur_survol ++;

	if ($spip_display != 1 AND $spip_display != 4) {
		$class ='cellule48';
		$texte = http_img_pack($fond, $alt, "$title width='48' height='48'")
		. ($spip_display == 3 ? '' :  "<span>$texte</span>");
	} else {
		$class = 'cellule-texte';
	}  
		
	return "<td class='$class' onmouseover=\"changestyle('bandeau$rubrique_icone', 'visibility', 'visible');\" width='$largeur'><a$accesskey$a_href>$texte</a></td>\n";
}

// http://doc.spip.org/@bandeau_principal2
function bandeau_principal2($rubrique, $sous_rubrique, $largeur) {
	global $spip_lang_left;

	$res = '';
	$decal=0;
	$coeff_decalage = 0;
	if ($GLOBALS['browser_name']=="MSIE") $coeff_decalage = 1.0;
	$largeur_maxi_menu = $largeur-100;
	$largitem_moy = 85;

	foreach($GLOBALS['boutons_admin'] as $page => $detail) {
		if (($rubrique == $page) AND (!_SPIP_AJAX)) {
			$class = "visible_au_chargement";
		} else {
			$class = "invisible_au_chargement";
		}

		$sousmenu= $detail->sousmenu;
		if($sousmenu) {
			$offset = (int)round($decal-$coeff_decalage*max(0,($decal+count($sousmenu)*$largitem_moy-$largeur_maxi_menu)));
			if ($offset<0){	$offset = 0; }

			$res .= "<div class='$class bandeau' id='bandeau$page' style='position: absolute; $spip_lang_left: ".$offset."px;'><div class='bandeau_sec'><table class='gauche'><tr>\n";
			$width=0;
			foreach($sousmenu as $souspage => $sousdetail) {
				if ($width+1.25*$largitem_moy>$largeur_maxi_menu){$res .= "</tr><tr>\n";$width=0;}
				if($souspage=='espacement') {
					if ($width>0){
						$res .= "<td class='separateur'></td>\n";
						$largitem = 0;
					}
				} else {
				  list($html,$largitem) = icone_bandeau_secondaire (_T($sousdetail->libelle), generer_url_ecrire($sousdetail->url?$sousdetail->url:$souspage, $sousdetail->urlArg), $sousdetail->icone, $souspage, $sous_rubrique);
				  $res .= $html;
				}
				$width+=$largitem+10;
			}
			$res .= "</tr></table></div></div>";
		}
		
		$decal += _LARGEUR_ICONES_BANDEAU;
	}
	return $res;
}

// http://doc.spip.org/@bandeau_double_rangee
function bandeau_double_rangee($rubrique, $sous_rubrique, $largeur)
{
	global $spip_lang_left;
	definir_barre_boutons();

	return "<div class='invisible_au_chargement' style='position: absolute; height: 0px; visibility: hidden;'><a href='oo'>"._T("access_mode_texte")."</a></div>"
	. "<div id='haut-page'>"
	. "<div id='bandeau-principal' align='center'>\n"
	. bandeau_principal($rubrique, $sous_rubrique, $largeur)
	. "<table width='$largeur' cellpadding='0' cellspacing='0' align='center'><tr><td>"
	. "<div style='text-align: $spip_lang_left; width: ".$largeur."px; position: relative; z-index: 2000;'>"
	. bandeau_principal2($rubrique, $sous_rubrique, $largeur)
	. "</div>"
	. "</td></tr></table>"
	. "</div>\n"; 
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
		$alt = " ";
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

	$class_select =  ($rubrique_icone != $rubrique) ? '' : " class='selection'";
	$compteur_survol ++;

	$a_href = "<a$accesskey href=\"$lien\"$class_select>";

	$res = '';
	if ($spip_display != 1) {
		$res .= "<td class='cellule36' style='width: ".$largeur."px;'>";
		$res .= "$a_href" .
		  http_img_pack("$fond", $alt, "$title");
		if ($aide AND $spip_display != 3) $res .= aide($aide)." ";
		if ($spip_display != 3) {
			$res .= "<span>$texte</span>";
		}
	}
	else $res .= "<td class='cellule-texte' width='$largeur'>$a_href".$texte;
	$res .= "</a>";	
	$res .= "</td>\n";
	return array($res, $largeur);
}


?>
