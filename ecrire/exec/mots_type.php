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

function exec_mots_type_dist()
{
  global $connect_statut, $descriptif, $id_groupe, $new, $options, $texte, $titre;

  $id_groupe= intval($id_groupe);

if ($connect_statut == '0minirezo' AND $new == "oui") {
	$id_groupe = '';
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
		$new = '';
	}
}

pipeline('exec_init',array('args'=>array('exec'=>'mots_types','id_groupe'=>$id_groupe),'data'=>''));
debut_page("&laquo; $titre &raquo;", "documents", "mots");

debut_gauche();



debut_droite();

debut_cadre_relief("groupe-mot-24.gif");



echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr width='100%'>";

	echo "<td  align='right' valign='top'>";
	icone(_T('icone_retour'), generer_url_ecrire("mots_tous",""), "mot-cle-24.gif", "rien.gif");
	echo "</td>";
	echo "<td>". http_img_pack('rien.gif', " ", "width='5'") . "</td>\n";

echo "<td width='100%' valign='top'>";
echo "<font face='Verdana,Arial,Sans,sans-serif' size=1><b>"._T('titre_groupe_mots')."</b><br></font>";
gros_titre($titre);
echo aide("motsgroupes");

if ($connect_statut =="0minirezo"){
	$type=entites_html(rawurldecode($type));
	echo "<p><font face='Verdana,Arial,Sans,sans-serif'>";
	echo generer_url_post_ecrire("mots_tous", "id_groupe=$id_groupe");
	echo "<input type='hidden' name='modifier_groupe' value='oui' />\n";
	echo "<input type='hidden' name='new' value='$new' />\n";
	debut_cadre_formulaire();
	echo "<b>"._T('info_changer_nom_groupe')."</b><br />\n";
	echo "<INPUT TYPE='Text' SIZE=40 CLASS='formo' NAME='change_type' VALUE=\"$type\" $onfocus />\n";

	if ($options == 'avancees' OR $descriptif) {
		echo "<B>"._T('texte_descriptif_rapide')."</B><BR>";
		echo "<TEXTAREA NAME='descriptif' CLASS='forml' ROWS='4' COLS='40' wrap=soft>";
		echo entites_html($descriptif);
		echo "</TEXTAREA><P>\n";
	}
	else
		echo "<INPUT TYPE='hidden' NAME='descriptif' VALUE=\"$descriptif\">";

	if ($options == 'avancees' OR $texte) {
		echo "<B>"._T('info_texte_explicatif')."</B><BR>";
		echo "<TEXTAREA NAME='texte' ROWS='8' CLASS='forml' COLS='40' wrap=soft>";
		echo entites_html($texte);
		echo "</TEXTAREA><P>\n";
	}
	else
		echo "<INPUT TYPE='hidden' NAME='texte' VALUE=\"$texte\">";


	echo "<p><div align='right'><INPUT TYPE='submit' CLASS='fondo' NAME='Valider' VALUE='"._T('bouton_valider')."'></div>";
	fin_cadre_formulaire();
}


echo "</td></tr></table>";



fin_cadre_relief();

if ($connect_statut =="0minirezo"){
	echo "<p>";
	debut_cadre_formulaire();
	echo "<div style='padding: 5px; border: 1px dashed #aaaaaa; background-color: #dddddd;'>";
		echo "<b>"._T('info_mots_cles_association')."</b>";
		echo "<ul>";
		
		if ($articles == "oui") $checked = "checked";
		else $checked = "";
		echo "<input type='checkbox' name='articles' value='oui' $checked id='articles'> <label for='articles'>"._T('item_mots_cles_association_articles')."</label><br>";
		$activer_breves = $GLOBALS['meta']["activer_breves"];
		if ($activer_breves != "non"){
			if ($breves == "oui") $checked = "checked";
			else $checked = "";
			echo "<input type='checkbox' name='breves' value='oui' $checked id='breves'> <label for='breves'>"._T('item_mots_cles_association_breves')."</label><br>";
		} else {
			echo "<input type='hidden' name='breves' value='non'>";
		}
		if ($rubriques == "oui") $checked = "checked";
		else $checked = "";
		echo "<input type='checkbox' name='rubriques' value='oui' $checked id='rubriques'> <label for='rubriques'>"._T('item_mots_cles_association_rubriques')."</label><br>";
		if ($syndic == "oui") $checked = "checked";
		else $checked = "";
		echo "<input type='checkbox' name='syndic' value='oui' $checked id='syndic'> <label for='syndic'>"._T('item_mots_cles_association_sites')."</label>";
		
		echo "</ul>";
	echo "</div>";


	$config_precise_groupes = $GLOBALS['meta']["config_precise_groupes"];
	if ($config_precise_groupes == "oui" OR $unseul == "oui" OR $obligatoire == "oui"){
		echo "<p><div style='padding: 5px; border: 1px dashed #aaaaaa; background-color: #dddddd;'>";

		if ($unseul == "oui")
			$checked = "checked";
		else
			$checked = "";
		echo "<input type='checkbox' name='unseul' value='oui' $checked id='unseul'> <label for='unseul'>"._T('info_selection_un_seul_mot_cle')."</label>";
		echo "<br>";

		if ($obligatoire == "oui")
			$checked = "checked";
		else $checked = "";
		echo "<input type='checkbox' name='obligatoire' value='oui' $checked id='obligatoire'> <label for='obligatoire'>"._T('avis_conseil_selection_mot_cle')."</label>";

		echo "</div>";
	} else {
		echo "<input type='hidden' name='unseul' value='non'>";
		echo "<input type='hidden' name='obligatoire' value='non'>";
	}


	
	echo "<p>";
	echo "<div style='padding: 5px; border: 1px dashed #aaaaaa; background-color: #dddddd;'>";
		echo "<b>"._T('info_qui_attribue_mot_cle')."</b>";
		echo "<ul>";
		
		if ($acces_minirezo == "oui") $checked = "checked";
		else $checked = "";
		echo "<input type='checkbox' name='acces_minirezo' value='oui' $checked id='administrateurs'> <label for='administrateurs'>"._T('bouton_checkbox_qui_attribue_mot_cle_administrateurs')."</label><br>";
		if ($acces_comite == "oui") $checked = "checked";
		else $checked = "";
		echo "<input type='checkbox' name='acces_comite' value='oui' $checked id='comite'> <label for='comite'>"._T('bouton_checkbox_qui_attribue_mot_cle_redacteurs')."</label><br>";
	
		$mots_cles_forums = $GLOBALS['meta']["mots_cles_forums"];
		$forums_publics=$GLOBALS['meta']["forums_publics"];
		
		if (($mots_cles_forums == "oui" OR $acces_forum == "oui") AND $forums_publics != "non"){
			if ($acces_forum == "oui") $checked = "checked";
			else $checked = "";
			echo "<input type='checkbox' name='acces_forum' value='oui' $checked id='forum'> <label for='forum'>"._T('bouton_checkbox_qui_attribue_mot_cle_visiteurs')."</label>";
		} 
		else {
			echo "<input type='hidden' name='acces_forum' value='non'>";
		}
			
		echo "</ul>";
	echo "</div>";
	
	
	

	echo "<p><div align='right'><INPUT TYPE='submit' CLASS='fondo' NAME='Valider' VALUE='"._T('bouton_valider')."'></div>";
	echo "</FORM><P>";
	fin_cadre_formulaire();	
	echo "</font>";


}else{

	echo "<H3>"._T('avis_non_acces_page')."</H3>";

}


fin_page();
}

?>
