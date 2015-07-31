<?php

include ("inc.php3");


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
	global $couleur_claire;
	
	$i++;
 	$query="SELECT * FROM spip_rubriques WHERE id_parent='$leparent' ORDER BY titre";
 	$result=spip_query($query);

	while($row=mysql_fetch_array($result)){
		$my_rubrique=$row['id_rubrique'];
		$titre=typo($row['titre']);
		
		if ($my_rubrique != $id_rubrique){

			$espace="";
			for ($count=1;$count<$i;$count++){
				$espace.="&nbsp;&nbsp;&nbsp; ";
			}
			if ($i > 3) $style .= "color: #666666;";
			if ($i > 4) $style .= "font-style: italic;";
			if ($i < 3) $style .= "font-weight:bold; ";
			if ($i==1) {
				$espace= "";
				$style .= "background-color: $couleur_claire;";
			}
			
			if ($statut_rubrique!='publie') $titre = "($titre)";

			if (acces_rubrique($my_rubrique)) {
				echo "<OPTION".mySel($my_rubrique,$id_parent)." style=\"$style\">$espace$titre\n";
			}
			enfant($my_rubrique);
		}		

	}
	$i=$i-1;
}


if ($new == "oui") {
	$id_parent = intval($id_parent);
	$id_rubrique = 0;
	$titre = "Nouvelle rubrique";
	$descriptif = "";
	$texte = "";
}
else {
	$query = "SELECT * FROM spip_rubriques WHERE id_rubrique='$id_rubrique' ORDER BY titre";
	$result = spip_query($query);
	while ($row = mysql_fetch_array($result)) {
		$id_rubrique = $row['id_rubrique'];
		$id_parent = $row['id_parent'];
		$titre = $row['titre'];
		$descriptif = $row['descriptif'];
		$texte = $row['texte'];
	}
}

debut_page("Modifier : $titre_page", "documents", "rubriques");

if ($id_parent == 0) $ze_logo = "secteur-24.gif";
else $ze_logo = "rubrique-24.gif";

if ($id_parent == 0) $logo_parent = "racine-site-24.gif";
else {
	$query = "SELECT id_parent FROM spip_rubriques WHERE id_rubrique='$id_parent'";
 	$result=spip_query($query);
	while($row=mysql_fetch_array($result)){
		$parent_parent=$row['id_parent'];
	}
	if ($parent_parent == 0) $logo_parent = "secteur-24.gif";
	else $logo_parent = "rubrique-24.gif";
}



debut_grand_cadre();

afficher_parents($id_parent);
$parents="~ <img src='img_pack/racine-site-24.gif' width=24 height=24 align='middle'> <A HREF='naviguer.php3?coll=0'><B>RACINE DU SITE</B></A> ".aide ("rubhier")."<BR>".$parents;

$parents=ereg_replace("~","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$parents);
$parents=ereg_replace("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ","",$parents);

echo "$parents";

fin_grand_cadre();

debut_gauche();
//////// parents



debut_droite();

debut_cadre_formulaire();

echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr width='100%'>";
echo "<td>";

if ($id_rubrique) icone("Retour", "naviguer.php3?coll=$id_rubrique", $ze_logo, "rien.gif");
else icone("Retour", "naviguer.php3?coll=$id_parent", $ze_logo, "rien.gif");

echo "</td>";
	echo "<td><img src='img_pack/rien.gif' width=10></td>\n";
echo "<td width='100%'>";
echo "Modifier la rubrique :";
gros_titre($titre);
echo "</td></tr></table>";
echo "<p>";

echo "<FORM ACTION='naviguer.php3' METHOD='post'>";
echo "<INPUT TYPE='Hidden' NAME='coll' VALUE=\"$id_rubrique\">";
if ($new == "oui") echo "<INPUT TYPE='Hidden' NAME='new' VALUE=\"oui\">";

$titre = htmlspecialchars($titre);

echo "<B>Titre</B> [Obligatoire]<BR>";
echo "<INPUT TYPE='text' CLASS='formo' NAME='titre' VALUE=\"$titre\" SIZE='40'><P>";


if ($options=="avancees") {
	debut_cadre_relief("$logo_parent");
	echo "<B>&Agrave; l'int&eacute;rieur de la rubrique&nbsp;:</B> ".aide ("rubrub")."<BR>\n";
	echo "<SELECT NAME='id_parent'  style='background-color:#ffffff; font-size:10px; width:100%; font-face:verdana,arial,helvetica,sans-serif;' CLASS='forml' SIZE=1>\n";
	if ($connect_toutes_rubriques) {
		echo "<OPTION".mySel("0",$id_parent)." style='background-color:$couleur_foncee; font-weight:bold; color:white;'>Racine du site\n";
	} else {
		echo "<OPTION".mySel("0",$id_parent).">Ne pas d&eacute;placer...\n";
	}
	// si le parent ne fait pas partie des rubriques restreintes, modif impossible
	if (acces_rubrique($id_parent)) {
		enfant(0);
	}
	echo "</SELECT>\n";

	// si c'est une rubrique-secteur contenant des breves, ne pas proposer de deplacer
	$query = "SELECT COUNT(*) AS cnt FROM spip_breves WHERE id_rubrique=\"$id_rubrique\"";
	$row = mysql_fetch_array(spip_query($query));
	$contient_breves = $row['cnt'];
	if ($contient_breves > 0) {
		echo "<br><font size='2'><input type='checkbox' name='confirme_deplace' value='oui' id='confirme-deplace'><label for='confirme-deplace'>&nbsp;Attention&nbsp;! Cette rubrique contient $contient_breves br&egrave;ve".($contient_breves>1? 's':'')."&nbsp;: si vous la d&eacute;placez, veuillez cocher cette case de confirmation.</font></label>\n";
	}
	fin_cadre_relief();

	echo "<P>";

} else {
	echo "<INPUT TYPE='Hidden' NAME='id_parent' VALUE=\"$id_parent\">";
}

if ($options == "avancees" OR $descriptif) {
	echo "<B>Descriptif rapide</B><BR>";
	echo "(Contenu de la rubrique en quelques mots.)<BR>";
	echo "<TEXTAREA NAME='descriptif' CLASS='forml' ROWS='4' COLS='40' wrap=soft>";
	echo $descriptif;
	echo "</TEXTAREA><P>\n";
}
else {
	echo "<INPUT TYPE='Hidden' NAME='descriptif' VALUE=\"$descriptif\">";
}

echo "<B>Texte explicatif</B>";
echo aide ("raccourcis");
echo "<BR><TEXTAREA NAME='texte' ROWS='20' CLASS='forml' COLS='40' wrap=soft>";
echo $texte;
echo "</TEXTAREA>\n";

echo "<P align='right'><INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
echo "</FORM>";
fin_cadre_formulaire();

fin_page();

?>
