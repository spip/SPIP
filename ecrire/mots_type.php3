<?

include ("inc.php3");


if ($connect_statut == '0minirezo' AND $new == "oui") {
	mysql_query("INSERT INTO spip_groupes_mots 
		(titre, unseul, obligatoire, articles, breves, rubriques, syndic, 0minirezo, 1comite, 6forum)
		VALUES (\" Nouveau groupe\", 'non', 'non', 'oui', 'oui', 'non', 'oui', 'oui', 'oui', 'non')");

	$id_groupe = mysql_insert_id();

}

$query_groupes = "SELECT * FROM spip_groupes_mots WHERE id_groupe='$id_groupe'";
$result_groupes = mysql_query($query_groupes);
while($row = mysql_fetch_array($result_groupes)) {
	$id_groupe = $row['id_groupe'];
	$titre = htmlspecialchars($row['titre']);
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


debut_page($titre_breve);
debut_gauche();



debut_droite();


echo "<A HREF='mots_tous.php3' onMouseOver=\"retour.src='IMG2/retour-on.gif'\" onMouseOut=\"retour.src='IMG2/retour-off.gif'\"><img src='IMG2/retour-off.gif' alt='Retour' width='49' height='46' border='0' name='retour' align='left'></A>";

echo "&nbsp; <font face='verdana,arial,helvetica' size=1><b>GROUPE DE MOTS :</b><br></font>";
echo "&nbsp; <font face='verdana,arial,helvetica' size=5><b>$titre</b></font>";
echo aide("motsgroupes")."<p><br><p>";


if ($connect_statut =="0minirezo"){

	$type=htmlspecialchars(urldecode($type));
	
	
	echo "<font face='verdana,arial,helvetica'>";
	echo "<FORM ACTION='mots_tous.php3' METHOD='post'>\n";
	echo "<INPUT TYPE='Hidden' NAME='modifier_groupe' VALUE=\"oui\">\n";
	echo "<INPUT TYPE='Hidden' NAME='id_groupe' VALUE=\"$id_groupe\">\n";
	echo "<INPUT TYPE='Hidden' NAME='ancien_type' VALUE=\"$titre\">\n";


	debut_cadre_relief();
	echo "<b>Titre de ce groupe :</b><br>\n";
	echo "<INPUT TYPE='Text' SIZE=40 CLASS='formo' NAME='change_type' VALUE=\"$titre\">\n";
	fin_cadre_relief();


	echo "<p><div class='forml' style='padding:5px; border: solid black 1px'>";
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
		echo "<p>";
		echo "<div class='forml' style='padding:5px; border: solid black 1px'>";
			
			if ($unseul == "oui") $checked = "checked";
			else $checked = "";
			echo "<input type='checkbox' name='unseul' value='oui' $checked id='unseul'> <label for='unseul'>On ne peut s&eacute;lectionner qu'<b>un seul mot-cl&eacute; &agrave;</b> la fois dans ce groupe.</label>";
			echo "<br>";
			if ($obligatoire == "oui") $checked = "checked";
			else $checked = "";
			echo "<input type='checkbox' name='obligatoire' value='oui' $checked id='obligatoire'> <label for='obligatoire'><b>Groupe important&nbsp;:</b> il est fortement conseill&eacute; de s&eacute;lectionner un mot-cl&eacute; dans ce groupe.</label>";


		echo "</div>";
	}


	
	echo "<p>";
	echo "<div class='forml' style='padding:5px; border: solid black 1px'>";
		echo "<b>Les mots de ce groupe peuvent être attribués par&nbsp;:</b>";
		echo "<ul>";
		
		if ($acces_minirezo == "oui") $checked = "checked";
		else $checked = "";
		echo "<input type='checkbox' name='acces_minirezo' value='oui' $checked id='administrateurs'> <label for='administrateurs'>les administrateurs du site</label><br>";
		if ($acces_comite == "oui") $checked = "checked";
		else $checked = "";
		echo "<input type='checkbox' name='acces_comite' value='oui' $checked id='comite'> <label for='comite'>les rédacteurs</label><br>";
	
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
	
	
	

	echo "<p><div align='right'><INPUT TYPE='submit' CLASS='fondo' NAME='Valider' VALUE='Valider'>";
	echo "</FORM><P>";
	
	echo "</font>";


}else{

	echo "<H3>Vous n'avez pas acc&egrave;s &agrave; cette page.</H3>";

}







fin_page();

?>
