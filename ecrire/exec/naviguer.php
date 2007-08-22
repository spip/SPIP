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

include_spip('inc/presentation');
include_spip('inc/forum');

// http://doc.spip.org/@exec_naviguer_dist
function exec_naviguer_dist()
{
	global $connect_toutes_rubriques;
	global $spip_display,$spip_lang_left,$spip_lang_right;

	$cherche_mot = _request('cherche_mot');
	$id_rubrique = intval(_request('id_rubrique'));
	$select_groupe = intval(_request('select_groupe'));

	$row = sql_fetch(spip_query("SELECT * FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
	if ($row) {
		$id_parent=$row['id_parent'];
		$id_secteur=$row['id_secteur'];
		$titre=$row['titre'];
		$descriptif=$row['descriptif'];
		$texte=$row['texte'];
		$statut = $row['statut'];
		$extra = $row["extra"];
		$lang = $row["lang"];
	} elseif ($id_rubrique)
	      {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	      }

	else $lang = $statut = $titre = $descriptif = $texte = $extra = $id_parent='';

	if ($id_rubrique ==  0) $ze_logo = "racine-site-24.gif";
	else if ($id_parent == 0) $ze_logo = "secteur-24.gif";
	else $ze_logo = "rubrique-24.gif";

	$flag_editable = autoriser('publierdans','rubrique',$id_rubrique);

	pipeline('exec_init',array('args'=>array('exec'=>'naviguer','id_rubrique'=>$id_rubrique),'data'=>''));

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(($titre ? ("&laquo; ".textebrut(typo($titre))." &raquo;") :
		    _T('titre_naviguer_dans_le_site')),
		   "naviguer",
		   "rubriques",
		   $id_rubrique);

	echo debut_grand_cadre(true);
	if ($id_rubrique  > 0) echo afficher_hierarchie($id_parent);
	else $titre = _T('info_racine_site').": ". $GLOBALS['meta']["nom_site"];
	echo fin_grand_cadre(true);

	changer_typo($lang);
	  
	if (!autoriser('voir','rubrique',$id_rubrique)){
		echo "<strong>"._T('avis_acces_interdit')."</strong>";
		echo fin_page();
		exit;
  }

	echo debut_gauche('', true);
	
	if (autoriser('publierdans','rubrique',$id_rubrique)) {
		$parent = sql_fetch(spip_query("SELECT id_parent FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
		if (!$parent['id_parent']) {
		  list($from, $where) = critere_statut_controle_forum('prop', $id_rubrique);
		  $n_forums = spip_num_rows(spip_query("SELECT id_forum FROM $from" .($where ? (" WHERE $where") : '')));
		}
	}
	$iconifier = charger_fonction('iconifier', 'inc');

	echo infos_naviguer($id_rubrique, $statut, $ze_logo, $n_forums);
	echo ($iconifier('id_rubrique', $id_rubrique, 'naviguer', false));
	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'naviguer','id_rubrique'=>$id_rubrique),'data'=>''));

	//
	// Afficher les boutons de creation d'article et de breve
	//
	/*if ($spip_display != 4) {
		raccourcis_naviguer($id_rubrique, $id_parent);
	}*/
		

	echo creer_colonne_droite('', true);
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'naviguer','id_rubrique'=>$id_rubrique),'data'=>''));	  
	echo debut_droite('', true);

	//  echo debut_cadre_relief($ze_logo, true);
	$actions = 
		voir_en_ligne ('rubrique', $id_rubrique, $statut, 'racine-24.gif', false)
		. icone_inline(_T('icone_tous_articles'), generer_url_ecrire("articles_page"), "article-24.gif", '', $spip_lang_left)
  	. (($id_rubrique > 0 AND $flag_editable)?icone_inline(_T('icone_modifier_rubrique'), generer_url_ecrire("rubriques_edit","id_rubrique=$id_rubrique&retour=nav"), $ze_logo, "edit.gif", $spip_lang_right):"")
		////// Supprimer cette rubrique (si vide)
		.	((($id_rubrique>0) AND tester_rubrique_vide($id_rubrique) AND $flag_editable)?
	    icone_inline(_T('icone_supprimer_rubrique'), redirige_action_auteur('supprimer', "rubrique-$id_rubrique", "naviguer","id_rubrique=$id_parent"), $ze_logo, "supprimer.gif", $spip_lang_right)
	    :"")
	  . (autoriser('creerrubriquedans','rubrique',$id_rubrique)?
	    (!$id_rubrique
		    ? icone_inline(_T('icone_creer_rubrique'), generer_url_ecrire("rubriques_edit","new=oui&retour=nav"), "secteur-24.gif", "creer.gif",$spip_lang_left)
		    : icone_inline(_T('icone_creer_sous_rubrique'), generer_url_ecrire("rubriques_edit","new=oui&retour=nav&id_parent=$id_rubrique"), "rubrique-24.gif", "creer.gif",$spip_lang_left))
		    :"");

	$n = spip_num_rows(spip_query("SELECT id_rubrique FROM spip_rubriques LIMIT 1"));
	if ($n) {
		if (autoriser('creerarticledans','rubrique',$id_rubrique))
		  $actions .= icone_inline(_T('icone_ecrire_article'), generer_url_ecrire("articles_edit","id_rubrique=$id_rubrique&new=oui"), "article-24.gif","creer.gif", $spip_lang_left);
	
		$activer_breves = $GLOBALS['meta']["activer_breves"];
		if (autoriser('creerbrevedans','rubrique',$id_rubrique,NULL,array('id_parent'=>$id_parent)))
		  $actions .= icone_inline(_T('icone_nouvelle_breve'), generer_url_ecrire("breves_edit","id_rubrique=$id_rubrique&new=oui"), "breve-24.gif","creer.gif", $spip_lang_left);

		if (autoriser('creersitedans','rubrique',$id_rubrique))
			$actions .= icone_inline(_T('info_sites_referencer'), generer_url_ecrire('sites_edit', "id_rubrique=$id_rubrique"), "site-24.gif", "creer.gif", $spip_lang_left);
	}
	
	$actions .= "<div class='nettoyeur'></div>";
	
	$haut =
	  gros_titre((!acces_restreint_rubrique($id_rubrique) ? '' :
	  http_img_pack("admin-12.gif",'', "width='12' height='12'",
			      _T('info_administrer_rubrique'))) .
	     $titre,'', false)
		. "<div class='bandeau_actions'>$actions</div>";

	if ($extra)
		include_spip('inc/extra');
	if ($id_rubrique > 0)
		$editer_mot = charger_fonction('editer_mot', 'inc');

	$onglet_proprietes = 
		afficher_rubrique_rubrique($id_rubrique, $id_parent, $id_secteur, $connect_toutes_rubriques)
		/// Mots-cles
		. ($editer_mot ? $editer_mot('rubrique', $id_rubrique,  $cherche_mot,  $select_groupe, $flag_editable, true):"")
		. langue_naviguer($id_rubrique, $id_parent, $flag_editable)
		. pipeline('affiche_milieu',array('args'=>array('exec'=>'naviguer','id_rubrique'=>$id_rubrique),'data'=>''))
	;

	$afficher_contenu_objet = charger_fonction('afficher_contenu_objet', 'inc');

	$onglet_contenu = 
		($extra?extra_affichage($extra, "rubriques"):"")
		. $afficher_contenu_objet('rubrique', $id_rubrique,$row)
		. (_INTERFACE_ONGLETS?contenu_naviguer($id_rubrique, $id_parent, $ze_logo, $flag_editable):"")
	;

	$onglet_enfants = 
	  afficher_enfant_rub($id_rubrique, false, true)
	  .(_INTERFACE_ONGLETS?"":
	   (autoriser('creerrubriquedans','rubrique',$id_rubrique)?"<div style='clear:$spip_lang_right;'>" .
	    (!$id_rubrique
		    ? icone_inline(_T('icone_creer_rubrique'), generer_url_ecrire("rubriques_edit","new=oui&retour=nav"), "secteur-24.gif", "creer.gif",$spip_lang_right)
		    : icone_inline(_T('icone_creer_sous_rubrique'), generer_url_ecrire("rubriques_edit","new=oui&retour=nav&id_parent=$id_rubrique"), "rubrique-24.gif", "creer.gif",$spip_lang_right))
		    :"")
		. "</div><br class='nettoyeur' />"
	  . contenu_naviguer($id_rubrique, $id_parent, $ze_logo, $flag_editable))
	;


	// Logos de la rubrique
	$onglet_documents = 
		/// Documents associes a la rubrique
		($id_rubrique > 0 ? naviguer_doc($id_rubrique, "rubrique", 'naviguer', $flag_editable) :"" )
	;
	
	$onglet_interactivite = "";
  if ($n_forums)
    $onglet_interactivite = icone_inline(_T('icone_suivi_forum', array('nb_forums' => $n_forums)), generer_url_ecrire("controle_forum","id_rubrique=$id_rubrique"), "suivi-forum-24.gif", "", 'center');
	$onglet_interactivite = 
	  $onglet_interactivite
		;

	echo 
	  "<div class='fiche_objet'>",
		$haut,
		(_INTERFACE_ONGLETS?
	  afficher_onglets_pages(array(
	    'sousrub'=>_L('Sous-rubriques'),
	  	'voir' =>_L('Contenu'),
	  	'props' => _L('Propri&eacute;t&eacute;s'),
	  	'docs' => _L('Documents'),
	  	'interactivite' => _L('Interactivit&eacute;')),
	  	array(
	    'voir'=>$onglet_contenu,
	    'sousrub'=>$onglet_enfants,
	    'props'=>$onglet_proprietes,
	    'docs'=>$onglet_documents,
	    'interactivite'=>$onglet_interactivite
	    )):$onglet_contenu.$onglet_proprietes),
	  "</div>",
	  (_INTERFACE_ONGLETS?"":$onglet_enfants.$onglet_interactivite),
	  fin_gauche(),
	  fin_page();
}

