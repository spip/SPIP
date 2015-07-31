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

// http://doc.spip.org/@exec_mots_type_dist
function exec_mots_type_dist()
{
	global $connect_statut, $descriptif, $id_groupe, $new, $options, $texte, $titre;

	if ($new == "oui") {
	  $id_groupe = 0;
	  $type = filtrer_entites(_T('titre_nouveau_groupe'));
	  $onfocus = " onfocus=\"if(!antifocus){this.value='';antifocus=true;}\"";
	  $ancien_type = '';
	  $unseul = 'non';
	  $obligatoire = 'non';
	  $articles = 'oui';
	  $breves = 'oui';
	  $rubriques = 'non';
	  $syndic = 'oui';
	  $acces_minirezo = 'oui';
	  $acces_comite = 'oui';
	  $acces_forum = 'non';
	} else {
		$id_groupe= intval($id_groupe);
		$result_groupes = spip_query("SELECT * FROM spip_groupes_mots WHERE id_groupe=$id_groupe");

		while($row = spip_fetch_array($result_groupes)) {
			$id_groupe = $row['id_groupe'];
			$type = $row['titre'];
			$titre = typo($type);
			$descriptif = $row['descriptif'];
			$texte = $row['texte'];
			$unseul = $row['unseul'];
			$obligatoire = $row['obligatoire'];
			$articles = $row['articles'];
			$breves = $row['breves'];
			$rubriques = $row['rubriques'];
			$syndic = $row['syndic'];
			$acces_minirezo = $row['minirezo'];
			$acces_comite = $row['comite'];
			$acces_forum = $row['forum'];
			$onfocus ="";
		}
	}

	pipeline('exec_init',array('args'=>array('exec'=>'mots_types','id_groupe'=>$id_groupe),'data'=>''));
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page("&laquo; $titre &raquo;", "naviguer", "mots");
	
	$multi_js = "";
	if($GLOBALS['meta']['multi_rubriques']=="oui" || $GLOBALS['meta']['multi_articles']=="oui" || $GLOBALS['meta']['multi_secteurs']=="oui") {
	$active_langs = "'".str_replace(",","','",$GLOBALS['meta']['langues_multilingue'])."'";
	$multi_js = "<script type='text/javascript' src='"._DIR_JAVASCRIPT."multilang.js'></script>\n".
	"<script type='text/javascript'>\n".
	"var multilang_def_lang='".$GLOBALS["spip_lang"]."';var multilang_avail_langs=[$active_langs];\n".
	"$(function(){\n".
	"multilang_init_lang({'root':'#page','form_menu':'div.cadre-formulaire:eq(0)','fields':'input[@name=\'change_type\'],textarea'});\n".
	"});\n".
	"</script>\n";
	}
	echo $multi_js; 

	
	debut_gauche();

	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'mots_types','id_groupe'=>$id_groupe),'data'=>''));
	creer_colonne_droite();
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'mots_types','id_groupe'=>$id_groupe),'data'=>''));
	debut_droite();

	if ($connect_statut != "0minirezo") {
		echo "<h3>"._T('avis_non_acces_page')."</h3>";
		exit;
	}


	$type = entites_html(rawurldecode($type));

	$res = debut_cadre_relief("groupe-mot-24.gif", true)
	. "\n<table cellpadding='0' cellspacing='0' border='0' width='100%'>"
	. "<tr>"
	. "<td  align='right' valign='top'>"
	. icone(_T('icone_retour'), generer_url_ecrire("mots_tous",""), "mot-cle-24.gif", "rien.gif",'', false)
	. "</td>"
	. "<td>". http_img_pack('rien.gif', " ", "width='5'") . "</td>\n"
	. "<td style='width: 100%' valign='top'>"
	  . "<span style='font-family: Verdana,Arial,Sans,sans-serif; font-size: 12px;'><b>". _T('titre_groupe_mots') . "</b></span><br />"
	  . gros_titre($titre,'',false)
	. aide("motsgroupes")
	. "<div style='font-family: Verdana,Arial,Sans,sans-serif;'>"
	. debut_cadre_formulaire('',true)
	. "<b>"._T('info_changer_nom_groupe')."</b><br />\n"
	. "<input type='text' size='40' class='formo' name='change_type' value=\"$type\" $onfocus />\n";
		
	if ($options == 'avancees' OR $descriptif) {
			$res .= "<br /><b>"._T('texte_descriptif_rapide')
			. "</b><br />"
			. "<textarea name='descriptif' class='forml' rows='4' cols='40'>"
			. entites_html($descriptif)
			. "</textarea>\n";
	} else
			$res .= "<input type='hidden' name='descriptif' value=\"$descriptif\" />";

	if ($options == 'avancees' OR $texte) {
			$res .= "<br /><b>"._T('info_texte_explicatif')."</b><br />";
			$res .= "<textarea name='texte' rows='8' class='forml' cols='40'>";
			$res .= entites_html($texte);
			$res .= "</textarea>\n";
	} else
		  $res .= "<input type='hidden' name='texte' value=\"$texte\" />";

	$res .= "<div align='right'><input type='submit' class='fondo' value='"
	. _T('bouton_valider')
	. "' /></div>"
	. fin_cadre_formulaire(true)
	. "</div>"
	. "</td></tr></table>"
	. fin_cadre_relief(true)
	. "<br />\n<div style='font-family: Verdana,Arial,Sans,sans-serif;'>"
	. debut_cadre_formulaire('',true)
	. "<div style='padding: 5px; border: 1px dashed #aaaaaa; background-color: #dddddd;'>"
	. "<b>"._T('info_mots_cles_association')."</b>"
	. "<br />";
		
	$checked =  ($articles == "oui") ? "checked='checked'" : ''; 
	$res .= "<input type='checkbox' name='articles' value='oui' $checked id='articles' /> <label for='articles'>"._T('item_mots_cles_association_articles')."</label><br />";
	$activer_breves = $GLOBALS['meta']["activer_breves"];

	if ($activer_breves != "non"){
		$checked =  ($breves == "oui") ? "checked='checked'" : '';
			
		$res .= "<input type='checkbox' name='breves' value='oui' $checked id='breves' /> <label for='breves'>"._T('item_mots_cles_association_breves')."</label><br />";
	} else {
		$res .= "<input type='hidden' name='breves' value='non' />";
	}
	$checked = ($rubriques == "oui") ? "checked='checked'" : '';	

	$res .= "<input type='checkbox' name='rubriques' value='oui' $checked id='rubriques' /> <label for='rubriques'>"._T('item_mots_cles_association_rubriques')."</label><br />";

	$checked = ($syndic == "oui") ? "checked='checked'" : ''; 
	$res .= "<input type='checkbox' name='syndic' value='oui' $checked id='syndic' /> <label for='syndic'>"._T('item_mots_cles_association_sites')."</label>"
	.  "</div>";

	if ($GLOBALS['meta']["config_precise_groupes"] == "oui" OR $unseul == "oui" OR $obligatoire == "oui"){
		$res .= "<div style='padding: 5px; border: 1px dashed #aaaaaa; background-color: #dddddd;'>";

		$checked =  ($unseul == "oui") ? "checked='checked'" : ''; 
		$res .= "<input type='checkbox' name='unseul' value='oui' $checked id='unseul' /> <label for='unseul'>"._T('info_selection_un_seul_mot_cle')."</label>";
		$res .= "<br />";

		$checked = ($obligatoire == "oui") ? "checked='checked'" : '';
		$res .= "<input type='checkbox' name='obligatoire' value='oui' $checked id='obligatoire' /> <label for='obligatoire'>"._T('avis_conseil_selection_mot_cle')."</label>";
		$res .= "</div>";
	} else {
		  $res .= "<input type='hidden' name='unseul' value='non' />";
		  $res .= "<input type='hidden' name='obligatoire' value='non' />";
	}

	$res .= "<div style='padding: 5px; border: 1px dashed #aaaaaa; background-color: #dddddd;'>"
	.  "<b>"._T('info_qui_attribue_mot_cle')."</b>"
	.  "<br />";
		
	$checked = ($acces_minirezo == "oui") ? "checked='checked'" : ''; 
	$res .= "<input type='checkbox' name='acces_minirezo' value='oui' $checked id='administrateurs' /> <label for='administrateurs'>"
	 . _T('bouton_checkbox_qui_attribue_mot_cle_administrateurs')
	. "</label><br />";

	$checked =  ($acces_comite == "oui") ? "checked='checked'" : ''; 
	$res .= "<input type='checkbox' name='acces_comite' value='oui' $checked id='comite' /> <label for='comite'>"._T('bouton_checkbox_qui_attribue_mot_cle_redacteurs')."</label><br />";
	
	$mots_cles_forums = $GLOBALS['meta']["mots_cles_forums"];
	$forums_publics=$GLOBALS['meta']["forums_publics"];
		
	if (($mots_cles_forums == "oui" OR $acces_forum == "oui") AND $forums_publics != "non"){
		$checked = ($acces_forum == "oui") ? "checked='checked'" : ''; 
		$res .= "<input type='checkbox' name='acces_forum' value='oui' $checked id='forum' /> <label for='forum'>"._T('bouton_checkbox_qui_attribue_mot_cle_visiteurs')."</label>";
	} 
	else {
		$res .= "<input type='hidden' name='acces_forum' value='non' />";
	}
			
	$res .= "<br /></div><div align='right'><input type='submit' class='fondo' value='"
	. _T('bouton_valider')
	. "' /></div>"
	.  fin_cadre_formulaire(true)
	. "</div>";

	echo redirige_action_auteur('instituer_groupe_mots', $id_groupe, "mots_tous", "id_groupe=$id_groupe", $res),
		fin_gauche(),
		fin_page();
}

?>
