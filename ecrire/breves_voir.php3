<?php

include ("inc.php3");
include_ecrire ("inc_logos.php3");
include_ecrire ("inc_mots.php3");

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
	my_sel("00",_T('mois_non_connu'),$mois);
	my_sel("01",_T('date_mois_1'),$mois);
	my_sel("02",_T('date_mois_2'),$mois);
	my_sel("03",_T('date_mois_3'),$mois);
	my_sel("04",_T('date_mois_4'),$mois);
	my_sel("05",_T('date_mois_5'),$mois);
	my_sel("06",_T('date_mois_6'),$mois);
	my_sel("07",_T('date_mois_7'),$mois);
	my_sel("08",_T('date_mois_8'),$mois);
	my_sel("09",_T('date_mois_9'),$mois);
	my_sel("10",_T('date_mois_10'),$mois);
	my_sel("11",_T('date_mois_11'),$mois);
	my_sel("12",_T('date_mois_12'),$mois);
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
	my_sel("00",_T('jour_non_connu_nc'),$jour);
	for($i=1;$i<32;$i++){
		if ($i<10){$aff="&nbsp;".$i;}else{$aff=$i;}
		my_sel($i,$aff,$jour);
	}
}

if (($id_breve == 0) AND ($new == "oui")) {
	$query="INSERT INTO spip_breves (titre, date_heure, id_rubrique, statut) VALUES ('"._T('item_nouvelle_breve')."', NOW(), '$id_rubrique', 'refuse')";
	$result=spip_query($query);
	$id_breve=spip_insert_id();
}


$clean_link = new Link("breves_voir.php3?id_breve=$id_breve");


if ($titre AND $modifier_breve) {
	$titre = addslashes($titre);
	$texte = addslashes($texte);
	$lien_titre = addslashes($lien_titre);
	$query = "UPDATE spip_breves SET titre=\"$titre\", texte=\"$texte\", lien_titre=\"$lien_titre\", lien_url=\"$lien_url\", statut=\"$statut\", id_rubrique=\"$id_rubrique\" WHERE id_breve=$id_breve";
	$result = spip_query($query);
	if (lire_meta('activer_moteur') == 'oui') {
		include_ecrire ("inc_index.php3");
		indexer_breve($id_breve);
	}
	calculer_rubriques();
}


if ($jour AND $connect_statut == '0minirezo') {
	if ($annee == "0000") $mois = "00";
	if ($mois == "00") $jour = "00";
	$query = "UPDATE spip_breves SET date_heure='$annee-$mois-$jour' WHERE id_breve=$id_breve";
	$result = spip_query($query);
	calculer_dates_rubriques();
}


$query = "SELECT * FROM spip_breves WHERE id_breve='$id_breve'";
$result = spip_query($query);

while ($row = spip_fetch_array($result)) {
	$id_breve=$row['id_breve'];
	$date_heure=$row['date_heure'];
	$titre_breve=$row['titre'];
	$titre=$row['titre'];
	$texte=$row['texte'];
	$lien_titre=$row['lien_titre'];
	$lien_url=$row['lien_url'];
	$statut=$row['statut'];
	$id_rubrique=$row['id_rubrique'];
}

$flag_editable = (($connect_statut == '0minirezo' AND acces_rubrique($id_rubrique)) OR $statut == 'prop');



debut_page("&laquo; $titre_breve &raquo;", "documents", "breves");


debut_grand_cadre();

afficher_parents($id_rubrique);
$parents="~ <img src='img_pack/racine-site-24.gif' width=24 height=24 align='middle'> <A HREF='naviguer.php3?coll=0'><B>"._T('lien_racine_site')."</B></A> ".aide ("rubhier")."<BR>".$parents;

