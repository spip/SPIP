<?php

include ("inc.php3");

debut_page();
debut_gauche();
debut_droite();


$titre_message = ereg_replace("^([^>])", "> \\1", $titre_message);
$titre_message = htmlspecialchars($titre_message);
$nom = htmlspecialchars($connect_nom);
$adresse_retour = rawurldecode($adresse_retour);

if ($id_parent) {
	$query = "SELECT * FROM spip_forum WHERE id_forum=$id_parent";
	$result = mysql_query($query);
	if ($row = mysql_fetch_array($result)) {
		$id_article = $row['id_article'];
		$id_breve = $row['id_breve'];
		$id_rubrique = $row['id_rubrique'];
		$id_message = $row['id_message'];
		$id_syndic = $row['id_syndic'];
		$statut = $row['statut'];
	}
}


echo "<FORM ACTION='$adresse_retour' METHOD='post'>";

echo "<TABLE BORDER=0 CELLPADDING=0 CELLSPACING=0 BACKGROUND='' WIDTH=\"100%\"><TR><TD>";
	echo "<A HREF='$adresse_retour' onMouseOver=\"retour.src='IMG2/retour-on.gif'\" onMouseOut=\"retour.src='IMG2/retour-off.gif'\"><img src='IMG2/retour-off.gif' alt='Annuler le nouveau mot-cl&eacute;' width='49' height='46' border='0' name='retour' align='middle'></A>";
echo "<TD><TD><IMG SRC='IMG2/rien.gif' WIDTH=10 BORDER=0>";
echo "</TD><TD WIDTH=\"100%\">";
echo "<B>Titre :</B><BR>";
echo "<INPUT TYPE='text' CLASS='formo' NAME='titre' VALUE=\"$titre_message\" SIZE='40'><P>\n";
echo "</TD></TR></TABLE>";

echo "<INPUT TYPE='Hidden' NAME='forum_id_rubrique' VALUE=\"$id_rubrique\">\n";
echo "<INPUT TYPE='Hidden' NAME='forum_id_parent' VALUE=\"$id_parent\">\n";
echo "<INPUT TYPE='Hidden' NAME='forum_id_article' VALUE=\"$id_article\">\n";
echo "<INPUT TYPE='Hidden' NAME='forum_id_breve' VALUE=\"$id_breve\">\n";
echo "<INPUT TYPE='Hidden' NAME='forum_id_message' VALUE=\"$id_message\">\n";
echo "<INPUT TYPE='Hidden' NAME='forum_id_syndic' VALUE=\"$id_syndic\">\n";
echo "<INPUT TYPE='Hidden' NAME='ajout_forum' VALUE=\"ajout_forum\">\n";
echo "<INPUT TYPE='Hidden' NAME='forum_statut' VALUE=\"$statut\">\n";


echo "<B>Texte de votre message :</B><BR>";
echo "(Pour cr&eacute;er des paragraphes, laissez simplement des lignes vides.)<BR>";
echo "<TEXTAREA NAME='texte' ROWS='25' CLASS='forml' COLS='40' wrap=soft>";
echo $texte;
echo "</TEXTAREA><P>\n";

echo "<B>Lien hypertexte :</B><BR>";
echo "(Si votre message se r&eacute;f&egrave;re &agrave; un article publi&eacute; sur le Web, ou &agrave; une page fournissant plus d'informations, veuillez indiquer ci-apr&egrave;s le titre de la page et son adresse URL.)<BR>";
echo "Titre :<BR>";
echo "<INPUT TYPE='text' CLASS='forml' NAME='nom_site' VALUE=\"$nom_site\" SIZE='40'><BR>";

$lien_url="http://";
echo "URL :<BR>";
echo "<INPUT TYPE='text' CLASS='forml' NAME='url_site' VALUE=\"$url_site\" SIZE='40'><P>";

echo "<INPUT TYPE='hidden' NAME='auteur' VALUE=\"$nom\" SIZE='40'><BR>";

echo "<INPUT TYPE='hidden' NAME='email_auteur' VALUE=\"$connect_email\" SIZE='40'><P>";


echo "<DIV ALIGN='right'><INPUT CLASS='fondo' TYPE='submit' NAME='Valider' VALUE='Poster ce message'>";
echo "</FORM>";


fin_page();

?>
