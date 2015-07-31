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
include_spip('inc/actions');
include_spip("inc/indexation");

// http://doc.spip.org/@exec_breves_voir_dist
function exec_breves_voir_dist()
{
	$id_breve = intval(_request('id_breve'));
	exec_breves_voir_args($id_breve, _request('cherche_mot'), _request('select_groupe'));
}

// http://doc.spip.org/@exec_breves_voir_args
function exec_breves_voir_args($id_breve, $cherche_mot, $select_groupe)
{
	global $champs_extra, $les_notes, $spip_display, $spip_lang_left, $spip_lang_right;

	$row = sql_fetsel("*", "spip_breves", "id_breve=$id_breve");
	if (!$row OR !autoriser('voir','breve',$id_breve)){
		include_spip('inc/minipres');
		echo minipres();
	} else {
	$id_breve=$row['id_breve'];
	$date_heure=$row['date_heure'];
	$titre_breve=$row['titre'];
	$titre=$row['titre'];
	$texte=$row['texte'];
	$extra=$row['extra'];
	$lien_titre=$row['lien_titre'];
	$lien_url=$row['lien_url'];
	$statut=$row['statut'];
	$id_rubrique=$row['id_rubrique'];

	$commencer_page = charger_fonction('commencer_page', 'inc');

	$flag_editable = autoriser('modifier','breve',$id_breve);

	// Est-ce que quelqu'un a deja ouvert la breve en edition ?
	if ($flag_editable
	AND $GLOBALS['meta']['articles_modif'] != 'non') {
		include_spip('inc/drapeau_edition');
		$modif = mention_qui_edite($id_breve, 'breve');
	} else
		$modif = array();


	pipeline('exec_init',
		array(
			'args'=>array('exec'=>'breves_voir','id_breve'=>$id_breve),
			'data'=>''
		)
	);
	$iconifier = charger_fonction('iconifier', 'inc');

	$dater = charger_fonction('dater', 'inc');

	$editer_mot = charger_fonction('editer_mot', 'inc');
	if ($champs_extra AND $extra)
		include_spip('inc/extra');

	echo $commencer_page("&laquo; $titre_breve &raquo;", "naviguer", "breves", $id_rubrique);
	
	echo debut_grand_cadre(true);
	echo afficher_hierarchie($id_rubrique);
	echo fin_grand_cadre(true);
	
	echo debut_gauche('', true);
	
	echo debut_boite_info(true)
	  . pipeline ('boite_infos', array('data' => '',
		'args' => array(
			'type'=>'breve',
			'id' => $id_breve,
			'row' => $row
		)))
		. fin_boite_info(true);

	echo pipeline('affiche_gauche',
		array(
		'args'=>array('exec'=>'breves_voir','id_breve'=>$id_breve),
		'data'=>''
		)
	);
	echo $iconifier('id_breve', $id_breve, 'breves_voir', false, autoriser('publierdans','rubrique',$id_rubrique));

	echo creer_colonne_droite('', true);
	echo pipeline('affiche_droite',
		array(
		'args'=>array('exec'=>'breves_voir','id_breve'=>$id_breve),
		'data'=>''
		)
	);
	echo meme_rubrique($id_rubrique, $id_breve, 'breve', 'date_heure');

	/* raccourcis ont disparu */
	echo bloc_des_raccourcis(icone_horizontale(_T('icone_nouvelle_breve'), generer_url_ecrire("breves_edit","new=oui&id_rubrique=$id_rubrique"), "breve-24.gif","creer.gif", 0));

	$afficher_contenu_objet = charger_fonction('afficher_contenu_objet', 'inc');

	$actions = $flag_editable
		? icone_inline(
			// TODO -- _L("Fil a travaille sur cette breve il y a x minutes")
			!$modif ? _T('icone_modifier_breve')
				: _T('texte_travail_article', $modif),
			generer_url_ecrire("breves_edit","id_breve=$id_breve&retour=nav"),
			!$modif ? "breve-24.gif" : "warning-24.gif",
			!$modif ? "edit.gif" : '',
			$GLOBALS['spip_lang_right']
			)
		: "";

	$haut =
		"<div class='bandeau_actions'>$actions</div>"
		. gros_titre($titre,'', false);


	$onglet_contenu = $afficher_contenu_objet('breve', $id_breve,$id_rubrique);

	

	$onglet_proprietes = 
		afficher_breve_rubrique($id_breve, $id_rubrique, $statut)
		. ($dater
			? $dater($id_breve, $flag_editable, $statut, 'breve', 'breves_voir', $date_heure)
			: ''
		)

	  . $editer_mot('breve', $id_breve, $cherche_mot, $select_groupe, $flag_editable, true)
	  . ((($GLOBALS['meta']['multi_articles'] == 'oui') AND ($flag_editable)) ? langue_breve($id_breve,$row):"")
	  . pipeline('affiche_milieu',array(
			'args'=>array('exec'=>'breves_voir','id_breve'=>$id_breve),
			'data'=>''))
		  ;

	$onglet_documents = "";
	
	$onglet_interactivite = "";
		
	$onglet_discuter = 
	  icone_inline(_T('icone_poster_message'), generer_url_ecrire("forum_envoi", "statut=prive&id=$id_breve&script=breves_voir") . '#formulaire', "forum-interne-24.gif", "creer.gif", 'center')
		. afficher_forum(sql_select("*", 'spip_forum', "statut='prive' AND id_breve=$id_breve AND id_parent=0",'', "date_heure DESC",  "20"), "breves_voir", "id_breve=$id_breve")
	  ;

	echo 
		debut_droite('', true)
	  . "<div class='fiche_objet'>"
	  . $haut 
	  . afficher_onglets_pages(array(
	  	'voir' =>_L('Contenu'),
	  	'props' => _L('Propri&eacute;t&eacute;s'),
	  	'docs' => _L('Documents'),
	  	'interactivite' => _L('Interactivit&eacute;'),
	  	'discuter' => _L('Discuter')),
	  _INTERFACE_ONGLETS?
	  array(
	    'props'=>$onglet_proprietes,
	    'voir'=>$onglet_contenu,
	    'docs'=>$onglet_documents,
	    'interactivite'=>$onglet_interactivite,	    
	    'discuter'=>$onglet_discuter)
	    :
	  array(
	    'props'=>$onglet_proprietes,
	    'voir'=>$onglet_contenu)
	  )
	  . "</div>"
	  . (_INTERFACE_ONGLETS?"":$onglet_discuter)
	  . fin_gauche()
	  . fin_page();
	}
}

