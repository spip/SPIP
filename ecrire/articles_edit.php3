<?

include ("inc.php3");

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
}

if ($transformer_vignette == 'oui' AND $flag_document_editable) {
	$query_doc = "UPDATE spip_documents SET mode='document' WHERE id_document=$id_document";
	mysql_query($query_doc);
}

if ($transformer_document == 'oui' AND $flag_document_editable) {
	$query_doc = "UPDATE spip_documents SET mode='vignette' WHERE id_document=$id_document";
	mysql_query($query_doc);
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


//
// Affichage des images et documents
//

function afficher_image_edition($row) {
	global $connect_id_auteur, $id_article;

	$mode = $row['mode'];

	if ($mode == 'vignette') {
		$id_vignette = $row['id_document'];
		$fichier_vignette = $row['fichier'];
		$largeur_vignette = $row['largeur'];
		$hauteur_vignette = $row['hauteur'];
		$taille_vignette = $row['taille'];

		// Si vignette, recuperer document correspondant
		$query_doc = "SELECT * FROM spip_documents WHERE id_vignette=$id_vignette";
		$result_doc = mysql_query($query_doc);
		$row_doc = @mysql_fetch_array($result_doc);
	}
	else {
		// Si document, recuperer infos directement
		$row_doc = $row;
	}

	if ($row_doc) {
		$id_type = $row_doc['id_type'];
		$id_document = $row_doc['id_document'];
		$fichier = $row_doc['fichier'];
		$largeur = $row_doc['largeur'];
		$hauteur = $row_doc['hauteur'];
		$taille = $row_doc['taille'];
		$titre = $row_doc['titre'];
		$descriptif = $row_doc['descriptif'];

		$query_type = "SELECT * FROM spip_types_documents WHERE id_type=$id_type";
		$result_type = mysql_query($query_type);
		$row_type = @mysql_fetch_array($result_type);

		$type_inclus = $row_type['inclus'];
		$type_titre = $row_type['titre'];
		$type_ext = $row_type['extension'];
	}

	echo "<p><div style='border: 1px solid black; padding: 6px; background-color: #FEF6E0; text-align: center;'>";
	echo "<font face=\"Verdana, Arial, Helvetica, sans-serif\" size=\"1\">";

	$lien_retour = new Link("articles_edit.php3?id_article=$id_article");
	$url_retour = urlencode($lien_retour->getUrl());

	//
	// Si vignette, afficher la vignette
	//
	if ($fichier_vignette) {
		echo "<div align='center'>";
		if ($fichier) echo "Vignette de previsualisation :<br>\n";
		else echo "Image affichee dans l'article:<br>\n";
		echo "$largeur_vignette x $hauteur_vignette pixels";
		echo "</div>\n";

		if ($largeur_vignette > 180) {
			$rapport = 180.0 / $largeur_vignette;
			$largeur_vignette = 180;
			$hauteur_vignette = floor($hauteur_vignette * $rapport);
		}

		echo "<a href='../$fichier_vignette'><img src='../$fichier_vignette' height='$hauteur_vignette' width='$largeur_vignette' border='0'></a>\n";

		$hash = calculer_action_auteur("supp_doc ".$id_vignette);
		$link = new Link('../spip_image.php3');
		$link->addVar('redirect', $url_retour);
		$link->addVar('hash_id_auteur', $connect_id_auteur);
		$link->addVar('hash', $hash);
		$link->addVar('doc_supp', $id_vignette);

		if ($fichier) $texte_effacer = "Supprimer la vignette";
		else $texte_effacer = "Supprimer cette image";
		echo "<br>[<b><a ".$link->getHref().">$texte_effacer</a></b>]\n";

		echo "<p>\n";
	}

	echo "<div style='border: 1px dashed grey; padding: 5px; background-color:#F8E8E8; text-align: center;'>";

	//
	// Si document joint, afficher le document
	//
	if ($fichier) {
		$texte_info = "";

		if ($titre) $texte_info .= "<b><a href='../$fichier'>$titre</a></b><br>\n";
		else $texte_info .= "<b><a href='../$fichier'>Document sans titre</a></b><br>\n";
		if ($type_titre) $texte_info .= propre($type_titre)." - ";
		if ($taille) $texte_info .= taille_en_octets($taille);
		$texte_info .= "<br>\n";
		if ($largeur && $hauteur) $texte_info .= "$largeur&nbsp;x&nbsp;$hauteur pixels<br>\n";

		if ($type_inclus == 'image') {
			$html_size = "";
			if ($largeur && $hauteur) {
				if ($largeur > 170)
					$html_size = " height='".floor($hauteur * 170.0 / $largeur)."' width='170'";
				else
					$html_size = " height='$hauteur' width='$largeur'";
			}
			echo "<a href='../$fichier'><img src='../$fichier'$html_size border='0'></a>\n";
		}
		else {
			echo "<table cellpadding=0 cellspacing=0 border=0 width=35 height=32 align='right'>\n";
			echo "<tr width=35 height=32>\n";
			echo "<td width=35 height=32 background='IMG2/document-vierge.gif' align='left'>\n";
			echo "<table bgcolor='#666666' style='border: solid 1px black; margin-top: 10px; padding-top: 0px; padding-bottom: 0px; padding-left: 3px; padding-right: 3px;' cellspacing=0 border=0>\n";
			echo "<tr><td>\n";
			echo "<font face='verdana,arial,helvetica,sans-serif' color='white' size='1'>$type_ext</font></td></tr></table>\n";
			echo "</td></tr></table>\n";
		}

		echo $texte_info;

		// Modifier le document
		echo "Vous pouvez <a ".newLinkHref("document_edit.php3?id_document=$id_document&url_retour=$url_retour").">";
		echo "modifier ce document";
		if (!$fichier_vignette AND $type_inclus != 'non') {
			echo "</a>\n ou le <br><a ".newLinkHref("articles_edit.php3?id_article=$id_article&transformer_document=oui&id_document=$id_document").">";
			echo "transformer en vignette";
			echo "";
		}
		echo "</a>.\n";

		// Supprimer le document
		$hash = calculer_action_auteur("supp_doc ".$id_document);
		$link = new Link('../spip_image.php3');
		$link->addVar('redirect', $url_retour);
		$link->addVar('hash_id_auteur', $connect_id_auteur);
		$link->addVar('hash', $hash);
		$link->addVar('doc_supp', $id_document);

		$texte_effacer = "Supprimer ce document";
		echo "<p>[<b><a ".$link->getHref().">$texte_effacer</a></b>]\n";
	}
	else {
		// Ajouter un document lie a la vignette
		echo "<a ".newLinkHref("document_edit.php3?id_vignette=$id_vignette&id_article=$id_article&url_retour=$url_retour&new=oui").">\n";
		echo "<img src='IMG2/document.gif' align='middle' alt='[DOC]' width='16' height='12' border='0'> Lier un document &agrave; cette image</a>\n";

		echo "<br><a ".newLinkHref("articles_edit.php3?id_article=$id_article&transformer_vignette=oui&id_document=$id_vignette").">\n";
		echo "<img src='IMG2/document.gif' align='middle' alt='[DOC]' width='16' height='12' border='0'>\n";
		echo "Transformer l'image en document\n";
		echo "</a>\n";
	}

	echo "</div>";

	//
	// Afficher les raccourcis typo
	//
	$nbsp = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	if (!($id_img = $id_document)) $id_img = $id_vignette;
	echo "<br>&lt;IMG$id_img|left&gt;$nbsp\n";
	echo "<br>&lt;center&gt;&lt;IMG$id_img|center&gt;&lt;/center&gt;\n";
	echo "<br>$nbsp&lt;IMG$id_img|right&gt;\n";

	echo "</font>\n";
	echo "</div>\n";
}


function afficher_images($id_article) {
	$query = "SELECT d.* FROM spip_documents AS d, spip_documents_articles AS a ".
		"WHERE a.id_article=$id_article AND d.id_document=a.id_document AND d.id_vignette=0 ".
		"ORDER BY d.id_document";
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result))
	{
		afficher_image_edition($row);
	}
}


