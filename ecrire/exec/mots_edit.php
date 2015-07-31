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
include_spip('base/abstract_sql');

// http://doc.spip.org/@exec_mots_edit_dist
function exec_mots_edit_dist()
{
global
  $ajouter_id_article, // attention, ce n'est pas forcement un id d'article
  $champs_extra,
  $connect_statut,
  $descriptif,
  $id_groupe,
  $id_mot,
  $table_id,
  $new,
  $options,
  $redirect,
  $spip_display,
  $table,
  $texte,
  $titre,
  $titre_mot,
  $les_notes;

 $id_groupe = intval($id_groupe);
 $id_mot = intval($id_mot);

//
// Recupere les donnees
//
	$out = "";

	$row = spip_fetch_array(spip_query("SELECT * FROM spip_mots WHERE id_mot=$id_mot"));
	 if ($row) {
		$id_mot = $row['id_mot'];
		$titre_mot = $row['titre'];
		$descriptif = $row['descriptif'];
		$texte = $row['texte'];
		$extra = $row['extra'];
		$id_groupe = $row['id_groupe'];
	 } else $id_mot = 0;
 
	 pipeline('exec_init',array('args'=>array('exec'=>'mots_edit','id_mot'=>$id_mot),'data'=>''));

	 $commencer_page = charger_fonction('commencer_page', 'inc');
	 $out .= $commencer_page("&laquo; $titre_mot &raquo;", "naviguer", "mots");
	 
	 $out .= debut_gauche('',true);


//////////////////////////////////////////////////////
// Boite "voir en ligne"
//

	 if ($id_mot) {
		$res = "\n<div style='font-weight: bold; text-align: center' class='verdana1 spip_xx-small'>" 
		.  _T('titre_gauche_mots_edit')
		.  "<br /><span class='spip_xx-large'>"
		.  $id_mot
		.  '</span></div>';

		$out .= debut_boite_info(true);
		$out .= $res;
		$out .= voir_en_ligne ('mot', $id_mot, false, 'racine-24.gif', false);
		$out .= fin_boite_info(true);
		$onfocus ='';

	 } elseif (!$new OR !acces_mots()) {
		$out .= _T('info_mot_sans_groupe');
		exit;
	 } else {
		if (!$titre_mot = $titre) {
			$titre_mot = filtrer_entites(_T('texte_nouveau_mot'));
			$onfocus = " onfocus=\"if(!antifocus){this.value='';antifocus=true;}\"";
		}
	 }

//////////////////////////////////////////////////////
// Logos du mot-clef
//

	if ($id_mot > 0 AND acces_mots() AND ($spip_display != 4)) {
		$iconifier = charger_fonction('iconifier', 'inc');
		$out .= $iconifier('id_mot', $id_mot, 'mots_edit');
	}

//
// Afficher les boutons de creation 
//

	$res ='';

	if (acces_mots() AND $id_groupe) {
		$res = icone_horizontale(_T('icone_modif_groupe_mots'), generer_url_ecrire("mots_type","id_groupe=$id_groupe"), "groupe-mot-24.gif", "edit.gif", false)
		  . icone_horizontale(_T('icone_creation_mots_cles'), generer_url_ecrire("mots_edit", "new=oui&id_groupe=$id_groupe&redirect=" . generer_url_retour('mots_tous')),  "mot-cle-24.gif",  "creer.gif", false);
	}

	$out .= bloc_des_raccourcis($res . icone_horizontale(_T('icone_voir_tous_mots_cles'), generer_url_ecrire("mots_tous",""), "mot-cle-24.gif", "rien.gif", false));

	$out .= pipeline('affiche_gauche',array('args'=>array('exec'=>'mots_edit','id_mot'=>$id_mot),'data'=>''));

	$out .= creer_colonne_droite('',true);

	$out .= pipeline('affiche_droite',array('args'=>array('exec'=>'mots_edit','id_mot'=>$id_mot),'data'=>''));

	$out .= debut_droite('',true);

	$out .= debut_cadre_relief("mot-cle-24.gif",true);


	$out .= "\n<table cellpadding='0' cellspacing='0' border='0' width='100%'>";
	$out .= "<tr>";
	$out .= "<td style='width: 100%' valign='top'>";
	$out .= gros_titre($titre_mot,'',false);


	if ($descriptif) {
		$out .= "<div style='border: 1px dashed #aaaaaa; ' class='verdana1 spip_small'>";
		$out .= "<b>" . _T('info_descriptif') . "</b> ";
		$out .= propre($descriptif);
		$out .= "&nbsp; ";
		$out .= "</div>";
	}

	$out .= "</td>";
	$out .= "</tr></table>\n";


	if (strlen($texte)>0){
		$out .= "<p class='verdana1 spip_small'>";
		$out .= propre($texte);
		$out .= "</p>";
	}

	if ($les_notes) {
		$out .= debut_cadre_relief('',true);
		$out .= "<div $dir_lang class='arial11'>";
		$out .= justifier("<b>"._T('info_notes')."&nbsp;:</b> ".$les_notes);
		$out .= "</div>";
		$out .= fin_cadre_relief(true);
	}

	if ($id_mot) {

		if ($connect_statut == "0minirezo")
			$aff_articles = "'prepa','prop','publie','refuse'";
		else
			$aff_articles = "'prop','publie'";

		$out .= afficher_rubriques('<b>' . _T('info_rubriques_liees_mot') . '</b>', array("FROM" => 'spip_rubriques AS rubrique, spip_mots_rubriques AS lien', 'WHERE' => "lien.id_mot='$id_mot' AND lien.id_rubrique=rubrique.id_rubrique", 'ORDER BY' => "rubrique.titre"));

		$out .= afficher_articles(_T('info_articles_lies_mot'),	array('FROM' => "spip_articles AS articles, spip_mots_articles AS lien", 'WHERE' => "lien.id_mot='$id_mot' AND lien.id_article=articles.id_article AND articles.statut IN ($aff_articles)", 'ORDER BY' => "articles.date DESC"));

		$out .= afficher_breves('<b>' . _T('info_breves_liees_mot') . '</b>', array("FROM" => 'spip_breves AS breves, spip_mots_breves AS lien', 'WHERE' => "lien.id_mot='$id_mot' AND lien.id_breve=breves.id_breve", 'ORDER BY' => "breves.date_heure DESC"));

		include_spip('inc/sites_voir');
		$out .= afficher_sites('<b>' . _T('info_sites_lies_mot') . '</b>', array("FROM" => 'spip_syndic AS syndic, spip_mots_syndic AS lien', 'WHERE' => "lien.id_mot='$id_mot' AND lien.id_syndic=syndic.id_syndic", 'ORDER BY' => "syndic.nom_site DESC"));
	}

	$out .= fin_cadre_relief(true);

	$out .= pipeline('affiche_milieu',array('args'=>array('exec'=>'mots_edit','id_mot'=>$id_mot),'data'=>''));

	if (acces_mots()){

		$out .= debut_cadre_formulaire('',true);

		$res = "<div class='serif'>";

		if ($new=='oui')
			$res .= "<input type='hidden' name='new' value='oui' />\n";
		$res .= "<input type='hidden' name='table' value='$table' />\n";
		$res .= "<input type='hidden' name='table_id' value='$table_id' />\n";
		$res .= "<input type='hidden' name='ajouter_id_article' value=\"$ajouter_id_article\" />\n";
		
		$titre_mot = entites_html($titre_mot);
		$descriptif = entites_html($descriptif);
		$texte = entites_html($texte);
		
		$res .= "<b>"._T('info_titre_mot_cle')."</b> "._T('info_obligatoire_02');
		$res .= aide ("mots");

		$res .= "<br /><input type='text' name='titre' class='formo' value=\"$titre_mot\" size='40' $onfocus />";

		$res .= determine_groupe_mots($table, $id_groupe);

		if ($options == 'avancees' OR $descriptif) {
			$res .= "<b>"._T('texte_descriptif_rapide')."</b><br />";
			$res .= "<textarea name='descriptif' class='forml' rows='4' cols='40'>";
			$res .= $descriptif;
			$res .= "</textarea><br />\n";
		}
		else
			$res .= "<input type='hidden' name='descriptif' value=\"$descriptif\" />";

		if ($options == 'avancees' OR $texte) {
			$res .= "<b>"._T('info_texte_explicatif')."</b><br />";
			$res .= "<textarea name='texte' rows='8' class='forml' cols='40'>";
			$res .= $texte;
			$res .= "</textarea><br />\n";
		}
		else
			$res .= "<input type='hidden' name='texte' value=\"$texte\" />";

		if ($champs_extra) {
			include_spip('inc/extra');
			$res .= extra_saisie($extra, 'mots', $id_groupe);
		}

		$res .= "<div align='right'><input type='submit' value='"._T('bouton_enregistrer')."' class='fondo' /></div>";
	
		$res .= "</div>";

		if (!$redirect)
			$redirect = generer_url_ecrire('mots_edit','id_mot='.$id_mot, '&',true);
		else
			$redirect = rawurldecode($redirect);
		$out .= generer_action_auteur("instituer_mot", $id_mot, _DIR_RESTREINT_ABS . $redirect, $res);

		$out .= fin_cadre_formulaire(true);
	}

	echo $out, fin_gauche(), fin_page();
}


