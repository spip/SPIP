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
include_spip('base/abstract_sql');
include_spip("inc/indexation");

// http://doc.spip.org/@afficher_breves_voir
function afficher_breves_voir($id_breve, $cherche_mot, $select_groupe)
{
	global $champs_extra, $les_notes, $spip_display;
	$result = spip_query("SELECT * FROM spip_breves WHERE id_breve=$id_breve");

	if ($row = sql_fetch($result)) {
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
	}
	else {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}

	$commencer_page = charger_fonction('commencer_page', 'inc');
	if (!autoriser('voir','breve',$id_breve)){
		echo $commencer_page("&laquo; $titre_breve &raquo;", "naviguer", "breves", $id_rubrique);
		echo "<strong>"._T('avis_acces_interdit')."</strong>";
		echo fin_page();
		exit;
	}

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

	echo $commencer_page("&laquo; $titre_breve &raquo;", "naviguer", "breves", $id_rubrique);
	
	echo debut_grand_cadre(true);
	
	echo afficher_hierarchie($id_rubrique);
	
	echo fin_grand_cadre(true);
	if (!$row) {echo _T('public:aucune_breve'); exit;}
	echo debut_gauche('', true);
	
	echo debut_boite_info(true);
	
	$res = "\n<div style='font-weight: bold; text-align: center' class='verdana1 spip_xx-small'>" 
	. _T('info_gauche_numero_breve')
	. "<br /><span class='spip_xx-large'>"
	. $id_breve
	. '</span></div>';

	echo $res;
	echo voir_en_ligne ('breve', $id_breve, $statut,'', false);
	
	echo fin_boite_info(true);


	//////////////////////////////////////////////////////
	// Logos de la breve
	//

	if (($spip_display != 4) AND $id_breve>0 AND autoriser('publierdans','rubrique',$id_rubrique)) {
		$iconifier = charger_fonction('iconifier', 'inc');
		echo $iconifier('id_breve', $id_breve, 'breves_voir'); 
	}

	echo pipeline('affiche_gauche',
		array(
		'args'=>array('exec'=>'breves_voir','id_breve'=>$id_breve),
		'data'=>''
		)
	);

	echo bloc_des_raccourcis(icone_horizontale(_T('icone_nouvelle_breve'), generer_url_ecrire("breves_edit","new=oui&id_rubrique=$id_rubrique"), "breve-24.gif","creer.gif", false));

	

	echo creer_colonne_droite(true);

	echo pipeline('affiche_droite',
		array(
		'args'=>array('exec'=>'breves_voir','id_breve'=>$id_breve),
		'data'=>''
		)
	);

	echo meme_rubrique($id_rubrique, $id_breve, 'breve', 'date_heure');

	echo debut_droite('', true);
	
	echo debut_cadre_relief("breve-24.gif", true);
	//echo "</td>";

	if ($flag_editable) {
		echo icone_inline(
			// TODO -- _L("Fil a travaille sur cette breve il y a x minutes")
			!$modif ? _T('icone_modifier_breve')
				: _T('texte_travail_article', $modif),
			generer_url_ecrire("breves_edit","id_breve=$id_breve&retour=nav"),
			!$modif ? "breve-24.gif" : "warning-24.gif",
			!$modif ? "edit.gif" : '',
			$GLOBALS['spip_lang_right']
		);
	}
	echo gros_titre($titre,'', false). "<br class='nettoyeur' />";

	if ($flag_editable AND ($statut == 'publie')) {
	
		if ($statut == 'publie') {	
	
			$dater = charger_fonction('dater', 'inc');
			echo $dater($id_breve, $flag_editable, $statut, 'breve', 'breves_voir', $date_heure);
		}
		else {
			echo "<p><span class='verdana1 spip_medium'><b>".affdate($date_heure)."&nbsp;</b></span></p>";
		}
	}

	$editer_mot = charger_fonction('editer_mot', 'inc');
	echo $editer_mot('breve', $id_breve, $cherche_mot, $select_groupe, $flag_editable);

	//
	// Langue de la breve
	//
	if (($GLOBALS['meta']['multi_articles'] == 'oui') AND ($flag_editable)) {
		$row = sql_fetch(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
		$langue_parent = $row['lang'];
	
		$row = sql_fetch(spip_query("SELECT lang, langue_choisie FROM spip_breves WHERE id_breve=$id_breve"));
		$langue_breve = $row['lang'];
	
		$bouton = bouton_block_depliable(_T('titre_langue_breve')."&nbsp; (".traduire_nom_langue($langue_breve).")",false,'languesbreve');
		echo debut_cadre_enfonce('langues-24.gif',true,'',$bouton);
	
		echo debut_block_depliable(false,'languesbreve');
		echo "<div style='text-align: center'>";

		if ($menu = liste_options_langues('changer_lang', $langue_breve, $langue_parent))
			$lien = "\nonchange=\"this.nextSibling.firstChild.style.visibility='visible';\"";
			$menu = select_langues('changer_lang', $lien, $menu)
			. "<span><input type='submit' class='visible_au_chargement fondo' value='". _T('bouton_changer')."' /></span>";

		echo redirige_action_auteur('editer_breve', "$id_breve/$id_rubrique", "breves_voir","id_breve=$id_breve", $menu);
		echo "</div>\n";
		echo fin_block();
	
		echo fin_cadre_enfonce(true);
	}
	echo pipeline('affiche_milieu',
		array(
		'args'=>array('exec'=>'breves_voir','id_breve'=>$id_breve),
		'data'=>''
		)
	);

	echo justifier(propre($texte))."\n";

	$texte_case = ($lien_titre.vider_url($lien_url)) ? "{{"._T('lien_voir_en_ligne')."}} [".$lien_titre."->".$lien_url."]" : '';
	echo propre($texte_case);

	if ($les_notes) {
		echo "<hr width='70%' height='1' align='left'><span class='spip_small'>$les_notes</span>\n";
	}

	// afficher les extra
	if ($champs_extra AND $extra) {
		include_spip('inc/extra');
		echo extra_affichage($extra, "breves");
	}

	if (autoriser('publierdans','rubrique',$id_rubrique) AND ($statut=="prop" OR $statut=="prepa")){
		echo "<div style='float: right; margin: 10px;'>";
		echo icone_inline(_T('icone_refuser_breve'), 
		      redirige_action_auteur('editer_breve', "$id_breve-statut-refuse", "breves_voir","id_breve=$id_breve"), "breve-24.gif", "supprimer.gif");
		echo "</div>";
		echo "<div style='float: right; margin: 10px;'>";
		echo icone_inline(_T('icone_publier_breve'), 
		      redirige_action_auteur('editer_breve',"$id_breve-statut-publie","breves_voir","id_breve=$id_breve"), "breve-24.gif", "racine-24.gif");
		echo "</div>";
	}	


	echo fin_cadre_relief(true);
	
	//////////////////////////////////////////////////////
	// Forums
	//
	
	echo "<br /><br />";
	
	echo "\n<div class='centered'>";
	echo icone_inline(_T('icone_poster_message'), generer_url_ecrire("forum_envoi", "statut=prive&id=$id_breve&script=breves_voir") . '#formulaire',
	     "forum-interne-24.gif", "creer.gif");
	echo "</div>";
	
	echo "<br />";
	
	echo afficher_forum(sql_select("*", 'spip_forum', "statut='prive' AND id_breve=$id_breve AND id_parent=0",'', "date_heure DESC",  "20"), "breves_voir", "id_breve=$id_breve");
	
	echo fin_gauche(), fin_page();
}

// http://doc.spip.org/@exec_breves_voir_dist
function exec_breves_voir_dist()
{
	afficher_breves_voir(intval(_request('id_breve')), _request('cherche_mot'), _request('select_groupe'));
}


?>
