<?php

include ("inc.php3");
include_ecrire ("inc_logos.php3");
include_ecrire ("inc_mots.php3");
include_ecrire ("inc_sites.php3");

$proposer_sites = lire_meta("proposer_sites");

function calculer_droits() {
	global $connect_statut, $statut, $id_rubrique, $id_rubrique_depart, $proposer_sites, $new;
	global $flag_editable, $flag_administrable;

	$flag_administrable = ($connect_statut == '0minirezo' AND acces_rubrique($id_rubrique));
	if ($id_rubrique_depart > 0)
		 $flag_administrable &= acces_rubrique($id_rubrique_depart);
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
	my_sel("00",_T('mois_non_connu'),$mois);
	my_sel("01",_T('mois_janvier'),$mois);
	my_sel("02",_T('mois_fevrier'),$mois);
	my_sel("03",_T('mois_mars'),$mois);
	my_sel("04",_T('mois_avril'),$mois);
	my_sel("05",_T('mois_mai'),$mois);
	my_sel("06",_T('mois_juin'),$mois);
	my_sel("07",_T('mois_juillet'),$mois);
	my_sel("08",_T('mois_aout'),$mois);
	my_sel("09",_T('mois_septembre'),$mois);
	my_sel("10",_T('mois_octobre'),$mois);
	my_sel("11",_T('mois_novembre'),$mois);
	my_sel("12",_T('mois_decembre'),$mois);
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

	$moderation = (lire_meta("moderation_sites") == "oui")? 'oui' : 'non';

	$query = "INSERT INTO spip_syndic (nom_site, id_rubrique, id_secteur, date, date_syndic, statut, syndication, moderation) ".
		"VALUES ('"._T('avis_site_introuvable')."', $id_rubrique, $id_rubrique, NOW(), NOW(), 'refuse', 'non', '$moderation')";
	$result = spip_query($query);
	$id_syndic = spip_insert_id();
}

$query = "SELECT statut, id_rubrique FROM spip_syndic WHERE id_syndic='$id_syndic'";
$result = spip_query($query);

