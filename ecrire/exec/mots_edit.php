<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');
include_spip('base/abstract_sql');

function exec_mots_edit_dist()
{
global
  $ajouter_id_article, // attention, ce n'est pas forcement un id d'article
  $champs_extra,
  $connect_statut,
  $connect_toutes_rubriques,
  $descriptif,
  $id_groupe,
  $id_mot,
  $table_id,
  $new,
  $onfocus,
  $options,
  $redirect,
  $redirect_ok,
  $supp_mot,
  $spip_display,
  $table,
  $texte,
  $titre,
  $titre_groupe,
  $titre_mot,
  $les_notes,
  $type;

 $id_groupe = intval($id_groupe);
 $supp_mot = intval($supp_mot);
 $id_mot = intval($id_mot);
//
// modifications mot
//
 if (acces_mots()) {
	if ($supp_mot) {
		spip_query("DELETE FROM spip_mots WHERE id_mot=$supp_mot");
		spip_query("DELETE FROM spip_mots_articles WHERE id_mot=$supp_mot");
	}

	if (strval($titre_mot)!='') {
		if ($new == 'oui' && $id_groupe) {
			$id_mot = spip_abstract_insert("spip_mots", '(id_groupe)', "($id_groupe)");

			if($ajouter_id_article = intval($ajouter_id_article))
			// heureusement que c'est pour les admin complet,
			// sinon bonjour le XSS
				ajouter_nouveau_mot($id_groupe, $table, $table_id, $id_mot, $ajouter_id_article);

		}

		$result = spip_query("SELECT titre FROM spip_groupes_mots WHERE id_groupe=$id_groupe");
		if ($row = spip_fetch_array($result))
			$type = (corriger_caracteres($row['titre']));
		else $type = (corriger_caracteres($type));
		// recoller les champs du extra
		if ($champs_extra) {
			include_spip('inc/extra');
			$add_extra = extra_recup_saisie("mots");
		} else
			$add_extra = '';

		spip_query("UPDATE spip_mots SET titre=" . spip_abstract_quote($titre_mot) . ", texte=" . spip_abstract_quote($texte) . ", descriptif=" . spip_abstract_quote($descriptif) . ", type=" . spip_abstract_quote($type) . ", id_groupe=$id_groupe" . (!$add_extra ? '' : (", extra = " . spip_abstract_quote($add_extra))) . " WHERE id_mot=$id_mot");

		if ($GLOBALS['meta']['activer_moteur'] == 'oui') {
			include_spip("inc/indexation");
			marquer_indexer('mot', $id_mot);
		}
	}
	else if ($new == 'oui') {
		if (!$titre_mot = $titre) {
			$titre_mot = filtrer_entites(_T('texte_nouveau_mot'));
			$onfocus = " onfocus=\"if(!antifocus){this.value='';antifocus=true;}\"";
		}
	}
 }

//
// redirection ou affichage
//
if ($redirect_ok == 'oui' && $redirect) {
	redirige_par_entete(rawurldecode($redirect));
}

//
// Recupere les donnees
//
 if ($id_mot) {
	$row = spip_fetch_array(spip_query("SELECT * FROM spip_mots WHERE id_mot=$id_mot"));
	 if ($row) {
		$id_mot = $row['id_mot'];
		$titre_mot = $row['titre'];
		$descriptif = $row['descriptif'];
		$texte = $row['texte'];
		$type = $row['type'];
		$extra = $row['extra'];
		$id_groupe = $row['id_groupe'];
	 } else $id_mot = 0;
 }
pipeline('exec_init',array('args'=>array('exec'=>'mots_edit','id_mot'=>$id_mot),'data'=>''));

debut_page("&laquo; $titre_mot &raquo;", "naviguer", "mots");
debut_gauche();


//////////////////////////////////////////////////////
// Boite "voir en ligne"
//

if ($id_mot) {
	debut_boite_info();
	echo "<CENTER>";
	echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=1><B>"._T('titre_gauche_mots_edit')."</B></FONT>";
	echo "<BR><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=6><B>$id_mot</B></FONT>";
	echo "</CENTER>";

	voir_en_ligne ('mot', $id_mot);

	fin_boite_info();
 } else if (!$new) {echo _T('info_mot_sans_groupe'); exit;}

//////////////////////////////////////////////////////
// Logos du mot-clef
//

if ($id_mot > 0 AND acces_mots() AND ($spip_display != 4)) {
	include_spip('inc/chercher_logo');
	echo afficher_boite_logo('id_mot', $id_mot, _T('logo_mot_cle').aide("breveslogo"), _T('logo_survol'), 'mots_edit');
 }

//
// Afficher les boutons de creation d'article et de breve
//
debut_raccourcis();

if (acces_mots() AND $id_groupe) {
	icone_horizontale(_T('icone_modif_groupe_mots'), generer_url_ecrire("mots_type","id_groupe=$id_groupe"), "groupe-mot-24.gif", "edit.gif");
	icone_horizontale(_T('icone_creation_mots_cles'), generer_url_ecrire("mots_edit", "new=oui&id_groupe=$id_groupe&redirect=" . generer_url_retour('mots_tous')),  "mot-cle-24.gif",  "creer.gif");
 }
 icone_horizontale(_T('icone_voir_tous_mots_cles'), generer_url_ecrire("mots_tous",""), "mot-cle-24.gif", "rien.gif");

fin_raccourcis();


echo pipeline('affiche_gauche',array('args'=>array('exec'=>'mots_edit','id_mot'=>$id_mot),'data'=>''));
creer_colonne_droite();
echo pipeline('affiche_droite',array('args'=>array('exec'=>'mots_edit','id_mot'=>$id_mot),'data'=>''));
debut_droite();

debut_cadre_relief("mot-cle-24.gif");


echo "\n<table cellpadding='0' cellspacing='0' border='0' width='100%'>";
echo "<tr width='100%'>";
echo "<td width='100%' valign='top'>";
gros_titre($titre_mot);


if ($descriptif) {
	echo "<p><div style='border: 1px dashed #aaaaaa;'>";
	echo "<font size='2' face='Verdana,Arial,Sans,sans-serif'>";
	echo "<b>",_T('info_descriptif'),"</b> ";
	echo propre($descriptif);
	echo "&nbsp; ";
	echo "</font>";
	echo "</div>";
}
echo "</td>";
echo "</tr></table>\n";


if (strlen($texte)>0){
	echo "<FONT FACE='Verdana,Arial,Sans,sans-serif'>";
	echo "<P>".propre($texte);
	echo "</FONT>";
}

 if ($les_notes) {
			echo debut_cadre_relief();
			echo "<div $dir_lang class='arial11'>";
			echo justifier("<b>"._T('info_notes')."&nbsp;:</b> ".$les_notes);
			echo "</div>";
			echo fin_cadre_relief();
 }


if ($id_mot) {
	echo "<P>";

	if ($connect_statut == "0minirezo")
		$aff_articles = "'prepa','prop','publie','refuse'";
	else
		$aff_articles = "'prop','publie'";

	afficher_rubriques(_T('info_rubriques_liees_mot'), array("FROM" => 'spip_rubriques AS rubrique, spip_mots_rubriques AS lien', 'WHERE' => "lien.id_mot='$id_mot' AND lien.id_rubrique=rubrique.id_rubrique", 'ORDER BY' => "rubrique.titre"));

	afficher_articles(_T('info_articles_lies_mot'),	array('FROM' => "spip_articles AS articles, spip_mots_articles AS lien", 'WHERE' => "lien.id_mot='$id_mot' AND lien.id_article=articles.id_article AND articles.statut IN ($aff_articles)", 'ORDER BY' => "articles.date DESC"), true);

	afficher_breves(_T('info_breves_liees_mot'), array("FROM" => 'spip_breves AS breves, spip_mots_breves AS lien', 'WHERE' => "lien.id_mot='$id_mot' AND lien.id_breve=breves.id_breve", 'ORDER BY' => "breves.date_heure DESC"));

	include_spip('inc/sites_voir');
	afficher_sites(_T('info_sites_lies_mot'), array("FROM" => 'spip_syndic AS syndic, spip_mots_syndic AS lien', 'WHERE' => "lien.id_mot='$id_mot' AND lien.id_syndic=syndic.id_syndic", 'ORDER BY' => "syndic.nom_site DESC"));
}

fin_cadre_relief();



if (acces_mots()){
	echo "<P>";
	debut_cadre_formulaire();

	echo "<div class='serif'>";
	echo generer_url_post_ecrire("mots_edit", ($id_mot ? "id_mot=$id_mot" : ""));

	if ($new=='oui')
		echo "<input type='hidden' name='new' VALUE='oui' />\n";
	echo "<input type='hidden' name='redirect' VALUE=\"$redirect\" />\n";
	echo "<input type='hidden' name='redirect_ok' VALUE='oui' />\n";
	echo "<input type='hidden' name='table' VALUE='$table' />\n";
	echo "<input type='hidden' name='table_id' VALUE='$table_id' />\n";
	echo "<input type='hidden' name='ajouter_id_article' VALUE=\"$ajouter_id_article\" />\n";

	$titre_mot = entites_html($titre_mot);
	$descriptif = entites_html($descriptif);
	$texte = entites_html($texte);

	echo "<B>"._T('info_titre_mot_cle')."</B> "._T('info_obligatoire_02');
	echo aide ("mots");

	echo "<BR><input type='text' NAME='titre_mot' CLASS='formo' VALUE=\"$titre_mot\" SIZE='40' $onfocus />";

	determine_groupe_mots($table, $id_groupe);

	if ($options == 'avancees' OR $descriptif) {
		echo "<B>"._T('texte_descriptif_rapide')."</B><BR>";
		echo "<TEXTAREA NAME='descriptif' CLASS='forml' ROWS='4' COLS='40' wrap=soft>";
		echo $descriptif;
		echo "</TEXTAREA><P>\n";
	}
	else
		echo "<input type='hidden' NAME='descriptif' VALUE=\"$descriptif\">";

	if ($options == 'avancees' OR $texte) {
		echo "<B>"._T('info_texte_explicatif')."</B><BR>";
		echo "<TEXTAREA NAME='texte' ROWS='8' CLASS='forml' COLS='40' wrap=soft>";
		echo $texte;
		echo "</TEXTAREA><P>\n";
	}
	else
		echo "<input type='hidden' NAME='texte' VALUE=\"$texte\">";

	if ($champs_extra) {
		include_spip('inc/extra');
		extra_saisie($extra, 'mots', $id_groupe);
	}

	echo "<DIV align='right'><input type='submit' NAME='Valider' VALUE='"._T('bouton_enregistrer')."' CLASS='fondo'></div>";
	
	echo "</div>";
	echo "</FORM>";

	fin_cadre_formulaire();
 }

fin_page();
}


