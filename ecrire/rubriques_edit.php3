<?php

include ("inc.php3");

debut_page();
debut_gauche();

debut_droite();


if ($new=="oui"){
	if(!$id_parent){$id_parent=0;}
	$query="INSERT INTO spip_rubriques (titre, id_parent) VALUES ('Nouvelle rubrique', '$id_parent')";
	$result=spip_query($query);
	$id_rubrique=mysql_insert_id();
}


function mySel($varaut,$variable){
	$retour= " VALUE=\"$varaut\"";

	if ($variable==$varaut){
		$retour.= " SELECTED";
	}

	return $retour;
}

function enfant($leparent){
	global $id_parent;
	global $id_rubrique;
	global $connect_toutes_rubriques;
	global $i;
	
	$i++;
 	$query="SELECT * FROM spip_rubriques WHERE id_parent='$leparent' ORDER BY titre";
 	$result=spip_query($query);

	while($row=mysql_fetch_array($result)){
		$my_rubrique=$row['id_rubrique'];
		$titre=typo($row['titre']);
		
		if ($my_rubrique != $id_rubrique){

			$espace="";
			for ($count=0;$count<$i;$count++){$espace.="&nbsp;&nbsp;";}
			$espace .= "|";
			if ($i==1)
				$espace = "*";

			if (acces_rubrique($my_rubrique)) {
				echo "<OPTION".mySel($my_rubrique,$id_parent).">$espace $titre\n";
			}
			enfant($my_rubrique);
		}		

	}
	$i=$i-1;
}


$query="SELECT * FROM spip_rubriques WHERE id_rubrique='$id_rubrique' ORDER BY titre";
$result=spip_query($query);

while($row=mysql_fetch_array($result)){
	$id_rubrique=$row[0];
	$id_parent=$row[1];
	$titre = $row[2];
	$descriptif = $row[3];
	$texte = $row[4];
}



echo "<A HREF='naviguer.php3?coll=$id_rubrique' onMouseOver=\"retour.src='IMG2/retour-on.gif'\" onMouseOut=\"retour.src='IMG2/retour-off.gif'\"><img src='IMG2/retour-off.gif' alt='Retour &agrave; la rubrique' width='49' height='46' border='0' name='retour' align='left'></A>";
echo "Modifier la rubrique :<BR><FONT SIZE=5 COLOR='$couleur_foncee' FACE='Verdana,Arial,Helvetica,sans-serif'><B>".typo($titre)."</B></FONT><P><HR><P>";

echo "<FORM ACTION='naviguer.php3' METHOD='post'>";
echo "<INPUT TYPE='Hidden' NAME='id_rubrique' VALUE=\"$id_rubrique\">";
echo "<INPUT TYPE='Hidden' NAME='coll' VALUE=\"$id_rubrique\">";

$titre = htmlspecialchars($titre);

echo "<B>Titre</B> [Obligatoire]<BR>";
echo "<INPUT TYPE='text' CLASS='formo' NAME='titre' VALUE=\"$titre\" SIZE='40'><P>";


if ($options=="avancees"){
	echo "<B>&Agrave; l'int&eacute;rieur de la rubrique&nbsp;:</B> ".aide ("rubrub")."<BR>\n";
	echo "<SELECT NAME='id_parent' CLASS='forml' SIZE=1>\n";
	if ($connect_toutes_rubriques) {
		echo "<OPTION".mySel("0",$id_parent).">Racine du site\n";
	} else {
		echo "<OPTION".mySel("0",$id_parent).">Ne pas d&eacute;placer...\n";
	}
	// si le parent ne fait pas partie des rubriques restreintes, modif impossible
	if (acces_rubrique($id_parent)) {
		enfant(0);
	}
	echo "</SELECT>\n";

	// si c'est une rubrique-secteur contenant des breves, ne pas proposer de deplacer
	$query = "SELECT COUNT(*) FROM spip_breves WHERE id_rubrique=\"$id_rubrique\"";
	$row = mysql_fetch_array(spip_query($query));
	$contient_breves = $row[0];
	if ($contient_breves > 0) {
		echo "<font size='2'><input type='checkbox' name='confirme_deplace' value='oui' id='confirme_deplace'><label for='confirme_deplace'>&nbsp;<i>Attention&nbsp;! Cette rubrique contient $contient_breves br&egrave;ve".($contient_breves>1? 's':'')."&nbsp;: si vous la d&eacute;placez, veuillez cocher cette case de confirmation.</i></font></label>\n";
	}

	echo "<P>";

} else {
	echo "<INPUT TYPE='Hidden' NAME='id_parent' VALUE=\"$id_parent\">";
}

if ($options=="avancees" OR strlen($descriptif)>0){
	echo "<B>Descriptif rapide</B><BR>";
	echo "(Contenu de la rubrique en quelques mots.)<BR>";
	echo "<TEXTAREA NAME='descriptif' CLASS='forml' ROWS='4' COLS='40' wrap=soft>";
	echo $descriptif;
	echo "</TEXTAREA><P>\n";
}else{
	echo "<INPUT TYPE='Hidden' NAME='descriptif' VALUE=\"$descriptif\">";
}

echo "<B>Texte explicatif</B>";
echo aide ("raccourcis");
echo "<BR><TEXTAREA NAME='texte' ROWS='20' CLASS='forml' COLS='40' wrap=soft>";
echo $texte;
echo "</TEXTAREA>\n";

echo "<P align='right'><INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
echo "</FORM>";


fin_page();

?>
