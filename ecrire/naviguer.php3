<?php

include ("inc.php3");
include_ecrire ("inc_logos.php3");
include_ecrire ("inc_index.php3");
include_ecrire ("inc_meta.php3");
include_ecrire ("inc_mots.php3");
include_ecrire ("inc_documents.php3");


$coll = intval($coll);
$flag_mots = lire_meta("articles_mots");


function enfant($collection){
	global $les_enfants, $couleur_foncee;
	$query2 = "SELECT * FROM spip_rubriques WHERE id_parent=\"$collection\" ORDER BY titre";
	$result2 = spip_query($query2);
	
	while($row=mysql_fetch_array($result2)){
		$id_rubrique=$row['id_rubrique'];
		$id_parent=$row['id_parent'];
		$titre=$row['titre'];
		$descriptif=propre($row['descriptif']);
	
		$bouton_layer = bouton_block_invisible("enfants$id_rubrique");
		$les_sous_enfants = sous_enfant($id_rubrique);

		$les_enfants.= "<P>";
		if ($id_parent == "0") $les_enfants .= debut_cadre_relief("secteur-24.gif", true);
		else  $les_enfants .= debut_cadre_relief("rubrique-24.gif", true);
		$les_enfants.= "<FONT FACE=\"Verdana,Arial,Helvetica,sans-serif\">";

		if (strlen($les_sous_enfants) > 0){
			$les_enfants.= $bouton_layer;
		}
		if  (acces_restreint_rubrique($id_rubrique)){
			$les_enfants.= "<B><A HREF='naviguer.php3?coll=$id_rubrique'><font color='red'>".typo($titre)."</font></A></B>";
		}else{
			$les_enfants.= "<B><A HREF='naviguer.php3?coll=$id_rubrique'><font color='$couleur_foncee'>".typo($titre)."</font></A></B>";
		}
		if (strlen($descriptif)>1)
			$les_enfants.="<BR><FONT SIZE=1>$descriptif</FONT>";

		$les_enfants.= "</FONT>";

		$les_enfants .= $les_sous_enfants;
		$les_enfants .= fin_cadre_relief(true);
	}
}

