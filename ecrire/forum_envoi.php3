<?php

include ("inc.php3");


if ($modif_forum != "oui") $titre_message = ereg_replace("^([^>])", "> \\1", $titre_message);
$nom = entites_html(corriger_caracteres($connect_nom));
$adresse_retour = rawurldecode($adresse_retour);

if ($valider_forum AND ($statut!='')) {
	$titre_message = addslashes(corriger_caracteres($titre_message));
	$texte = addslashes(corriger_caracteres($texte));
	$query = "INSERT INTO spip_forum (titre, texte, date_heure, nom_site, url_site, statut, id_auteur, auteur, email_auteur, id_rubrique, id_parent, id_article, id_breve, id_message, id_syndic) ".
	"VALUES (\"$titre_message\", \"$texte\", NOW(), \"$nom_site\", \"$url_site\", \"$statut\", \"$connect_id_auteur\", \"$nom\", '$connect_email', '$id_rubrique', '$id_parent', '$id_article', '$id_breve', '$id_message', '$id_syndic')";
	$result = spip_query($query);

	if ($id_message > 0) {
		$query = "UPDATE spip_auteurs_messages SET vu = 'non' WHERE id_message='$id_message'";
		$result = spip_query($query);
	}

	@header("Location: $adresse_retour");
	die();
}

if ($id_message) debut_page("Envoyer un message", "asuivre", "messagerie");
else debut_page("Envoyer un message", "messagerie");
debut_gauche();
debut_droite();


if ($id_parent) {
	$query = "SELECT * FROM spip_forum WHERE id_forum=$id_parent";
	$result = spip_query($query);
	if ($row = spip_fetch_array($result)) {
		$id_article = $row['id_article'];
		$id_breve = $row['id_breve'];
		$id_rubrique = $row['id_rubrique'];
		$id_message = $row['id_message'];
		$id_syndic = $row['id_syndic'];
		$statut = $row['statut'];
		$titre_parent = stripslashes($row['titre']);
		$texte_parent = stripslashes($row['texte']);
		$auteur_parent = stripslashes($row['auteur']);
		$id_auteur_parent = $row['id_auteur'];
		$date_heure_parent = $row['date_heure'];
		$nom_site_parent = $row['nom_site'];
		$url_site_parent = $row['url_site'];
	}
}


echo "<FORM ACTION='forum_envoi.php3' METHOD='post'>";

if ($titre_parent) {
	debut_cadre_relief("forum-interne-24.gif");
	echo "<table width=100% cellpadding=3 cellspacing=0><tr><td bgcolor='$couleur_foncee'><font face='Verdana,Arial,Helvetica,sans-serif' size=2 color='#FFFFFF'><b>".typo($titre_parent)."</b></font></td></tr>";
	echo "<tr><td bgcolor='#EEEEEE'>";
	echo "<font size='2' face='Georgia,Garamond,Times,serif'>";
	echo "<font face='arial,helvetica'>$date_heure_parent</font>";
	echo " $auteur_parent";

	if ($id_auteur_parent AND $activer_messagerie != "non" AND $connect_activer_messagerie != "non") {
		$bouton = bouton_imessage($id_auteur_parent, $row);
		if ($bouton) echo "&nbsp;".$bouton;
	}

	echo justifier(propre($texte_parent));

	if (strlen($url_site_parent) > 10 AND $nom_site_parent) {
		echo "<p align='left'><font face='Verdana,Arial,Helvetica,sans-serif'><b><a href='$url_site_parent'>$nom_site_parent</a></b></font>";
	}

	echo "</font>";
	echo "</td></tr></table>";
	fin_cadre_relief();

	if ($modif_forum == "oui") {
		echo "<table width=100% cellpadding=0 cellspacing=0 border=0><tr>";
		echo "<td width='10' height='13' valign='top' background='img_pack/forum-vert.gif'><img src='img_pack/rien.gif' alt='' width=10 height=13 border=0></td>\n";
		echo "\n<td width=100% valign='top' rowspan='2'>";
	}
}


if ($modif_forum == "oui") {
	debut_cadre_relief();

	echo "<b>".propre($titre_message)."</b>";
	echo "<p>".propre($texte);

	if (strlen($nom_site)>0) {
		echo "<p><a href='$url_site'>$nom_site</a>";
	}

	echo "<p><div align='right'><INPUT CLASS='fondo' TYPE='submit' NAME='valider_forum' VALUE='Message d&eacute;finitif : envoyer'></div>";

	fin_cadre_relief();
	if ($titre_parent) {
		echo "</td></tr><tr>";
		echo "<td width=10 valign='top' background='img_pack/rien.gif'><img src='img_pack/forum-droite.gif' alt='' width=10 height=13 border=0></td>\n";
		echo "</tr></table>";
	}
}

echo "<p></p>";


debut_cadre_formulaire();

echo "<TABLE BORDER=0 CELLPADDING=0 CELLSPACING=0 BACKGROUND='' WIDTH=\"100%\"><TR><TD>";
	icone("Retour", $adresse_retour, "forum-interne-24.gif");
echo "</TD>";

echo "<TD><IMG SRC='img_pack/rien.gif' WIDTH=10 BORDER=0></td><TD WIDTH=\"100%\">";
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
echo "<TEXTAREA NAME='texte' ROWS='15' CLASS='formo' COLS='40' wrap=soft>";
echo entites_html($texte);
echo "</TEXTAREA><P>\n";

if ($statut != 'perso' AND $options == "avancees") {
	echo "<B>Lien hypertexte :</B><BR>";
	echo "(Si votre message se r&eacute;f&egrave;re &agrave; un article publi&eacute; sur le Web, ou &agrave; une page fournissant plus d'informations, veuillez indiquer ci-apr&egrave;s le titre de la page et son adresse URL.)<BR>";
	echo "Titre :<BR>";
	echo "<INPUT TYPE='text' CLASS='forml' NAME='nom_site' VALUE=\"$nom_site\" SIZE='40'><BR>";

	$lien_url="http://";
	echo "URL :<BR>";
	echo "<INPUT TYPE='text' CLASS='forml' NAME='url_site' VALUE=\"$url_site\" SIZE='40'><P>";
}

echo "<DIV ALIGN='right'><INPUT CLASS='fondo' TYPE='submit' NAME='Valider' VALUE='Voir ce message avant de le valider'></div>";
echo "</FORM>";

fin_page();
fin_cadre_formulaire();

?>
