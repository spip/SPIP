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
		$ze_logo = "racine-24.png";
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

			if ($id_parent == 0) $ze_logo = "secteur-24.png";
			else $ze_logo = "rubrique-24.png";
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
		echo afficher_hierarchie($id_parent,true,$id_rubrique,'rubrique',$id_secteur,(!$GLOBALS['connect_toutes_rubriques']));
	else $titre = _T('info_racine_site').": ". $GLOBALS['meta']["nom_site"];

	echo fin_grand_cadre(true);

	echo debut_gauche('', true);

	$flag_editable = autoriser('publierdans','rubrique',$id_rubrique);

	changer_typo($lang);
	echo infos_naviguer($id_rubrique, $statut, $row);

	$iconifier = charger_fonction('iconifier', 'inc');
	echo $iconifier('rubrique', $id_rubrique, 'naviguer', false, $flag_editable);


	echo pipeline('afficher_config_objet',array('args'=>array('type'=>'rubrique','id'=>$id_rubrique),'data'=>''));
	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'naviguer','id_rubrique'=>$id_rubrique),'data'=>''));

	echo creer_colonne_droite('', true);
	echo raccourcis_naviguer($id_rubrique, $id_parent);
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'naviguer','id_rubrique'=>$id_rubrique),'data'=>''));
	echo debut_droite('', true);

	$haut = montre_naviguer($id_rubrique, $titre, $id_parent, $ze_logo, $flag_editable);

	$boucles = contenu_naviguer($id_rubrique, $id_parent, $ze_logo, $flag_editable);


	echo naviguer_droite($row, $id_rubrique, $id_parent, $id_secteur, $haut, 0, '', $flag_editable, $boucles),
	  fin_gauche(),
	  fin_page();
	}
}

// http://doc.spip.org/@naviguer_droite
function naviguer_droite($row, $id_rubrique, $id_parent, $id_secteur, $haut, $inutile , $inutile_bis, $flag_editable, $boucles)
{
	global $spip_lang_right, $connect_toutes_rubriques;

	$onglet_proprietes =
		  langue_naviguer($id_rubrique, $id_parent, $flag_editable)
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
	
	$onglet_contenu = "<div id='wysiwyg'>$fond</div>";

	include_spip('inc/presenter_enfants');
	$onglet_enfants =
	  afficher_enfant_rub($id_rubrique, false, true)
	  .
	   (autoriser('creerrubriquedans','rubrique',$id_rubrique)?"<div style='clear:$spip_lang_right;'>" .
	    (!$id_rubrique
		    ? icone_inline(_T('icone_creer_rubrique'), generer_url_ecrire("rubriques_edit","new=oui&retour=nav"), "secteur-24.png", "new",$spip_lang_right)
		    : icone_inline(_T('icone_creer_sous_rubrique'), generer_url_ecrire("rubriques_edit","new=oui&retour=nav&id_parent=$id_rubrique"), "rubrique-24.png", "new",$spip_lang_right))
	    ."</div>":"")
	  . "<br class='nettoyeur' />"
	  . $boucles;

	$onglet_enfants = pipeline('affiche_enfants',array('args'=>array('exec'=>'naviguer','id_rubrique'=>$id_rubrique),'data'=>$onglet_enfants));

	return
	  pipeline('afficher_fiche_objet',array('args'=>array('type'=>'rubrique','id'=>$id_rubrique),'data'=>
	  	"<div class='fiche_objet'>" .
			$haut .

			"<div class='nettoyeur'></div>" .
			$onglet_contenu .
			"<div class='nettoyeur'></div>" .
			$onglet_proprietes .
			"<div class='nettoyeur'></div>" .
	  	"</div>" .
			
	  	$onglet_enfants .
			pipeline('afficher_complement_objet',array('args'=>array('type'=>'rubrique','id'=>$id_rubrique),'data'=>"<div class='nettoyeur'></div>")) .
			"<div class='nettoyeur'></div>"
	  	));
}

// http://doc.spip.org/@infos_naviguer
function infos_naviguer($id_rubrique, $statut, $row)
{
	$boite = pipeline ('boite_infos', array('data' => '',
		'args' => array(
			'type'=>'rubrique',
			'id' => $id_rubrique,
			'row' => $row,
		)
	));

	$navigation =
	  ($boite ?debut_boite_info(true). $boite . fin_boite_info(true):"");

	$res = sql_allfetsel("A.nom, A.id_auteur", "spip_auteurs AS A LEFT JOIN spip_auteurs_liens AS R ON (R.objet='rubrique' AND A.id_auteur=R.id_auteur)", "A.statut = '0minirezo' AND R.id_objet=$id_rubrique");

	if (!$res) return $navigation;

	$img = http_img_pack(chemin_image('auteur-0minirezo-16.png'),'','');
	foreach ($res as $k => $row) {
		$h = generer_url_ecrire('auteur_infos', "id_auteur=" .$row['id_auteur']);
		$res[$k] = "$img <a href='$h'>" . $row['nom'] . '</a>';
	}
	$res = corriger_typo(join('<br />', $res));

	return $navigation . debut_cadre_relief("information-perso-24.png", true, '', _T('info_administrateurs')). $res . fin_cadre_relief(true);
}


