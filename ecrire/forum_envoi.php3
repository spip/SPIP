<?php

include ("inc.php3");


$titre_message = ereg_replace("^([^>])", "> \\1", $titre_message);
$nom = entites_html(corriger_caracteres($connect_nom));
$adresse_retour = rawurldecode($adresse_retour);

if ($valider_forum) {
	$titre_message = addslashes(corriger_caracteres($titre_message));
	$texte = addslashes(corriger_caracteres($texte));
	$query = "INSERT INTO spip_forum (titre, texte, date_heure, nom_site, url_site, statut, id_auteur, auteur, email_auteur, id_rubrique, id_parent, id_article, id_breve, id_message, id_syndic) ".
	"VALUES (\"$titre_message\", \"$texte\", NOW(), \"$nom_site\", \"$url_site\", \"$statut\", \"$connect_id_auteur\", \"$nom\", '$connect_email', '$id_rubrique', '$id_parent', '$id_article', '$id_breve', '$id_message', '$id_syndic')";
	$result = spip_query($query);
	
	if ($id_message > 0) {
		$query = "UPDATE spip_auteurs_messages SET vu = 'non' WHERE id_message='$id_message'";
		$result = spip_query($query);
	}
	
	@header("location:$adresse_retour");
	die();
}

debut_page("Envoyer un message", "messagerie");
debut_gauche();
debut_droite();


if ($id_parent) {
	$query = "SELECT * FROM spip_forum WHERE id_forum=$id_parent";
	$result = spip_query($query);
	if ($row = mysql_fetch_array($result)) {
		$id_article = $row['id_article'];
		$id_breve = $row['id_breve'];
		$id_rubrique = $row['id_rubrique'];
		$id_message = $row['id_message'];
		$id_syndic = $row['id_syndic'];
		$statut = $row['statut'];
	}
}


echo "<FORM ACTION='forum_envoi.php3' METHOD='post'>";

if ($modif_forum == "oui") {
	debut_cadre_relief("forum-interne-24.gif");
	echo "<b>".propre($titre_message)."</b>";
	
	echo "<p>".propre($texte);
	
	if (strlen($nom_site)>0) {
		echo "<p><a href='$url_site'>$nom_site</a>";
	}
	
	echo "<p><DIV ALIGN='right'><INPUT CLASS='fondo' TYPE='submit' NAME='valider_forum' VALUE='Message d&eacute;finitif : envoyer'></div>";

	fin_cadre_relief();
}


debut_cadre_formulaire();

echo "<TABLE BORDER=0 CELLPADDING=0 CELLSPACING=0 BACKGROUND='' WIDTH=\"100%\"><TR><TD>";
	icone("Retour", $adresse_retour, "forum-interne-24.gif");
echo "</TD><TD><IMG SRC='img_pack/rien.gif' WIDTH=10 BORDER=0>";
echo "</TD><TD WIDTH=\"100%\">";
echo "<B>Titre :</B><BR>";
$titre_message = entites_html($titre_message);
echo "<INPUT TYPE='text' CLASS='formo' NAME='titre_message' VALUE=\"$titre_message\" SIZE='40'><P>\n";
echo "</TD></TR></TABLE>";

if (!$modif_forum OR $modif_forum == "oui") {
	echo "<INPUT TYPE='Hidden' NAME='modif_forum' VALUE='oui'>\n";
}

echo "<INPUT TYPE='Hidden' NAME='adresse_retour' VALUE=\"$adresse_retour\">\n";
echo "<INPUT TYPE='Hidden' NAME='id_rubrique' VALUE=\"$id_rubrique\">\n";
echo "<INPUT TYPE='Hidden' NAME='id_parent' VALUE=\"$id_parent\">\n";
echo "<INPUT TYPE='Hidden' NAME='id_article' VALUE=\"$id_article\">\n";
echo "<INPUT TYPE='Hidden' NAME='id_breve' VALUE=\"$id_breve\">\n";
echo "<INPUT TYPE='Hidden' NAME='id_message' VALUE=\"$id_message\">\n";
echo "<INPUT TYPE='Hidden' NAME='id_syndic' VALUE=\"$id_syndic\">\n";
echo "<INPUT TYPE='Hidden' NAME='statut' VALUE=\"$statut\">\n";


echo "<p><B>Texte de votre message :</B><BR>";
echo "(Pour cr&eacute;er des paragraphes, laissez simplement des lignes vides.)<BR>";
echo "<TEXTAREA NAME='texte' ROWS='25' CLASS='forml' COLS='40' wrap=soft>";
echo entites_html($texte);
echo "</TEXTAREA><P>\n";

echo "<B>Lien hypertexte :</B><BR>";
echo "(Si votre message se r&eacute;f&egrave;re &agrave; un article publi&eacute; sur le Web, ou &agrave; une page fournissant plus d'informations, veuillez indiquer ci-apr&egrave;s le titre de la page et son adresse URL.)<BR>";
echo "Titre :<BR>";
echo "<INPUT TYPE='text' CLASS='forml' NAME='nom_site' VALUE=\"$nom_site\" SIZE='40'><BR>";

$lien_url="http://";
echo "URL :<BR>";
echo "<INPUT TYPE='text' CLASS='forml' NAME='url_site' VALUE=\"$url_site\" SIZE='40'><P>";

echo "<DIV ALIGN='right'><INPUT CLASS='fondo' TYPE='submit' NAME='Valider' VALUE='Voir ce message avant de le valider'></div>";
echo "</FORM>";


fin_page();
fin_cadre_formulaire();

?>
