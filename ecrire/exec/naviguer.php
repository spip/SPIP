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

include_spip('inc/presentation');
include_spip('inc/forum');

// http://doc.spip.org/@exec_naviguer_dist
function exec_naviguer_dist()
{
	exec_naviguer_args(intval(_request('id_rubrique')),
			   _request('cherche_mot'),
			   intval(_request('select_groupe')));
}

// http://doc.spip.org/@exec_naviguer_args
function exec_naviguer_args($id_rubrique, $cherche_mot, $select_groupe)
{
	if (!$id_rubrique) {
		$lang = $statut = $titre = $extra = $id_parent=$id_secteur='';
		$ze_logo = "racine-site-24.gif";
		$row = array();
	} else {
		$row = sql_fetsel('id_parent, id_secteur, titre, statut, lang, descriptif, texte', 'spip_rubriques', "id_rubrique=$id_rubrique");

		if (!$row OR !autoriser('voir','rubrique',$id_rubrique)) {
			include_spip('inc/minipres');
			echo minipres();
		} else {
			$id_parent=$row['id_parent'];
			$id_secteur=$row['id_secteur'];
			$titre=$row['titre'];
			$statut = $row['statut'];
			$lang = $row["lang"];

			if ($id_parent == 0) $ze_logo = "secteur-24.gif";
			else $ze_logo = "rubrique-24.gif";
		}
	}

	if ($ze_logo) {
	pipeline('exec_init',array('args'=>array('exec'=>'naviguer','id_rubrique'=>$id_rubrique),'data'=>''));

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(($titre ? ("&laquo; ".textebrut(typo($titre))." &raquo;") :
		    _T('titre_naviguer_dans_le_site')),
		   "naviguer",
		   "rubriques",
		   $id_rubrique);

	echo debut_grand_cadre(true);
	if ($id_rubrique  > 0)
		echo afficher_hierarchie($id_parent,_T('titre_cadre_interieur_rubrique'),$id_rubrique,'rubrique',$id_secteur,(!$GLOBALS['connect_toutes_rubriques']));
	else $titre = _T('info_racine_site').": ". $GLOBALS['meta']["nom_site"];

	echo fin_grand_cadre(true);

	echo debut_gauche('', true);

	$flag_editable = autoriser('publierdans','rubrique',$id_rubrique);

	if ($flag_editable AND !$id_parent) {
		list($from, $where) = critere_statut_controle_forum('prop', $id_rubrique);
		$n_forums = sql_countsel($from, $where);
	} else 	$n_forums = 0;

	changer_typo($lang);
	echo infos_naviguer($id_rubrique, $statut, $row, $n_forums);

	$iconifier = charger_fonction('iconifier', 'inc');
	echo $iconifier('id_rubrique', $id_rubrique, 'naviguer', false, $flag_editable);


	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'naviguer','id_rubrique'=>$id_rubrique),'data'=>''));

	echo creer_colonne_droite('', true);
	echo raccourcis_naviguer($id_rubrique, $id_parent);
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'naviguer','id_rubrique'=>$id_rubrique),'data'=>''));
	echo debut_droite('', true);

	$haut = montre_naviguer($id_rubrique, $titre, $id_parent, $ze_logo, $flag_editable);

	$boucles = contenu_naviguer($id_rubrique, $id_parent, $ze_logo, $flag_editable);

	if ($id_rubrique > 0) {
		$editer_mots = charger_fonction('editer_mots', 'inc');
		$editer_mots = $editer_mots('rubrique', $id_rubrique,  $cherche_mot,  $select_groupe, $flag_editable, true, 'naviguer');
	} else $editer_mots = '';

	echo naviguer_droite($row, $id_rubrique, $id_parent, $id_secteur, $haut, $n_forums, $editer_mots, $flag_editable, $boucles),
	  fin_gauche(),
	  fin_page();
	}
}