// http://doc.spip.org/@infos_naviguer
function infos_naviguer($id_rubrique, $statut, $ze_logo, $n_forums)
{
	$boite = pipeline ('boite_infos', array('data' => '',
		'args' => array(
			'type'=>'rubrique',
			'id' => $id_rubrique,
			'row' => $row,
			'n_forums' => $n_forums
		)
	));

	$navigation =
	  ($boite ?debut_boite_info(true). $boite . fin_boite_info(true):"");

	$q = spip_query("SELECT A.nom, A.id_auteur FROM spip_auteurs AS A LEFT JOIN spip_auteurs_rubriques AS R ON A.id_auteur=R.id_auteur WHERE R.id_rubrique=$id_rubrique");
	$res = "";
	while ($row = sql_fetch($q)) {
		$id = $row['id_auteur'];
		$res .= 
			http_img_pack('admin-12.gif','','') .
			    " <a href='" . generer_url_ecrire('auteur_infos', "id_auteur=$id") .
				"'>" .
				extraire_multi($row['nom']) .
				'</a><br />';
	}
	if ($res)
		$navigation .= debut_cadre_relief("fiche-perso-24.gif", true, '', _T('info_administrateurs')). $res . fin_cadre_relief(true);

	return $navigation;
}


// http://doc.spip.org/@raccourcis_naviguer
function raccourcis_naviguer($id_rubrique, $id_parent)
{
	$res = icone_horizontale(_T('icone_tous_articles'), generer_url_ecrire("articles_page"), "article-24.gif", '',false);
	
	$n = spip_num_rows(spip_query("SELECT id_rubrique FROM spip_rubriques LIMIT 1"));
	if ($n) {
		if (autoriser('creerarticledans','rubrique',$id_rubrique))
		  $res .= icone_horizontale(_T('icone_ecrire_article'), generer_url_ecrire("articles_edit","id_rubrique=$id_rubrique&new=oui"), "article-24.gif","creer.gif", false);
	
		$activer_breves = $GLOBALS['meta']["activer_breves"];
		if (autoriser('creerbrevedans','rubrique',$id_rubrique,NULL,array('id_parent'=>$id_parent))) {
		  $res .= icone_horizontale(_T('icone_nouvelle_breve'), generer_url_ecrire("breves_edit","id_rubrique=$id_rubrique&new=oui"), "breve-24.gif","creer.gif", false);
		}
	}
	else {
		// Post-install = ici pas de rubrique, veuillez en creer une
		if (autoriser('creerrubriquedans','rubrique',$id_rubrique))
			$res .= "<br />"._T('info_creation_rubrique');
	}

	echo bloc_des_raccourcis($res);
}

