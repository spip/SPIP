<?php

include ("inc.php3");
include_local ("inc_index.php3");
include_local ("inc_logos.php3");



$query = "SELECT * FROM spip_articles WHERE id_article='$id_article'";
$result = spip_query($query);

while($row = mysql_fetch_array($result)) {
	$titre = $row["titre"];
}


debut_page($titre);
debut_gauche();


debut_boite_info();

echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";
echo "<P align=left>".propre("La page de {suivi des forums} est un outil de gestion de votre site (et non un espace de discussion ou de r&eacute;daction). Elle affiche toutes les contributions du forum public de cet article et vous permet de g&eacute;rer ces contributions.");

echo aide ("suiviforum");
echo "</FONT>";

fin_boite_info();


debut_droite();


echo "<A HREF='articles.php3?id_article=$id_article' onMouseOver=\"retour.src='IMG2/retour-on.gif'\" onMouseOut=\"retour.src='IMG2/retour-off.gif'\"><img src='IMG2/retour-off.gif' alt='Retour &agrave; l article' width='49' height='46' border='0' name='retour' align='left'></A>";
echo "Messages publics de l'article :<BR><FONT SIZE=5 COLOR='$couleur_foncee' FACE='Verdana,Arial,Helvetica,sans-serif'><B>".typo($titre)."</B></FONT>";

echo "<BR><BR>";

$mots_cles_forums = lire_meta("mots_cles_forums");

if ($connect_statut == "0minirezo") {
	$query_forum = "SELECT * FROM spip_forum WHERE id_article='$id_article' AND id_parent=0 AND statut!='redac' ORDER BY date_heure DESC";
	$result_forum = spip_query($query_forum);
	afficher_forum($result_forum, $forum_retour, 'oui');
}

echo "</FONT>";

fin_page();

?>