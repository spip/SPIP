<?

include ("inc.php3");
include_local ("inc_index.php3");
include_local ("inc_logos.php3");
include_local ("inc_mots.php3");

if (!$id_breve) $id_breve=0;

$flag_mots = lire_meta("articles_mots");




function my_sel($num,$tex,$comp){
	if ($num==$comp){
		echo "<OPTION VALUE='$num' SELECTED>$tex\n";
	}else{
		echo "<OPTION VALUE='$num'>$tex\n";
	}

}

function afficher_mois($mois){
	my_sel("00","non connu",$mois);
	my_sel("01","janvier",$mois);
	my_sel("02","f&eacute;vrier",$mois);
	my_sel("03","mars",$mois);
	my_sel("04","avril",$mois);
	my_sel("05","mai",$mois);
	my_sel("06","juin",$mois);
	my_sel("07","juillet",$mois);
	my_sel("08","ao&ucirc;t",$mois);
	my_sel("09","septembre",$mois);
	my_sel("10","octobre",$mois);
	my_sel("11","novembre",$mois);
	my_sel("12","d&eacute;cembre",$mois);
}

function afficher_annee($annee){
	// Cette ligne permettrait de faire des articles sans date de publication
	// my_sel("0000","n.c.",$annee); 

	if($annee<1996 AND $annee <> 0){
		echo "<OPTION VALUE='$annee' SELECTED>$annee\n";
	}
	for($i=1996;$i<date(Y)+2;$i++){
		my_sel($i,$i,$annee);
	}
}

function afficher_jour($jour){
	my_sel("00","n.c.",$jour);
	for($i=1;$i<32;$i++){
		if ($i<10){$aff="&nbsp;".$i;}else{$aff=$i;}
		my_sel($i,$aff,$jour);
	}
}


if ($titre AND $modifier_breve) {
	$titre = addslashes($titre);
	$texte = addslashes($texte);
	$lien_titre = addslashes($lien_titre);
	$query = "UPDATE spip_breves SET titre=\"$titre\", texte=\"$texte\", lien_titre=\"$lien_titre\", lien_url=\"$lien_url\", statut=\"$statut\", id_rubrique=\"$id_rubrique\" WHERE id_breve=$id_breve";
	$result = mysql_query($query);
	if (lire_meta('activer_moteur') == 'oui') {
		indexer_breve($id_breve);
	}
	calculer_rubriques();
}


if ($jour AND $connect_statut == '0minirezo') {
	if ($annee == "0000") $mois = "00";
	if ($mois == "00") $jour = "00";
	$query = "UPDATE spip_breves SET date_heure='$annee-$mois-$jour' WHERE id_breve=$id_breve";
	$result = mysql_query($query);
	calculer_dates_rubriques();
}


$query = "SELECT * FROM spip_breves WHERE id_breve='$id_breve'";
$result = mysql_query($query);

while ($row = mysql_fetch_array($result)) {
	$id_breve=$row[0];
	$date_heure=$row[1];
	$titre_breve=$row[2];
	$titre=$row[2];
	$texte=$row[3];
	$lien_titre=$row[4];
	$lien_url=$row[5];
	$statut=$row[6];
	$id_rubrique=$row[7];
}

$flag_editable = (($connect_statut == '0minirezo' AND acces_rubrique($id_rubrique)) OR $statut == 'prop');



debut_page("&laquo; $titre_breve &raquo;");
debut_gauche();


debut_boite_info();

echo "<CENTER>";
if ($statut == "publie") {
	echo "<A HREF='../spip_redirect.php3?id_breve=$id_breve&recalcul=oui'><img src='IMG2/voirenligne.gif' alt='voir en ligne' width='48' height='48' border='0' align='right'></A>";
}

echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=1><B>BR&Egrave;VE NUM&Eacute;RO&nbsp;:</B></FONT>";
echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=6><B>$id_breve</B></FONT>";
echo "</CENTER>";

