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
	global $champs_extra, $options, $connect_statut, $les_notes,$spip_display;
	$result = spip_query("SELECT * FROM spip_breves WHERE id_breve='$id_breve'");

	if ($row = spip_fetch_array($result)) {
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
	} else 
	      {include_spip('minipres');
		echo minipres();
		exit;
	      }

	$flag_editable = (($connect_statut == '0minirezo' AND acces_rubrique($id_rubrique)) OR $statut == 'prop');

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

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page("&laquo; $titre_breve &raquo;", "naviguer", "breves", $id_rubrique);
	
	debut_grand_cadre();
	
	echo afficher_hierarchie($id_rubrique);
	
	fin_grand_cadre();
	if (!$row) {echo _T('public:aucune_breve'); exit;}
	debut_gauche();
	
	debut_boite_info();
	
	echo "<center>";
	echo "<font face='Verdana,Arial,Sans,sans-serif' size='1'><b>"._T('info_gauche_numero_breve')."&nbsp;:</b></font>";
	echo "<br /><font face='Verdana,Arial,Sans,sans-serif' size='6'><b>$id_breve</b></font>";
	echo "</center>";
	
	voir_en_ligne ('breve', $id_breve, $statut);
	
	fin_boite_info();


	//////////////////////////////////////////////////////
	// Logos de la breve
	//

	if (($spip_display != 4) AND $id_breve>0 AND ($connect_statut == '0minirezo' AND acces_rubrique($id_rubrique))) {
		$iconifier = charger_fonction('iconifier', 'inc');
		echo $iconifier('id_breve', $id_breve, 'breves_voir'); 
	}

	echo bloc_des_raccourcis(icone_horizontale(_T('icone_nouvelle_breve'), generer_url_ecrire("breves_edit","new=oui&id_rubrique=$id_rubrique"), "breve-24.gif","creer.gif", false));

	
	echo pipeline('affiche_gauche',
		array(
		'args'=>array('exec'=>'breves_voir','id_breve'=>$id_breve),
		'data'=>''
		)
	);

	creer_colonne_droite();

	echo pipeline('affiche_droite',
		array(
		'args'=>array('exec'=>'breves_voir','id_breve'=>$id_breve),
		'data'=>''
		)
	);

	echo meme_rubrique($id_rubrique, $id_breve, 'breve', 'date_heure');

	debut_droite();
	
	debut_cadre_relief("breve-24.gif");
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr><td class='serif'>";

	echo "\n<table cellpadding='0' cellspacing='0' border='0' width='100%'>";
	echo "<tr><td width='100%' valign='top'>";
	gros_titre($titre);
	echo "</td>";

	if ($flag_editable) {
		echo "<td>", http_img_pack("rien.gif", ' ', "width='5'") ."</td>\n";
		echo "<td  align='right'>";
		icone(
			// TODO -- _L("Fil a travaille sur cette breve il y a x minutes")
			!$modif ? _T('icone_modifier_breve')
				: _T('texte_travail_article', $modif),
			generer_url_ecrire("breves_edit","id_breve=$id_breve&retour=nav"),
			!$modif ? "breve-24.gif" : "warning-24.gif",
			!$modif ? "edit.gif" : ''
		);
		echo "</td>";
	}
	echo "</tr></table>\n";

	if ($flag_editable AND ($options == 'avancees' OR $statut == 'publie')) {
	
		if ($statut == 'publie') {	
	
			debut_cadre_enfonce();
			$dater = charger_fonction('dater', 'inc');
			echo $dater($id_breve, $flag_editable, $statut, 'breve', 'breves_voir', $date_heure);
			fin_cadre_enfonce();	
		}
		else {
			echo "<p><font face='Verdana,Arial,Sans,sans-serif' size='3'><b>".affdate($date_heure)."&nbsp;</b></font></p>";
		}
	}

	$editer_mot = charger_fonction('editer_mot', 'inc');
	echo $editer_mot('breve', $id_breve, $cherche_mot, $select_groupe, $flag_editable);

	//
	// Langue de la breve
	//
	if (($GLOBALS['meta']['multi_articles'] == 'oui') AND ($flag_editable)) {
		$row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
		$langue_parent = $row['lang'];
	
		$row = spip_fetch_array(spip_query("SELECT lang, langue_choisie FROM spip_breves WHERE id_breve=$id_breve"));
		$langue_breve = $row['lang'];
	
		debut_cadre_enfonce('langues-24.gif');
	
		echo "<table border='0' cellspacing='0' cellpadding='3' width='100%'><tr><td bgcolor='#EEEECC' class='serif2'>";
		echo bouton_block_invisible('languesbreve');
		echo "<b>";
		echo _T('titre_langue_breve');
		echo "&nbsp; (".traduire_nom_langue($langue_breve).")";
		echo "</b>";
		echo "</td></tr></table>";
	
		echo debut_block_invisible('languesbreve');
		echo "<center>";
		$menu = menu_langues('changer_lang', $langue_breve, '', $langue_parent,'ajax');
		echo redirige_action_auteur('editer_breve', "$id_breve/$id_rubrique", "breves_voir","id_breve=$id_breve", $menu);
		echo "</center>\n";
		echo fin_block();
	
		fin_cadre_enfonce();
	}

	echo justifier(propre($texte))."\n";

	$texte_case = ($lien_titre.vider_url($lien_url)) ? "{{"._T('lien_voir_en_ligne')."}} [".$lien_titre."->".$lien_url."]" : '';
	echo propre($texte_case);

	if ($les_notes) {
		echo "<hr width='70%' height='1' align='left'><font size='2'>$les_notes</font>\n";
	}

	// afficher les extra
	if ($champs_extra AND $extra) {
		include_spip('inc/extra');
		echo extra_affichage($extra, "breves");
	}

	if ($connect_statut=="0minirezo" AND acces_rubrique($id_rubrique) AND ($statut=="prop" OR $statut=="prepa")){
		echo "<div align='right'>";
		
		echo "<table><tr>";
		echo "<td  align='right'>";
		icone(_T('icone_publier_breve'), 
		      redirige_action_auteur('editer_breve',"$id_breve-statut-publie","breves_voir","id_breve=$id_breve"), "breve-24.gif", "racine-24.gif");
		echo "</td>";
		
		echo "<td>", http_img_pack("rien.gif", ' ', "width='5'") ."</td>\n";
		echo "<td  align='right'>";
		icone(_T('icone_refuser_breve'), 
		      redirige_action_auteur('editer_breve', "$id_breve-statut-refuse", "breves_voir","id_breve=$id_breve"), "breve-24.gif", "supprimer.gif");
		echo "</td></tr>";
		echo "</table>";	
		
		echo "</div>";
	}	

	echo "</td></tr></table>";

	fin_cadre_relief();
	
	//////////////////////////////////////////////////////
	// Forums
	//
	
	echo "<br /><br />";
	
	echo "\n<div align='center'>";
	icone(_T('icone_poster_message'), generer_url_ecrire("forum_envoi", "statut=prive&id=$id_breve&script=breves_voir") . '#formulaire',
	     "forum-interne-24.gif", "creer.gif");
	echo "</div>";
	
	echo "<br />";
	
	echo afficher_forum(spip_query("SELECT * FROM spip_forum WHERE statut='prive' AND id_breve='$id_breve' AND id_parent=0 ORDER BY date_heure DESC LIMIT 20"), "breves_voir", "id_breve=$id_breve");
	
	echo fin_gauche(), fin_page();
}

// http://doc.spip.org/@exec_breves_voir_dist
function exec_breves_voir_dist()
{
	afficher_breves_voir(intval(_request('id_breve')), _request('cherche_mot'), _request('select_groupe'));
}


?>
