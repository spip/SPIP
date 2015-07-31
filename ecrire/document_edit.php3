<?php

include ("inc.php3");


//
// Gerer les modifications
//

if ($new == "oui") {
	spip_query("INSERT spip_documents (id_vignette, titre) ".
		"VALUES ('$id_vignette', 'nouveau document')");
	$id_document =  mysql_insert_id();

	if ($id_article) {
		spip_query("INSERT spip_documents_articles (id_document, id_article) ".
			"VALUES ($id_document, $id_article)");
	}
}

if (!$id_article) {
	$result = spip_query("SELECT * FROM spip_documents_articles WHERE id_document=$id_document");
	if ($row = @mysql_fetch_array($result)) {
		$id_article = $row['id_article'];
	}
}


$flag_editable = true; // a affiner ;-))

if ($modif_document == 'oui') {
	$titre = addslashes(corriger_caracteres($titre));
	$descriptif = addslashes(corriger_caracteres($descriptif));
	spip_query("UPDATE spip_documents SET titre=\"$titre\", descriptif=\"$descriptif\" WHERE id_document=$id_document");
}

$result = spip_query("SELECT * FROM spip_documents WHERE id_document=$id_document");
while ($row = mysql_fetch_array($result)){
	$id_document = $row['id_document'];
	$id_vignette = $row['id_vignette'];
	$id_type = $row['id_type'];
	$titre = $row['titre'];
	$descriptif = $row['descriptif'];
	$fichier = $row['fichier'];
	$largeur = $row['largeur'];
	$hauteur = $row['hauteur'];
	$taille = $row['taille'];
	$mode = $row['mode'];
}

if ($mode == 'vignette') {
	$row_vignette = $row;
}
else if ($id_vignette) {
	$result_vignette = spip_query("SELECT * FROM spip_documents WHERE id_document=$id_vignette");
	$row_vignette = @mysql_fetch_array($result_vignette);
}

if ($row_vignette) {
	$fichier_vignette = $row_vignette['fichier'];
	$largeur_vignette = $row_vignette['largeur'];
	$hauteur_vignette = $row_vignette['hauteur'];
	$taille_vignette = $row_vignette['taille'];
}

$result = spip_query("SELECT * FROM spip_types_documents WHERE id_type=$id_type");
if ($row = @mysql_fetch_array($result))	{
	$type_extension = $row['extension'];
	$type_inclus = $row['inclus'];
	$type_titre = $row['titre'];
}


debut_page("document &laquo; $titre &raquo;");
debut_gauche();

debut_boite_info();

	echo "<CENTER>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=1><B>DOCUMENT NUM&Eacute;RO&nbsp;:</B></FONT>";
	echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=6><B>$id_document</B></FONT>";
	echo "</CENTER>";

fin_boite_info();


debut_droite();

if (!$url_retour) {
	if ($id_article) $url_retour = "articles_edit.php3?id_article=$id_article";
	else $url_retour = "index.php3";
}

echo "<a href='$url_retour' onMouseOver=\"retour.src='IMG2/retour-on.gif'\" onMouseOut=\"retour.src='IMG2/retour-off.gif'\"><img src='IMG2/retour-off.gif' alt=\"Retour\" width='49' height='46' border='0' name='retour' align='left'></A>";

echo "G&eacute;rer le document <b>".propre($titre)."</b> :";
echo "<BR><FONT SIZE=5 COLOR='$couleur_foncee' FACE='Verdana,Arial,Helvetica,sans-serif'><B>".typo($titre_article)."</B></FONT>";

echo aide("raccourcis");

echo "<br><br><p><table cellpadding=0 border=0 cellspacing=0 width='100%' background=''>";
echo "<tr width='100%'>";


//
// Affichage de la vignette
//

echo "<td width='200' valign='top'>";

if ($mode == 'vignette') {
	$texte_vignette = "aper&ccedil;u du document";
	$couleur_vignette = "#FEF6E0";
}
else {
	$texte_vignette = "image de pr&eacute;visualisation";
	$couleur_vignette = "#F8E8E8";
}

echo "<div style='border: 1px solid black; padding: 4px; background-color: #fdf4c1;'>\n";
echo "<font face=\"Verdana, Arial, Helvetica, sans-serif\" size=\"2\">\n";
echo "<div align='center'>\n";
echo "<p><font size=1><b>".majuscules($texte_vignette)."</b></font><p>\n";


