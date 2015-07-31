<?php

include ("inc.php3");


if ($connect_statut == '0minirezo' AND $new == "oui") {
	$id_groupe = '';
	$type = entites_html(_T('titre_nouveau_groupe'));
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
	$query_groupes = "SELECT * FROM spip_groupes_mots WHERE id_groupe='$id_groupe'";
	$result_groupes = spip_query($query_groupes);
	while($row = spip_fetch_array($result_groupes)) {
		$id_groupe = $row['id_groupe'];
		$type = entites_html($row['titre']);
		$ancien_type = $type;
		$unseul = $row['unseul'];
		$obligatoire = $row['obligatoire'];
		$articles = $row['articles'];
		$breves = $row['breves'];
		$rubriques = $row['rubriques'];
		$syndic = $row['syndic'];
		$acces_minirezo = $row['0minirezo'];
		$acces_comite = $row['1comite'];
		$acces_forum = $row['6forum'];
	}
}

debut_page("&laquo; $type &raquo;", "documents", "mots");

debut_gauche();



debut_droite();

debut_cadre_relief("groupe-mot-24.gif");



echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr width='100%'>";

	echo "<td  align='right' valign='top'>";
	icone(_T('icone_retour'), "mots_tous.php3", "mot-cle-24.gif", "rien.gif");
	echo "</td>";
	echo "<td><img src='img_pack/rien.gif' width=5></td>\n";


echo "<td width='100%' valign='top'>";
echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=1><b>"._T('titre_groupe_mots')."</b><br></font>";
gros_titre($type);
echo aide("motsgroupes");

if ($connect_statut =="0minirezo"){
	$type=entites_html(urldecode($type));
	echo "<p><font face='Verdana,Arial,Helvetica,sans-serif'>";
	echo "<FORM ACTION='mots_tous.php3' METHOD='post'>\n";
	echo "<INPUT TYPE='Hidden' NAME='modifier_groupe' VALUE=\"oui\">\n";
	echo "<INPUT TYPE='Hidden' NAME='id_groupe' VALUE=\"$id_groupe\">\n";
	echo "<INPUT TYPE='Hidden' NAME='ancien_type' VALUE=\"$ancien_type\">\n";
	debut_cadre_formulaire();
	echo "<b>"._T('info_changer_nom_groupe')."</b><br>\n";
	echo "<INPUT TYPE='Text' SIZE=40 CLASS='formo' NAME='change_type' VALUE=\"$type\">\n";
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
		$activer_breves = lire_meta("activer_breves");
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


	$config_precise_groupes = lire_meta("config_precise_groupes");
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
	
		$mots_cles_forums = lire_meta("mots_cles_forums");
		$forums_publics=lire_meta("forums_publics");
		
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

?>
