<?php

include ("inc.php3");
include_ecrire ("inc_documents.php3");

$articles_surtitre = lire_meta("articles_surtitre");
$articles_soustitre = lire_meta("articles_soustitre");
$articles_descriptif = lire_meta("articles_descriptif");
$articles_chapeau = lire_meta("articles_chapeau");
$articles_ps = lire_meta("articles_ps");
$articles_redac = lire_meta("articles_redac");
$articles_mots = lire_meta("articles_mots");
$articles_modif = lire_meta("articles_modif");

// securite
$id_article = (int) $id_article;
unset ($flag_editable);

//
// Creation de l'objet article
//

if ($id_article) {
	spip_query("UPDATE spip_articles SET date_modif=NOW(), auteur_modif=$connect_id_auteur WHERE id_article=$id_article");
	$id_article_bloque = $id_article;	// message pour inc_presentation

	// Recuperer les donnees de l'article
	$query = "SELECT * FROM spip_articles WHERE id_article=$id_article";
	$result = spip_query($query);

	if ($row = spip_fetch_array($result)) {
		$id_article = $row["id_article"];
		$surtitre = $row["surtitre"];
		$titre = $row["titre"];
		$soustitre = $row["soustitre"];
		$id_rubrique = $row["id_rubrique"];
		$descriptif = $row["descriptif"];
		$chapo = $row["chapo"];
		$texte = $row["texte"];
		$ps = $row["ps"];
		$date = $row["date"];
		$statut = $row['statut'];
		$date_redac = $row['date_redac'];
	    	if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})",$date_redac,$regs)){
		        $mois_redac = $regs[2];
		        $jour_redac = $regs[3];
		        $annee_redac = $regs[1];
		        if ($annee_redac > 4000) $annee_redac -= 9000;
		}

		$query = "SELECT * FROM spip_auteurs_articles WHERE id_article=$id_article AND id_auteur=$connect_id_auteur";
		$result_auteur = spip_query($query);
		$flag_auteur = (spip_num_rows($result_auteur) > 0);

		$flag_editable = (acces_rubrique($id_rubrique) OR ($flag_auteur > 0 AND ($statut == 'prepa' OR $statut == 'prop' OR $new == 'oui')));
	}
}
else if ($new=='oui') {
	$flag_editable = true;
	$titre = 'Nouvel article';
}

if (!$flag_editable) {
	die ("<H3>Acc&egrave;s interdit</H3>");
}

