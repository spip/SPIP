<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');

// http://doc.spip.org/@exec_breves_dist
function exec_breves_dist()
{
	global  $spip_lang_left, $spip_lang_right;

 	pipeline('exec_init',array('args'=>array('exec'=>'breves'),'data'=>''));
	
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_breves'), "naviguer", "breves");
	echo debut_gauche('', true);
	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'breves'),'data'=>''));
	echo creer_colonne_droite('', true);
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'breves'),'data'=>''));
	echo debut_droite('', true);

 	$result = sql_select('*', "spip_rubriques", "id_parent=0",'', '0+titre,titre');
	$lister_objets = charger_fonction('lister_objets','inc');

 	while($row=sql_fetch($result)){
		$id_rubrique=$row['id_rubrique'];
		$id_parent=$row['id_parent'];
		$titre=typo($row['titre']);
		$descriptif=$row['descriptif'];
		$texte=$row['texte'];
		$editable = autoriser('publierdans','rubrique',$id_rubrique);

		$statuts = array('prop', 'publie');
		if ($editable)
			$statuts[] = 'refuse';

		$res = $lister_objets('breves',array('titre'=>$titre.aide ("breves"),'statut'=>$statuts, 'id_rubrique'=>$id_rubrique, 'par'=>'date_heure'), true);

		echo $res ;

		if ($editable) {
		  echo "<div style='float:$spip_lang_right'>"
		  . icone_inline(_T('icone_nouvelle_breve'), generer_url_ecrire("breves_edit","new=oui&id_rubrique=$id_rubrique"), "breve-24.png", "new", $spip_lang_right)
		  . "</div><br class='nettoyeur' />";
		}

	}
	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'breves'),'data'=>''));

	echo fin_gauche(), fin_page();
}

?>
