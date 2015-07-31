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

// http://doc.spip.org/@exec_breves_dist
function exec_breves_dist()
{
	global  $spip_lang_left, $spip_lang_right;

	charger_generer_url();
 	pipeline('exec_init',array('args'=>array('exec'=>'breves'),'data'=>''));
	
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_breves'), "naviguer", "breves");
	debut_gauche();
	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'breves'),'data'=>''));
	creer_colonne_droite();
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'breves'),'data'=>''));
	debut_droite();

 	$result=spip_query("SELECT * FROM spip_rubriques WHERE id_parent=0 ORDER BY 0+titre, titre");

 	while($row=spip_fetch_array($result)){
		$id_rubrique=$row['id_rubrique'];
		$id_parent=$row['id_parent'];
		$titre=typo($row['titre']);
		$descriptif=$row['descriptif'];
		$texte=$row['texte'];
		$editable = autoriser('publierdans','rubrique',$id_rubrique);

		$statuts = "'prop', 'publie'" . ($editable ? ", 'refuse'": "");

		$res = afficher_objets('breve',$titre.aide ("breves"), array("SELECT" => 'id_rubrique, id_breve, date_heure, titre, statut', "FROM" => 'spip_breves', 'WHERE' => "id_rubrique='$id_rubrique' AND statut IN ($statuts)", 'ORDER BY' => "date_heure DESC"),'',true);

		echo $res ;

		if ($editable) {
		  if (!$res) echo bandeau_titre_boite2($titre, "breve-24.gif", 'toile_foncee', "ligne_blanche");
		  echo "<div style='float:$spip_lang_right'>"
		  . icone_inline(_T('icone_nouvelle_breve'), generer_url_ecrire("breves_edit","new=oui&id_rubrique=$id_rubrique"), "breve-24.gif", "creer.gif", $spip_lang_right)
		  . "</div><br class='nettoyeur' />";
		}

	}
	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'breves'),'data'=>''));

	echo fin_gauche(), fin_page();
}

?>