function determine_groupe_mots($table, $id_groupe) {

	$result = spip_query("SELECT id_groupe, titre FROM spip_groupes_mots ". ($table ? "WHERE $table='oui'" : '') . " ORDER BY titre");

	echo  _T('info_dans_groupe'), aide("motsgroupes");
	debut_cadre_relief("groupe-mot-24.gif");
	if (spip_num_rows($result)>1) {

		echo  " &nbsp; <SELECT NAME='id_groupe' class='fondl'>\n";
		while ($row_groupes = spip_fetch_array($result)){
			$groupe = $row_groupes['id_groupe'];
			$titre_groupe = texte_backend(supprimer_tags(typo($row_groupes['titre'])));
			echo  "<OPTION".mySel($groupe, $id_groupe).">$titre_groupe</OPTION>\n";
		}			
		echo  "</SELECT>";
	} else {
		$row_groupes = spip_fetch_array($result);
		if (!$row_groupes) {
			// il faut creer un groupe de mots (cas d'un mot cree depuis le script articles)

			$titre = _T('info_mot_sans_groupe');
		  	$row_groupes['id_groupe'] = spip_abstract_insert("spip_groupes_mots", "(titre, unseul, obligatoire, articles, breves, rubriques, syndic, minirezo, comite, forum)", "(" . spip_abstract_quote($titre) . ", 'non',  'non', '" . (($table=='articles') ? 'oui' : 'non') ."', '" . (($table=='breves') ? 'oui' : 'non') ."','" . (($table=='rubriques') ? 'oui' : 'non') ."','" . (($table=='syndic') ? 'oui' : 'non') ."', 'oui', 'non', 'non'" . ")");
		} else $titre = $row_groupes['titre'];
		echo $titre, '<br />';
		echo "<input type='hidden' name='id_groupe' value='".$row_groupes['id_groupe']."' />";
	}
	fin_cadre_relief();
}

function un_seul_mot_dans_groupe($id_groupe)
{
	$u = spip_fetch_array(spip_query("SELECT unseul FROM spip_groupes_mots WHERE id_groupe = $id_groupe"));
	return ($u['unseul'] == 'oui');
}

function ajouter_nouveau_mot($id_groupe, $table, $table_id, $id_mot, $id)
{
	if (un_seul_mot_dans_groupe($id_groupe)) {
		$mots = spip_query("SELECT id_mot FROM spip_mots WHERE id_groupe = $id_groupe");
		while ($r = spip_fetch_array($mots))
			spip_query("DELETE FROM spip_mots_$table WHERE id_mot=" . $r['id_mot'] ." AND $table_id=$id");
	}
	spip_abstract_insert("spip_mots_$table", "(id_mot, $table_id)", "($id_mot, $id)");
}

?>