// http://doc.spip.org/@determine_groupe_mots
function determine_groupe_mots($table, $id_groupe) {

	$q = spip_query("SELECT id_groupe, titre FROM spip_groupes_mots ". ($table ? "WHERE $table='oui'" : '') . " ORDER BY titre");

	if (spip_num_rows($q)>1) {

		$res = " &nbsp; <select name='id_groupe' class='fondl'>\n";
		while ($row = spip_fetch_array($q)){
			$groupe = $row['id_groupe'];
			$titre_groupe = texte_backend(supprimer_tags(typo($row['titre'])));
			$res .=  "<option".mySel($groupe, $id_groupe).">$titre_groupe</option>\n";
		}			
		$res .=  "</select>";
	} else {
		$row = spip_fetch_array($q);
		if (!$row) {
		// il faut creer un groupe de mots
		// (cas d'un mot cree depuis le script articles)

			$titre = _T('info_mot_sans_groupe');
		  	$row['id_groupe'] = spip_abstract_insert("spip_groupes_mots", "(titre, unseul, obligatoire, articles, breves, rubriques, syndic, minirezo, comite, forum)", "(" . _q($titre) . ", 'non',  'non', '" . (($table=='articles') ? 'oui' : 'non') ."', '" . (($table=='breves') ? 'oui' : 'non') ."','" . (($table=='rubriques') ? 'oui' : 'non') ."','" . (($table=='syndic') ? 'oui' : 'non') ."', 'oui', 'non', 'non'" . ")");
		} else $titre = $row['titre'];
		$res = $titre
		. "<br /><input type='hidden' name='id_groupe' value='".$row['id_groupe']."' />";
	}

	return _T('info_dans_groupe')
	. aide("motsgroupes")
	. debut_cadre_relief("groupe-mot-24.gif", true)
	. $res
	. fin_cadre_relief(true);
}
?>
