<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
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
	exec_rubriques_edit_args(intval(_request('id_rubrique')), intval(_request('id_parent')), _request('new'));
}

// http://doc.spip.org/@exec_rubriques_edit_args
function exec_rubriques_edit_args($id_rubrique, $id_parent, $new)
{
	global $connect_toutes_rubriques, $connect_statut, $spip_lang_right;

	$titre = false;

	if ($new == "oui") {
		$id_rubrique = 0;
		$titre = filtrer_entites(_T('titre_nouvelle_rubrique'));
		$onfocus = " onfocus=\"if(!antifocus){this.value='';antifocus=true;}\"";
		$descriptif = "";
		$texte = "";

		if (!autoriser('creerrubriquedans','rubrique',$id_parent)) {
			$id_parent = intval(reset($GLOBALS['connect_id_rubrique']));
		}
	} else {
		$row = sql_fetsel("*", "spip_rubriques", "id_rubrique=$id_rubrique");
		if ($row) {
	
			$id_parent = $row['id_parent'];
			$titre = $row['titre'];
			$descriptif = $row['descriptif'];
			$texte = $row['texte'];
			$id_secteur = $row['id_secteur'];
			$extra = $row["extra"];
			$onfocus = '';
		}
	}
	$commencer_page = charger_fonction('commencer_page', 'inc');

	if ($titre === false
        OR $connect_statut !='0minirezo'
	OR ($new=='oui' AND !autoriser('creerrubriquedans','rubrique',$id_parent))
	OR ($new!='oui' AND !autoriser('modifier','rubrique',$id_rubrique)))  {
		include_spip('inc/minipres');
		echo minipres();
	} else {

	pipeline('exec_init',array('args'=>array('exec'=>'rubriques_edit','id_rubrique'=>$id_rubrique),'data'=>''));
	echo $commencer_page(_T('info_modifier_titre', array('titre' => $titre)), "naviguer", "rubriques", $id_rubrique);

	if ($id_parent == 0) $ze_logo = "secteur-24.gif";
	else $ze_logo = "rubrique-24.gif";

	if ($id_parent == 0) $logo_parent = "racine-site-24.gif";
	else {
		$id_secteur = sql_fetsel("id_secteur", "spip_rubriques", "id_rubrique=$id_parent");
		$id_secteur = $id_secteur['id_secteur'];
		if ($id_parent == $id_secteur)
			$logo_parent = "secteur-24.gif";
		else	$logo_parent = "rubrique-24.gif";
	}

	echo debut_grand_cadre(true);

	echo afficher_hierarchie($id_parent);

	echo fin_grand_cadre(true);

	echo debut_gauche('', true);

	// Pave "documents associes a la rubrique"

	if (!$new){
		# affichage sur le cote des pieces jointes, en reperant les inserees
		# note : traiter_modeles($texte, true) repere les doublons
		# aussi efficacement que propre(), mais beaucoup plus rapidement
		traiter_modeles(join('',$row), true);
		echo afficher_documents_colonne($id_rubrique, 'rubrique');
	} 

	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'rubriques_edit','id_rubrique'=>$id_rubrique),'data'=>''));
	echo creer_colonne_droite('', true);
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'rubriques_edit','id_rubrique'=>$id_rubrique),'data'=>''));	  
	echo debut_droite('', true);
	
	echo debut_cadre_formulaire("", true);

	$contexte = array(
	'icone_retour'=>icone_inline(_T('icone_retour'), generer_url_ecrire("naviguer","id_rubrique=$id_rubrique"), $ze_logo, "rien.gif",$GLOBALS['spip_lang_right']),
	'redirect'=>generer_url_ecrire("naviguer"),
	'titre'=>$titre,
	'new'=>$new == "oui"?$new:$id_rubrique,
	'id_rubrique'=>$id_parent, // pour permettre la specialisation par la rubrique appelante
	'config_fonc'=>'rubriques_edit_config'
	);
	$page = evaluer_fond("prive/editer/rubrique", $contexte, $connect);
	echo $page['texte'];
	echo fin_cadre_formulaire(true);
	/*

	if ($id_rubrique) echo icone_inline(_T('icone_retour'), generer_url_ecrire("naviguer","id_rubrique=$id_rubrique"), $ze_logo, "rien.gif",$spip_lang_right);
	else echo icone_inline(_T('icone_retour'), generer_url_ecrire("naviguer","id_rubrique=$id_parent"), $ze_logo, "rien.gif",$spip_lang_right);

	echo _T('info_modifier_rubrique');
	echo gros_titre($titre,'', false);
	echo "<br class='nettoyeur' />";

	$titre = entites_html($titre);
	$chercher_rubrique = charger_fonction('chercher_rubrique', 'inc');
	
	$form = "<ol class='formfx'>";

	$form .= "<li class='gauche obligatoire'><label for='titre'>" . _T('info_titre') ."</label>"
		.  "<input type='text' class='formo' name='titre' id='titre' value=\"$titre\" size='40' $onfocus /></li>";
	
	$form .= "<li>"
		. debut_cadre_couleur("$logo_parent", true, '', _T('entree_interieur_rubrique').aide ("rubrub"))
		. $chercher_rubrique($id_parent, 'rubrique', !$connect_toutes_rubriques, $id_rubrique);
	// si c'est une rubrique-secteur contenant des breves, demander la
	// confirmation du deplacement
	$contient_breves = sql_countsel('spip_breves', "id_rubrique=$id_rubrique",'',2);

	if ($contient_breves > 0) {
		$scb = ($contient_breves>1? 's':'');
		$scb = _T('avis_deplacement_rubrique',
			array('contient_breves' => $contient_breves,
			      'scb' => $scb));
		$form .= "\n<div class='confirmer_deplacement verdana2'><input type='checkbox' name='confirme_deplace' value='oui' id='confirme-deplace' /><label for='confirme-deplace'>" . $scb . "</label></div>\n";
	} else
		$form .= "<input type='hidden' name='confirme_deplace' value='oui' />\n";

	$form .= fin_cadre_couleur(true)
	. "</li>\n";

	if (($GLOBALS['meta']['rubriques_descriptif'] == "oui") OR strlen($descriptif)) {
		$form .= "<li class='haut'><label for='descriptif'>"
			. _T('texte_descriptif_rapide')
			."</label>\n"
			. "<div class='commentaire'>" 
			. _T('entree_contenu_rubrique')
			. "</div>"
			. "<textarea name='descriptif' id='descriptif' class='forml' rows='4' cols='40'>"
			. entites_html($descriptif)
			. "</textarea></li>\n";
	}

	if (($GLOBALS['meta']['rubriques_texte'] == "oui") OR strlen($texte)) {
		$form .= "<li class='haut'><label for='texte'>"
		. _T('info_texte_explicatif')
		. aide ("raccourcis")
		. "</label>"
		. "\n<textarea name='texte' id='texte' rows='15' class='formo barre_inserer' cols='40'>"
		. entites_html($texte)
		. "</textarea></li>\n";
	}

	// Ajouter le controles md5
	if (isset($row)) {
		include_spip('inc/editer');
		$form .= controles_md5($row);
	}


	if ($GLOBALS['champs_extra']) {
		include_spip('inc/extra');
		$form .= extra_saisie($extra, 'rubriques', $id_secteur);
	}

	$form .= "\n<div style='text-align: right'><input type='submit' value='"
	. _T('bouton_enregistrer')
	. "' class='fondo' /></div>";
	$form .= "</ol>";

	echo redirige_action_auteur("editer_rubrique", $id_rubrique ? $id_rubrique : 'oui', 'naviguer', '', $form, " method='post'");
	*/

	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'rubriques_edit','id_rubrique'=>$id_rubrique),'data'=>''));	  

	echo fin_gauche(), fin_page();
	}
}
?>