<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;
include_spip('inc/presentation');
include_spip('inc/forum'); // pour boutons_controle_forum 

function exec_articles_forum_dist()
{
  global $connect_statut, $debut, $forum_retour, $id_article;

  $id_article = intval($id_article);
  $debut = intval($debut);
  
$query = "SELECT titre, id_rubrique FROM spip_articles WHERE id_article=$id_article";
$result = spip_query($query);

if ($row = spip_fetch_array($result)) {
	$titre = $row["titre"];
	$id_rubrique = $row["id_rubrique"];
}


debut_page($titre, "documents", "articles");



debut_grand_cadre();

afficher_hierarchie($id_rubrique);

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
	icone(_T('icone_retour'), generer_url_ecrire("articles","id_article=$id_article&id_rubrique=$id_rubrique"), "article-24.gif", "rien.gif");

echo "</td>";
echo "<td>" . http_img_pack('rien.gif', " ", "width='10'") ."</td>\n";
echo "<td width='100%'>";
echo _T('texte_messages_publics');
gros_titre($titre);
echo "</td></tr></table>";
echo "<p>";

// Ne pas donner les cles du forum a des non-admins
if (! ($connect_statut=='0minirezo' AND acces_rubrique($id_rubrique)))
	return;

echo "<div class='serif2'>";

// reglages
if (!$debut) $debut = 0;
$pack = 5;		// nb de forums affiches par page
$enplus = 200;	// intervalle affiche autour du debut
$limitdeb = ($debut > $enplus) ? $debut-$enplus : 0;
$limitnb = $debut + $enplus - $limitdeb;

$query_forum = "SELECT id_forum FROM spip_forum WHERE id_article='$id_article' AND id_parent=0 AND statut IN ('publie', 'off', 'prop') LIMIT  $limitnb OFFSET $limitdeb";
$result_forum = spip_query($query_forum);


$i = $limitdeb;
if ($i>0)
	echo "<A href='" . generer_url_ecrire("articles_forum","id_article=$id_article") . "'>0</A> ... | ";
while ($row = spip_fetch_array($result_forum)) {

	// barre de navigation
	if ($i == $pack*floor($i/$pack)) {
		if ($i == $debut)
			echo "<FONT SIZE=3><B>$i</B></FONT>";
		else
			echo "<A href='" . generer_url_ecrire("articles_forum","id_article=$id_article&debut=$i") . "'>$i</A>";
		echo " | ";
	}

	// elements a controler

	$i ++;
}
echo "<A href='" . generer_url_ecrire("articles_forum","id_article=$id_article&debut=$i") . "'>...</A>";

echo "</div>";

if ($connect_statut == "0minirezo") {
	$query_forum = "SELECT pied.*, max(thread.date_heure) AS date
		FROM spip_forum AS pied, spip_forum AS thread
		WHERE pied.id_article='$id_article'
		AND pied.id_parent=0
		AND pied.statut IN ('publie', 'off', 'prop')
		AND thread.id_thread=pied.id_forum
		GROUP BY id_thread
		ORDER BY date DESC LIMIT $debut, $pack";
	$result_forum = spip_query($query_forum);
	afficher_forum($result_forum, $forum_retour, $id_article);
}

echo "</FONT>";

fin_page();
}

?>
