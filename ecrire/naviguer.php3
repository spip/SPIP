<?php

include ("inc.php3");
include_local ("inc_logos.php3");
include_local ("inc_index.php3");
include_local ("inc_meta.php3");
include_local ("inc_mots.php3");


if (!$coll) $coll=0;
$flag_mots = lire_meta("articles_mots");


function enfant($collection){
	global $les_enfants;
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
		$les_enfants.= "<FONT FACE=\"verdana,arial,helvetica,sans-serif\">";

		if (strlen($les_sous_enfants) > 0){
			$les_enfants.= $bouton_layer;
		}
		if  (acces_restreint_rubrique($id_rubrique)){
			$les_enfants.= "<B><A HREF='naviguer.php3?coll=$id_rubrique'><font color='red'>".typo($titre)."</font></A></B>";
		}else{
			$les_enfants.= "<B><A HREF='naviguer.php3?coll=$id_rubrique'>".typo($titre)."</A></B>";
		}
		if (strlen($descriptif)>1)
			$les_enfants.="<BR><FONT SIZE=1>$descriptif</FONT>";

		$les_enfants.= "</FONT>";

		$les_enfants.="<FONT FACE='arial, helvetica'>";
		$les_enfants .= $les_sous_enfants;
		$les_enfants .="</FONT>&nbsp;";
		$les_enfants .= fin_cadre_relief(true);
	}
}

function sous_enfant($collection2){
	$query3 = "SELECT * FROM spip_rubriques WHERE id_parent=\"$collection2\" ORDER BY titre";
	$result3 = spip_query($query3);

	if (mysql_num_rows($result3) > 0){
		$retour = debut_block_invisible("enfants$collection2")."\n\n<FONT SIZE=1><ul style='list-style-image: url(img_pack/rubrique-12.gif)'>";
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



//
// Gerer les modifications...
//
if ($titre){
	// si c'est une rubrique-secteur contenant des breves, ne deplacer
	// que si $confirme_deplace == 'oui'
	$query = "SELECT COUNT(*) AS cnt FROM spip_breves WHERE id_rubrique=\"$id_rubrique\"";
	$row = mysql_fetch_array(spip_query($query));
	if (($row['cnt'] > 0) and !($confirme_deplace == 'oui')) {
		$id_parent = 0;
	}

	// verifier qu'on envoit bien dans une rubrique autorisee
	if (acces_rubrique($id_parent)) {
		$change_parent = "id_parent=\"$id_parent\",";
	} else {
		$change_parent = "";
	}

	$titre = addslashes($titre);
	$descriptif = addslashes($descriptif);
	$texte = addslashes($texte);
	$query = "UPDATE spip_rubriques SET $change_parent titre=\"$titre\", descriptif=\"$descriptif\", texte=\"$texte\" WHERE id_rubrique=$id_rubrique";
	$result = spip_query($query);
	
	calculer_rubriques();

	if (lire_meta('activer_moteur') == 'oui') {
		indexer_rubrique($id_rubrique);
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




///// debut de la page
debut_page($titre_page, "documents", "rubriques");


//////// parents


debut_grand_cadre();

afficher_parents($id_parent);
$parents="~ <IMG SRC='img_pack/racine-site-24.gif' WIDTH=24 HEIGHT=24 align='middle'> <A HREF='naviguer.php3?coll=0'><B>RACINE DU SITE</B></A> ".aide ("rubhier")."<BR>".$parents;

$parents=ereg_replace("~","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$parents);
$parents=ereg_replace("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ","",$parents);

echo "$parents";

fin_grand_cadre();



debut_gauche();

if ($coll > 0) {
	debut_boite_info();
	echo "<CENTER>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=1><B>RUBRIQUE NUM&Eacute;RO&nbsp;:</B></FONT>";
	echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=6><B>$coll</B></FONT>";
	echo "</CENTER>";
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
debut_cadre_enfonce();
echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=1>";
echo "<b>RACCOURCIS :</b><p>";

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
if ($connect_statut == '0minirezo') {
	icone_horizontale("Cr&eacute;er une sous-rubrique", "rubriques_edit.php3?new=oui&retour=nav&id_parent=$coll", "rubrique-24.gif","creer.gif");
}


echo "</font>";
fin_cadre_enfonce();






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

if ($coll >0 AND $statut == 'publie') {
	echo "<td><img src='img_pack/rien.gif' width=5></td>\n";
	echo "<td  align='right'>";
	icone("Voir en ligne", "../spip_redirect.php3?id_rubrique=$coll&recalcul=oui", "racine-24.gif", "rien.gif");
	echo "</td>";
}




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
	echo "\n<p><font size=3 face='verdana,arial,helvetica,sans-serif'><div align='justify'>";
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
"SELECT spip_articles.id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut FROM spip_articles, spip_auteurs_articles AS lien WHERE spip_articles.id_article=lien.id_article AND id_rubrique='$coll' AND lien.id_auteur=\"$connect_id_auteur\" AND spip_articles.statut=\"prepa\" ORDER BY spip_articles.date DESC");


//////////  Les articles a valider
/////////////////////////

afficher_articles("Les articles &agrave; valider",
"SELECT spip_articles.id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut FROM spip_articles WHERE spip_articles.statut=\"prop\" AND id_rubrique='$coll' ORDER BY spip_articles.date DESC");


//////////  Les articles en cours de redaction
/////////////////////////

if ($connect_statut == "0minirezo" AND $options == 'avancees') {
	afficher_articles("Tous les articles en cours de r&eacute;daction",
	"SELECT spip_articles.id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut FROM spip_articles WHERE spip_articles.statut=\"prepa\" AND id_rubrique='$coll' ORDER BY spip_articles.date DESC");
}


//////////  Les articles publies
/////////////////////////

afficher_articles("Tous les articles publi&eacute;s dans cette rubrique",
"SELECT spip_articles.id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut FROM spip_articles WHERE spip_articles.statut=\"publie\" AND id_rubrique='$coll' ORDER BY spip_articles.date DESC");

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

afficher_sites("Les sites contenus dans cette rubrique", "SELECT * FROM spip_syndic WHERE id_rubrique='$coll' AND statut!='refuse' ORDER BY nom_site");


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
