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
include_spip('inc/actions');

// http://doc.spip.org/@exec_mots_tous_dist
function exec_mots_tous_dist()
{
	global $spip_lang, $spip_lang_left, $spip_lang_right;

	$conf_mot = intval(_request('conf_mot'));
	$son_groupe = intval(_request('son_groupe'));

	pipeline('exec_init',array('args'=>array('exec'=>'mots_tous'),'data'=>''));
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_mots_tous'), "naviguer", "mots");
	echo debut_gauche('', true);


	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'mots_tous'),'data'=>''));

	if (autoriser('creer','groupemots')  AND !$conf_mot){
		$out = "";
		$result = sql_select("*, ".sql_multi ("titre", "$spip_lang"), "spip_groupes_mots", "", "", "multi");
		while ($row_groupes = sql_fetch($result)) {
			$id_groupe = $row_groupes['id_groupe'];
			$titre_groupe = typo($row_groupes['titre']);		
			$out .= "<li><a href='#mots_tous-$id_groupe' onclick='$(\"div.mots_tous\").hide().filter(\"#mots_tous-$id_groupe\").show();return false;'>$titre_groupe</a></li>";
		}
		if (strlen($out))
			$out = "
			<ul class='raccourcis_rapides'>".$out."</ul>
			<a href='#' onclick='$(\"div.mots_tous\").show();return false;'>"._T('icone_voir_tous_mots_cles')."</a>";

		$res = icone_horizontale(_T('icone_creation_groupe_mots'), generer_url_ecrire("mots_type","new=oui"), "groupe-mot-24.png", "new",false);
		echo bloc_des_raccourcis($res . $out);
	}


	echo creer_colonne_droite('', true);
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'mots_tous'),'data'=>''));
	echo debut_droite('', true);

	echo gros_titre(_T('titre_mots_tous'),'', false);
	if (autoriser('creer','groupemots')) {
	  echo typo(_T('info_creation_mots_cles')) . aide ("mots") ;
	}
	echo "<br /><br />";

//
// On boucle d'abord sur les groupes de mots
//

	$result = sql_select("*, ".sql_multi ("titre", "$spip_lang"), "spip_groupes_mots", "", "", "multi");

	while ($row_groupes = sql_fetch($result)) {
		if (autoriser('voir','groupemots',$row_groupes['id_groupe'])){
			$id_groupe = $row_groupes['id_groupe'];
			$titre_groupe = typo($row_groupes['titre']);
			$descriptif = $row_groupes['descriptif'];
			$texte = $row_groupes['texte'];
			$unseul = $row_groupes['unseul'];
			$obligatoire = $row_groupes['obligatoire'];
			$tables_liees = $row_groupes['tables_liees'];
			$acces_minirezo = $row_groupes['minirezo'];
			$acces_comite = $row_groupes['comite'];
			$acces_visiteur = $row_groupes['forum'];

			// Afficher le titre du groupe
			echo "<div id='mots_tous-$id_groupe' class='mots_tous'>";

			echo debut_cadre_enfonce("groupe-mot-24.png", true, '', $titre_groupe);
			// Affichage des options du groupe (types d'elements, permissions...)
			$res = '';
			$tables_liees = explode(',',$tables_liees);

			$libelles = array('articles'=>'info_articles_2','breves'=>'info_breves_02','rubriques'=>'info_rubriques','syndic'=>'icone_sites_references');
			$libelles = pipeline('libelle_association_mots',$libelles);
			foreach($tables_liees as $table)
				if (strlen($table))
					$res .= "> " . _T(isset($libelles[$table])?$libelles[$table]:"$table:info_$table") . " &nbsp;&nbsp;";

			if ($unseul == "oui" OR $obligatoire == "oui") $res .= "<br />";
			if ($unseul == "oui") $res .= "> "._T('info_un_mot')." &nbsp;&nbsp;";
			if ($obligatoire == "oui") $res .= "> "._T('info_groupe_important')." &nbsp;&nbsp;";

			$res .= "<br />";
			if ($acces_minirezo == "oui") $res .= "> "._T('info_administrateurs')." &nbsp;&nbsp;";
			if ($acces_comite == "oui") $res .= "> "._T('info_redacteurs')." &nbsp;&nbsp;";
			if ($acces_visiteur == "oui") $res .= "> "._T('info_visiteurs_02')." &nbsp;&nbsp;";

			echo "<span class='verdana1 spip_x-small'>", $res, "</span>";
			if (strlen($descriptif)) {
				echo "<div style='border: 1px dashed #aaa; background-color: #fff;' class='verdana1 spip_x-small '>", propre("{{"._T('info_descriptif')."}} ".$descriptif), "&nbsp; </div>";
			}

			if (strlen($texte)>0){
				echo "<div class='verdana1 spip_small'>", propre($texte), "</div>";
			}

			//
			// Afficher les mots-cles du groupe
			//
			echo "<div id='editer_mots-$id_groupe'>";

			$lister_objets = charger_fonction('lister_objets','inc');
			echo $lister_objets('mots-admin',array(
					'id_groupe'=>$id_groupe,
					'retour'=> ancre_url(generer_url_ecrire('mots_tous','',false,true),"editer_mots-$id_groupe")
					));

			echo "</div>";

			if (autoriser('modifier','groupemots',$id_groupe)){
				echo "\n<table border='0' width='100%'>";
				echo "<tr>";
				echo "<td>";
				echo icone_inline(_T('icone_modif_groupe_mots'), generer_url_ecrire("mots_type","id_groupe=$id_groupe"), "groupe-mot-24.png", "edit", $spip_lang_left);
				echo "</td>";
				echo "\n<td id='editer_mots-$id_groupe-supprimer'",
					(!$groupe ? '' : " style='visibility: hidden'"),
					">";
				echo icone_inline(_T('icone_supprimer_groupe_mots'), redirige_action_auteur('instituer_groupe_mots', "-$id_groupe", "mots_tous"), "groupe-mot-24.png", "del", $spip_lang_left);
				echo "</td>";
				echo "<td>";
				echo icone_inline(_T('icone_creation_mots_cles'), generer_url_ecrire("mots_edit","new=oui&id_groupe=$id_groupe&redirect=" . generer_url_retour('mots_tous', "#mots_tous-$id_groupe")), "mot-24.png", "new", $spip_lang_right);
				echo "</td></tr></table>";
			}

			echo fin_cadre_enfonce(true);
			echo "</div>";
		}
	}

	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'mots_tous'),'data'=>''));


	echo fin_gauche(), fin_page();
}

?>
