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
include_spip('inc/documents');

// http://doc.spip.org/@exec_rubriques_edit_dist
function exec_rubriques_edit_dist()
{
	exec_rubriques_edit_args(intval(_request('id_rubrique')), intval(_request('id_parent')), _request('new'), intval(_request('lier_trad')));
}

// http://doc.spip.org/@exec_rubriques_edit_args
function exec_rubriques_edit_args($id_rubrique, $id_parent, $new, $lier_trad)
{
	global $connect_toutes_rubriques, $connect_statut, $spip_lang_right;

	$titre = false;

	if ($new == "oui") {
		$id_rubrique = 0;
		$titre = filtrer_entites(_T('titre_nouvelle_rubrique'));

		if (!autoriser('creerrubriquedans','rubrique',$id_parent)) {
			$id_parent = intval(reset($GLOBALS['connect_id_rubrique']));
		}
	} else {
		$row = sql_fetsel("*", "spip_rubriques", "id_rubrique=$id_rubrique");
		if ($row) {
	
			$id_parent = $row['id_parent'];
			$titre = $row['titre'];
			$id_secteur = $row['id_secteur'];
		}
	}
	$commencer_page = charger_fonction('commencer_page', 'inc');

	if ($titre === false
	OR ($new=='oui' AND !autoriser('creerrubriquedans','rubrique',$id_parent))
	OR ($new!='oui' AND !autoriser('modifier','rubrique',$id_rubrique)))  {
		include_spip('inc/minipres');
		echo minipres();
	} else {

	pipeline('exec_init',array('args'=>array('exec'=>'rubriques_edit','id_rubrique'=>$id_rubrique),'data'=>''));
	echo $commencer_page(_T('info_modifier_titre', array('titre' => $titre)), "naviguer", "rubriques", $id_rubrique);

	if ($id_parent == 0) $ze_logo = "secteur-24.gif";
	else $ze_logo = "rubrique-24.gif";

	echo debut_grand_cadre(true);
	echo afficher_hierarchie($id_parent,'',$id_rubrique,'rubrique');
	echo fin_grand_cadre(true);

	echo debut_gauche('', true);

	// Pave "documents associes a la rubrique"
	if (!$new){
		# affichage sur le cote des pieces jointes, en reperant les inserees
		# note : traiter_modeles($texte, true) repere les doublons
		# aussi efficacement que propre(), mais beaucoup plus rapidement
		traiter_modeles(join('',$row), true);
		echo afficher_documents_colonne($id_rubrique, 'rubrique');
	} 

	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'rubriques_edit','id_rubrique'=>$id_rubrique),'data'=>''));
	echo creer_colonne_droite('', true);
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'rubriques_edit','id_rubrique'=>$id_rubrique),'data'=>''));	  
	echo debut_droite('', true);

	$contexte = array(
	'icone_retour'=>icone_inline(_T('icone_retour'), generer_url_ecrire("naviguer","id_rubrique=$id_rubrique"), $ze_logo, "rien.gif",$GLOBALS['spip_lang_left']),
	'redirect'=>generer_url_ecrire("naviguer"),
	'titre'=>$titre,
	'new'=>$new == "oui"?$new:$id_rubrique,
	'id_rubrique'=>$id_parent, // pour permettre la specialisation par la rubrique appelante
	'config_fonc'=>'rubriques_edit_config',
	'lier_trad'=>$lier_trad
	);

	echo recuperer_fond("prive/editer/rubrique", $contexte);

	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'rubriques_edit','id_rubrique'=>$id_rubrique),'data'=>''));	  

	echo fin_gauche(), fin_page();
	}
}
?>