function sous_enfant($collection2){
	$query3 = "SELECT * FROM spip_rubriques WHERE id_parent=\"$collection2\" ORDER BY titre";
	$result3 = spip_query($query3);

	if (mysql_num_rows($result3) > 0){
		$retour = debut_block_invisible("enfants$collection2")."\n\n<FONT SIZE=1 face='arial,helvetica,sans-serif'><ul style='list-style-image: url(img_pack/rubrique-12.gif)'>";
		while($row=mysql_fetch_array($result3)){
			$id_rubrique2=$row['id_rubrique'];
			$id_parent2=$row['id_parent'];
			$titre2=$row['titre'];
			
			$retour.="<LI><A HREF='naviguer.php3?coll=$id_rubrique2'>$titre2</A>\n";
		}
		$retour .= "</FONT></ul>\n\n".fin_block()."\n\n";
	}
	
	return $retour;
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
// Gerer les modifications...
//

if ($modifier_rubrique == "oui") {
	calculer_rubriques_publiques();
}

if ($titre) {
	$id_parent = intval($id_parent);

	// creation, le cas echeant
	if ($new == 'oui' AND !$coll) {
		$query = "INSERT INTO spip_rubriques (titre, id_parent) VALUES ('Nouvelle rubrique', '$id_parent')";
		$result = spip_query($query);
		$coll = mysql_insert_id();
	}

	// si c'est une rubrique-secteur contenant des breves, ne deplacer
	// que si $confirme_deplace == 'oui'
	$query = "SELECT COUNT(*) AS cnt FROM spip_breves WHERE id_rubrique=\"$coll\"";
	$row = mysql_fetch_array(spip_query($query));
	if (($row['cnt'] > 0) and !($confirme_deplace == 'oui')) {
		$id_parent = 0;
	}

	// verifier qu'on envoie bien dans une rubrique autorisee
	if (acces_rubrique($id_parent)) {
		$change_parent = "id_parent=\"$id_parent\",";
	}
	else {
		$change_parent = "";
	}

	$titre = addslashes($titre);
	$descriptif = addslashes($descriptif);
	$texte = addslashes($texte);
	$query = "UPDATE spip_rubriques SET $change_parent titre=\"$titre\", descriptif=\"$descriptif\", texte=\"$texte\" WHERE id_rubrique=$coll";
	$result = spip_query($query);
	
	calculer_rubriques();

	if (lire_meta('activer_moteur') == 'oui') {
		indexer_rubrique($coll);
	}
}


//
// infos sur cette rubrique
//

$query="SELECT * FROM spip_rubriques WHERE id_rubrique='$coll'";
$result=spip_query($query);

while($row=mysql_fetch_array($result)){
	$id_rubrique=$row['id_rubrique'];
	$id_parent=$row['id_parent'];
	$titre=$row['titre'];
	$descriptif=$row['descriptif'];
	$texte=$row['texte'];
	$statut = $row['statut'];
}

if ($titre)
	$titre_page = "&laquo; ".textebrut($titre)." &raquo;";
else
	$titre_page = "Naviguer dans le site...";


if ($id_document) {
	$query_doc = "SELECT * FROM spip_documents_rubriques WHERE id_document=$id_document AND id_rubrique=$coll";
	$result_doc = spip_query($query_doc);
	$flag_document_editable = (mysql_num_rows($result_doc) > 0);
} else {
	$flag_document_editable = false;
}


$modif_document = $GLOBALS['modif_document'];
if ($modif_document == 'oui' AND $flag_document_editable) {
	$titre_document = addslashes(corriger_caracteres($titre_document));
	$descriptif_document = addslashes(corriger_caracteres($descriptif_document));

	$query = "UPDATE spip_documents SET titre=\"$titre_document\", descriptif=\"$descriptif_document\"";
	if ($largeur_document AND $hauteur_document) $query .= ", largeur='$largeur_document', hauteur='$hauteur_document'";
	$query .= " WHERE id_document=$id_document";
	spip_query($query);

	if ($jour_doc AND $connect_statut == '0minirezo') {
		if ($annee_doc == "0000") $mois_doc = "00";
		if ($mois_doc == "00") $jour_doc = "00";
		$query = "UPDATE spip_documents SET date='$annee_doc-$mois_doc-$jour_doc' WHERE id_document=$id_document";
		$result = spip_query($query);
		calculer_dates_rubriques();
	}

}
		




///// debut de la page
debut_page($titre_page, "documents", "rubriques");


//////// parents


debut_grand_cadre();

if ($coll  > 0) {
	afficher_parents($id_parent);
	$parents="~ <IMG SRC='img_pack/racine-site-24.gif' WIDTH=24 HEIGHT=24 align='middle'> <A HREF='naviguer.php3?coll=0'><b><font color='$couleur_foncee'>RACINE DU SITE</font></b></A> ".aide ("rubhier")."<BR>".$parents;

	$parents=ereg_replace("~","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$parents);
	$parents=ereg_replace("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ","",$parents);

	echo "$parents";
}

fin_grand_cadre();



debut_gauche();

if ($coll > 0) {
	debut_boite_info();
	echo "<CENTER>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=1><B>RUBRIQUE NUM&Eacute;RO&nbsp;:</B></FONT>";
	echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=6><B>$coll</B></FONT>";
	echo "</CENTER>";
	
	
		
	if ($coll >0 AND $statut == 'publie') {
		icone_horizontale("Voir en ligne", "../spip_redirect.php3?id_rubrique=$coll&recalcul=oui", "racine-24.gif", "rien.gif");
	}
	
	
	
	fin_boite_info();
}

$rubon = "rubon$coll";
$ruboff = "ruboff$coll";
$rubon_ok = get_image($rubon);
if ($rubon_ok) $ruboff_ok = get_image($ruboff);

if ($connect_statut == '0minirezo' AND acces_rubrique($coll) AND ($options == 'avancees' OR $rubon_ok) AND tester_upload()) {

	debut_boite_info();

	afficher_boite_logo($rubon, "LOGO DE LA RUBRIQUE ".aide ("rublogo"));

	if (($options == 'avancees' AND $rubon_ok) OR $ruboff_ok) {
		echo "<P>";
		afficher_boite_logo($ruboff, "LOGO POUR SURVOL");
	}

	fin_boite_info();
}




//
// Afficher les boutons de creation d'article et de breve
//
debut_raccourcis();

$query = "SELECT id_rubrique FROM spip_rubriques LIMIT 0,1";
$result = spip_query($query);

icone_horizontale("Tous vos articles", "articles_page.php3", "article-24.gif");
echo "<p>";


if (mysql_num_rows($result) > 0) {
	if ($coll > 0)
		icone_horizontale("&Eacute;crire un nouvel article", "articles_edit.php3?id_rubrique=$coll&new=oui", "article-24.gif","creer.gif");

	$activer_breves = lire_meta("activer_breves");
	if ($activer_breves != "non" AND $id_parent == "0") {
		icone_horizontale("&Eacute;crire une nouvelle br&egrave;ve", "breves_edit.php3?id_rubrique=$coll&new=oui", "breve-24.gif","creer.gif");
	}
}
else {
	if ($connect_statut == '0minirezo') {
		echo "<p>Avant de pouvoir &eacute;crire des articles,<BR> vous devez cr&eacute;er au moins une rubrique.<BR>";
	}
}
if ($connect_statut == '0minirezo' AND acces_rubrique($coll)) {
	icone_horizontale("Cr&eacute;er une sous-rubrique", "rubriques_edit.php3?new=oui&retour=nav&id_parent=$coll", "rubrique-24.gif","creer.gif");
}

fin_raccourcis();




debut_droite();
///// Editable ?
$flag_editable = ($connect_statut == '0minirezo' AND acces_rubrique($coll));


if ($coll == 0) $titre = "Racine du site";

if ($coll ==  0) $ze_logo = "racine-site-24.gif";
else if ($id_parent == 0) $ze_logo = "secteur-24.gif";
else $ze_logo = "rubrique-24.gif";


debut_cadre_relief($ze_logo);

echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr width='100%'><td width='100%' valign='top'>";
gros_titre($titre);


if (strlen($descriptif) > 1) {
	echo "<p><div align='left' style='padding: 5px; border: 1px dashed #aaaaaa;'>";
	echo "<font size=2 face='Verdana,Arial,Helvetica,sans-serif'>";
	echo "<b>Descriptif :</b> ";
	echo propre($descriptif);
	echo "&nbsp; ";
	echo "</font>";
	echo "</div>";
}



echo "</td>";




if ($coll > 0 AND $connect_statut == '0minirezo' AND acces_rubrique($coll)) {
	echo "<td><img src='img_pack/rien.gif' width=5></td>\n";
	echo "<td  align='right'>";
	icone("Modifier cette rubrique", "rubriques_edit.php3?id_rubrique=$id_rubrique&retour=nav", $ze_logo, "edit.gif");
	echo "</td>";
}
echo "</tr></table>\n";


/// Mots-cles
if ($flag_mots!= 'non' AND $connect_statut == '0minirezo' AND acces_rubrique($coll) AND $options == 'avancees' AND $coll > 0) {
	echo "\n<p>";
	formulaire_mots('rubriques', $coll, $nouv_mot, $supp_mot, $cherche_mot, $flag_editable);
}



if (strlen($texte) > 1) {
	echo "\n<p><font size=3 face='Verdana,Arial,Helvetica,sans-serif'><div align='justify'>";
	echo justifier(propre($texte));
	echo "&nbsp;</font></div>";
}

fin_cadre_relief();







echo "<DIV align=left>";
enfant($coll);


$les_enfants2=substr($les_enfants,round(strlen($les_enfants)/2),strlen($les_enfants));
if (strpos($les_enfants2,"<P>")){
	$les_enfants2=substr($les_enfants2,strpos($les_enfants2,"<P>"),strlen($les_enfants2));
	$les_enfants1=substr($les_enfants,0,strlen($les_enfants)-strlen($les_enfants2));
}else{
	$les_enfants1=$les_enfants;
	$les_enfants2="";
}


// Afficher les sous-rubriques
	echo "<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
	echo "<tr><td valign='top' width=50% rowspan=2>$les_enfants1</td>";
	echo "<td width=20 rowspan=2><img src='img_pack/rien.gif' width=20></td>";
	echo "<td valign='top' width=50%>$les_enfants2 &nbsp;";
	if (strlen($les_enfants2) > 0) echo "<p>";
	echo "</td></tr>";
	
	echo "<tr><td align='right' valign='bottom'>";
	if ($connect_statut == '0minirezo' AND acces_rubrique($coll)) {
	if ($coll == "0") icone("Cr&eacute;er une rubrique", "rubriques_edit.php3?new=oui&retour=nav&id_parent=$id_rubrique", "secteur-24.gif", "creer.gif");
	else  icone("Cr&eacute;er une sous-rubrique", "rubriques_edit.php3?new=oui&retour=nav&id_parent=$id_rubrique", "rubrique-24.gif", "creer.gif");
	echo "<p>";
	}
	echo "</td></tr>";
	echo "</table>";


echo "<DIV align='left'>";


//////////  Vos articles en cours de redaction
/////////////////////////

echo "<P>";
afficher_articles("Vos articles en cours de r&eacute;daction",
"SELECT articles.id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
"FROM spip_articles AS articles, spip_auteurs_articles AS lien ".
"WHERE articles.id_article=lien.id_article AND id_rubrique='$coll' ".
"AND lien.id_auteur=\"$connect_id_auteur\" AND articles.statut=\"prepa\" ORDER BY articles.date DESC");


//////////  Les articles a valider
/////////////////////////

afficher_articles("Les articles &agrave; valider",
"SELECT id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
"FROM spip_articles WHERE statut=\"prop\" AND id_rubrique='$coll' ORDER BY date DESC");


//////////  Les articles en cours de redaction
/////////////////////////

if ($connect_statut == "0minirezo" AND $options == 'avancees') {
	afficher_articles("Tous les articles en cours de r&eacute;daction",
	"SELECT id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
	"FROM spip_articles WHERE statut=\"prepa\" AND id_rubrique='$coll' ORDER BY date DESC");
}


//////////  Les articles publies
/////////////////////////

afficher_articles("Tous les articles publi&eacute;s dans cette rubrique",
"SELECT id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
"FROM spip_articles WHERE statut=\"publie\" AND id_rubrique='$coll' ORDER BY date DESC");

if ($coll > 0){
	echo "<div align='right'>";
	icone("&Eacute;crire un nouvel article", "articles_edit.php3?id_rubrique=$coll&new=oui", "article-24.gif", "creer.gif");
	echo "</div><p>";
}

//// Les breves

afficher_breves("Les br&egrave;ves contenues dans cette rubrique", "SELECT * FROM spip_breves WHERE id_rubrique='$coll' ORDER BY date_heure DESC");

$activer_breves=lire_meta("activer_breves");

if ($id_parent == "0" AND $activer_breves!="non"){
	echo "<div align='right'>";
	icone("&Eacute;crire une nouvelle br&egrave;ve", "breves_edit.php3?id_rubrique=$coll&new=oui", "breve-24.gif", "creer.gif");
	echo "</div><p>";
}



//// Les sites references

afficher_sites("Les sites r&eacute;f&eacute;renc&eacute;s dans cette rubrique", "SELECT * FROM spip_syndic WHERE id_rubrique='$coll' AND statut!='refuse' ORDER BY nom_site");

if ($options == "avancees"){
	$proposer_sites=lire_meta("proposer_sites");
	if ($coll > 0 AND ($connect_statut == '0minirezo' OR $proposer_sites > 0)) {
		$link = new Link('sites_edit.php3');
		$link->addVar('id_rubrique', $coll);
		$link->addVar('target', 'sites.php3');
		$link->addVar('redirect', $this_link->getUrl());
	
		echo "<div align='right'>";
		icone("R&eacute;f&eacute;rencer un site", $link->getUrl(), "site-24.gif", "creer.gif");
		echo "</div><p>";
	}
}


/// Documents associes a la rubrique

if ($coll>0){
	afficher_documents_non_inclus($coll, "rubrique", $flag_editable);
}




////// Supprimer cette rubrique (si vide)

if (tester_rubrique_vide($coll)) {
	$link = new Link('naviguer.php3');
	$link->addVar('coll', $id_parent);
	$link->addVar('supp_rubrique', $coll);

	echo "<p><div align='center'>";
	icone("Supprimer cette rubrique", $link->getUrl(), "$ze_logo", "supprimer.gif");
	echo "</div><p>";


}



fin_page();

?>
