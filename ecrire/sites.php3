<?php

include ("inc.php3");
include_local ("inc_logos.php3");
include_local ("inc_index.php3");
include_local ("inc_meta.php3");
include_local ("inc_mots.php3");

$proposer_sites = lire_meta("proposer_sites");

function calculer_droits() {
	global $connect_statut, $statut, $id_rubrique, $proposer_sites, $new;
	global $flag_editable, $flag_administrable;

	$flag_administrable = ($connect_statut == '0minirezo' AND acces_rubrique($id_rubrique));
	$flag_editable = ($flag_administrable OR ($statut == 'prop' AND $proposer_sites > 0) OR $new == 'oui');
}


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






//
// Afficher la hierarchie des rubriques
//

function parent($collection){
	global $parents;
	global $coll;
	$parents=ereg_replace("(~+)","\\1~",$parents);
	if ($collection!=0){	
		$query2="SELECT * FROM spip_rubriques WHERE id_rubrique=\"$collection\"";
		$result2=spip_query($query2);

		while($row=mysql_fetch_array($result2)){
			$id_rubrique = $row[0];
			$id_parent = $row[1];
			$titre = $row[2];
			
			if ($id_rubrique==$coll){
				if (acces_restreint_rubrique($id_rubrique))
					$parents="~ <IMG SRC='IMG2/triangle-anim.gif' WIDTH=16 HEIGHT=14 BORDER=0> <FONT SIZE=4 FACE='Verdana,Arial,Helvetica,sans-serif'><B>".majuscules($titre)."</B></FONT><BR>\n$parents";
				else
					$parents="~ <IMG SRC='IMG2/triangle.gif' WIDTH=16 HEIGHT=14 BORDER=0> <FONT SIZE=4 FACE='Verdana,Arial,Helvetica,sans-serif'><B>".majuscules($titre)."</B></FONT><BR>\n$parents";
			}else{
				if (acces_restreint_rubrique($id_rubrique))
					$parents="~ <IMG SRC='IMG2/triangle-bas-anim.gif' WIDTH=16 HEIGHT=14 BORDER=0> <FONT SIZE=3 FACE='Verdana,Arial,Helvetica,sans-serif'><a href='naviguer.php3?coll=$id_rubrique'>$titre</a></FONT><BR>\n$parents";
				else
					$parents="~ <IMG SRC='IMG2/triangle-bas.gif' WIDTH=16 HEIGHT=14 BORDER=0> <FONT SIZE=3 FACE='Verdana,Arial,Helvetica,sans-serif'><a href='naviguer.php3?coll=$id_rubrique'>$titre</a></FONT><BR>\n$parents";
			}
		}
	parent($id_parent);
	}
}


//
// Creation d'un site
//

if ($new == 'oui' AND ($connect_statut == '0minirezo' OR $proposer_sites > 0)) {
	$id_rubrique = intval($id_rubrique);

	$mydate = date("YmdHis", time() - 12 * 3600);
	$query = "DELETE FROM spip_syndic WHERE (statut = 'refuse') && (maj < $mydate)";
	$result = spip_query($query);

	$query = "INSERT spip_syndic (nom_site, id_rubrique, id_secteur, date, date_syndic, statut, syndication) ".
		"VALUES ('Site introuvable', $id_rubrique, $id_rubrique, NOW(), NOW(), 'refuse', 'non')";
	$result = spip_query($query);
	$id_syndic = mysql_insert_id();
}

$query = "SELECT statut FROM spip_syndic WHERE id_syndic='$id_syndic'";
$result = spip_query($query);

if ($row = mysql_fetch_array($result)) {
	$statut = $row["statut"];
}
if ($new == 'oui') $statut = 'prop';

calculer_droits();


//
// Analyse automatique d'une URL
//

if ($analyser_site == 'oui' AND $flag_editable) {

	$v = analyser_site($url);

	if ($v) {
		$nom_site = addslashes($v['nom_site']);
		$url_site = addslashes($v['url_site']);
		if (!$nom_site) $nom_site = $url_site;
		$url_syndic = addslashes($v['url_syndic']);
		$descriptif = addslashes($v['descriptif']);
		$syndication = $v[syndic] ? 'oui' : 'non';
		$query = "UPDATE spip_syndic ".
			"SET nom_site='$nom_site', url_site='$url_site', url_syndic='$url_syndic', descriptif='$descriptif', syndication='$syndication', statut='$statut' ".
			"WHERE id_syndic=$id_syndic";
		$result = spip_query($query);
		if ($syndication == 'oui') syndic_a_jour($id_syndic);
		$link = new Link('sites.php3');
		$link->addVar('id_syndic');
		$link->addVar('redirect');
		$redirect = $link->getUrl();
		$redirect_ok = 'oui';
	}
}