// http://doc.spip.org/@naviguer_droite
function naviguer_droite($row, $id_rubrique, $id_parent, $id_secteur, $haut, $n_forums, $editer_mots, $flag_editable, $boucles)
{
	global $spip_lang_right, $connect_toutes_rubriques;

	$onglet_proprietes =
		$editer_mots
		. langue_naviguer($id_rubrique, $id_parent, $flag_editable)
		. pipeline('affiche_milieu',array('args'=>array('exec'=>'naviguer','id_rubrique'=>$id_rubrique),'data'=>''))
	;

	$type = 'rubrique';
	$contexte = array('id'=>$id_rubrique,'id_rubrique'=>$id_rubrique);
	$fond = recuperer_fond("prive/contenu/$type",$contexte);
	// permettre aux plugin de faire des modifs ou des ajouts
	$fond = pipeline('afficher_contenu_objet',
			array(
			'args'=>array(
				'type'=>$type,
				'id_objet'=>$id_rubrique,
				'contexte'=>$contexte),
			'data'=> $fond));
	
	$onglet_contenu = "<div id='wysiwyg'>$fond</div>"
		. (_INTERFACE_ONGLETS? $boucles:"");

	include_spip('inc/presenter_enfants');
	$onglet_enfants =
	  afficher_enfant_rub($id_rubrique, false, true)
	  .(_INTERFACE_ONGLETS?"":
	   (autoriser('creerrubriquedans','rubrique',$id_rubrique)?"<div style='clear:$spip_lang_right;'>" .
	    (!$id_rubrique
		    ? icone_inline(_T('icone_creer_rubrique'), generer_url_ecrire("rubriques_edit","new=oui&retour=nav"), "secteur-24.gif", "creer.gif",$spip_lang_right)
		    : icone_inline(_T('icone_creer_sous_rubrique'), generer_url_ecrire("rubriques_edit","new=oui&retour=nav&id_parent=$id_rubrique"), "rubrique-24.gif", "creer.gif",$spip_lang_right))
	    ."</div>":""))
	  . "<div class='nettoyeur'></div>"
	  . $boucles;

	$onglet_enfants = pipeline('affiche_enfants',array('args'=>array('exec'=>'naviguer','id_rubrique'=>$id_rubrique),'data'=>$onglet_enfants));

	$documenter_objet = charger_fonction('documenter_objet','inc');
	$onglet_documents =
		($id_rubrique > 0 ? $documenter_objet($id_rubrique, "rubrique", 'naviguer', $flag_editable) :"" )
	;

	if ($n_forums)
	  $onglet_interactivite = icone_inline(_T('icone_suivi_forum', array('nb_forums' => $n_forums)), generer_url_ecrire("controle_forum","id_rubrique=$id_rubrique"), "suivi-forum-24.gif", "", 'center');
	else $onglet_interactivite = "";

	return
	  pipeline('afficher_fiche_objet',array('args'=>array('type'=>'rubrique','id'=>$id_rubrique),'data'=>
	  "<div class='fiche_objet'>".
		$haut.
		(_INTERFACE_ONGLETS?
		 afficher_onglets_pages(array(
			'sousrub'=> _T('onglet_sous_rubriques'),
			'voir' => _T('onglet_contenu'),
			'props' => _T('onglet_proprietes'),
			'docs' => _T('onglet_documents'),
			'interactivite' => _T('onglet_interactivite')),
					array(
			'voir'=>$onglet_contenu,
			'sousrub'=>$onglet_enfants,
			'props'=>$onglet_proprietes,
			'docs'=>$onglet_documents,
			'interactivite'=>$onglet_interactivite
			))
		 :$onglet_contenu.$onglet_proprietes).
	  "</div>".
	  (_INTERFACE_ONGLETS?"":$onglet_enfants.$onglet_documents.$onglet_interactivite)
				));
}

// http://doc.spip.org/@infos_naviguer
function infos_naviguer($id_rubrique, $statut, $row, $n_forums)
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

	$res = sql_allfetsel("A.nom, A.id_auteur", "spip_auteurs AS A LEFT JOIN spip_auteurs_rubriques AS R ON A.id_auteur=R.id_auteur", "A.statut = '0minirezo' AND R.id_rubrique=$id_rubrique");

	if (!$res) return $navigation;

	$img = http_img_pack('admin-12.gif','','');
	foreach ($res as $k => $row) {
		$h = generer_url_ecrire('auteur_infos', "id_auteur=" .$row['id_auteur']);
		$res[$k] = "$img <a href='$h'>" . $row['nom'] . '</a>';
	}
	$res = corriger_typo(join('<br />', $res));

	$navigation .= debut_cadre_relief("fiche-perso-24.gif", true, '', _T('info_administrateurs')). $res . fin_cadre_relief(true);

	return $navigation;
}