if ($id_article && $id_document) {
	$query_doc = "SELECT * FROM spip_documents_articles WHERE id_document=$id_document AND id_article=$id_article";
	$result_doc = spip_query($query_doc);
	$flag_document_editable = (spip_num_rows($result_doc) > 0);
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


debut_page("Modifier : $titre", "documents", "articles");


debut_grand_cadre();

afficher_parents($id_rubrique);
$parents="~ <img src='img_pack/racine-site-24.gif' width=24 height=24 align='middle'> <A HREF='naviguer.php3?coll=0'><B>RACINE DU SITE</B></A> ".aide ("rubhier")."<BR>".$parents;

$parents=ereg_replace("~","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$parents);
$parents=ereg_replace("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ","",$parents);

echo "$parents";

fin_grand_cadre();



debut_gauche();



//
// Pave "documents associes a l'article"
//

if ($new != 'oui'){
	afficher_documents_colonne($id_article, 'article', $flag_editable);
}

debut_droite();
debut_cadre_formulaire();


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
	static $i = 0, $premier = 1;
	global $statut;
	global $connect_toutes_rubriques;
	global $connect_id_rubriques;
	global $couleur_claire;

	$i++;
 	$query="SELECT * FROM spip_rubriques WHERE id_parent='$leparent' ORDER BY titre";
 	$result=spip_query($query);

	while($row=spip_fetch_array($result)){
		$my_rubrique=$row['id_rubrique'];
		$titre=typo($row['titre']);
		$statut_rubrique=$row['statut'];
		$style = "";

		// si l'article est publie il faut etre admin pour avoir le menu
		// sinon le menu est present en entier (proposer un article)
		if ($statut != "publie" OR acces_rubrique($my_rubrique)) {
			$rubrique_acceptable = true;
		} else {
			$rubrique_acceptable = false;
		}

		$espace="";
		for ($count=1;$count<$i;$count++){
			$espace.="&nbsp;&nbsp;&nbsp; ";
		}

		switch ($i) {
		case 1:
			$espace= "";
			$style .= "font-weight: bold;";
			break;
		case 2:
			$style .= "color: #202020;";
			break;
		case 3:
			$style .= "color: #404040;";
			break;
		case 4:
			$style .= "color: #606060;";
			break;
		case 5:
			$style .= "color: #808080;";
			break;
		default;
			$style .= "color: #A0A0A0;";
			break;
		}

		if ($rubrique_acceptable) {
			if ($i == 1 && !$premier) echo "<OPTION VALUE='$my_rubrique'>\n"; // sert a separer les secteurs
			$titre = couper($titre, 50); // largeur maxi
			echo "<OPTION".mySel($my_rubrique,$id_rubrique)." style=\"$style\">$espace$titre\n";
		}
		$premier = 0;
		enfant($my_rubrique);
	}
	$i=$i-1;
}


echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr width='100%'>";
echo "<td>";
	icone("Retour", "articles.php3?id_article=$id_article", "article-24.gif", "rien.gif");

echo "</td>";
	echo "<td><img src='img_pack/rien.gif' width=10></td>\n";
echo "<td width='100%'>";
echo "Modifier l'article :";
gros_titre($titre);
echo "</td></tr></table>";
echo "<p>";

echo "<P><HR><P>";

	$titre = entites_html($titre);
	$soustitre = entites_html($soustitre);
	$surtitre = entites_html($surtitre);

	$descriptif = entites_html($descriptif);
	$chapo = entites_html($chapo);
	$texte = entites_html($texte);
	$ps = entites_html($ps);

	$lien = 'articles.php3';
	if ($id_article) $lien .= "?id_article=$id_article";
	echo "<FORM ACTION='$lien' METHOD='post'>\n";

	if ($id_article)
		echo "<INPUT TYPE='Hidden' NAME='id_article' VALUE='$id_article'>";
	else if ($new == 'oui')
		echo "<INPUT TYPE='Hidden' NAME='new' VALUE='oui'>";

	if (($articles_surtitre != "non") OR strlen($surtitre) > 0) {
		echo "<B>Sur-titre</B>";
		echo aide ("arttitre");
		echo "<BR><INPUT TYPE='text' NAME='surtitre' CLASS='forml' VALUE=\"$surtitre\" SIZE='40'><P>";
	}
	else {
		echo "<INPUT TYPE='hidden' NAME='surtitre' VALUE=\"$surtitre\" >";
	}

	echo "<B>Titre</B> [Obligatoire]";
	echo aide ("arttitre");
	echo "<BR><INPUT TYPE='text' NAME='titre' style='font-weight: bold;' CLASS='formo' VALUE=\"$titre\" SIZE='40'><P>";

	if (($articles_soustitre != "non") OR strlen($soustitre) > 0) {
		echo "<B>Sous-titre</B>";
		echo aide ("arttitre");
		echo "<BR><INPUT TYPE='text' NAME='soustitre' CLASS='forml' VALUE=\"$soustitre\" SIZE='40'><br><br>";
	}
	else {
		echo "<INPUT TYPE='hidden' NAME='soustitre' VALUE=\"$soustitre\">";
	}


	/// Dans la rubrique....

	if ($id_rubrique == 0) $logo_parent = "racine-site-24.gif";
	else {
		$query = "SELECT id_parent FROM spip_rubriques WHERE id_rubrique='$id_rubrique'";
		$result=spip_query($query);
		while($row=spip_fetch_array($result)){
			$parent_parent=$row['id_parent'];
		}
		if ($parent_parent == 0) $logo_parent = "secteur-24.gif";
		else $logo_parent = "rubrique-24.gif";
	}

	debut_cadre_relief("$logo_parent");
	echo "<B>&Agrave; l'int&eacute;rieur de la rubrique&nbsp;:</B>\n";
	echo aide ("artrub");
	echo "<BR><SELECT NAME='id_rubrique' style='background-color: $couleur_claire; font-size: 90%; width:100%; font-face:verdana,arial,helvetica,sans-serif;' SIZE=1>\n";
	enfant(0);
	echo "</SELECT><BR>\n";
	echo "[N'oubliez pas de s&eacute;lectionner correctement ce champ.]\n";
	fin_cadre_relief();

	if (($options == "avancees" AND $articles_descriptif != "non") OR strlen($descriptif) > 0) {
		echo "<P><B>Descriptif rapide</B>";
		echo aide ("artdesc");
		echo "<BR>(Contenu de l'article en quelques mots.)<BR>";
		echo "<TEXTAREA NAME='descriptif' CLASS='forml' ROWS='2' COLS='40' wrap=soft>";
		echo $descriptif;
		echo "</TEXTAREA><P>\n";
	}
	else {
		echo "<INPUT TYPE='hidden' NAME='descriptif' VALUE=\"$descriptif\">";
	}

	if (substr($chapo, 0, 1) == '=') {
		$virtuel = substr($chapo, 1, strlen($chapo));
		$chapo = "";
	}

	if ($connect_statut=="0minirezo" AND strlen($virtuel) > 0){
		echo "<p><div style='border: 1px dashed #666666; background-color: #f0f0f0; padding: 5px;'>";
		echo "<table width=100% cellspacing=0 cellpadding=0 border=0>";
		echo "<tr><td valign='top'>";
		echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=2>";
		echo "<B><label for='confirme-virtuel'>Redirection&nbsp;:</label></B>";
		echo aide ("artvirt");
		echo "</font>";
		echo "</td>";
		echo "<td width=10>&nbsp;</td>";
		echo "<td valign='top' width='50%'>";
		if (strlen($virtuel) == 0) $virtuel = "http://";
		echo "<INPUT TYPE='text' NAME='virtuel' CLASS='forml' style='font-size:9px;' VALUE=\"$virtuel\" SIZE='40'>";
		echo "<input type='hidden' name='changer_virtuel' value='oui'>";
		echo "</td></tr></table>\n";
		echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=2>";
		echo "<b>Article virtuel&nbsp;:</b> article r&eacute;f&eacute;renc&eacute; dans votre site SPIP, mais redirig&eacute; vers une autre URL. Pour supprimer la redirection, effacez l'URL ci-dessus.";
		echo "</font>";
		echo "</div><p>\n";
	}

	else {
		echo "<HR>";

		if (($articles_chapeau != "non") OR strlen($chapo) > 0) {
			if ($spip_ecran == "large") $rows = 8;
			else $rows = 5;
			echo "<B>Chapeau</B>";
			echo aide ("artchap");
			echo "<BR>(Texte introductif de l'article.)<BR>";
			echo "<TEXTAREA NAME='chapo' CLASS='forml' ROWS='$rows' COLS='40' wrap=soft>";
			echo $chapo;
			echo "</TEXTAREA><P>\n";
		}
		else {
			echo "<INPUT TYPE='hidden' NAME='chapo' VALUE=\"$chapo\">";
		}

	}

	if ($spip_ecran == "large") $rows = 28;
	else $rows = 20;

	if (strlen($texte)>29*1024) // texte > 32 ko -> decouper en morceaux
	{
		$textes_supplement = "<br><font color='red'>(le texte est long&nbsp;: il appara&icirc;t donc en plusieurs parties qui seront recoll&eacute;es apr&egrave;s validation.)</font>\n";
		while (strlen($texte)>29*1024)
		{
			$nombre_textes ++;
			list($texte1,$texte) = coupe_trop_long($texte);

			$textes_supplement .= "<BR><TEXTAREA NAME='texte$nombre_textes'".
				" CLASS='formo' ROWS='$rows' COLS='40' wrap=soft>" .
				$texte1 . "</TEXTAREA><P>\n";
		}
	}
	echo "<B>Texte</B>";
	echo aide ("arttexte");
	echo "<br>Vous pouvez enrichir la mise en page de votre texte en utilisant des &laquo;&nbsp;raccourcis typographiques&nbsp;&raquo;.";
	echo aide("raccourcis");

	echo $textes_supplement;

	echo "<BR><TEXTAREA NAME='texte' CLASS='formo' ROWS='$rows' COLS='40' wrap=soft>";
	echo $texte;
	echo "</TEXTAREA><P>\n";

	if (($articles_ps != "non" AND $options == "avancees") OR strlen($ps) > 0) {
		echo "<B>Post-Scriptum</B><BR>";
		echo "<TEXTAREA NAME='ps' CLASS='forml' ROWS='5' COLS='40' wrap=soft>";
		echo $ps;
		echo "</TEXTAREA><P>\n";
	}
	else {
		echo "<INPUT TYPE='hidden' NAME='ps' VALUE=\"$ps\">";
	}

	if ($date)
		echo "<INPUT TYPE='Hidden' NAME='date' VALUE=\"$date\" SIZE='40'><P>";

	if ($new == "oui")
		echo "<INPUT TYPE='Hidden' NAME='statut_nouv' VALUE=\"prepa\" SIZE='40'><P>";

	echo "<DIV ALIGN='right'>";
	echo "<INPUT CLASS='fondo' TYPE='submit' NAME='Valider' VALUE='Valider'>";
	echo "</DIV></FORM>";

fin_cadre_formulaire();

fin_page();

?>