fin_boite_info();


//////////////////////////////////////////////////////
// Logos de la breve
//

$arton = "breveon$id_breve";
$artoff = "breveoff$id_breve";
$arton_ok = get_image($arton);
if ($arton_ok) $artoff_ok = get_image($artoff);

if ($connect_statut == '0minirezo' AND acces_rubrique($id_rubrique) AND ($options == 'avancees' OR $arton_ok)) {

	debut_boite_info();

	afficher_boite_logo($arton, "LOGO DE LA BREVE".aide ("breveslogo"));

	if (($options == 'avancees' AND $arton_ok) OR $artoff_ok) {
		echo "<P>";
		afficher_boite_logo($artoff, "LOGO POUR SURVOL");
	}

	fin_boite_info();
}




debut_droite();

echo "<TABLE CELLPADDING=18 CELLSPACING=0 BORDER=1 WIDTH=\"100%\"><TR><TD BGCOLOR='#FFFFFF' ALIGN='center' WIDTH=\"100%\">";
echo "<CENTER>";
echo "<TABLE WIDTH=100% CELLPADDING=0 CELLSPACING=0 BORDER=0>";
echo "<TR><td>";

echo "<font face='Georgia,Garamond,Times,serif'>";




function enfant($leparent){
	global $id_parent;
	global $id_rubrique;
	global $i;
	
	$i++;
 	$query="SELECT * FROM spip_rubriques WHERE id_parent='$leparent' ORDER BY titre";
 	$result=mysql_query($query);

	while($row=mysql_fetch_array($result)){
		$my_rubrique=$row[0];
		$titre=$row[2];
		echo "<OPTION".mySel($my_rubrique,$id_rubrique).">$titre\n";
	}
	$i=$i-1;

}




echo "<P>";

echo "<table width='100%'><tr width='100%'>";

echo "<td><A HREF='breves.php3' onMouseOver=\"retour.src='IMG2/retour-on.gif'\" onMouseOut=\"retour.src='IMG2/retour-off.gif'\"><img src='IMG2/retour-off.gif' alt='Retour aux Br&egrave;ves' width='49' height='46' border='0' name='retour' align='left'></A></td>";


echo "<td align='center'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=5><B>".typo($titre)."</B></FONT></td>";

if ($flag_editable){
	echo "<td align='right'><A HREF='breves_edit.php3?id_breve=$id_breve' onMouseOver=\"modif_breve.src='IMG2/modifier-breve-on.gif'\" onMouseOut=\"modif_breve.src='IMG2/modifier-breve-off.gif'\"><img src='IMG2/modifier-breve-off.gif' alt='Modifier cette breve' width='58' height='50' border='0' align='right' name='modif_breve'></A></td>";
}
echo "</tr></table>";


if ($flag_editable AND ($options == 'avancees' OR $statut == 'publie')) {

	if ($statut == 'publie') {	
		echo "<p>";

		if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})", $date_heure, $regs)) {
		        $mois = $regs[2];
		        $jour = $regs[3];
		        $annee = $regs[1];
		}


		debut_cadre_relief();
		echo "<FORM ACTION='breves_voir.php3?id_breve=$id_breve' METHOD='GET'>";
		echo "<INPUT TYPE='hidden' NAME='id_breve' VALUE='$id_breve'>";
		echo "<INPUT NAME='options' TYPE=Hidden VALUE=\"$options\">";
		echo "<TABLE CELLPADDING=5 CELLSPACING=0 BORDER=0 WIDTH=100% BACKGROUND='IMG2/rien.gif'>";
		echo "<TR><TD BGCOLOR='$couleur_foncee' COLSPAN=2><FONT SIZE=2 COLOR='#FFFFFF'><B>DATE DE PUBLICATION EN LIGNE :";
		//echo aide ("artdate");
		echo "</B></FONT></TR>";
		echo "<TR><TD ALIGN='center' BGCOLOR='#FFFFFF'>";
		echo "<SELECT NAME='jour' SIZE=1 CLASS='fondl'>";
		afficher_jour($jour);
		echo "</SELECT> ";
		echo "<SELECT NAME='mois' SIZE=1 CLASS='fondl'>";
		afficher_mois($mois);
		echo "</SELECT> ";
		echo "<SELECT NAME='annee' SIZE=1 CLASS='fondl'>";
		afficher_annee($annee);
		echo "</SELECT>";
 		
		echo "</TD><TD ALIGN='right' BGCOLOR='#FFFFFF'>";
		echo "<INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='Changer'>";
		echo "</TD></TR></TABLE>";
		echo "</FORM>";
		fin_cadre_relief();	
	}
	else {
		echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3><B>".affdate($date_heure)."&nbsp;</B></FONT><P>";
	}
}