// http://doc.spip.org/@raccourcis_naviguer
function raccourcis_naviguer($id_rubrique, $id_parent)
{
	$res = icone_horizontale(_T('icone_tous_articles'), generer_url_ecrire("articles_page"), "article-24.png", '',false);

	$n = sql_countsel('spip_rubriques');
	if ($n) {
		if (autoriser('creerarticledans','rubrique',$id_rubrique))
		  $res .= icone_horizontale(_T('icone_ecrire_article'), generer_url_ecrire("articles_edit","id_rubrique=$id_rubrique&new=oui"), "article-24.png","new", false);

		$activer_breves = $GLOBALS['meta']["activer_breves"];
		if (autoriser('creerbrevedans','rubrique',$id_rubrique,NULL,array('id_parent'=>$id_parent))) {
		  $res .= icone_horizontale(_T('icone_nouvelle_breve'), generer_url_ecrire("breves_edit","id_rubrique=$id_rubrique&new=oui"), "breve-24.png","new", false);
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

		$res .= debut_cadre_enfonce('langue-24.png', true);
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

	$res = '';
	$lister_objets = charger_fonction('lister_objets','inc');

	$encours = "";
	//
	// Les articles a valider
	//
	$encours .=  $lister_objets('articles',array('titre'=>_T('info_articles_proposes'),'statut'=>'prop', 'id_rubrique'=>$id_rubrique,'par'=>'date'));


	$encours = pipeline('rubrique_encours',array('args'=>array('type'=>'rubrique','id_objet'=>$id_rubrique),'data'=>$encours));

	if (strlen(trim($encours)))
		$res .= 
			debut_cadre_couleur_foncee("",true, "", _T('texte_en_cours_validation'))
			. $encours
			. fin_cadre_couleur_foncee(true);

	$n = sql_countsel('spip_rubriques');
	$bouton_article = "";
	if ($n) {
		if (autoriser('creerarticledans','rubrique',$id_rubrique))
		  $bouton_article .= icone_inline(_T('icone_ecrire_article'), generer_url_ecrire("articles_edit","id_rubrique=$id_rubrique&new=oui"), "article-24.png","new", $spip_lang_right)
		  . "<br class='nettoyeur' />";

	}

	//////////  Les articles en cours de redaction
	/////////////////////////

	$res .=  $lister_objets('articles',array('titre'=>_T('info_tous_articles_en_redaction'),'statut'=>'prepa', 'id_rubrique'=>$id_rubrique,'par'=>'date'));


	//////////  Les articles publies
	/////////////////////////

	if (!defined('_TRI_ARTICLES_RUBRIQUE')) define('_TRI_ARTICLES_RUBRIQUE', 'date');  # 'num titre'
	$res .=  $lister_objets('articles',array('titre'=>_T('info_tous_articles_presents'),'statut'=>'publie', 'id_rubrique'=>$id_rubrique,'par'=>_TRI_ARTICLES_RUBRIQUE));

	// si une rubrique n'a pas/plus d'article publie, afficher les eventuels articles refuses
	// pour permettre de la vider et la supprimer eventuellement
	if (sql_countsel("spip_articles", "statut='publie' AND id_rubrique=".intval($id_rubrique), $groupby, $having)==0)
		$res .=  $lister_objets('articles',array('titre'=>_T('info_tous_articles_refuses'),'statut'=>'refuse', 'id_rubrique'=>$id_rubrique,'par'=>_TRI_ARTICLES_RUBRIQUE));

  $res .= $bouton_article;


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
				"id_rubrique=$id_rubrique&retour=nav"), $ze_logo, "edit", $spip_lang_right);

		// Supprimer cette rubrique (si vide)
		if (tester_rubrique_vide($id_rubrique))
			$actions .= icone_inline(_T('icone_supprimer_rubrique'),
				redirige_action_auteur('supprimer', "rubrique-$id_rubrique", "naviguer","id_rubrique=$id_parent"), $ze_logo, "del", $spip_lang_right);
	}
	else
		$actions = ''; // rubrique non editable

	return
	  "<div class='bandeau_actions'>$actions</div>" .
	  gros_titre((!acces_restreint_rubrique($id_rubrique) ? '' :
	  http_img_pack(chemin_image('auteur-0minirezo-16.png'),'', "",
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
	  return icone_inline(_T('icone_supprimer_rubrique'), redirige_action_auteur('supprimer', "rubrique-$id_rubrique", "naviguer","id_rubrique=$id_parent"), $ze_logo, "del") . "</div>";
	return "";
}

?>
