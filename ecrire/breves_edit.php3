<?php

include ("inc.php3");


debut_page();
debut_gauche();
debut_droite();


if ($new=="oui") {
	if (!$id_rubrique) $id_rubrique=0;

	$mydate = date("YmdHis", time() - 12 * 3600);
	$query = "DELETE FROM spip_breves WHERE (statut = 'refuse') && (maj < $mydate)";
	$result = spip_query($query);

	$query="INSERT INTO spip_breves (titre, date_heure, id_rubrique, statut) VALUES ('Nouvelle breve', NOW(), '$id_rubrique', 'refuse')";
	$result=spip_query($query);
	$id_breve=mysql_insert_id();
}


function mySel($varaut,$variable){
		$retour= " VALUE=\"$varaut\"";

	if ($variable==$varaut){
		$retour.= " SELECTED";
	}

	return $retour;
}

function enfant($leparent) {
	global $id_parent;
	global $id_rubrique;
 	$query="SELECT * FROM spip_rubriques WHERE id_parent='$leparent' ORDER BY titre";
 	$result=spip_query($query);

	while($row=mysql_fetch_array($result)){
		$my_rubrique=$row[0];
		$titre=$row[2];
		$descriptif=$row[3];
		$texte=$row[4];
		echo "<OPTION".mySel($my_rubrique,$id_rubrique).">$titre\n";		
	}
}


$query = "SELECT * FROM spip_breves WHERE id_breve='$id_breve'";
$result = spip_query($query);

while($row=mysql_fetch_array($result)){
	$id_breve=$row[0];
	$date_heure=$row[1];
	$titre=$row[2];
	$texte=$row[3];
	$lien_titre=$row[4];
	$lien_url=$row[5];
	$statut=$row[6];
	$id_rubrique=$row[7];
	if ($new == "oui") $statut = "prop";
}

if ($connect_statut=="0minirezo" OR $statut=="prop") {
	echo "<FORM ACTION='breves_voir.php3?id_breve=$id_breve' METHOD='post'>";

	echo "<INPUT TYPE='Hidden' NAME='modifier_breve' VALUE=\"oui\">";
	echo "<INPUT TYPE='Hidden' NAME='id_breve' VALUE=\"$id_breve\">";
	echo "<INPUT TYPE='Hidden' NAME='statut_old' VALUE=\"$statut\">";

	$titre = htmlspecialchars($titre);
	$lien_titre = htmlspecialchars($lien_titre);

	echo "<B>Titre</B> [Obligatoire]<BR>";
	echo "<INPUT TYPE='text' CLASS='formo' NAME='titre' VALUE=\"$titre\" SIZE='40'><P>";

		echo "<B>&Agrave; l'int&eacute;rieur de la rubrique&nbsp;:</B>".aide ("brevesrub")."<BR>\n";

		echo "<SELECT NAME='id_rubrique' CLASS='forml' SIZE=1>\n";
		enfant(0);
		echo "</SELECT><P>\n";


	echo "<B>Texte de la br&egrave;ve</B><BR>";
	echo "<TEXTAREA NAME='texte' ROWS='15' CLASS='formo' COLS='40' wrap=soft>";
	echo $texte;
	echo "</TEXTAREA><P>\n";


	echo "<B>Lien hypertexte</B> (r&eacute;f&eacute;rence, site &agrave; visiter...)".aide ("breveslien")."<BR>";
	echo "Titre :<BR>";
	echo "<INPUT TYPE='text' CLASS='forml' NAME='lien_titre' VALUE=\"$lien_titre\" SIZE='40'><BR>";

	if (strlen($lien_url) < 8) $lien_url="http://";
	echo "URL :<BR>";
	echo "<INPUT TYPE='text' CLASS='forml' NAME='lien_url' VALUE=\"$lien_url\" SIZE='40'><P>";


	if ($connect_statut=="0minirezo" AND acces_rubrique($id_rubrique)) {
		echo "<B>Cette br&egrave;ve doit-elle &ecirc;tre publi&eacute;e ?</B>\n";

		echo "<SELECT NAME='statut' SIZE=1 CLASS='fondl'>\n";
		
		echo "<OPTION".mySel("prop",$statut).">Br&egrave;ve propos&eacute;e\n";		
		echo "<OPTION".mySel("refuse",$statut).">NON - Br&egrave;ve refus&eacute;e\n";		
		echo "<OPTION".mySel("publie",$statut).">OUI - Br&egrave;ve valid&eacute;e\n";		

		echo "</SELECT>".aide ("brevesstatut")."<P>\n";
	}
	else {
		echo "<INPUT TYPE='Hidden' NAME='statut' VALUE=\"$statut\">";
	}
	echo "<P ALIGN='right'><INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'  >";
	echo "</FORM>";
}
else echo "<H2>Page interdite</H2>";

fin_page();

?>