//
// Ajout et suppression syndication
//

if ($nouveau_statut AND $flag_administrable) {
	$statut = $nouveau_statut;
	$query = "UPDATE spip_syndic SET statut='$statut' WHERE id_syndic='$id_syndic'";
	$result = spip_query($query);
	//if ($statut == 'refuse') $redirect_ok = 'oui';
	if ($statut == 'publie') {
		$query = "UPDATE spip_syndic SET date=NOW() WHERE id_syndic='$id_syndic'";
		$result = spip_query($query);
	}
	calculer_rubriques_publiques();
	if ($statut == 'publie') {
		if (lire_meta('activer_moteur') == 'oui') {
			indexer_syndic($id_syndic);
		}
	}


}

if ($nom_site AND $modifier_site == 'oui' AND $flag_editable) {
	$nom_site = addslashes($nom_site);
	$url_site = addslashes($url_site);
	$descriptif = addslashes($descriptif);
	if (strlen($url_syndic) < 8) $syndication = "non";
	$url_syndic = addslashes($url_syndic);

	$query = "UPDATE spip_syndic SET id_rubrique='$id_rubrique', nom_site='$nom_site', url_site='$url_site', url_syndic='$url_syndic', descriptif='$descriptif', syndication='$syndication', statut='$statut' WHERE id_syndic='$id_syndic'";
	$result = spip_query($query);

	if ($syndication_old != $syndication OR $url_syndic != $old_syndic) {
		$recalcul = "oui";
	}
	if ($syndication_old != $syndication AND $syndication == "non") {
		spip_query("DELETE FROM spip_syndic_articles WHERE id_syndic='$id_syndic'");
	}
	calculer_rubriques_publiques();
	if ($statut == 'publie') {
		if (lire_meta('activer_moteur') == 'oui') {
			indexer_syndic($id_syndic);
		}
	}
	$link = new Link('sites.php3');
	$link->addVar('id_syndic');
	$link->addVar('redirect');
	$redirect = $link->getUrl();
	$redirect_ok = 'oui';
}


if ($jour AND $connect_statut == '0minirezo') {
	if ($annee == "0000") $mois = "00";
	if ($mois == "00") $jour = "00";
	$query = "UPDATE spip_syndic SET date='$annee-$mois-$jour' WHERE id_syndic=$id_syndic";
	$result = spip_query($query);
	calculer_dates_rubriques();
}



if ($redirect AND $redirect_ok == 'oui') {
	@header("Location: $redirect");
}


//
// Afficher la page
//

calculer_droits();

$query = "SELECT * FROM spip_syndic WHERE id_syndic='$id_syndic'";
$result = spip_query($query);

while ($row = mysql_fetch_array($result)) {
	$id_syndic = $row["id_syndic"];
	$id_rubrique = $row["id_rubrique"];
	$nom_site = stripslashes($row["nom_site"]);
	$url_site = stripslashes($row["url_site"]);
	$url_syndic = stripslashes($row["url_syndic"]);
	$descriptif = stripslashes($row["descriptif"]);
	$syndication = $row["syndication"];
	$statut = $row["statut"];
	$date_heure = $row["date"];
}


if ($nom_site)
	$titre_page = "&laquo; $nom_site &raquo;";
else
	$titre_page = "Site";

debut_page($titre_page);
debut_gauche();

debut_boite_info();

echo "<center>";
echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=1><b>SITE NUM&Eacute;RO&nbsp;:</b></font>";
echo "<br><font face='Verdana,Arial,Helvetica,sans-serif' size=6><b>$id_syndic</b></font>";
echo "</center>";

fin_boite_info();


$rubon = "siteon$id_syndic";
$ruboff = "siteoff$id_syndic";
$rubon_ok = get_image($rubon);
if ($rubon_ok) $ruboff_ok = get_image($ruboff);

