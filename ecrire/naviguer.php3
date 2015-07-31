<?php

include ("inc.php3");
include_local ("inc_logos.php3");
include_local ("inc_index.php3");
include_local ("inc_meta.php3");
include_local ("inc_mots.php3");


if (!$coll) $coll=0;
$flag_mots = lire_meta("articles_mots");

//
// Afficher la hierarchie des rubriques
//
function parent($collection){
	global $parents;
	global $coll;
	$parents=ereg_replace("(~+)","\\1~",$parents);
	if ($collection!=0){	
		$query2="SELECT * FROM spip_rubriques WHERE id_rubrique=\"$collection\"";
		$result2=mysql_query($query2);

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

function enfant($collection){
	global $les_enfants;
	$query2 = "SELECT * FROM spip_rubriques WHERE id_parent=\"$collection\" ORDER BY titre";
	$result2 = mysql_query($query2);
	
	while($row=mysql_fetch_array($result2)){
		$id_rubrique=$row[0];
		$id_parent=$row[1];
		$titre=$row[2];
		$descriptif=$row[3];
	
		$les_sous_enfants = sous_enfant($id_rubrique);

		$les_enfants.= "<P><TABLE CELLPADDING=0 CELLSPACING=0 BORDER=0 WIDTH=\"100%\">";
		$les_enfants.= "<TR><TD WIDTH=\"100%\">";
		$les_enfants.= "<TABLE CELLPADDING=1 CELLSPACING=0 BORDER=0 WIDTH=\"100%\"><TR><TD BGCOLOR='#000000' WIDTH=\"100%\">";
		$les_enfants.= "<TABLE CELLPADDING=5 CELLSPACING=0 BORDER=0 WIDTH=\"100%\"><TR><TD BACKGROUND='IMG2/rayures.gif' BGCOLOR='#FFFFFF' WIDTH=\"100%\">";
		$les_enfants.= "<FONT FACE=\"Georgia,Garamond,Times,serif\">";

		if (strlen($les_sous_enfants) > 0){
			$les_enfants.= bouton_block_invisible("enfants$id_rubrique");
		}
		if  (acces_restreint_rubrique($id_rubrique)){
			$les_enfants.= "<B><A HREF='naviguer.php3?coll=$id_rubrique'><font color='red'>".typo($titre)."</font></A></B>";
		}else{
			$les_enfants.= "<B><A HREF='naviguer.php3?coll=$id_rubrique'>".typo($titre)."</A></B>";
		}
		if (strlen($descriptif)>1)
			$les_enfants.="<BR><FONT SIZE=1>$descriptif</FONT>";

		$les_enfants.= "</FONT>";
		$les_enfants.= "</TD></TR></TABLE>";
		$les_enfants.= "</TD></TR></TABLE>";
		$les_enfants.= "</TD>";
		$les_enfants.= "<TD VALIGN='top' BACKGROUND='IMG2/ombre-d.gif' WIDTH=5><img src='IMG2/ombre-hd.gif' width='5' height='9' border=0><TD></TR>";
		$les_enfants.= "<TR><TD BACKGROUND='IMG2/ombre-b.gif' ALIGN='left'><img src='IMG2/ombre-bg.gif' width='8' height='5' border='0'></TD><TD><img src='IMG2/ombre-bd.gif' width='5' height='5' border='0'></TD></TR></TABLE>";

//		$les_enfants.="<BR>";
		$les_enfants.="<FONT FACE='arial, helvetica'>";
		$les_enfants .= $les_sous_enfants;
		$les_enfants.="</FONT>&nbsp;";
	}
}

function sous_enfant($collection2){
	$query3 = "SELECT * FROM spip_rubriques WHERE id_parent=\"$collection2\" ORDER BY titre";
	$result3 = mysql_query($query3);

	if (mysql_num_rows($result3) > 0){
		$retour = debut_block_invisible("enfants$collection2")."\n\n<FONT SIZE=1><ul>";
		while($row=mysql_fetch_array($result3)){
			$id_rubrique2=$row[0];
			$id_parent2=$row[1];
			$titre2=$row[2];
			
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
	$query = "SELECT COUNT(*) FROM spip_breves WHERE id_rubrique=\"$id_rubrique\"";
	$row = mysql_fetch_array(mysql_query($query));
	if (($row[0] > 0) and !($confirme_deplace == 'oui')) {
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
	$result = mysql_query($query);
	
	calculer_rubriques();

	if (lire_meta('activer_moteur') == 'oui') {
		indexer_rubrique($id_rubrique);
	}
}

//
// infos sur cette rubrique
//
$query="SELECT * FROM spip_rubriques WHERE id_rubrique='$coll'";
$result=mysql_query($query);

while($row=mysql_fetch_array($result)){
	$id_rubrique=$row[0];
	$id_parent=$row[1];
	$titre=$row[2];
	$descriptif=$row[3];
	$texte=$row[4];
}

if ($titre)
	$titre_page = "&laquo; ".textebrut($titre)." &raquo;";
else
	$titre_page = "Naviguer dans le site...";

debut_page($titre_page);
debut_gauche();

debut_boite_info();

echo "<CENTER>";


if ($coll >0) {
	echo "<A HREF='../spip_redirect.php3?id_rubrique=$coll&recalcul=oui'><img src='IMG2/voirenligne.gif' alt='voir en ligne' width='48' height='48' border='0' align='right'></A>";
}


echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=1><B>RUBRIQUE NUM&Eacute;RO&nbsp;:</B></FONT>";
echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=6><B>$coll</B></FONT>";
echo "</CENTER>";

fin_boite_info();

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



debut_droite();


//////// parents

parent($coll);
$parents="~ <IMG SRC='IMG2/triangle-bas.gif' WIDTH=16 HEIGHT=14> <A HREF='naviguer.php3?coll=0'><B>RACINE DU SITE</B></A> ".aide ("rubhier")."<BR>".$parents;

$parents=ereg_replace("~","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$parents);
$parents=ereg_replace("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ","",$parents);

echo "$parents";



///// ICONES DE CREATION/MODIFICATION
$flag_editable = ($connect_statut == '0minirezo' AND acces_rubrique($coll));

echo "<div align='right'>";

echo "<table cellpadding=0 cellspacing=10 border=0><tr>";
if ($coll > 0 AND $connect_statut == '0minirezo' AND acces_rubrique($coll)) {
	echo "<td  valign='bottom'><A HREF='rubriques_edit.php3?id_rubrique=$id_rubrique&retour=nav' onMouseOver=\"modifier_rubrique.src='IMG2/modifier-rubrique-on.gif'\" onMouseOut=\"modifier_rubrique.src='IMG2/modifier-rubrique-off.gif'\"><img src='IMG2/modifier-rubrique-off.gif' alt='Modifier cette rubrique' width='73' height='56' border='0' name='modifier_rubrique'></A></td>";
}
if ($connect_statut == '0minirezo' AND acces_rubrique($coll)) {
	echo "<td  valign='bottom'><A HREF='rubriques_edit.php3?new=oui&retour=nav&id_parent=$id_rubrique' onMouseOver=\"creer_rubrique.src='IMG2/creer-rubrique-on.gif'\" onMouseOut=\"creer_rubrique.src='IMG2/creer-rubrique-off.gif'\"><img src='IMG2/creer-rubrique-off.gif' alt='Creer une nouvelle sous-rubrique' width='95' height='56' border='0' name='creer_rubrique' ALIGN='top'></A></td>";
}

if ($coll > 0){
	echo "<td  valign='bottom'><A HREF='articles_edit.php3?id_rubrique=$coll&new=oui' onMouseOver=\"ecrire_article.src='IMG2/ecrire-article-on.gif'\" onMouseOut=\"ecrire_article.src='IMG2/ecrire-article-off.gif'\"><img src='IMG2/ecrire-article-off.gif' alt='Ecrire un nouvel article' width='69' height='53' border='0' name='ecrire_article'></A></td>";
}

$activer_breves=lire_meta("activer_breves");

if ($id_parent == "0" AND $activer_breves!="non"){
	echo "<td valign='bottom'><A HREF='./breves_edit.php3?id_rubrique=$coll&new=oui' onMouseOver=\"ecrire_breve.src='IMG2/ecrire-breve-on.gif'\" onMouseOut=\"ecrire_breve.src='IMG2/ecrire-breve-off.gif'\"><img src='IMG2/ecrire-breve-off.gif' alt='Ecrire une nouvelle breve' width='75' height='53' border='0' name='ecrire_breve'></A></td>";
}

$proposer_sites=lire_meta("proposer_sites");
if ($coll > 0 AND ($connect_statut == '0minirezo' OR $proposer_sites > 0)) {
	$link = new Link('sites_edit.php3');
	$link->addVar('id_rubrique', $coll);
	$link->addVar('target', 'sites.php3');
	$link->addVar('redirect', $this_link->getUrl());
	echo "<td valign='bottom' align='center'>";
	echo "<a ".$link->getHref()." onMouseOver=\"ecrire_site.src='IMG2/ecrire-site-on.gif'\" onMouseOut=\"ecrire_site.src='IMG2/ecrire-site-off.gif'\" class='boutonlien'>";
	echo "<img src='IMG2/ecrire-site-off.gif' alt='R&eacute;f&eacute;rencer un site' width='58' height='34' border='0' name='ecrire_site'>";
	echo "<br>R&eacute;f&eacute;rencer<br>un site</a></td>\n";
}


echo "</tr></table>";

if (strlen($descriptif) > 1) {
	echo "<DIV align='left'>";
	debut_boite_info();

	echo "<img src='IMG2/descriptif.gif' alt='DESCRIPTIF' width='59' height='12' border='0'><BR>";
	echo "<FONT SIZE=3 FACE='Verdana,Arial,Helvetica,sans-serif'>";
	echo propre($descriptif);
	echo "&nbsp; ";
	echo "</FONT>";
	fin_boite_info();
}


if (strlen($texte) > 1) {
	echo "<FONT SIZE=3 FACE='Georgia,Garamond,Times,serif'><B><DIV align='justify'>";
	echo justifier(propre($texte));
	echo "&nbsp;<P></B></FONT>";
}



/// Mots-cles


if ($flag_mots!= 'non' AND $connect_statut == '0minirezo' AND acces_rubrique($coll) AND $options == 'avancees' AND $coll > 0) {
	echo "<br><br>";
	formulaire_mots('rubriques', $coll, $nouv_mot, $supp_mot, $cherche_mot, $flag_editable);
}



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


if (strlen($les_enfants) > 0) {
	echo "<CENTER><TABLE CELLPADING=0 CELLSPACING=0 BORDER=2 WIDTH=100% CLASS='profondeur'>";
	echo "<TR><TD WIDTH=\"100%\"><TABLE CELLPADDING=5 CELLSPACING=0 BORDER=0 WIDTH=\"100%\">";
	echo "<TR><TD VALIGN='top' WIDTH=50% BGCOLOR='#FFFFFF'>$les_enfants1</TD>";
	echo "<TD VALIGN='top' WIDTH=50% BGCOLOR='#FFFFFF'>$les_enfants2 &nbsp;</TD></TR>";
	echo "</TABLE></TD></TR></TABLE></CENTER>";
}


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

if ($connect_statut == "0minirezo") {
	afficher_articles("Tous les articles en cours de r&eacute;daction",
	"SELECT spip_articles.id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut FROM spip_articles WHERE spip_articles.statut=\"prepa\" AND id_rubrique='$coll' ORDER BY spip_articles.date DESC");
}


//////////  Les articles publies
/////////////////////////

afficher_articles("Tous les articles publi&eacute;s dans cette rubrique",
"SELECT spip_articles.id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut FROM spip_articles WHERE spip_articles.statut=\"publie\" AND id_rubrique='$coll' ORDER BY spip_articles.date DESC");

afficher_sites("Les sites contenus dans cette rubrique", "SELECT * FROM spip_syndic WHERE id_rubrique='$coll' AND statut!='refuse' ORDER BY nom_site");


////// Supprimer cette rubrique (si vide)

if (tester_rubrique_vide($coll)) {
	echo "<p><div align='right'><table cellpadding=0 cellspacing=10 border=0><tr>";
	$link = new Link('naviguer.php3');
	$link->addVar('coll', $id_parent);
	$link->addVar('supp_rubrique', $coll);
	echo "<td valign='bottom' align='center'>";
	echo "<a ".$link->getHref()." onMouseOver=\"supp_rubrique.src='IMG2/supp-rubrique-on.gif'\" onMouseOut=\"supp_rubrique.src='IMG2/supp-rubrique-off.gif'\" class='boutonlien'>";
	echo "<img src='IMG2/supp-rubrique-off.gif' alt='R&eacute;f&eacute;rencer un site' width='57' height='38' border='0' name='supp_rubrique'>";
	echo "<br>Supprimer<br>cette rubrique</a></td>\n";
	echo "</tr></table>";
}



fin_page();

?>