if ($fichier_vignette) {
	if ($largeur_vignette > 190) {
		$rapport = 190.0 / $largeur_vignette;
		$largeur_vignette = 190;
		$hauteur_vignette = floor($hauteur_vignette * $rapport);
	}
	echo "<img src='../$fichier_vignette' height='$hauteur_vignette' width='$largeur_vignette'>\n";

	if ($mode == 'document') {
		$hash = calculer_action_auteur("supp_doc ".$id_vignette);
		echo "<p><b><a href='../spip_image.php3?redirect=".urlencode("document_edit.php3?id_document=$id_document")."&hash_id_auteur=$connect_id_auteur&hash=$hash&doc_supp=$id_vignette'>SUPPRIMER LA VIGNETTE</a></b><p>";
	}
}
else {
	echo "<div align='left'>Si vous voulez ins&eacute;rer un lien graphique vers ce document, installez ici une vignette de pr&eacute;visualisation.<p>";

	$hash = calculer_action_auteur("ajout_doc");
	echo "<font face=\"verdana, arial, helvetica, sans-serif\" size=\"2\">\n";
	echo "<FORM ACTION='../spip_image.php3' METHOD='POST' ENCTYPE='multipart/form-data'>";
	echo "<INPUT NAME='redirect' TYPE=Hidden VALUE='document_edit.php3'>";
	echo "<INPUT NAME='ajout_doc' TYPE=Hidden VALUE='oui'>";
	echo "<INPUT NAME='id_document' TYPE=Hidden VALUE='$id_document'>";
	echo "<INPUT NAME='id_article' TYPE=Hidden VALUE='$id_article'>";
	echo "<INPUT NAME='mode' TYPE=Hidden VALUE='vignette'>";
	echo "<INPUT NAME='hash_id_auteur' TYPE=Hidden VALUE='$connect_id_auteur'>";
	echo "<INPUT NAME='hash' TYPE=Hidden VALUE='$hash'>";

	if (tester_upload()) {
		echo "<B>T&eacute;l&eacute;charger une nouvelle image&nbsp;:</B>";
		echo aide ("artimg");
		echo "<BR><small><INPUT NAME='image' TYPE=File></small>";
		echo "   <INPUT NAME='ok' TYPE=Submit VALUE='T&eacute;l&eacute;charger' CLASS='fondo'>";
	}
	else if ($connect_statut == '0minirezo') {
		$myDir = opendir("upload");
		while($entryName = readdir($myDir)){
			if (!ereg("^\.",$entryName) AND eregi("(gif|jpg|png)$",$entryName)){
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
	echo "</font>\n";
	echo "</div>";
}


echo "</div>";

echo "</td>";


//
// Afficher le document
//

echo "<td width='10'>&nbsp;</td>";
echo "<td valign='top'>";

if ($fichier OR $titre OR $descriptif) {
	debut_boite_info();
	echo "<FONT SIZE=3 FACE='Verdana,Arial,Helvetica,sans-serif'>";

	if ($fichier) {
		echo "<table cellpadding=0 cellspacing=0 border=0 width=35 height=32 align='right'>\n";
		echo "<tr width=35 height=32>\n";
		echo "<td width=35 height=32 background='IMG2/document-vierge.gif' align='left'>\n";
		echo "<table bgcolor='#666666' style='border: solid 1px black; margin-top: 10px; padding-top: 0px; padding-bottom: 0px; padding-left: 3px; padding-right: 3px;' cellspacing=0 border=0>\n";
		echo "<tr><td><font face='verdana,arial,helvetica,sans-serif' color='white' size='1'>$type_extension</font></td></tr></table>\n";
		echo "</td></tr></table>\n";
	}

	if ($titre) $titre_tmp = $titre;
	else $titre_tmp = "Document li&eacute;";

	if ($fichier) echo "<b><a href='../$fichier'>$titre_tmp</a></b>";
	else echo "<b>$titre_tmp</b>";

	if ($descriptif) echo "<br>".propre($descriptif);

	if ($type_titre) echo "<br><br>Type de document : <b>$type</b>";
	if ($taille) echo "<br>Poids du document : <b>".taille_en_octets($taille)."</b>";
	if ($largeur && $hauteur) echo "<br>Taille : $largeur x $hauteur pixels";

	if ($fichier) {
		echo "<p><div style='border: dashed 1px black; padding: 5px;'>";
		$hash = calculer_action_auteur("supp_doc ".$id_document);
		echo "<font size=1><b><a href='../spip_image.php3?redirect=".urlencode("document_edit.php3?id_document=$id_document&id_article=$id_article")."&hash_id_auteur=$connect_id_auteur&hash=$hash&doc_supp=".$id_document."'>SUPPRIMER LE DOCUMENT</a></b></font>";
		echo "</div>";
	}

	echo "</font>";
	fin_boite_info();
}


//
// Telecharger le document
//

if (!$fichier) {
	echo "<p><div style='border: 1px solid black; padding: 5px; background-color: white;'>";
	echo "<font face=\"verdana, arial, helvetica, sans-serif\" size=\"2\">\n";
	if (tester_upload()) {
	
		$formats = ereg_replace("\n", "", join(file("spip_formats.txt"),", "));
		
		echo "<div style='border: dashed 1px black; padding: 5px;'>";
		$hash = calculer_action_auteur("ajout_doc doc$id_article-$numero");
		echo "<FORM ACTION='../spip_image.php3' METHOD='POST' ENCTYPE='multipart/form-data'>";
		echo "<INPUT NAME='redirect' TYPE=Hidden VALUE='document_edit.php3'>";
		echo "<INPUT NAME='id_article' TYPE=Hidden VALUE=$id_article>";
		echo "<INPUT NAME='id_document' TYPE=Hidden VALUE=$id_document>";
		echo "<INPUT NAME='hash_id_auteur' TYPE=Hidden VALUE=$connect_id_auteur>";
		echo "<INPUT NAME='hash' TYPE=Hidden VALUE=$hash>";
		echo "<INPUT NAME='ajout_doc' TYPE=Hidden VALUE='oui'>";
		echo "<INPUT NAME='num_img' TYPE=Hidden VALUE='$numero'>";
		echo "<B>T&eacute;l&eacute;charger un nouveau document&nbsp;:</B>";
		echo aide ("artimg");
		echo "<BR><small><INPUT NAME='image' TYPE=File CLASS='forml'></small>";
		echo "<br><font size=1>Les formats de fichiers autoris&eacute;s au t&eacute;l&eacute;chargement sur ce site sont&nbsp;: $formats.</font>";
		echo "   <div align='right'><INPUT NAME='ok' TYPE=Submit VALUE='T&eacute;l&eacute;charger' CLASS='fondo'></div>";
		echo "</form>";
		echo "</div>";
	}
	
	if ($connect_statut == '0minirezo') {
		echo "<p><div style='border: dashed 1px black; padding: 5px;'>";
		$myDir = opendir("upload");
		while($entryName = readdir($myDir)){
			if (!ereg("^\.",$entryName) AND $entryName != "remove.txt"){
				$entryName = addslashes($entryName);
				$afficher .= "\n<OPTION VALUE='ecrire/upload/$entryName'>$entryName";
			}
		}
		closedir($myDir);
		if (strlen($afficher) > 10){
			$hash = calculer_action_auteur("ajout_doc doc$id_article-$numero");
			echo "<FORM ACTION='../spip_image.php3' METHOD='POST' ENCTYPE='multipart/form-data'>";
			echo "<INPUT NAME='redirect' TYPE=Hidden VALUE='document_edit.php3'>";
			echo "<INPUT NAME='id_article' TYPE=Hidden VALUE=$id_article>";
			echo "<INPUT NAME='id_document' TYPE=Hidden VALUE=$id_document>";
			echo "<INPUT NAME='hash_id_auteur' TYPE=Hidden VALUE=$connect_id_auteur>";
			echo "<INPUT NAME='hash' TYPE=Hidden VALUE=$hash>";
			echo "<INPUT NAME='ajout_doc' TYPE=Hidden VALUE='oui'>";
			echo "<INPUT NAME='num_img' TYPE=Hidden VALUE='$numero'>";
			echo "<INPUT NAME='doc_selection' TYPE=Hidden VALUE='oui'>";
	
			echo "\n<p><b>ou s&eacute;lectionner un fichier&nbsp;:</b>";
			echo "\n<SELECT NAME='image' CLASS='forml' SIZE=1>";
			echo $afficher;
			echo "\n</SELECT>";
			echo "\n  <div align='right'><INPUT NAME='ok' TYPE=Submit VALUE='Choisir' CLASS='fondo'></div>";
			echo "</form>";
		}
		else {
			echo "<font size=1><b>Installation par FTP</b><br>Vous pouvez installer des images dans le dossier /ecrire/upload pour ensuite les s&eacute;lectionner ici. Cette opération est utile pour les fichiers de grande taille et pour les formats non acceptés en téléchargement via le formulaire Web.</font>";
		} 
		echo "</div>";
	}
}

echo "</font>\n";
echo "</div><p>";


//
// Edition des infos sur le document (titre, descriptif)
//

echo "<font face=\"Verdana, Arial, Helvetica, sans-serif\" size=\"2\">";

echo "<form action='document_edit.php3?id_document=$id_document' method='post'>";
echo "<input type='hidden' name='id_article' value='$id_article'>";
echo "<input type='hidden' name='id_document' value='$id_document'>";
echo "<input type='hidden' name='modif_document' value='oui'>";

$titre = htmlspecialchars($titre);
echo "<B>Titre du document</B>";
echo "<BR><INPUT TYPE='text' NAME='titre' CLASS='formo' VALUE=\"$titre\" SIZE='40'><P>";

echo "Description du document&nbsp;:<br>";
echo "<TEXTAREA NAME='descriptif' CLASS='forml' ROWS='5' COLS='20' wrap=soft>";
echo $descriptif;
echo "</TEXTAREA><P>\n";

echo "<div align='right'>";
echo "<INPUT CLASS='fondo' TYPE='submit' NAME='Valider' VALUE='Valider'>";
echo "</div>";
echo "</form>";
echo "</font>";



echo "</td>";
echo "</tr></table>";

fin_page();

?>