if ($flag_administrable AND ($options == 'avancees' OR $rubon_ok)) {
	debut_boite_info();
	afficher_boite_logo($rubon, "LOGO DE CE SITE ".aide ("rublogo"));
	if (($options == 'avancees' AND $rubon_ok) OR $ruboff_ok) {
		echo "<P>";
		afficher_boite_logo($ruboff, "LOGO POUR SURVOL");
	}
	fin_boite_info();
}



debut_droite();


parent($id_rubrique);
$parents = "~ <img src='IMG2/triangle-bas.gif' width=16 height=14> " .
	"<a ".newLinkHref('naviguer.php3?coll=0')."><b>RACINE DU SITE</b></a> ".aide ("rubhier")."<br>".$parents;

$parents=ereg_replace("~","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$parents);
$parents=ereg_replace("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ","",$parents);

echo "$parents\n";

echo "<p><table cellpadding=18 cellspacing=0 border=1 width='100%'>";
echo "<tr width='100%'><td bgcolor='#ffffff' align='center' width='100%'>\n";
echo "<center>";
echo "<table width=100% cellpadding=0 cellspacing=0 border=0>";
echo "<tr width='100%'>\n";


//////////////////////////////////////////////////////
// Titre, surtitre, sous-titre
//
echo "<td width='100%'>";

if ($syndication == 'off') {
	echo "<img src='IMG2/puce-orange-anim.gif' alt='X' width='13' height='14' border='0' align='left'>";
} 
else if ($statut == 'publie') {
	echo "<img src='IMG2/puce-verte.gif' alt='X' width='13' height='14' border='0' align='left'>";
}
else if ($statut == 'prop') {
	echo "<img src='IMG2/puce-blanche.gif' alt='X' width='13' height='14' border='0' align='left'>";
}
else if ($statut == 'refuse') {
	echo "<img src='IMG2/puce-rouge.gif' alt='X' width='13' height='14' border='0' align='left'>";
}

if ($flag_editable) {
	$link = new Link('sites_edit.php3');
	$link->addVar('id_syndic');
	$link->addVar('target', $this_link->getUrl());
	echo "<table cellpadding=0 cellspacing=0 border=0 align='right'><tr>";
	echo "<td valign='bottom' align='center'>";
	echo "<a ".$link->getHref()."
		onMouseOver=\"modifier_site.src='IMG2/modifier-site-on.gif'\"
		onMouseOut=\"modifier_site.src='IMG2/modifier-site-off.gif'\"
		class='boutonlien'>";
	echo "<img src='IMG2/modifier-site-off.gif' alt='Modifier ce site' width='58' height='34' border='0' name='modifier_site'>";
	echo "<br>Modifier<br>ce site</A></td>";
	echo "</tr></table>";
}


echo "<center><font face='Verdana,Arial,Helvetica,sans-serif' size=4><b>";
echo typo($nom_site);
echo "</b></font></center><br>\n";
if (strlen($url_site) > 40) $url_site = substr($url_site, 0, 30)."...";
echo "<center><font face='Verdana,Arial,Helvetica,sans-serif'><font size=3>";
echo "<a href='$url_site'><b>$url_site</b></a></font></font></center>";

// Verifier si doublons...
$query_meme = "SELECT * FROM spip_syndic WHERE statut = 'publie' ".
	"AND id_syndic!='$id_syndic' AND (url_site='$url_site' OR (syndication='oui' AND url_syndic='$url_syndic'))";

afficher_sites("Attention : vous avez d&eacute;j&agrave; r&eacute;f&eacute;renc&eacute; un site ayant la m&ecirc;me adresse", $query_meme);

if (strlen($descriptif) > 1) {
	echo "<p align='left'>";
	debut_boite_info();

	echo "<img src='IMG2/descriptif.gif' alt='DESCRIPTIF' width='59' height='12' border='0'><BR>";
	echo "<FONT SIZE=3 FACE='Verdana,Arial,Helvetica,sans-serif'>";
	echo propre($descriptif);
	echo "&nbsp; ";
	echo "</FONT>";
	fin_boite_info();
}




if ($flag_editable AND ($options == 'avancees' OR $statut == 'publie')) {

	if ($statut == 'publie') {	
		echo "<p>";

		if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})", $date_heure, $regs)) {
		        $mois = $regs[2];
		        $jour = $regs[3];
		        $annee = $regs[1];
		}


		debut_cadre_relief();
		echo "<FORM ACTION='sites.php3?id_syndic=$id_syndic' METHOD='GET'>";
		echo "<INPUT TYPE='hidden' NAME='id_syndic' VALUE='$id_syndic'>";
		echo "<INPUT NAME='options' TYPE=Hidden VALUE=\"$options\">";
		echo "<TABLE CELLPADDING=5 CELLSPACING=0 BORDER=0 WIDTH=100% BACKGROUND='IMG2/rien.gif'>";
		echo "<TR><TD BGCOLOR='$couleur_foncee' COLSPAN=2><FONT SIZE=2 COLOR='#FFFFFF'><B>DATE DE R&Eacute;F&Eacute;RENCEMENT DE CE SITE&nbsp;:";
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
		echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Site propos&eacute; le : <B>".affdate($date_heure)."&nbsp;</B></FONT><P>";
	}
}

