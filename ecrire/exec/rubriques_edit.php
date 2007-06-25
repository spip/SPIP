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
include_spip('inc/documents');

// http://doc.spip.org/@exec_rubriques_edit_dist
function exec_rubriques_edit_dist()
{
	global $connect_toutes_rubriques, $champs_extra, $connect_statut, $id_parent, $id_rubrique, $new,$spip_lang_right;

	if ($new == "oui") {
		$id_rubrique = 0;
		$titre = filtrer_entites(_T('titre_nouvelle_rubrique'));
		$onfocus = " onfocus=\"if(!antifocus){this.value='';antifocus=true;}\"";
		$descriptif = "";
		$texte = "";
		$id_parent = intval($id_parent);

		if (!autoriser('creerrubriquedans','rubrique',$id_parent)) {
			$id_parent = reset($GLOBALS['connect_id_rubrique']);
		}
	} else {
		$id_rubrique = intval($id_rubrique);

		$row = spip_fetch_array(spip_query("SELECT * FROM spip_rubriques WHERE id_rubrique='$id_rubrique'"));
	
		if (!$row) exit;
	
		$id_parent = $row['id_parent'];
		$titre = $row['titre'];
		$descriptif = $row['descriptif'];
		$texte = $row['texte'];
		$id_secteur = $row['id_secteur'];
		$extra = $row["extra"];
		$onfocus = '';
	}
	$commencer_page = charger_fonction('commencer_page', 'inc');

	if ($connect_statut !='0minirezo'
	OR ($new=='oui' AND !autoriser('creerrubriquedans','rubrique',$id_parent))
	OR ($new!='oui' AND !autoriser('modifier','rubrique',$id_rubrique)))  {
		echo $commencer_page(_T('info_modifier_titre', array('titre' => $titre)), "naviguer", "rubriques", $id_rubrique);
		echo "<strong>"._T('avis_acces_interdit')."</strong>";
		echo fin_page();
		exit;
	}

	pipeline('exec_init',array('args'=>array('exec'=>'rubriques_edit','id_rubrique'=>$id_rubrique),'data'=>''));
	echo $commencer_page(_T('info_modifier_titre', array('titre' => $titre)), "naviguer", "rubriques", $id_rubrique);

	if ($id_parent == 0) $ze_logo = "secteur-24.gif";
	else $ze_logo = "rubrique-24.gif";

	if ($id_parent == 0) $logo_parent = "racine-site-24.gif";
	else {
		$id_secteur = spip_fetch_array(spip_query("SELECT id_secteur FROM spip_rubriques WHERE id_rubrique='$id_parent'"));
		$id_secteur = $id_secteur['id_secteur'];
		if ($id_parent == $id_secteur)
			$logo_parent = "secteur-24.gif";
		else	$logo_parent = "rubrique-24.gif";
	}

	debut_grand_cadre();

	echo afficher_hierarchie($id_parent);

	fin_grand_cadre();

	debut_gauche();

	// Pave "documents associes a la rubrique"

	if (!$new){
		# affichage sur le cote des pieces jointes, en reperant les inserees
		# note : traiter_modeles($texte, true) repere les doublons
		# aussi efficacement que propre(), mais beaucoup plus rapidement
		traiter_modeles(join('',$row), true);
		echo afficher_documents_colonne($id_rubrique, 'rubrique');
	} 

	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'rubriques_edit','id_rubrique'=>$id_rubrique),'data'=>''));
	creer_colonne_droite();
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'rubriques_edit','id_rubrique'=>$id_rubrique),'data'=>''));	  
	debut_droite();

	debut_cadre_formulaire();

	if ($id_rubrique) echo icone_inline(_T('icone_retour'), generer_url_ecrire("naviguer","id_rubrique=$id_rubrique"), $ze_logo, "rien.gif",$spip_lang_right);
	else echo icone_inline(_T('icone_retour'), generer_url_ecrire("naviguer","id_rubrique=$id_parent"), $ze_logo, "rien.gif",$spip_lang_right);

	echo _T('info_modifier_rubrique');
	gros_titre($titre);
	echo "<br class='nettoyeur' />";

	$titre = entites_html($titre);
	$chercher_rubrique = charger_fonction('chercher_rubrique', 'inc');

	$form = _T('entree_titre_obligatoire')
	.  "<input type='text' class='formo' name='titre' value=\"$titre\" size='40' $onfocus />"
	. debut_cadre_couleur("$logo_parent", true, '', _T('entree_interieur_rubrique').aide ("rubrub"))
	. $chercher_rubrique($id_parent, 'rubrique', !$connect_toutes_rubriques, $id_rubrique);

// si c'est une rubrique-secteur contenant des breves, demander la
// confirmation du deplacement
	 $contient_breves = spip_fetch_array(spip_query("SELECT COUNT(*) AS cnt FROM spip_breves WHERE id_rubrique='$id_rubrique' LIMIT 1"));

	 $contient_breves = $contient_breves['cnt'];

	if ($contient_breves > 0) {
		$scb = ($contient_breves>1? 's':'');
		$scb = _T('avis_deplacement_rubrique',
			array('contient_breves' => $contient_breves,
			      'scb' => $scb));
		$form .= "<div><span class='spip_small'><input type='checkbox' name='confirme_deplace' value='oui' id='confirme-deplace' /><label for='confirme-deplace'>&nbsp;" . $scb . "</span></label></div>\n";
	} else
		$form .= "<input type='hidden' name='confirme_deplace' value='oui' />\n";

	$form .= fin_cadre_couleur(true)
	. "<br />";

	if (($GLOBALS['meta']['rubriques_descriptif'] == "oui") OR strlen($descriptif)) {
		$form .= "<b>"._T('texte_descriptif_rapide')."</b><br />"
			. _T('entree_contenu_rubrique')."<br />"
			. "<textarea name='descriptif' class='forml' rows='4' cols='40'>"
			. entites_html($descriptif)
			. "</textarea>\n";
	}

	if (($GLOBALS['meta']['rubriques_texte'] == "oui") OR strlen($texte)) {
		$form .= "<b>"._T('info_texte_explicatif')."</b>"
		. aide ("raccourcis")
		. "<br /><textarea name='texte' rows='15' class='formo' cols='40'>"
		. entites_html($texte)
		. "</textarea>\n";
	}

	if ($champs_extra) {
		include_spip('inc/extra');
		$form .= extra_saisie($extra, 'rubriques', $id_secteur);
	}

	$form .= "\n<div style='text-align: right'><input type='submit' value='"
	. _T('bouton_enregistrer')
	. "' class='fondo' />\n</p>";

	echo redirige_action_auteur("editer_rubrique", $id_rubrique ? $id_rubrique : 'oui', 'naviguer', '', $form, " method='post'");

	echo fin_cadre_formulaire();

	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'rubriques_edit','id_rubrique'=>$id_rubrique),'data'=>''));	  

	echo fin_gauche(), fin_page();
}
?>