// http://doc.spip.org/@langue_naviguer
function langue_naviguer($id_rubrique, $id_parent, $flag_editable)
{
	$res = "";
	if ($id_rubrique>0 AND $GLOBALS['meta']['multi_rubriques'] == 'oui' AND ($GLOBALS['meta']['multi_secteurs'] == 'non' OR $id_parent == 0) AND $flag_editable) {

		$row = sql_fetch(spip_query("SELECT lang, langue_choisie FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
		$langue_rubrique = $row['lang'];
		$langue_choisie_rubrique = $row['langue_choisie'];
		$langue_parent = '';
		if ($id_parent) {
			$row = sql_fetch(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_parent"));
			$langue_parent = $row['lang'];
		} 
		if (!$langue_parent)
			$langue_parent = $GLOBALS['meta']['langue_site'];
		if (!$langue_rubrique)
			$langue_rubrique = $langue_parent;

		$res .= debut_cadre_enfonce('langues-24.gif', true);
		$res .= bouton_block_depliable(_T('titre_langue_rubrique')."&nbsp; (".traduire_nom_langue($langue_rubrique).")",false,'languesrubrique');

		$res .= debut_block_depliable(false,'languesrubrique');
		$res .= "<div class='verdana2' style='text-align: center;'>";
		if ($menu = liste_options_langues('changer_lang', $langue_rubrique, $langue_parent)) {
			$lien = redirige_action_auteur('instituer_langue_rubrique', "$id_rubrique-$id_parent","naviguer","id_rubrique=$id_rubrique");
			$lien = ("\nonchange=\"document.location.href='$lien" .
				 "&amp;changer_lang='+this.options[this.selectedIndex].value\"");
			$res .= select_langues('changer_lang', $lien, $menu);
		}
		$res .=  "</div>\n";
		$res .=  fin_block();
		$res .=  fin_cadre_enfonce(true);
	}
	return $res;
}

// http://doc.spip.org/@contenu_naviguer
function contenu_naviguer($id_rubrique, $id_parent) {

	global  $spip_lang_right;

	//
	// Verifier les boucles a mettre en relief
	//

	$relief = spip_num_rows(spip_query("SELECT id_article FROM spip_articles AS articles WHERE id_rubrique=$id_rubrique AND statut='prop' LIMIT 1"));

	if (!$relief) {
		$relief = spip_num_rows(spip_query("SELECT id_breve FROM spip_breves WHERE id_rubrique=$id_rubrique AND (statut='prepa' OR statut='prop') LIMIT 1"));
	}

	if (!$relief AND $GLOBALS['meta']['activer_sites'] != 'non') {
		$relief = spip_num_rows(spip_query("SELECT id_syndic FROM spip_syndic WHERE id_rubrique=$id_rubrique AND statut='prop' LIMIT 1"));
	}

	if (!$relief AND $GLOBALS['meta']['activer_syndic'] != 'non'
	  AND autoriser('publierdans','rubrique',$id_rubrique)) {
		$relief = spip_num_rows(spip_query("SELECT id_syndic FROM spip_syndic WHERE id_rubrique=$id_rubrique AND (syndication='off' OR syndication='sus') AND statut='publie' LIMIT 1"));
	}


	$res = '';

	if ($relief) {

		$res .= debut_cadre_couleur('',true);
		$res .= "<div class='verdana2' style='color: black;'><b>"._T('texte_en_cours_validation')."</b></div>";

		//
		// Les articles a valider
		//
		$res .= afficher_objets('article',_T('info_articles_proposes'),	array('WHERE' => "id_rubrique=$id_rubrique AND statut='prop'", 'ORDER BY' => "date DESC"));

		//
		// Les breves a valider
		//
		$res .= afficher_objets('breve','<b>' . _T('info_breves_valider') . '</b>', array("FROM" => 'spip_breves', 'WHERE' => "id_rubrique=$id_rubrique AND (statut='prepa' OR statut='prop')", 'ORDER BY' => "date_heure DESC"), true);

		//
		// Les sites references a valider
		//
		if ($GLOBALS['meta']['activer_sites'] != 'non') {
			$res .= afficher_objets('site','<b>' . _T('info_site_valider') . '</b>', array("FROM" => 'spip_syndic', 'WHERE' => "id_rubrique=$id_rubrique AND statut='prop'", 'ORDER BY' => "nom_site"));
		}

		//
		// Les sites a probleme
		//
		if ($GLOBALS['meta']['activer_sites'] != 'non'
		AND autoriser('publierdans','rubrique',$id_rubrique)) {
	
			$res .= afficher_objets('site','<b>' . _T('avis_sites_syndiques_probleme') . '</b>', array('FROM' => 'spip_syndic', 'WHERE' => "id_rubrique=$id_rubrique AND (syndication='off' OR syndication='sus') AND statut='publie'", 'ORDER BY' => "nom_site"));
		}

		// Les articles syndiques en attente de validation
		if ($id_rubrique == 0 
		AND autoriser('publierdans','rubrique',$id_rubrique)) {
	
			$cpt = sql_fetch(spip_query("SELECT COUNT(*) AS n FROM spip_syndic_articles WHERE statut='dispo'"));
			if ($cpt = $cpt['n'])
				$res .= "<br /><small><a href='" .
					generer_url_ecrire("sites_tous") .
					"' style='color: black;'>" .
					$cpt .
					" " .
					_T('info_liens_syndiques_1') .
					" " .
					_T('info_liens_syndiques_2') .
					"</a></small>";
		}

		$res .= fin_cadre_couleur(true);
	}

	$n = spip_num_rows(spip_query("SELECT id_rubrique FROM spip_rubriques LIMIT 1"));
	$bouton_article = $bouton_breves = $bouton_sites = "";
	if ($n && !_INTERFACE_ONGLETS) {
		if (autoriser('creerarticledans','rubrique',$id_rubrique))
		  $bouton_article .= icone_inline(_T('icone_ecrire_article'), generer_url_ecrire("articles_edit","id_rubrique=$id_rubrique&new=oui"), "article-24.gif","creer.gif", $spip_lang_right)
		  . "<br class='nettoyeur' />";
	
		$activer_breves = $GLOBALS['meta']["activer_breves"];
		if (autoriser('creerbrevedans','rubrique',$id_rubrique,NULL,array('id_parent'=>$id_parent)))
		  $bouton_breves .= icone_inline(_T('icone_nouvelle_breve'), generer_url_ecrire("breves_edit","id_rubrique=$id_rubrique&new=oui"), "breve-24.gif","creer.gif", $spip_lang_right)
		  . "<br class='nettoyeur' />";

		if (autoriser('creersitedans','rubrique',$id_rubrique))
			$bouton_sites .= icone_inline(_T('info_sites_referencer'), generer_url_ecrire('sites_edit', "id_rubrique=$id_rubrique"), "site-24.gif", "creer.gif", $spip_lang_right)
		  . "<br class='nettoyeur' />";
	}
	
	//////////  Les articles en cours de redaction
	/////////////////////////

  $res .= afficher_objets('article',_T('info_tous_articles_en_redaction'), array("WHERE" => "statut='prepa' AND id_rubrique=$id_rubrique", 'ORDER BY' => "date DESC"));


	//////////  Les articles publies
	/////////////////////////

  $res .= afficher_objets('article',_T('info_tous_articles_presents'), array("WHERE" => "statut='publie' AND id_rubrique=$id_rubrique", 'ORDER BY' => "date DESC"));
  $res .= $bouton_article;

	//// Les breves

	$res .= afficher_objets('breve','<b>' . _T('icone_ecrire_nouvel_article') . '</b>', array("FROM" => 'spip_breves', 'WHERE' => "id_rubrique=$id_rubrique AND statut != 'prop' AND statut != 'prepa'", 'ORDER BY' => "date_heure DESC"));
  $res .= $bouton_breves;

	//// Les sites references

	if ($GLOBALS['meta']["activer_sites"] == 'oui') {
		$res .= afficher_objets('site','<b>' . _T('titre_sites_references_rubrique') . '</b>', array("FROM" => 'spip_syndic', 'WHERE' => "id_rubrique=$id_rubrique AND statut!='refuse' AND statut != 'prop' AND syndication NOT IN ('off','sus')", 'ORDER BY' => 'nom_site'));
 		$res .= $bouton_sites;
	}
	return $res;
}

// http://doc.spip.org/@naviguer_doc
function naviguer_doc ($id, $type = "article", $script, $flag_editable) {
	global $spip_lang_left;

	if ($GLOBALS['meta']["documents_$type"]!='non' AND $flag_editable) {
		$joindre = charger_fonction('joindre', 'inc');
		$res = $joindre(array(
			'cadre' => 'relief',
			'icone' => 'image-24.gif',
			'fonction' => 'creer.gif',
			'titre' => _T('titre_joindre_document'),
			'script' => $script,
			'args' => "id_$type=$id",
			'id' => $id,
			'intitule' => _T('info_telecharger_ordinateur'),
			'mode' => 'document',
			'type' => $type,
			'ancre' => '',
			'id_document' => 0,
			'iframe_script' => generer_url_ecrire("documenter","id_rubrique=$id&type=$type",true)
		));

	// eviter le formulaire upload qui se promene sur la page
	// a cause des position:relative incompris de MSIE

	  if ($GLOBALS['browser_name']!="MSIE") {
		$res = "\n<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n<tr><td>&nbsp;</td><td style='text-align: $spip_lang_left;width: 50%;'>\n$res</td></tr></table>";
	  }

	  $res .= "<script src='"._DIR_JAVASCRIPT."async_upload.js' type='text/javascript'></script>\n";
    $res .= <<<EOF
    <script type='text/javascript'>
    $(".form_upload").async_upload(async_upload_portfolio_documents);
    </script>
EOF;
	} else $res ='';

	$documenter = charger_fonction('documenter', 'inc');

	return "<div id='portfolio'>".$documenter($id, $type, 'portfolio', $flag_editable)."</div><br />"
	."<div id='documents'>". $documenter($id, $type, 'documents', $flag_editable)."</div>"
	. $res;
}

// http://doc.spip.org/@montre_naviguer
function montre_naviguer($id_rubrique, $titre, $descriptif, $logo, $flag_editable)
{
	global $spip_lang_right, $spip_lang_left;
	return 
	  gros_titre((!acces_restreint_rubrique($id_rubrique) ? '' :
	  http_img_pack("admin-12.gif",'', "width='12' height='12'",
			      _T('info_administrer_rubrique'))) .
	     $titre,'', false);
}

// http://doc.spip.org/@tester_rubrique_vide
function tester_rubrique_vide($id_rubrique) {
	$n = sql_fetch(spip_query("SELECT COUNT(*) AS n FROM spip_rubriques WHERE id_parent=$id_rubrique LIMIT 1"));
	if ($n['n'] > 0) return false;

	$n = sql_fetch(spip_query("SELECT COUNT(*) AS n FROM spip_articles WHERE id_rubrique=$id_rubrique AND (statut='publie' OR statut='prepa' OR statut='prop') LIMIT 1"));
	if ($n['n'] > 0) return false;

	$n = sql_fetch(spip_query("SELECT COUNT(*) AS n FROM spip_breves WHERE id_rubrique=$id_rubrique AND (statut='publie' OR statut='prop') LIMIT 1"));
	if ($n['n'] > 0) return false;

	$n = sql_fetch(spip_query("SELECT COUNT(*) AS n FROM spip_syndic WHERE id_rubrique=$id_rubrique AND (statut='publie' OR statut='prop') LIMIT 1"));
	if ($n['n'] > 0) return false;

	$n = sql_fetch(spip_query("SELECT COUNT(*) AS n FROM spip_documents_rubriques WHERE id_rubrique=$id_rubrique LIMIT 1"));
	if ($n['n'] > 0) return false;

	return true;
}

// http://doc.spip.org/@bouton_supprimer_naviguer
function bouton_supprimer_naviguer($id_rubrique, $id_parent, $ze_logo, $flag_editable)
{
	if (($id_rubrique>0) AND tester_rubrique_vide($id_rubrique) AND $flag_editable)
	  return icone_inline(_T('icone_supprimer_rubrique'), redirige_action_auteur('supprimer', "rubrique-$id_rubrique", "naviguer","id_rubrique=$id_parent"), $ze_logo, "supprimer.gif") . "</div>";
	return "";
}

// http://doc.spip.org/@afficher_rubrique_rubrique
function afficher_rubrique_rubrique($id_rubrique, $id_parent, $id_secteur, $connect_toutes_rubriques)
{
	if (!_INTERFACE_ONGLETS) return "";
	global $spip_lang_right;
	$chercher_rubrique = charger_fonction('chercher_rubrique', 'inc');
	$aider = charger_fonction('aider', 'inc');

	$form = $chercher_rubrique($id_parent, 'rubrique', !$connect_toutes_rubriques, $id_rubrique);
	if (strpos($form,'<select')!==false) {
		$form .= "<div style='text-align: $spip_lang_right;'>"
			. '<input class="fondo" type="submit" value="'._T('bouton_choisir').'"/>'
			. "</div>";
	}

	$msg = _T('titre_cadre_interieur_rubrique') .
	  ((preg_match('/^<input[^>]*hidden[^<]*$/', $form)) ? '' : $aider("rubrub"));

	$form = generer_action_auteur("editer_rubrique", $id_rubrique, generer_url_ecrire('naviguer'), $form, " method='post' name='formulaire' class='submit_plongeur'");

	if ($id_parent == 0) $logo = "racine-site-24.gif";
	elseif ($id_secteur == $id_parent) $logo = "secteur-24.gif";
	else $logo = "rubrique-24.gif";

	return debut_cadre_couleur($logo, true, "", $msg) . $form .fin_cadre_couleur(true);
}
?>