debut_page();
debut_gauche();


if ($new != "oui") {
	echo "<p>\n";
	afficher_images($id_article);

	$hash = calculer_action_auteur("ajout_doc");

	echo "<p><div style='border: 1px solid black; padding: 4px; background-color: white;'>";

	echo "<font face=\"verdana, arial, helvetica, sans-serif\" size=\"2\">\n";
	echo "<FORM ACTION='../spip_image.php3' METHOD='POST' ENCTYPE='multipart/form-data'>\n";
	echo "<INPUT NAME='redirect' TYPE=Hidden VALUE='articles_edit.php3'>\n";
	echo "<INPUT NAME='id_article' TYPE=Hidden VALUE=$id_article>\n";
	echo "<INPUT NAME='hash_id_auteur' TYPE=Hidden VALUE=$connect_id_auteur>\n";
	echo "<INPUT NAME='hash' TYPE=Hidden VALUE=$hash>\n";
	echo "<INPUT NAME='ajout_doc' TYPE=Hidden VALUE='oui'>\n";
	if (tester_upload()) {
		echo "<b>Ajouter une image ou un document&nbsp;:</b>";
		echo aide("artimg");
		echo "<br><small><INPUT NAME='image' TYPE=File></small>"; // CLASS='forml'
		echo "   <INPUT NAME='ok' TYPE=Submit VALUE='T&eacute;l&eacute;charger' CLASS='fondo'>";
	}
	else if ($connect_statut == '0minirezo') {
		$myDir = opendir("upload");
		while($entryName = readdir($myDir)){
			if (!ereg("^\.", $entryName) AND eregi("(gif|jpg|png)$", $entryName)) {
				$entryName = addslashes($entryName);
				$afficher .= "\n<OPTION VALUE='ecrire/upload/$entryName'>$entryName";
			}
		}
		closedir($myDir);

		if (strlen($afficher) > 10){
			echo "\nS&eacute;lectionner un fichier&nbsp;:";
			echo "\n<SELECT NAME='image' CLASS='forml' SIZE=1>";
			echo $afficher;
			echo "\n</SELECT>";
			echo "\n  <INPUT NAME='ok' TYPE=Submit VALUE='Choisir' CLASS='fondo'>";
		}
		else {
			echo "Installer des images dans le dossier /ecrire/upload pour pouvoir les s&eacute;lectionner ici.";
		}
	}
	echo "</FORM>";
	echo "</div>";
	echo "</font>\n";
}


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
