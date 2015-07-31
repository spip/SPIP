<?php

include ("inc.php3");
include_ecrire ("inc_logos.php3");
include_ecrire ("inc_index.php3");
include_ecrire ("inc_meta.php3");
include_ecrire ("inc_mots.php3");

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
// Creation d'un site
//

if ($new == 'oui' AND ($connect_statut == '0minirezo' OR $proposer_sites > 0)) {
	$id_rubrique = intval($id_rubrique);

	$mydate = date("YmdHis", time() - 12 * 3600);
	$query = "DELETE FROM spip_syndic WHERE (statut = 'refuse') && (maj < $mydate)";
	$result = spip_query($query);

	$query = "INSERT INTO spip_syndic (nom_site, id_rubrique, id_secteur, date, date_syndic, statut, syndication) ".
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
// recalcul
//
if ($recalcul ==  "oui") {
	$result = spip_query ("SELECT * FROM spip_syndic WHERE id_syndic='$id_syndic' AND FIND_IN_SET(syndication, 'oui,off')");
	if ($result AND mysql_num_rows($result)>0)
		$erreur_syndic = syndic_a_jour ($id_syndic);
}


//
// Afficher la page
//

calculer_droits();

$query = "SELECT * FROM spip_syndic WHERE id_syndic='$id_syndic'";
$result = spip_query($query);

if ($row = mysql_fetch_array($result)) {
	$id_syndic = $row["id_syndic"];
	$id_rubrique = $row["id_rubrique"];
	$nom_site = stripslashes($row["nom_site"]);
	$url_site = stripslashes($row["url_site"]);
	$url_syndic = stripslashes($row["url_syndic"]);
	$descriptif = stripslashes($row["descriptif"]);
	$syndication = $row["syndication"];
	$statut = $row["statut"];
	$date_heure = $row["date"];
	$date_syndic = $row['date_syndic'];
	$mod = $row['moderation'];
}


if ($nom_site)
	$titre_page = "&laquo; $nom_site &raquo;";
else
	$titre_page = "Site";



debut_page("$titre_page","documents","sites");


//////// parents


debut_grand_cadre();

afficher_parents($id_rubrique);
$parents="~ <IMG SRC='img_pack/racine-site-24.gif' WIDTH=24 HEIGHT=24 align='middle'> <A HREF='naviguer.php3?coll=0'><B>RACINE DU SITE</B></A> ".aide ("rubhier")."<BR>".$parents;

$parents=ereg_replace("~","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$parents);
$parents=ereg_replace("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ","",$parents);

echo "$parents";

fin_grand_cadre();



debut_gauche();

debut_boite_info();
	echo "<center>";
	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=1><b>SITE NUM&Eacute;RO&nbsp;:</b></font>";
	echo "<br><font face='Verdana,Arial,Helvetica,sans-serif' size=6><b>$id_syndic</b></font>";
	echo "</center>";
fin_boite_info();


echo "<p><center>";
	icone ("Voir les sites r&eacute;f&eacute;renc&eacute;s", "sites_tous.php3", "site-24.gif","rien.gif");
echo "</center>";

$rubon = "siteon$id_syndic";
$ruboff = "siteoff$id_syndic";
$rubon_ok = get_image($rubon);
if ($rubon_ok) $ruboff_ok = get_image($ruboff);

if ($flag_administrable AND ($options == 'avancees' OR $rubon_ok)) {
	debut_cadre_relief();
	afficher_boite_logo($rubon, "LOGO DE CE SITE ".aide ("rublogo"));
	if (($options == 'avancees' AND $rubon_ok) OR $ruboff_ok) {
		echo "<P>";
		afficher_boite_logo($ruboff, "LOGO POUR SURVOL");
	}
	fin_cadre_relief();
}



debut_droite();



debut_cadre_relief("site-24.gif");
echo "<center>";

if ($syndication == 'off') {
	$logo_statut = "puce-orange-anim.gif";
} 
else if ($statut == 'publie') {
	$logo_statut = "puce-verte.gif";
}
else if ($statut == 'prop') {
	$logo_statut = "puce-blanche.gif";
}
else if ($statut == 'refuse') {
	$logo_statut = "puce-rouge.gif";
}

echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr width='100%'><td width='100%' valign='top'>";
	gros_titre($nom_site, $logo_statut);

$url_affichee = $url_site;

if (strlen($url_affichee) > 40) $url_affichee = substr($url_affichee, 0, 30)."...";
echo "<a href='$url_site'><b>$url_affichee</b></a>";

if (strlen($descriptif) > 1) {
	echo "<p><div align='left' style='padding: 5px; border: 1px dashed #aaaaaa; background-color: #e4e4e4;'>";
	echo "<font size=2 face='Verdana,Arial,Helvetica,sans-serif'>";
	echo "<b>Descriptif :</b> ";
	echo propre($descriptif);
	echo "&nbsp; ";
	echo "</font>";
	echo "</div>";
}
echo "</td>";

if ($flag_editable) {
	$link = new Link('sites_edit.php3');
	$link->addVar('id_syndic');
	$link->addVar('target', $this_link->getUrl());
	echo "<td><img src='img_pack/rien.gif' width=5></td>\n";
	echo "<td  align='right'>";
	icone("Modifier ce site", $link->getUrl(), "site-24.gif", "edit.gif");
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
		echo "<FORM ACTION='sites.php3?id_syndic=$id_syndic' METHOD='GET'>";
		echo "<INPUT TYPE='hidden' NAME='id_syndic' VALUE='$id_syndic'>";
		echo "<INPUT NAME='options' TYPE=Hidden VALUE=\"$options\">";
		echo "<TABLE CELLPADDING=5 CELLSPACING=0 BORDER=0 WIDTH=100% BACKGROUND='img_pack/rien.gif'>";
		echo "<TR><TD BGCOLOR='$couleur_foncee' COLSPAN=2><FONT SIZE=2 COLOR='#FFFFFF'><B>DATE DE R&Eacute;F&Eacute;RENCEMENT DE CE SITE&nbsp;:";
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
		echo "<INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='Changer'>";
		echo "</TD></TR></TABLE>";
		echo "</FORM>";
		fin_cadre_enfonce();	
	}
	else {
		echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Site propos&eacute; le : <B>".affdate($date_heure)."&nbsp;</B></FONT><P>";
	}
}

if ($flag_editable AND $options == 'avancees') {
	formulaire_mots('syndic', $id_syndic, $nouv_mot, $supp_mot, $cherche_mot, $flag_editable);
}

if ($flag_administrable) {
	$link = $GLOBALS['clean_link'];
	$link->delVar('new');
	echo $link->getForm('GET');
	debut_cadre_relief("racine-site-24.gif");
	echo "\n<center>";

	echo "<b>Ce site est&nbsp;:</b> &nbsp;&nbsp; \n";

	echo "<select name='nouveau_statut' size=1 class='fondl'>\n";

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

	if ($erreur_syndic)
		echo "<p><font color=red><b>$erreur_syndic</b></font>";

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


	echo "<font face='verdana,arial,helvetica' size=2>";
	// afficher la date de dernier acces a la syndication
	if ($date_syndic)
		echo "<p><div align='left'>La derni&egrave;re syndication de ce site a &eacute;t&eacute; effectu&eacute;e le ".affdate($date_syndic)
		." &agrave; ".heures($date_syndic)."h ".minutes($date_syndic)."min.</div><div align='right'><a href='sites.php3?id_syndic=$id_syndic&recalcul=oui'>Mettre &agrave; jour maintenant</a></div>\n";

	// modifier la moderation
	if ($flag_administrable && $options=='avancees') {
		if ($moderation == 'oui' OR $moderation == 'non')
			spip_query("UPDATE spip_syndic SET moderation='$moderation' WHERE id_syndic=$id_syndic");
		else
			$moderation = $mod;

		if ($moderation == 'non' || $moderation =='')
			echo "<p><div align='left'>Les prochains liens en
				provenance de ce site seront affich&eacute;s imm&eacute;diatement sur le site public.". aide('artsyn') .
				"</div><div align='right'><a
				href='sites.php3?id_syndic=$id_syndic&moderation=oui'>Demander
				un blocage a priori</a></div>\n";
		else if ($moderation == 'oui')
			echo "<p><div align='left'>Les prochains liens en
				provenance de ce site seront bloqu&eacute;s a priori.</div>". aide('artsyn') .
				"<div align='right'> <a
				href='sites.php3?id_syndic=$id_syndic&moderation=non'>Annuler
				ce blocage a priori</a></div>\n";
	}
	echo "</font>";
}

fin_cadre_relief();



//////////////////////////////////////////////////////
// Forums
//

echo "<br><br>\n";

$forum_retour = "sites.php3?id_syndic=$id_syndic";

$link = new Link('forum_envoi.php3');
$link->addVar('statut', 'prive');
$link->addVar('adresse_retour', $forum_retour);
$link->addVar('id_syndic');
$link->addVar('titre_message', $nom_site);


echo "<div align='center'>";
icone ("Poster un message", $link->getUrl(), "forum-interne-24.gif", "creer.gif");
echo "</div>";

echo "<p align='left'>\n";

$query_forum = "SELECT * FROM spip_forum WHERE statut='prive' AND id_syndic='$id_syndic' AND id_parent=0 ORDER BY date_heure DESC LIMIT 0,20";
$result_forum = spip_query($query_forum);
afficher_forum($result_forum, $forum_retour);


fin_page();

?>