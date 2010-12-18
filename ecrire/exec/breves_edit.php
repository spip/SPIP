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

// http://doc.spip.org/@exec_breves_edit_dist
function exec_breves_edit_dist()
{
	exec_breves_edit_args(intval(_request('id_breve')),
		intval(_request('id_rubrique')),
		_request('new'));
}

// http://doc.spip.org/@exec_breves_edit_args
function exec_breves_edit_args($id_breve, $id_rubrique, $new)
{
	global $connect_id_rubrique;
	// appel du script a la racine, faut choisir 
	// on prend le dernier secteur cree
	// dans une liste restreinte si admin restreint

	if ($new === 'oui' AND $id_rubrique)
		$id_rubrique = sql_getfetsel('id_secteur', 'spip_rubriques', "id_rubrique=$id_rubrique");

	if (!$id_rubrique) {
		$in = !count($connect_id_rubrique)
		  ? '' 
		  : (" AND " . sql_in('id_rubrique', $connect_id_rubrique));

		$id_rubrique = sql_getfetsel('id_rubrique','spip_rubriques', "id_parent=0$in",'',  "id_rubrique DESC", 1);

		if (!autoriser('creerbrevedans','rubrique',$id_rubrique )){
			// manque de chance, la rubrique n'est pas autorisee, on cherche un des secteurs autorises
			$res = sql_select("id_rubrique", "spip_rubriques", "id_parent=0");
			while (!autoriser('creerbrevedans','rubrique',$id_rubrique ) && $row_rub = sql_fetch($res)){
				$id_rubrique = $row_rub['id_rubrique'];
			}
		}
	}
	

	$row = false;
	if (!( ($new!='oui' AND (!autoriser('voir','breve',$id_breve) OR !autoriser('modifier','breve', $id_breve)))
	       OR ($new=='oui' AND !autoriser('creerbrevedans','rubrique',$id_rubrique)) )) {
		if ($new != "oui")
			$row = sql_fetsel("*", "spip_breves", "id_breve=$id_breve");
		else $row = true;
	}
	if (!$row) {
		include_spip('inc/minipres');
		echo minipres();
	} else  breves_edit_ok($row, $id_breve, $id_rubrique, $new);
}

// http://doc.spip.org/@breves_edit_ok
function breves_edit_ok($row, $id_breve, $id_rubrique, $new)
{
	global  $connect_statut, $spip_lang_right;

	if ($new != 'oui') {
		$id_breve=$row['id_breve'];
		$titre=$row['titre'];
		$statut=$row['statut'];
		$id_rubrique=$row['id_rubrique'];
	} else {
		$titre = filtrer_entites(_T('titre_nouvelle_breve'));
		$statut = "prop";
	}

	$commencer_page = charger_fonction('commencer_page', 'inc');
	pipeline('exec_init',array('args'=>array('exec'=>'breves_edit','id_breve'=>$id_breve),'data'=>''));

	echo $commencer_page(_T('titre_page_breves_edit', array('titre' => $titre)), "naviguer", "breves", $id_rubrique);

	echo debut_grand_cadre(true);
	echo afficher_hierarchie($id_rubrique);

	echo fin_grand_cadre(true);
	echo debut_gauche('', true);
	if ($new != 'oui' AND ($connect_statut=="0minirezo" OR $statut=="prop")) {
	# affichage sur le cote des images, en reperant les inserees
	# note : traiter_modeles($texte, true) repere les doublons
	# aussi efficacement que propre(), mais beaucoup plus rapidement
		traiter_modeles("$titre$texte", true);
		echo afficher_documents_colonne($id_breve, "breve");
	}
	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'breves_edit','id_breve'=>$id_breve),'data'=>''));
	echo creer_colonne_droite('', true);
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'breves_edit','id_breve'=>$id_breve),'data'=>''));
	echo debut_droite('', true);

	$contexte = array(
	'icone_retour'=>$new=='oui'?'':icone_inline(_T('icone_retour'), generer_url_ecrire("breves_voir","id_breve=$id_breve"), "breve-24.gif", "rien.gif",$GLOBALS['spip_lang_left']),
	'redirect'=>generer_url_ecrire("breves_voir"),
	'titre'=>$titre,
	'new'=>$new == "oui"?$new:$id_breve,
	'id_rubrique'=>$id_rubrique,
	'config_fonc'=>'breves_edit_config'
	);

	echo recuperer_fond("prive/editer/breve", $contexte);

	echo fin_gauche(), fin_page();

}

?>