$parents=ereg_replace("~","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$parents);
$parents=ereg_replace("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ","",$parents);

echo "$parents";

fin_grand_cadre();


debut_gauche();


debut_boite_info();

echo "<CENTER>";
echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=1><B>"._T('info_gauche_numero_breve')."&nbsp;:</B></FONT>";
echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=6><B>$id_breve</B></FONT>";
echo "</CENTER>";
if ($statut == 'publie') {
	icone_horizontale(_T('icone_voir_en_ligne'), "../spip_redirect.php3?id_breve=$id_breve&recalcul=oui", "racine-24.gif", "rien.gif");
}

fin_boite_info();


//////////////////////////////////////////////////////
// Logos de la breve
//

$arton = "breveon$id_breve";
$artoff = "breveoff$id_breve";

if ($id_breve>0 AND ($connect_statut == '0minirezo' AND acces_rubrique($id_rubrique)))
	afficher_boite_logo($arton, $artoff, _T('logo_breve').aide ("breveslogo"), _T('logo_survol'));

debut_raccourcis();
icone_horizontale(_T('icone_nouvelle_breve'), "breves_edit.php3?new=oui", "breve-24.gif","creer.gif");
fin_raccourcis();

debut_droite();

debut_cadre_relief("breve-24.gif");
echo "<TABLE WIDTH=100% CELLPADDING=0 CELLSPACING=0 BORDER=0>";
echo "<TR><td>";

echo "<font face='Georgia,Garamond,Times,serif'>";




function enfant($leparent){
	global $id_parent;
	global $id_rubrique;
	global $i;
	
	$i++;
 	$query="SELECT * FROM spip_rubriques WHERE id_parent='$leparent' ORDER BY titre";
 	$result=spip_query($query);

	while($row=spip_fetch_array($result)){
		$my_rubrique=$row['id_rubrique'];
		$titre=$row['titre'];
		echo "<OPTION".mySel($my_rubrique,$id_rubrique).">$titre\n";
	}
	$i=$i-1;

}


echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr width='100%'><td width='100%' valign='top'>";
gros_titre($titre);
echo "</td>";

if ($flag_editable) {
	echo "<td><img src='img_pack/rien.gif' width=5></td>\n";
	echo "<td  align='right'>";
	icone(_T('icone_modifier_breve'), "breves_edit.php3?id_breve=$id_breve&retour=nav", "breve-24.gif", "edit.gif");
	echo "</td>";
}
echo "</tr></table>\n";

if ($flag_editable AND ($options == 'avancees' OR $statut == 'publie')) {

	if ($statut == 'publie') {	
		echo "<p>";

		if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})", $date_heure, $regs)) {
		        $mois = $regs[2];
		        $jour = $regs[3];
		        $annee = $regs[1];
		}


		debut_cadre_enfonce();
		echo "<FORM ACTION='breves_voir.php3?id_breve=$id_breve' METHOD='GET'>";
		echo "<INPUT TYPE='hidden' NAME='id_breve' VALUE='$id_breve'>";
		echo "<INPUT NAME='options' TYPE=Hidden VALUE=\"$options\">";
		echo "<TABLE CELLPADDING=5 CELLSPACING=0 BORDER=0 WIDTH=100% BACKGROUND='img_pack/rien.gif'>";
		echo "<TR><TD BGCOLOR='$couleur_foncee' COLSPAN=2><FONT SIZE=2 COLOR='#FFFFFF'><B>"._T('texte_date_publication_article');
		//echo aide ("artdate");
		echo "</B></FONT></TR>";
		echo "<TR><TD ALIGN='center'>";
		echo "<SELECT NAME='jour' SIZE=1 CLASS='fondl'>";
		afficher_jour($jour);
		echo "</SELECT> ";
		echo "<SELECT NAME='mois' SIZE=1 CLASS='fondl'>";
		afficher_mois($mois);
		echo "</SELECT> ";
		echo "<SELECT NAME='annee' SIZE=1 CLASS='fondl'>";
		afficher_annee($annee);
		echo "</SELECT>";
 		
		echo "</TD><TD ALIGN='right'>";
		echo "<INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='"._T('bouton_changer')."'>";
		echo "</TD></TR></TABLE>";
		echo "</FORM>";
		fin_cadre_enfonce();	
	}
	else {
		echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3><B>".affdate($date_heure)."&nbsp;</B></FONT><P>";
	}
}



if ($flag_mots!='non' AND $flag_editable AND $options == 'avancees') {
	formulaire_mots('breves', $id_breve, $nouv_mot, $supp_mot, $cherche_mot, $flag_editable);
}

echo justifier(propre($texte))."\n";

if (strlen($lien_url)>7 AND strlen($lien_titre)>2){
	echo "<P><font size=1>"._T('lien_voir_en_ligne')."</font> <A HREF='$lien_url'><B>".typo($lien_titre)."</B></A>\n";
} else if (strlen($lien_titre)>2) {
	echo "<P><font size=1>"._T('lien_nom_site')."</font> ".typo($lien_titre)."</B></A>\n";
} else if (strlen($lien_url)>7) {
	echo "<P><font size=1>"._T('info_url_site')."</font> <tt>$lien_url</tt>\n";
}

if ($les_notes) {
	echo "<hr width='70%' height=1 align='left'><font size=2>$les_notes</font>\n";
}


if ($connect_statut=="0minirezo" AND acces_rubrique($id_rubrique) AND ($statut=="prop" OR $statut=="prepa")){
	echo "<div align='right'>";
	
	echo "<table>";
	echo "<td  align='right'>";
	icone(_T('icone_publier_breve'), "breves.php3?id_breve=$id_breve&statut=publie", "breve-24.gif", "racine-24.gif");
	echo "</td>";
	
	echo "<td><img src='img_pack/rien.gif' width=5></td>\n";
	echo "<td  align='right'>";
	icone(_T('icone_refuser_breve'), "breves.php3?id_breve=$id_breve&statut=refuse", "breve-24.gif", "supprimer.gif");
	echo "</td>";
	

	echo "</table>";	
	echo "</div>";
	
}	

echo "</TD></TR></TABLE>";

fin_cadre_relief();

//////////////////////////////////////////////////////
// Forums
//

echo "<BR><BR>";

$forum_retour = urlencode("breves_voir.php3?id_breve=$id_breve");



echo "\n<div align='center'>";
	icone(_T('icone_poster_message'), "forum_envoi.php3?statut=prive&adresse_retour=".$forum_retour."&id_breve=$id_breve&titre_message=".urlencode($titre), "forum-interne-24.gif", "creer.gif");
echo "</div>";


echo "<P align='left'>";


$query_forum = "SELECT * FROM spip_forum WHERE statut='prive' AND id_breve='$id_breve' AND id_parent=0 ORDER BY date_heure DESC LIMIT 0,20";
$result_forum = spip_query($query_forum);
afficher_forum($result_forum, $forum_retour);






fin_page();

?>
