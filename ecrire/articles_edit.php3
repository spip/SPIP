<?php

include ("inc.php3");
include_local ("inc_documents.php3");

$articles_surtitre = lire_meta("articles_surtitre");
$articles_soustitre = lire_meta("articles_soustitre");
$articles_descriptif = lire_meta("articles_descriptif");
$articles_chapeau = lire_meta("articles_chapeau");
$articles_ps = lire_meta("articles_ps");
$articles_redac = lire_meta("articles_redac");
$articles_mots = lire_meta("articles_mots");


//
// Gestion des modifications
//

if ($new == "oui") {
	$id_rubrique = (int) $id_rubrique;

	$mydate = date("YmdHis", time() - 24 * 3600);
	$query = "DELETE FROM spip_articles WHERE (statut = 'poubelle') && (maj < $mydate)";
	$result = mysql_query($query);

	$query = "INSERT INTO spip_articles (titre, id_rubrique, date, statut) VALUES ('Nouvel article', '$id_rubrique', NOW(), 'poubelle')";
	$result = mysql_query($query);
	$id_article = mysql_insert_id();

	$query = "DELETE FROM spip_auteurs_articles WHERE id_article=$id_article";
	$result = mysql_query($query);
	$query = "INSERT INTO spip_auteurs_articles (id_auteur, id_article) VALUES('$connect_id_auteur','$id_article')";
	$result = mysql_query($query);
}

$query = "SELECT * FROM spip_articles WHERE id_article='$id_article'";
$result = mysql_query($query);

while ($row = mysql_fetch_array($result)) {
	$id_article = $row[0];
	$surtitre = $row[1];
	$titre = $row[2];
	$soustitre = $row[3];
	$id_rubrique = $row[4];
	$descriptif = $row[5];
	$chapo = $row[6];
	$texte = $row[7];
	$ps = $row[8];
	$date = $row[9];
	$statut = $row['statut'];
	$date_redac = $row['date_redac'];
    	if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})",$date_redac,$regs)){
	        $mois_redac = $regs[2];
	        $jour_redac = $regs[3];
	        $annee_redac = $regs[1];
	        if ($annee_redac > 4000) $annee_redac -= 9000;
	}

	$query = "SELECT * FROM spip_auteurs_articles WHERE id_article=$id_article AND id_auteur=$connect_id_auteur";
	$result_auteur = mysql_query($query);

	$flag_auteur = (mysql_num_rows($result_auteur) > 0);

	$flag_editable = (acces_rubrique($id_rubrique) OR ($flag_auteur > 0 AND ($statut == 'prepa' OR $statut == 'prop' OR $new == 'oui')));
}

if (!$flag_editable) {
	die("<H3>Acc&egrave;s interdit</H3>");
}

if ($id_document) {
	$query_doc = "SELECT * FROM spip_documents_articles WHERE id_document=$id_document AND id_article=$id_article";
	$result_doc = mysql_query($query_doc);
	$flag_document_editable = (mysql_num_rows($result_doc) > 0);
} else {
	$flag_document_editable = false;
}


//
// Gestion des textes trop longs (limitation brouteurs)
//

function coupe_trop_long($texte){	// utile pour les textes > 32ko
	if (strlen($texte) > 28*1024) {
		$texte = str_replace("\r\n","\n",$texte);
		$pos = strpos($texte, "\n\n\n", 28*1024);	// coupe para > 28 ko
		if ($pos > 0 and $pos < 32 * 1024) {
			$debut = substr($texte, 0, $pos)."\n\n\n<!--SPIP-->\n";
			$suite = substr($texte, $pos + 3);
		} else {
			$pos = strpos($texte, " ", 28*1024);	// sinon coupe espace
			if (!($pos > 0 and $pos < 32 * 1024))
				$pos = 28*1024;	// au pire
			$debut = substr($texte,0,$pos);
			$suite = substr($texte,$pos + 1);
		}
		return (array($debut,$suite));
	}
	else
		return (array($texte,''));
}