// http://doc.spip.org/@raccourcis_naviguer
function raccourcis_naviguer($id_rubrique, $id_parent)
{
	$res = icone_horizontale(_T('icone_tous_articles'), generer_url_ecrire("articles_page"), "article-24.gif", '',false);

	$n = sql_countsel('spip_rubriques');
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

		$row = sql_fetsel("lang, langue_choisie", "spip_rubriques", "id_rubrique=$id_rubrique");
		$langue_rubrique = $row['lang'];
		$langue_choisie_rubrique = $row['langue_choisie'];
		$langue_parent = '';
		if ($id_parent) {
			$row = sql_fetsel("lang", "spip_rubriques", "id_rubrique=$id_parent");
			$langue_parent = $row['lang'];
		}
		if (!$langue_parent)
			$langue_parent = $GLOBALS['meta']['langue_site'];
		if (!$langue_rubrique)
			$langue_rubrique = $langue_parent;

		$res .= debut_cadre_enfonce('langues-24.gif', true);
		#$res .= bouton_block_depliable(_T('titre_langue_rubrique')."&nbsp; (".traduire_nom_langue($langue_rubrique).")",false,'languesrubrique');

		#$res .= debut_block_depliable(false,'languesrubrique');
		$res .= "<div class='langue'>";
		if ($menu = liste_options_langues('changer_lang', $langue_rubrique, $langue_parent)) {
			$lien = redirige_action_auteur('instituer_langue_rubrique', "$id_rubrique-$id_parent","naviguer","id_rubrique=$id_rubrique");
			$lien = ("\nonchange=\"document.location.href='$lien" .
				 "&amp;changer_lang='+this.options[this.selectedIndex].value\"");
			$res .= select_langues('changer_lang', $lien, $menu, _T('titre_langue_rubrique'));
		}
		$res .=  "</div>\n";
		#$res .=  fin_block();
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

	$relief = sql_countsel('spip_articles', "id_rubrique=$id_rubrique AND statut='prop'");

	if (!$relief) {
		$relief = sql_countsel('spip_breves', "id_rubrique=$id_rubrique AND (statut='prepa' OR statut='prop')");
	}

	if (!$relief AND $GLOBALS['meta']['activer_sites'] != 'non') {
		$relief = sql_countsel('spip_syndic', "id_rubrique=$id_rubrique AND statut='prop'");
	}

	if (!$relief AND $GLOBALS['meta']['activer_syndic'] != 'non'
	AND autoriser('publierdans','rubrique',$id_rubrique)) {
		$relief = sql_countsel('spip_syndic', "id_rubrique=$id_rubrique AND (syndication='off' OR syndication='sus') AND statut='publie'");
	}

	$res = '';

	if ($relief) {

		$encours = "";
		//
		// Les articles a valider
		//
		$encours .= afficher_objets('article',_T('info_articles_proposes'),	array('WHERE' => "id_rubrique=$id_rubrique AND statut='prop'", 'ORDER BY' => "date DESC"));

		//
		// Les breves a valider
		//
		$encours .= afficher_objets('breve','<b>' . _T('info_breves_valider') . '</b>', array("FROM" => 'spip_breves', 'WHERE' => "id_rubrique=$id_rubrique AND (statut='prepa' OR statut='prop')", 'ORDER BY' => "date_heure DESC"), true);

		//
		// Les sites references a valider
		//
		if ($GLOBALS['meta']['activer_sites'] != 'non') {
			$encours .= afficher_objets('site','<b>' . _T('info_site_valider') . '</b>', array("FROM" => 'spip_syndic', 'WHERE' => "id_rubrique=$id_rubrique AND statut='prop'", 'ORDER BY' => "nom_site"));
		}

		//
		// Les sites a probleme
		//
		if ($GLOBALS['meta']['activer_sites'] != 'non'
		AND autoriser('publierdans','rubrique',$id_rubrique)) {

			$encours .= afficher_objets('site','<b>' . _T('avis_sites_syndiques_probleme') . '</b>', array('FROM' => 'spip_syndic', 'WHERE' => "id_rubrique=$id_rubrique AND (syndication='off' OR syndication='sus') AND statut='publie'", 'ORDER BY' => "nom_site"));
		}

		// Les articles syndiques en attente de validation
		if ($id_rubrique == 0
		AND autoriser('publierdans','rubrique',$id_rubrique)) {

			$cpt = sql_countsel("spip_syndic_articles", "statut='dispo'");
			if ($cpt)
				$encours .= "<br /><small><a href='" .
					generer_url_ecrire("sites_tous") .
					"' style='color: black;'>" .
					$cpt .
					" " .
					_T('info_liens_syndiques_1') .
					" " .
					_T('info_liens_syndiques_2') .
					"</a></small>";
		}

		$res .= debut_cadre_couleur_foncee("",true, "", _T('texte_en_cours_validation')
				. (($GLOBALS['meta']['forum_prive_objets'] != 'non')
					? ' '._T('texte_en_cours_validation_forum')
					: '' )
				)
			. pipeline('rubrique_encours',array('args'=>array('type'=>'rubrique','id_objet'=>$id_rubrique),'data'=>$encours))
			. fin_cadre_couleur(true);
	}

	$n = sql_countsel('spip_rubriques');
	$bouton_article = $bouton_breves = $bouton_sites = "";
	if ($n && !_INTERFACE_ONGLETS) {
		if (autoriser('creerarticledans','rubrique',$id_rubrique))
		  $bouton_article .= icone_inline(_T('icone_ecrire_article'), generer_url_ecrire("articles_edit","id_rubrique=$id_rubrique&new=oui"), "article-24.gif","creer.gif", $spip_lang_right)
		  . "<div class='nettoyeur'></div>";

		$activer_breves = $GLOBALS['meta']["activer_breves"];
		if (autoriser('creerbrevedans','rubrique',$id_rubrique,NULL,array('id_parent'=>$id_parent)))
		  $bouton_breves .= icone_inline(_T('icone_nouvelle_breve'), generer_url_ecrire("breves_edit","id_rubrique=$id_rubrique&new=oui"), "breve-24.gif","creer.gif", $spip_lang_right)
		  . "<div class='nettoyeur'></div>";

		if (autoriser('creersitedans','rubrique',$id_rubrique))
			$bouton_sites .= icone_inline(_T('info_sites_referencer'), generer_url_ecrire('sites_edit', "id_rubrique=$id_rubrique"), "site-24.gif", "creer.gif", $spip_lang_right)
		  . "<div class='nettoyeur'></div>";
	}

	//////////  Les articles en cours de redaction
	/////////////////////////

  $res .= afficher_objets('article',_T('info_tous_articles_en_redaction'), array("WHERE" => "statut='prepa' AND id_rubrique=$id_rubrique", 'ORDER BY' => "date DESC"));


	//////////  Les articles publies
	/////////////////////////

	define('_TRI_ARTICLES_RUBRIQUE', 'date DESC');  # 0+titre,titre
	$res .= afficher_objets('article',_T('info_tous_articles_presents'), array("WHERE" => "statut='publie' AND id_rubrique=$id_rubrique", 'ORDER BY' => _TRI_ARTICLES_RUBRIQUE));

	// si une rubrique n'a pas/plus d'article publie, afficher les eventuels articles refuses
	// pour permettre de la vider et la supprimer eventuellement
	if (sql_countsel("spip_articles", "statut='publie' AND id_rubrique=".intval($id_rubrique), $groupby, $having)==0)
		$res .= afficher_objets('article',_T('info_tous_articles_refuses'), array("WHERE" => "statut='refuse' AND id_rubrique=$id_rubrique", 'ORDER BY' => _TRI_ARTICLES_RUBRIQUE));

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



// http://doc.spip.org/@montre_naviguer
function montre_naviguer($id_rubrique, $titre, $id_parent, $ze_logo, $flag_editable)
{
	global $spip_lang_right;

	if ($flag_editable
	AND $id_rubrique > 0) {
		$actions = icone_inline(_T('icone_modifier_rubrique'),
			generer_url_ecrire("rubriques_edit",
				"id_rubrique=$id_rubrique&retour=nav"), $ze_logo, "edit.gif", $spip_lang_right);

		// Supprimer cette rubrique (si vide)
		if (tester_rubrique_vide($id_rubrique))
			$actions .= icone_inline(_T('icone_supprimer_rubrique'),
				redirige_action_auteur('supprimer', "rubrique-$id_rubrique", "naviguer","id_rubrique=$id_parent"), $ze_logo, "supprimer.gif", $spip_lang_right);
	}
	else
		$actions = ''; // rubrique non editable

	return
	  "<div class='bandeau_actions'>$actions</div>" .
	  gros_titre((!acces_restreint_rubrique($id_rubrique) ? '' :
	  http_img_pack("admin-12.gif",'', "width='12' height='12'",
			      _T('info_administrer_rubrique'))) .
	     $titre,'', false)
		. "<div class='nettoyeur'></div>\n";
}

// http://doc.spip.org/@tester_rubrique_vide
function tester_rubrique_vide($id_rubrique) {
	if (sql_countsel('spip_rubriques', "id_parent=$id_rubrique"))
		return false;

	if (sql_countsel('spip_articles', "id_rubrique=$id_rubrique AND (statut<>'poubelle')"))
		return false;

	if (sql_countsel('spip_breves', "id_rubrique=$id_rubrique AND (statut='publie' OR statut='prop')"))
		return false;

	if (sql_countsel('spip_syndic', "id_rubrique=$id_rubrique AND (statut='publie' OR statut='prop')"))
		return false;

	if (sql_countsel('spip_documents_liens', "id_objet=".intval($id_rubrique)." AND objet='rubrique'"))
		return false;

	$compte = pipeline('objet_compte_enfants',array('args'=>array('objet'=>'rubrique','id_objet'=>$id_rubrique),'data'=>array()));
	foreach($compte as $objet => $n)
		if ($n)
			return false;

	return true;
}

// http://doc.spip.org/@bouton_supprimer_naviguer
function bouton_supprimer_naviguer($id_rubrique, $id_parent, $ze_logo, $flag_editable)
{
	if (($id_rubrique>0) AND tester_rubrique_vide($id_rubrique) AND $flag_editable)
	  return icone_inline(_T('icone_supprimer_rubrique'), redirige_action_auteur('supprimer', "rubrique-$id_rubrique", "naviguer","id_rubrique=$id_parent"), $ze_logo, "supprimer.gif") . "</div>";
	return "";
}

?>