// http://doc.spip.org/@langue_breve
function langue_breve($id_breve, $row){
	$id_rubrique = $row['id_rubrique'];
	//
	// Langue de la breve
	//
	$row2 = sql_fetsel("lang", "spip_rubriques", "id_rubrique=$id_rubrique");
	$langue_parent = $row2['lang'];
	
	$langue_breve = $row['lang'];
	
	$res = "";
	$bouton = bouton_block_depliable(_T('titre_langue_breve')."&nbsp; (".traduire_nom_langue($langue_breve).")",false,'languesbreve');
	$res .= debut_cadre_enfonce('langues-24.gif',true,'',$bouton);
	
	$res .= debut_block_depliable(false,'languesbreve');
	$res .= "<div class='langue'>";

	if ($menu = liste_options_langues('changer_lang', $langue_breve, $langue_parent)) {
		$lien = "\nonchange=\"this.nextSibling.firstChild.style.visibility='visible';\"";
		$menu = select_langues('changer_lang', $lien, $menu)
	. "<span><input type='submit' class='visible_au_chargement fondo' value='". _T('bouton_changer')."' /></span>";
	}
	$res .= redirige_action_auteur('editer_breve', "$id_breve/$id_rubrique", "breves_voir","id_breve=$id_breve", $menu);
	$res .= "</div>\n";
	$res .= fin_block();
	
	$res .= fin_cadre_enfonce(true);
	return $res;
}


// http://doc.spip.org/@afficher_breve_rubrique
function afficher_breve_rubrique($id_breve, $id_rubrique, $statut)
{
	if (!_INTERFACE_ONGLETS) return "";
	global $spip_lang_right;
	$aider = charger_fonction('aider', 'inc');
	$chercher_rubrique = charger_fonction('chercher_rubrique', 'inc');
	
	$form = $chercher_rubrique($id_rubrique, 'breve', ($statut == 'publie'));
	if (strpos($form,'<select')!==false) {
		$form .= "<div style='text-align: $spip_lang_right;'>"
			. '<input class="fondo" type="submit" value="'._T('bouton_choisir').'"/>'
			. "</div>";
	}
	
	$form = generer_action_auteur('editer_breve', $id_breve, generer_url_ecrire('breves_voir'), $form, " method='post' class='submit_plongeur'"	);


	if ($id_rubrique == 0) $logo = "racine-site-24.gif";
	else $logo = "secteur-24.gif";

	return 
		debut_cadre_couleur($logo, true, "",_T('entree_interieur_rubrique').$aider ("brevesrub"))
		. $form
		. fin_cadre_couleur(true);

}
?>