debut_page();
debut_gauche();



//
// Pave "documents associes a l'article"
//

boite_documents_article($id_article);


debut_droite();


function mySel($varaut,$variable) {
		$retour= " VALUE=\"$varaut\"";

	if ($variable==$varaut) {
		$retour.= " SELECTED";
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

function afficher_jour($jour){
	for($i=1;$i<32;$i++){
		if ($i<10){$aff="&nbsp;".$i;}else{$aff=$i;}
		my_sel($i,$aff,$jour);
	}
}


function enfant($leparent){
	global $id_parent;
	global $id_rubrique;
	global $i;
	global $statut;
	global $connect_toutes_rubriques;
	global $connect_id_rubriques;
	
	$i++;
 	$query="SELECT * FROM spip_rubriques WHERE id_parent='$leparent' ORDER BY titre";
 	$result=mysql_query($query);

	while($row=mysql_fetch_array($result)){
		$my_rubrique=$row['id_rubrique'];
		$titre=typo($row['titre']);

		// si l'article est publie il faut etre admin pour avoir le menu
		// sinon le menu est present en entier (proposer un article)
		if ($statut != "publie" OR acces_rubrique($my_rubrique)) {
			$rubrique_acceptable = true;
		} else {
			$rubrique_acceptable = false;
		}

		$espace="";
		for ($count=0;$count<$i;$count++){$espace.="&nbsp;&nbsp;";}
		$espace .= "|";
		if ($i==1)
			$espace = "*";

		if ($rubrique_acceptable) {
			echo "<OPTION".mySel($my_rubrique,$id_rubrique).">$espace $titre\n";
		}
		enfant($my_rubrique);
	}
	$i=$i-1;
}


echo "<A HREF='articles.php3?id_article=$id_article' onMouseOver=\"retour.src='IMG2/retour-on.gif'\" onMouseOut=\"retour.src='IMG2/retour-off.gif'\"><img src='IMG2/retour-off.gif' alt=\"Retour &agrave; l'article\" width='49' height='46' border='0' name='retour' align='left'></A>";

echo "Modifier l'article :<BR><FONT SIZE=5 COLOR='$couleur_foncee' FACE='Verdana,Arial,Helvetica,sans-serif'><B>".typo($titre)."</B></FONT>";

echo aide ("raccourcis");

//bouton("Retour &agrave; l article","articles.php3?id_article=$id_article");


echo "<P><HR><P>";
	
	$titre = htmlspecialchars($titre);
	$soustitre = htmlspecialchars($soustitre);
	$surtitre = htmlspecialchars($surtitre);

	$descriptif = htmlspecialchars($descriptif);
	$chapo = htmlspecialchars($chapo);
	$texte = htmlspecialchars($texte);
	$ps = htmlspecialchars($ps);


	echo "<FORM ACTION='articles.php3?id_article=$id_article' METHOD='post'>";

	echo "<INPUT TYPE='Hidden' NAME='id_article' VALUE=\"$id_article\">";


	if (($options=="avancees" AND $articles_surtitre!="non") OR strlen($surtitre)>0){
		echo "<B>Sur-titre</B>";
		echo aide ("arttitre");
		echo "<BR><INPUT TYPE='text' NAME='surtitre' CLASS='forml' VALUE=\"$surtitre\" SIZE='40'><P>";
	}else{
		echo "<INPUT TYPE='hidden' NAME='surtitre' VALUE=\"$surtitre\" >";
	}
	
	echo "<B>Titre</B> [Obligatoire]";
	echo aide ("arttitre");
	echo "<BR><INPUT TYPE='text' NAME='titre' CLASS='formo' VALUE=\"$titre\" SIZE='40'><P>";

	if (($options=="avancees" AND $articles_soustitre!="non") OR strlen($soustitre) > 0) {
		echo "<B>Sous-titre</B>";
		echo aide ("arttitre");
		echo "<BR><INPUT TYPE='text' NAME='soustitre' CLASS='forml' VALUE=\"$soustitre\" SIZE='40'><P>";
	}else{
		echo "<INPUT TYPE='hidden' NAME='soustitre' VALUE=\"$soustitre\">";	
	}
	
	echo "<B>&Agrave; l'int&eacute;rieur de la rubrique&nbsp;:</B>\n";
	echo aide ("artrub");
	echo "<BR><SELECT NAME='id_rubrique' CLASS='formo' SIZE=1>\n";
	enfant(0);
	echo "</SELECT><BR>\n";
	echo "[N'oubliez pas de s&eacute;lectionner correctement ce champ.]<P>\n";

	if (($options=="avancees" AND $articles_descriptif!="non") OR strlen($descriptif) > 0) {
		echo "<B>Descriptif rapide</B>";
		echo aide ("artdesc");
		echo "<BR>(Contenu de l'article en quelques mots.)<BR>";
		echo "<TEXTAREA NAME='descriptif' CLASS='forml' ROWS='2' COLS='40' wrap=soft>";
		echo $descriptif;
		echo "</TEXTAREA><P>\n";
	}
	else{
		echo "<INPUT TYPE='hidden' NAME='descriptif' VALUE=\"$descriptif\">";
	}

	echo "<HR>";

	if (($articles_chapeau!="non") OR strlen($chapeau) > 0) {
		echo "<B>Chapeau</B>";
		echo aide ("artchap");
		echo "<BR>(Texte introductif de l'article.)<BR>";
		echo "<TEXTAREA NAME='chapo' CLASS='forml' ROWS='5' COLS='40' wrap=soft>";
		echo $chapo;
		echo "</TEXTAREA><P>\n";
	}else{
			echo "<INPUT TYPE='hidden' NAME='chapo' VALUE=\"$chapo\">";

	}



	if (strlen($texte)>29*1024) // texte > 32 ko -> decouper en morceaux
	{
		include "inc_32ko_browsers.php3";
		if (! browser_32ko($HTTP_USER_AGENT)){ // browser pas connu comme "sur"
			$textes_supplement = "<br><font color='red'>(le texte est long&nbsp;: il appara&icirc;t donc en plusieurs parties qui seront recoll&eacute;es apr&egrave;s validation.)</font>\n";
			while (strlen($texte)>29*1024)
			{
				$nombre_textes ++;
				list($texte1,$texte) = coupe_trop_long($texte);

				$textes_supplement .= "<BR><TEXTAREA NAME='texte$nombre_textes'".
					" CLASS='forml' ROWS='20' COLS='40'>" .
					$texte1 . "</TEXTAREA><P>\n";
			}
		}
	}
	echo "<B>Texte</B>";
	echo aide ("arttexte");

	echo $textes_supplement;

	echo "<BR><TEXTAREA NAME='texte' CLASS='forml' ROWS='20' COLS='40' wrap=soft>";
	echo $texte;
	echo "</TEXTAREA><P>\n";


if (($articles_ps!="non") OR strlen($ps) > 0) {
	echo "<B>Post-Scriptum</B><BR>";
	echo "<TEXTAREA NAME='ps' CLASS='forml' ROWS='3' COLS='40' wrap=soft>";
	echo $ps;
	echo "</TEXTAREA><P>\n";
}else{
		echo "<INPUT TYPE='hidden' NAME='ps' VALUE=\"$ps\">";

}

	echo "<INPUT TYPE='Hidden' NAME='date' VALUE=\"$date\" SIZE='40'><P>";

	if ($new == "oui")
		echo "<INPUT TYPE='Hidden' NAME='statut_nouv' VALUE=\"prepa\" SIZE='40'><P>";

	echo "<DIV ALIGN='right'>";
	echo "<INPUT CLASS='fondo' TYPE='submit' NAME='Valider' VALUE='Valider'>";
	echo "</FORM>";


fin_page();

?>
