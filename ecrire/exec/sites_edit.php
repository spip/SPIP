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

// http://doc.spip.org/@exec_sites_edit_dist
function exec_sites_edit_dist()
{
	global $connect_statut, $connect_id_rubrique, $spip_lang_right;

	$id_syndic = intval(_request('id_syndic'));
	$row = sql_fetsel("*", "spip_syndic", "id_syndic=$id_syndic");

	if ($row) {
		$id_syndic = $row["id_syndic"];
		$id_rubrique = $row["id_rubrique"];
		$nom_site = $row["nom_site"];
		$new = false;
	} else {
		$id_rubrique = intval(_request('id_rubrique'));
		$new = 'oui';
		$nom_site = '';
		if (!$id_rubrique) {
			$in = !$connect_id_rubrique ? ''
			  : sql_in('id_rubrique', $connect_id_rubrique);
			$id_rubrique = sql_getfetsel('id_rubrique', 'spip_rubriques', $in, '',  'id_rubrique DESC',  1);
		}
		if (!autoriser('creersitedans','rubrique',$id_rubrique )){
			// manque de chance, la rubrique n'est pas autorisee, on cherche un des secteurs autorises
			$res = sql_select("id_rubrique", "spip_rubriques", "id_parent=0");
			while (!autoriser('creersitedans','rubrique',$id_rubrique ) && $t = sql_fetch($res)){
				$id_rubrique = $t['id_rubrique'];
			}
		}
	}

	if ( ($new!='oui' AND (!autoriser('voir','site',$id_syndic) OR !autoriser('modifier','site',$id_syndic)))
	  OR ($new=='oui' AND !autoriser('creersitedans','rubrique',$id_rubrique)) ){
		include_spip('inc/minipres');
		echo minipres();
	} else {

	$commencer_page = charger_fonction('commencer_page', 'inc');
	pipeline('exec_init',array('args'=>array('exec'=>'sites_edit','id_syndic'=>$id_syndic),'data'=>''));

	echo $commencer_page(_T('info_site_reference_2'), "naviguer", "sites", $id_rubrique);

	echo debut_grand_cadre(true);

	echo afficher_hierarchie($id_rubrique);

	echo fin_grand_cadre(true);

	echo debut_gauche('', true);
	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'sites_edit','id_syndic'=>$id_syndic),'data'=>''));
	echo creer_colonne_droite('', true);
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'sites_edit','id_syndic'=>$id_syndic),'data'=>''));	  
	echo debut_droite('', true);

	$contexte = array(
	'icone_retour'=>$new=='oui'?'':icone_inline(_T('icone_retour'), generer_url_ecrire("sites","id_syndic=$id_syndic"), "site-24.gif", "rien.gif",$GLOBALS['spip_lang_left']),
	'redirect'=>generer_url_ecrire("sites"),
	'titre'=>$nom_site,
	'new'=>$new == "oui"?$new:$id_syndic,
	'id_rubrique'=>$id_rubrique,
	'config_fonc'=>'sites_edit_config'
	);

	echo recuperer_fond("prive/editer/site", $contexte);

	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'sites_edit','id_syndic'=>$id_syndic),'data'=>''));
	echo fin_gauche(), fin_page();
	}
}
?>
