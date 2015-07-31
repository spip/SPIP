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
			$out .= "<li class='item'><a href='#mots_tous-$id_groupe' onclick='$(\"div.mots_tous\").hide().filter(\"#mots_tous-$id_groupe\").show();return false;'>$titre_groupe</a></li>";
		}
		if (strlen($out))
			$out =
			"<a class='verdana1' href='#' onclick='$(\"div.mots_tous\").show();return false;'>"._T('icone_voir_tous_mots_cles')."</a>"
			."<ul class='liste-items raccourcis_rapides'>"
			.$out
			."</ul>"
			;

		echo debut_boite_info(true) . $out . fin_boite_info(true);
		$res = icone_horizontale(_T('icone_creation_groupe_mots'), generer_url_ecrire("mots_type","new=oui"), "groupe-mot-24.gif", "creer.gif",false);
		echo bloc_des_raccourcis($res);
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
			$acces_forum = $row_groupes['forum'];

			// Afficher le titre du groupe
			echo "<div id='mots_tous-$id_groupe' class='mots_tous'>";

			echo debut_cadre_enfonce("groupe-mot-24.gif", true, '', $titre_groupe);
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
			if ($acces_forum == "oui") $res .= "> "._T('info_visiteurs_02')." &nbsp;&nbsp;";

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

			$groupe = sql_countsel("spip_mots", "id_groupe=$id_groupe");

			echo "<div\nid='editer_mots-$id_groupe' style='position: relative;'>";

			// Preliminaire: confirmation de suppression d'un mot lie a qqch
			// (cf fin de afficher_groupe_mots_boucle executee a l'appel precedent)
			if ($conf_mot  AND $son_groupe==$id_groupe) {
				echo confirmer_mot($conf_mot, $row_groupes, $groupe);
			}
			if ($groupe) {
					$grouper_mots = charger_fonction('grouper_mots', 'inc');
				echo $grouper_mots($id_groupe, $groupe);
			}

			echo "</div>";

			if (autoriser('modifier','groupemots',$id_groupe)){
				echo "\n<table cellpadding='0' cellspacing='0' border='0' width='100%'>";
				echo "<tr>";
				echo "<td>";
				echo icone_inline(_T('icone_modif_groupe_mots'), generer_url_ecrire("mots_type","id_groupe=$id_groupe"), "groupe-mot-24.gif", "edit.gif", $spip_lang_left);
				echo "</td>";
				echo "\n<td id='editer_mots-$id_groupe-supprimer'",
					(!$groupe ? '' : " style='visibility: hidden'"),
					">";
				echo icone_inline(_T('icone_supprimer_groupe_mots'), redirige_action_auteur('instituer_groupe_mots', "-$id_groupe", "mots_tous"), "groupe-mot-24.gif", "supprimer.gif", $spip_lang_left);
				echo "</td>";
				echo "<td>";
				echo icone_inline(_T('icone_creation_mots_cles'), generer_url_ecrire("mots_edit","new=oui&id_groupe=$id_groupe&redirect=" . generer_url_retour('mots_tous', "#mots_tous-$id_groupe")), "mot-cle-24.gif", "creer.gif", $spip_lang_right);
				echo "</td></tr></table>";
			}

			echo fin_cadre_enfonce(true);
			echo "</div>";
		}
	}

	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'mots_tous'),'data'=>''));


	echo fin_gauche(), fin_page();
}

// http://doc.spip.org/@confirmer_mot
function confirmer_mot ($id_mot, $row_groupe, $total)
{
	$row = sql_fetsel("titre", "spip_mots", "id_mot=$id_mot");
	if (!$row) return ""; // deja detruit (acces concurrent etc)

	if (!autoriser('modifier', 'mot', $id_mot, null, array('id_groupe' => $row_groupe['id_groupe'])))
		return ''; // usurpateur

	include_spip('inc/grouper_mots');
	$titre_mot = typo($row['titre']);
	$type_mot = typo($row_groupe['titre']);
	$son_groupe = $row_groupe['id_groupe'];

	if (($na = intval(_request('na'))) == 1) {
		$texte_lie = _T('info_un_article')." ";
	} else if ($na > 1) {
		$texte_lie = _T('info_nombre_articles', array('nb_articles' => $na)) ." ";
	}
	if (($nb = intval(_request('nb'))) == 1) {
		$texte_lie .= _T('info_une_breve')." ";
	} else if ($nb > 1) {
		$texte_lie .= _T('info_nombre_breves', array('nb_breves' => $nb))." ";
	}
	if (($ns = intval(_request('ns'))) == 1) {
		$texte_lie .= _T('info_un_site')." ";
	} else if ($ns > 1) {
		$texte_lie .= _T('info_nombre_sites', array('nb_sites' => $ns))." ";
	}
	if (($nr = intval(_request('nr'))) == 1) {
		$texte_lie .= _T('info_une_rubrique')." ";
	} else if ($nr > 1) {
		$texte_lie .= _T('info_nombre_rubriques', array('nb_rubriques' => $nr))." ";
	}

	return debut_boite_info(true)
	. "<div class='serif'>"
	. _T('info_delet_mots_cles', array('titre_mot' => $titre_mot, 'type_mot' => $type_mot, 'texte_lie' => $texte_lie))
	. "<p style='text-align: right'>"
	. generer_supprimer_mot($id_mot, $son_groupe, ("<b>" . _T('item_oui') . "</b>"), $total)
	. "<br />\n"
	.  _T('info_oui_suppression_mot_cle')
	. '</p>'
	  /* troublant. A refaire avec une visibility
	 . "<li><b><a href='" 
	. generer_url_ecrire("mots_tous")
	. "#editer_mots-$son_groupe"
	. "'>"
	. _T('item_non')
	. "</a>,</b> "
	. _T('info_non_suppression_mot_cle')
	. "</ul>" */
	. "</div>"
	. fin_boite_info(true);
}
?>