if ($row = spip_fetch_array($result)) {
	$statut = $row["statut"];
	$id_rubrique_depart = $row["id_rubrique"];
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
	calculer_rubriques();
	if ($statut == 'publie') {
		if (lire_meta('activer_moteur') == 'oui') {
			include_ecrire ("inc_index.php3");
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
			include_ecrire ("inc_index.php3");
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
	$result = spip_query ("SELECT * FROM spip_syndic WHERE id_syndic='$id_syndic' AND syndication IN ('oui', 'sus', 'off')");
	if ($result AND spip_num_rows($result)>0)
		$erreur_syndic = syndic_a_jour ($id_syndic);
}


//
// Afficher la page
//

calculer_droits();

$query = "SELECT * FROM spip_syndic WHERE id_syndic='$id_syndic'";
$result = spip_query($query);

if ($row = spip_fetch_array($result)) {
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
	$titre_page = _T('info_site');



debut_page("$titre_page","documents","sites");


//////// parents


debut_grand_cadre();

afficher_parents($id_rubrique);
$parents="~ <IMG SRC='img_pack/racine-site-24.gif' WIDTH=24 HEIGHT=24 align='middle'> <A HREF='naviguer.php3?coll=0'><B>"._T('lien_racine_site')."</B></A> ".aide ("rubhier")."<BR>".$parents;

$parents=ereg_replace("~","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$parents);
$parents=ereg_replace("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ","",$parents);

echo "$parents";

fin_grand_cadre();



debut_gauche();

debut_boite_info();
	echo "<center>";
	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=1><b>"._T('titre_site_numero')."</b></font>";
	echo "<br><font face='Verdana,Arial,Helvetica,sans-serif' size=6><b>$id_syndic</b></font>";
	echo "</center>";
fin_boite_info();


echo "<p><center>";
	icone (_T('icone_voir_sites_references'), "sites_tous.php3", "site-24.gif","rien.gif");
echo "</center>";

$rubon = "siteon$id_syndic";
$ruboff = "siteoff$id_syndic";

if ($id_syndic>0 AND $flag_administrable)
	afficher_boite_logo($rubon, $ruboff, _T('logo_site').aide ("rublogo"), _T('logo_survol'));


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
	echo "<b>"._T('info_descriptif')."</b> ";
	echo propre($descriptif);
	echo "&nbsp; ";
	echo "</font>";
	echo "</div>";
}
echo "</td>";

if ($flag_editable) {
	$link = new Link('sites_edit.php3');
	$link->addVar('id_syndic');
	$link->addVar('target', $clean_link->getUrl());
	echo "<td><img src='img_pack/rien.gif' width=5></td>\n";
	echo "<td  align='right'>";
	icone(_T('icone_modifier_site'), $link->getUrl(), "site-24.gif", "edit.gif");
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
		echo "<TR><TD BGCOLOR='$couleur_foncee' COLSPAN=2><FONT SIZE=2 COLOR='#FFFFFF'><B>"._T('info_date_referencement');
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
		echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>"._T('info_site_propose')." <B>".affdate($date_heure)."&nbsp;</B></FONT><P>";
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

	echo "<b>"._T('info_statut_site_1')."</b> &nbsp;&nbsp; \n";

	echo "<select name='nouveau_statut' size=1 class='fondl'>\n";

	my_sel("publie",_T('info_statut_site_2'),$statut);
	my_sel("prop",_T('info_statut_site_3'),$statut);
	my_sel("refuse",_T('info_statut_site_4'),$statut);

	echo "</select>\n";

	echo " &nbsp;&nbsp;&nbsp; <input type='submit' name='Valider' value='"._T('bouton_valider')."' class='fondo'>\n</center>\n";
	fin_cadre_relief();
	echo "</form>\n";
}

if ($syndication == "oui" OR $syndication == "off" OR $syndication == "sus") {
	echo "<p><font size=3 face='Verdana,Arial,Helvetica,sans-serif'><b>"._T('info_site_syndique')."</b></font>";

	if ($erreur_syndic)
		echo "<p><font color=red><b>$erreur_syndic</b></font>";

	if ($syndication == "off") {
		debut_boite_info();
		echo _T('avis_site_syndique_probleme_1');
		echo _T('avis_site_syndique_probleme_2');
		echo _T('avis_site_syndique_probleme_3', array('url_syndic' => $url_syndic));
		echo _T('avis_site_syndique_probleme_4')."</font>\n";
		echo "<center><b>";
		echo "<a ".newLinkHref("sites.php3?id_syndic=$id_syndic&recalcul=oui").">";
		echo _T('lien_nouvelle_recuperation')."</a></b></center>\n";
		fin_boite_info();
	}
	afficher_syndic_articles(_T('titre_articles_syndiques'),
		"SELECT * FROM spip_syndic_articles WHERE id_syndic='$id_syndic' ORDER BY date DESC");


	echo "<font face='verdana,arial,helvetica' size=2>";
	// afficher la date de dernier acces a la syndication
	if ($date_syndic)
		echo "<p><div align='left'>"._T('info_derniere_syndication').affdate($date_syndic)
		." &agrave; ".heures($date_syndic)."h ".minutes($date_syndic)."min.</div><div align='right'><a href='sites.php3?id_syndic=$id_syndic&recalcul=oui'>"._T('lien_mise_a_jour_syndication')."</a></div>\n";

	// modifier la moderation
	if ($flag_administrable && $options=='avancees') {
		if ($moderation == 'oui' OR $moderation == 'non')
			spip_query("UPDATE spip_syndic SET moderation='$moderation' WHERE id_syndic=$id_syndic");
		else
			$moderation = $mod;

		if ($moderation == 'non' || $moderation =='')
			echo "<p><div align='left'>"._T('texte_liens_syndication'). aide('artsyn') .
				"</div><div align='right'><a
				href='sites.php3?id_syndic=$id_syndic&moderation=oui'>"._T('info_demander_blocage_priori')."</a></div>\n";
		else if ($moderation == 'oui')
			echo "<p><div align='left'>"._T('texte_demander_blocage_priori'). aide('artsyn') .
				"</div><div align='right'> <a
				href='sites.php3?id_syndic=$id_syndic&moderation=non'>"._T('info_annuler_blocage_priori')."</a></div>\n";
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
icone (_T('icone_poster_message'), $link->getUrl(), "forum-interne-24.gif", "creer.gif");
echo "</div>";

echo "<p align='left'>\n";

$query_forum = "SELECT * FROM spip_forum WHERE statut='prive' AND id_syndic='$id_syndic' AND id_parent=0 ORDER BY date_heure DESC LIMIT 0,20";
$result_forum = spip_query($query_forum);
afficher_forum($result_forum, $forum_retour);


fin_page();

?>
