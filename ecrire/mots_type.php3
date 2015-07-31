<?php

include ("inc.php3");


if ($connect_statut == '0minirezo' AND $new == "oui") {
	$id_groupe = '';
	$type = htmlspecialchars("Nouveau groupe");
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
	while($row = mysql_fetch_array($result_groupes)) {
		$id_groupe = $row['id_groupe'];
		$type = htmlspecialchars($row['titre']);
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
	icone("Retour", "mots_tous.php3", "mot-cle-24.gif", "rien.gif");
	echo "</td>";
	echo "<td><img src='img_pack/rien.gif' width=5></td>\n";


echo "<td width='100%' valign='top'>";
echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=1><b>GROUPE DE MOTS :</b><br></font>";
gros_titre($type);
echo aide("motsgroupes");

if ($connect_statut =="0minirezo"){
	$type=htmlspecialchars(urldecode($type));
	echo "<p><font face='Verdana,Arial,Helvetica,sans-serif'>";
	echo "<FORM ACTION='mots_tous.php3' METHOD='post'>\n";
	echo "<INPUT TYPE='Hidden' NAME='modifier_groupe' VALUE=\"oui\">\n";
	echo "<INPUT TYPE='Hidden' NAME='id_groupe' VALUE=\"$id_groupe\">\n";
	echo "<INPUT TYPE='Hidden' NAME='ancien_type' VALUE=\"$ancien_type\">\n";
	debut_cadre_formulaire();
	echo "<b>Changer le nom de ce groupe :</b><br>\n";
	echo "<INPUT TYPE='Text' SIZE=40 CLASS='formo' NAME='change_type' VALUE=\"$type\">\n";
	echo "<p><div align='right'><INPUT TYPE='submit' CLASS='fondo' NAME='Valider' VALUE='Valider'></div>";
	fin_cadre_formulaire();
}


echo "</td></tr></table>";



fin_cadre_relief();

if ($connect_statut =="0minirezo"){
	echo "<p>";
	debut_cadre_formulaire();
	echo "<div style='padding: 5px; border: 1px dashed #aaaaaa; background-color: #dddddd;'>";
		echo "<b>Les mots-cl&eacute;s de ce groupe peuvent &ecirc;tre associ&eacute;s&nbsp;:</b>";
		echo "<ul>";
		
		if ($articles == "oui") $checked = "checked";
		else $checked = "";
		echo "<input type='checkbox' name='articles' value='oui' $checked id='articles'> <label for='articles'>aux articles</label><br>";
		$activer_breves = lire_meta("activer_breves");
		if ($activer_breves != "non"){
			if ($breves == "oui") $checked = "checked";
			else $checked = "";
			echo "<input type='checkbox' name='breves' value='oui' $checked id='breves'> <label for='breves'>aux br&egrave;ves</label><br>";
		} else {
			echo "<input type='hidden' name='breves' value='non'>";
		}
		if ($rubriques == "oui") $checked = "checked";
		else $checked = "";
		echo "<input type='checkbox' name='rubriques' value='oui' $checked id='rubriques'> <label for='rubriques'>aux rubriques</label><br>";
		if ($syndic == "oui") $checked = "checked";
		else $checked = "";
		echo "<input type='checkbox' name='syndic' value='oui' $checked id='syndic'> <label for='syndic'>aux sites r&eacute;f&eacute;renc&eacute;s ou syndiqu&eacute;s.</label>";
		
		echo "</ul>";
	echo "</div>";


	$config_precise_groupes = lire_meta("config_precise_groupes");
	if ($config_precise_groupes == "oui" OR $unseul == "oui" OR $obligatoire == "oui"){
		echo "<p><div style='padding: 5px; border: 1px dashed #aaaaaa; background-color: #dddddd;'>";

		if ($unseul == "oui")
			$checked = "checked";
		else
			$checked = "";
		echo "<input type='checkbox' name='unseul' value='oui' $checked id='unseul'> <label for='unseul'>On ne peut s&eacute;lectionner qu'<b>un seul mot-cl&eacute; &agrave;</b> la fois dans ce groupe.</label>";
		echo "<br>";

		if ($obligatoire == "oui")
			$checked = "checked";
		else $checked = "";
		echo "<input type='checkbox' name='obligatoire' value='oui' $checked id='obligatoire'> <label for='obligatoire'><b>Groupe important&nbsp;:</b> il est fortement conseill&eacute; de s&eacute;lectionner un mot-cl&eacute; dans ce groupe.</label>";

		echo "</div>";
	} else {
		echo "<input type='hidden' name='unseul' value='non'>";
		echo "<input type='hidden' name='obligatoire' value='non'>";
	}


	
	echo "<p>";
	echo "<div style='padding: 5px; border: 1px dashed #aaaaaa; background-color: #dddddd;'>";
		echo "<b>Les mots de ce groupe peuvent &ecirc;tre attribu&eacute;s par&nbsp;:</b>";
		echo "<ul>";
		
		if ($acces_minirezo == "oui") $checked = "checked";
		else $checked = "";
		echo "<input type='checkbox' name='acces_minirezo' value='oui' $checked id='administrateurs'> <label for='administrateurs'>les administrateurs du site</label><br>";
		if ($acces_comite == "oui") $checked = "checked";
		else $checked = "";
		echo "<input type='checkbox' name='acces_comite' value='oui' $checked id='comite'> <label for='comite'>les r&eacute;dacteurs</label><br>";
	
		$mots_cles_forums = lire_meta("mots_cles_forums");
		$forums_publics=lire_meta("forums_publics");
		
		if (($mots_cles_forums == "oui" OR $acces_forum == "oui") AND $forums_publics != "non"){
			if ($acces_forum == "oui") $checked = "checked";
			else $checked = "";
			echo "<input type='checkbox' name='acces_forum' value='oui' $checked id='forum'> <label for='forum'>les visiteurs du site public lorsqu'ils postent un message dans un forum.</label>";
		} 
		else {
			echo "<input type='hidden' name='acces_forum' value='non'>";
		}
			
		echo "</ul>";
	echo "</div>";
	
	
	

	echo "<p><div align='right'><INPUT TYPE='submit' CLASS='fondo' NAME='Valider' VALUE='Valider'></div>";
	echo "</FORM><P>";
	fin_cadre_formulaire();	
	echo "</font>";


}else{

	echo "<H3>Vous n'avez pas acc&egrave;s &agrave; cette page.</H3>";

}


fin_page();

?>