if ($flag_mots AND $options == 'avancees') {
	formulaire_mots('breves', $id_breve, $nouv_mot, $supp_mot, $cherche_mot, $flag_editable);
}

echo justifier(propre($texte))."\n";

if (strlen($lien_url)>7 AND strlen($lien_titre)>2){
	echo "<P><font size=1>VOIR EN LIGNE :</font> <A HREF='$lien_url'><B>".typo($lien_titre)."</B></A>\n";
} else if (strlen($lien_titre)>2) {
	echo "<P><font size=1>NOM DU SITE :</font> ".typo($lien_titre)."</B></A>\n";
} else if (strlen($lien_url)>7) {
	echo "<P><font size=1>URL DU SITE :</font> <tt>$lien_url</tt>\n";
}

if ($les_notes) {
	echo "<hr width='70%' height=1 align='left'><font size=2>$les_notes</font>\n";
}


if ($connect_statut=="0minirezo" AND acces_rubrique($id_rubrique) AND ($statut=="prop" OR $statut=="prepa")){
	echo "<P align='right'>";
	
	
	echo "<A HREF='breves.php3?id_breve=$id_breve&statut=publie' onMouseOver=\"publier_breve.src='IMG2/publier-breve-on.gif'\" onMouseOut=\"publier_breve.src='IMG2/publier-breve-off.gif'\"><img src='IMG2/publier-breve-off.gif' alt='OUI - publier cette breve' width='57' height='56' border='0'  name='publier_breve'></A>";
	echo " &nbsp; <A HREF='breves.php3?id_breve=$id_breve&statut=refuse' onMouseOver=\"refuser_breve.src='IMG2/refuser-breve-on.gif'\" onMouseOut=\"refuser_breve.src='IMG2/refuser-breve-off.gif'\"><img src='IMG2/refuser-breve-off.gif' alt='OUI - refuser cette breve' width='57' height='56' border='0'  name='refuser_breve'></A>";
	
	
}	

echo "</TD></TR></TABLE></TD></TR></TABLE>";

//////////////////////////////////////////////////////
// Forums
//

echo "<BR><BR>";

$forum_retour = urlencode("breves_voir.php3?id_breve=$id_breve");

echo "<P align='right'>";
echo "<A HREF='forum_envoi.php3?statut=prive&adresse_retour=".$forum_retour."&id_breve=$id_breve&titre_message=".urlencode($titre)."' onMouseOver=\"message.src='IMG2/message-on.gif'\" onMouseOut=\"message.src='IMG2/message-off.gif'\">";
echo "<img src='IMG2/message-off.gif' alt='Poster un message' width='51' height='52' border='0' name='message'></A>";
echo "<P align='left'>";


$query_forum = "SELECT * FROM spip_forum WHERE statut='prive' AND id_breve='$id_breve' AND id_parent=0 ORDER BY date_heure DESC LIMIT 0,20";
$result_forum = mysql_query($query_forum);
afficher_forum($result_forum, $forum_retour);






fin_page();

?>
