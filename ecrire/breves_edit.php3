<?php

include ("inc.php3");



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
		$my_rubrique=$row['id_rubrique'];
		$titre=$row['titre'];
		$descriptif=$row['descriptif'];
		$texte=$row['texte'];
		echo "<OPTION".mySel($my_rubrique,$id_rubrique).">$titre\n";		
	}
}


$query = "SELECT * FROM spip_breves WHERE id_breve='$id_breve'";
$result = spip_query($query);

while($row=mysql_fetch_array($result)){
	$id_breve=$row['id_breve'];
	$date_heure=$row['date_heure'];
	$titre=$row['titre'];
	$texte=$row['texte'];
	$lien_titre=$row['lien_titre'];
	$lien_url=$row['lien_url'];
	$statut=$row['statut'];
	$id_rubrique=$row['id_rubrique'];
	if ($new == "oui") $statut = "prop";
}



debut_page("Modifier la br&egrave;ve : &laquo; $titre &raquo;", "documents", "breves");


debut_grand_cadre();

afficher_parents($id_rubrique);
$parents="~ <img src='img_pack/racine-site-24.gif' width=24 height=24 align='middle'> <A HREF='naviguer.php3?coll=0'><B>RACINE DU SITE</B></A> ".aide ("rubhier")."<BR>".$parents;

$parents=ereg_replace("~","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$parents);
$parents=ereg_replace("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ","",$parents);

echo "$parents";

fin_grand_cadre();
debut_gauche();
debut_droite();
debut_cadre_formulaire();


echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr width='100%'>";
echo "<td>";
	icone("Retour", "breve_voir.php3?id_breve=$id_breve", "breve-24.gif", "rien.gif");

echo "</td>";
	echo "<td><img src='img_pack/rien.gif' width=10></td>\n";
echo "<td width='100%'>";
echo "Modifier la br&egrave;ve :";
gros_titre($titre);
echo "</td></tr></table>";
echo "<p>";


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



	/// Dans la rubrique....

	if ($id_rubrique == 0) $logo_parent = "racine-site-24.gif";
	else {
		$query = "SELECT id_parent FROM spip_rubriques WHERE id_rubrique='$id_rubrique'";
		$result=spip_query($query);
		while($row=mysql_fetch_array($result)){
			$parent_parent=$row['id_parent'];
		}
		if ($parent_parent == 0) $logo_parent = "secteur-24.gif";
		else $logo_parent = "rubrique-24.gif";
	}

	debut_cadre_relief("$logo_parent");

		echo "<SELECT NAME='id_rubrique' CLASS='forml' SIZE=1>\n";
		enfant(0);
		echo "</SELECT><P>\n";

	fin_cadre_relief();
	
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
		debut_cadre_relief();
		echo "<B>Cette br&egrave;ve doit-elle &ecirc;tre publi&eacute;e ?</B>\n";

		echo "<SELECT NAME='statut' SIZE=1 CLASS='fondl'>\n";
		
		echo "<OPTION".mySel("prop",$statut).">Br&egrave;ve propos&eacute;e\n";		
		echo "<OPTION".mySel("refuse",$statut).">NON - Br&egrave;ve refus&eacute;e\n";		
		echo "<OPTION".mySel("publie",$statut).">OUI - Br&egrave;ve valid&eacute;e\n";		

		echo "</SELECT>".aide ("brevesstatut")."<P>\n";
		fin_cadre_relief();
	}
	else {
		echo "<INPUT TYPE='Hidden' NAME='statut' VALUE=\"$statut\">";
	}
	echo "<P ALIGN='right'><INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'  >";
	echo "</FORM>";
}
else echo "<H2>Page interdite</H2>";

fin_cadre_formulaire();
fin_page();

?>
