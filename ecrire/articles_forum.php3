<?php

include ("inc.php3");
include_ecrire ("inc_index.php3");
include_ecrire ("inc_logos.php3");



$query = "SELECT titre, id_rubrique FROM spip_articles WHERE id_article='$id_article'";
$result = spip_query($query);

while($row = spip_fetch_array($result)) {
	$titre = $row["titre"];
	$id_rubrique = $row["id_rubrique"];
}


debut_page($titre, "documents", "articles");



debut_grand_cadre();

afficher_parents($id_rubrique);
$parents="~ <img src='img_pack/racine-site-24.gif' width=24 height=24 align='middle'> <A HREF='naviguer.php3?coll=0'><B>"._T('lien_racine_site')."</B></A> ".aide ("rubhier")."<BR>".$parents;

$parents=ereg_replace("~","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$parents);
$parents=ereg_replace("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ","",$parents);

echo "$parents";

fin_grand_cadre();



debut_gauche();


debut_boite_info();

echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2>";
echo "<P align=left>"._T('info_gauche_suivi_forum');

echo aide ("suiviforum");
echo "</FONT>";

fin_boite_info();


debut_droite();


echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr width='100%'>";
echo "<td>";
	icone(_T('icone_retour'), "articles.php3?id_article=$id_article", "article-24.gif", "rien.gif");

echo "</td>";
	echo "<td><img src='img_pack/rien.gif' width=10></td>\n";
echo "<td width='100%'>";
echo _T('texte_messages_publics');
gros_titre($titre);
echo "</td></tr></table>";
echo "<p>";


echo "<div class='serif2'>";

// reglages
if (!$debut) $debut = 0;
$pack = 5;		// nb de forums affiches par page
$enplus = 200;	// intervalle affiche autour du debut
$limitdeb = ($debut > $enplus) ? $debut-$enplus : 0;
$limitnb = $debut + $enplus - $limitdeb;

$query_forum = "SELECT id_forum FROM spip_forum WHERE id_article='$id_article' AND id_parent=0 AND statut IN ('publie', 'off', 'prop') LIMIT $limitdeb, $limitnb";
$result_forum = spip_query($query_forum);


$i = $limitdeb;
if ($i>0)
	echo "<A HREF='articles_forum.php3?id_article=$id_article&page=$page'>0</A> ... | ";
while ($row = spip_fetch_array($result_forum)) {

	// barre de navigation
	if ($i == $pack*floor($i/$pack)) {
		if ($i == $debut)
			echo "<FONT SIZE=3><B>$i</B></FONT>";
		else
			echo "<A HREF='articles_forum.php3?id_article=$id_article&debut=$i&page=$page'>$i</A>";
		echo " | ";
	}

	// elements a controler

	$i ++;
}
echo "<A HREF='articles_forum.php3?id_article=$id_article&debut=$i&page=$page'>...</A>";

echo $controle;

echo "</div>";



$mots_cles_forums = lire_meta("mots_cles_forums");

if ($connect_statut == "0minirezo") {
	$query_forum = "SELECT * FROM spip_forum WHERE id_article='$id_article' AND id_parent=0 AND statut IN ('publie', 'off', 'prop') ORDER BY date_heure DESC LIMIT $debut, $pack";
	$result_forum = spip_query($query_forum);
	afficher_forum($result_forum, $forum_retour, 'oui');
}

echo "</FONT>";

fin_page();

?>