if ($flag_editable AND $options == 'avancees') {
	echo "<p>";
	formulaire_mots('syndic', $id_syndic, $nouv_mot, $supp_mot, $cherche_mot, $flag_editable);
}

if ($flag_administrable) {
	$link = new Link();
	$link->delVar('new');
	echo $link->getForm('GET');
	debut_cadre_relief();
	echo "\n<center>";

	echo "<b>Ce site est&nbsp;:</b> &nbsp;&nbsp; \n";

	echo "<select name='nouveau_statut' size=1 class='fondl'>\n";
	/*
	echo "<option".mySel("publie", $statut).">Publi&eacute;\n";
	echo "<option".mySel("prop", $statut).">Propos&eacute;\n";
	echo "<option".mySel("refuse", $statut).">A la poubelle\n";
	*/

	my_sel("publie","Publi&eacute;",$statut);
	my_sel("prop","Propos&eacute;",$statut);
	my_sel("refuse","A la poubelle",$statut);

	echo "</select>\n";

	echo " &nbsp;&nbsp;&nbsp; <input type='submit' name='Valider' value='Valider' class='fondo'>\n</center>\n";
	fin_cadre_relief();
	echo "</form>\n";
}

if ($syndication == "oui" OR $syndication == "off") {
	echo "<p><font size=3 face='Verdana,Arial,Helvetica,sans-serif'><b>Ce site est syndiqu&eacute;...</b></font>";
	if ($recalcul ==  "oui") {
		syndic_a_jour($id_syndic, true);
	}
	if ($syndication == "off") {
		debut_boite_info();
		echo "Attention : la syndication de ce site a rencontr&eacute; un probl&egrave;me&nbsp;; ";
		echo "le syst&egrave;me est donc temporairement interrompu pour l'instant. V&eacute;rifiez ";
		echo "l'adresse du fichier de syndication de ce site (<b>$url_syndic</b>), et tentez une nouvelle ";
		echo "r&eacute;cup&eacute;ration des informations.</font>\n";
		echo "<center><b>";
		echo "<a ".newLinkHref("sites.php3?id_syndic=$id_syndic&recalcul=oui").">";
		echo "Tenter une nouvelle r&eacute;cup&eacute;ration des donn&eacute;es</a></b></center>\n";
		fin_boite_info();
	}
	afficher_syndic_articles("Articles syndiqu&eacute;s tir&eacute;s de ce site",
		"SELECT * FROM spip_syndic_articles WHERE id_syndic='$id_syndic' ORDER BY date DESC");
}


echo "</td>";

echo "</tr></table>";
echo "</td></tr></table>\n";



//////////////////////////////////////////////////////
// Forums
//

echo "<br><br>\n";

$forum_retour = "sites.php3?id_syndic=$id_syndic";

echo "<p align='right'>";
$link = new Link('forum_envoi.php3');
$link->addVar('statut', 'prive');
$link->addVar('adresse_retour', $forum_retour);
$link->addVar('id_syndic');
$link->addVar('titre_message', $nom_site);

echo "<a ".$link->getHref()." onMouseOver=\"message.src='IMG2/message-on.gif'\" onMouseOut=\"message.src='IMG2/message-off.gif'\">";
echo "<img src='IMG2/message-off.gif' alt='Poster un message' width='51' height='52' border='0' name='message'></a>\n";
echo "<p align='left'>\n";

$query_forum = "SELECT * FROM spip_forum WHERE statut='prive' AND id_syndic='$id_syndic' AND id_parent=0 ORDER BY date_heure DESC LIMIT 0,20";
$result_forum = spip_query($query_forum);
afficher_forum($result_forum, $forum_retour);


fin_page();

?>