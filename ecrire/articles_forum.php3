<?php

include ("inc.php3");
include_ecrire ("inc_index.php3");
include_ecrire ("inc_logos.php3");



$query = "SELECT titre, id_rubrique FROM spip_articles WHERE id_article='$id_article'";
$result = spip_query($query);

while($row = mysql_fetch_array($result)) {
	$titre = $row["titre"];
	$id_rubrique = $row["id_rubrique"];
}


debut_page($titre, "documents", "articles");



debut_grand_cadre();

afficher_parents($id_rubrique);
$parents="~ <img src='img_pack/racine-site-24.gif' width=24 height=24 align='middle'> <A HREF='naviguer.php3?coll=0'><B>RACINE DU SITE</B></A> ".aide ("rubhier")."<BR>".$parents;

$parents=ereg_replace("~","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$parents);
$parents=ereg_replace("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ","",$parents);

echo "$parents";

fin_grand_cadre();



debut_gauche();


debut_boite_info();

echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";
echo "<P align=left>".propre("La page de {suivi des forums} est un outil de gestion de votre site (et non un espace de discussion ou de r&eacute;daction). Elle affiche toutes les contributions du forum public de cet article et vous permet de g&eacute;rer ces contributions.");

echo aide ("suiviforum");
echo "</FONT>";

fin_boite_info();


debut_droite();


echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr width='100%'>";
echo "<td>";
	icone("Retour", "articles.php3?id_article=$id_article", "article-24.gif", "rien.gif");

echo "</td>";
	echo "<td><img src='img_pack/rien.gif' width=10></td>\n";
echo "<td width='100%'>";
echo "Messages publics de l'article :";
gros_titre($titre);
echo "</td></tr></table>";
echo "<p>";




$mots_cles_forums = lire_meta("mots_cles_forums");

if ($connect_statut == "0minirezo") {
	$query_forum = "SELECT * FROM spip_forum WHERE id_article='$id_article' AND id_parent=0 AND FIND_IN_SET(statut,'publie,off,prop') ORDER BY date_heure DESC";
	$result_forum = spip_query($query_forum);
	afficher_forum($result_forum, $forum_retour, 'oui');
}

echo "</FONT>";

fin_page();

?>